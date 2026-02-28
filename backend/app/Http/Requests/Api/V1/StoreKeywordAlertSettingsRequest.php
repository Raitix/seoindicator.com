<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKeywordAlertSettingsRequest extends FormRequest
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
            'enabled' => ['sometimes', 'boolean'],
            'custom_threshold' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'alert_events' => ['sometimes', 'nullable', 'array'],
            'alert_events.*' => ['string', Rule::in([
                'position_drop',
                'position_gain',
                'entered_top_10',
                'left_top_10',
                'entered_top_3',
                'left_top_3',
                'serp_feature_gained',
                'serp_feature_lost',
            ])],
            'notification_channels' => ['sometimes', 'nullable', 'array'],
            'notification_channels.*' => ['string', Rule::in(['email', 'in_app'])],
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
            'custom_threshold.min' => 'The threshold must be at least 1 position.',
            'custom_threshold.max' => 'The threshold cannot exceed 100 positions.',
            'alert_events.*.in' => 'Invalid alert event type selected.',
            'notification_channels.*.in' => 'Invalid notification channel selected.',
        ];
    }
}
