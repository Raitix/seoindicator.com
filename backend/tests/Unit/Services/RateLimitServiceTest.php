<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RateLimitService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Mockery;

/**
 * Unit tests for TASK-6-19: Rate Limit Service
 *
 * Tests the RateLimitService responsible for:
 * - Request counting with sliding window
 * - Rate limit checking
 * - CAPTCHA bypass handling
 * - Rate limit headers
 * - IP normalization
 *
 * Note: These tests use Cache fallback (no Redis required).
 * The RateLimitService automatically falls back to Cache when Redis is unavailable.
 *
 * @see \App\Services\RateLimitService
 */
class RateLimitServiceTest extends TestCase
{
    protected RateLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Use the app container to get the service
        // It will automatically use Cache fallback since Redis isn't available
        $this->service = app(RateLimitService::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // CHECK METHOD TESTS (CK)
    // =========================================================================

    /**
     * CK-001: Check allows request under limit
     */
    public function test_check_allows_request_under_limit(): void
    {
        $ip = '192.168.1.1';

        // First request should be allowed
        $result = $this->service->check($ip);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(3, $result['remaining']);
        $this->assertFalse($result['captchaRequired']);
    }

    /**
     * CK-002: Check blocks request at limit
     */
    public function test_check_blocks_request_at_limit(): void
    {
        $ip = '192.168.1.2';

        // Use up all requests
        $this->service->increment($ip);
        $this->service->increment($ip);
        $this->service->increment($ip);

        // Fourth request should be blocked
        $result = $this->service->check($ip);

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertTrue($result['captchaRequired']);
    }

    /**
     * CK-003: Check returns correct remaining count
     */
    public function test_check_returns_correct_remaining_count(): void
    {
        $ip = '192.168.1.3';

        // No requests yet
        $result = $this->service->check($ip);
        $this->assertEquals(3, $result['remaining']);

        // After one request
        $this->service->increment($ip);
        $result = $this->service->check($ip);
        $this->assertEquals(2, $result['remaining']);

        // After two requests
        $this->service->increment($ip);
        $result = $this->service->check($ip);
        $this->assertEquals(1, $result['remaining']);

        // After three requests (at limit)
        $this->service->increment($ip);
        $result = $this->service->check($ip);
        $this->assertEquals(0, $result['remaining']);
    }

    /**
     * CK-004: Check returns reset timestamp
     */
    public function test_check_returns_reset_timestamp(): void
    {
        $ip = '192.168.1.4';

        $result = $this->service->check($ip);

        $this->assertArrayHasKey('resetAt', $result);
        $this->assertIsInt($result['resetAt']);
        $this->assertGreaterThan(time(), $result['resetAt']);
    }

    /**
     * CK-005: Check different IPs have independent counters
     */
    public function test_check_different_ips_independent(): void
    {
        $ip1 = '192.168.1.5';
        $ip2 = '192.168.1.6';

        // Use up limit for IP1
        $this->service->increment($ip1);
        $this->service->increment($ip1);
        $this->service->increment($ip1);

        // IP1 should be blocked
        $result1 = $this->service->check($ip1);
        $this->assertFalse($result1['allowed']);

        // IP2 should still be allowed
        $result2 = $this->service->check($ip2);
        $this->assertTrue($result2['allowed']);
        $this->assertEquals(3, $result2['remaining']);
    }

    // =========================================================================
    // INCREMENT METHOD TESTS (IN)
    // =========================================================================

    /**
     * IN-001: Increment returns new count
     */
    public function test_increment_returns_new_count(): void
    {
        $ip = '192.168.1.10';

        $count1 = $this->service->increment($ip);
        $this->assertEquals(1, $count1);

        $count2 = $this->service->increment($ip);
        $this->assertEquals(2, $count2);

        $count3 = $this->service->increment($ip);
        $this->assertEquals(3, $count3);
    }

    /**
     * IN-002: Increment starts from zero
     */
    public function test_increment_starts_from_zero(): void
    {
        $ip = '192.168.1.11';

        // First increment should return 1
        $count = $this->service->increment($ip);
        $this->assertEquals(1, $count);
    }

    /**
     * IN-003: Increment stores count in cache
     */
    public function test_increment_stores_count_in_cache(): void
    {
        $ip = '192.168.1.12';

        $this->service->increment($ip);
        $this->service->increment($ip);

        // Verify count persisted
        $count = $this->service->getCurrentCount($ip);
        $this->assertEquals(2, $count);
    }

    /**
     * IN-004: Increment different IPs don't interfere
     */
    public function test_increment_different_ips_dont_interfere(): void
    {
        $ip1 = '192.168.1.13';
        $ip2 = '192.168.1.14';

        $this->service->increment($ip1);
        $this->service->increment($ip1);

        $count1 = $this->service->getCurrentCount($ip1);
        $count2 = $this->service->getCurrentCount($ip2);

        $this->assertEquals(2, $count1);
        $this->assertEquals(0, $count2);
    }

    // =========================================================================
    // VERIFY CAPTCHA METHOD TESTS (VC)
    // =========================================================================

    /**
     * VC-001: Verify captcha resets counter
     */
    public function test_verify_captcha_resets_counter(): void
    {
        $ip = '192.168.1.20';

        // Use up the limit
        $this->service->increment($ip);
        $this->service->increment($ip);
        $this->service->increment($ip);

        // Verify blocked
        $this->assertFalse($this->service->check($ip)['allowed']);

        // Verify CAPTCHA
        $result = $this->service->verifyCaptcha($ip, 'test-token');

        $this->assertTrue($result['valid']);
        $this->assertEquals(3, $result['remaining']);
    }

    /**
     * VC-002: Verify captcha creates bypass
     */
    public function test_verify_captcha_creates_bypass(): void
    {
        $ip = '192.168.1.21';

        $this->service->verifyCaptcha($ip, 'test-token');

        $this->assertTrue($this->service->hasCaptchaBypass($ip));
    }

    /**
     * VC-003: Has captcha bypass returns false without verification
     */
    public function test_has_captcha_bypass_false_without_verification(): void
    {
        $ip = '192.168.1.22';

        $this->assertFalse($this->service->hasCaptchaBypass($ip));
    }

    /**
     * VC-004: Verify captcha allows new requests
     */
    public function test_verify_captcha_allows_new_requests(): void
    {
        $ip = '192.168.1.23';

        // Exhaust limit
        for ($i = 0; $i < 3; $i++) {
            $this->service->increment($ip);
        }

        // Verify CAPTCHA
        $this->service->verifyCaptcha($ip, 'test-token');

        // Should be able to make requests again
        $result = $this->service->check($ip);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(3, $result['remaining']);
    }

    // =========================================================================
    // RESET METHOD TESTS (RS)
    // =========================================================================

    /**
     * RS-001: Reset clears counter
     */
    public function test_reset_clears_counter(): void
    {
        $ip = '192.168.1.30';

        // Add some counts
        $this->service->increment($ip);
        $this->service->increment($ip);

        // Reset
        $this->service->reset($ip);

        // Should start fresh
        $count = $this->service->getCurrentCount($ip);
        $this->assertEquals(0, $count);
    }

    /**
     * RS-002: Reset allows requests again
     */
    public function test_reset_allows_requests_again(): void
    {
        $ip = '192.168.1.31';

        // Exhaust limit
        for ($i = 0; $i < 3; $i++) {
            $this->service->increment($ip);
        }

        // Reset
        $this->service->reset($ip);

        // Should be allowed again
        $result = $this->service->check($ip);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(3, $result['remaining']);
    }

    /**
     * RS-003: Reset for non-existent IP doesn't error
     */
    public function test_reset_non_existent_ip_no_error(): void
    {
        $ip = '192.168.1.32';

        // Should not throw
        $this->service->reset($ip);

        $count = $this->service->getCurrentCount($ip);
        $this->assertEquals(0, $count);
    }

    // =========================================================================
    // GET HEADERS METHOD TESTS (GH)
    // =========================================================================

    /**
     * GH-001: Get headers returns correct format
     */
    public function test_get_headers_returns_correct_format(): void
    {
        $ip = '192.168.1.40';

        $headers = $this->service->getHeaders($ip);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    /**
     * GH-002: Get headers shows correct limit
     */
    public function test_get_headers_shows_correct_limit(): void
    {
        $ip = '192.168.1.41';

        $headers = $this->service->getHeaders($ip);

        $this->assertEquals('3', $headers['X-RateLimit-Limit']);
    }

    /**
     * GH-003: Get headers shows correct remaining
     */
    public function test_get_headers_shows_correct_remaining(): void
    {
        $ip = '192.168.1.42';

        // No requests yet
        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('3', $headers['X-RateLimit-Remaining']);

        // After one request
        $this->service->increment($ip);
        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('2', $headers['X-RateLimit-Remaining']);

        // After two requests
        $this->service->increment($ip);
        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('1', $headers['X-RateLimit-Remaining']);
    }

    /**
     * GH-004: Get headers shows zero remaining at limit
     */
    public function test_get_headers_shows_zero_remaining_at_limit(): void
    {
        $ip = '192.168.1.43';

        // Exhaust limit
        for ($i = 0; $i < 3; $i++) {
            $this->service->increment($ip);
        }

        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('0', $headers['X-RateLimit-Remaining']);
    }

    /**
     * GH-005: Get headers includes reset timestamp
     */
    public function test_get_headers_includes_reset_timestamp(): void
    {
        $ip = '192.168.1.44';

        $headers = $this->service->getHeaders($ip);

        $this->assertIsNumeric($headers['X-RateLimit-Reset']);
        $resetTime = (int) $headers['X-RateLimit-Reset'];
        $this->assertGreaterThan(time(), $resetTime);
    }

    // =========================================================================
    // GET CLIENT IP METHOD TESTS (IP)
    // =========================================================================

    /**
     * IP-001: Get client IP from direct request
     */
    public function test_get_client_ip_from_direct_request(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.50');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('192.168.1.50', $ip);
    }

    /**
     * IP-002: Get client IP from X-Forwarded-For
     */
    public function test_get_client_ip_from_x_forwarded_for(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Forwarded-For', '192.168.1.51, 10.0.0.1');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('192.168.1.51', $ip);
    }

    /**
     * IP-003: Get client IP from X-Real-IP
     */
    public function test_get_client_ip_from_x_real_ip(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Real-IP', '192.168.1.52');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('192.168.1.52', $ip);
    }

    /**
     * IP-004: Get client IP from Cloudflare header
     */
    public function test_get_client_ip_from_cloudflare_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('CF-Connecting-IP', '192.168.1.53');
        $request->headers->set('X-Forwarded-For', '10.0.0.1');

        $ip = $this->service->getClientIp($request);

        // Cloudflare header takes precedence
        $this->assertEquals('192.168.1.53', $ip);
    }

    /**
     * IP-005: Get client IP normalizes IPv4-mapped IPv6
     */
    public function test_get_client_ip_normalizes_ipv4_mapped_ipv6(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '::ffff:192.168.1.54');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('192.168.1.54', $ip);
    }

