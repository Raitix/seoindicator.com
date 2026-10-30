<?php

namespace App\Http\Requests\Api\V1\Payments;

use App\Models\CreditPackage;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request validation for Stripe Payment Intent Creation.
 *
 * @see TASK-26-9
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
            'return_url' => [
                'nullable',
                'url',
                'max:2048',
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
            'package_id.exists' => 'Invalid package selected',
            'return_url.url' => 'The return URL must be a valid URL.',
            'return_url.max' => 'The return URL may not be greater than 2048 characters.',
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
            'message' => 'Validation failed',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }

    /**
     * Get the validated package.
     */
    public function getPackage(): ?CreditPackage
    {
        return CreditPackage::find($this->validated('package_id'));
    }
}
