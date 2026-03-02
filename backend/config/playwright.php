<?php

/**
 * Playwright Configuration
 *
 * Configuration for headless browser automation with Playwright.
 * Used by the BrowserLauncher service to configure browser instances.
 *
 * @see TASK-22-4
 * @see SPEC-022
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Browser
    |--------------------------------------------------------------------------
    |
    | The default browser to use for Playwright automation.
    | Supported: 'chromium', 'firefox', 'webkit'
    |
    | Chromium is recommended for best JavaScript rendering support.
    |
    */
    'browser' => env('PLAYWRIGHT_BROWSER', 'chromium'),

    /*
    |--------------------------------------------------------------------------
    | Headless Mode
    |--------------------------------------------------------------------------
    |
    | Run browser in headless mode (no visible window).
    | Should be true in production, false for debugging.
    |
    */
    'headless' => env('PLAYWRIGHT_HEADLESS', true),

    /*
    |--------------------------------------------------------------------------
    | Default Timeout
    |--------------------------------------------------------------------------
    |
    | Default timeout in milliseconds for page operations.
    | This includes navigation, waiting for selectors, etc.
    |
    */
    'timeout' => env('PLAYWRIGHT_TIMEOUT', 30000),

    /*
    |--------------------------------------------------------------------------
    | Slow Motion
    |--------------------------------------------------------------------------
    |
    | Delay between actions in milliseconds.
    | Useful for debugging. Set to 0 for production.
    |
    */
    'slow_mo' => env('PLAYWRIGHT_SLOW_MO', 0),

    /*
    |--------------------------------------------------------------------------
    | Default Viewport
    |--------------------------------------------------------------------------
    |
    | Default viewport dimensions for browser pages.
    | Used when creating new pages.
    |
    */
    'viewport' => [
        'width' => env('PLAYWRIGHT_VIEWPORT_WIDTH', 1280),
        'height' => env('PLAYWRIGHT_VIEWPORT_HEIGHT', 720),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default User Agent
    |--------------------------------------------------------------------------
    |
    | Default user agent string for browser requests.
    | Used when creating new browser contexts.
    |
    */
    'user_agent' => env('PLAYWRIGHT_USER_AGENT', 'SEOIndicator/1.0 Headless Browser'),

    /*
    |--------------------------------------------------------------------------
    | Ignore HTTPS Errors
    |--------------------------------------------------------------------------
    |
    | Whether to ignore HTTPS certificate errors.
    | Should be false in production for security.
    |
    */
    'ignore_https_errors' => env('PLAYWRIGHT_IGNORE_HTTPS_ERRORS', false),

    /*
    |--------------------------------------------------------------------------
    | Downloads Path
    |--------------------------------------------------------------------------
    |
    | Directory to save downloaded files.
    | Set to null to disable downloads.
    |
    */
    'downloads_path' => env('PLAYWRIGHT_DOWNLOADS_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Browser Instances
    |--------------------------------------------------------------------------
    |
    | Maximum number of browser instances to run simultaneously.
    | Each instance uses ~512MB RAM.
    |
    */
    'max_instances' => env('PLAYWRIGHT_MAX_INSTANCES', 3),

    /*
    |--------------------------------------------------------------------------
    | Instance Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum lifetime of a browser instance in seconds before restart.
    | Prevents memory leaks from long-running instances.
    |
    */
    'instance_timeout' => env('PLAYWRIGHT_INSTANCE_TIMEOUT', 300), // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for retrying failed browser operations.
    |
    */
    'retry' => [
        'max_attempts' => env('PLAYWRIGHT_RETRY_MAX_ATTEMPTS', 3),
        'delay_ms' => env('PLAYWRIGHT_RETRY_DELAY_MS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for browser automation.
    |
    */
    'security' => [
        // Block automatic downloads
        'block_downloads' => true,

        // Run in sandbox mode (requires --no-sandbox for CI)
        'sandbox' => env('PLAYWRIGHT_SANDBOX', true),

        // Block additional protocols
        'blocked_protocols' => ['file', 'gopher', 'data'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Logging settings for browser operations.
    |
    */
    'logging' => [
        // Log browser console messages
        'console' => env('PLAYWRIGHT_LOG_CONSOLE', false),

        // Log network requests
        'network' => env('PLAYWRIGHT_LOG_NETWORK', false),

        // Log performance metrics
        'performance' => env('PLAYWRIGHT_LOG_PERFORMANCE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Browser Launch Options
    |--------------------------------------------------------------------------
    |
    | Additional options passed to browser launch.
    |
    */
    'launch_options' => [
        // Arguments passed to the browser process
        'args' => [
            '--disable-extensions',
            '--disable-plugins',
            '--disable-images', // Speed up for SEO analysis
        ],
    ],
];
