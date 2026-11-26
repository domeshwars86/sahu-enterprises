<?php

namespace App\Http\Requests\Admin;

use App\Services\Admin\AdminRecordScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCrmTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $enquiry = $this->route('enquiry');

        return $user !== null && $enquiry !== null && app(AdminRecordScope::class)->canAccessEnquiry($user, $enquiry) && (
            $user->hasAdminPermission('tasks.manage')
            || ($enquiry->assigned_user_id === $user->id && $user->hasAdminPermission('tasks.assigned'))
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
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,normal,high'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
