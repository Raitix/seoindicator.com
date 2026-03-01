<?php

namespace Tests\Unit\Services\AI;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Test suite for AI configuration validation.
 * 
 * AC-5: Configuration File Created
 * 
 * @group ai
 * @group task-21-1
 */
class ConfigurationTest extends TestCase
{
    // =========================================================================
    // CFG-001: Configuration file exists
    // =========================================================================

    #[Test]
    #[TestDox('CFG-001: Configuration file exists')]
    public function configuration_file_exists(): void
    {
        $this->assertFileExists(
            config_path('ai.php'),
            'AI configuration file should exist at config/ai.php'
        );
    }

    // =========================================================================
    // CFG-002: Default provider configuration
    // =========================================================================

    #[Test]
    #[TestDox('CFG-002: Default provider configuration')]
    public function default_provider_configuration(): void
    {
        $default = config('ai.default');
        
        $this->assertNotNull($default, 'AI default provider should be configured');
        $this->assertIsString($default, 'Default provider should be a string');
        $this->assertContains($default, ['openrouter', 'vercel', 'fallback'], 'Default provider should be valid');
    }

    // =========================================================================
    // CFG-003: OpenRouter provider configuration exists
    // =========================================================================

    #[Test]
    #[TestDox('CFG-003: OpenRouter provider configuration exists')]
    public function openrouter_provider_configuration_exists(): void
    {
        $openrouter = config('ai.providers.openrouter');
        
        $this->assertNotNull($openrouter, 'OpenRouter provider should be configured');
        $this->assertIsArray($openrouter, 'OpenRouter config should be an array');
    }

    // =========================================================================
    // CFG-004: OpenRouter provider has required keys
    // =========================================================================

