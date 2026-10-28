<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.edit') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user') instanceof \App\Models\User 
            ? $this->route('user')->id 
            : $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:6'],
            'role_name' => ['required', 'string', 'in:super_admin,estimation,engineer,accountant'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'all_branches' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:30'],
            'engineer_code' => ['nullable', 'string', 'max:20'],
            'zoho_petty_cash_account_id' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
