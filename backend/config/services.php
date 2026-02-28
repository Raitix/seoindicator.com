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
    | Google OAuth for Search Console Integration
    |--------------------------------------------------------------------------
    |
    | Credentials for Google OAuth 2.0 flow used to access Google Search Console API.
    | Create credentials at: https://console.cloud.google.com/apis/credentials
    |
    | Required scopes: https://www.googleapis.com/auth/webmasters.readonly
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uris' => array_filter([
            env('GOOGLE_REDIRECT_URI'),
            // Production URI (if different from env)
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

];
