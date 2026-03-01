<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for AI Suggestion API validation.
 *
 * Validates requests to the AI suggestions endpoint,
 * ensuring indicator types and context data are valid.
 */
class AISuggestionRequest extends FormRequest
{
    /**
     * Valid indicator types for AI suggestions.
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
     * Valid provider options.
     */
    public const VALID_PROVIDERS = [
        'openrouter',
        'vercel',
        'static',
    ];

    /**
     * Maximum total characters allowed in context.
     */
    public const MAX_CONTEXT_CHARS = 5000;

    /**
     * Determine if the user is authorized to make this request.
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
                Rule::in(self::VALID_INDICATORS),
            ],
            'context' => [
                'required',
                'array',
                'max_context_length',
            ],
            'provider' => [
                'nullable',
                'string',
                Rule::in(self::VALID_PROVIDERS),
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
     * Get validated data with defaults applied.
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();
        
        $validated['provider'] = $validated['provider'] ?? config('ai.default', 'openrouter');
        $validated['debug'] = $validated['debug'] ?? false;

        return $validated;
    }
}
