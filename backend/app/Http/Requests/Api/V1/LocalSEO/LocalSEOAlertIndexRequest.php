<?php

namespace App\Http\Requests\Api\V1\LocalSEO;

use App\Models\LocalSEOAlert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocalSEOAlertIndexRequest extends FormRequest
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
            'type' => ['nullable', 'string', Rule::in(LocalSEOAlert::ALERT_TYPES)],
            'is_read' => ['nullable', Rule::in([true, false, 1, 0, '1', '0', 'true', 'false'])],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.integer' => 'The location ID must be an integer.',
            'severity.in' => 'The selected severity is invalid. Must be one of: info, warning, critical.',
            'type.in' => 'The selected alert type is invalid.',
            'is_read.in' => 'The is_read field must be true or false.',
            'per_page.integer' => 'The per_page must be an integer.',
            'per_page.min' => 'The per_page must be at least 1.',
        ];
    }
}
