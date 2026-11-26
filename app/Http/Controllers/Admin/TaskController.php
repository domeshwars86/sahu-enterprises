<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCrmTaskRequest;
use App\Models\CrmTask;
use App\Models\LoanEnquiry;
use App\Models\User;
use App\Services\Admin\AdminRecordScope;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request, AdminRecordScope $scope): Response
    {
        $user = $request->user();
        $canManage = $user->hasAdminPermission('tasks.manage');
        abort_unless($canManage || $user->hasAdminPermission('tasks.assigned'), 403);
        $query = $scope->tasks($user)->with(['enquiry:id,reference_number', 'assignee:id,name']);

        $status = $request->query('status', 'open');
        abort_unless(in_array($status, ['open', 'pending', 'in_progress', 'completed', 'cancelled', 'all'], true), 422);
        $query->when($status === 'open', fn ($builder) => $builder->whereIn('status', ['pending', 'in_progress']))
            ->when(! in_array($status, ['open', 'all'], true), fn ($builder) => $builder->where('status', $status));

        return Inertia::render('Admin/Tasks/Index', [
            'tasks' => $query->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')->orderBy('due_at')->paginate(25)->withQueryString()->through(fn (CrmTask $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'dueAt' => $task->due_at?->toIso8601String(),
                'priority' => $task->priority,
                'status' => $task->status,
                'reference' => $task->enquiry?->reference_number,
                'enquiryId' => $task->loan_enquiry_id,
                'assignee' => $task->assignee?->name,
                'overdue' => $task->due_at?->isPast() && ! in_array($task->status, ['completed', 'cancelled'], true),
            ]),
            'status' => $status,
            'canManage' => $canManage,
            'staff' => $canManage ? User::query()->where('is_active', true)->whereNotNull('admin_role_id')->orderBy('name')->get(['id', 'name']) : [],
            'enquiries' => $scope->enquiries($user)->latest()->limit(100)->get(['id', 'reference_number']),
        ]);
    }

    public function storeGeneral(Request $request, AuditLogger $auditLogger, AdminRecordScope $scope): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('tasks.manage') || $user->hasAdminPermission('tasks.assigned'), 403);
        $data = $request->validate([
            'loan_enquiry_id' => ['required', 'integer', 'exists:loan_enquiries,id'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,normal,high'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $enquiry = $scope->enquiries($user)->findOrFail($data['loan_enquiry_id']);
        abort_unless($user->hasAdminPermission('tasks.manage') || ($enquiry->assigned_user_id === $user->id && $user->hasAdminPermission('tasks.assigned')), 403);
        $assigneeId = $user->hasAdminPermission('tasks.manage') ? ($data['assigned_user_id'] ?? $user->id) : $user->id;
        $assignee = User::query()->with('adminRole')->where('is_active', true)->whereNotNull('admin_role_id')->findOrFail($assigneeId);
        abort_unless($scope->canAssignTo($user, $assignee), 403);
        $task = $enquiry->tasks()->create([...$data, 'assigned_user_id' => $assigneeId, 'created_by' => $user->id]);
        $auditLogger->record('task.created', $user, 'crm_task', $task->id, ['loan_enquiry_id' => $enquiry->id]);

        return back()->with('success', 'Task created.');
    }

    public function store(StoreCrmTaskRequest $request, LoanEnquiry $enquiry, AuditLogger $auditLogger, AdminRecordScope $scope): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        abort_unless($scope->canAccessEnquiry($user, $enquiry), 403);
        $assigneeId = $user->hasAdminPermission('tasks.manage') ? ($data['assigned_user_id'] ?? $user->id) : $user->id;
        $assignee = User::query()->with('adminRole')->where('is_active', true)->whereNotNull('admin_role_id')->findOrFail($assigneeId);
        abort_unless($scope->canAssignTo($user, $assignee), 403);
        $task = $enquiry->tasks()->create([...$data, 'assigned_user_id' => $assigneeId, 'created_by' => $user->id]);
        $auditLogger->record('task.created', $user, 'crm_task', $task->id, ['loan_enquiry_id' => $enquiry->id]);

        return back()->with('success', 'Task created.');
    }

    public function update(Request $request, CrmTask $task, AuditLogger $auditLogger, AdminRecordScope $scope): RedirectResponse
    {
        $user = $request->user();
        abort_unless($scope->canAccessTask($user, $task) && ($user->hasAdminPermission('tasks.manage') || ($task->assigned_user_id === $user->id && $user->hasAdminPermission('tasks.assigned')) || $user->adminRole?->slug === 'sales-manager'), 403);
        $data = $request->validate(['status' => ['required', 'in:pending,in_progress,completed,cancelled']]);
        $task->update(['status' => $data['status'], 'completed_at' => $data['status'] === 'completed' ? now() : null]);
        $auditLogger->record('task.updated', $user, 'crm_task', $task->id, ['status' => $data['status']]);

        return back()->with('success', 'Task updated.');
    }
}
