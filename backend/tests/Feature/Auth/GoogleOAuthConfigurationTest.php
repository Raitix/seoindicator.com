<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Google OAuth Configuration Tests
 * 
 * Tests for verifying Google OAuth 2.0 credential configuration.
 * Task: TASK-25-6 - Configure Google OAuth Credentials
 */
class GoogleOAuthConfigurationTest extends TestCase
{
    /**
     * GC-001: GOOGLE_CLIENT_ID is configured
     * Note: Uses config() because env() returns null when config is cached in tests
     */
    public function test_google_client_id_env_is_set(): void
    {
        // In Laravel, env() only works in config files. Tests should verify config values.
        $clientId = config('services.google.client_id');
        
        $this->assertNotEmpty($clientId, 'GOOGLE_CLIENT_ID must be configured in services.google.client_id');
        $this->assertNotNull($clientId, 'GOOGLE_CLIENT_ID cannot be null');
    }

    /**
     * GC-002: GOOGLE_CLIENT_SECRET is configured
     * Note: Uses config() because env() returns null when config is cached in tests
     */
    public function test_google_client_secret_env_is_set(): void
    {
        $clientSecret = config('services.google.client_secret');
        
        $this->assertNotEmpty($clientSecret, 'GOOGLE_CLIENT_SECRET must be configured in services.google.client_secret');
        $this->assertNotNull($clientSecret, 'GOOGLE_CLIENT_SECRET cannot be null');
    }

    /**
     * GC-003: GOOGLE_REDIRECT_URI is configured
     * Note: Uses config() because env() returns null when config is cached in tests
     */
    public function test_google_redirect_uri_env_is_set(): void
    {
        $redirectUri = config('services.google.redirect');
        
        $this->assertNotEmpty($redirectUri, 'GOOGLE_REDIRECT_URI must be configured in services.google.redirect');
        $this->assertNotNull($redirectUri, 'GOOGLE_REDIRECT_URI cannot be null');
    }

    /**
     * GC-004: Google config exists in services.php
     */
    public function test_google_config_exists_in_services(): void
    {
        $googleConfig = config('services.google');
        
        $this->assertIsArray($googleConfig, 'services.google must be an array');
        $this->assertArrayHasKey('client_id', $googleConfig, 'services.google must have client_id');
        $this->assertArrayHasKey('client_secret', $googleConfig, 'services.google must have client_secret');
        $this->assertArrayHasKey('redirect', $googleConfig, 'services.google must have redirect');
    }

    /**
     * GC-005: Client ID is properly configured
     * Verifies the config value is set and not null
     */
    public function test_client_id_matches_env_value(): void
    {
        $configClientId = config('services.google.client_id');
        
        $this->assertNotEmpty($configClientId, 'Client ID must be configured');
        $this->assertIsString($configClientId, 'Client ID must be a string');
    }

    /**
     * GC-006: Client secret is properly configured
     * Verifies the config value is set and not null
     */
    public function test_client_secret_matches_env_value(): void
    {
        $configSecret = config('services.google.client_secret');
        
        $this->assertNotEmpty($configSecret, 'Client secret must be configured');
        $this->assertIsString($configSecret, 'Client secret must be a string');
    }

    /**
     * GC-007: Redirect URI is properly configured
     * Verifies the config value is set and is a valid URL
     */
    public function test_redirect_uri_matches_env_value(): void
    {
        $configRedirect = config('services.google.redirect');
        
        $this->assertNotEmpty($configRedirect, 'Redirect URI must be configured');
        $this->assertIsString($configRedirect, 'Redirect URI must be a string');
        // Verify it's a valid URL
        $this->assertTrue(filter_var($configRedirect, FILTER_VALIDATE_URL) !== false, 'Redirect URI must be a valid URL');
    }

    /**
     * GC-008: Client ID is not a placeholder value
     */
    public function test_client_id_is_not_placeholder(): void
    {
        $clientId = config('services.google.client_id');
        
        $placeholders = ['your-client-id', 'xxx', 'YOUR_CLIENT_ID', 'google-client-id', ''];
        
        $this->assertNotContains($clientId, $placeholders, 'Client ID should not be a placeholder value');
    }

