<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LoanCategory extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'description', 'icon', 'enquiry_fields', 'is_enabled', 'display_order'];

    protected function casts(): array
    {
        return ['enquiry_fields' => 'array', 'is_enabled' => 'boolean', 'display_order' => 'integer'];
    }

    /** @return BelongsToMany<LoanProduct, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(LoanProduct::class, 'category_loan_product');
    }
}