    /**
     * IP-006: Get client IP normalizes IPv6 localhost
     */
    public function test_get_client_ip_normalizes_ipv6_localhost(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '::1');

        $ip = $this->service->getClientIp($request);

        $this->assertEquals('127.0.0.1', $ip);
    }

    /**
     * IP-007: Get client IP handles multiple X-Forwarded-For entries
     */
    public function test_get_client_ip_handles_multiple_x_forwarded_for(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Forwarded-For', '192.168.1.55, 10.0.0.1, 172.16.0.1');

        $ip = $this->service->getClientIp($request);

        // Should get the first (original client) IP
        $this->assertEquals('192.168.1.55', $ip);
    }

    // =========================================================================
    // GETTERS TESTS (GT)
    // =========================================================================

    /**
     * GT-001: Get max requests returns correct value
     */
    public function test_get_max_requests_returns_correct_value(): void
    {
        $this->assertEquals(3, $this->service->getMaxRequests());
    }

    /**
     * GT-002: Get window seconds returns correct value
     */
    public function test_get_window_seconds_returns_correct_value(): void
    {
        $this->assertEquals(86400, $this->service->getWindowSeconds());
    }

    /**
     * GT-003: Get current count returns zero for new IP
     */
    public function test_get_current_count_returns_zero_for_new_ip(): void
    {
        $ip = '192.168.1.60';

        $count = $this->service->getCurrentCount($ip);

        $this->assertEquals(0, $count);
    }

    /**
     * GT-004: Get current count returns correct count
     */
    public function test_get_current_count_returns_correct_count(): void
    {
        $ip = '192.168.1.61';

        $this->service->increment($ip);
        $this->service->increment($ip);

        $count = $this->service->getCurrentCount($ip);

        $this->assertEquals(2, $count);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Handle empty IP string
     */
    public function test_handle_empty_ip_string(): void
    {
        $ip = '';

        // Should not throw
        $this->service->increment($ip);
        $result = $this->service->check($ip);

        $this->assertIsBool($result['allowed']);
    }

    /**
     * EC-002: Handle IPv6 address
     */
    public function test_handle_ipv6_address(): void
    {
        $ip = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $this->service->increment($ip);
        $result = $this->service->check($ip);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(2, $result['remaining']);
    }

    /**
     * EC-003: Handle localhost IP
     */
    public function test_handle_localhost_ip(): void
    {
        $ip = '127.0.0.1';

        $this->service->increment($ip);
        $result = $this->service->check($ip);

        $this->assertTrue($result['allowed']);
    }

    /**
     * EC-004: Handle special characters in IP (invalid)
     */
    public function test_handle_special_characters_in_ip(): void
    {
        $ip = "192.168.1.1\n";

        // Should handle gracefully (normalized or hashed)
        $this->service->increment($ip);

        $this->assertIsInt($this->service->getCurrentCount($ip));
    }

    /**
     * EC-005: Concurrent increments for same IP
     */
    public function test_concurrent_increments_for_same_ip(): void
    {
        $ip = '192.168.1.70';

        // Simulate concurrent increments
        $count1 = $this->service->increment($ip);
        $count2 = $this->service->increment($ip);
        $count3 = $this->service->increment($ip);
        $count4 = $this->service->increment($ip);

        $this->assertEquals(1, $count1);
        $this->assertEquals(2, $count2);
        $this->assertEquals(3, $count3);
        $this->assertEquals(4, $count4);
    }

    /**
     * EC-006: Very long IP string
     */
    public function test_very_long_ip_string(): void
    {
        $ip = str_repeat('1', 1000);

        // Should not throw
        $this->service->increment($ip);
        $result = $this->service->check($ip);

        $this->assertIsBool($result['allowed']);
    }

    // =========================================================================
    // INTEGRATION TESTS (IT)
    // =========================================================================

    /**
     * IT-001: Full rate limit flow
     */
    public function test_full_rate_limit_flow(): void
    {
        $ip = '192.168.1.80';

        // Initial state
        $result = $this->service->check($ip);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(3, $result['remaining']);

        // First request
        $this->service->increment($ip);
        $result = $this->service->check($ip);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(2, $result['remaining']);

        // Second request
        $this->service->increment($ip);
        $result = $this->service->check($ip);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['remaining']);

        // Third request (at limit)
        $this->service->increment($ip);
        $result = $this->service->check($ip);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertTrue($result['captchaRequired']);

        // CAPTCHA bypass
        $this->service->verifyCaptcha($ip, 'token');
        $result = $this->service->check($ip);
        $this->assertTrue($result['allowed']);
        $this->assertTrue($this->service->hasCaptchaBypass($ip));
    }

    /**
     * IT-002: Headers updated correctly throughout flow
     */
    public function test_headers_updated_correctly(): void
    {
        $ip = '192.168.1.81';

        // Initial headers
        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('3', $headers['X-RateLimit-Limit']);
        $this->assertEquals('3', $headers['X-RateLimit-Remaining']);

        // After one request
        $this->service->increment($ip);
        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('2', $headers['X-RateLimit-Remaining']);

        // After reset
        $this->service->reset($ip);
        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('3', $headers['X-RateLimit-Remaining']);
    }

    /**
     * IT-003: Multiple IPs tracked independently
     */
    public function test_multiple_ips_tracked_independently(): void
    {
        $ips = ['192.168.1.90', '192.168.1.91', '192.168.1.92'];

        foreach ($ips as $index => $ip) {
            // Use different amounts for each IP
            for ($i = 0; $i <= $index; $i++) {
                $this->service->increment($ip);
            }
        }

        // Verify each IP has correct count
        $this->assertEquals(1, $this->service->getCurrentCount($ips[0]));
        $this->assertEquals(2, $this->service->getCurrentCount($ips[1]));
        $this->assertEquals(3, $this->service->getCurrentCount($ips[2]));

        // Verify headers for each
        $headers0 = $this->service->getHeaders($ips[0]);
        $headers1 = $this->service->getHeaders($ips[1]);
        $headers2 = $this->service->getHeaders($ips[2]);

        $this->assertEquals('2', $headers0['X-RateLimit-Remaining']);
        $this->assertEquals('1', $headers1['X-RateLimit-Remaining']);
        $this->assertEquals('0', $headers2['X-RateLimit-Remaining']);
    }
}
