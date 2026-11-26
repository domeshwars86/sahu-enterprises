<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanApplication extends Model
{
    protected $fillable = ['reference', 'loan_enquiry_id', 'loan_product_id', 'lending_partner_id', 'customer_id', 'owner_user_id', 'product_slug', 'lender_name', 'lender_reference', 'requested_amount', 'stage', 'submitted_at', 'staff_notes', 'verified_disbursement_amount', 'verified_disbursed_at'];

    protected function casts(): array
    {
        return ['requested_amount' => 'decimal:2', 'submitted_at' => 'immutable_datetime', 'staff_notes' => 'encrypted', 'verified_disbursement_amount' => 'decimal:2', 'verified_disbursed_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<LoanEnquiry, $this> */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(LoanEnquiry::class, 'loan_enquiry_id');
    }

    /** @return BelongsTo<LoanProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    /** @return BelongsTo<LendingPartner, $this> */
    public function lendingPartner(): BelongsTo
    {
        return $this->belongsTo(LendingPartner::class);
    }

    /** @return HasMany<LoanApplicationStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(LoanApplicationStatusHistory::class);
    }
}
