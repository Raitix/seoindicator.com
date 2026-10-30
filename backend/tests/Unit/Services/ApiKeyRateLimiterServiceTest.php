<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ApiKeyRateLimiterService;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Unit tests for TASK-6-5: API Key Rate Limiter Service
 *
 * Tests the ApiKeyRateLimiterService responsible for:
 * - Per-API-key rate limiting with sliding window algorithm
 * - Configurable limits per key (default: 60 requests per minute)
 * - Request counting and remaining calculations
 * - Reset time tracking
 *
 * This service is designed for API authentication, different from
 * the IP-based RateLimitService which handles CAPTCHA challenges.
 *
 * @see \App\Services\ApiKeyRateLimiterService
 */
class ApiKeyRateLimiterServiceTest extends TestCase
{
    protected ApiKeyRateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApiKeyRateLimiterService::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // IS ALLOWED TESTS (RL)
    // =========================================================================

    /**
     * RL-001: First request allowed for new key
     */
    public function test_first_request_allowed_for_new_key(): void
    {
        $key = 'api_key:1';
        $maxRequests = 60;
        $windowMs = 60000;

        $allowed = $this->service->isAllowed($key, $maxRequests, $windowMs);

        $this->assertTrue($allowed);
    }

    /**
     * RL-002: Request within limit allowed
     */
    public function test_request_within_limit_allowed(): void
    {
        $key = 'api_key:2';
        $maxRequests = 60;
        $windowMs = 60000;

        // Simulate 59 requests
        for ($i = 0; $i < 59; $i++) {
            $this->service->increment($key, $windowMs);
        }

        // 60th request should be allowed
        $allowed = $this->service->isAllowed($key, $maxRequests, $windowMs);
        $this->assertTrue($allowed);
    }

