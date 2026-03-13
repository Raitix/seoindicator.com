<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request validation for crawl job creation.
 *
 * Validates URL, depth, and crawl options for the Headless Crawler Service.
 *
 * @see TASK-22-11
 * @see SPEC-022
 */
class CrawlRequest extends FormRequest
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
            'url' => [
                'required',
                'string',
                'max:2083',
                'url',
                function ($attribute, $value, $fail) {
                    // SSRF protection - these will be validated by the service
                    // but we do basic checks here to fail fast
                    $blockedPatterns = [
                        '/^https?:\/\/localhost/i',
                        '/^https?:\/\/127\./i',
                        '/^https?:\/\/10\./i',
                        '/^https?:\/\/172\.(1[6-9]|2[0-9]|3[0-1])\./i',
                        '/^https?:\/\/192\.168\./i',
                        '/^https?:\/\/169\.254\./i',
                        '/^https?:\/\/\[::1\]/i',
                        '/^https?:\/\/\[fe80::/i',
                        '/\.local$/i',
                        '/\.internal$/i',
                        '/^https?:\/\/internal\./i',  // Block subdomains starting with "internal."
                        '/^file:\/\//i',
                        '/^ftp:\/\//i',
                        '/^gopher:\/\//i',
                    ];

                    foreach ($blockedPatterns as $pattern) {
                        if (preg_match($pattern, $value)) {
                            $fail('The URL is not allowed.');
                            return;
                        }
                    }
                },
            ],
            'depth' => ['nullable', 'integer', 'min:0', 'max:2'],
            'options' => ['nullable', 'array'],
            'options.waitForSelector' => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    // Block path traversal patterns in CSS selector
                    // CSS selectors can contain these characters, but we block obvious path traversal attempts
                    $blockedPatterns = [
                        '/\.\./',           // Parent directory traversal
                        '#/etc/#i',         // Unix system paths
                        '#/var/#i',         // Unix var directory
                        '#/usr/#i',         // Unix usr directory
                        '#\\\windows\\\#i', // Windows system paths
                        '#\\\system32\\\#i', // Windows system32
                        '/[<>:"|?*]/',      // Invalid filename characters (Windows)
                    ];

                    foreach ($blockedPatterns as $pattern) {
                        if (preg_match($pattern, $value)) {
                            $fail('The selector contains invalid characters.');
                            return;
                        }
                    }
                },
            ],
            'options.waitTime' => ['nullable', 'integer', 'min:0', 'max:30000'],
            'options.userAgent' => ['nullable', 'string', 'max:500'],
            'options.includeImages' => ['nullable', 'boolean'],
            'options.followRedirects' => ['nullable', 'boolean'],
            'options.maxRedirects' => ['nullable', 'integer', 'min:0', 'max:10'],
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
            'url.required' => 'The URL is required.',
            'url.url' => 'Please provide a valid URL.',
            'url.max' => 'The URL must not exceed 2083 characters.',
            'depth.min' => 'The depth must be at least 0.',
            'depth.max' => 'The depth must not exceed 2.',
            'options.waitTime.min' => 'The wait time must be at least 0 milliseconds.',
            'options.waitTime.max' => 'The wait time must not exceed 30000 milliseconds.',
            'options.maxRedirects.min' => 'The max redirects must be at least 0.',
            'options.maxRedirects.max' => 'The max redirects must not exceed 10.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults
        $this->merge([
            'depth' => $this->depth ?? 0,
            'options' => array_merge([
                'waitTime' => 2000,
                'userAgent' => 'SEOIndicator/1.0 Crawler',
                'includeImages' => false,
                'followRedirects' => true,
                'maxRedirects' => 5,
            ], $this->options ?? []),
        ]);
    }
}
