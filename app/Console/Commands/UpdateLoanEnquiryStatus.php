<?php

namespace App\Console\Commands;

use App\Models\LoanEnquiry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('enquiries:status {reference : Enquiry reference number} {status : new, contacted or closed}')]
#[Description('Update the follow-up status of a loan enquiry')]
class UpdateLoanEnquiryStatus extends Command
{
    public function handle(): int
    {
        $status = $this->argument('status');
        if (! in_array($status, ['new', 'contacted', 'closed'], true)) {
            $this->error('Status must be new, contacted or closed.');

            return self::INVALID;
        }

        $enquiry = LoanEnquiry::query()->where('reference_number', $this->argument('reference'))->first();
        if (! $enquiry) {
            $this->error('Enquiry not found.');

            return self::FAILURE;
        }

        $enquiry->update(['status' => $status]);
        $this->info("Enquiry {$enquiry->reference_number} status updated to {$status}.");

        return self::SUCCESS;
    }
}
