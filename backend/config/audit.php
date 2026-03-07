<?php

/**
 * SEO Audit Configuration.
 *
 * Controls crawler behavior for technical SEO audits.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Crawl Settings
    |--------------------------------------------------------------------------
    |
    | These settings control how the SiteCrawler discovers and fetches pages.
    |
    */

    'crawl' => [
        // Maximum depth to crawl from the starting URL
        'max_depth' => env('AUDIT_MAX_DEPTH', 3),

        // Maximum number of pages to crawl per audit
        'max_pages' => env('AUDIT_MAX_PAGES', 500),

        // Requests per second per domain (rate limiting)
        'requests_per_second' => env('AUDIT_RATE_LIMIT', 2),

        // Maximum concurrent requests
        'max_concurrent' => env('AUDIT_MAX_CONCURRENT', 5),

        // HTTP timeout in seconds
        'timeout' => env('AUDIT_TIMEOUT', 30),

        // User agent string for crawler
        'user_agent' => env('AUDIT_USER_AGENT', 'SEOIndicator-Bot/1.0 (+https://seoindicator.com/bot)'),

        // Maximum URL length to process
        'max_url_length' => env('AUDIT_MAX_URL_LENGTH', 2048),

        // Maximum HTML content size in bytes (100MB default)
        'max_html_size' => env('AUDIT_MAX_HTML_SIZE', 104857600),

        // Maximum redirects to follow
        'max_redirects' => env('AUDIT_MAX_REDIRECTS', 5),

        // Maximum crawl duration in seconds (1 hour default)
        'max_duration' => env('AUDIT_MAX_DURATION', 3600),

        // Enable/disable robots.txt compliance
        'respect_robots_txt' => env('AUDIT_RESPECT_ROBOTS', true),

        // Cache TTL for robots.txt in seconds (1 hour default)
        'robots_cache_ttl' => env('AUDIT_ROBOTS_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSRF Protection
    |--------------------------------------------------------------------------
    |
    | Block requests to internal/private networks for security.
    |
    */

    'ssrf' => [
        // Enable SSRF protection
        'enabled' => env('AUDIT_SSRF_PROTECTION', true),

        // Blocked IP ranges (CIDR notation)
        'blocked_ranges' => [
            '127.0.0.0/8',       // Localhost
            '10.0.0.0/8',        // Private Class A
            '172.16.0.0/12',     // Private Class B
            '192.168.0.0/16',    // Private Class C
            '169.254.169.254',   // Cloud metadata (AWS/GCP/Azure)
            '::1/128',           // IPv6 localhost
            'fc00::/7',          // IPv6 ULA
            'fe80::/10',         // IPv6 link-local
        ],

        // Blocked hostnames
        'blocked_hosts' => [
            'localhost',
            'localhost.localdomain',
            'ip6-localhost',
            'ip6-loopback',
            'metadata.google.internal',
            'metadata',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the crawl queue persistence.
    |
    */

    'queue' => [
        // Queue connection for crawl jobs
        'connection' => env('AUDIT_QUEUE_CONNECTION', 'redis'),

        // Queue name
        'name' => env('AUDIT_QUEUE_NAME', 'seo-audit'),

        // Job retry limit
        'retry_limit' => env('AUDIT_RETRY_LIMIT', 3),

        // Lock TTL for crawl state (30 minutes)
        'lock_ttl' => env('AUDIT_LOCK_TTL', 1800),
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Normalization
    |--------------------------------------------------------------------------
    |
    | Settings for URL normalization and deduplication.
    |
    */

    'normalization' => [
        // Strip URL fragments (#section)
        'strip_fragments' => true,

        // Strip tracking parameters
        'strip_tracking_params' => true,

        // Tracking parameters to strip
        'tracking_params' => [
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'fbclid', 'gclid', 'msclkid', 'ref', 'source',
        ],

        // Normalize trailing slashes (null = keep as-is)
        'normalize_trailing_slash' => null,

        // Sort query parameters
        'sort_query_params' => true,

        // Case-sensitive URL paths
        'case_sensitive_paths' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings for performance-related SEO checks.
    |
    */

    'performance' => [
        // Response time threshold for "slow" pages (milliseconds)
        'slow_threshold_ms' => env('AUDIT_SLOW_THRESHOLD', 3000),

        // Response time threshold for "very slow" pages (milliseconds)
        'very_slow_threshold_ms' => env('AUDIT_VERY_SLOW_THRESHOLD', 5000),

        // Response time threshold for "acceptable" pages (milliseconds)
        'acceptable_threshold_ms' => env('AUDIT_ACCEPTABLE_THRESHOLD', 1000),

        // Response time threshold for "needs improvement" pages (milliseconds)
        'needs_improvement_threshold_ms' => env('AUDIT_NEEDS_IMPROVEMENT_THRESHOLD', 2000),

        // Maximum page size in KB before flagging as large
        'max_page_size_kb' => env('AUDIT_MAX_PAGE_SIZE', 500),
    ],
];
