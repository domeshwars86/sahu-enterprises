<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\LendingPartner;
use App\Models\User;
use App\Notifications\CrmAlertNotification;
use App\Services\Admin\AdminRecordScope;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\UpdateLoanApplicationStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LoanApplicationController extends Controller
{
    public function index(Request $request, AdminRecordScope $scope): Response
    {
        $user = $request->user();
        $canManage = $user->hasAdminPermission('applications.manage');
        abort_unless($canManage || $user->hasAdminPermission('applications.assigned'), 403);
        $query = $scope->applications($user)->with(['enquiry:id,reference_number,loan_category', 'product:id,name,slug', 'lendingPartner:id,name', 'statusHistory.actor:id,name']);

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $query->latest()->paginate(25)->withQueryString()->through(fn (LoanApplication $application): array => [
                'id' => $application->id,
                'reference' => $application->reference,
                'enquiryReference' => $application->enquiry?->reference_number,
                'enquiryId' => $application->loan_enquiry_id,
                'product' => $application->product_slug,
                'productName' => $application->product?->name,
                'lenderName' => $application->lender_name,
                'lenderReference' => $application->lender_reference,
                'amount' => $application->requested_amount,
                'stage' => $application->stage,
                'verifiedDisbursementAmount' => $application->verified_disbursement_amount,
                'verifiedDisbursedAt' => $application->verified_disbursed_at?->toIso8601String(),
                'submittedAt' => $application->submitted_at?->toIso8601String(),
                'history' => $application->statusHistory->map(fn ($history): array => ['from' => $history->old_stage, 'to' => $history->new_stage, 'by' => $history->actor?->name ?? 'System', 'reason' => $history->reason, 'at' => $history->created_at?->toIso8601String()])->all(),
            ]),
            'eligibleEnquiries' => $scope->enquiries($user)->with('product:id,name')
                ->whereIn('status', ['ready_for_submission', 'submitted_to_lender', 'under_lender_review', 'approved', 'disbursed'])
                ->whereDoesntHave('loanApplications')
                ->orderByDesc('created_at')->limit(100)->get(['id', 'reference_number', 'loan_category', 'requested_amount', 'loan_product_id'])
                ->map(fn ($enquiry): array => ['id' => $enquiry->id, 'reference_number' => $enquiry->reference_number, 'loan_category' => $enquiry->product?->name ?? $enquiry->loan_category, 'requested_amount' => $enquiry->requested_amount, 'loan_product_id' => $enquiry->loan_product_id]),
            'verifiedLenderMappings' => LendingPartner::query()->where('is_verified', true)->where('is_active', true)->with(['loanProducts' => fn ($query) => $query->wherePivot('partnership_status', 'active')->wherePivot('is_available', true)])->get()->flatMap(fn (LendingPartner $partner): array => $partner->loanProducts->map(fn ($product): array => ['lending_partner_id' => $partner->id, 'lender_name' => $partner->name, 'loan_product_id' => $product->id, 'product_name' => $product->name])->all())->values()->all(),
            'stages' => UpdateLoanApplicationStage::STAGES,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger, AdminRecordScope $scope): RedirectResponse
    {
        $user = $request->user();
        $canManage = $user->hasAdminPermission('applications.manage');
        abort_unless($canManage || $user->hasAdminPermission('applications.assigned'), 403);
        $data = $request->validate([
            'loan_enquiry_id' => ['required', 'integer', 'exists:loan_enquiries,id'],
            'lender_name' => ['nullable', 'string', 'max:160'],
            'lending_partner_id' => ['nullable', 'integer', 'exists:lending_partners,id'],
            'lender_reference' => ['nullable', 'string', 'max:160'],
            'staff_notes' => ['nullable', 'string', 'max:4000'],
        ]);
        $enquiry = $scope->enquiries($user)->findOrFail($data['loan_enquiry_id']);
        abort_unless(in_array($enquiry->status, ['ready_for_submission', 'submitted_to_lender', 'under_lender_review', 'approved', 'disbursed'], true), 422, 'Move the enquiry to a verified application workflow stage before creating an application.');
        abort_unless($canManage || $enquiry->assigned_user_id === $user->id, 403);
        $lendingPartner = null;
        if (! empty($data['lending_partner_id'])) {
            $lendingPartner = LendingPartner::query()->whereKey($data['lending_partner_id'])
                ->where('is_verified', true)->where('is_active', true)
                ->whereHas('loanProducts', fn ($query) => $query->where('loan_products.id', $enquiry->loan_product_id)
                    ->where('lending_partner_products.partnership_status', 'active')->where('lending_partner_products.is_available', true))
                ->firstOrFail();
        }

        $application = DB::transaction(function () use ($data, $enquiry, $user, $lendingPartner): LoanApplication {
            $application = LoanApplication::query()->create([
                'reference' => 'SAH-APP-'.Str::upper(Str::random(8)),
                'loan_enquiry_id' => $enquiry->id,
                'loan_product_id' => $enquiry->loan_product_id,
                'lending_partner_id' => $lendingPartner?->id,
                'customer_id' => $enquiry->customer_id,
                'owner_user_id' => $user->id,
                'product_slug' => $enquiry->product?->slug ?? $enquiry->loan_category,
                'lender_name' => $lendingPartner?->name ?? ($data['lender_name'] ?? null),
                'lender_reference' => $data['lender_reference'] ?? null,
                'requested_amount' => $enquiry->requested_amount,
                'stage' => 'preparation',
                'staff_notes' => $data['staff_notes'] ?? null,
            ]);
            $application->statusHistory()->create(['changed_by' => $user->id, 'old_stage' => null, 'new_stage' => 'preparation', 'reason' => 'Staff explicitly recorded an application workflow.']);

            return $application;
        });

        $auditLogger->record('application.created', $user, 'loan_application', $application->id, ['loan_enquiry_id' => $enquiry->id]);

        return back()->with('success', 'Application record created.');
    }

    public function updateStage(Request $request, LoanApplication $application, UpdateLoanApplicationStage $updater, AdminRecordScope $scope): RedirectResponse
    {
        $user = $request->user();
        abort_unless($scope->applications($user)->whereKey($application->id)->exists() && ($user->hasAdminPermission('applications.manage') || ($application->owner_user_id === $user->id && $user->hasAdminPermission('applications.assigned')) || $user->adminRole?->slug === 'sales-manager'), 403);
        $data = $request->validate([
            'stage' => ['required', 'in:preparation,documents_pending,ready_for_submission,submitted,under_review,approved,rejected,disbursed,closed,cancelled'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        abort_if(in_array($data['stage'], ['submitted', 'approved', 'rejected', 'disbursed'], true) && empty($data['reason']), 422, 'Add a reason or the recorded evidence for this stage.');
        $previousStage = $application->stage;
        $updatedApplication = $updater->update($application, $data['stage'], $data['reason'] ?? '', $user);
        if ($previousStage !== $updatedApplication->stage && $updatedApplication->owner_user_id !== $user->id && ($owner = User::query()->find($updatedApplication->owner_user_id)) && $owner->hasAdminPermission('notifications.view')) {
            $owner->notify(new CrmAlertNotification(['title' => 'Application stage changed', 'message' => 'Application '.$updatedApplication->reference.' moved to '.$updatedApplication->stage.'.', 'url' => '/admin/applications']));
        }

        return back()->with('success', 'Application stage updated.');
    }

    public function recordDisbursement(Request $request, LoanApplication $application, AdminRecordScope $scope, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAdminPermission('applications.manage'), 403);
        abort_unless($scope->applications($user)->whereKey($application->id)->exists(), 404);
        abort_unless($application->stage === 'disbursed', 422, 'Set the application stage to disbursed before recording actual disbursement information.');
        $data = $request->validate([
            'verified_disbursement_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'verified_disbursed_at' => ['required', 'date', 'before_or_equal:today'],
            'evidence_note' => ['required', 'string', 'max:1000'],
        ]);
        $application->update([
            'verified_disbursement_amount' => $data['verified_disbursement_amount'],
            'verified_disbursed_at' => $data['verified_disbursed_at'],
        ]);
        $auditLogger->record('application.disbursement_verified', $user, 'loan_application', $application->id, [
            'amount' => $data['verified_disbursement_amount'],
            'disbursed_at' => $data['verified_disbursed_at'],
            'evidence_note' => $data['evidence_note'],
        ]);

        return back()->with('success', 'Verified disbursement recorded.');
    }
}
