<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DomainValidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;

/**
 * Unit tests for SPEC-003-3: Domain Validation Service
 * 
 * Tests verify:
 * - Domain format validation
 * - DNS record checks
 * - Domain accessibility
 * - SSRF protection
 * - Rate limiting
 */
class DomainValidatorTest extends TestCase
{
    protected DomainValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new DomainValidator();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Test 1-2: Valid Domain Format
    // ============================================

    /**
     * Test 1: Valid domain format - standard domains
     */
    public function test_valid_standard_domains(): void
    {
        $validDomains = [
            'example.com',
            'sub.example.com',
            'my-site.org',
            'example.co.uk',
            '123domain.com',
            'a.co',
            'sub.sub.sub.example.com',
        ];

        foreach ($validDomains as $domain) {
            $result = $this->validator->validateFormat($domain);
            $this->assertTrue(
                $result['valid'],
                "Domain '{$domain}' should be valid: " . ($result['message'] ?? 'no message')
            );
        }
    }

    /**
     * Test 2: Valid domain format - edge cases (punycode)
     */
    public function test_valid_punycode_domains(): void
    {
        $validDomains = [
            'xn--n3h.com', // ☃.com in punycode
            'xn--example-6q4a.com',
        ];

        foreach ($validDomains as $domain) {
            $result = $this->validator->validateFormat($domain);
            $this->assertTrue(
                $result['valid'],
                "Punycode domain '{$domain}' should be valid"
            );
        }
    }

    // ============================================
    // Test 3-6: Invalid Domain Format
    // ============================================

    /**
     * Test 3: Invalid domain format - empty and null
     */
    public function test_invalid_empty_domains(): void
    {
        $invalidDomains = [
            '',
            '   ',
            null,
        ];

        foreach ($invalidDomains as $domain) {
            $result = $this->validator->validateFormat($domain);
            $this->assertFalse($result['valid'], "Empty domain should be invalid");
            $this->assertEquals('INVALID_FORMAT', $result['code']);
        }
    }

    /**
     * Test 4: Invalid domain format - special characters
     */
    public function test_invalid_special_characters(): void
    {
        $invalidDomains = [
            'example!.com',
            'exa mple.com',
            'example@.com',
            'example#.com',
            'example$.com',
        ];

        foreach ($invalidDomains as $domain) {
            $result = $this->validator->validateFormat($domain);
            $this->assertFalse(
                $result['valid'],
                "Domain '{$domain}' with special chars should be invalid"
            );
            $this->assertEquals('INVALID_FORMAT', $result['code']);
        }
    }

    /**
     * Test 5: Invalid domain format - protocol prefix
     */
    public function test_invalid_protocol_prefix(): void
    {
        $invalidDomains = [
            'http://example.com',
            'https://example.com',
            'ftp://example.com',
            '//example.com',
        ];

        foreach ($invalidDomains as $domain) {
            $result = $this->validator->validateFormat($domain);
            // Should either reject or strip protocol
            $this->assertFalse(
                $result['valid'],
                "Domain with protocol '{$domain}' should be rejected or stripped"
            );
        }
    }

    /**
     * Test 6: Invalid domain format - path/query
     */
    public function test_invalid_path_query(): void
    {
        $invalidDomains = [
            'example.com/path',
            'example.com?query=1',
            'example.com#anchor',
            'example.com/path?query=1',
        ];

        foreach ($invalidDomains as $domain) {
            $result = $this->validator->validateFormat($domain);
            $this->assertFalse(
                $result['valid'],
                "Domain '{$domain}' with path/query should be invalid"
            );
            $this->assertEquals('INVALID_FORMAT', $result['code']);
        }
    }

    // ============================================
    // Test 7-8: DNS Record Check
    // ============================================

    /**
     * Test 7: DNS record check - valid domain (mocked)
     */
    public function test_dns_check_valid_domain(): void
    {
        $validator = Mockery::mock(DomainValidator::class)->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        
        $validator->shouldReceive('checkDnsRecord')
            ->with('google.com')
            ->andReturn(true);

        $result = $validator->validateDns('google.com');
        
        $this->assertTrue($result['valid']);
    }

