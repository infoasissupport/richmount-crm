<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
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
