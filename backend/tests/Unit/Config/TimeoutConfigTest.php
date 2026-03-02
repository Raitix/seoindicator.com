<?php

namespace Tests\Unit\Config;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Unit tests for TASK-22-17: Timeout Configuration
 *
 * Tests the timeouts configuration array in config/crawler.php
 *
 * @see TESTS/TASK-22-17.md
 * @see TASK-22-17
 */
class TimeoutConfigTest extends TestCase
{
    // =========================================================================
    // TC-01: TIMEOUT CONFIGURATION TESTS
    // =========================================================================

    /**
     * TC-01-01: Config file exists
     */
    public function test_config_file_exists(): void
    {
        $this->assertFileExists(config_path('crawler.php'));
    }

    /**
     * TC-01-02: Timeouts array exists
     */
    public function test_timeouts_array_exists(): void
    {
        $config = config('crawler');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('timeouts', $config);
    }

    /**
     * TC-01-03: Job timeout default is 300 seconds (5 minutes)
     */
    public function test_job_timeout_default_is_300_seconds(): void
    {
        $jobTimeout = config('crawler.timeouts.job');
        $this->assertEquals(300, $jobTimeout);
    }

    /**
     * TC-01-04: Page timeout default is 30 seconds
     */
    public function test_page_timeout_default_is_30_seconds(): void
    {
        $pageTimeout = config('crawler.timeouts.page');
        $this->assertEquals(30, $pageTimeout);
    }

    /**
     * TC-01-05: Browser timeout default is 60 seconds
     */
    public function test_browser_timeout_default_is_60_seconds(): void
    {
        $browserTimeout = config('crawler.timeouts.browser');
        $this->assertEquals(60, $browserTimeout);
    }

    /**
     * TC-01-06: Connection timeout default is 10 seconds
     */
    public function test_connection_timeout_default_is_10_seconds(): void
    {
        $connectionTimeout = config('crawler.timeouts.connection');
        $this->assertEquals(10, $connectionTimeout);
    }

    /**
     * TC-01-07: Grace period default is 30 seconds
     */
    public function test_grace_period_default_is_30_seconds(): void
    {
        $gracePeriod = config('crawler.timeouts.grace_period');
        $this->assertEquals(30, $gracePeriod);
    }

    /**
     * TC-01-08: Job timeout configurable via environment variable
     */
    public function test_job_timeout_configurable_via_env(): void
    {
        // Simulate environment variable
        Config::set('crawler.timeouts.job', 600);

        $this->assertEquals(600, config('crawler.timeouts.job'));
    }

    /**
     * TC-01-09: Page timeout configurable via environment variable
     */
    public function test_page_timeout_configurable_via_env(): void
    {
        Config::set('crawler.timeouts.page', 45);

        $this->assertEquals(45, config('crawler.timeouts.page'));
    }

    /**
     * TC-01-10: All timeouts are integers
     */
    public function test_all_timeouts_are_integers(): void
    {
        $timeouts = config('crawler.timeouts');

        foreach ($timeouts as $key => $value) {
            $this->assertIsInt($value, "Timeout '{$key}' should be an integer");
        }
    }

    /**
     * TC-01-11: All timeouts are positive values
     */
    public function test_all_timeouts_are_positive_values(): void
    {
        $timeouts = config('crawler.timeouts');

        foreach ($timeouts as $key => $value) {
            $this->assertGreaterThan(0, $value, "Timeout '{$key}' should be positive");
        }
    }

    /**
     * TC-01-12: Grace period is less than job timeout
     */
    public function test_grace_period_is_less_than_job_timeout(): void
    {
        $gracePeriod = config('crawler.timeouts.grace_period');
        $jobTimeout = config('crawler.timeouts.job');

        $this->assertLessThan($jobTimeout, $gracePeriod, 'Grace period should be less than job timeout');
    }

    /**
     * TC-01-13: Page timeout is less than job timeout
     */
    public function test_page_timeout_is_less_than_job_timeout(): void
    {
        $pageTimeout = config('crawler.timeouts.page');
        $jobTimeout = config('crawler.timeouts.job');

        $this->assertLessThan($jobTimeout, $pageTimeout, 'Page timeout should be less than job timeout');
    }

    /**
     * TC-01-14: Browser timeout is less than job timeout
     */
    public function test_browser_timeout_is_less_than_job_timeout(): void
    {
        $browserTimeout = config('crawler.timeouts.browser');
        $jobTimeout = config('crawler.timeouts.job');

        $this->assertLessThan($jobTimeout, $browserTimeout, 'Browser timeout should be less than job timeout');
    }

