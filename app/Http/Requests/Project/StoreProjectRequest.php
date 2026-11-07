<?php

namespace App\Http\Requests\Project;

use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('projects.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'client_name' => ['required', 'string', 'max:150'],
            'branch_id' => ['required', 'exists:branches,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'engineer_id' => ['nullable', 'exists:users,id'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'expected_end_date' => ['nullable', 'date'],
            'client_po_number' => ['nullable', 'string', 'max:100'],
            'client_po_date' => ['nullable', 'date'],
            'client_po_amount' => ['nullable', 'numeric', 'min:0'],
            'po_file' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:20480'],
            'project_documents' => ['nullable', 'array'],
            'project_documents.*' => ['file', 'mimes:pdf,png,jpg,jpeg,dwg,xls,xlsx,doc,docx', 'max:20480'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('client_po_number')) {
            $this->merge([
                'client_po_date' => null,
                'client_po_amount' => 0,
            ]);
        }

        $quotationId = $this->input('quotation_id');
        if (! $quotationId || ! ctype_digit((string) $quotationId)) {
            return;
        }

        $quotation = Quotation::with('lead')->find((int) $quotationId);
        if (! $quotation) {
            return;
        }

        $this->merge([
            'lead_id' => $quotation->lead_id,
            'branch_id' => $quotation->branch_id,
            'client_name' => $quotation->lead?->company_name ?? 'Customer',
            'contract_value' => $quotation->subtotal,
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('quotation_id') || ! $this->filled('quotation_id')) {
                    return;
                }

                $quotation = Quotation::find($this->integer('quotation_id'));
                if (! $quotation || $quotation->status !== 'awarded') {
                    $validator->errors()->add('quotation_id', 'Only awarded quotations can be used to create a project.');

                    return;
                }

                if ($quotation->project()->exists()) {
                    $validator->errors()->add('quotation_id', 'A project has already been created for this quotation.');
                }
            },
        ];
    }
}
