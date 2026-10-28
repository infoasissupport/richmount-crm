<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('projects.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'vendor_name' => ['required', 'string', 'max:150'],
            'vendor_trn' => ['nullable', 'string', 'max:50'],
            'vendor_contact' => ['nullable', 'string', 'max:100'],
            'po_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'vat_rate' => ['nullable', 'numeric', 'in:0.05,0'],
            'terms' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
