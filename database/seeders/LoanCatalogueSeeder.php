<?php

namespace Database\Seeders;

use App\Models\LoanCategory;
use App\Models\LoanProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LoanCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('loan_catalogue.categories', []) as $slug => $category) {
            LoanCategory::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'icon' => $category['icon'],
                    'enquiry_fields' => $category['fields'],
                    'is_enabled' => $slug === 'vehicle_finance',
                    'display_order' => array_search($slug, array_keys(config('loan_catalogue.categories')), true),
                ],
            );
        }

        foreach (config('loan_catalogue.products', []) as $displayOrder => $definition) {
            $slug = trim((string) ($definition['slug'] ?? 'loans/'.Str::slug($definition['name'])), '/');
            $categorySlug = $definition['category'];
            $product = LoanProduct::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'category' => $categorySlug,
                    'description' => $definition['description'] ?? 'Availability and eligibility depend on the applicable lender and verified service arrangements.',
                    'is_enabled' => (bool) ($definition['enabled'] ?? false),
                    'is_featured' => (bool) ($definition['featured'] ?? false),
                    'display_order' => $displayOrder,
                ],
            );

            $categorySlugs = array_values(array_unique([$categorySlug, ...($definition['aliases'] ?? [])]));
            $categoryIds = LoanCategory::query()->whereIn('slug', $categorySlugs)->pluck('id')->all();
            $product->categories()->syncWithoutDetaching($categoryIds);

            if (! $product->faqs()->exists()) {
                $product->faqs()->createMany([
                    ['question' => 'Does Sahu Enterprises approve this loan?', 'answer' => 'No. Sahu Enterprises provides assistance only where a service arrangement is available. The applicable lending institution independently decides eligibility, terms, approval and disbursement.', 'display_order' => 1],
                    ['question' => 'What are the eligibility criteria and documents?', 'answer' => 'Eligibility and required documents vary by lender, product and applicant. Confirm the current requirements directly with the applicable lending institution before applying.', 'display_order' => 2],
                ]);
            }
        }
    }
}
