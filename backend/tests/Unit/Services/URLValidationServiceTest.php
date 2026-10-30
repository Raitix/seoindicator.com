<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\URLValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * URL Validation Service Tests for TASK-16-3.
 * 
 * Tests SSRF prevention, URL validation, and security checks.
 */
class URLValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected URLValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new URLValidationService();
    }

    // ==================== VALID URL TESTS ====================

    /**
     * UT-061: Valid HTTPS URL
     */
    public function test_valid_https_url(): void
    {
        $result = $this->service->validate('https://example.com');
        
        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('normalized_url', $result);
    }

    /**
     * UT-062: Valid HTTP URL
     */
    public function test_valid_http_url(): void
    {
        $result = $this->service->validate('http://example.com');
        
        $this->assertTrue($result['valid']);
    }

    /**
     * UT-063: URL with path
     */
    public function test_url_with_path(): void
    {
        $result = $this->service->validate('https://example.com/page');
        
        $this->assertTrue($result['valid']);
    }

    /**
     * UT-064: URL with query string
     */
    public function test_url_with_query_string(): void
    {
        $result = $this->service->validate('https://example.com?a=1&b=2');
        
        $this->assertTrue($result['valid']);
    }

    /**
     * UT-065: URL with fragment
     */
    public function test_url_with_fragment(): void
    {
        $result = $this->service->validate('https://example.com#section');
        
        $this->assertTrue($result['valid']);
    }

    /**
     * UT-066: URL with subdomain
     */
    public function test_url_with_subdomain(): void
    {
        $result = $this->service->validate('https://www.example.com');
        
        $this->assertTrue($result['valid']);
    }

    /**
     * UT-067: URL with port
     */
    public function test_url_with_port(): void
    {
        $result = $this->service->validate('https://example.com:443');
        
        $this->assertTrue($result['valid']);
    }

    // ==================== INVALID URL TESTS ====================

    /**
     * UT-070: Missing protocol
     */
    public function test_missing_protocol(): void
    {
        $result = $this->service->validate('example.com');
        
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * UT-071: Invalid protocol
     */
    public function test_invalid_protocol(): void
    {
        $result = $this->service->validate('ftp://example.com');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('protocol', strtolower($result['error']));
    }

    /**
     * UT-073: Localhost blocked
     */
    public function test_localhost_blocked(): void
    {
        $result = $this->service->validate('http://localhost');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * UT-074: 127.0.0.1 blocked
     */
    public function test_127_0_0_1_blocked(): void
    {
        $result = $this->service->validate('http://127.0.0.1');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * UT-075: 10.x.x.x blocked
     */
    public function test_10_range_blocked(): void
    {
        $result = $this->service->validate('http://10.0.0.1');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * UT-076: 172.16.x.x blocked
     */
    public function test_172_range_blocked(): void
    {
        $result = $this->service->validate('http://172.16.0.1');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * UT-077: 192.168.x.x blocked
     */
    public function test_192_168_range_blocked(): void
    {
        $result = $this->service->validate('http://192.168.1.1');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * UT-078: 169.254.x.x blocked
     */
    public function test_169_254_range_blocked(): void
    {
        $result = $this->service->validate('http://169.254.1.1');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * UT-079: Empty string
     */
    public function test_empty_string(): void
    {
        $result = $this->service->validate('');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('required', strtolower($result['error']));
    }

    /**
     * UT-080: Null input
     */
    public function test_null_input(): void
    {
        $result = $this->service->validate(null);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('required', strtolower($result['error']));
    }

    // ==================== SSRF ADVANCED TESTS ====================

    /**
     * ST-028: Decimal IP blocked
     */
    public function test_decimal_ip_blocked(): void
    {
        // 2130706433 = 127.0.0.1 in decimal
        $result = $this->service->validate('http://2130706433');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * ST-029: Hex IP blocked
     */
    public function test_hex_ip_blocked(): void
    {
        // 0x7f000001 = 127.0.0.1 in hex
        $result = $this->service->validate('http://0x7f000001');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * ST-030: Octal IP blocked
     */
    public function test_octal_ip_blocked(): void
    {
        // 0177.0.0.1 = 127.0.0.1 in octal
        $result = $this->service->validate('http://0177.0.0.1');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * ST-032: Cloud metadata IP blocked
     */
    public function test_cloud_metadata_ip_blocked(): void
    {
        $result = $this->service->validate('http://169.254.169.254');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * Blocked hosts list
     */
    public function test_blocked_hosts(): void
    {
        $blockedHosts = URLValidationService::getBlockedHosts();
        
        $this->assertContains('localhost', $blockedHosts);
        $this->assertContains('metadata.google.internal', $blockedHosts);
    }

    /**
     * Blocked IPs list
     */
    public function test_blocked_ips(): void
    {
        $blockedIPs = URLValidationService::getBlockedIPs();
        
        $this->assertContains('169.254.169.254', $blockedIPs);
    }

    /**
     * IPv6 localhost blocked
     */
    public function test_ipv6_localhost_blocked(): void
    {
        $result = $this->service->validate('http://[::1]');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * 0.0.0.0 blocked
     */
    public function test_zero_ip_blocked(): void
    {
        $result = $this->service->validate('http://0.0.0.0');
        
        $this->assertFalse($result['valid']);
    }

    /**
     * URL normalization
     */
    public function test_url_normalization(): void
    {
        $result = $this->service->validate('HTTPS://EXAMPLE.COM/Path');
        
        $this->assertTrue($result['valid']);
        $this->assertEquals('https://example.com/Path', $result['normalized_url']);
    }

    /**
     * Maximum URL length
     */
    public function test_max_url_length(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 3000);
        
        $result = $this->service->validate($longUrl);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('long', strtolower($result['error']));
    }

    /**
     * Private IP ranges accessible
     */
    public function test_private_ip_ranges(): void
    {
        $ranges = URLValidationService::getPrivateIPRanges();
        
        $this->assertIsArray($ranges);
        $this->assertNotEmpty($ranges);
        
        // Verify loopback range is included
        $loopbackFound = false;
        foreach ($ranges as $range) {
            if ($range['start'] === '127.0.0.0' && $range['end'] === '127.255.255.255') {
                $loopbackFound = true;
                break;
            }
        }
        $this->assertTrue($loopbackFound);
    }
}
