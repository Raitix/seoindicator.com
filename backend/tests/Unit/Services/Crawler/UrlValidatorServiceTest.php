<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\UrlValidatorService;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Unit tests for UrlValidatorService - SSRF Protection
 * 
 * Task: TASK-22-7
 * Risk: HIGH (Security Critical)
 * 
 * @covers \App\Services\Crawler\UrlValidatorService
 */
class UrlValidatorServiceTest extends TestCase
{
    private UrlValidatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UrlValidatorService::class);
    }

    // =========================================================================
    // Protocol Validation Tests (PV)
    // =========================================================================

    /** @test */
    public function pv_001_valid_http_url_passes(): void
    {
        $result = $this->service->validate('http://example.com');

        $this->assertTrue($result['valid']);
        $this->assertEquals('http://example.com', $result['url']);
    }

    /** @test */
    public function pv_002_valid_https_url_passes(): void
    {
        $result = $this->service->validate('https://example.com');

        $this->assertTrue($result['valid']);
        $this->assertEquals('https://example.com', $result['url']);
    }

    /** @test */
    public function pv_003_blocks_file_protocol(): void
    {
        $result = $this->service->validate('file:///etc/passwd');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_004_blocks_ftp_protocol(): void
    {
        $result = $this->service->validate('ftp://example.com/file');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_005_blocks_data_protocol(): void
    {
        $result = $this->service->validate('data:text/html,<script>alert(1)</script>');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_006_blocks_javascript_protocol(): void
    {
        $result = $this->service->validate('javascript:alert(1)');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_007_blocks_mailto_protocol(): void
    {
        $result = $this->service->validate('mailto:test@example.com');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_008_blocks_gopher_protocol(): void
    {
        $result = $this->service->validate('gopher://example.com');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_009_blocks_custom_protocol(): void
    {
        $result = $this->service->validate('custom://resource');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_010_blocks_no_protocol(): void
    {
        $result = $this->service->validate('example.com');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_011_case_insensitive_protocol_block(): void
    {
        $result = $this->service->validate('FILE:///etc/passwd');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_012_mixed_case_protocol_block(): void
    {
        $result = $this->service->validate('JaVaScRiPt:alert(1)');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function pv_013_protocol_with_leading_whitespace(): void
    {
        $result = $this->service->validate(' file:///etc/passwd');

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function pv_014_null_byte_in_protocol(): void
    {
        $result = $this->service->validate("file\x00://example.com");

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // URL Format Validation Tests (UFV)
    // =========================================================================

    /** @test */
    public function ufv_001_valid_url_with_path(): void
    {
        $result = $this->service->validate('https://example.com/path/to/page');

        $this->assertTrue($result['valid']);
        $this->assertEquals('example.com', $result['host']);
    }

    /** @test */
    public function ufv_002_valid_url_with_query(): void
    {
        $result = $this->service->validate('https://example.com?page=1&sort=asc');

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function ufv_003_valid_url_with_fragment(): void
    {
        $result = $this->service->validate('https://example.com#section');

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function ufv_004_valid_url_with_port(): void
    {
        $result = $this->service->validate('https://example.com:8443/path');

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function ufv_005_invalid_url_no_host(): void
    {
        $result = $this->service->validate('https:///path');

        $this->assertFalse($result['valid']);
        $this->assertEquals('URL format invalid', $result['error']);
    }

    /** @test */
    public function ufv_006_invalid_url_malformed(): void
    {
        $result = $this->service->validate('https://[invalid');

        $this->assertFalse($result['valid']);
        $this->assertEquals('URL format invalid', $result['error']);
    }

    /** @test */
    public function ufv_007_empty_string(): void
    {
        $result = $this->service->validate('');

        $this->assertFalse($result['valid']);
        $this->assertEquals('URL format invalid', $result['error']);
    }

    /** @test */
    public function ufv_008_null_value(): void
    {
        $result = $this->service->validate(null);

        $this->assertFalse($result['valid']);
        $this->assertEquals('URL format invalid', $result['error']);
    }

    /** @test */
    public function ufv_015_very_long_url_rejected(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2100);

        $result = $this->service->validate($longUrl);

        $this->assertFalse($result['valid']);
        $this->assertEquals('URL format invalid', $result['error']);
    }

    /** @test */
    public function ufv_017_url_normalization_lowercase_host(): void
    {
        $result = $this->service->validate('https://Example.COM/Path');

        $this->assertTrue($result['valid']);
        $this->assertEquals('example.com', $result['host']);
    }

    /** @test */
    public function ufv_018_url_normalization_remove_default_port(): void
    {
        $result = $this->service->validate('https://example.com:443/');

        $this->assertTrue($result['valid']);
        $this->assertEquals('https://example.com/', $result['url']);
    }

    // =========================================================================
    // IP Address Blocking Tests - IPv4 (IPV4)
    // =========================================================================

    /** @test */
    public function ipv4_001_blocks_localhost_127_0_0_1(): void
    {
        $result = $this->service->validate('http://127.0.0.1/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertStringContainsString('localhost', $result['blockReason']);
    }

    /** @test */
    public function ipv4_002_blocks_localhost_127_0_0_2(): void
    {
        $result = $this->service->validate('http://127.0.0.2/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_003_blocks_127_0_0_0_range_start(): void
    {
        $result = $this->service->validate('http://127.0.0.0/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_004_blocks_127_255_255_255_range_end(): void
    {
        $result = $this->service->validate('http://127.255.255.255/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_005_blocks_private_10_0_0_1(): void
    {
        $result = $this->service->validate('http://10.0.0.1/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertStringContainsString('private network', $result['blockReason']);
    }

    /** @test */
    public function ipv4_006_blocks_private_10_255_255_255(): void
    {
        $result = $this->service->validate('http://10.255.255.255/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_007_blocks_private_172_16_0_1(): void
    {
        $result = $this->service->validate('http://172.16.0.1/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_008_blocks_private_172_31_255_255(): void
    {
        $result = $this->service->validate('http://172.31.255.255/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_009_blocks_private_192_168_0_1(): void
    {
        $result = $this->service->validate('http://192.168.0.1/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_010_blocks_private_192_168_255_255(): void
    {
        $result = $this->service->validate('http://192.168.255.255/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_011_blocks_aws_gcp_metadata(): void
    {
        $result = $this->service->validate('http://169.254.169.254/latest/meta-data/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertStringContainsString('metadata', $result['blockReason']);
    }

    /** @test */
    public function ipv4_012_blocks_link_local_169_254_0_1(): void
    {
        $result = $this->service->validate('http://169.254.0.1/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_014_blocks_current_network_0_0_0_0(): void
    {
        $result = $this->service->validate('http://0.0.0.0/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_015_blocks_0_0_0_0_range(): void
    {
        $result = $this->service->validate('http://0.0.0.5/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_016_blocks_multicast_224_0_0_1(): void
    {
        $result = $this->service->validate('http://224.0.0.1/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertStringContainsString('multicast', $result['blockReason']);
    }

    /** @test */
    public function ipv4_018_blocks_reserved_240_0_0_1(): void
    {
        $result = $this->service->validate('http://240.0.0.1/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertStringContainsString('reserved', $result['blockReason']);
    }

    /** @test */
    public function ipv4_020_blocks_broadcast_255_255_255_255(): void
    {
        $result = $this->service->validate('http://255.255.255.255/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_021_allows_valid_public_ip(): void
    {
        // 93.184.216.34 is example.com's IP
        $result = $this->service->validate('http://93.184.216.34/');

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function ipv4_023_blocks_decimal_ip_notation(): void
    {
        // 2130706433 = 127.0.0.1 in decimal
        $result = $this->service->validate('http://2130706433/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_024_blocks_hex_ip_notation(): void
    {
        // 0x7f000001 = 127.0.0.1 in hex
        $result = $this->service->validate('http://0x7f000001/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv4_025_blocks_octal_ip_notation(): void
    {
        // 0177.0.0.1 = 127.0.0.1 in octal
        $result = $this->service->validate('http://0177.0.0.1/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    // =========================================================================
    // IP Address Blocking Tests - IPv6 (IPV6)
    // =========================================================================

    /** @test */
    public function ipv6_001_blocks_ipv6_localhost(): void
    {
        $result = $this->service->validate('http://[::1]/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertStringContainsString('localhost', $result['blockReason']);
    }

    /** @test */
    public function ipv6_002_blocks_ipv6_unspecified(): void
    {
        $result = $this->service->validate('http://[::]/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv6_003_blocks_ipv6_unique_local_fc00(): void
    {
        $result = $this->service->validate('http://[fc00::1]/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv6_005_blocks_ipv6_link_local_fe80(): void
    {
        $result = $this->service->validate('http://[fe80::1]/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertStringContainsString('link-local', $result['blockReason']);
    }

    /** @test */
    public function ipv6_007_blocks_ipv6_multicast_ff00(): void
    {
        $result = $this->service->validate('http://[ff00::1]/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv6_009_blocks_ipv4_mapped_ipv6_localhost(): void
    {
        $result = $this->service->validate('http://[::ffff:127.0.0.1]/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv6_010_blocks_ipv4_mapped_ipv6_private(): void
    {
        $result = $this->service->validate('http://[::ffff:192.168.1.1]/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function ipv6_013_allows_valid_ipv6_public(): void
    {
        // Google DNS IPv6
        $result = $this->service->validate('http://[2001:4860:4860::8888]/');

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // Domain Blocking Tests (DB)
    // =========================================================================

    /** @test */
    public function db_001_blocks_internal_domain(): void
    {
        $result = $this->service->validate('http://app.internal/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
        $this->assertEquals('Domain blocked', $result['blockReason']);
    }

    /** @test */
    public function db_002_blocks_internal_subdomain(): void
    {
        $result = $this->service->validate('http://api.service.internal/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function db_003_blocks_local_domain(): void
    {
        $result = $this->service->validate('http://test.local/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function db_004_blocks_localhost_domain(): void
    {
        $result = $this->service->validate('http://api.localhost/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function db_008_case_insensitive_domain_block(): void
    {
        $result = $this->service->validate('http://Internal.Company.COM/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function db_010_allows_valid_external_domain(): void
    {
        $result = $this->service->validate('https://google.com/');

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // Security Edge Cases Tests (SEC)
    // =========================================================================

    /** @test */
    public function sec_001_ssrf_via_localhost_hostname(): void
    {
        $result = $this->service->validate('http://localhost/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function sec_005_ssrf_via_cloud_metadata_aws(): void
    {
        $result = $this->service->validate('http://169.254.169.254/latest/meta-data/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function sec_007_ssrf_via_gcp_metadata_hostname(): void
    {
        $result = $this->service->validate('http://metadata.google.internal/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function sec_016_ssrf_via_url_encoding(): void
    {
        // %31%32%37 = 127
        $result = $this->service->validate('http://%31%32%37%2e%30%2e%30%2e%31/');

        $this->assertFalse($result['valid']);
        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function sec_020_null_byte_injection(): void
    {
        $result = $this->service->validate("http://example.com\x00@127.0.0.1/");

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // ValidationResult Structure Tests (VRS)
    // =========================================================================

    /** @test */
    public function vrs_001_valid_response_structure(): void
    {
        $result = $this->service->validate('https://example.com');

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('originalUrl', $result);
        $this->assertArrayHasKey('host', $result);
        $this->assertArrayHasKey('ip', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('blocked', $result);
        $this->assertArrayHasKey('blockReason', $result);
    }

    /** @test */
    public function vrs_002_valid_response_has_url(): void
    {
        $result = $this->service->validate('https://example.com');

        $this->assertNotNull($result['url']);
    }

    /** @test */
    public function vrs_003_valid_response_has_original_url(): void
    {
        $result = $this->service->validate('https://Example.COM/');

        $this->assertEquals('https://Example.COM/', $result['originalUrl']);
    }

    /** @test */
    public function vrs_004_valid_response_has_host(): void
    {
        $result = $this->service->validate('https://example.com/path');

        $this->assertEquals('example.com', $result['host']);
    }

    /** @test */
    public function vrs_006_invalid_response_has_error(): void
    {
        $result = $this->service->validate('invalid-url');

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function vrs_008_blocked_response_has_blocked_flag(): void
    {
        $result = $this->service->validate('http://127.0.0.1/');

        $this->assertTrue($result['blocked']);
    }

    /** @test */
    public function vrs_009_blocked_response_has_block_reason(): void
    {
        $result = $this->service->validate('http://127.0.0.1/');

        $this->assertNotNull($result['blockReason']);
    }

    // =========================================================================
    // Error Message Tests (ERR)
    // =========================================================================

    /** @test */
    public function err_001_protocol_error_message(): void
    {
        $result = $this->service->validate('file:///etc/passwd');

        $this->assertEquals('Protocol not allowed', $result['error']);
    }

    /** @test */
    public function err_002_format_error_message(): void
    {
        $result = $this->service->validate('not a url');

        $this->assertEquals('URL format invalid', $result['error']);
    }

    /** @test */
    public function err_003_private_ip_error_message(): void
    {
        $result = $this->service->validate('http://192.168.1.1/');

        $this->assertStringContainsString('private network', $result['blockReason']);
    }

    /** @test */
    public function err_004_localhost_error_message(): void
    {
        $result = $this->service->validate('http://127.0.0.1/');

        $this->assertStringContainsString('localhost', $result['blockReason']);
    }

    /** @test */
    public function err_005_metadata_endpoint_error_message(): void
    {
        $result = $this->service->validate('http://169.254.169.254/');

        $this->assertStringContainsString('metadata', $result['blockReason']);
    }

    /** @test */
    public function err_006_domain_blocked_error_message(): void
    {
        $result = $this->service->validate('http://test.internal/');

        $this->assertEquals('Domain blocked', $result['blockReason']);
    }

    // =========================================================================
    // Configuration Tests (CFG)
    // =========================================================================

    /** @test */
    public function cfg_001_default_allowed_protocols(): void
    {
        // HTTP should be allowed
        $httpResult = $this->service->validate('http://example.com');
        $this->assertTrue($httpResult['valid']);

        // HTTPS should be allowed
        $httpsResult = $this->service->validate('https://example.com');
        $this->assertTrue($httpsResult['valid']);
    }

    /** @test */
    public function cfg_005_default_max_redirects(): void
    {
        $config = config('crawler.max_redirects', 5);

        $this->assertEquals(5, $config);
    }

    /** @test */
    public function cfg_006_default_dns_cache_ttl(): void
    {
        $config = config('crawler.dns_cache_ttl', 60);

        $this->assertEquals(60, $config);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
