<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('branches.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'country' => ['required', 'string', 'max:50'],
            'quote_format' => ['required', 'in:format_1,format_2,format_3'],
            'trn_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
