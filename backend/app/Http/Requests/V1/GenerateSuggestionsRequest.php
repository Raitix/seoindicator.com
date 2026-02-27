<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for validating generate suggestions requests.
 *
 * Validates the request body for POST /api/v1/sites/:siteId/suggestions/generate
 *
 * @see TASK-5-8
 * @see TEST-5-8
 */
class GenerateSuggestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by controller policy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'seedKeyword' => ['required', 'string', 'max:255'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['required', 'string', 'in:question,suggest,competitor,related'],
            'maxResults' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'seedKeyword.required' => 'The seed keyword field is required.',
            'seedKeyword.max' => 'The seed keyword may not be greater than :max characters.',
            'sources.required' => 'At least one source must be specified.',
            'sources.min' => 'At least one source must be specified.',
            'sources.*.in' => 'Invalid source type. Valid sources are: question, suggest, competitor, related.',
            'maxResults.min' => 'The max results must be at least :min.',
            'maxResults.max' => 'The max results may not be greater than :max.',
        ];
    }
}
