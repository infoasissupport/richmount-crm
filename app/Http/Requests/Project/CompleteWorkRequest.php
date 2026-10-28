<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class CompleteWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('projects.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'completion_date' => ['required', 'date'],
            'work_description' => ['required', 'string', 'max:3000'],
            'retention_pct' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'retention_amount' => ['nullable', 'numeric', 'min:0'],
            'client_representative_name' => ['nullable', 'string', 'max:150'],
            'client_representative_title' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
