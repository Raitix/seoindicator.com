<?php

namespace App\Http\Requests\Api\V1\GBP;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @Task TASK-35-14
 * @Description Request validation for GBP Reviews endpoints
 */
class GBPReviewsRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sentiment' => ['nullable', 'string', 'in:positive,neutral,negative'],
            'has_response' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort' => ['nullable', 'string', 'in:date,-date,rating,-rating'],
        ];
    }

    /**
     * Get the filters for the service.
     */
    public function getFilters(): array
    {
        return array_filter([
            'per_page' => min($this->input('per_page', 15), 100),
            'page' => $this->input('page', 1),
            'rating' => $this->input('rating'),
            'sentiment' => $this->input('sentiment'),
            'has_response' => $this->filled('has_response') ? $this->boolean('has_response') : null,
            'start_date' => $this->input('start_date'),
            'end_date' => $this->input('end_date'),
            'sort' => $this->input('sort', '-date'),
        ], fn ($value) => !is_null($value));
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.integer' => 'The rating must be a valid number.',
            'rating.min' => 'The rating must be between 1 and 5.',
            'rating.max' => 'The rating must be between 1 and 5.',
            'sentiment.in' => 'The sentiment must be one of: positive, neutral, negative.',
            'sort.in' => 'The sort field must be one of: date, -date, rating, -rating.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