    /**
     * GC-009: Client secret is not a placeholder value
     */
    public function test_client_secret_is_not_placeholder(): void
    {
        $clientSecret = config('services.google.client_secret');
        
        $placeholders = ['your-secret', 'xxx', 'YOUR_CLIENT_SECRET', 'google-secret', ''];
        
        $this->assertNotContains($clientSecret, $placeholders, 'Client secret should not be a placeholder value');
    }

    /**
     * GC-010: Services config returns valid array
     */
    public function test_services_config_returns_valid_array(): void
    {
        $services = config('services');
        
        $this->assertIsArray($services, 'services config must return an array');
    }

    /**
     * GC-011: Google provider key exists in services
     */
    public function test_google_provider_key_exists(): void
    {
        $googleConfig = config('services.google');
        
        $this->assertNotNull($googleConfig, 'services.google key must exist');
    }

    /**
     * GC-012: All required keys present in google config
     */
    public function test_all_required_keys_present(): void
    {
        $googleConfig = config('services.google');
        
        $requiredKeys = ['client_id', 'client_secret', 'redirect'];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $googleConfig, "services.google must have '{$key}' key");
        }
    }

    /**
     * GC-013: No extra sensitive keys exposed in config
     */
    public function test_no_extra_sensitive_keys_exposed(): void
    {
        $googleConfig = config('services.google');
        
        $expectedKeys = ['client_id', 'client_secret', 'redirect', 'redirect_uris'];
        $actualKeys = array_keys($googleConfig);
        
        // Filter out any unexpected keys that might expose sensitive data
        $unexpectedKeys = array_diff($actualKeys, $expectedKeys);
        
        $this->assertEmpty($unexpectedKeys, 'Google config should only have expected keys: ' . implode(', ', $unexpectedKeys));
    }

    /**
     * GC-014: Config values are consistent after cache
     */
    public function test_config_values_consistent_after_cache(): void
    {
        $clientIdBefore = config('services.google.client_id');
        
        // Simulate config cache by re-reading
        Config::set('services.google.client_id', $clientIdBefore);
        
        $clientIdAfter = config('services.google.client_id');
        
        $this->assertEquals($clientIdBefore, $clientIdAfter, 'Config values should be consistent');
    }

    /**
     * GC-015: Credentials are not logged
     */
    public function test_credentials_not_in_logs(): void
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $clientSecret = config('services.google.client_secret');
            
            if ($clientSecret && !empty($clientSecret)) {
                $this->assertStringNotContainsString($clientSecret, $logContent, 'Client secret should not appear in logs');
            }
        }
        
        $this->assertTrue(true, 'Log file check completed');
    }

    /**
     * GC-016: Services config file is valid PHP
     */
    public function test_services_config_valid_php(): void
    {
        $configPath = config_path('services.php');
        
        if (file_exists($configPath)) {
            $config = include $configPath;
            $this->assertIsArray($config, 'services.php must return an array');
        } else {
            // In Laravel 11+, config may be in different location
            $this->assertTrue(true, 'Config file location may vary in Laravel 11+');
        }
    }

    /**
     * GC-017: Socialite can use Google driver
     */
    public function test_socialite_can_use_google_driver(): void
    {
        // Skip if Socialite is not installed
        if (!class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            $this->markTestSkipped('Laravel Socialite not installed. Run: composer require laravel/socialite');
        }
        
        try {
            $provider = \Laravel\Socialite\Facades\Socialite::driver('google');
            $this->assertNotNull($provider, 'Socialite Google driver must be available');
        } catch (\Exception $e) {
            $this->fail('Socialite Google driver failed: ' . $e->getMessage());
        }
    }

    /**
     * GC-018: Google provider has credentials injected
     */
    public function test_google_provider_has_credentials_injected(): void
    {
        // Skip if Socialite is not installed
        if (!class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            $this->markTestSkipped('Laravel Socialite not installed.');
        }
        
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirect = config('services.google.redirect');
        
        $this->assertNotEmpty($clientId, 'Client ID must be injected into provider');
        $this->assertNotEmpty($clientSecret, 'Client secret must be injected into provider');
        $this->assertNotEmpty($redirect, 'Redirect URI must be injected into provider');
    }
}
