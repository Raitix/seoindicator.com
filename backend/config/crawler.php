<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Crawler Rate Limits
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for the crawler API.
    |
    */

    'rate_limits' => [
        // Maximum number of crawl jobs per user per day
        'daily_jobs' => env('CRAWLER_DAILY_LIMIT', 10),

        // Maximum number of concurrent crawl jobs per user
        'concurrent_jobs' => env('CRAWLER_CONCURRENT_LIMIT', 3),

        // Maximum requests per second to the same domain
        'per_domain_per_second' => env('CRAWLER_DOMAIN_RATE', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawler Content Limits
    |--------------------------------------------------------------------------
    |
    | Configure content size limits for crawl results.
    |
    */

    'content_limits' => [
        // Maximum content size in bytes (default: 3MB)
        'max_content_size' => env('CRAWLER_MAX_CONTENT_SIZE', 3145728),

        // Maximum page size in bytes (default: 10MB)
        'max_page_size' => env('CRAWLER_MAX_PAGE_SIZE', 10485760),
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawler Timeouts
    |--------------------------------------------------------------------------
    |
    | Configure timeouts for crawler operations.
    |
    */

    'timeouts' => [
        // Connection timeout in seconds
        'connect' => env('CRAWLER_CONNECT_TIMEOUT', 10),

        // Request timeout in seconds
        'request' => env('CRAWLER_REQUEST_TIMEOUT', 30),

        // Maximum crawl time per job in seconds
        'max_crawl_time' => env('CRAWLER_MAX_TIME', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawler Defaults
    |--------------------------------------------------------------------------
    |
    | Default values for crawl options.
    |
    */

    'defaults' => [
        // Default crawl depth
        'depth' => env('CRAWLER_DEFAULT_DEPTH', 1),

        // Default user agent
        'user_agent' => env('CRAWLER_USER_AGENT', 'SEOIndicator-Crawler/1.0'),

        // Default wait time in milliseconds
        'wait_time' => env('CRAWLER_DEFAULT_WAIT_TIME', 0),

        // Follow redirects by default
        'follow_redirects' => env('CRAWLER_FOLLOW_REDIRECTS', true),

        // Maximum redirects to follow
        'max_redirects' => env('CRAWLER_MAX_REDIRECTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawler Job Retention
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep crawl job data.
    |
    */

    'retention' => [
        // Days to keep completed jobs
        'completed_jobs' => env('CRAWLER_RETENTION_COMPLETED', 7),

        // Days to keep failed jobs
        'failed_jobs' => env('CRAWLER_RETENTION_FAILED', 30),

        // Days to keep cancelled jobs
        'cancelled_jobs' => env('CRAWLER_RETENTION_CANCELLED', 3),
    ],
];
