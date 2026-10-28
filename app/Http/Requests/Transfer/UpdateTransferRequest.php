<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('transfers.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'to_user_id' => ['sometimes', 'exists:users,id'],
            'amount' => ['sometimes', 'numeric', 'min:1'],
            'payment_method' => ['sometimes', 'in:cash,cheque,bank_transfer'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
