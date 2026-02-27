<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ApiKey;

/**
 * Form request for storing a new API Key.
 *
 * TASK-6-4: Validation for API Key creation.
 */
class StoreApiKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
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
                'required',
                'string',
                'max:255',
            ],
            'permissions' => [
                'nullable',
                'array',
            ],
            'permissions.*' => [
                'string',
                Rule::in(ApiKey::VALID_PERMISSIONS),
            ],
            'rate_limit' => [
                'nullable',
                'integer',
                'min:' . ApiKey::MIN_RATE_LIMIT,
                'max:' . ApiKey::MAX_RATE_LIMIT,
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
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
            'name.required' => 'The API key name is required.',
            'name.max' => 'The API key name may not be greater than 255 characters.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.in' => 'One or more permissions are invalid.',
            'rate_limit.integer' => 'The rate limit must be an integer.',
            'rate_limit.min' => 'The rate limit must be at least ' . ApiKey::MIN_RATE_LIMIT . '.',
            'rate_limit.max' => 'The rate limit may not be greater than ' . ApiKey::MAX_RATE_LIMIT . '.',
            'expires_at.date' => 'The expiration date must be a valid date.',
            'expires_at.after' => 'The expiration date must be in the future.',
        ];
    }
}
