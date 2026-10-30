<?php

namespace Tests\Unit\Rules;

use Tests\TestCase;
use App\Rules\ValidCrawlUrl;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for ValidCrawlUrl validation rule.
 *
 * Tests SSRF protection including:
 * - Protocol validation (only http/https allowed)
 * - Localhost blocking
 * - Private IP blocking
 * - Cloud metadata endpoint blocking
 * - Domain blocking
 * - Alternative IP notations
 *
 * @see TASK-22-12
 * @see \App\Rules\ValidCrawlUrl
 */
class ValidCrawlUrlTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a validator with the ValidCrawlUrl rule.
     */
    protected function validateUrl(string $url): array
    {
        $rule = new ValidCrawlUrl();
        $fails = false;
        $message = null;

        $failCallback = function ($errorMessage) use (&$fails, &$message) {
            $fails = true;
            $message = $errorMessage;
        };

        $rule->validate('url', $url, $failCallback);

        return [
            'passes' => !$fails,
            'message' => $message,
        ];
    }

    // =========================================================================
    // Valid URLs (VURL-VALID-*)
    // =========================================================================

    /** @test */
    public function vurl_valid_01_standard_https_url(): void
    {
        $result = $this->validateUrl('https://example.com');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_02_standard_http_url(): void
    {
        $result = $this->validateUrl('http://example.com');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_03_url_with_path(): void
    {
        $result = $this->validateUrl('https://example.com/page');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_04_url_with_query(): void
    {
        $result = $this->validateUrl('https://example.com?query=1');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_05_url_with_fragment(): void
    {
        $result = $this->validateUrl('https://example.com#section');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_06_url_with_port(): void
    {
        $result = $this->validateUrl('https://example.com:8080');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_07_subdomain(): void
    {
        $result = $this->validateUrl('https://sub.example.com');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_08_idn_domain(): void
    {
        $result = $this->validateUrl('https://münchen.de');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_09_ipv4_public_host(): void
    {
        $result = $this->validateUrl('https://8.8.8.8');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_10_ipv6_public_host(): void
    {
        $result = $this->validateUrl('https://[2001:4860:4860::8888]');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_11_deep_path(): void
    {
        $result = $this->validateUrl('https://example.com/a/b/c/d/e');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_valid_12_long_tld(): void
    {
        $result = $this->validateUrl('https://example.international');
        
        $this->assertTrue($result['passes']);
    }

    // =========================================================================
    // Blocked Protocols (VURL-PROTO-*)
    // =========================================================================

    /** @test */
    public function vurl_proto_01_file_protocol_blocked(): void
    {
        $result = $this->validateUrl('file:///etc/passwd');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('Protocol', $result['message']);
    }

    /** @test */
    public function vurl_proto_02_ftp_protocol_blocked(): void
    {
        $result = $this->validateUrl('ftp://server/file');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('Protocol', $result['message']);
    }

    /** @test */
    public function vurl_proto_03_data_protocol_blocked(): void
    {
        $result = $this->validateUrl('data:text/html,<script>alert(1)</script>');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('Protocol', $result['message']);
    }

    /** @test */
    public function vurl_proto_04_javascript_protocol_blocked(): void
    {
        $result = $this->validateUrl('javascript:alert(1)');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('Protocol', $result['message']);
    }

    /** @test */
    public function vurl_proto_05_mailto_protocol_blocked(): void
    {
        $result = $this->validateUrl('mailto:test@test.com');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('Protocol', $result['message']);
    }

    /** @test */
    public function vurl_proto_06_gopher_protocol_blocked(): void
    {
        $result = $this->validateUrl('gopher://server');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('Protocol', $result['message']);
    }

    /** @test */
    public function vurl_proto_07_ssh_protocol_blocked(): void
    {
        $result = $this->validateUrl('ssh://server');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('Protocol', $result['message']);
    }

    /** @test */
    public function vurl_proto_08_custom_protocol_blocked(): void
    {
        $result = $this->validateUrl('custom://resource');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('Protocol', $result['message']);
    }

    // =========================================================================
    // Localhost Blocking (VURL-LOCAL-*)
    // =========================================================================

    /** @test */
    public function vurl_local_01_localhost_hostname_blocked(): void
    {
        $result = $this->validateUrl('http://localhost');
        
        $this->assertFalse($result['passes']);
        $this->assertStringContainsString('allowed', strtolower($result['message']));
    }

    /** @test */
    public function vurl_local_02_localhost_with_port_blocked(): void
    {
        $result = $this->validateUrl('http://localhost:8080');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_local_03_localhost_with_path_blocked(): void
    {
        $result = $this->validateUrl('http://localhost/page');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_local_04_127_0_0_1_blocked(): void
    {
        $result = $this->validateUrl('http://127.0.0.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_local_05_127_0_0_0_8_range_blocked(): void
    {
        $result = $this->validateUrl('http://127.255.255.255');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_local_06_ipv6_localhost_blocked(): void
    {
        $result = $this->validateUrl('http://[::1]');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_local_07_ipv6_full_localhost_blocked(): void
    {
        $result = $this->validateUrl('http://[0:0:0:0:0:0:0:1]');
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Private IP Blocking (VURL-PRIVATE-*)
    // =========================================================================

    /** @test */
    public function vurl_private_01_10_0_0_0_8_start_blocked(): void
    {
        $result = $this->validateUrl('http://10.0.0.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_private_02_10_0_0_0_8_end_blocked(): void
    {
        $result = $this->validateUrl('http://10.255.255.255');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_private_03_172_16_0_0_12_start_blocked(): void
    {
        $result = $this->validateUrl('http://172.16.0.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_private_04_172_31_255_255_blocked(): void
    {
        $result = $this->validateUrl('http://172.31.255.255');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_private_05_172_15_x_x_passes(): void
    {
        // 172.15.x.x is NOT in the private range (172.16.0.0/12)
        // But we may block all 172.x for safety or validate correctly
        // This test documents expected behavior
        $result = $this->validateUrl('http://172.15.0.1');
        
        // 172.15.0.1 should pass as it's not in private range
        $this->assertTrue($result['passes'], '172.15.0.1 should be valid (not in private range)');
    }

    /** @test */
    public function vurl_private_06_172_32_x_x_passes(): void
    {
        $result = $this->validateUrl('http://172.32.0.1');
        
        // 172.32.0.1 should pass as it's not in private range
        $this->assertTrue($result['passes'], '172.32.0.1 should be valid (not in private range)');
    }

    /** @test */
    public function vurl_private_07_192_168_0_0_16_start_blocked(): void
    {
        $result = $this->validateUrl('http://192.168.0.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_private_08_192_168_255_255_blocked(): void
    {
        $result = $this->validateUrl('http://192.168.255.255');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_private_09_ipv6_unique_local_blocked(): void
    {
        $result = $this->validateUrl('http://[fc00::1]');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_private_10_ipv6_fd00_8_blocked(): void
    {
        $result = $this->validateUrl('http://[fd00::1]');
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Cloud Metadata Blocking (VURL-META-*)
    // =========================================================================

    /** @test */
    public function vurl_meta_01_aws_metadata_blocked(): void
    {
        $result = $this->validateUrl('http://169.254.169.254');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_meta_02_aws_metadata_latest_blocked(): void
    {
        $result = $this->validateUrl('http://169.254.169.254/latest/meta-data/');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_meta_03_gcp_metadata_blocked(): void
    {
        $result = $this->validateUrl('http://169.254.169.254/computeMetadata/v1/');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_meta_04_azure_metadata_blocked(): void
    {
        $result = $this->validateUrl('http://169.254.169.254/metadata/instance');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_meta_05_link_local_range_blocked(): void
    {
        $result = $this->validateUrl('http://169.254.1.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_meta_06_ipv6_link_local_blocked(): void
    {
        $result = $this->validateUrl('http://[fe80::1]');
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Special IP Blocking (VURL-SPECIAL-*)
    // =========================================================================

    /** @test */
    public function vurl_special_01_0_0_0_0_blocked(): void
    {
        $result = $this->validateUrl('http://0.0.0.0');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_special_02_0_0_0_0_8_range_blocked(): void
    {
        $result = $this->validateUrl('http://0.0.0.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_special_03_multicast_blocked(): void
    {
        $result = $this->validateUrl('http://224.0.0.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_special_04_broadcast_blocked(): void
    {
        $result = $this->validateUrl('http://255.255.255.255');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_special_05_reserved_blocked(): void
    {
        $result = $this->validateUrl('http://240.0.0.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_special_06_ipv6_unspecified_blocked(): void
    {
        $result = $this->validateUrl('http://[::]');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_special_07_ipv6_multicast_blocked(): void
    {
        $result = $this->validateUrl('http://[ff00::1]');
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Domain Blocking (VURL-DOMAIN-*)
    // =========================================================================

    /** @test */
    public function vurl_domain_01_internal_tld_blocked(): void
    {
        $result = $this->validateUrl('http://server.internal');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_domain_02_internal_subdomain_blocked(): void
    {
        $result = $this->validateUrl('http://internal.company.com');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_domain_03_local_tld_blocked(): void
    {
        $result = $this->validateUrl('http://server.local');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_domain_04_localhost_tld_blocked(): void
    {
        $result = $this->validateUrl('http://app.localhost');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_domain_05_localhost_in_path_allowed(): void
    {
        // localhost in path should be allowed
        $result = $this->validateUrl('http://example.com/localhost');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_domain_06_case_insensitive(): void
    {
        $result = $this->validateUrl('http://INTERNAL.COMPANY.COM');
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Alternative Notations (VURL-ALT-*)
    // =========================================================================

    /** @test */
    public function vurl_alt_01_decimal_ip_localhost_blocked(): void
    {
        // 2130706433 = 127.0.0.1 in decimal
        $result = $this->validateUrl('http://2130706433');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_alt_02_hex_ip_localhost_blocked(): void
    {
        // 0x7f000001 = 127.0.0.1 in hex
        $result = $this->validateUrl('http://0x7f000001');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_alt_03_octal_ip_blocked(): void
    {
        // 0177.0.0.1 = 127.0.0.1 in octal notation
        $result = $this->validateUrl('http://0177.0.0.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_alt_04_mixed_notation_blocked(): void
    {
        $result = $this->validateUrl('http://0x7f.0.0.1');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_alt_05_ipv4_mapped_ipv6_blocked(): void
    {
        $result = $this->validateUrl('http://[::ffff:127.0.0.1]');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_alt_06_ipv4_compatible_ipv6_blocked(): void
    {
        $result = $this->validateUrl('http://[::127.0.0.1]');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_alt_07_url_encoded_localhost_blocked(): void
    {
        // %6c%6f%63%61%6c%68%6f%73%74 = localhost URL-encoded
        $result = $this->validateUrl('http://%6c%6f%63%61%6c%68%6f%73%74');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_alt_08_decimal_private_ip_blocked(): void
    {
        // 3232235521 = 192.168.0.1 in decimal
        $result = $this->validateUrl('http://3232235521');
        
        $this->assertFalse($result['passes']);
    }

    // =========================================================================
    // Edge Cases (VURL-EDGE-*)
    // =========================================================================

    /** @test */
    public function vurl_edge_01_empty_url_fails(): void
    {
        $result = $this->validateUrl('');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_edge_02_null_url_fails(): void
    {
        $rule = new ValidCrawlUrl();
        $fails = false;

        $failCallback = function ($errorMessage) use (&$fails) {
            $fails = true;
        };

        $rule->validate('url', null, $failCallback);

        $this->assertTrue($fails);
    }

    /** @test */
    public function vurl_edge_03_malformed_url_fails(): void
    {
        $result = $this->validateUrl('not a url');
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_edge_04_url_with_credentials_passes(): void
    {
        // URL with credentials should be valid (credentials handled elsewhere)
        $result = $this->validateUrl('http://user:pass@example.com');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_edge_05_very_long_url_fails(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2070);
        
        $result = $this->validateUrl($longUrl);
        
        $this->assertFalse($result['passes']);
    }

    /** @test */
    public function vurl_edge_06_unicode_hostname_passes(): void
    {
        $result = $this->validateUrl('http://münchen.de');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_edge_07_punycode_hostname_passes(): void
    {
        $result = $this->validateUrl('http://xn--mnchen-3ya.de');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_edge_08_trailing_slash_passes(): void
    {
        $result = $this->validateUrl('http://example.com/');
        
        $this->assertTrue($result['passes']);
    }

    /** @test */
    public function vurl_edge_09_no_trailing_slash_passes(): void
    {
        $result = $this->validateUrl('http://example.com');
        
        $this->assertTrue($result['passes']);
    }
}