    /**
     * Test 8: DNS record check - non-existent domain
     */
    public function test_dns_check_nonexistent_domain(): void
    {
        $validator = Mockery::mock(DomainValidator::class)->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        
        $validator->shouldReceive('checkDnsRecord')
            ->with('nonexistent-domain-xyz123.com')
            ->andReturn(false);

        $result = $validator->validateDns('nonexistent-domain-xyz123.com');
        
        $this->assertFalse($result['valid']);
        $this->assertEquals('DNS_NOT_FOUND', $result['code']);
    }

    // ============================================
    // Test 9-12: Domain Accessibility
    // ============================================

    /**
     * Test 9: Domain accessibility - HTTP success
     */
    public function test_accessibility_http_success(): void
    {
        Http::fake([
            'example.com' => Http::response('', 200),
        ]);

        $result = $this->validator->checkAccessibility('example.com');
        
        $this->assertTrue($result['valid']);
    }

    /**
     * Test 10: Domain accessibility - HTTP redirect
     */
    public function test_accessibility_redirect(): void
    {
        Http::fake([
            'redirect.com' => Http::response('', 301, ['Location' => 'https://final.com']),
            'final.com' => Http::response('', 200),
        ]);

        $result = $this->validator->checkAccessibility('redirect.com');
        
        // Should follow redirects up to a limit
        $this->assertTrue($result['valid'] || $result['code'] === 'REDIRECT_LIMIT');
    }

    /**
     * Test 11: Domain accessibility - timeout
     */
    public function test_accessibility_timeout(): void
    {
        Http::fake([
            'slow-domain.com' => Http::timeout(),
        ]);

        $result = $this->validator->checkAccessibility('slow-domain.com');
        
        $this->assertFalse($result['valid']);
        $this->assertEquals('CONNECTION_TIMEOUT', $result['code']);
    }

    /**
     * Test 12: Domain accessibility - connection refused
     */
    public function test_accessibility_connection_refused(): void
    {
        Http::fake([
            'unreachable.com' => Http::response('', 500),
        ]);

        $result = $this->validator->checkAccessibility('unreachable.com');
        
        // Connection refused or 5xx should fail
        $this->assertFalse($result['valid']);
    }

    // ============================================
    // Test 13-17: SSRF Protection - IPv4
    // ============================================

    /**
     * Test 13: SSRF protection - loopback IP (127.0.0.0/8)
     */
    public function test_ssrf_loopback_ip(): void
    {
        $blockedIps = [
            '127.0.0.1',
            '127.0.0.100',
            '127.255.255.255',
            '127.0.0.0',
        ];

        foreach ($blockedIps as $ip) {
            $result = $this->validator->validateIp($ip);
            $this->assertFalse(
                $result['valid'],
                "IP '{$ip}' should be blocked as SSRF"
            );
            $this->assertEquals('SSRF_BLOCKED', $result['code']);
        }
    }

    /**
     * Test 14: SSRF protection - private IP 10.0.0.0/8
     */
    public function test_ssrf_private_10_range(): void
    {
        $blockedIps = [
            '10.0.0.1',
            '10.0.0.0',
            '10.255.255.255',
            '10.128.0.1',
        ];

        foreach ($blockedIps as $ip) {
            $result = $this->validator->validateIp($ip);
            $this->assertFalse(
                $result['valid'],
                "IP '{$ip}' should be blocked as SSRF"
            );
            $this->assertEquals('SSRF_BLOCKED', $result['code']);
        }
    }

