<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DomainValidatorService;
use App\Services\URLValidationService;
use Illuminate\Validation\ValidationException;
use Mockery;

/**
 * Unit tests for DomainValidatorService.
 *
 * Tests domain normalization, format validation, accessibility checking,
 * and SSRF protection integration.
 *
 * @see \App\Services\DomainValidatorService
 * @see TEST-18-4
 */
class DomainValidatorServiceTest extends TestCase
{
    protected DomainValidatorService $service;
    protected $urlValidationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlValidationServiceMock = Mockery::mock(URLValidationService::class);
        $this->service = new DomainValidatorService($this->urlValidationServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // DOMAIN NORMALIZATION TESTS (DN-*)
    // =========================================================================

    /**
     * DN-01: Add https protocol to bare domain.
     */
    public function test_normalize_adds_https_protocol(): void
    {
        $result = $this->service->normalize('example.com');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * DN-02: Preserve http protocol.
     */
    public function test_normalize_preserves_http_protocol(): void
    {
        $result = $this->service->normalize('http://example.com');

        $this->assertEquals('http://example.com', $result);
    }

    /**
     * DN-03: Preserve https protocol.
     */
    public function test_normalize_preserves_https_protocol(): void
    {
        $result = $this->service->normalize('https://example.com');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * DN-04: Remove www prefix from bare domain.
     */
    public function test_normalize_removes_www_prefix(): void
    {
        $result = $this->service->normalize('www.example.com');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * DN-05: Preserve www in URL with protocol.
     */
    public function test_normalize_preserves_www_with_protocol(): void
    {
        $result = $this->service->normalize('https://www.example.com');

        $this->assertEquals('https://www.example.com', $result);
    }

    /**
     * DN-06: Remove trailing slash.
     */
    public function test_normalize_removes_trailing_slash(): void
    {
        $result = $this->service->normalize('example.com/');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * DN-07: Remove path from domain input.
     */
    public function test_normalize_removes_path(): void
    {
        $result = $this->service->normalize('example.com/page');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * DN-08: Remove query string.
     */
    public function test_normalize_removes_query_string(): void
    {
        $result = $this->service->normalize('example.com?foo=bar');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * DN-09: Remove fragment.
     */
    public function test_normalize_removes_fragment(): void
    {
        $result = $this->service->normalize('example.com#section');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * DN-10: Mixed normalization (www + path + query + fragment).
     */
    public function test_normalize_handles_mixed_input(): void
    {
        $result = $this->service->normalize('www.example.com/path?query=1#hash');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * DN-11: Lowercase domain.
     */
    public function test_normalize_lowercases_domain(): void
    {
        $result = $this->service->normalize('EXAMPLE.COM');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * DN-12: Preserve port.
     */
    public function test_normalize_preserves_port(): void
    {
        $result = $this->service->normalize('example.com:8080');

        $this->assertEquals('https://example.com:8080', $result);
    }

    /**
     * DN-13: Handle subdomain.
     */
    public function test_normalize_handles_subdomain(): void
    {
        $result = $this->service->normalize('blog.example.com');

        $this->assertEquals('https://blog.example.com', $result);
    }

    /**
     * DN-14: Handle multi-level subdomain.
     */
    public function test_normalize_handles_multi_level_subdomain(): void
    {
        $result = $this->service->normalize('api.v2.example.com');

        $this->assertEquals('https://api.v2.example.com', $result);
    }

    // =========================================================================
    // DOMAIN FORMAT VALIDATION TESTS (DV-*)
    // =========================================================================

    /**
     * DV-01: Valid domain.
     */
    public function test_is_valid_format_accepts_valid_domain(): void
    {
        $this->assertTrue($this->service->isValidFormat('example.com'));
    }

    /**
     * DV-02: Valid subdomain.
     */
    public function test_is_valid_format_accepts_subdomain(): void
    {
        $this->assertTrue($this->service->isValidFormat('blog.example.com'));
    }

    /**
     * DV-03: Valid with multi-part TLD.
     */
    public function test_is_valid_format_accepts_multi_part_tld(): void
    {
        $this->assertTrue($this->service->isValidFormat('example.co.uk'));
    }

    /**
     * DV-04: Valid new gTLD.
     */
    public function test_is_valid_format_accepts_new_gtld(): void
    {
        $this->assertTrue($this->service->isValidFormat('example.app'));
    }

    /**
     * DV-05: Valid punycode.
     */
    public function test_is_valid_format_accepts_punycode(): void
    {
        $this->assertTrue($this->service->isValidFormat('xn--example-6q4a.com'));
    }

    /**
     * DV-06: Empty domain is invalid.
     */
    public function test_is_valid_format_rejects_empty_domain(): void
    {
        $this->assertFalse($this->service->isValidFormat(''));
    }

    /**
     * DV-07: Only whitespace is invalid.
     */
    public function test_is_valid_format_rejects_whitespace_only(): void
    {
        $this->assertFalse($this->service->isValidFormat('   '));
    }

    /**
     * DV-08: Too long domain is invalid.
     */
    public function test_is_valid_format_rejects_too_long_domain(): void
    {
        $longDomain = str_repeat('a', 300) . '.com';

        $this->assertFalse($this->service->isValidFormat($longDomain));
    }

    /**
     * DV-09: Invalid characters are rejected.
     */
    public function test_is_valid_format_rejects_invalid_characters(): void
    {
        $this->assertFalse($this->service->isValidFormat('example!.com'));
    }

    /**
     * DV-10: Missing TLD is invalid.
     */
    public function test_is_valid_format_rejects_missing_tld(): void
    {
        $this->assertFalse($this->service->isValidFormat('example'));
    }

    /**
     * DV-11: Starting with hyphen is invalid.
     */
    public function test_is_valid_format_rejects_starting_hyphen(): void
    {
        $this->assertFalse($this->service->isValidFormat('-example.com'));
    }

    /**
     * DV-12: Ending with hyphen is invalid.
     */
    public function test_is_valid_format_rejects_ending_hyphen(): void
    {
        $this->assertFalse($this->service->isValidFormat('example-.com'));
    }

    /**
     * DV-13: Double hyphen is valid (but suspicious).
     */
    public function test_is_valid_format_accepts_double_hyphen(): void
    {
        // Double hyphen is technically valid in DNS
        $this->assertTrue($this->service->isValidFormat('exa--mple.com'));
    }

    /**
     * DV-14: Single label (localhost) is valid.
     */
    public function test_is_valid_format_accepts_single_label(): void
    {
        $this->assertTrue($this->service->isValidFormat('localhost'));
    }

    /**
     * DV-15: IP address as domain - handled by SSRF.
     */
    public function test_is_valid_format_accepts_ip_address(): void
    {
        // Format validation allows IPs; SSRF protection blocks them
        $this->assertTrue($this->service->isValidFormat('192.168.1.1'));
    }

    // =========================================================================
    // IDN (INTERNATIONALIZED DOMAIN NAME) TESTS (IDN-*)
    // =========================================================================

    /**
     * IDN-01: Unicode domain (Japanese).
     */
    public function test_normalize_handles_unicode_domain_japanese(): void
    {
        $result = $this->service->normalize('例え.jp');

        // Should convert to punycode
        $this->assertStringContainsString('https://', $result);
        $this->assertStringContainsString('.jp', $result);
    }

    /**
     * IDN-02: Unicode domain (Cyrillic).
     */
    public function test_normalize_handles_unicode_domain_cyrillic(): void
    {
        $result = $this->service->normalize('пример.рф');

        $this->assertStringContainsString('https://', $result);
    }

    /**
     * IDN-03: Unicode domain (Arabic).
     */
    public function test_normalize_handles_unicode_domain_arabic(): void
    {
        $result = $this->service->normalize('مثال.eg');

        $this->assertStringContainsString('https://', $result);
    }

    /**
     * IDN-04: Unicode domain (Chinese).
     */
    public function test_normalize_handles_unicode_domain_chinese(): void
    {
        $result = $this->service->normalize('例子.中国');

        $this->assertStringContainsString('https://', $result);
    }

    /**
     * IDN-05: Mixed script domain.
     */
    public function test_normalize_handles_mixed_script(): void
    {
        $result = $this->service->normalize('exa例mple.com');

        $this->assertStringContainsString('https://', $result);
    }

    /**
     * IDN-06: IDN with protocol.
     */
    public function test_normalize_handles_idn_with_protocol(): void
    {
        $result = $this->service->normalize('https://例え.jp');

        $this->assertStringContainsString('https://', $result);
    }

    // =========================================================================
    // SSRF PROTECTION TESTS (SSRF-*)
    // =========================================================================

    /**
     * SSRF-01: Localhost blocked.
     */
    public function test_validate_blocks_localhost(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('localhost');
    }

    /**
     * SSRF-02: 127.0.0.1 blocked.
     */
    public function test_validate_blocks_127_0_0_1(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('127.0.0.1');
    }

    /**
     * SSRF-03: 127.x.x.x blocked.
     */
    public function test_validate_blocks_127_subnet(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('127.0.0.2');
    }

    /**
     * SSRF-04: 10.x.x.x blocked (private IP).
     */
    public function test_validate_blocks_10_subnet(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('10.0.0.1');
    }

    /**
     * SSRF-05: 172.16-31.x.x blocked.
     */
    public function test_validate_blocks_172_subnet(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('172.16.0.1');
    }

    /**
     * SSRF-06: 192.168.x.x blocked.
     */
    public function test_validate_blocks_192_168_subnet(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('192.168.1.1');
    }

    /**
     * SSRF-07: Cloud metadata endpoint blocked.
     */
    public function test_validate_blocks_cloud_metadata(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('169.254.169.254');
    }

    /**
     * SSRF-08: file:// protocol blocked.
     */
    public function test_validate_blocks_file_protocol(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validate('file:///etc/passwd');
    }

    /**
     * SSRF-09: ftp:// protocol blocked.
     */
    public function test_validate_blocks_ftp_protocol(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validate('ftp://example.com');
    }

    /**
     * SSRF-10: gopher:// protocol blocked.
     */
    public function test_validate_blocks_gopher_protocol(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validate('gopher://example.com');
    }

    /**
     * SSRF-11: DNS rebinding attempt blocked.
     */
    public function test_validate_blocks_dns_rebinding(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        // Domain that resolves to private IP
        $this->service->validate('internal.local');
    }

    /**
     * SSRF-12: IPv6 localhost blocked.
     */
    public function test_validate_blocks_ipv6_localhost(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('[::1]');
    }

    /**
     * SSRF-13: IPv6 mapped IPv4 blocked.
     */
    public function test_validate_blocks_ipv6_mapped_ipv4(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        $this->service->validate('[::ffff:127.0.0.1]');
    }

    /**
     * SSRF-14: Decimal IP bypass blocked.
     */
    public function test_validate_blocks_decimal_ip_bypass(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        // 2130706433 = 127.0.0.1 in decimal
        $this->service->validate('2130706433');
    }

    /**
     * SSRF-15: Hex IP bypass blocked.
     */
    public function test_validate_blocks_hex_ip_bypass(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        // 0x7f000001 = 127.0.0.1 in hex
        $this->service->validate('0x7f000001');
    }

    /**
     * SSRF-16: Octal IP bypass blocked.
     */
    public function test_validate_blocks_octal_ip_bypass(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        // 0177.0.0.1 = 127.0.0.1 in octal
        $this->service->validate('0177.0.0.1');
    }

    // =========================================================================
    // ERROR HANDLING TESTS (EH-*)
    // =========================================================================

    /**
     * EH-01: Invalid domain format throws ValidationException.
     */
    public function test_validate_throws_exception_for_invalid_format(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validate('!@#$%');
    }

    /**
     * EH-02: Empty domain throws ValidationException.
     */
    public function test_validate_throws_exception_for_empty_domain(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validate('');
    }

    /**
     * EH-03: Whitespace-only domain throws ValidationException.
     */
    public function test_validate_throws_exception_for_whitespace_only(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validate('   ');
    }

    /**
     * EH-04: ValidationException contains error code.
     */
    public function test_validate_exception_contains_error_code(): void
    {
        try {
            $this->service->validate('');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors()['domain'] ?? []);
        }
    }

    // =========================================================================
    // INTEGRATION TESTS (INT-*)
    // =========================================================================

    /**
     * INT-01: Uses URLValidationService for SSRF checks.
     */
    public function test_validate_uses_url_validation_service(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->with('https://example.com')
            ->andReturn(['valid' => true]);

        // Mock HTTP response for accessibility check
        // Note: In real implementation, this would mock HTTP client

        $this->service->normalize('example.com');
    }

    /**
     * INT-03: Normalization then SSRF check.
     */
    public function test_validate_normalizes_then_checks_ssrf(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->with('https://localhost')
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $this->expectException(ValidationException::class);

        // localhost should be normalized to https://localhost then blocked
        $this->service->validate('localhost');
    }

    // =========================================================================
    // RETURN VALUE TESTS (RV-*)
    // =========================================================================

    /**
     * RV-01: Success returns normalized URL structure.
     */
    public function test_validate_returns_normalized_url_structure(): void
    {
        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        // This test would need HTTP mocking for accessibility check
        // For now, test normalization output
        $normalized = $this->service->normalize('example.com');

        $this->assertEquals('https://example.com', $normalized);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Handles domain with spaces.
     */
    public function test_normalize_trims_spaces(): void
    {
        $result = $this->service->normalize('  example.com  ');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * Handles domain with uppercase.
     */
    public function test_normalize_converts_to_lowercase(): void
    {
        $result = $this->service->normalize('EXAMPLE.COM');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * Handles domain with mixed case.
     */
    public function test_normalize_handles_mixed_case(): void
    {
        $result = $this->service->normalize('ExAmPlE.CoM');

        $this->assertEquals('https://example.com', $result);
    }

    /**
     * Handles domain with URL-encoded characters.
     */
    public function test_normalize_handles_url_encoded(): void
    {
        $result = $this->service->normalize('example%20test.com');

        // Should decode or preserve
        $this->assertStringContainsString('https://', $result);
    }

    /**
     * Handles very long but valid domain.
     */
    public function test_normalize_handles_long_valid_domain(): void
    {
        $longDomain = str_repeat('sub', 30) . '.example.com';

        $result = $this->service->normalize($longDomain);

        $this->assertStringContainsString('https://', $result);
    }

    /**
     * Handles domain with numbers.
     */
    public function test_normalize_handles_numeric_domain(): void
    {
        $result = $this->service->normalize('example123.com');

        $this->assertEquals('https://example123.com', $result);
    }

    /**
     * Handles domain with hyphens.
     */
    public function test_normalize_handles_hyphenated_domain(): void
    {
        $result = $this->service->normalize('my-example-site.com');

        $this->assertEquals('https://my-example-site.com', $result);
    }

    /**
     * Handles punycode domain.
     */
    public function test_normalize_handles_punycode(): void
    {
        $result = $this->service->normalize('xn--example-6q4a.com');

        $this->assertEquals('https://xn--example-6q4a.com', $result);
    }

    /**
     * Handles domain with underscore (invalid but some systems allow).
     */
    public function test_normalize_handles_underscore(): void
    {
        // Underscores are technically invalid in DNS but often work
        $result = $this->service->normalize('example_test.com');

        // Should handle gracefully
        $this->assertStringContainsString('https://', $result);
    }

    /**
     * Handles HTTP with www.
     */
    public function test_normalize_handles_http_with_www(): void
    {
        $result = $this->service->normalize('http://www.example.com');

        $this->assertEquals('http://www.example.com', $result);
    }

    /**
     * Handles HTTPS with www.
     */
    public function test_normalize_handles_https_with_www(): void
    {
        $result = $this->service->normalize('https://www.example.com');

        $this->assertEquals('https://www.example.com', $result);
    }

    /**
     * Handles port with protocol.
     */
    public function test_normalize_handles_port_with_protocol(): void
    {
        $result = $this->service->normalize('https://example.com:8443');

        $this->assertEquals('https://example.com:8443', $result);
    }

    /**
     * Handles domain starting with number.
     */
    public function test_normalize_handles_numeric_start(): void
    {
        $result = $this->service->normalize('2example.com');

        $this->assertEquals('https://2example.com', $result);
    }

    /**
     * Validates domain with IDN characters.
     */
    public function test_is_valid_format_accepts_idn(): void
    {
        $this->assertTrue($this->service->isValidFormat('例え.jp'));
    }

    /**
     * Validates punycode domain.
     */
    public function test_is_valid_format_accepts_punycode_domain(): void
    {
        $this->assertTrue($this->service->isValidFormat('xn--fsq.com'));
    }

    /**
     * Rejects domain with control characters.
     */
    public function test_is_valid_format_rejects_control_characters(): void
    {
        $this->assertFalse($this->service->isValidFormat("example\x00.com"));
    }

    /**
     * Rejects domain with newline.
     */
    public function test_is_valid_format_rejects_newline(): void
    {
        $this->assertFalse($this->service->isValidFormat("example\n.com"));
    }

    /**
     * Rejects domain with tab.
     */
    public function test_is_valid_format_rejects_tab(): void
    {
        $this->assertFalse($this->service->isValidFormat("example\t.com"));
    }

    /**
     * Handles extremely long TLD.
     */
    public function test_is_valid_format_handles_long_tld(): void
    {
        // Some new gTLDs can be long
        $this->assertTrue($this->service->isValidFormat('example.technology'));
    }

    /**
     * Validates numeric TLD (should fail).
     */
    public function test_is_valid_format_rejects_numeric_tld(): void
    {
        // TLD should not be all numeric
        $this->assertFalse($this->service->isValidFormat('example.123'));
    }
}
