<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for Project validation.
 *
 * Validates project creation and update requests.
 *
 * @see TASK-8-7
 */
class ProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller via Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'domain' => [
                'nullable',
                'url',
                'max:500',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:65535',
            ],
            'is_default' => [
                'nullable',
                'boolean',
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
            'name.required' => 'The project name is required.',
            'name.max' => 'The project name may not be greater than 255 characters.',
            'domain.url' => 'The domain must be a valid URL.',
            'domain.max' => 'The domain may not be greater than 500 characters.',
            'notes.max' => 'The notes may not be greater than 65535 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from name
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }

        // Sanitize HTML from notes to prevent XSS
        if ($this->has('notes') && $this->notes !== null) {
            $this->merge([
                'notes' => strip_tags($this->notes),
            ]);
        }
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure name is not just whitespace
            if ($this->has('name') && trim($this->name) === '') {
                $validator->errors()->add('name', 'The project name cannot be empty or whitespace only.');
            }
        });
    }
}
