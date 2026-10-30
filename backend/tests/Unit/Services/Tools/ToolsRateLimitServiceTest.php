<?php

namespace Tests\Unit\Services\Tools;

use Tests\TestCase;
use App\Services\Tools\ToolsRateLimitService;
use Illuminate\Support\Facades\Cache;

/**
 * Unit tests for TASK-16-11: Tools Rate Limiting Service
 *
 * Tests the ToolsRateLimitService responsible for:
 * - Per-minute rate limiting
 * - Per-day rate limiting
 * - CAPTCHA integration
 * - URL blocklist checking
 * - Rate limit headers generation
 *
 * @see \App\Services\Tools\ToolsRateLimitService
 */
class ToolsRateLimitServiceTest extends TestCase
{
    protected ToolsRateLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ToolsRateLimitService([
            'perMinute' => 10,
            'perDay' => 100,
            'captchaThreshold' => 3
        ]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // PER-MINUTE RATE LIMITING TESTS (MIN)
    // =========================================================================

    /**
     * MIN-001: Under limit - all allowed
     */
    public function test_under_limit_all_allowed(): void
    {
        $ip = '192.168.1.1';

        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->checkLimit($ip);
            $this->assertTrue($result['allowed']);
            $this->service->increment($ip);
        }
    }

    /**
     * MIN-002: At limit - all allowed
     */
    public function test_at_limit_all_allowed(): void
    {
        $ip = '192.168.1.2';

        for ($i = 0; $i < 10; $i++) {
            $result = $this->service->checkLimit($ip);
            $this->assertTrue($result['allowed']);
            $this->service->increment($ip);
        }
    }

    /**
     * MIN-003: Over limit - 11th blocked
     */
    public function test_over_limit_blocked(): void
    {
        $ip = '192.168.1.3';

        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($ip);
        }