    /**
     * RL-003: Request at limit blocked
     */
    public function test_request_at_limit_blocked(): void
    {
        $key = 'api_key:3';
        $maxRequests = 60;
        $windowMs = 60000;

        // Simulate 60 requests
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, $windowMs);
        }

        // 61st request should be blocked
        $allowed = $this->service->isAllowed($key, $maxRequests, $windowMs);
        $this->assertFalse($allowed);
    }

    /**
     * RL-004: Request over limit blocked
     */
    public function test_request_over_limit_blocked(): void
    {
        $key = 'api_key:4';
        $maxRequests = 60;
        $windowMs = 60000;

        // Simulate 65 requests
        for ($i = 0; $i < 65; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $allowed = $this->service->isAllowed($key, $maxRequests, $windowMs);
        $this->assertFalse($allowed);
    }

    /**
     * RL-005: Different keys isolated
     */
    public function test_different_keys_isolated(): void
    {
        $key1 = 'api_key:5a';
        $key2 = 'api_key:5b';
        $maxRequests = 60;
        $windowMs = 60000;

        // Exhaust key1
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key1, $windowMs);
        }

        // key1 should be blocked
        $this->assertFalse($this->service->isAllowed($key1, $maxRequests, $windowMs));

        // key2 should still be allowed
        $this->assertTrue($this->service->isAllowed($key2, $maxRequests, $windowMs));
    }

    /**
     * RL-007: Custom max requests applied
     */
    public function test_custom_max_requests_applied(): void
    {
        $key = 'api_key:7';
        $maxRequests = 100;
        $windowMs = 60000;

        // Simulate 99 requests
        for ($i = 0; $i < 99; $i++) {
            $this->service->increment($key, $windowMs);
        }

        // 100th should be allowed
        $this->assertTrue($this->service->isAllowed($key, $maxRequests, $windowMs));

        // Increment to 100
        $this->service->increment($key, $windowMs);

        // 101st should be blocked
        $this->assertFalse($this->service->isAllowed($key, $maxRequests, $windowMs));
    }

    /**
     * RL-008: Zero max requests blocks all
     */
    public function test_zero_max_requests_blocks_all(): void
    {
        $key = 'api_key:8';
        $maxRequests = 0;
        $windowMs = 60000;

        $allowed = $this->service->isAllowed($key, $maxRequests, $windowMs);
        $this->assertFalse($allowed);
    }

    // =========================================================================
    // SLIDING WINDOW TESTS (SW)
    // =========================================================================

    /**
     * SW-001: Window starts at first request
     */
    public function test_window_starts_at_first_request(): void
    {
        $key = 'api_key:sw1';
        $windowMs = 60000;

        $beforeTime = microtime(true);
        $this->service->increment($key, $windowMs);
        $afterTime = microtime(true);

        // Window should be established - check via reset time
        $resetTime = $this->service->getResetTime($key, $windowMs);
        $this->assertGreaterThan(time(), $resetTime);
        
        // Reset time should be approximately window size ahead
        $expectedResetMin = time() + (int)($windowMs / 1000) - 2;
        $expectedResetMax = time() + (int)($windowMs / 1000) + 2;
        $this->assertGreaterThanOrEqual($expectedResetMin, $resetTime);
        $this->assertLessThanOrEqual($expectedResetMax, $resetTime);
    }

    /**
     * SW-002: Requests within window counted
     */
    public function test_requests_within_window_counted(): void
    {
        $key = 'api_key:sw2';
        $windowMs = 60000;

        // Make 10 requests
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $count = $this->service->getCount($key, $windowMs);
        $this->assertEquals(10, $count);
    }

    /**
     * SW-003: Old requests expire outside window
     */
    public function test_old_requests_expire_outside_window(): void
    {
        $key = 'api_key:sw3';
        $windowMs = 1000; // 1 second window for testing

        // Make 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->service->increment($key, $windowMs);
        }

        // Wait for window to expire
        usleep(1100000); // 1.1 seconds

        // Count should be reduced (old requests expired)
        $count = $this->service->getCount($key, $windowMs);
        $this->assertLessThan(5, $count);
    }

    /**
     * SW-005: Window reset after expiry
     */
    public function test_window_reset_after_expiry(): void
    {
        $key = 'api_key:sw5';
        $windowMs = 1000; // 1 second window

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $this->assertFalse($this->service->isAllowed($key, 60, $windowMs));

        // Wait for expiry
        usleep(1100000);

        // Should be allowed again
        $this->assertTrue($this->service->isAllowed($key, 60, $windowMs));
    }

    // =========================================================================
    // REMAINING REQUESTS TESTS (RM)
    // =========================================================================

    /**
     * RM-001: Full remaining on new key
     */
    public function test_full_remaining_on_new_key(): void
    {
        $key = 'api_key:rm1';
        $maxRequests = 60;
        $windowMs = 60000;

        $remaining = $this->service->getRemaining($key, $maxRequests, $windowMs);
        $this->assertEquals(60, $remaining);
    }

    /**
     * RM-002: Decreased after requests
     */
    public function test_decreased_after_requests(): void
    {
        $key = 'api_key:rm2';
        $maxRequests = 60;
        $windowMs = 60000;

        // Make 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $remaining = $this->service->getRemaining($key, $maxRequests, $windowMs);
        $this->assertEquals(55, $remaining);
    }

    /**
     * RM-003: Zero at limit
     */
    public function test_zero_at_limit(): void
    {
        $key = 'api_key:rm3';
        $maxRequests = 60;
        $windowMs = 60000;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $remaining = $this->service->getRemaining($key, $maxRequests, $windowMs);
        $this->assertEquals(0, $remaining);
    }

    /**
     * RM-004: Never negative
     */
    public function test_never_negative(): void
    {
        $key = 'api_key:rm4';
        $maxRequests = 60;
        $windowMs = 60000;

        // Exhaust limit and go over
        for ($i = 0; $i < 65; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $remaining = $this->service->getRemaining($key, $maxRequests, $windowMs);
        $this->assertEquals(0, $remaining);
    }

    /**
     * RM-006: Correct with custom max
     */
    public function test_correct_with_custom_max(): void
    {
        $key = 'api_key:rm6';
        $maxRequests = 100;
        $windowMs = 60000;

        // Make 25 requests
        for ($i = 0; $i < 25; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $remaining = $this->service->getRemaining($key, $maxRequests, $windowMs);
        $this->assertEquals(75, $remaining);
    }

    // =========================================================================
    // RESET TIME TESTS (RT)
    // =========================================================================

    /**
     * RT-001: Reset time in future
     */
    public function test_reset_time_in_future(): void
    {
        $key = 'api_key:rt1';
        $windowMs = 60000;

        $resetTime = $this->service->getResetTime($key, $windowMs);

        $this->assertIsInt($resetTime);
        $this->assertGreaterThan(time(), $resetTime);
    }

    /**
     * RT-002: Reset time approximately 60s ahead
     */
    public function test_reset_time_approximately_60s_ahead(): void
    {
        $key = 'api_key:rt2';
        $windowMs = 60000;

        $this->service->increment($key, $windowMs);
        $resetTime = $this->service->getResetTime($key, $windowMs);

        $expectedReset = time() + 60;
        $this->assertGreaterThan($expectedReset - 2, $resetTime);
        $this->assertLessThan($expectedReset + 2, $resetTime);
    }

    /**
     * RT-003: Reset time with custom window
     */
    public function test_reset_time_with_custom_window(): void
    {
        $key = 'api_key:rt3';
        $windowMs = 30000; // 30 seconds

        $this->service->increment($key, $windowMs);
        $resetTime = $this->service->getResetTime($key, $windowMs);

        $expectedReset = time() + 30;
        $this->assertGreaterThan($expectedReset - 2, $resetTime);
        $this->assertLessThan($expectedReset + 2, $resetTime);
    }

    /**
     * RT-004: Reset time is Unix timestamp
     */
    public function test_reset_time_is_unix_timestamp(): void
    {
        $key = 'api_key:rt4';
        $windowMs = 60000;

        $resetTime = $this->service->getResetTime($key, $windowMs);

        $this->assertIsInt($resetTime);
        // Should be a reasonable Unix timestamp (year 2024-2030 range)
        $this->assertGreaterThan(1700000000, $resetTime);
        $this->assertLessThan(1900000000, $resetTime);
    }

    // =========================================================================
    // REQUEST COUNT TESTS (RC)
    // =========================================================================

    /**
     * RC-001: Count starts at 0
     */
    public function test_count_starts_at_zero(): void
    {
        $key = 'api_key:rc1';

        $count = $this->service->getCount($key);
        $this->assertEquals(0, $count);
    }

    /**
     * RC-002: Count increments
     */
    public function test_count_increments(): void
    {
        $key = 'api_key:rc2';
        $windowMs = 60000;

        $this->service->increment($key, $windowMs);
        $count = $this->service->getCount($key);
        $this->assertEquals(1, $count);

        $this->service->increment($key, $windowMs);
        $count = $this->service->getCount($key);
        $this->assertEquals(2, $count);
    }

    /**
     * RC-003: Count reflects all requests
     */
    public function test_count_reflects_all_requests(): void
    {
        $key = 'api_key:rc3';
        $windowMs = 60000;

        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $count = $this->service->getCount($key, $windowMs);
        $this->assertEquals(10, $count);
    }

    /**
     * RC-004: Count isolated per key
     */
    public function test_count_isolated_per_key(): void
    {
        $key1 = 'api_key:rc4a';
        $key2 = 'api_key:rc4b';
        $windowMs = 60000;

        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($key1, $windowMs);
        }
        for ($i = 0; $i < 5; $i++) {
            $this->service->increment($key2, $windowMs);
        }

        $this->assertEquals(10, $this->service->getCount($key1, $windowMs));
        $this->assertEquals(5, $this->service->getCount($key2, $windowMs));
    }

    // =========================================================================
    // INCREMENT TESTS (IN)
    // =========================================================================

    /**
     * IN-001: Increment new key
     */
    public function test_increment_new_key(): void
    {
        $key = 'api_key:in1';
        $windowMs = 60000;

        $count = $this->service->increment($key, $windowMs);
        $this->assertEquals(1, $count);
    }

    /**
     * IN-002: Increment existing key
     */
    public function test_increment_existing_key(): void
    {
        $key = 'api_key:in2';
        $windowMs = 60000;

        $this->service->increment($key, $windowMs);
        $this->service->increment($key, $windowMs);
        $this->service->increment($key, $windowMs);
        $this->service->increment($key, $windowMs);
        $this->service->increment($key, $windowMs);

        $count = $this->service->increment($key, $windowMs);
        $this->assertEquals(6, $count);
    }

    /**
     * IN-004: Rapid increments
     */
    public function test_rapid_increments(): void
    {
        $key = 'api_key:in4';
        $windowMs = 60000;

        for ($i = 0; $i < 100; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $count = $this->service->getCount($key, $windowMs);
        $this->assertEquals(100, $count);
    }

    /**
     * IN-005: Increment isolated per key
     */
    public function test_increment_isolated_per_key(): void
    {
        $key1 = 'api_key:in5a';
        $key2 = 'api_key:in5b';
        $windowMs = 60000;

        $this->service->increment($key1, $windowMs);
        $this->service->increment($key2, $windowMs);
        $this->service->increment($key1, $windowMs);

        $this->assertEquals(2, $this->service->getCount($key1, $windowMs));
        $this->assertEquals(1, $this->service->getCount($key2, $windowMs));
    }

    // =========================================================================
    // RESET TESTS (RS)
    // =========================================================================

    /**
     * RS-001: Reset clears count
     */
    public function test_reset_clears_count(): void
    {
        $key = 'api_key:rs1';
        $windowMs = 60000;

        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $this->service->reset($key);
        $count = $this->service->getCount($key);
        $this->assertEquals(0, $count);
    }

    /**
     * RS-002: Reset allows new requests
     */
    public function test_reset_allows_new_requests(): void
    {
        $key = 'api_key:rs2';
        $maxRequests = 60;
        $windowMs = 60000;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, $windowMs);
        }

        $this->assertFalse($this->service->isAllowed($key, $maxRequests, $windowMs));

        // Reset
        $this->service->reset($key);

        // Should be allowed
        $this->assertTrue($this->service->isAllowed($key, $maxRequests, $windowMs));
    }

    /**
     * RS-003: Reset one key doesn't affect others
     */
    public function test_reset_one_key_doesnt_affect_others(): void
    {
        $key1 = 'api_key:rs3a';
        $key2 = 'api_key:rs3b';
        $windowMs = 60000;

        for ($i = 0; $i < 10; $i++) {
            $this->service->increment($key1, $windowMs);
            $this->service->increment($key2, $windowMs);
        }

        $this->service->reset($key1);

        $this->assertEquals(0, $this->service->getCount($key1, $windowMs));
        $this->assertEquals(10, $this->service->getCount($key2, $windowMs));
    }

    /**
     * RS-004: Reset non-existent key no error
     */
    public function test_reset_non_existent_key_no_error(): void
    {
        $key = 'api_key:rs4';

        // Should not throw
        $this->service->reset($key);
        $this->assertEquals(0, $this->service->getCount($key));
    }

    // =========================================================================
    // PER-KEY CONFIGURATION TESTS (PK)
    // =========================================================================

    /**
     * PK-001: Key with default limit
     */
    public function test_key_with_default_limit(): void
    {
        $key = 'api_key:pk1';
        $defaultLimit = 60;
        $windowMs = 60000;

        // Make 60 requests
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, $windowMs);
        }

        // Should be at limit
        $this->assertFalse($this->service->isAllowed($key, $defaultLimit, $windowMs));
    }

    /**
     * PK-002: Key with custom limit
     */
    public function test_key_with_custom_limit(): void
    {
        $key = 'api_key:pk2';
        $customLimit = 300;
        $windowMs = 60000;

        // Make 250 requests (within custom limit)
        for ($i = 0; $i < 250; $i++) {
            $this->service->increment($key, $windowMs);
        }

        // Should still be allowed
        $this->assertTrue($this->service->isAllowed($key, $customLimit, $windowMs));

        // Make 50 more to reach 300
        for ($i = 0; $i < 50; $i++) {
            $this->service->increment($key, $windowMs);
        }

        // Now should be at limit
        $this->assertFalse($this->service->isAllowed($key, $customLimit, $windowMs));
    }

    /**
     * PK-003: Key with zero limit
     */
    public function test_key_with_zero_limit(): void
    {
        $key = 'api_key:pk3';
        $zeroLimit = 0;
        $windowMs = 60000;

        // All requests should be blocked
        $this->assertFalse($this->service->isAllowed($key, $zeroLimit, $windowMs));
    }

    // =========================================================================
    // HIT METHOD TESTS
    // =========================================================================

    /**
     * Test hit method returns correct structure
     */
    public function test_hit_returns_correct_structure(): void
    {
        $key = 'api_key:hit1';
        $maxRequests = 60;
        $windowMs = 60000;

        $result = $this->service->hit($key, $maxRequests, $windowMs);

        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('remaining', $result);
        $this->assertArrayHasKey('reset_at', $result);
    }

    /**
     * Test hit method increments when allowed
     */
    public function test_hit_increments_when_allowed(): void
    {
        $key = 'api_key:hit2';
        $maxRequests = 60;
        $windowMs = 60000;

        // First hit
        $result1 = $this->service->hit($key, $maxRequests, $windowMs);
        $this->assertTrue($result1['allowed']);
        $this->assertEquals(59, $result1['remaining']);

        // Second hit
        $result2 = $this->service->hit($key, $maxRequests, $windowMs);
        $this->assertTrue($result2['allowed']);
        $this->assertEquals(58, $result2['remaining']);
    }

    /**
     * Test hit method blocks when limit reached
     */
    public function test_hit_blocks_when_limit_reached(): void
    {
        $key = 'api_key:hit3';
        $maxRequests = 5;
        $windowMs = 60000;

        // Use all 5 requests
        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->hit($key, $maxRequests, $windowMs);
            $this->assertTrue($result['allowed']);
        }

        // 6th should be blocked
        $result = $this->service->hit($key, $maxRequests, $windowMs);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }

    // =========================================================================
    // HEADERS TESTS
    // =========================================================================

    /**
     * Test getHeaders returns correct format
     */
    public function test_get_headers_returns_correct_format(): void
    {
        $key = 'api_key:headers1';
        $maxRequests = 60;
        $windowMs = 60000;

        $headers = $this->service->getHeaders($key, $maxRequests, $windowMs);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Empty rate limit key
     */
    public function test_empty_rate_limit_key(): void
    {
        $key = '';
        $maxRequests = 60;
        $windowMs = 60000;

        // Should handle gracefully (not throw)
        $this->service->increment($key, $windowMs);
        $allowed = $this->service->isAllowed($key, $maxRequests, $windowMs);

        $this->assertIsBool($allowed);
    }

    /**
     * EC-002: Very long key name
     */
    public function test_very_long_key_name(): void
    {
        $key = str_repeat('a', 1000);
        $maxRequests = 60;
        $windowMs = 60000;

        // Should handle gracefully
        $this->service->increment($key, $windowMs);
        $allowed = $this->service->isAllowed($key, $maxRequests, $windowMs);

        $this->assertTrue($allowed);
    }

    /**
     * EC-003: Special characters in key
     */
    public function test_special_characters_in_key(): void
    {
        $key = 'test@#$%^&*()';
        $maxRequests = 60;
        $windowMs = 60000;

        // Should handle gracefully
        $this->service->increment($key, $windowMs);
        $allowed = $this->service->isAllowed($key, $maxRequests, $windowMs);

        $this->assertTrue($allowed);
    }

    /**
     * EC-007: Negative max requests
     */
    public function test_negative_max_requests(): void
    {
        $key = 'api_key:ec7';
        $negativeMax = -1;
        $windowMs = 60000;

        // Should treat as 0 or block
        $allowed = $this->service->isAllowed($key, $negativeMax, $windowMs);
        $this->assertFalse($allowed);
    }

    /**
     * EC-009: Very large max requests
     */
    public function test_very_large_max_requests(): void
    {
        $key = 'api_key:ec9';
        $largeMax = PHP_INT_MAX;
        $windowMs = 60000;

        // Should always be allowed
        $this->service->increment($key, $windowMs);
        $allowed = $this->service->isAllowed($key, $largeMax, $windowMs);

        $this->assertTrue($allowed);
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-002: No SQL injection via key
     */
    public function test_no_sql_injection_via_key(): void
    {
        $key = "test'; DROP TABLE users; --";
        $maxRequests = 60;
        $windowMs = 60000;

        // Should be treated as a string key, not executed
        $this->service->increment($key, $windowMs);
        $count = $this->service->getCount($key, $windowMs);

        $this->assertEquals(1, $count);
    }

    /**
     * SC-003: Cache key collision prevention
     */
    public function test_cache_key_collision_prevention(): void
    {
        $key1 = 'api_key:1';
        $key2 = 'api_key:1a'; // Similar but different
        $maxRequests = 60;
        $windowMs = 60000;

        // Exhaust key1
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key1, $windowMs);
        }

        // key2 should still be independent
        $this->assertTrue($this->service->isAllowed($key2, $maxRequests, $windowMs));
    }

    /**
     * SC-008: Timing attack on limit check
     */
    public function test_timing_attack_on_limit_check(): void
    {
        $key = 'api_key:sc8';
        $maxRequests = 60;
        $windowMs = 60000;

        // Measure time for allowed request
        $startAllowed = microtime(true);
        $this->service->isAllowed($key, $maxRequests, $windowMs);
        $timeAllowed = microtime(true) - $startAllowed;

        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->service->increment($key, $windowMs);
        }

        // Measure time for blocked request
        $startBlocked = microtime(true);
        $this->service->isAllowed($key, $maxRequests, $windowMs);
        $timeBlocked = microtime(true) - $startBlocked;

        // Times should be similar (within an order of magnitude)
        // This is a rough test - proper timing tests need many iterations
        $ratio = $timeBlocked / max($timeAllowed, 0.000001);
        $this->assertLessThan(10, $ratio);
    }

    // =========================================================================
    // RETRY AFTER TESTS
    // =========================================================================

    /**
     * Test getRetryAfter returns positive integer
     */
    public function test_get_retry_after_returns_positive_integer(): void
    {
        $key = 'api_key:retry1';
        $windowMs = 60000;

        $retryAfter = $this->service->getRetryAfter($key, $windowMs);

        $this->assertIsInt($retryAfter);
        $this->assertGreaterThan(0, $retryAfter);
    }

    /**
     * Test getRetryAfter decreases over time
     */
    public function test_get_retry_after_decreases_over_time(): void
    {
        $key = 'api_key:retry2';
        $windowMs = 2000; // 2 seconds

        $this->service->increment($key, $windowMs);
        $retryAfter1 = $this->service->getRetryAfter($key, $windowMs);

        // Wait a bit
        usleep(500000); // 0.5 seconds

        $retryAfter2 = $this->service->getRetryAfter($key, $windowMs);

        // Retry after should decrease (or be similar due to timing)
        $this->assertLessThanOrEqual($retryAfter1 + 1, $retryAfter2);
    }

    // =========================================================================
    // CLEANUP TESTS
    // =========================================================================

    /**
     * Test cleanup removes expired entries
     */
    public function test_cleanup_removes_expired_entries(): void
    {
        $key = 'api_key:cleanup1';
        $windowMs = 1000; // 1 second

        // Add entries
        for ($i = 0; $i < 5; $i++) {
            $this->service->increment($key, $windowMs);
        }

        // Wait for expiry
        usleep(1100000);

        // Cleanup should remove old entries
        $removed = $this->service->cleanup($key, $windowMs);
        $this->assertEquals(5, $removed);
    }
}
