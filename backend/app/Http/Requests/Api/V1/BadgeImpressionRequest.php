<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for badge impression tracking.
 *
 * Validates the incoming request for the POST /api/v1/badge/impression endpoint.
 *
 * @see TASK-33-17
 * @see TEST-33-17
 */
class BadgeImpressionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * No authentication required for public badge impressions.
     */
    public function authorize(): bool
    {
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
            'hash' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9]{32}$/',
            ],
            'referrer_domain' => [
                'nullable',
                'string',
                'max:255',
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
            'hash.required' => 'The hash field is required.',
            'hash.regex' => 'The hash must be a 32-character alphanumeric string.',
            'referrer_domain.max' => 'The referrer domain may not be greater than 255 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from hash
        if ($this->has('hash')) {
            $this->merge([
                'hash' => trim($this->hash),
            ]);
        }

        // Convert empty referrer_domain to null
        if ($this->has('referrer_domain') && $this->referrer_domain === '') {
            $this->merge([
                'referrer_domain' => null,
            ]);
        }
    }
}
