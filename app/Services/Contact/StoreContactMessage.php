<?php

namespace App\Services\Contact;

use App\Models\ContactMessage;
use Illuminate\Support\Str;

class StoreContactMessage
{
    /** @param array<string, mixed> $data */
    public function store(array $data): ContactMessage
    {
        return ContactMessage::query()->firstOrCreate(
            ['idempotency_key' => $data['idempotency_key']],
            [
                'reference_number' => 'SAH-C-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'name' => $data['name'],
                'mobile_number' => $data['mobile_number'],
                'email' => $data['email'] ?: null,
                'subject' => $data['subject'],
                'message' => $data['message'],
                'privacy_consent' => true,
                'privacy_notice_version' => config('services.sahu.privacy_notice_version'),
                'consented_at' => now(),
            ],
        );
    }
}
