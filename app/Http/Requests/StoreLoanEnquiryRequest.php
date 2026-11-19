<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
        return [
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
            ])],
            'requested_amount' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'preferred_tenure' => ['required', Rule::in(['1–3 years', '3–5 years', '5+ years'])],
            'employment_category' => ['nullable', Rule::in(['salaried', 'self_employed', 'business_owner', 'other'])],
            'vehicle_type' => ['nullable', Rule::in(['car', 'two_wheeler', 'commercial_vehicle'])],
            'vehicle_condition' => ['required', Rule::in(['new', 'used'])],
            'vehicle_price' => ['nullable', 'numeric', 'min:1', 'max:9999999999'],
            'down_payment' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'customer_notes' => ['nullable', 'string', 'max:1500'],
            'preferred_callback_time' => ['required', Rule::in(['Morning', 'Afternoon', 'Evening'])],
            'contact_consent' => ['accepted'],
            'marketing_consent' => ['sometimes', 'boolean'],
            'website' => ['prohibited'],
        ];
    }
}
