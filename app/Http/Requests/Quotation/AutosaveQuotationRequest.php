<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Foundation\Http\FormRequest;

class AutosaveQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quotes.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'quote_date' => ['nullable', 'date'],
            'validity_days' => ['nullable', 'integer'],
            'output_type' => ['nullable', 'in:tax,notax,tech'],
            'discount_type' => ['nullable', 'in:amt,pct'],
            'discount_value' => ['nullable', 'numeric'],
            'vat_rate' => ['nullable', 'numeric'],
            'technical_description' => ['nullable', 'string'],
            'terms_conditions' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.type' => ['nullable', 'in:item,section,subdesc'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.photos' => ['nullable', 'array', 'max:3'],
            'items.*.photos.*' => ['string', 'max:255'],
            'attachments_sync' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:30'],
            'attachments.*.path' => ['required', 'string', 'max:255'],
            'attachments.*.name' => ['nullable', 'string', 'max:255'],
            'attachments.*.size' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
