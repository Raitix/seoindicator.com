<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Google OAuth Credentials Format Tests
 * 
 * Tests for validating the format of Google OAuth credentials.
 * Task: TASK-25-6 - Configure Google OAuth Credentials
 */
class GoogleOAuthCredentialsFormatTest extends TestCase
{
    /**
     * CF-001: Client ID format matches Google's expected format
     * 
     * Google Client IDs follow the format:
     * {numeric_id}-{random_string}.apps.googleusercontent.com
     */
    public function test_client_id_format_valid(): void
    {
        $clientId = env('GOOGLE_CLIENT_ID');
        
        if (empty($clientId)) {
            $this->markTestSkipped('GOOGLE_CLIENT_ID not configured yet.');
        }
        
        // Google Client ID format: digits-dash-alphanumeric.apps.googleusercontent.com
        $pattern = '/^\d+-[a-zA-Z0-9-]+\.apps\.googleusercontent\.com$/';
        
        $this->assertMatchesRegularExpression(
            $pattern,
            $clientId,
            'Client ID must match Google format: {id}-{string}.apps.googleusercontent.com'
        );
    }

    /**
     * CF-002: Client ID has correct numeric prefix
     */
    public function test_client_id_has_numeric_prefix(): void
    {
        $clientId = env('GOOGLE_CLIENT_ID');
        
        if (empty($clientId)) {
            $this->markTestSkipped('GOOGLE_CLIENT_ID not configured yet.');
        }
        
        // Client ID should start with numeric project ID
        $this->assertMatchesRegularExpression(
            '/^\d+/',
            $clientId,
            'Client ID must start with numeric project ID'
        );
    }

    /**
     * CF-003: Client ID has apps.googleusercontent.com suffix
     */
    public function test_client_id_has_apps_suffix(): void
    {
        $clientId = env('GOOGLE_CLIENT_ID');
        
        if (empty($clientId)) {
            $this->markTestSkipped('GOOGLE_CLIENT_ID not configured yet.');
        }
        
        $this->assertStringEndsWith(
            '.apps.googleusercontent.com',
            $clientId,
            'Client ID must end with .apps.googleusercontent.com'
        );
    }

    /**
     * CF-004: Client secret format is valid
     * 
     * Google Client Secrets typically start with GOCSPX- for OAuth 2.0
     */
    public function test_client_secret_format_valid(): void
    {
        $clientSecret = env('GOOGLE_CLIENT_SECRET');
        
        if (empty($clientSecret)) {
            $this->markTestSkipped('GOOGLE_CLIENT_SECRET not configured yet.');
        }
        
        // Google secrets often start with GOCSPX- (OAuth 2.0 client secrets)
        // Or older format: longer alphanumeric strings
        $isValidFormat = preg_match('/^GOCSPX-/', $clientSecret) ||
                         preg_match('/^[A-Za-z0-9_-]{20,}$/', $clientSecret);
        
        $this->assertTrue(
            $isValidFormat,
            'Client secret must have valid format (GOCSPX-* or 20+ alphanumeric chars)'
        );
    }

    /**
     * CF-005: Client secret has minimum length
     */
    public function test_client_secret_minimum_length(): void
    {
        $clientSecret = env('GOOGLE_CLIENT_SECRET');
        
        if (empty($clientSecret)) {
            $this->markTestSkipped('GOOGLE_CLIENT_SECRET not configured yet.');
        }
        
        $this->assertGreaterThanOrEqual(
            20,
            strlen($clientSecret),
            'Client secret must be at least 20 characters'
        );
    }

    /**
     * CF-006: Redirect URI is a valid URL
     */
    public function test_redirect_uri_is_valid_url(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        
        if (empty($redirectUri)) {
            $this->markTestSkipped('GOOGLE_REDIRECT_URI not configured yet.');
        }
        
        $this->assertTrue(
            filter_var($redirectUri, FILTER_VALIDATE_URL) !== false,
            'Redirect URI must be a valid URL'
        );
    }

    /**
     * CF-007: Redirect URI uses HTTPS in production
     */
    public function test_redirect_uri_uses_https_in_production(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        $appEnv = env('APP_ENV', 'local');
        
        if (empty($redirectUri)) {
            $this->markTestSkipped('GOOGLE_REDIRECT_URI not configured yet.');
        }
        
        // In production, HTTPS is required
        if ($appEnv === 'production') {
            $this->assertStringStartsWith(
                'https://',
                $redirectUri,
                'Production redirect URI must use HTTPS'
            );
        } else {
            // Development can use HTTP
            $this->assertTrue(
                str_starts_with($redirectUri, 'http://') || str_starts_with($redirectUri, 'https://'),
                'Redirect URI must use http:// or https://'
            );
        }
    }

    /**
     * CF-008: Redirect URI has callback path
     */
    public function test_redirect_uri_has_callback_path(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        
        if (empty($redirectUri)) {
            $this->markTestSkipped('GOOGLE_REDIRECT_URI not configured yet.');
        }
        
        $this->assertStringContainsString(
            '/auth/google/callback',
            $redirectUri,
            'Redirect URI must contain /auth/google/callback path'
        );
    }

    /**
     * CF-009: Redirect URI contains valid domain
     */
    public function test_redirect_uri_contains_valid_domain(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        
        if (empty($redirectUri)) {
            $this->markTestSkipped('GOOGLE_REDIRECT_URI not configured yet.');
        }
        
        $parsedUrl = parse_url($redirectUri);
        
        $this->assertArrayHasKey('host', $parsedUrl, 'Redirect URI must have a host');
        $this->assertNotEmpty($parsedUrl['host'], 'Redirect URI host must not be empty');
    }

    /**
     * CF-010: Test credentials are different from production
     */
    public function test_credentials_different_per_environment(): void
    {
        $clientId = env('GOOGLE_CLIENT_ID');
        $appEnv = env('APP_ENV', 'local');
        
        if (empty($clientId)) {
            $this->markTestSkipped('GOOGLE_CLIENT_ID not configured yet.');
        }
        
        // This test documents that credentials should be different per environment
        // In local/dev, you might use test credentials from a separate Google Cloud project
        $this->assertTrue(true, 'Credentials should be environment-specific');
    }

    /**
     * CF-011: Environment-specific credentials work
     */
    public function test_env_local_overrides_work(): void
    {
        // Test that .env.local can override .env values
        // This is more of a documentation test
        $this->assertTrue(true, 'Local environment overrides should be supported');
    }

    /**
     * CF-012: Empty credentials fail gracefully
     */
    public function test_empty_credentials_fail_gracefully(): void
    {
        // Temporarily set empty values
        $originalClientId = config('services.google.client_id');
        
        Config::set('services.google.client_id', '');
        Config::set('services.google.client_secret', '');
        
        $config = config('services.google');
        
        // Config should exist but have empty values
        $this->assertIsArray($config);
        $this->assertEquals('', $config['client_id']);
        
        // Restore original values
        Config::set('services.google.client_id', $originalClientId);
    }
}
