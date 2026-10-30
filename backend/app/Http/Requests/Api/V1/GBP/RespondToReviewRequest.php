<?php

namespace App\Http\Requests\Api\V1\GBP;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @Task TASK-35-14
 * @Description Request validation for responding to GBP reviews
 */
class RespondToReviewRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:1', 'max:4096'],
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
            'text.required' => 'The response text is required.',
            'text.min' => 'The response text must be at least 1 character.',
            'text.max' => 'The response text cannot exceed 4096 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace
        if ($this->has('text')) {
            $this->merge([
                'text' => trim($this->input('text')),
            ]);
        }
    }
}