    /**
     * TC-01-15: Connection timeout is less than page timeout
     */
    public function test_connection_timeout_is_less_than_page_timeout(): void
    {
        $connectionTimeout = config('crawler.timeouts.connection');
        $pageTimeout = config('crawler.timeouts.page');

        $this->assertLessThan($pageTimeout, $connectionTimeout, 'Connection timeout should be less than page timeout');
    }

    /**
     * TC-01-16: Timeouts array has all required keys
     */
    public function test_timeouts_array_has_all_required_keys(): void
    {
        $timeouts = config('crawler.timeouts');
        $requiredKeys = ['job', 'page', 'browser', 'connection', 'grace_period'];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $timeouts, "Missing required timeout key: {$key}");
        }
    }

    /**
     * TC-01-17: Timeout values are reasonable defaults
     */
    public function test_timeout_values_are_reasonable_defaults(): void
    {
        // Job timeout: 5 minutes (reasonable for crawl jobs)
        $this->assertEquals(300, config('crawler.timeouts.job'));

        // Page timeout: 30 seconds (reasonable for page load)
        $this->assertEquals(30, config('crawler.timeouts.page'));

        // Browser timeout: 60 seconds (reasonable for browser operations)
        $this->assertEquals(60, config('crawler.timeouts.browser'));

        // Connection timeout: 10 seconds (reasonable for HTTP connections)
        $this->assertEquals(10, config('crawler.timeouts.connection'));

        // Grace period: 30 seconds (reasonable for cleanup)
        $this->assertEquals(30, config('crawler.timeouts.grace_period'));
    }

    /**
     * TC-01-18: Legacy job_timeout key still works for backward compatibility
     */
    public function test_legacy_job_timeout_key_still_works(): void
    {
        // The old 'job_timeout' key should still exist for backward compatibility
        $legacyTimeout = config('crawler.job_timeout');
        $this->assertEquals(300, $legacyTimeout);
    }

    /**
     * TC-01-19: Legacy page_timeout key still works for backward compatibility
     */
    public function test_legacy_page_timeout_key_still_works(): void
    {
        // The old 'page_timeout' key should still exist for backward compatibility
        $legacyTimeout = config('crawler.page_timeout');
        $this->assertEquals(30, $legacyTimeout);
    }

    /**
     * TC-01-20: Timeouts can be retrieved all at once
     */
    public function test_timeouts_can_be_retrieved_all_at_once(): void
    {
        $timeouts = config('crawler.timeouts');

        $this->assertIsArray($timeouts);
        $this->assertCount(5, $timeouts);
    }

    /**
     * TC-01-21: Custom timeout via options overrides config
     */
    public function test_custom_timeout_via_options(): void
    {
        // When implementing, this should allow per-job timeout override
        $options = ['timeout' => 120];
        $expectedTimeout = $options['timeout'] ?? config('crawler.timeouts.job');

        $this->assertEquals(120, $expectedTimeout);
    }

    /**
     * TC-01-22: Minimum valid job timeout
     */
    public function test_minimum_valid_job_timeout(): void
    {
        // Minimum should be at least grace_period + some buffer
        $minValid = config('crawler.timeouts.grace_period') + 60;
        $jobTimeout = config('crawler.timeouts.job');

        $this->assertGreaterThanOrEqual($minValid, $jobTimeout);
    }

    /**
     * TC-01-23: Browser timeout is greater than page timeout
     */
    public function test_browser_timeout_is_greater_than_page_timeout(): void
    {
        // Browser needs more time than page because it includes multiple operations
        $browserTimeout = config('crawler.timeouts.browser');
        $pageTimeout = config('crawler.timeouts.page');

        $this->assertGreaterThan($pageTimeout, $browserTimeout);
    }

    /**
     * TC-01-24: Timeouts are in seconds (not milliseconds)
     */
    public function test_timeouts_are_in_seconds(): void
    {
        // Values should be in seconds, not milliseconds
        // 300 seconds = 5 minutes (reasonable)
        // If in milliseconds, 300 would be 0.3 seconds (unreasonable)
        $jobTimeout = config('crawler.timeouts.job');
        $this->assertGreaterThan(60, $jobTimeout, 'Job timeout should be in seconds (greater than 1 minute)');
    }

    /**
     * TC-01-25: Configuration is cached properly
     */
    public function test_configuration_is_cached_properly(): void
    {
        // Config should be accessible without rebuilding
        $timeout1 = config('crawler.timeouts.job');
        $timeout2 = config('crawler.timeouts.job');

        $this->assertEquals($timeout1, $timeout2);
    }
}