    #[Test]
    #[TestDox('CFG-004: OpenRouter provider has required keys')]
    public function openrouter_provider_has_required_keys(): void
    {
        $openrouter = config('ai.providers.openrouter');
        
        $requiredKeys = ['api_key', 'base_url', 'model', 'timeout'];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $openrouter,
                "OpenRouter config must have '{$key}' key"
            );
        }
    }

    // =========================================================================
    // CFG-005: Vercel provider configuration exists
    // =========================================================================

    #[Test]
    #[TestDox('CFG-005: Vercel provider configuration exists')]
    public function vercel_provider_configuration_exists(): void
    {
        $vercel = config('ai.providers.vercel');
        
        $this->assertNotNull($vercel, 'Vercel provider should be configured');
        $this->assertIsArray($vercel, 'Vercel config should be an array');
    }

    // =========================================================================
    // CFG-006: Vercel provider has required keys
    // =========================================================================

    #[Test]
    #[TestDox('CFG-006: Vercel provider has required keys')]
    public function vercel_provider_has_required_keys(): void
    {
        $vercel = config('ai.providers.vercel');
        
        $requiredKeys = ['api_key', 'model', 'timeout'];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $vercel,
                "Vercel config must have '{$key}' key"
            );
        }
    }

    // =========================================================================
    // CFG-007: Fallback configuration exists
    // =========================================================================

    #[Test]
    #[TestDox('CFG-007: Fallback configuration exists')]
    public function fallback_configuration_exists(): void
    {
        $fallback = config('ai.fallback');
        
        $this->assertNotNull($fallback, 'Fallback should be configured');
        $this->assertIsArray($fallback, 'Fallback config should be an array');
        $this->assertArrayHasKey('enabled', $fallback, 'Fallback config must have enabled key');
    }

    // =========================================================================
    // CFG-008: Cache configuration exists
    // =========================================================================

    #[Test]
    #[TestDox('CFG-008: Cache configuration exists')]
    public function cache_configuration_exists(): void
    {
        $cache = config('ai.cache');
        
        $this->assertNotNull($cache, 'Cache config should be set');
        $this->assertIsArray($cache, 'Cache config should be an array');
        $this->assertArrayHasKey('ttl_hours', $cache, 'Cache config must have ttl_hours key');
    }

    // =========================================================================
    // CFG-009: Debug flag configuration exists
    // =========================================================================

    #[Test]
    #[TestDox('CFG-009: Debug flag configuration exists')]
    public function debug_flag_configuration_exists(): void
    {
        $debug = config('ai.debug');
        
        // Debug can be null (defaults to false) or boolean
        $this->assertTrue(
            is_bool($debug) || is_null($debug),
            'Debug should be boolean or null'
        );
    }

    // =========================================================================
    // CFG-010: Timeout settings are integers
    // =========================================================================

    #[Test]
    #[TestDox('CFG-010: Timeout settings are integers')]
    public function timeout_settings_are_integers(): void
    {
        $openrouterTimeout = config('ai.providers.openrouter.timeout');
        $vercelTimeout = config('ai.providers.vercel.timeout');
        
        $this->assertIsInt($openrouterTimeout, 'OpenRouter timeout should be an integer');
        $this->assertIsInt($vercelTimeout, 'Vercel timeout should be an integer');
        
        // Reasonable timeout range
        $this->assertGreaterThan(0, $openrouterTimeout, 'OpenRouter timeout should be positive');
        $this->assertLessThan(300, $openrouterTimeout, 'OpenRouter timeout should be reasonable (<300s)');
    }

    // =========================================================================
    // CFG-011: API key references environment variable
    // =========================================================================

    #[Test]
    #[TestDox('CFG-011: API key references environment variable')]
    public function api_key_references_environment_variable(): void
    {
        // Check that config uses env() for API keys
        $configContent = file_get_contents(config_path('ai.php'));
        
        $this->assertStringContainsString('OPENROUTER_API_KEY', $configContent, 'Config should reference OPENROUTER_API_KEY env var');
        $this->assertStringContainsString('VERCEL_AI_API_KEY', $configContent, 'Config should reference VERCEL_AI_API_KEY env var');
    }

    // =========================================================================
    // CFG-012: Model configuration is string
    // =========================================================================

    #[Test]
    #[TestDox('CFG-012: Model configuration is string')]
    public function model_configuration_is_string(): void
    {
        $openrouterModel = config('ai.providers.openrouter.model');
        $vercelModel = config('ai.providers.vercel.model');
        
        $this->assertIsString($openrouterModel, 'OpenRouter model should be a string');
        $this->assertIsString($vercelModel, 'Vercel model should be a string');
        
        $this->assertNotEmpty($openrouterModel, 'OpenRouter model should not be empty');
        $this->assertNotEmpty($vercelModel, 'Vercel model should not be empty');
    }

    // =========================================================================
    // CFG-013: Cache TTL is positive integer
    // =========================================================================

    #[Test]
    #[TestDox('CFG-013: Cache TTL is positive integer')]
    public function cache_ttl_is_positive_integer(): void
    {
        $ttl = config('ai.cache.ttl_hours');
        
        $this->assertIsInt($ttl, 'Cache TTL should be an integer');
        $this->assertGreaterThan(0, $ttl, 'Cache TTL should be positive');
    }

    // =========================================================================
    // CFG-014: Base URL is valid URL format
    // =========================================================================

    #[Test]
    #[TestDox('CFG-014: Base URL is valid URL format')]
    public function base_url_is_valid_url_format(): void
    {
        $baseUrl = config('ai.providers.openrouter.base_url');
        
        $this->assertIsString($baseUrl, 'Base URL should be a string');
        $this->assertStringStartsWith('https://', $baseUrl, 'Base URL should use HTTPS');
        $this->assertTrue(
            filter_var($baseUrl, FILTER_VALIDATE_URL) !== false,
            'Base URL should be a valid URL'
        );
    }

    // =========================================================================
    // CFG-015: Configuration is loadable
    // =========================================================================

    #[Test]
    #[TestDox('CFG-015: Configuration is loadable')]
    public function configuration_is_loadable(): void
    {
        // This test verifies the config file doesn't have syntax errors
        $config = include config_path('ai.php');
        
        $this->assertIsArray($config, 'Config file should return an array');
        $this->assertArrayHasKey('default', $config, 'Config must have default key');
        $this->assertArrayHasKey('providers', $config, 'Config must have providers key');
    }

    // =========================================================================
    // CFG-016: Default values are reasonable
    // =========================================================================

    #[Test]
    #[TestDox('CFG-016: Default values are reasonable')]
    public function default_values_are_reasonable(): void
    {
        $this->assertEquals('openrouter', config('ai.default'), 'Default provider should be openrouter');
        $this->assertTrue(config('ai.fallback.enabled'), 'Fallback should be enabled by default');
        $this->assertEquals(24, config('ai.cache.ttl_hours'), 'Default cache TTL should be 24 hours');
    }

    // =========================================================================
    // CFG-017: Providers array structure
    // =========================================================================

    #[Test]
    #[TestDox('CFG-017: Providers array structure')]
    public function providers_array_structure(): void
    {
        $providers = config('ai.providers');
        
        $this->assertIsArray($providers, 'Providers should be an array');
        $this->assertArrayHasKey('openrouter', $providers, 'Should have openrouter provider');
        $this->assertArrayHasKey('vercel', $providers, 'Should have vercel provider');
    }
}
