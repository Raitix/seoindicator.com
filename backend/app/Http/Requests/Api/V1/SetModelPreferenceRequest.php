<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\AIModel;

/**
 * Form request for setting user model preferences.
 *
 * TASK-26-19: User Model Preferences
 */
class SetModelPreferenceRequest extends FormRequest
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
            'model_id' => [
                'required',
                'integer',
                'exists:ai_models,id',
            ],
            'is_default' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'model_id.required' => 'A model ID is required.',
            'model_id.integer' => 'The model ID must be a valid integer.',
            'model_id.exists' => 'The selected model does not exist.',
            'is_default.boolean' => 'The is_default field must be true or false.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $modelId = $this->input('model_id');

            if ($modelId) {
                $model = AIModel::find($modelId);

                if ($model && !$model->is_active) {
                    $validator->errors()->add(
                        'model_id',
                        'Cannot set an inactive model as preference.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default is_default to false if not provided
        if (!$this->has('is_default')) {
            $this->merge([
                'is_default' => false,
            ]);
        }
    }
}
