<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('expenses.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'exists:projects,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'category' => ['required', 'string', 'in:materials,transport,labor,meals,tools,fuel,permits,miscellaneous'],
            'vendor_name' => ['nullable', 'string', 'max:150'],
            'vendor_trn' => ['nullable', 'string', 'max:50'],
            'bill_number' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date'],
            'type' => ['required', 'in:cash,card'],
            'subtotal' => ['required', 'numeric', 'min:0.01'],
            'vat_rate' => ['nullable', 'numeric', 'in:0.05,0'],
            'receipt' => ['nullable', 'file', 'max:20480'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
