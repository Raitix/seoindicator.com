<?php

/**
 * Request Tracking Configuration
 * 
 * Configuration for the Request Tracking Middleware and Service.
 * Tracks API requests for usage analytics.
 * 
 * @see TASK-27-6: Request Tracking Middleware
 * @see SPEC-027: Admin Dashboard (Ops + Usage)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Request Tracking
    |--------------------------------------------------------------------------
    |
    | Set to false to completely disable request tracking. Useful for
    | development or when Redis is not available.
    |
    */

    'enabled' => env('REQUEST_TRACKING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Global Sample Rate
    |--------------------------------------------------------------------------
    |
    | The percentage of requests to track (0.0 to 1.0).
    | 1.0 = track all requests, 0.0 = track no requests.
    |
    */

    'sample_rate' => env('REQUEST_TRACKING_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Excluded Endpoints
    |--------------------------------------------------------------------------
    |
    | Endpoints that should never be tracked. Supports wildcards (*).
    | Health check endpoints are excluded by default.
    |
    */

    'excluded_endpoints' => [
        'api/v1/health',
        'api/v1/health/*',
        'health',
        'healthz',
        'ping',
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-Endpoint Sample Rates
    |--------------------------------------------------------------------------
    |
    | Override sample rates for specific endpoints. Useful for high-traffic
    | endpoints where you want to track fewer requests.
    |
    | Example: 'api/v1/high-traffic' => 0.1 (track 10% of requests)
    |
    */

    'endpoint_sample_rates' => [
        // 'api/v1/high-traffic' => 0.1,
        // 'api/v1/metrics' => 0.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Key Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for all Redis keys used by the tracking service.
    |
    */

    'key_prefix' => env('REQUEST_TRACKING_KEY_PREFIX', 'request_tracking:'),

    /*
    |--------------------------------------------------------------------------
    | Redis Key TTL
    |--------------------------------------------------------------------------
    |
    | Time-to-live in seconds for Redis tracking keys.
    | Default: 86400 (24 hours)
    |
    */

    'ttl' => env('REQUEST_TRACKING_TTL', 86400),

];
