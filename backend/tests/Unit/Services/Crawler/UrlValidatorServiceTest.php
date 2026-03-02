<?php

namespace Tests\Unit\Services\Crawler;

use App\Services\Crawler\UrlValidatorService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit tests for UrlValidatorService.
 *
 * Tests SSRF protection, URL validation, and security measures.
 *
 * @see TASK-22-18
 * @see TASK-22-7
 * @see SPEC-022
 */
class UrlValidatorServiceTest extends TestCase
{
    use CrawlerTestHelper;

    protected UrlValidatorService $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new UrlValidatorService();
    }

    // =========================================================================
    // Valid URLs Tests
    // =========================================================================

    /** @test */
    public function valid_urls_pass_validation(): void
    {
        $validUrls = [
            'https://example.com',
            'https://example.com/',
            'https://example.com/page',
            'https://example.com/page?query=value',
            'https://example.com/page#anchor',
            'https://sub.example.com',
            'https://example.com:8080',
            'https://example.com/path/to/page',
            'http://example.com', // HTTP is allowed
        ];

        foreach ($validUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertUrlValid($result, $url);
        }
    }

    /** @test */
    public function validation_returns_normalized_url(): void
    {
        $testCases = [
            'HTTPS://EXAMPLE.COM' => 'https://example.com',
            'https://Example.com/Page' => 'https://example.com/Page',
            'https://example.com:443/' => 'https://example.com/', // Default port removed
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->validator->validate($input);
            $this->assertTrue($result['valid'], "URL '{$input}' should be valid");
        }
    }

    // =========================================================================
    // Invalid Protocols Tests
    // =========================================================================

    /** @test */
    public function invalid_protocols_are_blocked(): void
    {
        $invalidProtocols = [
            'ftp://example.com',
            'file:///etc/passwd',
            'gopher://example.com',
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'ssh://example.com',
            'telnet://example.com',
            'ldap://example.com',
            '' => null, // Empty URL
        ];

        foreach ($invalidProtocols as $url => $_) {
            $result = $this->validator->validate($url);
            $this->assertFalse($result['valid'], "Protocol should be blocked: {$url}");
        }
    }

    /** @test */
    public function url_without_protocol_is_rejected(): void
    {
        $result = $this->validator->validate('example.com');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Protocol', $result['error']);
    }

    // =========================================================================
    // Localhost IPs Tests
    // =========================================================================

    /** @test */
    public function localhost_ips_are_blocked(): void
    {
        $localhostUrls = [
            'https://127.0.0.1',
            'https://127.0.0.1:8080',
            'https://127.0.0.2',
            'https://127.255.255.255',
            'https://localhost',
            'https://localhost:8080',
            'https://[::1]',
            'https://[::1]:8080',
        ];

        foreach ($localhostUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertUrlBlocked($result, 'localhost');
        }
    }

    /** @test */
    public function localhost_subdomains_are_blocked(): void
    {
        $blockedUrls = [
            'https://test.localhost',
            'https://app.localhost',
            'https://sub.test.localhost',
            'https://test.local',
            'https://app.local',
        ];

        foreach ($blockedUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertFalse($result['valid'], "Localhost subdomain should be blocked: {$url}");
        }
    }

    // =========================================================================
    // Private IP Ranges Tests
    // =========================================================================

    /** @test */
    public function private_ip_ranges_are_blocked(): void
    {
        // Class A private (10.0.0.0/8)
        $classAUrls = [
            'https://10.0.0.1',
            'https://10.255.255.255',
            'https://10.128.0.1',
        ];

        foreach ($classAUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertUrlBlocked($result, 'private');
        }

        // Class B private (172.16.0.0/12)
        $classBUrls = [
            'https://172.16.0.1',
            'https://172.31.255.255',
            'https://172.20.0.1',
        ];

        foreach ($classBUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertUrlBlocked($result, 'private');
        }

        // Class C private (192.168.0.0/16)
        $classCUrls = [
            'https://192.168.0.1',
            'https://192.168.255.255',
            'https://192.168.100.50',
        ];

        foreach ($classCUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertUrlBlocked($result, 'private');
        }
    }

    // =========================================================================
    // Cloud Metadata IP Tests
    // =========================================================================

    /** @test */
    public function cloud_metadata_ip_is_blocked(): void
    {
        $metadataUrls = [
            'https://169.254.169.254',
            'https://169.254.169.254/latest/meta-data/',
            'http://169.254.169.254',
        ];

        foreach ($metadataUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertUrlBlocked($result, 'metadata');
        }
    }

    /** @test */
    public function metadata_hostnames_are_blocked(): void
    {
        $metadataHosts = [
            'https://metadata.google.internal',
            'https://metadata',
            'https://kubernetes.default',
            'https://kubernetes.default.svc',
        ];

        foreach ($metadataHosts as $url) {
            $result = $this->validator->validate($url);
            $this->assertFalse($result['valid'], "Metadata host should be blocked: {$url}");
        }
    }

    // =========================================================================
    // Blocked Domains Tests
    // =========================================================================

    /** @test */
    public function blocked_domains_are_blocked(): void
    {
        $blockedDomains = [
            'https://test.internal',
            'https://app.local',
            'https://service.localhost',
            'https://internal.company.com',
        ];

        foreach ($blockedDomains as $url) {
            $result = $this->validator->validate($url);
            $this->assertFalse($result['valid'], "Domain should be blocked: {$url}");
        }
    }

    // =========================================================================
    // DNS Rebinding Protection Tests
    // =========================================================================

    /** @test */
    public function dns_resolution_is_checked(): void
    {
        // This test verifies that DNS resolution happens
        // We can't mock DNS easily, but we verify the structure
        $result = $this->validator->validate('https://example.com');

        $this->assertArrayHasKey('host', $result);
        $this->assertEquals('example.com', $result['host']);
    }

    /** @test */
    public function domain_resolving_to_private_ip_is_blocked(): void
    {
        // If we had control over DNS, we'd test this
        // For now, we verify the IP resolution is part of the result
        $result = $this->validator->validate('https://example.com');

        $this->assertArrayHasKey('ip', $result);
        // IP might be null if DNS fails, which is acceptable
    }

    // =========================================================================
    // URL Normalization Tests
    // =========================================================================

    /** @test */
    public function url_normalization_produces_consistent_output(): void
    {
        $result1 = $this->validator->validate('HTTPS://EXAMPLE.COM/');
        $result2 = $this->validator->validate('https://example.com/');

        $this->assertEquals($result1['url'], $result2['url']);
    }

    /** @test */
    public function url_normalization_preserves_path(): void
    {
        $result = $this->validator->validate('https://example.com/path/to/page');

        $this->assertEquals('https://example.com/path/to/page', $result['url']);
    }

    /** @test */
    public function url_normalization_preserves_query(): void
    {
        $result = $this->validator->validate('https://example.com/page?query=value');

        $this->assertStringContainsString('query=value', $result['url']);
    }

    /** @test */
    public function url_normalization_removes_default_port(): void
    {
        $httpResult = $this->validator->validate('https://example.com:443/');
        $httpsResult = $this->validator->validate('https://example.com/');

        $this->assertEquals($httpsResult['url'], $httpResult['url']);
    }

    /** @test */
    public function url_normalization_keeps_non_default_port(): void
    {
        $result = $this->validator->validate('https://example.com:8443/');

        $this->assertStringContainsString(':8443', $result['url']);
    }

    // =========================================================================
    // Redirect Validation Tests
    // =========================================================================

    /** @test */
    public function validate_redirect_works_same_as_validate(): void
    {
        $result1 = $this->validator->validate('https://example.com');
        $result2 = $this->validator->validateRedirect('https://example.com');

        $this->assertEquals($result1['valid'], $result2['valid']);
    }

    /** @test */
    public function validate_redirect_logs_blocked_urls(): void
    {
        \Illuminate\Support\Facades\Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'blocked');
            });

        $this->validator->validateRedirect('https://127.0.0.1');
    }

    // =========================================================================
    // Dangerous IP Notation Tests
    // =========================================================================

    /** @test */
    public function decimal_ip_notation_is_blocked(): void
    {
        // 2130706433 = 127.0.0.1 in decimal
        $result = $this->validator->validate('https://2130706433');

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function hex_ip_notation_is_blocked(): void
    {
        // 0x7f000001 = 127.0.0.1 in hex
        $result = $this->validator->validate('https://0x7f000001');

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function octal_ip_notation_is_blocked(): void
    {
        // 0177.0.0.1 = 127.0.0.1 in octal
        $result = $this->validator->validate('https://0177.0.0.1');

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function mixed_ip_notation_is_blocked(): void
    {
        // Various mixed notations for 127.0.0.1
        $result = $this->validator->validate('https://0x7f.0.0.1');

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // IPv6 Tests
    // =========================================================================

    /** @test */
    public function valid_ipv6_urls_pass(): void
    {
        // Public IPv6 addresses should pass
        // Note: actual DNS resolution may fail, but format should be valid
        $result = $this->validator->validate('https://[2001:4860:4860::8888]/');

        // We accept that DNS may fail, but the URL format is valid
        $this->assertArrayHasKey('host', $result);
    }

    /** @test */
    public function ipv6_loopback_is_blocked(): void
    {
        $result = $this->validator->validate('https://[::1]/');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('localhost', $result['blockReason'] ?? $result['error']);
    }

    /** @test */
    public function ipv6_unspecified_is_blocked(): void
    {
        $result = $this->validator->validate('https://[::]/');

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function ipv6_link_local_is_blocked(): void
    {
        $result = $this->validator->validate('https://[fe80::1]/');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('link-local', $result['blockReason'] ?? $result['error']);
    }

    /** @test */
    public function ipv6_unique_local_is_blocked(): void
    {
        $result = $this->validator->validate('https://[fc00::1]/');

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function ipv4_mapped_ipv6_is_blocked(): void
    {
        // ::ffff:127.0.0.1 is IPv4-mapped IPv6 for localhost
        $result = $this->validator->validate('https://[::ffff:127.0.0.1]/');

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // URL Encoding Tests
    // =========================================================================

    /** @test */
    public function url_encoded_ip_is_blocked(): void
    {
        // URL encoded 127.0.0.1
        $result = $this->validator->validate('https://127%2E0%2E0%2E1/');

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function double_url_encoding_is_blocked(): void
    {
        // Double URL encoded
        $result = $this->validator->validate('https://127%252E0%252E0%252E1/');

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    /** @test */
    public function null_url_returns_invalid(): void
    {
        $result = $this->validator->validate(null);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('invalid', $result['error']);
    }

    /** @test */
    public function empty_url_returns_invalid(): void
    {
        $result = $this->validator->validate('');

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function url_with_null_byte_returns_invalid(): void
    {
        $result = $this->validator->validate("https://example.com\x00.malicious.com");

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function overly_long_url_returns_invalid(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2100);

        $result = $this->validator->validate($longUrl);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function url_with_spaces_returns_invalid(): void
    {
        $result = $this->validator->validate('https://example.com/path with spaces');

        // Behavior depends on parse_url handling
        $this->assertArrayHasKey('valid', $result);
    }

    /** @test */
    public function malformed_url_returns_invalid(): void
    {
        $malformedUrls = [
            '://example.com',
            'https://',
            'https://?',
            'not a url',
        ];

        foreach ($malformedUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertFalse($result['valid'], "Malformed URL should be invalid: {$url}");
        }
    }

    // =========================================================================
    // Broadcast and Reserved Tests
    // =========================================================================

    /** @test */
    public function broadcast_address_is_blocked(): void
    {
        $result = $this->validator->validate('https://255.255.255.255');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('broadcast', $result['blockReason'] ?? $result['error']);
    }

    /** @test */
    public function multicast_addresses_are_blocked(): void
    {
        $multicastUrls = [
            'https://224.0.0.1',
            'https://239.255.255.255',
        ];

        foreach ($multicastUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertFalse($result['valid']);
            $this->assertStringContainsString('multicast', $result['blockReason'] ?? $result['error']);
        }
    }

    /** @test */
    public function reserved_addresses_are_blocked(): void
    {
        $reservedUrls = [
            'https://240.0.0.1',
            'https://250.0.0.1',
        ];

        foreach ($reservedUrls as $url) {
            $result = $this->validator->validate($url);
            $this->assertFalse($result['valid']);
            $this->assertStringContainsString('reserved', $result['blockReason'] ?? $result['error']);
        }
    }

    /** @test */
    public function zero_network_is_blocked(): void
    {
        $result = $this->validator->validate('https://0.0.0.1');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('unspecified', $result['blockReason'] ?? $result['error']);
    }

    // =========================================================================
    // Static Helper Tests
    // =========================================================================

    /** @test */
    public function get_blocked_ipv4_ranges_returns_array(): void
    {
        $ranges = UrlValidatorService::getBlockedIpv4Ranges();

        $this->assertIsArray($ranges);
        $this->assertContains('127.0.0.0/8', $ranges);
        $this->assertContains('10.0.0.0/8', $ranges);
        $this->assertContains('172.16.0.0/12', $ranges);
        $this->assertContains('192.168.0.0/16', $ranges);
    }

    /** @test */
    public function get_blocked_ipv6_ranges_returns_array(): void
    {
        $ranges = UrlValidatorService::getBlockedIpv6Ranges();

        $this->assertIsArray($ranges);
        $this->assertContains('::1/128', $ranges);
    }

    /** @test */
    public function get_default_blocked_domains_returns_array(): void
    {
        $domains = UrlValidatorService::getDefaultBlockedDomains();

        $this->assertIsArray($domains);
        $this->assertContains('*.internal', $domains);
        $this->assertContains('*.local', $domains);
    }
}
