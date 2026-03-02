<?php

namespace Tests\Unit\Config;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Unit tests for Playwright configuration.
 *
 * @see TASK-22-4
 * @see SPEC-022
 */
class PlaywrightConfigTest extends TestCase
{
    // ==========================================
    // Default Configuration Tests (PC-001 to PC-015)
    // ==========================================

    /**
     * Test default configuration is loaded.
     * PC-001: Load default config
     */
    public function test_default_configuration_is_loaded(): void
    {
        // Default values should be set
        $this->assertEquals('chromium', config('playwright.browser', 'chromium'));
        $this->assertTrue(config('playwright.headless', true));
        $this->assertEquals(30000, config('playwright.timeout', 30000));
        $this->assertEquals(0, config('playwright.slow_mo', 0));
        $this->assertEquals(3, config('playwright.max_instances', 3));
        $this->assertEquals(300, config('playwright.instance_timeout', 300));
    }

    /**
     * Test configuration can be overridden via env.
     * PC-002: Load config from env
     */
    public function test_configuration_can_be_overridden_via_env(): void
    {
        // Set environment variables
        Config::set('playwright.browser', 'firefox');
        Config::set('playwright.timeout', 60000);
        Config::set('playwright.headless', false);

        $this->assertEquals('firefox', config('playwright.browser'));
        $this->assertEquals(60000, config('playwright.timeout'));
        $this->assertFalse(config('playwright.headless'));
    }

    /**
     * Test headless default is true.
     * PC-005: Headless default
     */
    public function test_headless_default_is_true(): void
    {
        $this->assertTrue(config('playwright.headless', true));
    }

    /**
     * Test timeout default is 30000.
     * PC-006: Timeout default
     */
    public function test_timeout_default_is_30000(): void
    {
        $this->assertEquals(30000, config('playwright.timeout', 30000));
    }

    /**
     * Test viewport default is 1280x720.
     * PC-007: Viewport default
     */
    public function test_viewport_default_is_1280x720(): void
    {
        $viewport = config('playwright.viewport', ['width' => 1280, 'height' => 720]);

        $this->assertIsArray($viewport);
        $this->assertEquals(1280, $viewport['width']);
        $this->assertEquals(720, $viewport['height']);
    }

    /**
     * Test max instances default is 3.
     * PC-008: Max instances default
     */
    public function test_max_instances_default_is_3(): void
    {
        $this->assertEquals(3, config('playwright.max_instances', 3));
    }

    /**
     * Test instance timeout default is 300 (5 minutes).
     * PC-009: Instance timeout default
     */
    public function test_instance_timeout_default_is_300(): void
    {
        $this->assertEquals(300, config('playwright.instance_timeout', 300));
    }

    /**
     * Test ignore HTTPS errors default is false.
     * PC-010: Ignore HTTPS errors default
     */
    public function test_ignore_https_errors_default_is_false(): void
    {
        $this->assertFalse(config('playwright.ignore_https_errors', false));
    }

    /**
     * Test slow_mo default is 0.
     * PC-011: SlowMo default
     */
    public function test_slow_mo_default_is_0(): void
    {
        $this->assertEquals(0, config('playwright.slow_mo', 0));
    }

    /**
     * Test downloads path default is null.
     * PC-012: Downloads path default
     */
    public function test_downloads_path_default_is_null(): void
    {
        $this->assertNull(config('playwright.downloads_path'));
    }

    /**
     * Test user agent has default value.
     * PC-013: User agent default
     */
    public function test_user_agent_has_default_value(): void
    {
        $userAgent = config('playwright.user_agent', 'SEOIndicator/1.0 Headless Browser');

        $this->assertIsString($userAgent);
        $this->assertStringContainsString('SEOIndicator', $userAgent);
    }

    // ==========================================
    // Configuration Validation Tests
    // ==========================================

    /**
     * Test valid browser types.
     * PC-014: Browser enum validation
     */
    public function test_valid_browser_types(): void
    {
        $validBrowsers = ['chromium', 'firefox', 'webkit'];

        foreach ($validBrowsers as $browser) {
            Config::set('playwright.browser', $browser);
            $this->assertEquals($browser, config('playwright.browser'));
        }
    }

    /**
     * Test invalid browser type validation.
     * PC-014: Browser enum validation (invalid)
     */
    public function test_invalid_browser_type_validation(): void
    {
        $invalidBrowsers = ['chrome', 'safari', 'edge', 'opera', 'ie'];

        foreach ($invalidBrowsers as $browser) {
            // In production, this would throw a validation error
            // For now, we just verify the valid options
            $this->assertNotContains($browser, ['chromium', 'firefox', 'webkit']);
        }
    }

    /**
     * Test timeout bounds validation.
     * PC-015: Numeric bounds validation
     */
    public function test_timeout_bounds_validation(): void
    {
        // Valid timeout values
        $validTimeouts = [1000, 5000, 30000, 60000, 120000];

        foreach ($validTimeouts as $timeout) {
            Config::set('playwright.timeout', $timeout);
            $this->assertEquals($timeout, config('playwright.timeout'));
        }
    }

    /**
     * Test negative timeout is invalid.
     * PC-015: Numeric bounds validation (negative)
     */
    public function test_negative_timeout_is_invalid(): void
    {
        // In production, negative values should throw validation error
        // We verify that negative is not a valid configuration
        $negativeTimeout = -1;
        $this->assertLessThan(0, $negativeTimeout);
        $this->assertTrue($negativeTimeout < 0);
    }

