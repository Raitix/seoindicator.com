<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google OAuth for Socialite (User Authentication)
    |--------------------------------------------------------------------------
    |
    | Credentials for Google OAuth 2.0 flow used for user authentication via
    | Laravel Socialite. Create credentials at:
    | https://console.cloud.google.com/apis/credentials
    |
    | Required scopes: email, profile (default from Socialite)
    |
    | Setup Instructions:
    | 1. Go to Google Cloud Console: https://console.cloud.google.com
    | 2. Create/select a project (e.g., "SEOIndicator")
    | 3. Go to APIs & Services > Credentials
    | 4. Create OAuth 2.0 Client ID (Web application)
    | 5. Add authorized JavaScript origins:
    |    - Development: http://localhost:3000
    |    - Production: https://seoindicator.com
    | 6. Add authorized redirect URIs:
    |    - Development: http://localhost:8000/api/v1/auth/google/callback
    |    - Production: https://seoindicator.com/api/v1/auth/google/callback
    | 7. Copy Client ID and Client Secret to .env
    |
    | FREE Tier: 100 OAuth logins per day
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        // Additional redirect URIs for multi-environment support
        'redirect_uris' => array_filter([
            env('GOOGLE_REDIRECT_URI'),
            env('GOOGLE_REDIRECT_URI_PROD'),
        ]),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Custom Search API
    |--------------------------------------------------------------------------
    |
    | Credentials for Google Custom Search JSON API (free tier: 100 queries/day).
    | Create a Custom Search Engine at: https://programmablesearchengine.google.com/
    | Get API key from: https://console.cloud.google.com/apis/credentials
    |
    */
    'google_cse' => [
        'api_key' => env('GOOGLE_CSE_API_KEY'),
        'search_engine_id' => env('GOOGLE_CSE_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenRouter API
    |--------------------------------------------------------------------------
    |
    | Credentials for OpenRouter API - a unified API for multiple LLM providers.
    | Get your API key at: https://openrouter.ai/keys
    |
    | FREE to query model list and pricing data.
    | Pricing varies by model (many free tier models available).
    |
    */
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal API
    |--------------------------------------------------------------------------
    |
    | Credentials for PayPal REST API (Orders API v2).
    | Get your credentials at: https://developer.paypal.com/developer/applications/
    |
    | Sandbox mode for development, live for production.
    |
    */
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'return_url' => env('PAYPAL_RETURN_URL'),
        'cancel_url' => env('PAYPAL_CANCEL_URL'),
    ],

];
