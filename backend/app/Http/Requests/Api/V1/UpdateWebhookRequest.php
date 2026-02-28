<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Webhook;

/**
 * Form request for updating a Webhook.
 *
 * TASK-6-13: Validation for Webhook updates.
 */
class UpdateWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller
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
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'url' => [
                'sometimes',
                'required',
                'string',
                'max:2048',
                'url',
                'regex:/^https?:\/\//i',
                function ($attribute, $value, $fail) {
                    // Block javascript: URLs
                    if (preg_match('/^javascript:/i', $value)) {
                        $fail('The URL must be a valid HTTP or HTTPS URL.');
                    }
                },
            ],
            'events' => [
                'sometimes',
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!array_is_list($value)) {
                        $fail('The events must be a list of event types.');
                    }
                },
            ],
            'events.*' => [
                'string',
                Rule::in(Webhook::VALID_EVENTS),
            ],
            'enabled' => [
                'sometimes',
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
            'name.required' => 'The webhook name is required.',
            'name.max' => 'The webhook name may not be greater than 255 characters.',
            'url.required' => 'The webhook URL is required.',
            'url.max' => 'The webhook URL may not be greater than 2048 characters.',
            'url.url' => 'The URL must be a valid HTTP or HTTPS URL.',
            'url.regex' => 'The URL must start with http:// or https://.',
            'events.required' => 'At least one event must be selected.',
            'events.array' => 'Events must be an array.',
            'events.min' => 'At least one event must be selected.',
            'events.*.in' => 'One or more selected events are invalid.',
            'enabled.boolean' => 'The enabled field must be true or false.',
        ];
    }
}
