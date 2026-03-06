<?php

namespace App\Http\Requests\Api\V1\Credits;

use App\Models\CreditTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for validating transaction history query parameters.
 *
 * @see TASK-26-7
 * @see FEAT-026
 */
class TransactionHistoryRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'type' => ['nullable', 'string', Rule::in([
                CreditTransaction::TYPE_PURCHASE,
                CreditTransaction::TYPE_USAGE,
                CreditTransaction::TYPE_REFUND,
                CreditTransaction::TYPE_BONUS,
            ])],
            'from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
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
            'page.integer' => 'The page must be a valid number.',
            'page.min' => 'The page must be at least 1.',
            'per_page.integer' => 'The per_page must be a valid number.',
            'per_page.min' => 'The per_page must be at least 1.',
            'per_page.max' => 'The per_page may not be greater than 50.',
            'type.in' => 'The selected type is invalid. Must be one of: purchase, usage, refund, bonus.',
            'from.date' => 'The from date must be a valid date.',
            'from.date_format' => 'The from date must be in Y-m-d format (e.g., 2026-03-01).',
            'to.date' => 'The to date must be a valid date.',
            'to.date_format' => 'The to date must be in Y-m-d format (e.g., 2026-03-01).',
            'to.after_or_equal' => 'The to date must be after or equal to the from date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty strings to null
        $this->merge([
            'type' => $this->type ?: null,
            'from' => $this->from ?: null,
            'to' => $this->to ?: null,
        ]);
    }

    /**
     * Get validated parameters with defaults.
     *
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return [
            'page' => (int) $this->validated('page', 1),
            'per_page' => (int) $this->validated('per_page', 15),
            'type' => $this->validated('type'),
            'from' => $this->validated('from'),
            'to' => $this->validated('to'),
        ];
    }
}
