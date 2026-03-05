<?php

/**
 * AI Suggestion Service Configuration.
 *
 * Configures AI providers (OpenRouter, Vercel AI Gateway) and
 * related settings for the SEO Indicator AI suggestion system.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Specify the default AI provider to use for suggestions.
    | Supported: 'openrouter', 'vercel', 'fallback'
    |
    */

    'default' => env('AI_PROVIDER', 'openrouter'),

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Configure each AI provider with its API credentials and settings.
    | Providers are selected based on the 'default' setting above or
    | can be specified per-request.
    |
    */

    'providers' => [

        'openrouter' => [
            'api_key' => env('OPENROUTER_API_KEY'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'model' => env('OPENROUTER_MODEL', 'meta-llama/llama-3.1-8b-instruct:free'),
            'timeout' => env('AI_TIMEOUT_SECONDS', 30),
            'max_tokens' => env('AI_MAX_TOKENS', 1000),
        ],

        'vercel' => [
            'api_key' => env('VERCEL_AI_API_KEY'),
            'model' => env('VERCEL_AI_MODEL', 'gpt-4o-mini'),
            'timeout' => env('AI_TIMEOUT_SECONDS', 30),
            'max_tokens' => env('AI_MAX_TOKENS', 1000),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | When AI providers are unavailable, the system can fall back to
    | static, rule-based suggestions to ensure functionality.
    |
    */

    'fallback' => [
        'enabled' => env('AI_FALLBACK_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache AI suggestions to reduce API calls and improve response times.
    | Identical requests will return cached responses within the TTL.
    |
    */

    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', true),
        'ttl_hours' => env('AI_CACHE_TTL_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug mode to include additional information in responses:
    | - Raw prompts sent to AI
    | - Raw responses from AI
    | - Token usage and latency metrics
    |
    | WARNING: Enable only in development/staging environments.
    |
    */

    'debug' => env('AI_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Rate Limits (TASK-21-10)
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for AI suggestion endpoints to prevent abuse
    | and manage API quotas. Uses sliding window algorithm.
    |
    | Per-user limits apply to authenticated users.
    | Per-IP limits apply to anonymous requests.
    |
    */

    'rate_limits' => [
        'user' => [
            'limit' => (int) env('AI_RATE_LIMIT_USER', 20),
            'window_seconds' => 3600, // 1 hour
        ],
        'ip' => [
            'limit' => (int) env('AI_RATE_LIMIT_IP', 10),
            'window_seconds' => 3600, // 1 hour
        ],
        'admin_whitelist' => env('AI_RATE_LIMIT_ADMIN_WHITELIST', true),
    ],

];
