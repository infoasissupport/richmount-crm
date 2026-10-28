<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class AssignEngineerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('leads.assign') ?? false;
    }

    public function rules(): array
    {
        return [
            'engineer_id' => ['required', 'exists:users,id'],
        ];
    }
}
