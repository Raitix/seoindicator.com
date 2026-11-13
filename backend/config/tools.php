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

    /*
    |--------------------------------------------------------------------------
    | Export Rate Window
    |--------------------------------------------------------------------------
    |
    | Time window in seconds for the export rate limit.
    |
    | Default: 3600 (1 hour)
    |
    */
    'export_rate_window' => env('TOOL_EXPORT_RATE_WINDOW', 3600),

    /*
    |--------------------------------------------------------------------------
    | Analysis Rate Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of tool analyses one IP may run per window. The tool
    | makes the server fetch an arbitrary URL, so this is the protection
    | against abuse of the server as a fetch proxy.
    |
    | Three per day is tight enough to stop abuse and loose enough for a
    | reviewer to try the tools a few times. It is configurable because
    | the right number depends on the deployment.
    |
    | Default: 20
    |
    */
    'analysis_rate_limit' => env('TOOL_ANALYSIS_RATE_LIMIT', 20),

    /*
    |--------------------------------------------------------------------------
    | Analysis Rate Window
    |--------------------------------------------------------------------------
    |
    | Time window in seconds for the analysis rate limit.
    |
    | Default: 3600 (1 hour)
    |
    */
    'analysis_rate_window' => env('TOOL_ANALYSIS_RATE_WINDOW', 3600),

];
