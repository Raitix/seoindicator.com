<?php

namespace App\Http\Requests\V1\AIVisibility;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request validation for updating an AI Visibility project.
 *
 * @see TASK-34-6
 * @see SPEC-034
 */
class UpdateAIVisibilityProjectRequest extends FormRequest
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
            'brand_name' => ['sometimes', 'required', 'string', 'max:255'],
            'keywords' => ['sometimes', 'required', 'array', 'min:1', 'max:10'],
            'keywords.*' => ['required', 'string', 'max:100', 'min:1'],
            'competitors' => ['sometimes', 'required', 'array', 'min:1', 'max:3'],
            'competitors.*' => ['required', 'string', 'max:100', 'min:1'],
            'query_templates' => ['nullable', 'array'],
            'query_templates.*' => ['string', 'max:500'],
            'tracking_frequency' => ['nullable', 'string', 'in:daily,weekly,monthly'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    public function passedValidation(): void
    {
        // Get only the fields defined in the rules
        $rules = $this->rules();
        $allowedFields = array_keys($rules);
        
        // Filter the input to only include allowed fields
        $filtered = array_intersect_key($this->all(), array_flip($allowedFields));
        $this->replace($filtered);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'brand_name.required' => 'The brand name field is required.',
            'brand_name.max' => 'The brand name may not be greater than 255 characters.',
            'keywords.required' => 'At least one keyword is required.',
            'keywords.array' => 'Keywords must be provided as an array.',
            'keywords.min' => 'At least one keyword is required.',
            'keywords.max' => 'You can add a maximum of 10 keywords.',
            'keywords.*.required' => 'Each keyword is required.',
            'keywords.*.min' => 'Each keyword must be at least 1 character.',
            'keywords.*.max' => 'Each keyword may not be greater than 100 characters.',
            'competitors.required' => 'At least one competitor is required.',
            'competitors.array' => 'Competitors must be provided as an array.',
            'competitors.min' => 'At least one competitor is required.',
            'competitors.max' => 'You can add a maximum of 3 competitors.',
            'competitors.*.required' => 'Each competitor is required.',
            'competitors.*.min' => 'Each competitor name must be at least 1 character.',
            'competitors.*.max' => 'Each competitor name may not be greater than 100 characters.',
            'tracking_frequency.in' => 'Tracking frequency must be daily, weekly, or monthly.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => $validator->errors()->messages(),
            ],
        ], 422));
    }
}
