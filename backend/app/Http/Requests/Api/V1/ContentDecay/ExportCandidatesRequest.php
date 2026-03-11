<?php

namespace App\Http\Requests\Api\V1\ContentDecay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for exporting decay candidates.
 */
class ExportCandidatesRequest extends FormRequest
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
            'format' => [
                'sometimes',
                'string',
                Rule::in(['csv', 'pdf', 'xls']),
            ],
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
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'recovery_report' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'format.in' => 'Invalid format. Allowed values: csv, pdf, xls.',
            'status.in' => 'Invalid status. Allowed values: backlog, planned, in_progress, refreshed, recovered, ignored.',
            'severity.in' => 'Invalid severity. Allowed values: critical, high, medium, low.',
            'date_to.after_or_equal' => 'The date_to must be a date after or equal to date_from.',
        ];
    }
}
