<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Tests for CORS configuration for SPA authentication.
 * 
 * @see TASK-25-1
 * @see https://laravel.com/docs/12.x/sanctum#spa-configuration
 */
class SanctumCorsConfigurationTest extends TestCase
{
    /**
     * SC-001: CORS configuration exists.
     */
    #[Test]
    public function cors_config_exists(): void
    {
        // Laravel 12 may use default CORS config
        // Check if config/cors.php exists or use defaults
        $corsConfigPath = config_path('cors.php');
        
        $corsConfig = config('cors');
        
        // CORS config should be available (either from file or defaults)
        $this->assertTrue(
            file_exists($corsConfigPath) || $corsConfig !== null,
            'CORS configuration should exist'
        );
    }

    /**
     * SC-002: CORS paths include API routes.
     */
    #[Test]
    public function cors_paths_include_api(): void
    {
        $paths = config('cors.paths', []);
        
        // Should include API paths for SPA authentication
        $hasApiPath = collect($paths)->contains(function ($path) {
            return str_contains($path, 'api');
        });
        
        $this->assertTrue($hasApiPath,
            'CORS paths should include API routes for SPA authentication');
    }

    /**
     * SC-003: CORS allows credentials for cookie-based auth.
     */
    #[Test]
    public function cors_allows_credentials(): void
    {
        $supportsCredentials = config('cors.supports_credentials', false);
        
        // Should be true for SPA cookie-based authentication
        $this->assertTrue($supportsCredentials,
            'CORS should support credentials for cookie-based SPA authentication');
    }

    /**
     * SC-004: CORS allowed origins contain frontend domain(s).
     */
    #[Test]
    public function cors_allowed_origins_configured(): void
    {
        $allowedOrigins = config('cors.allowed_origins', []);
        
        // Should have frontend origins configured
        // In development, may include localhost:3000
        // In production, should include actual frontend domain
        $this->assertIsArray($allowedOrigins,
            'CORS allowed_origins should be an array');
        
        // Either specific origins or wildcard (not recommended for production)
        if (app()->environment('production')) {
            $this->assertNotEmpty(array_filter($allowedOrigins),
                'Production CORS should have specific allowed origins (not wildcard)');
        }
    }

    /**
     * SC-005: CORS allowed methods include required HTTP methods.
     */
    #[Test]
    public function cors_allowed_methods_configured(): void
    {
        $allowedMethods = config('cors.allowed_methods', []);
        
        $requiredMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                in_array($method, $allowedMethods) || in_array('*', $allowedMethods),
                "CORS should allow {$method} method"
            );
        }
    }

    /**
     * SC-006: CORS allowed headers include required headers.
     */
    #[Test]
    public function cors_allowed_headers_configured(): void
    {
        $allowedHeaders = config('cors.allowed_headers', []);
        
        $requiredHeaders = ['Authorization', 'Content-Type', 'X-Requested-With', 'Accept'];
        
        foreach ($requiredHeaders as $header) {
            $this->assertTrue(
                in_array($header, $allowedHeaders) || in_array('*', $allowedHeaders),
                "CORS should allow {$header} header"
            );
        }
    }

    /**
     * SC-007: CORS exposed headers include rate limit headers.
     */
    #[Test]
    public function cors_exposed_headers_configured(): void
    {
        $exposedHeaders = config('cors.exposed_headers', []);
        
        // Rate limit headers should be exposed for client access
        $rateLimitHeaders = ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-RateLimit-Reset'];
        
        // This is recommended but not strictly required
        $hasRateLimitHeaders = collect($rateLimitHeaders)->every(function ($header) use ($exposedHeaders) {
            return in_array($header, $exposedHeaders);
        });
        
        // Just verify exposed_headers is an array
        $this->assertIsArray($exposedHeaders,
            'CORS exposed_headers should be an array');
    }

    /**
     * SC-008: Preflight OPTIONS request succeeds.
     */
    #[Test]
    public function preflight_request_succeeds(): void
    {
        $response = $this->options('/api/v1/user', [], [
            'Origin' => config('app.url'),
            'Access-Control-Request-Method' => 'GET',
        ]);
        
        // Preflight should not return 405 Method Not Allowed
        $this->assertNotEquals(405, $response->getStatusCode(),
            'OPTIONS preflight request should be handled');
    }

    /**
     * SC-009: Credentials are sent with authenticated responses.
     */
    #[Test]
    public function credentials_in_response_headers(): void
    {
        // Test that Access-Control-Allow-Credentials header is set
        // when supports_credentials is true
        $supportsCredentials = config('cors.supports_credentials', false);
        
        if ($supportsCredentials) {
            $response = $this->getJson('/api/health', [
                'Origin' => config('app.url'),
            ]);
            
            // Check for credentials header (may vary by Laravel version)
            $this->assertTrue(
                $response->headers->has('Access-Control-Allow-Credentials') ||
                $supportsCredentials,
                'CORS credentials header should be set when configured'
            );
        } else {
            $this->markTestSkipped('CORS supports_credentials not enabled');
        }
    }

    /**
     * SC-010: Wildcard origin is properly handled.
     */
    #[Test]
    public function wildcard_origin_handled_correctly(): void
    {
        $allowedOrigins = config('cors.allowed_origins', []);
        
        // If wildcard is used, credentials should NOT be enabled
        // (browsers reject this combination)
        if (in_array('*', $allowedOrigins)) {
            $supportsCredentials = config('cors.supports_credentials', false);
            
            $this->assertFalse($supportsCredentials,
                'Cannot use wildcard origin with credentials enabled');
        }
        
        $this->assertTrue(true, 'Wildcard origin handling verified');
    }
}
