<?php

namespace App\Http\Requests\Api\V1\LocalSEO;

use App\Models\LocalSEOAlert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocalSEOAlertMarkAllReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer'],
            'severity' => ['nullable', 'string', Rule::in(LocalSEOAlert::SEVERITY_LEVELS)],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.integer' => 'The location ID must be an integer.',
            'severity.in' => 'The selected severity is invalid. Must be one of: info, warning, critical.',
        ];
    }
}
