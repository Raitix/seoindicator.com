<?php

namespace Tests\Unit\Services\Tools;

use Tests\TestCase;
use App\Services\Tools\UrlValidator;
use Illuminate\Support\Facades\Validator;

/**
 * Unit tests for TASK-16-1: URL Validation Service
 *
 * Tests the UrlValidator service responsible for:
 * - URL format validation
 * - URL sanitization
 * - Protocol enforcement
 * - Length constraints
 *
 * @see \App\Services\Tools\UrlValidator
 */
class UrlValidatorTest extends TestCase
{
    protected UrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new UrlValidator();
    }

    // =========================================================================
    // URL VALIDATION TESTS (URL-VAL)
    // =========================================================================

    /**
     * URL-VAL-001: Valid HTTP URL
     */
    public function test_valid_http_url(): void
    {
        $this->assertTrue($this->validator->isValid('http://example.com'));
    }

    /**
     * URL-VAL-002: Valid HTTPS URL
     */
    public function test_valid_https_url(): void
    {
        $this->assertTrue($this->validator->isValid('https://example.com'));
    }

    /**
     * URL-VAL-003: Valid URL with path
     */
    public function test_valid_url_with_path(): void
    {
        $this->assertTrue($this->validator->isValid('https://example.com/page'));
    }

    /**
     * URL-VAL-004: Valid URL with query string
     */
    public function test_valid_url_with_query(): void
    {
        $this->assertTrue($this->validator->isValid('https://example.com?page=1'));
    }

    /**
     * URL-VAL-005: Invalid URL - no protocol
     */
    public function test_invalid_url_no_protocol(): void
    {
        $this->assertFalse($this->validator->isValid('example.com'));
    }

    /**
     * URL-VAL-006: Invalid URL - malformed protocol
     */
    public function test_invalid_url_malformed_protocol(): void
    {
        $this->assertFalse($this->validator->isValid('htp://example.com'));
    }

    /**
     * URL-VAL-007: Empty string
     */
    public function test_empty_string(): void
    {
        $this->assertFalse($this->validator->isValid(''));
    }

    /**
     * URL-VAL-008: Null value
     */
    public function test_null_value(): void
    {
        $this->assertFalse($this->validator->isValid(null));
    }

    /**
     * URL-VAL-009: IP address URL
     */
    public function test_ip_address_url(): void
    {
        $this->assertTrue($this->validator->isValid('http://192.168.1.1'));
    }

    /**
     * URL-VAL-010: URL with port
     */
    public function test_url_with_port(): void
    {
        $this->assertTrue($this->validator->isValid('https://example.com:8080'));
    }

    /**
     * URL-VAL-011: URL with fragment
     */
    public function test_url_with_fragment(): void
    {
        $this->assertTrue($this->validator->isValid('https://example.com#section'));
    }

    /**
     * URL-VAL-012: Very long URL (exceeds max length)
     */
    public function test_very_long_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2084);
        $this->assertFalse($this->validator->isValid($longUrl));
    }

    /**
     * URL-VAL-013: URL with unicode domain
     */
    public function test_url_with_unicode_domain(): void
    {
        $this->assertTrue($this->validator->isValid('https://例え.jp'));
    }

    /**
     * URL-VAL-014: URL with special characters in path
     */
    public function test_url_with_special_chars(): void
    {
        $this->assertTrue($this->validator->isValid('https://example.com/path?query=test&foo=bar'));
    }

    /**
     * URL-VAL-015: Data URI rejected
     */
    public function test_data_uri_rejected(): void
    {
        $this->assertFalse($this->validator->isValid('data:text/html,<h1>Test</h1>'));
    }

    // =========================================================================
    // URL SANITIZATION TESTS (URL-SAN)
    // =========================================================================

    /**
     * URL-SAN-001: Trim whitespace
     */
    public function test_trim_whitespace(): void
    {
        $result = $this->validator->sanitize('  https://example.com  ');
        $this->assertEquals('https://example.com', $result);
    }

    /**
     * URL-SAN-002: Lowercase protocol (optional normalization)
     */
    public function test_lowercase_protocol(): void
    {
        $result = $this->validator->sanitize('HTTPS://example.com');
        $this->assertEquals('https://example.com', $result);
    }

    /**
     * URL-SAN-003: Remove tracking parameters
     */
    public function test_remove_tracking_params(): void
    {
        $result = $this->validator->sanitize('https://example.com?utm_source=x');
        $this->assertEquals('https://example.com', $result);
    }

    /**
     * URL-SAN-004: Preserve hash fragment
     */
    public function test_preserve_hash_fragment(): void
    {
        $result = $this->validator->sanitize('https://example.com#section');
        $this->assertEquals('https://example.com#section', $result);
    }

    /**
     * URL-SAN-005: Normalize www prefix (configurable)
     */
    public function test_normalize_www_prefix(): void
    {
        $result = $this->validator->sanitize('https://www.example.com', ['removeWww' => true]);
        $this->assertEquals('https://example.com', $result);
    }

    /**
     * URL-SAN-006: Handle double slashes in path
     */
    public function test_handle_double_slashes(): void
    {
        $result = $this->validator->sanitize('https://example.com//path');
        $this->assertEquals('https://example.com/path', $result);
    }

    /**
     * URL-SAN-007: URL decode encoded characters
     */
    public function test_url_decode_encoded_chars(): void
    {
        $result = $this->validator->sanitize('https://example.com/%70ath');
        $this->assertEquals('https://example.com/path', $result);
    }

    /**
     * URL-SAN-008: Remove null bytes
     */
    public function test_remove_null_bytes(): void
    {
        $result = $this->validator->sanitize("https://example.com\x00path");
        $this->assertEquals('https://example.compath', $result);
    }

    /**
     * URL-SAN-009: Handle unicode domains (punycode conversion)
     */
    public function test_handle_unicode_domains(): void
    {
        $result = $this->validator->sanitize('https://例え.jp');
        // Should convert to punycode
        $this->assertStringContainsString('xn--', $result);
    }

    /**
     * URL-SAN-010: Preserve auth info
     */
    public function test_preserve_auth_info(): void
    {
        $result = $this->validator->sanitize('https://user:pass@example.com');
        $this->assertEquals('https://user:pass@example.com', $result);
    }

    // =========================================================================
    // PROTOCOL TESTS (PROTO)
    // =========================================================================

    /**
     * PROTO-001: Only HTTP/HTTPS allowed
     */
    public function test_only_http_https_allowed(): void
    {
        $this->assertFalse($this->validator->isValid('ftp://example.com'));
        $this->assertFalse($this->validator->isValid('file:///etc/passwd'));
        $this->assertFalse($this->validator->isValid('javascript:alert(1)'));
    }

    /**
     * PROTO-002: Prefer HTTPS
     */
    public function test_prefer_https(): void
    {
        $result = $this->validator->ensureHttps('http://example.com');
        $this->assertEquals('https://example.com', $result);
    }

    /**
     * PROTO-003: HTTPS already present
     */
    public function test_https_already_present(): void
    {
        $result = $this->validator->ensureHttps('https://example.com');
        $this->assertEquals('https://example.com', $result);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Handle array input
     */
    public function test_handle_array_input(): void
    {
        $this->assertFalse($this->validator->isValid(['url' => 'example.com']));
    }

    /**
     * EC-002: Handle object input
     */
    public function test_handle_object_input(): void
    {
        $this->assertFalse($this->validator->isValid(new \stdClass()));
    }

    /**
     * EC-003: Handle URL with spaces
     */
    public function test_handle_url_with_spaces(): void
    {
        $this->assertFalse($this->validator->isValid('https://example.com/has spaces'));
    }

    /**
     * EC-004: Handle URL encoded spaces
     */
    public function test_handle_url_encoded_spaces(): void
    {
        $this->assertTrue($this->validator->isValid('https://example.com/has%20spaces'));
    }

    /**
     * EC-005: Handle extremely short URL
     */
    public function test_handle_extremely_short_url(): void
    {
        $this->assertFalse($this->validator->isValid('https://a'));
    }

    /**
     * EC-006: Handle URL with only domain
     */
    public function test_handle_url_with_only_domain(): void
    {
        $this->assertTrue($this->validator->isValid('https://example.com'));
    }

    /**
     * EC-007: Handle URL with subdomain
     */
    public function test_handle_url_with_subdomain(): void
    {
        $this->assertTrue($this->validator->isValid('https://sub.example.com'));
    }

    /**
     * EC-008: Handle URL with multiple subdomains
     */
    public function test_handle_url_with_multiple_subdomains(): void
    {
        $this->assertTrue($this->validator->isValid('https://a.b.c.example.com'));
    }

    // =========================================================================
    // SECURITY TESTS (SEC)
    // =========================================================================

    /**
     * SEC-001: Prevent javascript: protocol injection
     */
    public function test_prevent_javascript_protocol(): void
    {
        $this->assertFalse($this->validator->isValid('javascript:alert(document.cookie)'));
    }

    /**
     * SEC-002: Prevent data: protocol injection
     */
    public function test_prevent_data_protocol(): void
    {
        $this->assertFalse($this->validator->isValid('data:text/html,<script>alert(1)</script>'));
    }

    /**
     * SEC-003: Prevent vbscript: protocol (IE)
     */
    public function test_prevent_vbscript_protocol(): void
    {
        $this->assertFalse($this->validator->isValid('vbscript:msgbox(1)'));
    }

    /**
     * SEC-004: Prevent file: protocol
     */
    public function test_prevent_file_protocol(): void
    {
        $this->assertFalse($this->validator->isValid('file:///etc/passwd'));
    }

    /**
     * SEC-005: Handle case variations in protocol
     */
    public function test_handle_case_variations_protocol(): void
    {
        $this->assertFalse($this->validator->isValid('JAVASCRIPT:alert(1)'));
        $this->assertFalse($this->validator->isValid('JavaScript:alert(1)'));
        $this->assertFalse($this->validator->isValid('DATA:text/html,test'));
    }

    /**
     * SEC-006: Prevent null byte injection
     */
    public function test_prevent_null_byte_injection(): void
    {
        $sanitized = $this->validator->sanitize("https://example.com\x00.evil.com");
        $this->assertStringNotContainsString("\x00", $sanitized);
    }

    /**
     * SEC-007: Prevent CRLF injection
     */
    public function test_prevent_crlf_injection(): void
    {
        $sanitized = $this->validator->sanitize("https://example.com\r\nSet-Cookie: evil");
        $this->assertStringNotContainsString("\r", $sanitized);
        $this->assertStringNotContainsString("\n", $sanitized);
    }
}
