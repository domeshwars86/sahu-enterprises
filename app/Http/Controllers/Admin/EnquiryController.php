<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnquiryNoteRequest;
use App\Http\Requests\Admin\StoreFollowUpRequest;
use App\Http\Requests\Admin\UpdateEnquiryStatusRequest;
use App\Models\FollowUp;
use App\Models\LoanEnquiry;
use App\Models\User;
use App\Notifications\CrmAlertNotification;
use App\Services\Admin\AdminRecordScope;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\UpdateEnquiryStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EnquiryController extends Controller
{
    public function index(Request $request, AdminRecordScope $scope): Response
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('enquiries.view') || $user->hasAdminPermission('enquiries.view_assigned'), 403);
        $query = $scope->enquiries($user)->with(['assignee:id,name', 'product:id,name']);
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $fingerprints = $this->fingerprints($search);
            $query->where(function ($builder) use ($search, $fingerprints): void {
                $builder->where('reference_number', 'like', '%'.$search.'%');
                foreach ($fingerprints as $column => $fingerprint) {
                    $builder->orWhere($column, $fingerprint);
                }
            });
        }

        $query->when($request->query('status'), fn ($builder, $status) => $builder->where('status', $status))
            ->when($request->query('category'), fn ($builder, $category) => $builder->where('loan_category', $category))
            ->when($request->boolean('unassigned'), fn ($builder) => $builder->whereNull('assigned_user_id'))
            ->when($request->query('assigned_user_id'), fn ($builder, $id) => $builder->where('assigned_user_id', $id));

        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');
        abort_unless(in_array($sort, ['created_at', 'status', 'loan_category', 'requested_amount'], true), 422);
        abort_unless(in_array($direction, ['asc', 'desc'], true), 422);
        $canSeeSensitive = $user->hasAdminPermission('enquiries.view_sensitive');
        $enquiries = $query->orderBy($sort, $direction)->paginate(25)->withQueryString()->through(fn (LoanEnquiry $enquiry): array => [
            'id' => $enquiry->id,
            'reference' => $enquiry->reference_number,
            'customer' => $this->displayName($enquiry->customer_name, $canSeeSensitive),
            'mobile' => $canSeeSensitive ? $enquiry->mobile_number : $this->maskMobile($enquiry->mobile_number),
            'email' => $canSeeSensitive ? $enquiry->email : $this->maskEmail($enquiry->email),
            'category' => $enquiry->product?->name ?? $enquiry->loan_category,
            'product' => $enquiry->product?->name,
            'amount' => $enquiry->requested_amount,
            'city' => $canSeeSensitive ? $enquiry->city : null,
            'status' => $enquiry->status,
            'assignee' => $enquiry->assignee?->name,
            'createdAt' => $enquiry->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/Enquiries/Index', [
            'enquiries' => $enquiries,
            'filters' => $request->only(['search', 'status', 'category', 'assigned_user_id', 'unassigned', 'sort', 'direction']),
            'statuses' => UpdateEnquiryStatus::STATUSES,
            'staff' => User::query()->where('is_active', true)->whereNotNull('admin_role_id')->when($user->adminRole?->slug === 'sales-manager', fn ($builder) => $builder->whereIn('id', $user->operationalTeamUserIds()))->orderBy('name')->get(['id', 'name']),
            'canAssign' => $user->hasAdminPermission('enquiries.assign'),
        ]);
    }

    public function show(LoanEnquiry $enquiry, Request $request, AdminRecordScope $scope): Response
    {
        $user = $request->user();
        abort_unless($scope->canAccessEnquiry($user, $enquiry), 403);
        $enquiry->load(['assignee:id,name', 'product:id,name', 'notes.author:id,name', 'statusHistory.actor:id,name', 'followUps.assignee:id,name']);
        $canSeeSensitive = $user->hasAdminPermission('enquiries.view_sensitive');

        return Inertia::render('Admin/Enquiries/Show', [
            'enquiry' => [
                'id' => $enquiry->id,
                'reference' => $enquiry->reference_number,
                'customer' => $this->displayName($enquiry->customer_name, $canSeeSensitive),
                'mobile' => $canSeeSensitive ? $enquiry->mobile_number : $this->maskMobile($enquiry->mobile_number),
                'email' => $canSeeSensitive ? $enquiry->email : $this->maskEmail($enquiry->email),
                'city' => $canSeeSensitive ? $enquiry->city : null,
                'category' => $enquiry->loan_category,
                'product' => $enquiry->product?->name,
                'productDetails' => $canSeeSensitive ? ($enquiry->product_details ?? []) : [],
                'amount' => $enquiry->requested_amount,
                'tenure' => $enquiry->preferred_tenure,
                'notes' => $canSeeSensitive ? $enquiry->customer_notes : null,
                'status' => $enquiry->status,
                'priority' => $enquiry->priority,
                'assigneeId' => $enquiry->assigned_user_id,
                'assignee' => $enquiry->assignee?->name,
                'source' => $enquiry->enquiry_source,
                'createdAt' => $enquiry->created_at?->toIso8601String(),
                'statusHistory' => $enquiry->statusHistory->map(fn ($history): array => ['from' => $history->old_status, 'to' => $history->new_status, 'by' => $history->actor?->name ?? 'System', 'reason' => $history->reason, 'at' => $history->created_at?->toIso8601String()])->all(),
                'notesHistory' => $enquiry->notes->map(fn ($note): array => ['id' => $note->id, 'body' => $note->body, 'author' => $note->author?->name ?? 'Staff', 'at' => $note->created_at?->toIso8601String()])->all(),
                'followUps' => $enquiry->followUps->map(fn (FollowUp $followUp): array => ['id' => $followUp->id, 'purpose' => $followUp->purpose, 'scheduledAt' => $followUp->scheduled_at?->toIso8601String(), 'status' => $followUp->status, 'assignee' => $followUp->assignee?->name])->all(),
            ],
            'statuses' => UpdateEnquiryStatus::STATUSES,
            'staff' => User::query()->where('is_active', true)->whereNotNull('admin_role_id')->when($user->adminRole?->slug === 'sales-manager', fn ($builder) => $builder->whereIn('id', $user->operationalTeamUserIds()))->orderBy('name')->get(['id', 'name']),
            'permissions' => [
                'status' => $user->hasAdminPermission('enquiries.status') || ($enquiry->assigned_user_id === $user->id && $user->hasAdminPermission('enquiries.status_assigned')),
                'note' => $user->hasAdminPermission('enquiries.note') || ($enquiry->assigned_user_id === $user->id && $user->hasAdminPermission('enquiries.note_assigned')),
                'followup' => $user->hasAdminPermission('followups.manage') || ($enquiry->assigned_user_id === $user->id && $user->hasAdminPermission('followups.assigned')),
                'task' => $user->hasAdminPermission('tasks.manage') || ($enquiry->assigned_user_id === $user->id && $user->hasAdminPermission('tasks.assigned')),
                'assign' => $user->hasAdminPermission('enquiries.assign'),
            ],
        ]);
    }

    public function updateStatus(UpdateEnquiryStatusRequest $request, LoanEnquiry $enquiry, UpdateEnquiryStatus $updater): RedirectResponse
    {
        $updater->update($enquiry, $request->validated('status'), $request->user(), $request->validated('reason'));

        return back()->with('success', 'Enquiry status updated.');
    }

    public function storeNote(StoreEnquiryNoteRequest $request, LoanEnquiry $enquiry, AuditLogger $auditLogger): RedirectResponse
    {
        DB::transaction(function () use ($request, $enquiry, $auditLogger): void {
            $note = $enquiry->notes()->create(['user_id' => $request->user()->id, 'body' => $request->validated('body')]);
            $auditLogger->record('enquiry.note_added', $request->user(), 'loan_enquiry', $enquiry->id, ['note_id' => $note->id]);
        });

        return back()->with('success', 'Note added.');
    }

    public function assign(Request $request, LoanEnquiry $enquiry, AuditLogger $auditLogger, AdminRecordScope $scope): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('enquiries.assign'), 403);
        $data = $request->validate(['assigned_user_id' => ['nullable', 'integer', 'exists:users,id'], 'priority' => ['nullable', 'integer', 'between:1,3']]);
        abort_unless($scope->canAccessEnquiry($request->user(), $enquiry), 403);
        $assignee = isset($data['assigned_user_id']) ? User::query()->where('is_active', true)->whereNotNull('admin_role_id')->findOrFail($data['assigned_user_id']) : null;
        abort_if($assignee && ! $scope->canAssignTo($request->user(), $assignee), 403);
        $enquiry->update(['assigned_user_id' => $assignee?->id, 'priority' => $data['priority'] ?? $enquiry->priority]);
        $auditLogger->record('enquiry.assigned', $request->user(), 'loan_enquiry', $enquiry->id, ['assigned_user_id' => $assignee?->id]);
        if ($assignee && $assignee->id !== $request->user()->id && $assignee->hasAdminPermission('notifications.view')) {
            $assignee->notify(new CrmAlertNotification(['title' => 'Lead assigned', 'message' => 'Enquiry '.$enquiry->reference_number.' was assigned to you.', 'url' => '/admin/enquiries/'.$enquiry->id]));
        }

        return back()->with('success', 'Lead assignment saved.');
    }

    public function storeFollowUp(StoreFollowUpRequest $request, LoanEnquiry $enquiry, AuditLogger $auditLogger, AdminRecordScope $scope): RedirectResponse
    {
        $data = $request->validated();
        abort_unless($scope->canAccessEnquiry($request->user(), $enquiry), 403);
        $assignedUserId = $request->user()->hasAdminPermission('followups.manage') ? ($data['assigned_user_id'] ?? $request->user()->id) : $request->user()->id;
        $assignee = User::query()->with('adminRole')->where('is_active', true)->whereNotNull('admin_role_id')->findOrFail($assignedUserId);
        abort_unless($scope->canAssignTo($request->user(), $assignee), 403);
        $followUp = $enquiry->followUps()->create([...$data, 'assigned_user_id' => $assignedUserId, 'created_by' => $request->user()->id]);
        $auditLogger->record('followup.scheduled', $request->user(), 'follow_up', $followUp->id, ['loan_enquiry_id' => $enquiry->id]);

        return back()->with('success', 'Follow-up scheduled.');
    }

    /** @return array<string, string> */
    private function fingerprints(string $term): array
    {
        $key = (string) config('app.key');
        $normalized = ['name_lookup' => mb_strtolower(trim($term)), 'mobile_lookup' => preg_replace('/\D+/', '', $term), 'email_lookup' => mb_strtolower(trim($term)), 'city_lookup' => mb_strtolower(trim($term))];

        return array_filter(array_map(fn (string $value): string => hash_hmac('sha256', $value, $key), $normalized), fn (string $value, string $field): bool => $normalized[$field] !== '', ARRAY_FILTER_USE_BOTH);
    }

    private function displayName(?string $name, bool $sensitive): string
    {
        if ($sensitive || ! $name) {
            return $name ?? '—';
        }

        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return ($parts[0] ?? 'Customer').(isset($parts[1]) ? ' '.mb_substr($parts[1], 0, 1).'.' : '');
    }

    private function maskMobile(?string $mobile): ?string
    {
        return $mobile ? str_repeat('•', max(0, mb_strlen($mobile) - 4)).mb_substr($mobile, -4) : null;
    }

    private function maskEmail(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }

        [$name, $domain] = explode('@', $email, 2);

        return mb_substr($name, 0, 1).str_repeat('•', max(2, mb_strlen($name) - 1)).'@'.$domain;
    }
}
