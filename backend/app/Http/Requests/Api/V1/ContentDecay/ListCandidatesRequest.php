<?php

namespace App\Http\Requests\Api\V1\ContentDecay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for listing candidates with filters.
 */
class ListCandidatesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in(['backlog', 'planned', 'in_progress', 'refreshed', 'recovered', 'ignored']),
            ],
            'severity' => [
                'sometimes',
                'string',
                Rule::in(['critical', 'high', 'medium', 'low']),
            ],
            'page_type' => ['sometimes', 'string', 'max:100'],
            'reason_code' => ['sometimes', 'string', 'max:50'],
            'min_lost_clicks' => ['sometimes', 'integer', 'min:0'],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort' => [
                'sometimes',
                'string',
                Rule::in(['decay_score', 'recovery_upside_estimate', 'lost_click_estimate', 'last_detected_at', 'created_at']),
            ],
            'order' => [
                'sometimes',
                'string',
                Rule::in(['asc', 'desc']),
            ],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status. Allowed values: backlog, planned, in_progress, refreshed, recovered, ignored.',
            'severity.in' => 'Invalid severity. Allowed values: critical, high, medium, low.',
            'sort.in' => 'Invalid sort field. Allowed values: decay_score, recovery_upside_estimate, lost_click_estimate, last_detected_at, created_at.',
            'order.in' => 'Invalid order direction. Allowed values: asc, desc.',
            'per_page.max' => 'Per page cannot exceed 100.',
        ];
    }
}
