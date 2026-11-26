<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LendingPartner;
use App\Models\LoanProduct;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LendingPartnerController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAdminPermission('partners.manage'), 403);

        return Inertia::render('Admin/Partners/Index', [
            'products' => LoanProduct::query()->orderBy('name')->get(['id', 'name', 'slug', 'is_enabled']),
            'partners' => LendingPartner::query()->with('loanProducts:id,name,slug')->orderBy('name')->paginate(25)->withQueryString()->through(fn (LendingPartner $partner): array => [
                'id' => $partner->id,
                'name' => $partner->name,
                'category' => $partner->institution_category,
                'authorizedContact' => $partner->authorized_contact,
                'products' => $partner->supported_products ?? [],
                'productMappings' => $partner->loanProducts->map(fn (LoanProduct $product): array => [
                    'loan_product_id' => $product->id,
                    'name' => $product->name,
                    'partnership_status' => $product->pivot->partnership_status,
                    'is_available' => (bool) $product->pivot->is_available,
                    'internal_sourcing_info' => $product->pivot->internal_sourcing_info,
                ])->values()->all(),
                'notes' => $partner->internal_notes,
                'verified' => $partner->is_verified,
                'active' => $partner->is_active,
            ]),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('partners.manage'), 403);
        $data = $this->validated($request);
        abort_if($data['is_verified'] && empty($data['internal_notes']), 422, 'Record the authorization source in internal notes before verifying a partner.');
        $partner = LendingPartner::query()->create(collect($data)->except('product_mappings')->all());
        $this->syncProductMappings($partner, $data['product_mappings'], $data['is_verified'], $data['is_active']);
        $auditLogger->record('partner.created', $request->user(), 'lending_partner', $partner->id, ['is_verified' => (bool) $partner->is_verified]);

        return back()->with('success', 'Lending partner record saved.');
    }

    public function update(Request $request, LendingPartner $partner, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('partners.manage'), 403);
        $data = $this->validated($request, $partner);
        abort_if($data['is_verified'] && empty($data['internal_notes']), 422, 'Record the authorization source in internal notes before verifying a partner.');
        $partner->update(collect($data)->except('product_mappings')->all());
        $this->syncProductMappings($partner, $data['product_mappings'], $data['is_verified'], $data['is_active']);
        $auditLogger->record('partner.updated', $request->user(), 'lending_partner', $partner->id, ['is_verified' => (bool) $partner->is_verified, 'is_active' => (bool) $partner->is_active]);

        return back()->with('success', 'Lending partner record updated.');
    }

    /** @return array{name: string, institution_category: ?string, authorized_contact: ?string, supported_products: list<string>, product_mappings: list<array{loan_product_id: int, partnership_status: string, is_available: bool, internal_sourcing_info: ?string}>, internal_notes: ?string, is_verified: bool, is_active: bool} */
    private function validated(Request $request, ?LendingPartner $partner = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160', 'unique:lending_partners,name'.($partner ? ','.$partner->id : '')],
            'institution_category' => ['nullable', 'string', 'max:80'],
            'authorized_contact' => ['nullable', 'string', 'max:1000'],
            'supported_products' => ['nullable', 'array', 'max:20'],
            'supported_products.*' => ['string', 'max:100'],
            'product_mappings' => ['nullable', 'array', 'max:100'],
            'product_mappings.*.loan_product_id' => ['required', 'integer', 'distinct', 'exists:loan_products,id'],
            'product_mappings.*.partnership_status' => ['required', 'string', 'in:pending,active,ended'],
            'product_mappings.*.is_available' => ['required', 'boolean'],
            'product_mappings.*.internal_sourcing_info' => ['nullable', 'string', 'max:1000'],
            'internal_notes' => ['nullable', 'string', 'max:4000'],
            'is_verified' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'institution_category' => $data['institution_category'] ?? null,
            'authorized_contact' => $data['authorized_contact'] ?? null,
            'supported_products' => $data['supported_products'] ?? [],
            'internal_notes' => $data['internal_notes'] ?? null,
            'is_verified' => (bool) $data['is_verified'],
            'is_active' => (bool) $data['is_active'],
            'product_mappings' => array_map(fn (array $mapping): array => [
                'loan_product_id' => (int) $mapping['loan_product_id'],
                'partnership_status' => $mapping['partnership_status'],
                'is_available' => (bool) $mapping['is_available'],
                'internal_sourcing_info' => $mapping['internal_sourcing_info'] ?? null,
            ], $data['product_mappings'] ?? []),
        ];
    }

    /** @param list<array{loan_product_id: int, partnership_status: string, is_available: bool, internal_sourcing_info: ?string}> $mappings */
    private function syncProductMappings(LendingPartner $partner, array $mappings, bool $isVerified, bool $isActive): void
    {
        foreach ($mappings as $mapping) {
            abort_if($mapping['is_available'] && (! $isVerified || ! $isActive || $mapping['partnership_status'] !== 'active' || empty($mapping['internal_sourcing_info'])), 422,
                'A product may be marked available only with an active verified partner record and a sourcing authorization note.');
        }

        $partner->loanProducts()->sync(collect($mappings)->mapWithKeys(fn (array $mapping): array => [
            $mapping['loan_product_id'] => collect($mapping)->except('loan_product_id')->all(),
        ])->all());
    }
}
