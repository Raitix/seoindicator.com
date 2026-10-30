<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Keyword Request for REST API.
 * 
 * TASK-6-8: Keywords API Endpoints
 */
class StoreKeywordApiRequest extends FormRequest
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
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'keyword' => ['required', 'string', 'max:500'],
            'target_url' => ['nullable', 'url', 'max:2048'],
            'search_engine' => ['nullable', 'string', 'in:google'],
            'location' => ['nullable', 'string', 'max:10'],
            'device' => ['nullable', 'string', 'in:desktop,mobile'],
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
            'site_id.required' => 'The site_id field is required.',
            'site_id.exists' => 'The selected site does not exist.',
            'keyword.required' => 'The keyword field is required.',
            'keyword.max' => 'The keyword may not be greater than 500 characters.',
            'target_url.url' => 'The target_url must be a valid URL.',
            'search_engine.in' => 'The search_engine must be one of: google.',
            'device.in' => 'The device must be one of: desktop, mobile.',
        ];
    }
}
