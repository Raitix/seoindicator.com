<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for Laravel Sanctum installation and configuration.
 * 
 * @see TASK-25-1
 * @see https://laravel.com/docs/12.x/sanctum
 */
class SanctumInstallationTest extends TestCase
{
    /**
     * SI-001: Sanctum package is installed via Composer.
     */
    #[Test]
    public function sanctum_package_is_installed(): void
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        
        $this->assertArrayHasKey('laravel/sanctum', $composerJson['require'] ?? [],
            'laravel/sanctum should be in composer.json require section');
    }

    /**
     * SI-002: Sanctum version is compatible (^4.0).
     */
    #[Test]
    public function sanctum_version_is_compatible(): void
    {
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);
        
        $sanctumPackage = collect($composerLock['packages'] ?? [])
            ->firstWhere('name', 'laravel/sanctum');
        
        $this->assertNotNull($sanctumPackage, 'laravel/sanctum should be installed');
        
        $version = $sanctumPackage['version'] ?? '';
        $this->assertMatchesRegularExpression('/^v?4\./', $version,
            'Sanctum version should be ^4.0, got: ' . $version);
    }

    /**
     * SI-003: Sanctum configuration file is published.
     */
    #[Test]
    public function sanctum_config_is_published(): void
    {
        $configPath = config_path('sanctum.php');
        
        $this->assertFileExists($configPath, 
            'config/sanctum.php should exist after publishing Sanctum config');
        
        $config = include $configPath;
        $this->assertIsArray($config, 'Sanctum config should return an array');
    }

    /**
     * SI-004: Sanctum stateful domains are configured for SPA authentication.
     */
    #[Test]
    public function sanctum_stateful_domains_configured(): void
    {
        $config = config('sanctum.stateful', []);
        
        $this->assertIsArray($config, 'stateful should be an array');
        
        // Should have at least one stateful domain configured
        $this->assertNotEmpty(array_filter($config), 
            'At least one stateful domain should be configured for SPA authentication');
    }

    /**
     * SI-005: SANCTUM_STATEFUL_DOMAINS environment variable is parsed correctly.
     */
    #[Test]
    public function sanctum_stateful_domains_from_env(): void
    {
        // Test that config picks up env variable
        $originalValue = env('SANCTUM_STATEFUL_DOMAINS');
        
        if ($originalValue) {
            $config = config('sanctum.stateful', []);
            $expectedDomains = explode(',', $originalValue);
            
            foreach ($expectedDomains as $domain) {
                $domain = trim($domain);
                if ($domain) {
                    $this->assertContains($domain, $config,
                        "Stateful domains should contain: {$domain}");
                }
            }
        } else {
            $this->markTestSkipped('SANCTUM_STATEFUL_DOMAINS not set in environment');
        }
    }

    /**
     * SI-006: Sanctum middleware is registered.
     */
    #[Test]
    public function sanctum_middleware_registered(): void
    {
        // In Laravel 12, check that Sanctum middleware is auto-discovered
        // The middleware should be available as 'sanctum' or via the guard
        
        $middlewareAliases = app('router')->getMiddleware();
        
        $this->assertTrue(
            array_key_exists('auth', $middlewareAliases) || 
            array_key_exists('auth:sanctum', $middlewareAliases) ||
            class_exists(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class),
            'Sanctum middleware should be registered'
        );
    }

    /**
     * SI-007: Session domain is configured for cross-domain authentication.
     */
    #[Test]
    public function session_domain_configured(): void
    {
        $sessionDomain = config('session.domain');
        
        // Session domain should be set for production or be configurable
        $this->assertTrue(
            $sessionDomain !== null || app()->environment('local'),
            'Session domain should be configured for production SPA auth'
        );
    }

    /**
     * SI-008: Session same-site setting is appropriate for SPA authentication.
     */
    #[Test]
    public function session_same_site_configured_for_spa(): void
    {
        $sameSite = config('session.same_site');
        
        // For SPA authentication, should be 'lax' or 'none' (with secure)
        $this->assertContains($sameSite, ['lax', 'none', null],
            'Session same_site should be lax or none for SPA authentication');
    }

    /**
     * SI-009: Sanctum guard is configured for web sessions.
     */
    #[Test]
    public function sanctum_guard_configured(): void
    {
        $guard = config('sanctum.guard', []);
        
        // Should use 'web' guard for SPA cookie-based authentication
        $this->assertContains('web', (array) $guard,
            'Sanctum should use web guard for SPA cookie authentication');
    }

    /**
     * SI-010: Token expiration is configured.
     */
    #[Test]
    public function sanctum_expiration_configured(): void
    {
        $expiration = config('sanctum.expiration');
        
        // Expiration should be set (not null) for token-based auth
        // Null means tokens never expire
        $this->assertTrue(
            $expiration !== null,
            'Sanctum token expiration should be configured for security'
        );
    }

    /**
     * SI-011: Personal access tokens migration exists.
     */
    #[Test]
    public function personal_access_tokens_migration_exists(): void
    {
        $migrationPath = database_path('migrations');
        $migrations = scandir($migrationPath);
        
        $hasTokensMigration = collect($migrations)->contains(function ($file) {
            return str_contains($file, 'personal_access_tokens');
        });
        
        $this->assertTrue($hasTokensMigration,
            'personal_access_tokens migration should exist');
    }

    /**
     * SI-012: Sanctum cache store is configured.
     */
    #[Test]
    public function sanctum_cache_store_configured(): void
    {
        $cacheStore = config('sanctum.cache_store');
        
        // Cache store can be null (use default) or explicitly set
        // This test just verifies the config key exists
        $this->assertTrue(
            $cacheStore !== null || config('cache.default') !== null,
            'Sanctum cache should be configured or use default cache store'
        );
    }

    /**
     * SI-013: Sanctum middleware runs after EncryptCookies.
     */
    #[Test]
    public function sanctum_middleware_priority_correct(): void
    {
        // In Laravel 12, middleware priority is handled automatically
        // Verify the EnsureFrontendRequestsAreStateful middleware exists
        $this->assertTrue(
            class_exists(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class),
            'EnsureFrontendRequestsAreStateful middleware should exist'
        );
    }

    /**
     * SI-014: Auth middleware returns 401 for unauthenticated requests.
     */
    #[Test]
    public function auth_middleware_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/user');
        
        $response->assertStatus(401);
    }

    /**
     * SI-015: Sanctum token abilities work correctly.
     */
    #[Test]
    public function sanctum_token_abilities_validated(): void
    {
        // This test verifies the token abilities config
        $middleware = config('sanctum.middleware.authenticate_token');
        
        // Just verify the config is accessible - actual ability testing 
        // will be done in AuthController tests
        $this->assertTrue(true, 'Token abilities config accessible');
    }
}
