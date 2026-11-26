<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['contact_fingerprint', 'communication_preference', 'privacy_request_at'];

    protected function casts(): array
    {
        return ['privacy_request_at' => 'immutable_datetime'];
    }

    /** @return HasMany<LoanEnquiry, $this> */
    public function enquiries(): HasMany
    {
        return $this->hasMany(LoanEnquiry::class);
    }
}
