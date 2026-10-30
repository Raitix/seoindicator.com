<?php

namespace Tests\Unit\Config;

use Tests\TestCase;

/**
 * Unit tests for the crawler configuration.
 *
 * @see TASK-22-3
 * @see SPEC-022
 */
class CrawlerConfigTest extends TestCase
{
    // ==========================================
    // Config File Tests
    // ==========================================

    /**
     * Test config file exists.
     */
    public function test_config_file_exists(): void
    {
        $this->assertFileExists(config_path('crawler.php'));
    }

    /**
     * Test config can be loaded.
     */
    public function test_config_can_be_loaded(): void
    {
        $config = config('crawler');

        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
    }

    // ==========================================
    // Default Values Tests
    // ==========================================

    /**
     * Test max_depth default value.
     */
    public function test_max_depth_default(): void
    {
        $this->assertEquals(2, config('crawler.max_depth'));
    }

    /**
     * Test max_pages default value.
     */
    public function test_max_pages_default(): void
    {
        $this->assertEquals(50, config('crawler.max_pages'));
    }

    /**
     * Test max_content_size default value (3MB).
     */
    public function test_max_content_size_default(): void
    {
        $expected = 3 * 1024 * 1024; // 3MB in bytes

        $this->assertEquals($expected, config('crawler.max_content_size'));
    }

    /**
     * Test page_timeout default value.
     */
    public function test_page_timeout_default(): void
    {
        $this->assertEquals(30, config('crawler.page_timeout'));
    }

    /**
     * Test job_timeout default value (5 minutes).
     */
    public function test_job_timeout_default(): void
    {
        $this->assertEquals(300, config('crawler.job_timeout'));
    }

    /**
     * Test retention_days default value.
     */
    public function test_retention_days_default(): void
    {
        $this->assertEquals(7, config('crawler.retention_days'));
    }

    /**
     * Test user_agent default value.
     */
    public function test_user_agent_default(): void
    {
        $this->assertEquals('SEOIndicator/1.0 Crawler', config('crawler.user_agent'));
    }

    /**
     * Test default_wait_time default value.
     */
    public function test_default_wait_time_default(): void
    {
        $this->assertEquals(2000, config('crawler.default_wait_time'));
    }

    /**
     * Test max_redirects default value.
     */
    public function test_max_redirects_default(): void
    {
        $this->assertEquals(5, config('crawler.max_redirects'));
    }

    // ==========================================
    // Config Structure Tests
    // ==========================================

