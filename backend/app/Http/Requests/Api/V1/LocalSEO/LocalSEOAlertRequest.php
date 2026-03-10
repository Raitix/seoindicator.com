<?php

namespace App\Http\Requests\Api\V1\LocalSEO;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @Task TASK-35-16
 * @Description Form Request for Local SEO Alert API validation
 */
class LocalSEOAlertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validSeverities = ['info', 'warning', 'critical'];
        $validAlertTypes = [
            'ranking_improved',
            'ranking_dropped',
            'new_review',
            'negative_review',
            'rating_dropped',
            'competitor_overtake',
            'gbp_disconnected',
        ];

        return [
            'location_id' => [
                'nullable',
                'integer',
                'exists:business_locations,id',
            ],
            'severity' => [
                'nullable',
                'string',
                Rule::in($validSeverities),
            ],
            'alert_type' => [
                'nullable',
                'string',
                Rule::in($validAlertTypes),
            ],
            'is_read' => [
                'nullable',
                'boolean',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location_id.integer' => 'The location ID must be an integer.',
            'location_id.exists' => 'The selected location does not exist.',
            'severity.in' => 'The selected severity is invalid. Must be one of: info, warning, critical.',
            'alert_type.in' => 'The selected alert type is invalid.',
            'is_read.boolean' => 'The is_read field must be true or false.',
            'per_page.integer' => 'The per_page must be an integer.',
            'per_page.min' => 'The per_page must be at least 1.',
            'per_page.max' => 'The per_page may not be greater than 100.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string 'true'/'false' to boolean for is_read
        if ($this->has('is_read')) {
            $this->merge([
                'is_read' => filter_var($this->input('is_read'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        // Ensure per_page is an integer
        if ($this->has('per_page')) {
            $this->merge([
                'per_page' => (int) $this->input('per_page'),
            ]);
        }

        // Ensure location_id is an integer
        if ($this->has('location_id')) {
            $this->merge([
                'location_id' => (int) $this->input('location_id'),
            ]);
        }
    }
}
