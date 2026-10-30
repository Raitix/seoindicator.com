<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertSettingsRequest extends FormRequest
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
            'global_enabled' => ['sometimes', 'boolean'],
            'default_threshold' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'quiet_hours_start' => ['sometimes', 'nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['sometimes', 'nullable', 'date_format:H:i'],
            'digest_mode' => ['sometimes', Rule::in(['instant', 'daily', 'weekly'])],
            'serp_feature_alerts_enabled' => ['sometimes', 'boolean'],
            'serp_feature_types' => ['sometimes', 'nullable', 'array'],
            'serp_feature_types.*' => ['string', 'in:featured_snippet,local_pack,knowledge_panel,image_pack,video_carousel,people_also_ask,sitelinks'],
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
            'default_threshold.min' => 'The threshold must be at least 1 position.',
            'default_threshold.max' => 'The threshold cannot exceed 100 positions.',
            'quiet_hours_start.date_format' => 'Quiet hours start must be in HH:MM format.',
            'quiet_hours_end.date_format' => 'Quiet hours end must be in HH:MM format.',
            'digest_mode.in' => 'Digest mode must be one of: instant, daily, weekly.',
        ];
    }
}
