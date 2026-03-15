<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Health Check Cache TTL
    |--------------------------------------------------------------------------
    |
    | The number of seconds to cache health check results.
    | Default is 30 seconds to prevent overwhelming checks.
    |
    */
    'cache_ttl' => env('HEALTH_CHECK_CACHE_TTL', 30),

    /*
    |--------------------------------------------------------------------------
    | Health Check Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for determining healthy, degraded, and unhealthy states.
    | Values at or above warning/degraded thresholds trigger degraded status.
    | Values at or above critical thresholds trigger unhealthy status.
    |
    */
    'thresholds' => [
        // Database latency thresholds (milliseconds)
        'database_latency_warning' => env('HEALTH_CHECK_DB_LATENCY_WARNING', 100),
        'database_latency_critical' => env('HEALTH_CHECK_DB_LATENCY_CRITICAL', 500),

        // Cache hit rate threshold (percent)
        'cache_hit_rate_warning' => env('HEALTH_CHECK_CACHE_HIT_RATE_WARNING', 80),

        // Storage usage thresholds (percent)
        'storage_usage_warning' => env('HEALTH_CHECK_STORAGE_USAGE_WARNING', 80),
        'storage_usage_critical' => env('HEALTH_CHECK_STORAGE_USAGE_CRITICAL', 95),

        // Queue job thresholds
        'queue_pending_warning' => env('HEALTH_CHECK_QUEUE_PENDING_WARNING', 100),
        'queue_failed_critical' => env('HEALTH_CHECK_QUEUE_FAILED_CRITICAL', 10),
    ],
];
