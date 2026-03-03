<?php

namespace App\Http\Requests\Api\V1;

use App\Models\AIModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Form Request for setting user model preferences.
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
            'model_id' => ['required', 'integer', 'exists:ai_models,id'],
            'is_default' => ['sometimes', 'boolean'],
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
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Check if model is active
            $modelId = $this->input('model_id');
            
            if ($modelId) {
                $model = AIModel::find($modelId);
                
                if ($model && !$model->is_active) {
                    $validator->errors()->add(
                        'model_id',
                        'The selected model is not active and cannot be set as a preference.'
                    );
                }
            }
        });
    }
}
