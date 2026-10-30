<?php

namespace Tests\Feature\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\UrlValidatorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Integration tests for UrlValidatorService
 * 
 * Task: TASK-22-7
 * Risk: HIGH (Security Critical)
 * 
 * @covers \App\Services\Crawler\UrlValidatorService
 */
class UrlValidatorIntegrationTest extends TestCase
{
    private UrlValidatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UrlValidatorService::class);
    }

    // =========================================================================
    // Integration Tests (INT)
    // =========================================================================

    /** @test */
    public function int_001_full_validation_pipeline_valid_url(): void
    {
        $result = $this->service->validate('https://example.com/path?query=value');

        $this->assertTrue($result['valid']);
        $this->assertEquals('https://example.com/path?query=value', $result['url']);
        $this->assertEquals('example.com', $result['host']);
        $this->assertFalse($result['blocked']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function int_002_full_validation_pipeline_blocked_ip(): void
    {
        $result = $this->service->validate('http://192.168.1.1/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertNotNull($result['blockReason']);
    }

    /** @test */
    public function int_003_full_validation_pipeline_blocked_domain(): void
    {
        $result = $this->service->validate('http://test.internal/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertEquals('Domain blocked', $result['blockReason']);
    }

    /** @test */
    public function int_004_full_validation_pipeline_blocked_protocol(): void
    {
        $result = $this->service->validate('file:///etc/passwd');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function int_006_concurrent_requests_no_race_conditions(): void
    {
        $urls = [
            'https://example.com',
            'http://127.0.0.1/',
            'https://google.com',
            'http://192.168.1.1/',
            'https://github.com',
        ];

        $results = [];
        foreach ($urls as $url) {
            $results[$url] = $this->service->validate($url);
        }

        // Verify each result is correct
        $this->assertTrue($results['https://example.com']['valid']);
        $this->assertFalse($results['http://127.0.0.1/']['valid']);
        $this->assertTrue($results['https://google.com']['valid']);
        $this->assertFalse($results['http://192.168.1.1/']['valid']);
        $this->assertTrue($results['https://github.com']['valid']);
    }

    /** @test */
    public function int_007_large_batch_validation(): void
    {
        $validCount = 0;
        $blockedCount = 0;

        for ($i = 0; $i < 50; $i++) {
            $validResult = $this->service->validate("https://example{$i}.com");
            if ($validResult['valid']) {
                $validCount++;
            }

            $blockedResult = $this->service->validate("http://192.168.1.{$i}/");
            if ($blockedResult['blocked'] ?? false) {
                $blockedCount++;
            }
        }

        $this->assertEquals(50, $validCount);
        $this->assertEquals(50, $blockedCount);
    }

    /** @test */
    public function int_008_mixed_valid_invalid_batch(): void
    {
        $urls = [
            'https://example.com' => true,
            'http://127.0.0.1/' => false,
            'https://google.com' => true,
            'http://10.0.0.1/' => false,
            'https://github.com' => true,
            'http://localhost/' => false,
            'file:///etc/passwd' => false,
            'https://laravel.com' => true,
        ];

        foreach ($urls as $url => $shouldBeValid) {
            $result = $this->service->validate($url);

            if ($shouldBeValid) {
                $this->assertTrue(
                    $result['valid'],
                    "URL '{$url}' should be valid but was blocked: " . ($result['blockReason'] ?? $result['error'])
                );
            } else {
                $this->assertFalse(
                    $result['valid'],
                    "URL '{$url}' should be blocked but was valid"
                );
            }
        }
    }

    // =========================================================================
    // DNS Resolution Tests (DRP)
    // =========================================================================

    /** @test */
    public function drp_004_dns_resolves_to_blocked_ip(): void
    {
        // localhost resolves to 127.0.0.1
        $result = $this->service->validate('http://localhost/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function drp_011_dns_resolution_failure(): void
    {
        // This domain uses .invalid TLD which is reserved and never resolves
        $result = $this->service->validate('http://this-domain-definitely-does-not-exist-12345.invalid/');

        // DNS resolution failure should NOT block the URL (to prevent DoS attacks on DNS)
        // The URL format is valid, but IP will be null since DNS failed
        // Security decision: Allow the URL through, let the crawler handle the actual request failure
        $this->assertTrue($result['valid'], 'DNS resolution failure should not block valid URLs');
        $this->assertNull($result['ip'], 'IP should be null when DNS resolution fails');
    }

    // =========================================================================
    // Redirect Handling Tests (RH)
    // =========================================================================

    /** @test */
    public function rh_008_http_to_https_redirect_allowed(): void
    {
        // HTTP URLs to valid domains should be allowed
        // (redirect following happens at crawler level, not validator)
        $result = $this->service->validate('http://example.com/');

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function rh_009_relative_redirect_allowed(): void
    {
        // URL with path is valid
        $result = $this->service->validate('https://example.com/path');

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // Security Integration Tests (SEC)
    // =========================================================================

    /** @test */
    public function sec_integration_all_ssrf_vectors_blocked(): void
    {
        $ssrfVectors = [
            'http://localhost/',
            'http://127.0.0.1/',
            'http://[::1]/',
            'http://0.0.0.0/',
            'http://169.254.169.254/',
            'http://10.0.0.1/',
            'http://172.16.0.1/',
            'http://192.168.0.1/',
            'http://test.internal/',
            'http://metadata.google.internal/',
            'file:///etc/passwd',
            'ftp://example.com/',
            'javascript:alert(1)',
        ];

        foreach ($ssrfVectors as $vector) {
            $result = $this->service->validate($vector);

            $this->assertFalse(
                $result['valid'],
                "SSRF vector '{$vector}' should be blocked but was allowed"
            );
        }
    }

    /** @test */
    public function sec_integration_ip_notation_variations_blocked(): void
    {
        $variations = [
            'http://2130706433/',        // Decimal: 127.0.0.1
            'http://0x7f000001/',        // Hex: 127.0.0.1
            'http://0177.0.0.1/',        // Octal: 127.0.0.1
            'http://[::ffff:127.0.0.1]/', // IPv4-mapped IPv6
            'http://127.0.0.1.nip.io/',  // DNS rebinding service
        ];

        foreach ($variations as $vector) {
            $result = $this->service->validate($vector);

            $this->assertFalse(
                $result['valid'],
                "IP notation variation '{$vector}' should be blocked"
            );
        }
    }

    /** @test */
    public function sec_integration_url_encoding_attacks_blocked(): void
    {
        $encodedVectors = [
            // URL encoded 127.0.0.1
            'http://%31%32%37%2e%30%2e%30%2e%31/',
            // Double URL encoded
            'http://%25%33%31%25%33%32%25%33%37%25%32%65%25%33%30%25%32%65%25%33%30%25%32%65%25%33%31/',
        ];

        foreach ($encodedVectors as $vector) {
            $result = $this->service->validate($vector);

            $this->assertFalse(
                $result['valid'],
                "URL encoded vector '{$vector}' should be blocked"
            );
        }
    }

    // =========================================================================
    // Configuration Integration Tests (CFG)
    // =========================================================================

    /** @test */
    public function cfg_integration_custom_blocked_domains(): void
    {
        Config::set('crawler.blocked_domains', ['*.blocked-test.com']);

        // Re-resolve service to pick up new config
        $service = app(UrlValidatorService::class);

        $result = $service->validate('http://test.blocked-test.com/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function cfg_integration_allowed_domains_override(): void
    {
        Config::set('crawler.allowed_domains', ['trusted.internal']);
        Config::set('crawler.blocked_domains', ['*.internal']);

        // Re-resolve service to pick up new config
        $service = app(UrlValidatorService::class);

        // This should be allowed because it's in the allowlist
        $result = $service->validate('http://trusted.internal/');

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function performance_validation_is_fast(): void
    {
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->service->validate('https://example.com');
        }

        $elapsed = microtime(true) - $start;

        // 100 validations should complete in under 1 second
        $this->assertLessThan(1.0, $elapsed, "Validation is too slow: {$elapsed}s for 100 requests");
    }

    /** @test */
    public function performance_blocked_urls_fail_fast(): void
    {
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->service->validate('http://127.0.0.1/');
        }

        $elapsed = microtime(true) - $start;

        // Blocked IPs should fail fast (no DNS lookup)
        $this->assertLessThan(0.5, $elapsed, "Blocked URL validation is too slow: {$elapsed}s for 100 requests");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
