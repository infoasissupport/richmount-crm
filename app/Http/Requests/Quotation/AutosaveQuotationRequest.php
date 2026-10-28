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
        ];
    }
}
