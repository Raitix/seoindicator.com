<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

/**
 * Request validation for bulk updating issues' status.
 *
 * TASK-14-14
 */
class BulkUpdateIssuesRequest extends FormRequest
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
        return [
            'issue_ids' => ['required', 'array', 'max:100'],
            'issue_ids.*' => ['integer', 'exists:audit_issues,id'],
            'status' => ['required', 'in:open,fixed,ignored'],
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
            'issue_ids.required' => 'The issue IDs field is required.',
            'issue_ids.array' => 'The issue IDs must be an array.',
            'issue_ids.max' => 'You can update a maximum of 100 issues at once.',
            'issue_ids.*.integer' => 'Each issue ID must be an integer.',
            'issue_ids.*.exists' => 'One or more selected issues do not exist.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be one of: open, fixed, ignored.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
