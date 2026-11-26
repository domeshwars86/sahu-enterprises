<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'category', 'description', 'is_enabled', 'is_featured', 'display_order',
        'eligibility', 'documentation', 'enquiry_fields', 'seo_title', 'seo_description', 'calculator_key',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean', 'is_featured' => 'boolean', 'display_order' => 'integer',
            'documentation' => 'array', 'enquiry_fields' => 'array',
        ];
    }

    /** @return BelongsToMany<LoanCategory, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(LoanCategory::class, 'category_loan_product')->orderBy('loan_categories.display_order');
    }

    /** @return HasMany<LoanProductFaq, $this> */
    public function faqs(): HasMany
    {
        return $this->hasMany(LoanProductFaq::class)->orderBy('display_order')->orderBy('id');
    }

    /** @return BelongsToMany<LoanProduct, $this> */
    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'loan_product_related', 'loan_product_id', 'related_loan_product_id');
    }

    /** @return BelongsToMany<LendingPartner, $this> */
    public function lendingPartners(): BelongsToMany
    {
        return $this->belongsToMany(LendingPartner::class, 'lending_partner_products')
            ->withPivot(['partnership_status', 'is_available', 'internal_sourcing_info'])
            ->withTimestamps();
    }
}
