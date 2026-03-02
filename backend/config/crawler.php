<?php

/**
 * Crawler Service Configuration
 *
 * Configuration for the headless crawler service that renders JavaScript-heavy
 * pages and extracts SEO-relevant content.
 *
 * @see SPEC-022
 * @see TASK-22-3
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum Crawl Depth
    |--------------------------------------------------------------------------
    |
    | The maximum depth to crawl from the starting URL.
    | 0 = page only, 1 = sub-pages, 2 = sub-sub-pages.
    |
    */

    'max_depth' => env('CRAWLER_MAX_DEPTH', 2),

    /*
    |--------------------------------------------------------------------------
    | Maximum Pages per Job
    |--------------------------------------------------------------------------
    |
    | The maximum number of pages to crawl per job.
    | This prevents runaway crawls on large sites.
    |
    */

    'max_pages' => env('CRAWLER_MAX_PAGES', 50),

    /*
    |--------------------------------------------------------------------------
    | Maximum Content Size
    |--------------------------------------------------------------------------
    |
    | The maximum total content size in bytes per job.
    | Default: 3MB (3 * 1024 * 1024 = 3,145,728 bytes).
    | This prevents memory exhaustion from large crawls.
    |
    */

    'max_content_size' => env('CRAWLER_MAX_CONTENT_SIZE', 3 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Page Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum time in seconds to wait for a single page to render.
    | This includes navigation, JS execution, and content extraction.
    |
    */

    'page_timeout' => env('CRAWLER_PAGE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Job Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum time in seconds for an entire crawl job to complete.
    | Default: 5 minutes (300 seconds).
    |
    */

    'job_timeout' => env('CRAWLER_JOB_TIMEOUT', 300),

    /*
    |--------------------------------------------------------------------------
    | Retention Days
    |--------------------------------------------------------------------------
    |
    | The number of days to keep crawl job data before automatic cleanup.
    | After this period, jobs and their pages are deleted.
    |
    */

    'retention_days' => env('CRAWLER_RETENTION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Timeout Configuration (TASK-22-17)
    |--------------------------------------------------------------------------
    |
    | Timeout settings for crawl jobs, pages, browser instances, and connections.
    | All values are in seconds.
    |
    | @see \App\Services\Crawler\TimeoutHandlerService
    |
    */

    'timeouts' => [
        // Maximum time for an entire crawl job (default: 5 minutes)
        'job' => (int) env('CRAWLER_JOB_TIMEOUT', 300),

        // Maximum time for a single page to render (default: 30 seconds)
        'page' => (int) env('CRAWLER_PAGE_TIMEOUT', 30),

        // Maximum time for browser operations (default: 60 seconds)
        'browser' => (int) env('CRAWLER_BROWSER_TIMEOUT', 60),

        // Maximum time for HTTP connections (default: 10 seconds)
        'connection' => (int) env('CRAWLER_CONNECTION_TIMEOUT', 10),

        // Grace period before hard kill for graceful shutdown (default: 30 seconds)
        'grace_period' => (int) env('CRAWLER_GRACE_PERIOD', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default User Agent
    |--------------------------------------------------------------------------
    |
    | The default user agent string to use when crawling pages.
    | Should identify the crawler and provide contact information.
    |
    */

    'user_agent' => env('CRAWLER_USER_AGENT', 'SEOIndicator/1.0 Crawler'),

    /*
    |--------------------------------------------------------------------------
    | Default Wait Time
    |--------------------------------------------------------------------------
    |
    | The default time in milliseconds to wait after page load
    | before extracting content. Allows for JS rendering.
    |
    */

    'default_wait_time' => env('CRAWLER_DEFAULT_WAIT_TIME', 2000),

    /*
    |--------------------------------------------------------------------------
    | Maximum Redirects
    |--------------------------------------------------------------------------
    |
    | The maximum number of HTTP redirects to follow per page.
    |
    */

    'max_redirects' => env('CRAWLER_MAX_REDIRECTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for crawl job creation and execution.
    | Used by RateLimiterService for comprehensive rate limiting.
    |
    | @see TASK-22-13
    | @see \App\Services\Crawler\RateLimiterService
    |
    */

    'rate_limits' => [
        // Maximum crawl jobs per user per day (default: 10)
        'user_daily' => env('CRAWLER_USER_DAILY_LIMIT', 10),

        // Maximum concurrent jobs per user (default: 3)
        'user_concurrent' => env('CRAWLER_USER_CONCURRENT_LIMIT', 3),

        // Requests per second per domain (default: 1)
        'domain_per_second' => env('CRAWLER_DOMAIN_RATE', 1),

        // Maximum global concurrent browser instances (default: 10)
        'global_concurrent' => env('CRAWLER_GLOBAL_CONCURRENT', 10),

        // Window duration in hours for daily limit (default: 24)
        'window_hours' => env('CRAWLER_WINDOW_HOURS', 24),

        // Legacy keys for backward compatibility
        'daily_jobs_per_user' => env('CRAWLER_DAILY_LIMIT', 10),
        'concurrent_jobs_per_user' => env('CRAWLER_CONCURRENT_LIMIT', 3),
        'domain_delay_ms' => env('CRAWLER_DOMAIN_DELAY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Browser Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the headless browser instance.
    |
    */

    'browser' => [
        // Maximum memory per browser instance (MB)
        'max_memory_mb' => env('CRAWLER_BROWSER_MEMORY', 512),

        // Browser to use (chromium, firefox, webkit)
        'browser' => env('CRAWLER_BROWSER', 'chromium'),

        // Whether to run in headless mode
        'headless' => env('CRAWLER_HEADLESS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSRF Protection
    |--------------------------------------------------------------------------
    |
    | Blocked hosts and IP ranges for SSRF protection.
    |
    */

    'ssrf' => [
        // Blocked IP ranges (CIDR notation)
        'blocked_ip_ranges' => [
            '127.0.0.0/8',      // Localhost
            '10.0.0.0/8',       // Private Class A
            '172.16.0.0/12',    // Private Class B
            '192.168.0.0/16',   // Private Class C
            '169.254.0.0/16',   // Link-local (AWS metadata)
            '::1/128',          // IPv6 localhost
            'fe80::/10',        // IPv6 link-local
        ],

        // Blocked host patterns
        'blocked_hosts' => [
            'localhost',
            '*.local',
            '*.internal',
            '*.localhost',
        ],

        // Blocked protocols
        'blocked_protocols' => [
            'file',
            'ftp',
            'gopher',
            'data',
            'javascript',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the URL validator service (SSRF protection).
    | Used by UrlValidatorService for comprehensive URL validation.
    |
    */

    // Allowed protocols for URL validation
    'allowed_protocols' => ['http', 'https'],

    // Additional blocked IPs (beyond standard private ranges)
    'blocked_ips' => [],

    // Blocked domain patterns (wildcards supported)
    'blocked_domains' => [
        '*.internal',
        '*.local',
        '*.localhost',
        '*.internal.*',
    ],

    // Allowed domains whitelist (overrides blocked_domains)
    // Empty array = no whitelist bypass
    'allowed_domains' => [],

    // DNS cache TTL in seconds
    'dns_cache_ttl' => env('CRAWLER_DNS_CACHE_TTL', 60),

];
