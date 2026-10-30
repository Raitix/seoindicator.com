<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenRouter API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenRouter API key for authentication. Get your key at:
    | https://openrouter.ai/keys
    |
    */
    'api_key' => env('OPENROUTER_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the OpenRouter API. Can be overridden for testing
    | or custom endpoints.
    |
    */
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Default Model
    |--------------------------------------------------------------------------
    |
    | The default AI model to use for generation. This should be a free-tier
    | model for production use.
    |
    | Available free models:
    | - google/gemini-2.0-flash-exp:free
    | - qwen/qwen-2-7b-instruct:free
    | - mistralai/mistral-7b-instruct:free
    | - meta-llama/llama-3-8b-instruct:free
    |
    */
    'default_model' => env('OPENROUTER_MODEL', 'google/gemini-2.0-flash-exp:free'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests. Larger responses may require
    | longer timeouts (e.g., 2 minutes for comprehensive analyses).
    |
    */
    'timeout' => env('OPENROUTER_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Maximum Tokens
    |--------------------------------------------------------------------------
    |
    | The maximum number of tokens to generate in the response. Higher values
    | allow for more comprehensive responses but may increase latency.
    |
    */
    'max_tokens' => env('OPENROUTER_MAX_TOKENS', 4000),

    /*
    |--------------------------------------------------------------------------
    | Free Tier Models
    |--------------------------------------------------------------------------
    |
    | Configuration for free-tier models with their metadata including
    | display names and context window sizes.
    |
    */
    'free_models' => [
        'google/gemini-2.0-flash-exp:free' => [
            'name' => 'Gemini 2.0 Flash',
            'context_window' => 1000000,
            'provider' => 'google',
        ],
        'qwen/qwen-2-7b-instruct:free' => [
            'name' => 'Qwen 2 7B',
            'context_window' => 32768,
            'provider' => 'qwen',
        ],
        'mistralai/mistral-7b-instruct:free' => [
            'name' => 'Mistral 7B',
            'context_window' => 32768,
            'provider' => 'mistral',
        ],
        'meta-llama/llama-3-8b-instruct:free' => [
            'name' => 'Llama 3 8B',
            'context_window' => 8192,
            'provider' => 'meta',
        ],
    ],
];
