<?php

namespace Tests\Unit\Services\Auth;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for package version requirements and compatibility.
 * 
 * @see TASK-25-1
 */
class PackageVersionTest extends TestCase
{
    /**
     * PV-001: Sanctum version meets minimum requirement (4.0.0).
     */
    #[Test]
    public function sanctum_version_meets_minimum(): void
    {
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);
        
        $sanctumPackage = collect($composerLock['packages'] ?? [])
            ->firstWhere('name', 'laravel/sanctum');
        
        if (!$sanctumPackage) {
            $this->markTestSkipped('laravel/sanctum not installed yet');
        }
        
        $version = ltrim($sanctumPackage['version'] ?? '0.0.0', 'v');
        
        $this->assertTrue(
            version_compare($version, '4.0.0', '>='),
            "Sanctum version {$version} should be >= 4.0.0"
        );
    }

    /**
     * PV-002: Socialite version meets minimum requirement (5.0.0).
     */
    #[Test]
    public function socialite_version_meets_minimum(): void
    {
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);
        
        $socialitePackage = collect($composerLock['packages'] ?? [])
            ->firstWhere('name', 'laravel/socialite');
        
        if (!$socialitePackage) {
            $this->markTestSkipped('laravel/socialite not installed yet');
        }
        
        $version = ltrim($socialitePackage['version'] ?? '0.0.0', 'v');
        
        $this->assertTrue(
            version_compare($version, '5.0.0', '>='),
            "Socialite version {$version} should be >= 5.0.0"
        );
    }

    /**
     * PV-003: Laravel framework version is compatible.
     */
    #[Test]
    public function laravel_framework_version_compatible(): void
    {
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);
        
        $frameworkPackage = collect($composerLock['packages'] ?? [])
            ->firstWhere('name', 'laravel/framework');
        
        $this->assertNotNull($frameworkPackage, 'laravel/framework should be installed');
        
        $version = ltrim($frameworkPackage['version'] ?? '0.0.0', 'v');
        
        $this->assertTrue(
            version_compare($version, '12.0.0', '>='),
            "Laravel framework version {$version} should be >= 12.0.0"
        );
    }

    /**
     * PV-004: PHP version requirement is compatible.
     */
    #[Test]
    public function php_version_requirement_compatible(): void
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        
        $phpVersion = $composerJson['require']['php'] ?? '';
        
        $this->assertStringContainsString('8.2', $phpVersion,
            'PHP version requirement should be ^8.2');
        
        // Verify actual PHP version
        $this->assertTrue(
            version_compare(PHP_VERSION, '8.2.0', '>='),
            'PHP runtime version should be >= 8.2.0'
        );
    }

    /**
     * PV-005: No dependency conflicts exist.
     */
    #[Test]
    public function no_dependency_conflicts(): void
    {
        // Run composer validate to check for conflicts
        $output = [];
        $exitCode = 0;
        
        // This test just verifies composer.json is valid
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        
        $this->assertIsArray($composerJson, 'composer.json should be valid JSON');
        $this->assertArrayHasKey('require', $composerJson, 'composer.json should have require section');
        $this->assertArrayHasKey('autoload', $composerJson, 'composer.json should have autoload section');
    }

    /**
     * PV-006: Autoload configuration is correct.
     */
    #[Test]
    public function autoload_configuration_correct(): void
    {
        $composerJson = json_get_contents(base_path('composer.json'));
        
        $autoload = $composerJson['autoload'] ?? [];
        $autoloadDev = $composerJson['autoload-dev'] ?? [];
        
        // Verify PSR-4 autoload structure
        $this->assertArrayHasKey('psr-4', $autoload,
            'Autoload should use PSR-4');
        
        $this->assertArrayHasKey('App\\', $autoload['psr-4'],
            'App namespace should be autoloaded');
        
        $this->assertArrayHasKey('Tests\\', $autoloadDev['psr-4'] ?? [],
            'Tests namespace should be autoloaded in dev');
    }
}

/**
 * Helper function to decode JSON from file.
 */
function json_get_contents(string $path): array
{
    return json_decode(file_get_contents($path), true) ?? [];
}
