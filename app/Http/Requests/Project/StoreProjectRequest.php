<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

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
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