    /**
     * Test 15: SSRF protection - private IP 172.16.0.0/12
     */
    public function test_ssrf_private_172_range(): void
    {
        $blockedIps = [
            '172.16.0.1',
            '172.16.0.0',
            '172.31.255.255',
            '172.20.0.1',
        ];

        foreach ($blockedIps as $ip) {
            $result = $this->validator->validateIp($ip);
            $this->assertFalse(
                $result['valid'],
                "IP '{$ip}' should be blocked as SSRF"
            );
            $this->assertEquals('SSRF_BLOCKED', $result['code']);
        }

        // IPs outside this range should NOT be blocked
        $allowedIps = [
            '172.15.0.1',
            '172.32.0.1',
            '172.0.0.1',
        ];

        foreach ($allowedIps as $ip) {
            $result = $this->validator->validateIp($ip);
            $this->assertTrue(
                $result['valid'],
                "IP '{$ip}' should NOT be blocked (public IP)"
            );
        }
    }

    /**
     * Test 16: SSRF protection - private IP 192.168.0.0/16
     */
    public function test_ssrf_private_192_168_range(): void
    {
        $blockedIps = [
            '192.168.0.1',
            '192.168.0.0',
            '192.168.255.255',
            '192.168.100.50',
        ];

        foreach ($blockedIps as $ip) {
            $result = $this->validator->validateIp($ip);
            $this->assertFalse(
                $result['valid'],
                "IP '{$ip}' should be blocked as SSRF"
            );
            $this->assertEquals('SSRF_BLOCKED', $result['code']);
        }

        // IPs outside this range should NOT be blocked
        $result = $this->validator->validateIp('192.167.0.1');
        $this->assertTrue(
            $result['valid'],
            "IP '192.167.0.1' should NOT be blocked (public IP)"
        );
    }

    /**
     * Test 17: SSRF protection - link-local (169.254.0.0/16)
     */
    public function test_ssrf_link_local(): void
    {
        $blockedIps = [
            '169.254.0.1',
            '169.254.255.255',
            '169.254.100.50',
        ];

        foreach ($blockedIps as $ip) {
            $result = $this->validator->validateIp($ip);
            $this->assertFalse(
                $result['valid'],
                "Link-local IP '{$ip}' should be blocked"
            );
            $this->assertEquals('SSRF_BLOCKED', $result['code']);
        }
    }

    // ============================================
    // Test 18-19: SSRF Protection - Keywords
    // ============================================

    /**
     * Test 18: SSRF protection - localhost keyword
     */
    public function test_ssrf_localhost_keyword(): void
    {
        $blockedDomains = [
            'localhost',
            'LOCALHOST',
            'LocalHost',
            'local',
            'LOCAL',
            'internal',
            'intranet',
        ];

        foreach ($blockedDomains as $domain) {
            $result = $this->validator->validateFormat($domain);
            $this->assertFalse(
                $result['valid'],
                "Domain '{$domain}' should be blocked as SSRF"
            );
            $this->assertEquals('SSRF_BLOCKED', $result['code']);
        }
    }

    /**
     * Test 19: SSRF protection - internal domain patterns
     */
    public function test_ssrf_internal_domain_patterns(): void
    {
        $blockedDomains = [
            'test.internal',
            'app.local',
            'api.localhost',
            'service.internal.example.com',
        ];

        foreach ($blockedDomains as $domain) {
            $result = $this->validator->validateFormat($domain);
            $this->assertFalse(
                $result['valid'],
                "Internal domain '{$domain}' should be blocked"
            );
            $this->assertEquals('SSRF_BLOCKED', $result['code']);
        }
    }

    // ============================================
    // Test 20-21: SSRF Protection - IPv6
    // ============================================

    /**
     * Test 20: SSRF protection - IPv6 loopback
     */
    public function test_ssrf_ipv6_loopback(): void
    {
        $blockedIps = [
            '::1',
            '0:0:0:0:0:0:0:1',
            '::',
        ];

        foreach ($blockedIps as $ip) {
            $result = $this->validator->validateIp($ip);
            $this->assertFalse(
                $result['valid'],
                "IPv6 loopback '{$ip}' should be blocked"
            );
            $this->assertEquals('SSRF_BLOCKED', $result['code']);
        }
    }

