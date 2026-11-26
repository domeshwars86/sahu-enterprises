<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Services\Admin\AdminRecordScope;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FollowUpController extends Controller
{
    public function index(Request $request, AdminRecordScope $scope): Response
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('followups.manage') || $user->hasAdminPermission('followups.assigned'), 403);
        $query = $scope->followUps($user)->with(['enquiry:id,reference_number', 'assignee:id,name']);

        $view = $request->query('view', 'pending');
        abort_unless(in_array($view, ['pending', 'completed', 'cancelled', 'all'], true), 422);
        $query->when($view !== 'all', fn ($builder) => $builder->where('status', $view));

        return Inertia::render('Admin/FollowUps/Index', [
            'followUps' => $query->orderByRaw("CASE WHEN status = 'pending' AND scheduled_at < ? THEN 0 ELSE 1 END", [now()])->orderBy('scheduled_at')->paginate(25)->withQueryString()->through(fn (FollowUp $followUp): array => [
                'id' => $followUp->id,
                'purpose' => $followUp->purpose,
                'scheduledAt' => $followUp->scheduled_at?->toIso8601String(),
                'status' => $followUp->status,
                'priority' => $followUp->priority,
                'reference' => $followUp->enquiry?->reference_number,
                'enquiryId' => $followUp->loan_enquiry_id,
                'assignee' => $followUp->assignee?->name,
                'overdue' => $followUp->status === 'pending' && $followUp->scheduled_at?->isPast(),
            ]),
            'view' => $view,
            'canManage' => $user->hasAdminPermission('followups.manage'),
        ]);
    }

    public function update(Request $request, FollowUp $followUp, AuditLogger $auditLogger, AdminRecordScope $scope): RedirectResponse
    {
        $user = $request->user();
        $canManage = $user->hasAdminPermission('followups.manage');
        abort_unless($scope->canAccessFollowUp($user, $followUp) && ($canManage || ($followUp->assigned_user_id === $user->id && $user->hasAdminPermission('followups.assigned')) || $user->adminRole?->slug === 'sales-manager'), 403);
        $data = $request->validate(['status' => ['required', 'in:pending,completed,cancelled'], 'scheduled_at' => ['nullable', 'date', 'after:now']]);
        $followUp->update([
            'status' => $data['status'],
            'scheduled_at' => $data['scheduled_at'] ?? $followUp->scheduled_at,
            'completed_at' => $data['status'] === 'completed' ? now() : null,
        ]);
        $auditLogger->record('followup.updated', $user, 'follow_up', $followUp->id, ['status' => $data['status']]);

        return back()->with('success', 'Follow-up updated.');
    }
}
