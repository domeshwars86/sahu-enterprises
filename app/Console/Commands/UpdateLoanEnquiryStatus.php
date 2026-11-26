<?php

namespace App\Console\Commands;

use App\Models\LoanEnquiry;
use App\Services\Admin\UpdateEnquiryStatus as UpdateEnquiryStatusService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('enquiries:status {reference : Enquiry reference number} {status : New CRM status}')]
#[Description('Update the follow-up status of a loan enquiry')]
class UpdateLoanEnquiryStatus extends Command
{
    public function handle(UpdateEnquiryStatusService $updater): int
    {
        $status = $this->argument('status');
        if (! in_array($status, UpdateEnquiryStatusService::STATUSES, true)) {
            $this->error('Unsupported enquiry status. Run the command with --help for usage.');

            return self::INVALID;
        }

        $enquiry = LoanEnquiry::query()->where('reference_number', $this->argument('reference'))->first();
        if (! $enquiry) {
            $this->error('Enquiry not found.');

            return self::FAILURE;
        }

        $updater->update($enquiry, $status);
        $this->info("Enquiry {$enquiry->reference_number} status updated to {$status}.");

        return self::SUCCESS;
    }
}