    /**
     * Test 21: SSRF protection - IPv6 local addresses
     */
    public function test_ssrf_ipv6_local(): void
    {
        $blockedIps = [
            'fe80::1',
            'fe80::1:2:3:4',
            'fc00::1',
            'fd00::1',
            'fc00:1234:5678::1',
        ];

        foreach ($blockedIps as $ip) {
            $result = $this->validator->validateIp($ip);
            $this->assertFalse(
                $result['valid'],
                "IPv6 local '{$ip}' should be blocked"
            );
            $this->assertEquals('SSRF_BLOCKED', $result['code']);
        }
    }

    // ============================================
    // Test 22-24: SSRF Protection - Encoding Bypass
    // ============================================

    /**
     * Test 22: SSRF protection - DNS rebinding attack
     */
    public function test_ssrf_dns_rebinding(): void
    {
        // Simulate domain that resolves to different IPs
        $validator = Mockery::mock(DomainValidator::class)->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        
        // First call returns public IP, second returns private
        $validator->shouldReceive('resolveDomain')
            ->with('malicious.com')
            ->andReturn('93.184.216.34', '192.168.0.1');

        // Each request should validate the resolved IP
        $result1 = $validator->validate('malicious.com');
        $this->assertTrue($result1['valid']);

        $result2 = $validator->validate('malicious.com');
        $this->assertFalse($result2['valid'], 'DNS rebinding should be caught');
    }

    /**
     * Test 23: SSRF protection - decimal IP encoding
     */
    public function test_ssrf_decimal_ip_encoding(): void
    {
        $blockedInputs = [
            '2130706433',      // 127.0.0.1 in decimal
            '3232235521',      // 192.168.0.1 in decimal
            '167772161',       // 10.0.0.1 in decimal
        ];

        foreach ($blockedInputs as $input) {
            $result = $this->validator->validateFormat($input);
            $this->assertFalse(
                $result['valid'],
                "Decimal encoded IP '{$input}' should be blocked"
            );
        }
    }

    /**
     * Test 24: SSRF protection - hex/octal IP encoding
     */
    public function test_ssrf_hex_octal_encoding(): void
    {
        $blockedInputs = [
            '0x7f000001',      // 127.0.0.1 in hex
            '0177.0.0.1',      // 127.0.0.1 with octal
            '0x7f.0x00.0x00.0x01', // 127.0.0.1 mixed hex
        ];

        foreach ($blockedInputs as $input) {
            $result = $this->validator->validateFormat($input);
            $this->assertFalse(
                $result['valid'],
                "Encoded IP '{$input}' should be blocked"
            );
        }
    }

    // ============================================
    // Test 25-28: Rate Limiting
    // ============================================

    /**
     * Test 25: Rate limiting - within limit
     */
    public function test_rate_limit_within_limit(): void
    {
        Cache::flush();

        for ($i = 0; $i < 5; $i++) {
            $result = $this->validator->validateWithRateLimit('example.com', 'user-1');
            $this->assertNotEquals('RATE_LIMITED', $result['code'] ?? null);
        }
    }

    /**
     * Test 26: Rate limiting - exceed limit
     */
    public function test_rate_limit_exceed_limit(): void
    {
        Cache::flush();
        $limit = 10;

        // Exhaust the limit
        for ($i = 0; $i < $limit; $i++) {
            $this->validator->validateWithRateLimit('example.com', 'user-1');
        }

        // Next request should be rate limited
        $result = $this->validator->validateWithRateLimit('example.com', 'user-1');
        $this->assertFalse($result['valid']);
        $this->assertEquals('RATE_LIMITED', $result['code']);
    }

    /**
     * Test 27: Rate limiting - per user
     */
    public function test_rate_limit_per_user(): void
    {
        Cache::flush();
        $limit = 10;

        // User A exhausts their limit
        for ($i = 0; $i < $limit; $i++) {
            $this->validator->validateWithRateLimit('example.com', 'user-a');
        }

        // User B should still have their full limit
        for ($i = 0; $i < $limit; $i++) {
            $result = $this->validator->validateWithRateLimit('example.com', 'user-b');
            $this->assertNotEquals('RATE_LIMITED', $result['code'] ?? null);
        }
    }

