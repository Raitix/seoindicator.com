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
    | - baidu/ernie-5.5-flash-preview:free
    | - nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free
    | - liquid/lfm-3-1.7b:free
    | - liquid/lfm-2.5-2.6b:free
    |
    */
    'default_model' => env('OPENROUTER_MODEL', 'baidu/ernie-5.5-flash-preview:free'),

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
        'baidu/ernie-5.5-flash-preview:free' => [
            'name' => 'ERNIE 5.5 Flash',
            'context_window' => 1024000,
            'provider' => 'baidu',
        ],
        'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free' => [
            'name' => 'Nemotron 3 Nano Omni 30B',
            'context_window' => 256000,
            'provider' => 'nvidia',
        ],
        'liquid/lfm-3-1.7b:free' => [
            'name' => 'LFM 3 1.7B',
            'context_window' => 262144,
            'provider' => 'liquid',
        ],
        'liquid/lfm-2.5-2.6b:free' => [
            'name' => 'LFM 2.5 2.6B',
            'context_window' => 65536,
            'provider' => 'liquid',
        ],
    ],
];
