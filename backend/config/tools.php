<?php

/**
 * Tools Configuration
 *
 * Configuration for SEO analysis tools including export functionality.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Export Token TTL
    |--------------------------------------------------------------------------
    |
    | Time-to-live in seconds for download tokens generated for tool exports.
    | Tokens expire after this duration and cannot be used for downloads.
    |
    | Default: 3600 (1 hour)
    |
    */
    'export_token_ttl' => env('TOOL_EXPORT_TOKEN_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Export Max Indicators
    |--------------------------------------------------------------------------
    |
    | Maximum number of indicators per export. Larger datasets may cause
    | memory issues during PDF/XLS generation.
    |
    | Default: 500
    |
    */
    'export_max_indicators' => env('TOOL_EXPORT_MAX_INDICATORS', 500),

    /*
    |--------------------------------------------------------------------------
    | Export Rate Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of exports per user per hour.
    |
    | Default: 10
    |
    */
    'export_rate_limit' => env('TOOL_EXPORT_RATE_LIMIT', 10),

];
