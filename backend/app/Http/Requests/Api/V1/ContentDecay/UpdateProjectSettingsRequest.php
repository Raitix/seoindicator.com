<?php

namespace App\Http\Requests\Api\V1\ContentDecay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for updating project settings.
 */
class UpdateProjectSettingsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'detection_enabled' => ['sometimes', 'boolean'],
            'window_days' => [
                'sometimes',
                'integer',
                'min:7',
                'max:90',
                Rule::in([7, 14, 28, 56, 90]),
            ],
            'min_clicks_threshold' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'min_impressions_threshold' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'min_decay_score' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'digest_enabled' => ['sometimes', 'boolean'],
            'digest_frequency' => [
                'sometimes',
                'string',
                Rule::in(['weekly', 'monthly']),
            ],
            'settings_json' => ['sometimes', 'nullable', 'array'],
            'settings_json.*' => ['string'], // All values in settings_json should be strings for sanitization
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'window_days.min' => 'Window days must be at least 7.',
            'window_days.max' => 'Window days cannot exceed 90.',
            'window_days.in' => 'Window days must be one of: 7, 14, 28, 56, 90.',
            'min_clicks_threshold.min' => 'Click threshold cannot be negative.',
            'min_impressions_threshold.min' => 'Impressions threshold cannot be negative.',
            'min_decay_score.min' => 'Decay score threshold must be at least 0.',
            'min_decay_score.max' => 'Decay score threshold cannot exceed 100.',
            'digest_frequency.in' => 'Digest frequency must be weekly or monthly.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize XSS in settings_json
        if ($this->has('settings_json') && is_array($this->input('settings_json'))) {
            $sanitized = [];
            foreach ($this->input('settings_json') as $key => $value) {
                $sanitized[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            }
            $this->merge(['settings_json' => $sanitized]);
        }
    }
}
