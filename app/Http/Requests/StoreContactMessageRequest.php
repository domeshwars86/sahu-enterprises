<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactMessageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'mobile_number' => ['required', 'regex:/^[6-9][0-9]{9}$/'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'subject' => ['required', Rule::in(['loan_assistance', 'callback', 'other'])],
            'message' => ['required', 'string', 'max:3000'],
            'privacy_consent' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'privacy_consent.accepted' => 'Please agree to the privacy notice to send your message.',
        ];
    }
}
