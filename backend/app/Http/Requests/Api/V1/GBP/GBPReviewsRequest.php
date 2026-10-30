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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('has_response')) {
            $this->merge([
                'has_response' => $this->boolean('has_response'),
            ]);
        }

        $ratings = $this->input('ratings', $this->input('ratings[]'));

        if (is_string($ratings) && trim($ratings) !== '') {
            $ratings = array_filter(array_map('trim', explode(',', $ratings)), static fn ($value) => $value !== '');
        }

        if (is_array($ratings)) {
            $this->merge([
                'ratings' => array_values(array_unique(array_map('intval', $ratings))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'ratings' => ['nullable', 'array'],
            'ratings.*' => ['integer', 'min:1', 'max:5'],
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
        $ratings = array_values(array_unique(array_map('intval', $this->input('ratings', []))));

        return array_filter([
            'per_page' => min((int) $this->input('per_page', 15), 100),
            'page' => max((int) $this->input('page', 1), 1),
            'rating' => $this->input('rating'),
            'ratings' => $ratings ?: null,
            'sentiment' => $this->input('sentiment'),
            'has_response' => $this->filled('has_response') ? $this->boolean('has_response') : null,
            'start_date' => $this->input('start_date'),
            'end_date' => $this->input('end_date'),
            'sort' => $this->input('sort', '-date'),
        ], static fn ($value) => !is_null($value));
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
            'ratings.*.min' => 'Each rating must be between 1 and 5.',
            'ratings.*.max' => 'Each rating must be between 1 and 5.',
            'sentiment.in' => 'The sentiment must be one of: positive, neutral, negative.',
            'sort.in' => 'The sort field must be one of: date, -date, rating, -rating.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
