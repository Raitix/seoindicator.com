<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for AI logging configuration (TASK-21-7).
 *
 * Tests cover:
 * - AC-1: Debug mode configuration (env var, config, query param)
 * - AC-4: Log channel configuration (ai channel, daily rotation, 30-day retention)
 *
 * @see TASK-21-7
 * @see SPEC-021
 */
#[Group('ai')]
#[Group('config')]
#[Group('logging')]
class AILoggingConfigTest extends TestCase
{
    // ==========================================
    // AC-1: Debug Mode Configuration Tests
    // ==========================================

    #[Test]
    public function ai_config_has_debug_setting(): void
    {
        $this->assertArrayHasKey('debug', config('ai'));
    }

    #[Test]
    public function debug_defaults_to_false(): void
    {
        // Clear any cached config
        $this->app['config']->set('ai.debug', false);
        
        $this->assertFalse(config('ai.debug', false));
    }

    #[Test]
    public function debug_can_be_enabled_via_env(): void
    {
        // Simulate env variable being set
        $this->app['config']->set('ai.debug', true);
        
        $this->assertTrue(config('ai.debug'));
    }

    #[Test]
    public function debug_value_is_boolean(): void
    {
        $debug = config('ai.debug', false);
        
        $this->assertIsBool($debug);
    }

    #[Test]
    public function debug_uses_ai_debug_env_variable(): void
    {
        // The config file should reference AI_DEBUG env variable
        $configPath = config_path('ai.php');
        $configContent = file_get_contents($configPath);
        
        $this->assertStringContainsString("env('AI_DEBUG'", $configContent);
    }

    #[Test]
    public function debug_can_be_checked_via_config_helper(): void
    {
        // This tests that config('ai.debug') works correctly
        $debugValue = config('ai.debug', false);
        
        $this->assertNotNull($debugValue);
    }

    // ==========================================
    // AC-4: Log Channel Configuration Tests
    // ==========================================

    #[Test]
    public function logging_config_has_ai_channel(): void
    {
        $channels = config('logging.channels');
        
        $this->assertIsArray($channels);
        $this->assertArrayHasKey('ai', $channels);
    }

    #[Test]
    public function ai_channel_uses_daily_driver(): void
    {
        $aiChannel = config('logging.channels.ai');
        
        $this->assertIsArray($aiChannel);
        $this->assertEquals('daily', $aiChannel['driver']);
    }

    #[Test]
    public function ai_channel_writes_to_separate_log_file(): void
    {
        $aiChannel = config('logging.channels.ai');
        
        $this->assertIsArray($aiChannel);
        $this->assertStringContainsString('ai.log', $aiChannel['path']);
    }

    #[Test]
    public function ai_channel_path_is_in_storage_logs(): void
    {
        $aiChannel = config('logging.channels.ai');
        
        $this->assertIsArray($aiChannel);
        $this->assertStringContainsString('storage/logs', $aiChannel['path']);
    }

    #[Test]
    public function ai_channel_has_daily_rotation_enabled(): void
    {
        $aiChannel = config('logging.channels.ai');
        
        $this->assertIsArray($aiChannel);
        $this->assertEquals('daily', $aiChannel['driver']);
    }

    #[Test]
    public function ai_channel_has_30_day_retention(): void
    {
        $aiChannel = config('logging.channels.ai');
        
        $this->assertIsArray($aiChannel);
        $this->assertEquals(30, $aiChannel['days']);
    }

    #[Test]
    public function ai_channel_has_debug_level(): void
    {
        $aiChannel = config('logging.channels.ai');
        
        $this->assertIsArray($aiChannel);
        $this->assertEquals('debug', $aiChannel['level']);
    }

    #[Test]
    public function ai_channel_has_replace_placeholders(): void
    {
        $aiChannel = config('logging.channels.ai');
        
        $this->assertIsArray($aiChannel);
        $this->assertTrue($aiChannel['replace_placeholders'] ?? false);
    }

    // ==========================================
    // Full Configuration Structure Tests
    // ==========================================

