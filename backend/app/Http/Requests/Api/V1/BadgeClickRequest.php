<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for badge click tracking.
 *
 * Validates the incoming request for the POST /api/v1/badge/click endpoint.
 *
 * @see TASK-33-18
 * @see TEST-33-18
 */
class BadgeClickRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * No authentication required for public badge clicks.
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
            'click_target' => [
                'required',
                'string',
                'in:audit,home',
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
            'click_target.required' => 'The click_target field is required.',
            'click_target.in' => 'The click_target must be either "audit" or "home".',
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

        // Trim whitespace from click_target
        if ($this->has('click_target')) {
            $this->merge([
                'click_target' => trim($this->click_target),
            ]);
        }
    }

    /**
     * Get the validated hash in lowercase.
     */
    public function getHash(): string
    {
        return strtolower($this->validated('hash'));
    }

    /**
     * Get the validated click target.
     */
    public function getClickTarget(): string
    {
        return $this->validated('click_target');
    }
}