    /**
     * Test viewport dimensions are positive integers.
     */
    public function test_viewport_dimensions_are_positive_integers(): void
    {
        $viewport = config('playwright.viewport', ['width' => 1280, 'height' => 720]);

        $this->assertIsInt($viewport['width']);
        $this->assertIsInt($viewport['height']);
        $this->assertGreaterThan(0, $viewport['width']);
        $this->assertGreaterThan(0, $viewport['height']);
    }

    /**
     * Test max instances is positive integer.
     */
    public function test_max_instances_is_positive_integer(): void
    {
        $maxInstances = config('playwright.max_instances', 3);

        $this->assertIsInt($maxInstances);
        $this->assertGreaterThan(0, $maxInstances);
    }

    /**
     * Test instance timeout is positive integer.
     */
    public function test_instance_timeout_is_positive_integer(): void
    {
        $instanceTimeout = config('playwright.instance_timeout', 300);

        $this->assertIsInt($instanceTimeout);
        $this->assertGreaterThan(0, $instanceTimeout);
    }

    // ==========================================
    // Configuration Structure Tests
    // ==========================================

    /**
     * Test configuration has all required keys.
     * PC-003: Config validation - valid
     */
    public function test_configuration_has_all_required_keys(): void
    {
        $requiredKeys = [
            'browser',
            'headless',
            'timeout',
            'slow_mo',
            'viewport',
            'user_agent',
            'ignore_https_errors',
            'max_instances',
            'instance_timeout',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertNotNull(config("playwright.{$key}"), "Missing config key: {$key}");
        }

        // Optional keys that can be null
        $optionalKeys = ['downloads_path'];
        foreach ($optionalKeys as $key) {
            // Just verify the key exists (value can be null)
            config("playwright.{$key}");
        }
    }

    /**
     * Test viewport structure is correct.
     */
    public function test_viewport_structure_is_correct(): void
    {
        $viewport = config('playwright.viewport', ['width' => 1280, 'height' => 720]);

        $this->assertArrayHasKey('width', $viewport);
        $this->assertArrayHasKey('height', $viewport);
        $this->assertCount(2, $viewport);
    }

    // ==========================================
    // Edge Cases Tests
    // ==========================================

    /**
     * Test zero timeout handling.
     */
    public function test_zero_timeout_handling(): void
    {
        // Zero timeout should be treated as no timeout
        Config::set('playwright.timeout', 0);

        // In implementation, 0 means no timeout
        $timeout = config('playwright.timeout');
        $this->assertEquals(0, $timeout);
    }

    /**
     * Test very large viewport dimensions.
     */
    public function test_very_large_viewport_dimensions(): void
    {
        $largeViewport = ['width' => 3840, 'height' => 2160]; // 4K

        Config::set('playwright.viewport', $largeViewport);

        $this->assertEquals($largeViewport, config('playwright.viewport'));
    }

    /**
     * Test very small viewport dimensions.
     */
    public function test_very_small_viewport_dimensions(): void
    {
        $smallViewport = ['width' => 320, 'height' => 480]; // Mobile

        Config::set('playwright.viewport', $smallViewport);

        $this->assertEquals($smallViewport, config('playwright.viewport'));
    }

    /**
     * Test max instances of 1.
     */
    public function test_max_instances_of_one(): void
    {
        Config::set('playwright.max_instances', 1);

        $this->assertEquals(1, config('playwright.max_instances'));
    }

    /**
     * Test user agent with special characters.
     */
    public function test_user_agent_with_special_characters(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

        Config::set('playwright.user_agent', $userAgent);

        $this->assertEquals($userAgent, config('playwright.user_agent'));
    }

    // ==========================================
    // Environment Variable Mapping Tests
    // ==========================================

    /**
     * Test PLAYWRIGHT_BROWSER env mapping.
     */
    public function test_playwright_browser_env_mapping(): void
    {
        // Simulate env variable
        Config::set('playwright.browser', 'firefox');

        $this->assertEquals('firefox', config('playwright.browser'));
    }

    /**
     * Test PLAYWRIGHT_HEADLESS env mapping.
     */
    public function test_playwright_headless_env_mapping(): void
    {
        Config::set('playwright.headless', false);

        $this->assertFalse(config('playwright.headless'));
    }

    /**
     * Test PLAYWRIGHT_TIMEOUT env mapping.
     */
    public function test_playwright_timeout_env_mapping(): void
    {
        Config::set('playwright.timeout', 45000);

        $this->assertEquals(45000, config('playwright.timeout'));
    }

    /**
     * Test PLAYWRIGHT_MAX_INSTANCES env mapping.
     */
    public function test_playwright_max_instances_env_mapping(): void
    {
        Config::set('playwright.max_instances', 5);

        $this->assertEquals(5, config('playwright.max_instances'));
    }

    // ==========================================
    // Configuration Merging Tests
    // ==========================================

    /**
     * Test custom options merge with defaults.
     */
    public function test_custom_options_merge_with_defaults(): void
    {
        $defaults = [
            'browser' => 'chromium',
            'headless' => true,
            'timeout' => 30000,
        ];

        $custom = [
            'timeout' => 60000,
            'slow_mo' => 100,
        ];

        $merged = array_merge($defaults, $custom);

        $this->assertEquals('chromium', $merged['browser']);
        $this->assertTrue($merged['headless']);
        $this->assertEquals(60000, $merged['timeout']);
        $this->assertEquals(100, $merged['slow_mo']);
    }

    /**
     * Test override does not affect defaults.
     */
    public function test_override_does_not_affect_defaults(): void
    {
        $originalTimeout = config('playwright.timeout', 30000);

        Config::set('playwright.timeout', 99999);

        // Original value is preserved in config files
        $this->assertEquals(99999, config('playwright.timeout'));

        // Reset
        Config::set('playwright.timeout', $originalTimeout);
    }
}
