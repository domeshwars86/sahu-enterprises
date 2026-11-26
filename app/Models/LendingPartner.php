<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LendingPartner extends Model
{
    protected $fillable = ['name', 'institution_category', 'authorized_contact', 'supported_products', 'internal_notes', 'is_verified', 'is_active'];

    protected function casts(): array
    {
        return ['authorized_contact' => 'encrypted', 'supported_products' => 'array', 'internal_notes' => 'encrypted', 'is_verified' => 'boolean', 'is_active' => 'boolean'];
    }

    /** @return BelongsToMany<LoanProduct, $this> */
    public function loanProducts(): BelongsToMany
    {
        return $this->belongsToMany(LoanProduct::class, 'lending_partner_products')
            ->withPivot(['partnership_status', 'is_available', 'internal_sourcing_info'])
            ->withTimestamps();
    }
}
