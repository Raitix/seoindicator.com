<?php

namespace App\Http\Requests\Api\V1\ContentDecay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for updating candidate status.
 */
class UpdateCandidateRequest extends FormRequest
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
            'notes' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'planned_at' => ['sometimes', 'nullable', 'date'],
            'started_at' => ['sometimes', 'nullable', 'date'],
            'refreshed_at' => ['sometimes', 'nullable', 'date'],
            'recovered_at' => ['sometimes', 'nullable', 'date'],
            'ignored_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status. Allowed values: backlog, planned, in_progress, refreshed, recovered, ignored.',
            'notes.max' => 'Notes cannot exceed 65,535 characters.',
        ];
    }
}
