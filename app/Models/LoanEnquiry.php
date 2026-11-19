<?php

namespace App\Models;

use Database\Factories\LoanEnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanEnquiry extends Model
{
    /** @use HasFactory<LoanEnquiryFactory> */
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'status',
        'idempotency_key',
        'customer_name',
        'mobile_number',
        'email',
        'city',
        'loan_category',
        'requested_amount',
        'preferred_tenure',
        'employment_category',
        'vehicle_type',
        'vehicle_condition',
        'vehicle_price',
        'down_payment',
        'customer_notes',
        'preferred_callback_time',
        'enquiry_source',
        'contact_consent',
        'consented_at',
        'marketing_consent',
        'privacy_notice_version',
    ];

    protected function casts(): array
    {
        return [
            'customer_name' => 'encrypted',
            'mobile_number' => 'encrypted',
            'email' => 'encrypted',
            'city' => 'encrypted',
            'employment_category' => 'encrypted',
            'customer_notes' => 'encrypted',
            'requested_amount' => 'decimal:2',
            'vehicle_price' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'contact_consent' => 'boolean',
            'consented_at' => 'immutable_datetime',
            'marketing_consent' => 'boolean',
        ];
    }
}
