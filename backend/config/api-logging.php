<?php

/**
 * API Logging Configuration for TASK-6-6.
 *
 * Controls API request logging behavior for analytics and debugging.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Enable API Request Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, all API requests authenticated via API key will be logged
    | to the api_request_logs table for analytics and debugging purposes.
    |
    */
    'enabled' => env('API_LOGGING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Retention Period (Days)
    |--------------------------------------------------------------------------
    |
    | The number of days to retain API request logs. Logs older than this
    | period can be cleaned up via the api-logs:cleanup artisan command.
    |
    */
    'retention_days' => env('API_LOGGING_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Excluded Endpoints
    |--------------------------------------------------------------------------
    |
    | Endpoints that should not be logged. Supports wildcard patterns using *.
    | Health check endpoints are excluded by default to avoid noise.
    |
    */
    'excluded_endpoints' => [
        'api/v1/health',
        'api/v1/health/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Async Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, logs are written asynchronously via Laravel's queue system.
    | This ensures minimal impact on request response times.
    |
    */
    'async_logging' => env('API_LOGGING_ASYNC', true),
];