    /**
     * Test all required config keys are present.
     */
    public function test_all_required_keys_present(): void
    {
        $requiredKeys = [
            'max_depth',
            'max_pages',
            'max_content_size',
            'page_timeout',
            'job_timeout',
            'retention_days',
            'user_agent',
            'default_wait_time',
            'max_redirects',
            'rate_limits',
            'browser',
            'ssrf',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, config('crawler'), "Missing config key: {$key}");
        }
    }

    /**
     * Test rate_limits structure.
     */
    public function test_rate_limits_structure(): void
    {
        $rateLimits = config('crawler.rate_limits');

        $this->assertIsArray($rateLimits);
        $this->assertArrayHasKey('daily_jobs_per_user', $rateLimits);
        $this->assertArrayHasKey('concurrent_jobs_per_user', $rateLimits);
        $this->assertArrayHasKey('domain_delay_ms', $rateLimits);
    }

    /**
     * Test browser configuration structure.
     */
    public function test_browser_config_structure(): void
    {
        $browser = config('crawler.browser');

        $this->assertIsArray($browser);
        $this->assertArrayHasKey('max_memory_mb', $browser);
        $this->assertArrayHasKey('browser', $browser);
        $this->assertArrayHasKey('headless', $browser);
    }

    /**
     * Test SSRF configuration structure.
     */
    public function test_ssrf_config_structure(): void
    {
        $ssrf = config('crawler.ssrf');

        $this->assertIsArray($ssrf);
        $this->assertArrayHasKey('blocked_ip_ranges', $ssrf);
        $this->assertArrayHasKey('blocked_hosts', $ssrf);
        $this->assertArrayHasKey('blocked_protocols', $ssrf);
    }

    // ==========================================
    // Type Validation Tests
    // ==========================================

    /**
     * Test numeric config values are integers.
     */
    public function test_numeric_values_are_integers(): void
    {
        $numericKeys = [
            'max_depth',
            'max_pages',
            'max_content_size',
            'page_timeout',
            'job_timeout',
            'retention_days',
            'default_wait_time',
            'max_redirects',
        ];

        foreach ($numericKeys as $key) {
            $this->assertIsInt(config("crawler.{$key}"), "Config key {$key} should be integer");
        }
    }

    /**
     * Test string config values are strings.
     */
    public function test_string_values_are_strings(): void
    {
        $stringKeys = [
            'user_agent',
        ];

        foreach ($stringKeys as $key) {
            $this->assertIsString(config("crawler.{$key}"), "Config key {$key} should be string");
        }
    }

    // ==========================================
    // Value Range Tests
    // ==========================================

    /**
     * Test max_depth is within valid range.
     */
    public function test_max_depth_in_valid_range(): void
    {
        $maxDepth = config('crawler.max_depth');

        $this->assertGreaterThanOrEqual(0, $maxDepth);
        $this->assertLessThanOrEqual(10, $maxDepth);
    }

    /**
     * Test max_pages is positive.
     */
    public function test_max_pages_is_positive(): void
    {
        $this->assertGreaterThan(0, config('crawler.max_pages'));
    }

    /**
     * Test max_content_size is reasonable.
     */
    public function test_max_content_size_is_reasonable(): void
    {
        $size = config('crawler.max_content_size');

        // Should be at least 1MB
        $this->assertGreaterThanOrEqual(1024 * 1024, $size);
        // Should be at most 100MB
        $this->assertLessThanOrEqual(100 * 1024 * 1024, $size);
    }

    /**
     * Test page_timeout is reasonable.
     */
    public function test_page_timeout_is_reasonable(): void
    {
        $timeout = config('crawler.page_timeout');

        // Should be at least 5 seconds
        $this->assertGreaterThanOrEqual(5, $timeout);
        // Should be at most 5 minutes
        $this->assertLessThanOrEqual(300, $timeout);
    }

    /**
     * Test job_timeout is greater than page_timeout.
     */
    public function test_job_timeout_greater_than_page_timeout(): void
    {
        $this->assertGreaterThan(
            config('crawler.page_timeout'),
            config('crawler.job_timeout')
        );
    }

    /**
     * Test retention_days is reasonable.
     */
    public function test_retention_days_is_reasonable(): void
    {
        $days = config('crawler.retention_days');

        // Should be at least 1 day
        $this->assertGreaterThanOrEqual(1, $days);
        // Should be at most 30 days
        $this->assertLessThanOrEqual(30, $days);
    }

    // ==========================================
    // SSRF Configuration Tests
    // ==========================================

    /**
     * Test SSRF blocks localhost IP.
     */
    public function test_ssrf_blocks_localhost_ip(): void
    {
        $blockedRanges = config('crawler.ssrf.blocked_ip_ranges');

        $this->assertContains('127.0.0.0/8', $blockedRanges);
    }

    /**
     * Test SSRF blocks private Class A.
     */
    public function test_ssrf_blocks_private_class_a(): void
    {
        $blockedRanges = config('crawler.ssrf.blocked_ip_ranges');

        $this->assertContains('10.0.0.0/8', $blockedRanges);
    }

    /**
     * Test SSRF blocks private Class B.
     */
    public function test_ssrf_blocks_private_class_b(): void
    {
        $blockedRanges = config('crawler.ssrf.blocked_ip_ranges');

        $this->assertContains('172.16.0.0/12', $blockedRanges);
    }

    /**
     * Test SSRF blocks private Class C.
     */
    public function test_ssrf_blocks_private_class_c(): void
    {
        $blockedRanges = config('crawler.ssrf.blocked_ip_ranges');

        $this->assertContains('192.168.0.0/16', $blockedRanges);
    }

    /**
     * Test SSRF blocks cloud metadata IP.
     */
    public function test_ssrf_blocks_cloud_metadata(): void
    {
        $blockedRanges = config('crawler.ssrf.blocked_ip_ranges');

        $this->assertContains('169.254.0.0/16', $blockedRanges);
    }

    /**
     * Test SSRF blocks IPv6 localhost.
     */
    public function test_ssrf_blocks_ipv6_localhost(): void
    {
        $blockedRanges = config('crawler.ssrf.blocked_ip_ranges');

        $this->assertContains('::1/128', $blockedRanges);
    }

    /**
     * Test SSRF blocks localhost hostname.
     */
    public function test_ssrf_blocks_localhost_hostname(): void
    {
        $blockedHosts = config('crawler.ssrf.blocked_hosts');

        $this->assertContains('localhost', $blockedHosts);
    }

    /**
     * Test SSRF blocks .local domain.
     */
    public function test_ssrf_blocks_local_domain(): void
    {
        $blockedHosts = config('crawler.ssrf.blocked_hosts');

        $this->assertContains('*.local', $blockedHosts);
    }

    /**
     * Test SSRF blocks .internal domain.
     */
    public function test_ssrf_blocks_internal_domain(): void
    {
        $blockedHosts = config('crawler.ssrf.blocked_hosts');

        $this->assertContains('*.internal', $blockedHosts);
    }

    /**
     * Test SSRF blocks file protocol.
     */
    public function test_ssrf_blocks_file_protocol(): void
    {
        $blockedProtocols = config('crawler.ssrf.blocked_protocols');

        $this->assertContains('file', $blockedProtocols);
    }

    /**
     * Test SSRF blocks ftp protocol.
     */
    public function test_ssrf_blocks_ftp_protocol(): void
    {
        $blockedProtocols = config('crawler.ssrf.blocked_protocols');

        $this->assertContains('ftp', $blockedProtocols);
    }

    /**
     * Test SSRF blocks data protocol.
     */
    public function test_ssrf_blocks_data_protocol(): void
    {
        $blockedProtocols = config('crawler.ssrf.blocked_protocols');

        $this->assertContains('data', $blockedProtocols);
    }

    // ==========================================
    // Environment Override Tests
    // ==========================================

    /**
     * Test config values can be overridden via env.
     */
    public function test_config_uses_env_variables(): void
    {
        // This test verifies the config structure supports env overrides
        // Actual env testing would require process isolation

        $config = include config_path('crawler.php');

        $this->assertStringContainsString('env(', file_get_contents(config_path('crawler.php')));
    }

    // ==========================================
    // Rate Limit Configuration Tests
    // ==========================================

    /**
     * Test daily jobs limit.
     */
    public function test_daily_jobs_limit(): void
    {
        $this->assertEquals(10, config('crawler.rate_limits.daily_jobs_per_user'));
    }

    /**
     * Test concurrent jobs limit.
     */
    public function test_concurrent_jobs_limit(): void
    {
        $this->assertEquals(3, config('crawler.rate_limits.concurrent_jobs_per_user'));
    }

    /**
     * Test domain delay.
     */
    public function test_domain_delay(): void
    {
        $this->assertEquals(1000, config('crawler.rate_limits.domain_delay_ms'));
    }

    // ==========================================
    // Browser Configuration Tests
    // ==========================================

    /**
     * Test browser memory limit.
     */
    public function test_browser_memory_limit(): void
    {
        $this->assertEquals(512, config('crawler.browser.max_memory_mb'));
    }

    /**
     * Test default browser.
     */
    public function test_default_browser(): void
    {
        $this->assertEquals('chromium', config('crawler.browser.browser'));
    }

    /**
     * Test headless mode default.
     */
    public function test_headless_mode_default(): void
    {
        $this->assertTrue(config('crawler.browser.headless'));
    }
}
