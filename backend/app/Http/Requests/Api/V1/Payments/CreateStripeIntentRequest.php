<?php

namespace App\Http\Requests\Api\V1\Payments;

use App\Models\CreditPackage;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request validation for Stripe Payment Intent Creation.
 *
 * @see TASK-26-8
 * @see FEAT-026
 */
class CreateStripeIntentRequest extends FormRequest
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
            'amount' => [
                'required',
                'numeric',
                'min:5',
                'max:9999.99',
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
            'amount.required' => 'The amount is required.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The minimum purchase amount is $5.00.',
            'amount.max' => 'The maximum purchase amount is $9,999.99.',
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
            $packageId = $this->input('package_id');
            $amount = $this->input('amount');

            if ($packageId && $amount) {
                $package = CreditPackage::find($packageId);
                
                if ($package) {
                    // Check if package is active
                    if (!$package->is_active) {
                        $validator->errors()->add(
                            'package_id',
                            'The selected package is not available for purchase.'
                        );
                    }
                    
                    // Check if amount matches package price
                    if (abs((float) $amount - (float) $package->price_usd) > 0.01) {
                        $validator->errors()->add(
                            'amount',
                            "The amount must match the package price of \${$package->price_usd}."
                        );
                    }
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
