<?php

namespace App\Console\Commands;

use App\Models\LoanEnquiry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('enquiries:list {--status= : Filter by new, contacted or closed} {--limit=20 : Number of records to show (1-100)}')]
#[Description('List recent loan enquiry references without displaying customer contact details')]
class ListLoanEnquiries extends Command
{
    public function handle(): int
    {
        $status = $this->option('status');
        if ($status && ! in_array($status, ['new', 'contacted', 'closed'], true)) {
            $this->error('Status must be new, contacted or closed.');

            return self::INVALID;
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 100) {
            $this->error('Limit must be between 1 and 100.');

            return self::INVALID;
        }

        $enquiries = LoanEnquiry::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['reference_number', 'created_at', 'loan_category', 'status']);

        $this->table(['Reference', 'Received', 'Category', 'Status'], $enquiries->map(fn (LoanEnquiry $enquiry): array => [
            $enquiry->reference_number,
            $enquiry->created_at?->toDateTimeString(),
            $enquiry->loan_category,
            $enquiry->status,
        ])->all());

        return self::SUCCESS;
    }
}
