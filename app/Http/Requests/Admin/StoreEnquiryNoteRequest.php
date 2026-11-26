<?php

namespace App\Http\Requests\Admin;

use App\Services\Admin\AdminRecordScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnquiryNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $enquiry = $this->route('enquiry');

        return $user !== null && $enquiry !== null && app(AdminRecordScope::class)->canAccessEnquiry($user, $enquiry) && (
            $user->hasAdminPermission('enquiries.note')
            || ($enquiry->assigned_user_id === $user->id && $user->hasAdminPermission('enquiries.note_assigned'))
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:4000'],
        ];
    }
}
