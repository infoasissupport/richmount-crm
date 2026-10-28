<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class RecordClientPoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('projects.open') ?? false;
    }

    public function rules(): array
    {
        return [
            'client_po_number' => ['required', 'string', 'max:100'],
            'client_po_date' => ['required', 'date'],
            'client_po_amount' => ['nullable', 'numeric', 'min:0'],
            'po_file' => ['nullable', 'file', 'max:20480'], // max 20MB
        ];
    }
}