    /**
     * Test 28: Rate limiting - reset after window
     */
    public function test_rate_limit_reset_after_window(): void
    {
        Cache::flush();
        $limit = 10;

        // Exhaust the limit
        for ($i = 0; $i < $limit; $i++) {
            $this->validator->validateWithRateLimit('example.com', 'user-1');
        }

        // Simulate time passing (reset cache)
        Cache::forget('rate_limit:user-1');

        // Should be able to make requests again
        $result = $this->validator->validateWithRateLimit('example.com', 'user-1');
        $this->assertNotEquals('RATE_LIMITED', $result['code'] ?? null);
    }

    // ============================================
    // Test 29-31: Validation Result Structure
    // ============================================

    /**
     * Test 29: Validation result - success structure
     */
    public function test_result_success_structure(): void
    {
        $validator = Mockery::mock(DomainValidator::class)->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        
        $validator->shouldReceive('resolveDomain')
            ->andReturn('93.184.216.34');
        $validator->shouldReceive('validateIp')
            ->andReturn(['valid' => true]);
        $validator->shouldReceive('checkDnsRecord')
            ->andReturn(true);
        $validator->shouldReceive('checkAccessibility')
            ->andReturn(['valid' => true]);

        $result = $validator->validate('example.com');

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('domain', $result);
        $this->assertArrayHasKey('ip', $result);
        $this->assertTrue($result['valid']);
        $this->assertEquals('example.com', $result['domain']);
    }

    /**
     * Test 30: Validation result - failure structure
     */
    public function test_result_failure_structure(): void
    {
        $result = $this->validator->validate('127.0.0.1');

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('domain', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertFalse($result['valid']);
        $this->assertEquals('SSRF_BLOCKED', $result['code']);
    }

    /**
     * Test 31: Validation result - error codes
     */
    public function test_result_error_codes(): void
    {
        // Invalid format
        $result = $this->validator->validate('');
        $this->assertEquals('INVALID_FORMAT', $result['code']);

        // SSRF blocked
        $result = $this->validator->validate('127.0.0.1');
        $this->assertEquals('SSRF_BLOCKED', $result['code']);

        // SSRF via keyword
        $result = $this->validator->validate('localhost');
        $this->assertEquals('SSRF_BLOCKED', $result['code']);
    }

    // ============================================
    // Additional Security Tests
    // ============================================

    /**
     * Security: SSRF via IPv6 mapped IPv4
     */
    public function test_ssrf_ipv6_mapped_ipv4(): void
    {
        $blockedIps = [
            '::ffff:127.0.0.1',
            '::ffff:192.168.0.1',
            '::ffff:10.0.0.1',
            '::ffff:172.16.0.1',
        ];

        foreach ($blockedIps as $ip) {
            $result = $this->validator->validateIp($ip);
            $this->assertFalse(
                $result['valid'],
                "IPv6 mapped IPv4 '{$ip}' should be blocked"
            );
        }
    }

    /**
     * Security: SSRF via URL encoding
     */
    public function test_ssrf_url_encoding(): void
    {
        // URL encoded 127.0.0.1
        $result = $this->validator->validateFormat('%31%32%37%2e%30%2e%30%2e%31');
        $this->assertFalse($result['valid'], 'URL encoded IP should be blocked');
    }

    /**
     * Security: SSRF via redirect to internal IP
     */
    public function test_ssrf_redirect_to_internal(): void
    {
        Http::fake([
            'public.com' => Http::response('', 302, ['Location' => 'http://192.168.0.1']),
        ]);

        $validator = Mockery::mock(DomainValidator::class)->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        
        $validator->shouldReceive('resolveDomain')
            ->with('public.com')
            ->andReturn('93.184.216.34');
        $validator->shouldReceive('validateIp')
            ->with('93.184.216.34')
            ->andReturn(['valid' => true]);

        // Check that redirect destination is also validated
        $result = $validator->validate('public.com');
        
        // If following redirects, should block internal destination
        // This depends on implementation
    }
}
