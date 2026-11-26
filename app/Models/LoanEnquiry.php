<?php

namespace App\Models;

use Database\Factories\LoanEnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'loan_product_id',
        'product_details',
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
            'product_details' => 'encrypted:array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<LoanProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    /** @return HasMany<EnquiryNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(EnquiryNote::class);
    }

    /** @return HasMany<EnquiryStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(EnquiryStatusHistory::class);
    }

    /** @return HasMany<FollowUp, $this> */
    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    /** @return HasMany<CrmTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class);
    }

    /** @return HasMany<LoanApplication, $this> */
    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }
}
