<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreLoanEnquiryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->input('product_slug')
            ? \App\Models\LoanProduct::query()->where('slug', $this->input('product_slug'))->where('is_enabled', true)->with('categories')->first()
            : null;
        $category = $product?->categories()->orderBy('display_order')->first();
        $fields = $product?->enquiry_fields ?: ($category?->enquiry_fields ?? []);
        $rules = [
            'idempotency_key' => ['required', 'uuid'],
            'customer_name' => ['required', 'string', 'max:150'],
            'mobile_number' => ['required', 'regex:/^[6-9][0-9]{9}$/'],
            'email' => ['nullable', 'email', 'max:254'],
            'city' => ['required', 'string', 'max:100'],
            'loan_category' => ['required', Rule::in([
                'new_car',
                'used_car',
                'two_wheeler',
                'commercial_vehicle',
                'vehicle_refinance',
                ...\App\Models\LoanCategory::query()->pluck('slug')->all(),
            ])],
            'product_slug' => ['nullable', 'string', 'max:100', Rule::exists('loan_products', 'slug')->where('is_enabled', true)],
            'requested_amount' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'preferred_tenure' => ['nullable', 'string', 'max:40'],
            'employment_category' => ['nullable', Rule::in(['salaried', 'self_employed', 'business_owner', 'other'])],
            'vehicle_type' => ['nullable', Rule::in(['car', 'two_wheeler', 'commercial_vehicle'])],
            'vehicle_condition' => ['nullable', Rule::in(['new', 'used'])],
            'vehicle_price' => ['nullable', 'numeric', 'min:1', 'max:9999999999'],
            'down_payment' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'customer_notes' => ['nullable', 'string', 'max:1500'],
            'preferred_callback_time' => ['required', Rule::in(['Morning', 'Afternoon', 'Evening'])],
            'contact_consent' => ['accepted'],
            'marketing_consent' => ['sometimes', 'boolean'],
            'details' => ['nullable', 'array', 'max:20'],
            'website' => ['prohibited'],
        ];

        foreach ($fields as $field) {
            if (! is_array($field) || ! isset($field['key'], $field['type'])) {
                continue;
            }

            $key = (string) $field['key'];
            $required = (bool) ($field['required'] ?? false);
            $rules['details.'.$key] = match ($field['type']) {
                'number' => [$required ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999999'],
                'select' => [$required ? 'required' : 'nullable', 'string', Rule::in(array_keys($field['options'] ?? []))],
                default => [$required ? 'required' : 'nullable', 'string', 'max:240'],
            };
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $product = $this->input('product_slug')
                ? \App\Models\LoanProduct::query()->where('slug', $this->input('product_slug'))->where('is_enabled', true)->with('categories')->first()
                : null;
            $category = $product?->categories()->orderBy('display_order')->first();
            $fields = $product?->enquiry_fields ?: ($category?->enquiry_fields ?? []);
            $allowedKeys = collect($fields)->pluck('key')->filter()->all();
            $submittedKeys = array_keys($this->input('details', []));
            $unknownKeys = array_diff($submittedKeys, $allowedKeys);

            if ($unknownKeys !== []) {
                $validator->errors()->add('details', 'Remove fields that do not apply to this loan product.');
            }

            if ($product && $category && $this->input('loan_category') !== $category->slug) {
                $validator->errors()->add('loan_category', 'Choose a category that matches the selected product.');
            }
        }];
    }
}
