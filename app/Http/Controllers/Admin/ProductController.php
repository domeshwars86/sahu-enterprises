<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanCategory;
use App\Models\LoanProduct;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAdminPermission('products.manage'), 403);

        return Inertia::render('Admin/Products/Index', [
            'categories' => LoanCategory::query()->orderBy('display_order')->orderBy('name')->get(['id', 'name', 'slug', 'is_enabled']),
            'products' => LoanProduct::query()->with(['categories:id,name,slug', 'faqs:id,loan_product_id,question,answer,display_order', 'relatedProducts:id,name'])
                ->orderBy('display_order')->orderBy('name')->get()->map(fn (LoanProduct $product): array => [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'category' => $product->category,
                    'description' => $product->description,
                    'eligibility' => $product->eligibility,
                    'documentation' => $product->documentation ?? [],
                    'seoTitle' => $product->seo_title,
                    'seoDescription' => $product->seo_description,
                    'is_enabled' => $product->is_enabled,
                    'is_featured' => $product->is_featured,
                    'categories' => $product->categories->pluck('id')->all(),
                    'categoryNames' => $product->categories->pluck('name')->all(),
                    'faqs' => $product->faqs->map(fn ($faq): array => ['question' => $faq->question, 'answer' => $faq->answer])->all(),
                    'relatedProductIds' => $product->relatedProducts->pluck('id')->all(),
                ]),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('products.manage'), 403);
        $data = $this->validated($request);
        abort_if($data['is_enabled'], 422, 'Create products hidden, then verify an active lender relationship before publishing.');
        $product = DB::transaction(fn (): LoanProduct => $this->persist(null, $data));
        $auditLogger->record('product.created', $request->user(), 'loan_product', $product->id, ['slug' => $product->slug]);

        return back()->with('success', 'Loan product created as a hidden catalogue entry.');
    }

    public function update(Request $request, LoanProduct $product, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('products.manage'), 403);
        $data = $this->validated($request, $product);

        if ($data['is_enabled'] && ! $product->is_enabled) {
            abort_unless($product->lendingPartners()->wherePivot('partnership_status', 'active')->wherePivot('is_available', true)
                ->where('lending_partners.is_verified', true)->where('lending_partners.is_active', true)->exists(), 422,
                'Add an active, verified and available lender-product relationship before publishing this product.');
        }

        DB::transaction(fn () => $this->persist($product, $data));
        $auditLogger->record('product.updated', $request->user(), 'loan_product', $product->id, ['is_enabled' => $data['is_enabled'], 'is_featured' => $data['is_featured']]);

        return back()->with('success', 'Product details saved.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?LoanProduct $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*)?$/', Rule::unique('loan_products', 'slug')->ignore($product?->id)],
            'category_ids' => ['required', 'array', 'min:1', 'max:9'],
            'category_ids.*' => ['integer', 'distinct', 'exists:loan_categories,id'],
            'description' => ['nullable', 'string', 'max:1200'],
            'eligibility' => ['nullable', 'string', 'max:4000'],
            'documentation' => ['nullable', 'array', 'max:20'],
            'documentation.*' => ['string', 'max:180'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'is_enabled' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'faqs' => ['nullable', 'array', 'max:12'],
            'faqs.*.question' => ['required', 'string', 'max:240'],
            'faqs.*.answer' => ['required', 'string', 'max:3000'],
            'related_product_ids' => ['nullable', 'array', 'max:12'],
            'related_product_ids.*' => ['integer', 'distinct', 'exists:loan_products,id'],
        ]);
        $slug = trim((string) ($data['slug'] ?: 'loans/'.Str::slug($data['name'])), '/');
        $slugRules = ['required', 'string', 'max:100', Rule::unique('loan_products', 'slug')->ignore($product?->id)];
        validator(['slug' => $slug], ['slug' => $slugRules])->validate();

        if ($product && in_array($product->id, array_map('intval', $data['related_product_ids'] ?? []), true)) {
            abort(422, 'A product cannot recommend itself.');
        }

        return [
            ...$data,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'eligibility' => $data['eligibility'] ?? null,
            'documentation' => array_values(array_filter($data['documentation'] ?? [])),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'is_enabled' => (bool) $data['is_enabled'],
            'is_featured' => (bool) $data['is_featured'],
            'faqs' => array_values($data['faqs'] ?? []),
            'related_product_ids' => array_values($data['related_product_ids'] ?? []),
        ];
    }

    /** @param array<string, mixed> $data */
    private function persist(?LoanProduct $product, array $data): LoanProduct
    {
        $categoryIds = $data['category_ids'];
        $faqRows = $data['faqs'];
        $relatedProductIds = $data['related_product_ids'];
        unset($data['category_ids'], $data['faqs'], $data['related_product_ids']);
        $data['category'] = LoanCategory::query()->findOrFail($categoryIds[0])->slug;
        $product ??= new LoanProduct();
        $product->fill($data);
        $product->save();
        $product->categories()->sync($categoryIds);
        if ($product->is_enabled) {
            LoanCategory::query()->whereIn('id', $categoryIds)->update(['is_enabled' => true]);
        }
        $product->faqs()->delete();
        $product->faqs()->createMany(array_map(fn (array $faq, int $index): array => [...$faq, 'display_order' => $index], $faqRows, array_keys($faqRows)));
        $product->relatedProducts()->sync($relatedProductIds);

        return $product;
    }
}
