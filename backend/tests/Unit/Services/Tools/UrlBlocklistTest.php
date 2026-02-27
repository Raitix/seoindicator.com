<?php

namespace Tests\Unit\Services\Tools;

use Tests\TestCase;
use App\Services\Tools\UrlBlocklist;

/**
 * Unit tests for TASK-16-1: URL Blocklist Service
 *
 * Tests the UrlBlocklist service responsible for:
 * - Detecting blocked URL patterns
 * - Preventing SSRF attacks
 * - Blocking internal/private IP ranges
 *
 * @see \App\Services\Tools\UrlBlocklist
 */
class UrlBlocklistTest extends TestCase
{
    protected UrlBlocklist $blocklist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blocklist = new UrlBlocklist();
    }

    // =========================================================================
    // LOCALHOST BLOCKING TESTS
    // =========================================================================

    /**
     * URL-BLK-001: Block localhost
     */
    public function test_block_localhost(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://localhost'));
    }

    /**
     * URL-BLK-002: Block localhost with port
     */
    public function test_block_localhost_with_port(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://localhost:3000'));
        $this->assertTrue($this->blocklist->isBlocked('http://localhost:8080'));
    }

    /**
     * URL-BLK-003: Block 127.0.0.1
     */
    public function test_block_127_0_0_1(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://127.0.0.1'));
    }

    /**
     * URL-BLK-004: Block 127.x.x.x range
     */
    public function test_block_127_range(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://127.0.0.5'));
        $this->assertTrue($this->blocklist->isBlocked('http://127.255.255.255'));
    }

    // =========================================================================
    // PRIVATE IP RANGE TESTS
    // =========================================================================

    /**
     * URL-BLK-005: Block 10.x.x.x private range
     */
    public function test_block_10_range(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://10.0.0.1'));
        $this->assertTrue($this->blocklist->isBlocked('http://10.255.255.255'));
    }

    /**
     * URL-BLK-006: Block 172.16.x.x private range
     */
    public function test_block_172_range(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://172.16.0.1'));
        $this->assertTrue($this->blocklist->isBlocked('http://172.31.255.255'));
    }

    /**
     * URL-BLK-007: Block 192.168.x.x private range
     */
    public function test_block_192_168_range(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://192.168.1.1'));
        $this->assertTrue($this->blocklist->isBlocked('http://192.168.0.1'));
    }

    // =========================================================================
    // PROTOCOL BLOCKING TESTS
    // =========================================================================

    /**
     * URL-BLK-008: Block file:// protocol
     */
    public function test_block_file_protocol(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('file:///etc/passwd'));
        $this->assertTrue($this->blocklist->isBlocked('file://localhost/etc/passwd'));
    }

    /**
     * URL-BLK-009: Block ftp:// protocol
     */
    public function test_block_ftp_protocol(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('ftp://example.com'));
    }

    // =========================================================================
    // HOSTNAME BLOCKING TESTS
    // =========================================================================

    /**
     * URL-BLK-010: Block internal hostname pattern
     */
    public function test_block_internal_hostname(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://internal.example.com'));
        $this->assertTrue($this->blocklist->isBlocked('http://intranet.example.com'));
    }

    // =========================================================================
    // CLOUD METADATA ENDPOINT TESTS
    // =========================================================================

    /**
     * URL-BLK-011: Block AWS metadata endpoint
     */
    public function test_block_aws_metadata(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://169.254.169.254'));
        $this->assertTrue($this->blocklist->isBlocked('http://169.254.169.254/latest/meta-data'));
    }

    // =========================================================================
    // ALLOWED URL TESTS
    // =========================================================================

    /**
     * URL-BLK-012: Allow valid external URL
     */
    public function test_allow_valid_external_url(): void
    {
        $this->assertFalse($this->blocklist->isBlocked('https://google.com'));
        $this->assertFalse($this->blocklist->isBlocked('https://example.com'));
        $this->assertFalse($this->blocklist->isBlocked('https://github.com/user/repo'));
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * EC-001: Handle IPv6 localhost
     */
    public function test_handle_ipv6_localhost(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://[::1]'));
        $this->assertTrue($this->blocklist->isBlocked('http://[0:0:0:0:0:0:0:1]'));
    }

    /**
     * EC-002: Handle IPv6 private range
     */
    public function test_handle_ipv6_private_range(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://[fc00::1]'));
        $this->assertTrue($this->blocklist->isBlocked('http://[fd00::1]'));
    }

    /**
     * EC-003: Handle decimal IP representation
     */
    public function test_handle_decimal_ip_representation(): void
    {
        // 2130706433 = 127.0.0.1 in decimal
        $this->assertTrue($this->blocklist->isBlocked('http://2130706433'));
    }

    /**
     * EC-004: Handle hex IP representation
     */
    public function test_handle_hex_ip_representation(): void
    {
        // 0x7f000001 = 127.0.0.1 in hex
        $this->assertTrue($this->blocklist->isBlocked('http://0x7f000001'));
    }

    /**
     * EC-005: Handle octal IP representation
     */
    public function test_handle_octal_ip_representation(): void
    {
        // 017700000001 = 127.0.0.1 in octal
        $this->assertTrue($this->blocklist->isBlocked('http://017700000001'));
    }

    /**
     * EC-006: Handle IPv4-mapped IPv6
     */
    public function test_handle_ipv4_mapped_ipv6(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://[::ffff:127.0.0.1]'));
        $this->assertTrue($this->blocklist->isBlocked('http://[::ffff:192.168.1.1]'));
    }

    /**
     * EC-007: Handle DNS rebinding protection
     */
    public function test_dns_rebinding_protection(): void
    {
        // Even if hostname looks valid, resolve and check IP
        // This would require actual DNS resolution in production
        $this->assertTrue($this->blocklist->shouldResolveDns());
    }

    /**
     * EC-008: Handle URL with credentials
     */
    public function test_handle_url_with_credentials(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://user:pass@localhost'));
        $this->assertFalse($this->blocklist->isBlocked('http://user:pass@example.com'));
    }

    /**
     * EC-009: Handle case variations
     */
    public function test_handle_case_variations(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('http://LOCALHOST'));
        $this->assertTrue($this->blocklist->isBlocked('http://LocalHost'));
        $this->assertTrue($this->blocklist->isBlocked('FILE:///etc/passwd'));
    }

    /**
     * EC-010: Handle null input
     */
    public function test_handle_null_input(): void
    {
        $this->assertTrue($this->blocklist->isBlocked(null));
    }

    /**
     * EC-011: Handle empty string
     */
    public function test_handle_empty_string(): void
    {
        $this->assertTrue($this->blocklist->isBlocked(''));
    }

    /**
     * EC-012: Handle malformed URL
     */
    public function test_handle_malformed_url(): void
    {
        $this->assertTrue($this->blocklist->isBlocked('not-a-url'));
    }

    // =========================================================================
    // CUSTOM BLOCKLIST TESTS
    // =========================================================================

    /**
     * CUSTOM-001: Add custom blocked pattern
     */
    public function test_add_custom_blocked_pattern(): void
    {
        $this->blocklist->addPattern('evil\.com$');
        $this->assertTrue($this->blocklist->isBlocked('https://evil.com'));
    }

    /**
     * CUSTOM-002: Remove custom blocked pattern
     */
    public function test_remove_custom_blocked_pattern(): void
    {
        $this->blocklist->addPattern('test\.com$');
        $this->blocklist->removePattern('test\.com$');
        $this->assertFalse($this->blocklist->isBlocked('https://test.com'));
    }

    /**
     * CUSTOM-003: Get all blocked patterns
     */
    public function test_get_all_blocked_patterns(): void
    {
        $patterns = $this->blocklist->getPatterns();
        $this->assertIsArray($patterns);
        $this->assertNotEmpty($patterns);
    }

    // =========================================================================
    // BLOCK REASON TESTS
    // =========================================================================

    /**
     * REASON-001: Get block reason for localhost
     */
    public function test_get_block_reason_localhost(): void
    {
        $reason = $this->blocklist->getBlockReason('http://localhost');
        $this->assertStringContainsString('localhost', strtolower($reason));
    }

    /**
     * REASON-002: Get block reason for private IP
     */
    public function test_get_block_reason_private_ip(): void
    {
        $reason = $this->blocklist->getBlockReason('http://192.168.1.1');
        $this->assertStringContainsString('private', strtolower($reason));
    }

    /**
     * REASON-003: Get block reason for blocked protocol
     */
    public function test_get_block_reason_blocked_protocol(): void
    {
        $reason = $this->blocklist->getBlockReason('file:///etc/passwd');
        $this->assertStringContainsString('protocol', strtolower($reason));
    }

    /**
     * REASON-004: Get block reason returns null for allowed URL
     */
    public function test_get_block_reason_null_for_allowed(): void
    {
        $reason = $this->blocklist->getBlockReason('https://example.com');
        $this->assertNull($reason);
    }
}
