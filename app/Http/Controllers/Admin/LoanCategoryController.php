<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanCategory;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LoanCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAdminPermission('products.manage'), 403);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => LoanCategory::query()->withCount('products')->orderBy('display_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('products.manage'), 403);
        $data = $this->validated($request);
        $category = LoanCategory::query()->create($data);
        $auditLogger->record('loan_category.created', $request->user(), 'loan_category', $category->id);

        return back()->with('success', 'Loan category created.');
    }

    public function update(Request $request, LoanCategory $category, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('products.manage'), 403);
        $data = $this->validated($request, $category);
        abort_if($data['is_enabled'] && ! $category->products()->where('is_enabled', true)->exists(), 422, 'Publish at least one product in this category before making it publicly available.');
        $category->update($data);
        $auditLogger->record('loan_category.updated', $request->user(), 'loan_category', $category->id, ['is_enabled' => $category->is_enabled]);

        return back()->with('success', 'Loan category updated.');
    }

    /** @return array{slug: string, name: string, description: ?string, icon: string, is_enabled: bool, display_order: int} */
    private function validated(Request $request, ?LoanCategory $category = null): array
    {
        $request->merge(['slug' => $request->input('slug') ?: Str::slug((string) $request->input('name'))]);
        if (is_string($request->input('enquiry_fields'))) {
            $fields = json_decode($request->input('enquiry_fields'), true);

            if (! is_array($fields)) {
                throw ValidationException::withMessages(['enquiry_fields' => 'Form fields must be valid JSON.']);
            }

            $request->merge(['enquiry_fields' => $fields]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('loan_categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:24'],
            'is_enabled' => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'enquiry_fields' => ['required', 'array', 'max:20'],
            'enquiry_fields.*.key' => ['required', 'string', 'max:60', 'distinct', 'regex:/^[a-z][a-z0-9_]*$/'],
            'enquiry_fields.*.label' => ['required', 'string', 'max:100'],
            'enquiry_fields.*.type' => ['required', 'string', 'in:text,number,select'],
            'enquiry_fields.*.required' => ['required', 'boolean'],
            'enquiry_fields.*.options' => ['nullable', 'array', 'max:30'],
            'enquiry_fields.*.options.*' => ['string', 'max:100'],
        ]);

        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?: '↗',
            'is_enabled' => (bool) $data['is_enabled'],
            'display_order' => (int) ($data['display_order'] ?? 0),
            'enquiry_fields' => $data['enquiry_fields'],
        ];
    }
}
