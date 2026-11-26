<?php

namespace App\Services\Admin;

use App\Models\LoanEnquiry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateEnquiryStatus
{
    public const STATUSES = ['new', 'contact_attempted', 'contacted', 'qualified', 'documents_pending', 'ready_for_submission', 'submitted_to_lender', 'under_lender_review', 'approved', 'rejected', 'disbursed', 'on_hold', 'closed', 'duplicate', 'withdrawn'];

    public function update(LoanEnquiry $enquiry, string $status, ?User $user = null, ?string $reason = null): LoanEnquiry
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Choose a supported enquiry status.']);
        }

        return DB::transaction(function () use ($enquiry, $status, $user, $reason): LoanEnquiry {
            $enquiry = LoanEnquiry::query()->lockForUpdate()->findOrFail($enquiry->id);
            $oldStatus = $enquiry->status;

            if ($oldStatus === $status) {
                return $enquiry;
            }

            $enquiry->update(['status' => $status]);
            $enquiry->statusHistory()->create([
                'changed_by' => $user?->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'reason' => $reason,
            ]);

            app(AuditLogger::class)->record('enquiry.status_changed', $user, 'loan_enquiry', $enquiry->id, ['old_status' => $oldStatus, 'new_status' => $status]);

            return $enquiry;
        });
    }
}
