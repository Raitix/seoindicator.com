<?php

namespace App\Http\Requests\Api\V1\GBP;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * @Task TASK-35-14
 * @Description Request validation for GBP Insights endpoints
 */
class GBPInsightsRequest extends FormRequest
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
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date', 'before:tomorrow'],
            'end_date' => ['nullable', 'date', 'before:tomorrow'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            if ($startDate && $endDate && $startDate > $endDate) {
                $validator->errors()->add('start_date', 'Start date must be before or equal to end date.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.date' => 'The start date must be a valid date.',
            'start_date.before_or_equal' => 'The start date must be before or equal to the end date.',
            'start_date.before' => 'The start date cannot be in the future.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.before' => 'The end date cannot be in the future.',
        ];
    }
}
