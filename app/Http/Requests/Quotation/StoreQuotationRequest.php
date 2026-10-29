<?php

namespace App\Http\Requests\Quotation;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quotes.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'lead_id' => ['required', 'exists:leads,id'],
            'quote_date' => ['nullable', 'date'],
            'validity_days' => ['required', 'integer', 'in:15,30,45,60,90'],
            'output_type' => ['required', 'in:tax,notax,tech'],
            'discount_type' => ['nullable', 'in:amt,pct'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'in:0.05,0'],
            'technical_description' => ['nullable', 'string', 'max:5000'],
            'terms_conditions' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            // paths returned by the line photo upload (checked again in QuotationService)
            'items.*.photos' => ['nullable', 'array', 'max:3'],
            'items.*.photos.*' => ['string', 'max:255'],
            // "Submit for Approval" in the builder: save, then submit (QuotationController::submitAfterSave)
            'submit_for_approval' => ['nullable', 'boolean'],
            // Attachments card: the full current list (QuotationService::syncAttachments)
            'attachments_sync' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:30'],
            'attachments.*.path' => ['required', 'string', 'max:255'],
            'attachments.*.name' => ['nullable', 'string', 'max:255'],
            'attachments.*.size' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
