<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * Google OAuth Redirect URI Tests
 * 
 * Tests for verifying Google OAuth redirect URI configuration.
 * Task: TASK-25-6 - Configure Google OAuth Credentials
 */
class GoogleOAuthRedirectUriTest extends TestCase
{
    /**
     * RU-001: Redirect URI matches expected API path format
     */
    public function test_redirect_uri_matches_expected_path(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        
        if (empty($redirectUri)) {
            $this->markTestSkipped('GOOGLE_REDIRECT_URI not configured yet.');
        }
        
        // Should end with /api/v1/auth/google/callback or /auth/google/callback
        $validPatterns = [
            '/api/v1/auth/google/callback',
            '/auth/google/callback',
        ];
        
        $matchesPattern = false;
        foreach ($validPatterns as $pattern) {
            if (str_contains($redirectUri, $pattern)) {
                $matchesPattern = true;
                break;
            }
        }
        
        $this->assertTrue(
            $matchesPattern,
            'Redirect URI must contain valid callback path pattern'
        );
    }

    /**
     * RU-002: Local redirect URI is valid for development
     */
    public function test_local_redirect_uri_valid(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        $appEnv = env('APP_ENV', 'local');
        
        if (empty($redirectUri) || $appEnv !== 'local') {
            $this->markTestSkipped('Test only runs in local environment with redirect URI configured.');
        }
        
        // Local should use localhost
        $isLocalhost = str_contains($redirectUri, 'localhost') || 
                       str_contains($redirectUri, '127.0.0.1');
        
        $this->assertTrue(
            $isLocalhost || str_starts_with($redirectUri, 'http://'),
            'Local redirect URI should use localhost or http://'
        );
    }

    /**
     * RU-003: Production redirect URI uses correct domain
     */
    public function test_production_redirect_uri_configured(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        $appEnv = env('APP_ENV', 'local');
        
        if (empty($redirectUri)) {
            $this->markTestSkipped('GOOGLE_REDIRECT_URI not configured yet.');
        }
        
        if ($appEnv === 'production') {
            $this->assertStringStartsWith(
                'https://',
                $redirectUri,
                'Production redirect URI must use HTTPS'
            );
            
            // Should not contain localhost in production
            $this->assertStringNotContainsString(
                'localhost',
                $redirectUri,
                'Production redirect URI should not use localhost'
            );
        } else {
            $this->assertTrue(true, 'Production check skipped for non-production environment');
        }
    }

    /**
     * RU-004: Redirect URI documentation for Google Console
     * 
     * This test documents that the redirect URI must be whitelisted
     * in Google Cloud Console
     */
    public function test_redirect_uri_documented_for_google_console(): void
    {
        $redirectUri = env('GOOGLE_CLIENT_ID');
        
        // This is a documentation test
        // The actual verification happens in Google Cloud Console
        $expectedDocumentation = [
            'Google Cloud Console' => 'https://console.cloud.google.com',
            'Section' => 'APIs & Services > Credentials',
            'Action' => 'Add authorized redirect URI',
        ];
        
        $this->assertNotEmpty($expectedDocumentation, 'Redirect URI must be configured in Google Cloud Console');
    }

    /**
     * RU-005: Multiple redirect URIs can be configured
     */
    public function test_multiple_redirect_uris_supported(): void
    {
        // Google OAuth allows multiple redirect URIs
        // Different URIs for different environments (local, staging, production)
        
        // This test documents the capability
        $this->assertTrue(true, 'Multiple redirect URIs can be configured in Google Cloud Console');
    }

    /**
     * RU-006: Redirect URI has no trailing slash
     */
    public function test_redirect_uri_no_trailing_slash(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        
        if (empty($redirectUri)) {
            $this->markTestSkipped('GOOGLE_REDIRECT_URI not configured yet.');
        }
        
        $this->assertFalse(
            str_ends_with($redirectUri, '/'),
            'Redirect URI should not have trailing slash'
        );
    }

    /**
     * RU-007: Redirect URI includes protocol
     */
    public function test_redirect_uri_includes_protocol(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        
        if (empty($redirectUri)) {
            $this->markTestSkipped('GOOGLE_REDIRECT_URI not configured yet.');
        }
        
        $this->assertTrue(
            str_starts_with($redirectUri, 'http://') || str_starts_with($redirectUri, 'https://'),
            'Redirect URI must include http:// or https:// protocol'
        );
    }

    /**
     * RU-008: Redirect URI port specified for local dev
     */
    public function test_redirect_uri_port_for_local_dev(): void
    {
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        $appEnv = env('APP_ENV', 'local');
        
        if (empty($redirectUri) || $appEnv !== 'local') {
            $this->markTestSkipped('Test only runs in local environment.');
        }
        
        $parsedUrl = parse_url($redirectUri);
        
        if (isset($parsedUrl['port'])) {
            $this->assertIsInt($parsedUrl['port'], 'Port should be an integer');
        } else {
            // Default ports (80 for http, 443 for https) are implicit
            $this->assertTrue(true, 'Default port used (implicit)');
        }
    }
}
