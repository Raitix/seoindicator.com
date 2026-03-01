<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form request for validating AI suggestion requests.
 *
 * TASK-21-5: Create AI Suggestion API Endpoint
 *
 * Validates:
 * - indicator: required, enum of valid SEO indicators
 * - context: required, array, max 5000 chars total
 * - provider: optional, enum of valid AI providers
 * - debug: optional, boolean
 */
class AISuggestionRequest extends FormRequest
{
    /**
     * Valid SEO indicator types.
     */
    public const VALID_INDICATORS = [
        'title',
        'meta_description',
        'robots',
        'sitemap',
        'headings',
        'image_alt',
        'filenames',
        'url',
        'links',
        '404',
    ];

    /**
     * Valid AI provider types.
     */
    public const VALID_PROVIDERS = [
        'openrouter',
        'vercel',
        'static',
    ];

    /**
     * Maximum total characters for context.
     */
    public const MAX_CONTEXT_CHARS = 5000;

    /**
     * Determine if the user is authorized to make this request.
     *
     * AI suggestions endpoint is public (with rate limiting).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'indicator' => [
                'required',
                'string',
                'in:' . implode(',', self::VALID_INDICATORS),
            ],
            'context' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    // Only validate character count if value is actually an array
                    // (the 'array' rule runs first and will reject non-arrays)
                    if (!is_array($value)) {
                        return;
                    }
                    
                    $totalChars = 0;
                    array_walk_recursive($value, function ($item) use (&$totalChars) {
                        if (is_string($item)) {
                            $totalChars += strlen($item);
                        }
                    });

                    if ($totalChars > self::MAX_CONTEXT_CHARS) {
                        $fail("The {$attribute} may not exceed " . self::MAX_CONTEXT_CHARS . ' characters total.');
                    }
                },
            ],
            'provider' => [
                'nullable',
                'string',
                'in:' . implode(',', self::VALID_PROVIDERS),
            ],
            'debug' => [
                'nullable',
                'boolean',
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
            'indicator.required' => 'The indicator field is required.',
            'indicator.in' => 'The selected indicator is invalid. Valid options: ' . implode(', ', self::VALID_INDICATORS),
            'context.required' => 'The context field is required.',
            'context.array' => 'The context must be an array.',
            'provider.in' => 'The selected provider is invalid. Valid options: ' . implode(', ', self::VALID_PROVIDERS),
            'debug.boolean' => 'The debug field must be true or false.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Get the validated indicator.
     *
     * @return string
     */
    public function getIndicator(): string
    {
        return $this->validated()['indicator'];
    }

    /**
     * Get the validated context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->validated()['context'];
    }

    /**
     * Get the provider if specified, or null.
     *
     * @return string|null
     */
    public function getProvider(): ?string
    {
        return $this->validated()['provider'] ?? null;
    }

    /**
     * Check if debug mode is requested.
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return (bool) ($this->validated()['debug'] ?? false);
    }
}
