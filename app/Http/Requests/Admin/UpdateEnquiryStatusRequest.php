<?php

namespace App\Http\Requests\Admin;

use App\Services\Admin\AdminRecordScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEnquiryStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $enquiry = $this->route('enquiry');

        return $user !== null && $enquiry !== null && app(AdminRecordScope::class)->canAccessEnquiry($user, $enquiry) && (
            $user->hasAdminPermission('enquiries.status')
            || ($enquiry->assigned_user_id === $user->id && $user->hasAdminPermission('enquiries.status_assigned'))
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $reasonRequired = in_array($this->input('status'), ['submitted_to_lender', 'approved', 'rejected', 'disbursed'], true);

        return [
            'status' => ['required', 'string', 'in:new,contact_attempted,contacted,qualified,documents_pending,ready_for_submission,submitted_to_lender,under_lender_review,approved,rejected,disbursed,on_hold,closed,duplicate'],
            'reason' => [$reasonRequired ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }
}