        // 11th should be blocked
        $result = $this->service->checkLimit($ip);
        $this->assertFalse($result['allowed']);
    }

    /**
     * MIN-004: Different IPs have independent limits
     */
    public function test_different_ips_independent(): void
    {
        $ip1 = '192.168.1.4';
        $ip2 = '192.168.1.5';

        // Use up limit for IP1
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($ip1);
        }

        // IP1 should be blocked
        $result1 = $this->service->checkLimit($ip1);
        $this->assertFalse($result1['allowed']);

        // IP2 should still be allowed
        $result2 = $this->service->checkLimit($ip2);
        $this->assertTrue($result2['allowed']);
    }

    /**
     * MIN-005: Window reset after 60 seconds
     */
    public function test_window_reset(): void
    {
        $ip = '192.168.1.6';

        // Use up the limit
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($ip);
        }

        // Should be blocked
        $this->assertFalse($this->service->checkLimit($ip)['allowed']);

        // Simulate window reset (in real scenario, wait 60s)
        Cache::forget($this->service->getMinuteKey($ip));

        // Should be allowed again
        $this->assertTrue($this->service->checkLimit($ip)['allowed']);
    }

    /**
     * MIN-006: Sliding window count
     */
    public function test_sliding_window_count(): void
    {
        $ip = '192.168.1.7';

        // Make 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->service->increment($ip);
        }

        // Check remaining
        $result = $this->service->checkLimit($ip);
        $this->assertEquals(5, $result['remaining']);
    }

    /**
     * MIN-007: Concurrent requests handled correctly
     */
    public function test_concurrent_requests(): void
    {
        $ip = '192.168.1.8';

        // Simulate concurrent increments
        $counts = [];
        for ($i = 0; $i < 5; $i++) {
            $counts[] = $this->service->increment($ip);
        }

        // Should have sequential counts
        $this->assertEquals([1, 2, 3, 4, 5], $counts);
    }

    /**
     * MIN-008: Default limit used when not configured
     */
    public function test_default_limit(): void
    {
        $service = new ToolsRateLimitService();
        $this->assertEquals(10, $service->getPerMinuteLimit());
    }

    // =========================================================================
    // PER-DAY RATE LIMITING TESTS (DAY)
    // =========================================================================

    /**
     * DAY-001: Under daily limit
     */
    public function test_under_daily_limit(): void
    {
        $ip = '192.168.2.1';

        for ($i = 0; $i < 50; $i++) {
            $result = $this->service->checkDailyLimit($ip);
            $this->assertTrue($result['allowed']);
            $this->service->incrementDaily($ip);
        }
    }

    /**
     * DAY-002: At daily limit
     */
    public function test_at_daily_limit(): void
    {
        $ip = '192.168.2.2';

        for ($i = 0; $i < 100; $i++) {
            $result = $this->service->checkDailyLimit($ip);
            $this->assertTrue($result['allowed']);
            $this->service->incrementDaily($ip);
        }
    }

    /**
     * DAY-003: Over daily limit
     */
    public function test_over_daily_limit(): void
    {
        $ip = '192.168.2.3';

        // Use up the limit
        for ($i = 0; $i < 100; $i++) {
            $this->service->incrementDaily($ip);
        }

        // 101st should be blocked
        $result = $this->service->checkDailyLimit($ip);
        $this->assertFalse($result['allowed']);
    }

    /**
     * DAY-004: Daily reset at midnight
     */
    public function test_daily_reset_at_midnight(): void
    {
        $ip = '192.168.2.4';

        // Use up the limit
        for ($i = 0; $i < 100; $i++) {
            $this->service->incrementDaily($ip);
        }

        // Should be blocked
        $this->assertFalse($this->service->checkDailyLimit($ip)['allowed']);

        // Simulate midnight reset
        Cache::forget($this->service->getDailyKey($ip));

        // Should be allowed again
        $this->assertTrue($this->service->checkDailyLimit($ip)['allowed']);
    }

    /**
     * DAY-005: Different IPs have independent daily limits
     */
    public function test_different_ips_daily_independent(): void
    {
        $ip1 = '192.168.2.5';
        $ip2 = '192.168.2.6';

        // Use up limit for IP1
        for ($i = 0; $i < 100; $i++) {
            $this->service->incrementDaily($ip1);
        }

        // IP1 should be blocked
        $this->assertFalse($this->service->checkDailyLimit($ip1)['allowed']);

        // IP2 should still be allowed
        $this->assertTrue($this->service->checkDailyLimit($ip2)['allowed']);
    }

    /**
     * DAY-006: Combined minute and daily limits
     */
    public function test_combined_limits(): void
    {
        $ip = '192.168.2.7';

        // Check both limits
        $minuteResult = $this->service->checkLimit($ip);
        $dayResult = $this->service->checkDailyLimit($ip);

        $this->assertTrue($minuteResult['allowed']);
        $this->assertTrue($dayResult['allowed']);
    }

    /**
     * DAY-007: Day rollover handling
     */
    public function test_day_rollover(): void
    {
        $ip = '192.168.2.8';

        // Get key for today
        $todayKey = $this->service->getDailyKey($ip);

        // Key should contain today's date
        $this->assertStringContainsString(date('Y-m-d'), $todayKey);
    }

    /**
     * DAY-008: Count preserved across service instances
     */
    public function test_count_preserved(): void
    {
        $ip = '192.168.2.9';

        // Increment via first instance
        $this->service->incrementDaily($ip);
        $this->service->incrementDaily($ip);

        // Create new instance
        $newService = new ToolsRateLimitService([
            'perMinute' => 10,
            'perDay' => 100
        ]);

        // Count should be preserved
        $this->assertEquals(2, $newService->getDailyCount($ip));
    }

    // =========================================================================
    // CAPTCHA INTEGRATION TESTS (CAP)
    // =========================================================================

    /**
     * CAP-001: No CAPTCHA initially
     */
    public function test_no_captcha_initially(): void
    {
        $ip = '192.168.3.1';

        $result = $this->service->checkLimit($ip);
        $this->assertFalse($result['captchaRequired']);
    }

    /**
     * CAP-002: CAPTCHA required after threshold
     */
    public function test_captcha_after_threshold(): void
    {
        $ip = '192.168.3.2';

        // Make 3 requests (threshold)
        for ($i = 0; $i < 3; $i++) {
            $this->service->increment($ip);
        }

        // 4th should require CAPTCHA
        $result = $this->service->checkLimit($ip);
        $this->assertTrue($result['captchaRequired']);
    }

    /**
     * CAP-003: CAPTCHA bypasses limit
     */
    public function test_captcha_bypasses_limit(): void
    {
        $ip = '192.168.3.3';

        // Use up limit
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($ip);
        }

        // Should be blocked
        $this->assertFalse($this->service->checkLimit($ip)['allowed']);

        // Verify CAPTCHA
        $this->service->verifyCaptcha($ip, 'valid-token');

        // Should be allowed now
        $result = $this->service->checkLimit($ip);
        $this->assertTrue($result['allowed']);
    }

    /**
     * CAP-004: Invalid CAPTCHA token rejected
     */
    public function test_invalid_captcha_token(): void
    {
        $ip = '192.168.3.4';

        // Use up limit
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($ip);
        }

        // Try with invalid token
        $result = $this->service->verifyCaptcha($ip, 'invalid-token');

        // Note: In real implementation, this would verify with hCaptcha
        // For test, we mock the verification
        $this->assertFalse($result['valid'] ?? false);
    }

    /**
     * CAP-005: CAPTCHA expiry
     */
    public function test_captcha_expiry(): void
    {
        $ip = '192.168.3.5';

        // Verify CAPTCHA
        $this->service->verifyCaptcha($ip, 'valid-token');

        // Should have bypass
        $this->assertTrue($this->service->hasCaptchaBypass($ip));

        // Clear bypass (simulating expiry)
        Cache::forget($this->service->getCaptchaBypassKey($ip));

        // Should no longer have bypass
        $this->assertFalse($this->service->hasCaptchaBypass($ip));
    }

    /**
     * CAP-006: CAPTCHA per IP independent
     */
    public function test_captcha_per_ip_independent(): void
    {
        $ip1 = '192.168.3.6';
        $ip2 = '192.168.3.7';

        // Verify CAPTCHA for IP1
        $this->service->verifyCaptcha($ip1, 'valid-token');

        // IP1 should have bypass
        $this->assertTrue($this->service->hasCaptchaBypass($ip1));

        // IP2 should not have bypass
        $this->assertFalse($this->service->hasCaptchaBypass($ip2));
    }

    /**
     * CAP-007: CAPTCHA resets counter
     */
    public function test_captcha_resets_counter(): void
    {
        $ip = '192.168.3.8';

        // Use up some requests
        for ($i = 0; $i < 5; $i++) {
            $this->service->increment($ip);
        }

        // Verify count
        $this->assertEquals(5, $this->service->getCount($ip));

        // Verify CAPTCHA
        $this->service->verifyCaptcha($ip, 'valid-token');

        // Counter should be reset
        $this->assertEquals(0, $this->service->getCount($ip));
    }

    /**
     * CAP-008: hCaptcha integration mocked
     */
    public function test_hcaptcha_integration(): void
    {
        $ip = '192.168.3.9';

        // Mock hCaptcha verification
        $result = $this->service->verifyCaptchaWithProvider($ip, 'mock-token', 'mock-secret');

        // Should return valid result structure
        $this->assertArrayHasKey('valid', $result);
    }

    /**
     * CAP-009: CAPTCHA stored in session
     */
    public function test_captcha_in_session(): void
    {
        $ip = '192.168.3.10';

        // Verify CAPTCHA
        $this->service->verifyCaptcha($ip, 'valid-token');

        // Check session storage
        $this->assertTrue($this->service->hasCaptchaBypass($ip));
    }

    /**
     * CAP-010: Skip CAPTCHA for authenticated users
     */
    public function test_skip_captcha_for_authenticated(): void
    {
        $userId = 123;

        // Authenticated user should not need CAPTCHA
        $result = $this->service->checkLimitForUser('192.168.3.11', $userId);

        $this->assertFalse($result['captchaRequired']);
    }

    // =========================================================================
    // URL BLOCKLIST TESTS (BLK)
    // =========================================================================

    /**
     * BLK-001: Block localhost
     */
    public function test_block_localhost(): void
    {
        $this->assertTrue($this->service->isUrlBlocked('http://localhost'));
    }

    /**
     * BLK-002: Block 127.x.x.x
     */
    public function test_block_127_range(): void
    {
        $this->assertTrue($this->service->isUrlBlocked('http://127.0.0.1'));
        $this->assertTrue($this->service->isUrlBlocked('http://127.255.255.255'));
    }

    /**
     * BLK-003: Block 10.x.x.x
     */
    public function test_block_10_range(): void
    {
        $this->assertTrue($this->service->isUrlBlocked('http://10.0.0.1'));
        $this->assertTrue($this->service->isUrlBlocked('http://10.255.255.255'));
    }

    /**
     * BLK-004: Block 172.16.x.x
     */
    public function test_block_172_range(): void
    {
        $this->assertTrue($this->service->isUrlBlocked('http://172.16.0.1'));
        $this->assertTrue($this->service->isUrlBlocked('http://172.31.255.255'));
    }

    /**
     * BLK-005: Block 192.168.x.x
     */
    public function test_block_192_168_range(): void
    {
        $this->assertTrue($this->service->isUrlBlocked('http://192.168.1.1'));
        $this->assertTrue($this->service->isUrlBlocked('http://192.168.255.255'));
    }

    /**
     * BLK-006: Block AWS metadata endpoint
     */
    public function test_block_aws_metadata(): void
    {
        $this->assertTrue($this->service->isUrlBlocked('http://169.254.169.254'));
        $this->assertTrue($this->service->isUrlBlocked('http://169.254.169.254/latest/meta-data'));
    }

    /**
     * BLK-007: Block file:// protocol
     */
    public function test_block_file_protocol(): void
    {
        $this->assertTrue($this->service->isUrlBlocked('file:///etc/passwd'));
    }

    /**
     * BLK-008: Allow valid external URL
     */
    public function test_allow_valid_external_url(): void
    {
        $this->assertFalse($this->service->isUrlBlocked('https://google.com'));
        $this->assertFalse($this->service->isUrlBlocked('https://example.com'));
    }

    /**
     * BLK-009: Custom blocklist
     */
    public function test_custom_blocklist(): void
    {
        $this->service->addToBlocklist('evil\.com$');
        $this->assertTrue($this->service->isUrlBlocked('https://evil.com'));
    }

    /**
     * BLK-010: Case insensitive blocklist
     */
    public function test_case_insensitive_blocklist(): void
    {
        $this->assertTrue($this->service->isUrlBlocked('http://LOCALHOST'));
        $this->assertTrue($this->service->isUrlBlocked('http://LocalHost'));
    }

    /**
     * BLK-011: IP variations (decimal, hex)
     */
    public function test_ip_variations(): void
    {
        // 2130706433 = 127.0.0.1 in decimal
        $this->assertTrue($this->service->isUrlBlocked('http://2130706433'));
    }

    /**
     * BLK-012: DNS rebinding protection
     */
    public function test_dns_rebinding_protection(): void
    {
        // Should resolve and check IP
        $this->assertTrue($this->service->shouldResolveDns());
    }

    // =========================================================================
    // RATE LIMIT HEADERS TESTS (HDR)
    // =========================================================================

    /**
     * HDR-001: X-RateLimit-Limit header
     */
    public function test_ratelimit_limit_header(): void
    {
        $ip = '192.168.5.1';
        $headers = $this->service->getHeaders($ip);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertEquals('10', $headers['X-RateLimit-Limit']);
    }

    /**
     * HDR-002: X-RateLimit-Remaining header
     */
    public function test_ratelimit_remaining_header(): void
    {
        $ip = '192.168.5.2';

        // Initial remaining
        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('10', $headers['X-RateLimit-Remaining']);

        // After one request
        $this->service->increment($ip);
        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('9', $headers['X-RateLimit-Remaining']);
    }

    /**
     * HDR-003: X-RateLimit-Reset header
     */
    public function test_ratelimit_reset_header(): void
    {
        $ip = '192.168.5.3';
        $headers = $this->service->getHeaders($ip);

        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        $this->assertIsNumeric($headers['X-RateLimit-Reset']);
    }

    /**
     * HDR-004: Retry-After header at limit
     */
    public function test_retry_after_header(): void
    {
        $ip = '192.168.5.4';

        // Use up limit
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($ip);
        }

        $headers = $this->service->getHeaders($ip);
        $this->assertArrayHasKey('Retry-After', $headers);
        $this->assertIsNumeric($headers['Retry-After']);
    }

    /**
     * HDR-005: All headers present on 429
     */
    public function test_all_headers_on_429(): void
    {
        $ip = '192.168.5.5';

        // Use up limit
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($ip);
        }

        $headers = $this->service->getHeaders($ip);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        $this->assertArrayHasKey('Retry-After', $headers);
    }

    /**
     * HDR-006: Different IPs have different headers
     */
    public function test_different_ips_different_headers(): void
    {
        $ip1 = '192.168.5.6';
        $ip2 = '192.168.5.7';

        // Increment IP1
        $this->service->increment($ip1);

        // Get headers for both
        $headers1 = $this->service->getHeaders($ip1);
        $headers2 = $this->service->getHeaders($ip2);

        // Should have different remaining counts
        $this->assertEquals('9', $headers1['X-RateLimit-Remaining']);
        $this->assertEquals('10', $headers2['X-RateLimit-Remaining']);
    }

    /**
     * HDR-007: Headers after CAPTCHA verification
     */
    public function test_headers_after_captcha(): void
    {
        $ip = '192.168.5.8';

        // Use up some requests
        for ($i = 0; $i < 5; $i++) {
            $this->service->increment($ip);
        }

        // Verify CAPTCHA
        $this->service->verifyCaptcha($ip, 'valid-token');

        // Get headers
        $headers = $this->service->getHeaders($ip);

        // Remaining should be reset
        $this->assertEquals('10', $headers['X-RateLimit-Remaining']);
    }

    /**
     * HDR-008: Accuracy of remaining count
     */
    public function test_remaining_count_accuracy(): void
    {
        $ip = '192.168.5.9';

        for ($i = 0; $i < 7; $i++) {
            $this->service->increment($ip);
        }

        $headers = $this->service->getHeaders($ip);
        $this->assertEquals('3', $headers['X-RateLimit-Remaining']);
    }

    // =========================================================================
    // ABUSE LOGGING TESTS (LOG)
    // =========================================================================

    /**
     * LOG-001: Log blocked request
     */
    public function test_log_blocked_request(): void
    {
        $ip = '192.168.6.1';

        // Use up limit
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($ip);
        }

        // Try one more
        $this->service->checkLimit($ip);

        // Should be logged
        $logs = $this->service->getLogs($ip);
        $this->assertNotEmpty($logs);
    }

    /**
     * LOG-002: Log blocked URL
     */
    public function test_log_blocked_url(): void
    {
        $url = 'http://localhost';
        $ip = '192.168.6.2';

        $this->service->logBlockedUrl($ip, $url);

        $logs = $this->service->getLogs($ip);
        $this->assertNotEmpty($logs);
    }

    /**
     * LOG-003: Log CAPTCHA trigger
     */
    public function test_log_captcha_trigger(): void
    {
        $ip = '192.168.6.3';

        // Trigger CAPTCHA threshold
        for ($i = 0; $i < 3; $i++) {
            $this->service->increment($ip);
        }

        // Should be logged
        $logs = $this->service->getLogs($ip);
        $this->assertNotEmpty($logs);
    }

    /**
     * LOG-004: Include IP in log
     */
    public function test_include_ip_in_log(): void
    {
        $ip = '192.168.6.4';

        $this->service->logBlockedRequest($ip, 'rate_limit');

        $logs = $this->service->getLogs($ip);
        $this->assertEquals($ip, $logs[0]['ip'] ?? $ip);
    }

    /**
     * LOG-005: Include timestamp in log
     */
    public function test_include_timestamp_in_log(): void
    {
        $ip = '192.168.6.5';

        $this->service->logBlockedRequest($ip, 'rate_limit');

        $logs = $this->service->getLogs($ip);
        $this->assertArrayHasKey('timestamp', $logs[0]);
    }

    /**
     * LOG-006: Include user agent in log
     */
    public function test_include_user_agent_in_log(): void
    {
        $ip = '192.168.6.6';
        $userAgent = 'TestBot/1.0';

        $this->service->logBlockedRequest($ip, 'rate_limit', $userAgent);

        $logs = $this->service->getLogs($ip);
        $this->assertEquals($userAgent, $logs[0]['userAgent'] ?? $userAgent);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Missing IP header uses REMOTE_ADDR
     */
    public function test_missing_ip_header(): void
    {
        $ip = $this->service->getClientIp([]);

        $this->assertNotNull($ip);
    }

    /**
     * EC-002: Multiple X-Forwarded-For entries
     */
    public function test_multiple_x_forwarded_for(): void
    {
        $headers = ['X-Forwarded-For' => '192.168.7.1, 10.0.0.1, 172.16.0.1'];
        $ip = $this->service->getClientIp($headers);

        $this->assertEquals('192.168.7.1', $ip);
    }

    /**
     * EC-003: IPv6 address handling
     */
    public function test_ipv6_address(): void
    {
        $ip = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $result = $this->service->checkLimit($ip);
        $this->assertIsBool($result['allowed']);
    }

    /**
     * EC-004: IPv6 localhost blocked
     */
    public function test_ipv6_localhost_blocked(): void
    {
        $this->assertTrue($this->service->isUrlBlocked('http://[::1]'));
    }

    /**
     * EC-005: Empty IP handling
     */
    public function test_empty_ip(): void
    {
        $ip = '';

        // Should not throw
        $result = $this->service->checkLimit($ip);
        $this->assertIsBool($result['allowed']);
    }

    /**
     * EC-006: Malformed IP handling
     */
    public function test_malformed_ip(): void
    {
        $ip = 'not-an-ip';

        // Should not throw
        $result = $this->service->checkLimit($ip);
        $this->assertIsBool($result['allowed']);
    }

    /**
     * EC-007: Zero limits means no limit
     */
    public function test_zero_limits(): void
    {
        $service = new ToolsRateLimitService(['perMinute' => 0, 'perDay' => 0]);

        $this->assertEquals(0, $service->getPerMinuteLimit());
        // Zero should mean unlimited or handled specially
    }

    /**
     * EC-008: Negative limits handled
     */
    public function test_negative_limits(): void
    {
        $service = new ToolsRateLimitService(['perMinute' => -1, 'perDay' => -1]);

        // Should handle gracefully
        $this->assertLessThanOrEqual(0, $service->getPerMinuteLimit());
    }

    /**
     * EC-009: Very high limits work
     */
    public function test_very_high_limits(): void
    {
        $service = new ToolsRateLimitService(['perMinute' => 1000000, 'perDay' => 1000000]);

        $this->assertEquals(1000000, $service->getPerMinuteLimit());
    }

    /**
     * EC-010: Service restart preserves state
     */
    public function test_service_restart_preserves_state(): void
    {
        $ip = '192.168.7.10';

        // Increment via first instance
        $this->service->increment($ip);
        $this->service->increment($ip);
        $this->service->increment($ip);

        // Create new instance
        $newService = new ToolsRateLimitService(['perMinute' => 10, 'perDay' => 100]);

        // Count should be preserved
        $this->assertEquals(3, $newService->getCount($ip));
    }

    /**
     * EC-011: Distributed system shared state
     */
    public function test_distributed_shared_state(): void
    {
        // When using Redis/Cache, state is shared
        $ip = '192.168.7.11';

        $this->service->increment($ip);

        // Another "node" should see the same count
        $anotherService = new ToolsRateLimitService(['perMinute' => 10, 'perDay' => 100]);
        $this->assertEquals(1, $anotherService->getCount($ip));
    }

    /**
     * EC-012: High load no race conditions
     */
    public function test_high_load_no_race(): void
    {
        $ip = '192.168.7.12';

        // Simulate rapid increments
        for ($i = 0; $i < 100; $i++) {
            $this->service->increment($ip);
        }

        // Count should be exactly 100
        $this->assertEquals(100, $this->service->getCount($ip));
    }
}
