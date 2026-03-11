<?php

/**
 * Configuration for Self-Hosted SERP Scraper (ScraperSERPFetcher)
 *
 * @see TASK-1-9
 * @see SPEC-001
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Scraper
    |--------------------------------------------------------------------------
    |
    | Set to false to disable the self-hosted scraper. This is useful when
    | you want to use other fetchers (GoogleCSEFetcher, MockSERPFetcher).
    |
    */
    'enabled' => env('SERP_SCRAPER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in milliseconds to wait for a SERP page to load.
    | Increase this value for slower connections or when scraping
    | JavaScript-heavy pages.
    |
    */
    'timeout' => env('SERP_SCRAPER_TIMEOUT', 30000),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure delays between requests to avoid IP blocking.
    | These values are in milliseconds.
    |
    | min_delay_ms: Minimum delay between requests
    | max_delay_ms: Maximum delay between requests
    | engine_switch_delay_ms: Extra delay when switching search engines
    |
    | WARNING: Lower values increase risk of IP blocking.
    | Recommended: 3000-10000ms for normal use, higher for frequent scraping.
    |
    */
    'min_delay_ms' => env('SERP_SCRAPER_MIN_DELAY', 3000),
    'max_delay_ms' => env('SERP_SCRAPER_MAX_DELAY', 6000),
    'engine_switch_delay_ms' => env('SERP_SCRAPER_ENGINE_SWITCH_DELAY', 3000),

    /*
    |--------------------------------------------------------------------------
    | Proxy Configuration
    |--------------------------------------------------------------------------
    |
    | Configure proxy for scraping to avoid IP blocking.
    | Supports HTTP, HTTPS, and SOCKS5 proxies.
    |
    | Example:
    | 'server' => 'http://proxy.example.com:8080'
    | 'server' => 'socks5://proxy.example.com:1080'
    |
    */
    'proxy' => [
        'server' => env('SERP_SCRAPER_PROXY_SERVER', null),
        'username' => env('SERP_SCRAPER_PROXY_USERNAME', null),
        'password' => env('SERP_SCRAPER_PROXY_PASSWORD', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots.txt Compliance
    |--------------------------------------------------------------------------
    |
    | When enabled, the scraper will check robots.txt before scraping
    | and respect crawl-delay directives.
    |
    | Note: Most search engines prohibit automated scraping in robots.txt.
    | Enabling this will effectively disable the scraper for most engines.
    |
    */
    'respect_robots_txt' => env('SERP_SCRAPER_RESPECT_ROBOTS', false),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the scraper handles failures.
    |
    */
    'retry' => [
        'max_attempts' => env('SERP_SCRAPER_RETRY_MAX', 2),
        'delay_ms' => env('SERP_SCRAPER_RETRY_DELAY', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for monitoring and debugging.
    |
    */
    'logging' => [
        'enabled' => env('SERP_SCRAPER_LOGGING_ENABLED', true),
        'level' => env('SERP_SCRAPER_LOG_LEVEL', 'info'),
        'channel' => env('SERP_SCRAPER_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA Handling
    |--------------------------------------------------------------------------
    |
    | Configure how CAPTCHA challenges are handled.
    |
    */
    'captcha' => [
        // Cooldown period in minutes after detecting CAPTCHA
        'cooldown_minutes' => env('SERP_SCRAPER_CAPTCHA_COOLDOWN', 30),

        // Whether to notify on CAPTCHA detection
        'notify' => env('SERP_SCRAPER_CAPTCHA_NOTIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Results Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of results to extract per query.
    | Google typically shows up to 100 results per page.
    |
    */
    'max_results' => env('SERP_SCRAPER_MAX_RESULTS', 100),

];
