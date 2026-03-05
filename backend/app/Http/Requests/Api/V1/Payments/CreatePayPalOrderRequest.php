<?php

namespace App\Http\Requests\Api\V1\Payments;

use App\Models\CreditPackage;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request validation for PayPal Order Creation.
 *
 * @see TASK-26-13
 * @see FEAT-026
 */
class CreatePayPalOrderRequest extends FormRequest
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
            'package_id' => [
                'required',
                'integer',
                'exists:credit_packages,id',
            ],
            'return_url' => [
                'required',
                'url',
                'max:2048',
                'regex:/^https:\/\//i', // Must be HTTPS
            ],
            'cancel_url' => [
                'required',
                'url',
                'max:2048',
                'regex:/^https:\/\//i', // Must be HTTPS
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
            'package_id.required' => 'The package ID is required.',
            'package_id.integer' => 'The package ID must be a valid number.',
            'package_id.exists' => 'The selected package is invalid.',
            'return_url.required' => 'The return URL is required.',
            'return_url.url' => 'The return URL must be a valid URL.',
            'return_url.regex' => 'The return URL must use HTTPS.',
            'cancel_url.required' => 'The cancel URL is required.',
            'cancel_url.url' => 'The cancel URL must be a valid URL.',
            'cancel_url.regex' => 'The cancel URL must use HTTPS.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => $validator->errors(),
            ],
        ], 422));
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation: Check if package is active
            $packageId = $this->input('package_id');
            if ($packageId) {
                $package = CreditPackage::find($packageId);
                if ($package && !$package->is_active) {
                    $validator->errors()->add(
                        'package_id',
                        'The selected package is not available for purchase.'
                    );
                }
            }
        });
    }

    /**
     * Get the validated package.
     */
    public function getPackage(): ?CreditPackage
    {
        return CreditPackage::find($this->validated('package_id'));
    }
}
