<?php

namespace App\Console\Commands;

use App\Models\LoanEnquiry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('enquiries:show {reference : Enquiry reference number}')]
#[Description('Display a single enquiry for an authorized server operator')]
class ShowLoanEnquiry extends Command
{
    public function handle(): int
    {
        $enquiry = LoanEnquiry::query()->where('reference_number', $this->argument('reference'))->first();

        if (! $enquiry) {
            $this->error('Enquiry not found.');

            return self::FAILURE;
        }

        $this->table(['Field', 'Value'], [
            ['Reference', $enquiry->reference_number],
            ['Received', $enquiry->created_at?->toDateTimeString()],
            ['Status', $enquiry->status],
            ['Customer name', $enquiry->customer_name],
            ['Mobile number', $enquiry->mobile_number],
            ['Email', $enquiry->email],
            ['City', $enquiry->city],
            ['Loan category', $enquiry->loan_category],
            ['Requested amount', $enquiry->requested_amount],
            ['Preferred tenure', $enquiry->preferred_tenure],
            ['Employment category', $enquiry->employment_category],
            ['Vehicle type', $enquiry->vehicle_type],
            ['Vehicle condition', $enquiry->vehicle_condition],
            ['Vehicle price', $enquiry->vehicle_price],
            ['Down payment', $enquiry->down_payment],
            ['Preferred callback time', $enquiry->preferred_callback_time],
            ['Customer notes', $enquiry->customer_notes],
            ['Contact consent', $enquiry->contact_consent ? 'Yes' : 'No'],
            ['Marketing consent', $enquiry->marketing_consent ? 'Yes' : 'No'],
        ]);

        return self::SUCCESS;
    }
}
