<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for SEO site audits.
    |
    */

    'crawl' => [
        'max_depth' => env('AUDIT_MAX_DEPTH', 3),
        'max_pages' => env('AUDIT_MAX_PAGES', 500),
        'requests_per_second' => env('AUDIT_RATE_LIMIT', 2),
        'max_concurrent' => env('AUDIT_MAX_CONCURRENT', 5),
        'timeout' => env('AUDIT_TIMEOUT', 30),
        'user_agent' => env('AUDIT_USER_AGENT', 'SEOIndicator-Bot/1.0'),
    ],

    'rate_limits' => [
        'trigger_per_hour' => env('AUDIT_TRIGGER_RATE_LIMIT', 5),
    ],
];
