<?php

namespace App\Http\Requests\Api\V1\LocalSEO;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @Task TASK-35-13
 * @Description Update Business Location Request Validation
 */
class UpdateBusinessLocationRequest extends FormRequest
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
            'name' => ['sometimes', 'filled', 'string', 'max:255', 'regex:/^[^<>]*$/'],
            'address' => ['sometimes', 'filled', 'string', 'max:512', 'regex:/^[^<>]*$/'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[\d\s\+\-\(\)\.]+$/'],
            'website' => ['nullable', 'string', 'url', 'max:2048'],
            'category' => ['nullable', 'string', 'max:100'],
            'hours' => ['nullable', 'array'],
            'hours.*.open' => ['nullable', 'string', 'date_format:H:i'],
            'hours.*.close' => ['nullable', 'string', 'date_format:H:i'],
            'gbp_place_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
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
            'name.max' => 'The business name cannot exceed 255 characters.',
            'address.max' => 'The address cannot exceed 512 characters.',
            'phone.regex' => 'Please enter a valid phone number.',
            'phone.max' => 'The phone number cannot exceed 50 characters.',
            'website.url' => 'Please enter a valid website URL.',
            'website.max' => 'The website URL cannot exceed 2048 characters.',
            'category.max' => 'The category cannot exceed 100 characters.',
            'hours.array' => 'Operating hours must be an array.',
            'hours.*.open.date_format' => 'Opening time must be in HH:MM format.',
            'hours.*.close.date_format' => 'Closing time must be in HH:MM format.',
            'gbp_place_id.max' => 'The Google Business Profile place ID cannot exceed 255 characters.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string inputs
        // Note: We intentionally do NOT convert empty strings to null for name/address
        // so that validation catches them with 'filled' rule
        if ($this->has('name') && $this->name !== '' && $this->name !== null) {
            $this->merge(['name' => trim($this->name)]);
        }
        if ($this->has('address') && $this->address !== '' && $this->address !== null) {
            $this->merge(['address' => trim($this->address)]);
        }
        if ($this->has('phone')) {
            $this->merge(['phone' => $this->phone ? trim($this->phone) : null]);
        }
        if ($this->has('website')) {
            $this->merge(['website' => $this->website ? trim($this->website) : null]);
        }
        if ($this->has('category')) {
            $this->merge(['category' => $this->category ? trim($this->category) : null]);
        }

        // Reject XSS attempts - HTML tags not allowed in name and address
        // Validation will catch these and return 422
    }

    /**
     * Get the validated data with only fillable fields.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        
        // Remove any fields that should not be mass-assigned
        unset(
            $validated['user_id'],
            $validated['site_id'],
            $validated['gbp_access_token'],
            $validated['gbp_refresh_token'],
            $validated['gbp_token_expires_at'],
            $validated['gbp_connected']
        );

        return $validated;
    }
}
