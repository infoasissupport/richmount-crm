<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('leads.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'lead_date' => ['nullable', 'date'],
            'company_name' => ['required', 'string', 'max:120'],
            'rfq_number' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'department' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['required', 'exists:branches,id'],
            'assigned_engineer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'], // 20MB max
            'save_as_draft' => ['nullable'],
        ];
    }
}
