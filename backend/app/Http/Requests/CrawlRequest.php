<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Rules\ValidCrawlUrl;
use App\Services\Crawler\RateLimiterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

/**
 * Request validation for crawl job creation.
 *
 * Validates URL, depth, project_id, and crawl options for the Headless Crawler Service.
 * Includes SSRF protection via ValidCrawlUrl rule and rate limiting integration.
 *
 * @see TASK-22-12
 * @see SPEC-022
 */
class CrawlRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Checks:
     * - User is authenticated
     * - If project_id provided, user owns the project
     * - User hasn't exceeded rate limits (handled by middleware)
     */
    public function authorize(): bool
    {
        // User must be authenticated
        if (!Auth::check()) {
            return false;
        }

        // If project_id is provided, verify user owns it
        $projectId = $this->input('project_id');
        if ($projectId !== null) {
            $project = Project::find($projectId);
            if (!$project || $project->user_id !== Auth::id()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'You are not authorized to perform this action.',
        ], 403));
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
                new ValidCrawlUrl(),
            ],
            'project_id' => [
                'nullable',
                'integer',
                'exists:projects,id',
            ],
            'depth' => ['nullable', 'integer', 'min:0', 'max:2'],
            'options' => ['nullable', 'array'],
            'options.waitForSelector' => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    // Block path traversal patterns in CSS selector
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
            'options.userAgent' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9_\-\.\s\/\(\):;,]+$/',
            ],
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
            'project_id.integer' => 'The project ID must be a valid number.',
            'project_id.exists' => 'The selected project does not exist.',
            'depth.min' => 'The depth must be at least 0.',
            'depth.max' => 'The depth must not exceed 2.',
            'options.waitTime.min' => 'The wait time must be at least 0 milliseconds.',
            'options.waitTime.max' => 'The wait time must not exceed 30000 milliseconds.',
            'options.userAgent.regex' => 'The user agent contains invalid characters.',
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

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Rate limiting is handled by CrawlerRateLimit middleware
            // This is just for additional validation if needed
        });
    }
}
