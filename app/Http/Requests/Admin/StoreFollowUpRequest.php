<?php

namespace App\Http\Requests\Admin;

use App\Services\Admin\AdminRecordScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFollowUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $enquiry = $this->route('enquiry');

        return $user !== null && $enquiry !== null && app(AdminRecordScope::class)->canAccessEnquiry($user, $enquiry) && (
            $user->hasAdminPermission('followups.manage')
            || ($enquiry->assigned_user_id === $user->id && $user->hasAdminPermission('followups.assigned'))
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
            'purpose' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'priority' => ['required', 'in:low,normal,high'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
