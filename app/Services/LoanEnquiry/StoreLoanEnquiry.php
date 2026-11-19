<?php

namespace App\Services\LoanEnquiry;

use App\Models\LoanEnquiry;
use Illuminate\Support\Str;

class StoreLoanEnquiry
{
    /** @param array<string, mixed> $validatedData */
    public function store(array $validatedData): LoanEnquiry
    {
        $idempotencyKey = $validatedData['idempotency_key'];
        unset($validatedData['idempotency_key'], $validatedData['contact_consent']);

        return LoanEnquiry::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                ...$validatedData,
                'idempotency_key' => $idempotencyKey,
                'reference_number' => 'SAH-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'enquiry_source' => 'website',
                'contact_consent' => true,
                'consented_at' => now(),
                'marketing_consent' => (bool) ($validatedData['marketing_consent'] ?? false),
                'privacy_notice_version' => config('services.sahu.privacy_notice_version'),
            ],
        );
    }
}