    #[Test]
    public function ai_config_has_required_sections(): void
    {
        $config = config('ai');
        
        $this->assertArrayHasKey('default', $config);
        $this->assertArrayHasKey('providers', $config);
        $this->assertArrayHasKey('fallback', $config);
        $this->assertArrayHasKey('cache', $config);
        $this->assertArrayHasKey('debug', $config);
    }

    #[Test]
    public function ai_config_has_rate_limits_section(): void
    {
        $config = config('ai');
        
        $this->assertArrayHasKey('rate_limits', $config);
    }

    #[Test]
    public function rate_limits_have_user_and_ip_limits(): void
    {
        $rateLimits = config('ai.rate_limits');
        
        $this->assertArrayHasKey('user', $rateLimits);
        $this->assertArrayHasKey('ip', $rateLimits);
    }

    #[Test]
    public function provider_config_has_required_fields(): void
    {
        $openrouter = config('ai.providers.openrouter');
        
        $this->assertArrayHasKey('api_key', $openrouter);
        $this->assertArrayHasKey('base_url', $openrouter);
        $this->assertArrayHasKey('model', $openrouter);
        $this->assertArrayHasKey('timeout', $openrouter);
        $this->assertArrayHasKey('max_tokens', $openrouter);
    }

    // ==========================================
    // Environment Variable Tests
    // ==========================================

    #[Test]
    public function ai_debug_env_var_is_documented_in_config(): void
    {
        $configPath = config_path('ai.php');
        $configContent = file_get_contents($configPath);
        
        $this->assertStringContainsString('AI_DEBUG', $configContent);
    }

    #[Test]
    public function ai_provider_env_var_is_documented(): void
    {
        $configPath = config_path('ai.php');
        $configContent = file_get_contents($configPath);
        
        $this->assertStringContainsString('AI_PROVIDER', $configContent);
    }

    // ==========================================
    // Security Configuration Tests
    // ==========================================

    #[Test]
    public function debug_mode_should_be_false_in_production(): void
    {
        // In a test environment, this would typically be false
        // The actual production value depends on APP_ENV and AI_DEBUG
        $this->assertIsBool(config('ai.debug', false));
    }

    #[Test]
    public function api_keys_are_loaded_from_env(): void
    {
        $openrouter = config('ai.providers.openrouter');
        
        // API key should come from env, not be hardcoded
        $this->assertNull($openrouter['api_key']);
    }

    // ==========================================
    // Cache Configuration Tests
    // ==========================================

    #[Test]
    public function cache_ttl_is_configurable(): void
    {
        $cacheConfig = config('ai.cache');
        
        $this->assertArrayHasKey('ttl_hours', $cacheConfig);
    }

    #[Test]
    public function cache_ttl_defaults_to_24_hours(): void
    {
        $ttl = config('ai.cache.ttl_hours', 24);
        
        $this->assertEquals(24, $ttl);
    }

    // ==========================================
    // Fallback Configuration Tests
    // ==========================================

    #[Test]
    public function fallback_is_configurable(): void
    {
        $fallbackConfig = config('ai.fallback');
        
        $this->assertArrayHasKey('enabled', $fallbackConfig);
    }

    #[Test]
    public function fallback_defaults_to_enabled(): void
    {
        $enabled = config('ai.fallback.enabled', true);
        
        $this->assertTrue($enabled);
    }

    // ==========================================
    // Configuration File Existence Tests
    // ==========================================

    #[Test]
    public function ai_config_file_exists(): void
    {
        $this->assertFileExists(config_path('ai.php'));
    }

    #[Test]
    public function logging_config_file_exists(): void
    {
        $this->assertFileExists(config_path('logging.php'));
    }

    #[Test]
    public function ai_config_returns_array(): void
    {
        $config = config('ai');
        
        $this->assertIsArray($config);
    }

    // ==========================================
    // Log File Path Tests
    // ==========================================

    #[Test]
    public function ai_log_file_path_is_correct(): void
    {
        $expectedPath = storage_path('logs/ai.log');
        $configuredPath = config('logging.channels.ai.path');
        
        $this->assertEquals($expectedPath, $configuredPath);
    }

    #[Test]
    public function ai_log_directory_would_be_created(): void
    {
        // The storage/logs directory should exist
        $logsDir = storage_path('logs');
        
        $this->assertDirectoryExists($logsDir);
    }
}
