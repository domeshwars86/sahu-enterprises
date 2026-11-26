<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FollowUp;
use App\Services\Admin\AdminRecordScope;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request, AdminRecordScope $scope): Response
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('customers.view'), 403);
        $canSeeSensitive = $user->hasAdminPermission('enquiries.view_sensitive');
        $visibleEnquiries = $scope->enquiries($user)->select('loan_enquiries.id');
        $customers = Customer::query()
            ->whereHas('enquiries', fn ($query) => $query->whereIn('loan_enquiries.id', $visibleEnquiries))
            ->with(['enquiries' => fn ($query) => $query->whereIn('loan_enquiries.id', $visibleEnquiries)->latest()->with('assignee:id,name')])
            ->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers->through(fn (Customer $customer): array => [
                'id' => $customer->id,
                'enquiryCount' => $customer->enquiries->count(),
                'communicationPreference' => $customer->communication_preference,
                'enquiries' => $customer->enquiries->map(fn ($enquiry): array => [
                    'id' => $enquiry->id,
                    'reference' => $enquiry->reference_number,
                    'customerName' => $canSeeSensitive ? $enquiry->customer_name : $this->maskName($enquiry->customer_name),
                    'mobile' => $canSeeSensitive ? $enquiry->mobile_number : $this->maskMobile($enquiry->mobile_number),
                    'email' => $canSeeSensitive ? $enquiry->email : $this->maskEmail($enquiry->email),
                    'category' => $enquiry->loan_category,
                    'status' => $enquiry->status,
                    'assignedTo' => $enquiry->assignee?->name,
                    'createdAt' => $enquiry->created_at?->toIso8601String(),
                ])->values()->all(),
            ]),
        ]);
    }

    public function show(Request $request, Customer $customer, AdminRecordScope $scope): Response
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('customers.view'), 403);
        $canSeeSensitive = $user->hasAdminPermission('enquiries.view_sensitive');
        $visibleEnquiries = $scope->enquiries($user)->where('customer_id', $customer->id)->with([
            'assignee:id,name',
            'notes' => fn ($query) => $query->latest(),
            'statusHistory' => fn ($query) => $query->with('actor:id,name')->latest(),
            'followUps' => fn ($query) => $query->latest(),
        ])->latest()->get();
        abort_if($visibleEnquiries->isEmpty(), 404);
        $followUps = $scope->followUps($user)->whereIn('loan_enquiry_id', $visibleEnquiries->pluck('id'))->with('assignee:id,name')->latest('scheduled_at')->get();

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'communicationPreference' => $customer->communication_preference,
                'privacyRequestAt' => $customer->privacy_request_at?->toIso8601String(),
                'enquiries' => $visibleEnquiries->map(fn ($enquiry): array => [
                    'id' => $enquiry->id,
                    'reference' => $enquiry->reference_number,
                    'customerName' => $canSeeSensitive ? $enquiry->customer_name : $this->maskName($enquiry->customer_name),
                    'mobile' => $canSeeSensitive ? $enquiry->mobile_number : $this->maskMobile($enquiry->mobile_number),
                    'email' => $canSeeSensitive ? $enquiry->email : $this->maskEmail($enquiry->email),
                    'city' => $canSeeSensitive ? $enquiry->city : null,
                    'category' => $enquiry->loan_category,
                    'amount' => $enquiry->requested_amount,
                    'tenure' => $enquiry->preferred_tenure,
                    'status' => $enquiry->status,
                    'assignedTo' => $enquiry->assignee?->name,
                    'createdAt' => $enquiry->created_at?->toIso8601String(),
                    'notes' => $enquiry->notes->map(fn ($note): array => ['body' => $note->body, 'author' => $note->author?->name, 'createdAt' => $note->created_at?->toIso8601String()])->all(),
                    'statusHistory' => $enquiry->statusHistory->map(fn ($entry): array => ['oldStatus' => $entry->old_status, 'newStatus' => $entry->new_status, 'reason' => $entry->reason, 'changedBy' => $entry->actor?->name, 'createdAt' => $entry->created_at?->toIso8601String()])->all(),
                ])->values()->all(),
                'followUps' => $followUps->map(fn (FollowUp $followUp): array => ['purpose' => $followUp->purpose, 'status' => $followUp->status, 'scheduledAt' => $followUp->scheduled_at?->toIso8601String(), 'assignedTo' => $followUp->assignee?->name])->all(),
                'canManage' => $user->hasAdminPermission('customers.manage'),
            ],
        ]);
    }

    public function updatePreference(Request $request, Customer $customer, AdminRecordScope $scope, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('customers.manage'), 403);
        abort_unless($scope->enquiries($user)->where('customer_id', $customer->id)->exists(), 404);
        $validated = $request->validate(['communication_preference' => ['required', 'string', 'in:phone,email,whatsapp,no_marketing,unspecified']]);
        $customer->update(['communication_preference' => $validated['communication_preference']]);
        $auditLogger->record('customer.communication_preference_updated', $user, 'customer', $customer->id, ['preference' => $validated['communication_preference']]);

        return back()->with('success', 'Communication preference updated.');
    }

    public function recordPrivacyRequest(Request $request, Customer $customer, AdminRecordScope $scope, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('customers.manage'), 403);
        abort_unless($scope->enquiries($user)->where('customer_id', $customer->id)->exists(), 404);
        $validated = $request->validate(['request_type' => ['required', 'string', 'in:access,correction,deletion,marketing_opt_out']]);
        $customer->update(['privacy_request_at' => now()]);
        $auditLogger->record('customer.privacy_request_recorded', $user, 'customer', $customer->id, ['request_type' => $validated['request_type']]);

        return back()->with('success', 'Privacy request recorded for follow-up.');
    }

    private function maskName(?string $name): string
    {
        if (! $name) {
            return '—';
        }

        return (preg_split('/\s+/', trim($name))[0] ?? 'Customer').' …';
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
