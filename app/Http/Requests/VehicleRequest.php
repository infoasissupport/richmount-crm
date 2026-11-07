<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('expenses.approve') ?? false;
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'name' => ['nullable', 'string', 'max:100'],
            'registration_number' => [
                'required',
                'string',
                'max:32',
                Rule::unique('vehicles', 'registration_number')->ignore($vehicle?->id),
            ],
            'make_model' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
