<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for validating lead capture submissions
 * 
 * Validates email format, length, and optional disposable domain blocking
 * 
 * @see TASK-33-5
 */
class PublicAuditLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public endpoint - no authentication required
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
            'email' => [
                'required',
                'email:rfc,dns',
                'max:254',
                // Optionally block disposable email domains
                // Rule::notIn($this->getDisposableDomains()),
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
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email address is too long.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }
    }

    /**
     * Get list of disposable email domains to block.
     * 
     * @return array<string>
     */
    protected function getDisposableDomains(): array
    {
        // Common disposable email domains
        return [
            '10minutemail.com',
            'tempmail.com',
            'guerrillamail.com',
            'mailinator.com',
            'throwaway.email',
            // Add more as needed
        ];
    }
}
