<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Request validation for API Proxy analyze endpoint (TASK-16-1, TASK-16-2, TASK-16-3).
 */
class ProxyAnalyzeRequest extends FormRequest
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
            'url' => ['required', 'string', 'max:2048'],
            'tool' => ['nullable', 'string', 'in:meta-tag-analyzer,keyword-density-checker'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
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
            'url.required' => 'URL is required.',
            'url.string' => 'URL must be a valid string.',
            'url.max' => 'URL is too long. Maximum length is 2048 characters.',
            'tool.in' => 'Invalid tool specified. Valid options: meta-tag-analyzer, keyword-density-checker.',
            'keyword.max' => 'Keyword is too long. Maximum length is 255 characters.',
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
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => $validator->errors()->messages(),
            ],
        ], 422));
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // If tool is keyword-density-checker, keyword is required
            if ($this->input('tool') === 'keyword-density-checker' && empty($this->input('keyword'))) {
                $validator->errors()->add('keyword', 'Keyword is required when using keyword-density-checker tool.');
            }
        });
    }
}
