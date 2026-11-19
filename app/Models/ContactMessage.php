<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'reference_number',
        'idempotency_key',
        'name',
        'mobile_number',
        'email',
        'subject',
        'message',
        'privacy_consent',
        'privacy_notice_version',
        'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'mobile_number' => 'encrypted',
            'email' => 'encrypted',
            'message' => 'encrypted',
            'privacy_consent' => 'boolean',
            'consented_at' => 'immutable_datetime',
        ];
    }
}
