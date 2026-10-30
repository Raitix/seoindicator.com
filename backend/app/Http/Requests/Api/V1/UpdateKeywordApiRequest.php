<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Keyword Request for REST API.
 * 
 * TASK-6-8: Keywords API Endpoints
 */
class UpdateKeywordApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['nullable', 'string', 'in:active,paused'],
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
            'target_url.url' => 'The target_url must be a valid URL.',
            'status.in' => 'The status must be one of: active, paused.',
        ];
    }
}
