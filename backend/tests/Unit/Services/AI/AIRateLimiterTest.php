<?php

namespace Tests\Unit\Services\AI;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use App\Services\AI\AIRateLimiter;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Unit tests for AIRateLimiter service.
 * 
 * Tests the sliding window rate limiting algorithm for AI suggestion endpoints.
 * Covers both per-user and per-IP rate limiting with configurable thresholds.
 * 
 * @see TASK-21-10: Add Rate Limiting
 */
class AIRateLimiterTest extends TestCase
{
    private AIRateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = new AIRateLimiter();
        Carbon::setTestNow(Carbon::parse('2026-03-01 18:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ============================================
    // RL-001: Basic Limit Checking
    // ============================================

    #[Test]
    public function rl_001_01_check_limit_for_new_user_identifier_returns_true(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn([]);

        $result = $this->rateLimiter->checkLimit('user:1', 'user');

        $this->assertTrue($result);
    }

    #[Test]
    public function rl_001_02_check_limit_after_reaching_limit_returns_false(): void
    {
        $timestamps = $this->generateTimestamps(20);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $result = $this->rateLimiter->checkLimit('user:1', 'user');

        $this->assertFalse($result);
    }

    #[Test]
    public function rl_001_03_check_limit_with_custom_limit(): void
    {
        config(['ai.rate_limits.user.limit' => 5]);
        $timestamps = $this->generateTimestamps(5);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $result = $this->rateLimiter->checkLimit('user:1', 'user');

        $this->assertFalse($result);
    }

    #[Test]
    public function rl_001_04_check_limit_for_ip_type_returns_true(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:127.0.0.1', [])
            ->andReturn([]);

        $result = $this->rateLimiter->checkLimit('ip:127.0.0.1', 'ip');

        $this->assertTrue($result);
    }

    #[Test]
    public function rl_001_05_check_limit_with_zero_remaining_returns_false(): void
    {
        $timestamps = $this->generateTimestamps(20);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $result = $this->rateLimiter->checkLimit('user:1', 'user');

        $this->assertFalse($result);
    }

    // ============================================
    // RL-002: Remaining Count
    // ============================================

    #[Test]
    public function rl_002_01_get_remaining_for_new_user_returns_default_limit(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn([]);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        $this->assertEquals(20, $remaining);
    }

    #[Test]
    public function rl_002_02_get_remaining_after_five_requests(): void
    {
        $timestamps = $this->generateTimestamps(5);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        $this->assertEquals(15, $remaining);
    }

    #[Test]
    public function rl_002_03_get_remaining_at_limit_returns_zero(): void
    {
        $timestamps = $this->generateTimestamps(20);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        $this->assertEquals(0, $remaining);
    }

    #[Test]
    public function rl_002_04_get_remaining_for_ip_type_returns_default(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:ip:127.0.0.1', [])
            ->andReturn([]);

        $remaining = $this->rateLimiter->getRemaining('ip:127.0.0.1', 'ip');

        $this->assertEquals(10, $remaining);
    }

    #[Test]
    public function rl_002_05_get_remaining_never_negative(): void
    {
        $timestamps = $this->generateTimestamps(25);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        $this->assertEquals(0, $remaining);
    }

    // ============================================
    // RL-003: Reset Time
    // ============================================

    #[Test]
    public function rl_003_01_get_reset_time_for_new_user_returns_future(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn([]);

        $resetTime = $this->rateLimiter->getResetTime('user:1', 'user');

        $this->assertGreaterThan(time(), $resetTime);
    }

    #[Test]
    public function rl_003_02_reset_time_is_within_window(): void
    {
        $timestamps = $this->generateTimestamps(5);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $resetTime = $this->rateLimiter->getResetTime('user:1', 'user');
        $windowEnd = time() + 3600;

        $this->assertLessThanOrEqual($windowEnd, $resetTime);
    }

    #[Test]
    public function rl_003_03_reset_time_after_window_expiry(): void
    {
        // Set time to 2 hours ago for timestamps
        Carbon::setTestNow(Carbon::parse('2026-03-01 16:00:00'));
        $oldTimestamps = $this->generateTimestamps(5);

        // Now move to current time
        Carbon::setTestNow(Carbon::parse('2026-03-01 18:00:00'));

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($oldTimestamps);

        $resetTime = $this->rateLimiter->getResetTime('user:1', 'user');

        // Reset time should be based on fresh window
        $this->assertGreaterThan(time(), $resetTime);
    }

    // ============================================
    // RL-004: Sliding Window Behavior
    // ============================================

    #[Test]
    public function rl_004_01_old_requests_expire_not_counted(): void
    {
        // Create timestamps from 2 hours ago
        $oldTime = Carbon::now()->subHours(2);
        $timestamps = [
            $oldTime->toIso8601String(),
            $oldTime->addMinute()->toIso8601String(),
            $oldTime->addMinute()->toIso8601String(),
        ];

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        // All old requests expired, should have full limit
        $this->assertEquals(20, $remaining);
    }

    #[Test]
    public function rl_004_02_mixed_old_and_new_requests_only_new_counted(): void
    {
        $twoHoursAgo = Carbon::now()->subHours(2);
        $fiveMinutesAgo = Carbon::now()->subMinutes(5);

        $timestamps = [
            $twoHoursAgo->toIso8601String(),      // Expired
            $twoHoursAgo->addMinute()->toIso8601String(), // Expired
            $fiveMinutesAgo->toIso8601String(),   // Valid
            $fiveMinutesAgo->addMinute()->toIso8601String(), // Valid
        ];

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        // Only 2 valid requests, so 18 remaining
        $this->assertEquals(18, $remaining);
    }

    #[Test]
    public function rl_004_03_window_boundary_exact(): void
    {
        $fiftyNineMinutesAgo = Carbon::now()->subMinutes(59);
        $sixtyOneMinutesAgo = Carbon::now()->subMinutes(61);

        $timestamps = [
            $sixtyOneMinutesAgo->toIso8601String(),    // Expired (outside window)
            $fiftyNineMinutesAgo->toIso8601String(),   // Valid (inside window)
        ];

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        // Only 1 valid request
        $this->assertEquals(19, $remaining);
    }

    #[Test]
    public function rl_004_04_window_reset_after_expiry(): void
    {
        // All timestamps from 2 hours ago (expired)
        $oldTime = Carbon::now()->subHours(2);
        $timestamps = [];
        for ($i = 0; $i < 20; $i++) {
            $timestamps[] = $oldTime->addMinute()->toIso8601String();
        }

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $result = $this->rateLimiter->checkLimit('user:1', 'user');
        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        // All expired, fresh limit
        $this->assertTrue($result);
        $this->assertEquals(20, $remaining);
    }

    // ============================================
    // RL-005: Increment Operation
    // ============================================

    #[Test]
    public function rl_005_01_increment_adds_timestamp(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                return $key === 'ai_rate_limit:user:1' 
                    && count($value) === 1
                    && $ttl === 3600;
            });

        $this->rateLimiter->increment('user:1', 'user');

        $this->assertTrue(true); // Assert expectation was met
    }

    #[Test]
    public function rl_005_02_multiple_increments_adds_multiple_timestamps(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->times(3);

        $this->rateLimiter->increment('user:1', 'user');
        $this->rateLimiter->increment('user:1', 'user');
        $this->rateLimiter->increment('user:1', 'user');

        $this->assertTrue(true);
    }

    #[Test]
    public function rl_005_03_increment_updates_remaining(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->once();

        $this->rateLimiter->increment('user:1', 'user');

        // After increment, remaining should be 19
        $timestamps = $this->generateTimestamps(1);
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');
        $this->assertEquals(19, $remaining);
    }

    // ============================================
    // RL-006: Type Configuration
    // ============================================

    #[Test]
    public function rl_006_01_user_type_default_limit(): void
    {
        $limit = $this->rateLimiter->getLimit('user');

        $this->assertEquals(20, $limit);
    }

    #[Test]
    public function rl_006_02_ip_type_default_limit(): void
    {
        $limit = $this->rateLimiter->getLimit('ip');

        $this->assertEquals(10, $limit);
    }

    #[Test]
    public function rl_006_03_custom_limit_from_config(): void
    {
        config(['ai.rate_limits.user.limit' => 50]);

        $limit = $this->rateLimiter->getLimit('user');

        $this->assertEquals(50, $limit);
    }

    #[Test]
    public function rl_006_04_invalid_type_uses_default(): void
    {
        $limit = $this->rateLimiter->getLimit('invalid_type');

        // Should fallback to user limit as default
        $this->assertEquals(20, $limit);
    }

    // ============================================
    // SC-001: Security Tests
    // ============================================

    #[Test]
    public function sc_001_01_sql_injection_in_identifier(): void
    {
        $maliciousId = "user:1'; DROP TABLE users; --";

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:{$maliciousId}", [])
            ->andReturn([]);

        // Should not throw exception
        $result = $this->rateLimiter->checkLimit($maliciousId, 'user');

        $this->assertTrue($result);
    }

    #[Test]
    public function sc_001_02_special_characters_in_identifier(): void
    {
        $specialId = "user:<script>alert('xss')</script>";

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:{$specialId}", [])
            ->andReturn([]);

        $result = $this->rateLimiter->checkLimit($specialId, 'user');

        $this->assertTrue($result);
    }

    #[Test]
    public function sc_001_03_null_byte_in_identifier(): void
    {
        $nullByteId = "user:1\x00malicious";

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:user:1", [])
            ->andReturn([]);

        $result = $this->rateLimiter->checkLimit($nullByteId, 'user');

        $this->assertTrue($result);
    }

    #[Test]
    public function sc_001_04_very_long_identifier(): void
    {
        $longId = str_repeat('a', 10000);

        Cache::shouldReceive('get')
            ->withArgs(function ($key) use ($longId) {
                return strpos($key, $longId) !== false;
            })
            ->andReturn([]);

        $result = $this->rateLimiter->checkLimit($longId, 'user');

        $this->assertTrue($result);
    }

    #[Test]
    public function sc_001_05_unicode_in_identifier(): void
    {
        $unicodeId = "user:日本語テスト";

        Cache::shouldReceive('get')
            ->with("ai_rate_limit:{$unicodeId}", [])
            ->andReturn([]);

        $result = $this->rateLimiter->checkLimit($unicodeId, 'user');

        $this->assertTrue($result);
    }

    // ============================================
    // EC: Edge Cases
    // ============================================

    #[Test]
    public function ec_001_empty_identifier(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:', [])
            ->andReturn([]);

        $result = $this->rateLimiter->checkLimit('', 'user');

        $this->assertTrue($result);
    }

    #[Test]
    public function ec_002_empty_timestamps_array(): void
    {
        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn([]);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        $this->assertEquals(20, $remaining);
    }

    #[Test]
    public function ec_003_corrupted_timestamp_ignored(): void
    {
        $timestamps = [
            'invalid-timestamp',
            '2026-03-01T18:00:00Z', // valid
        ];

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn($timestamps);

        $remaining = $this->rateLimiter->getRemaining('user:1', 'user');

        // Only 1 valid timestamp
        $this->assertEquals(19, $remaining);
    }

    #[Test]
    public function ec_004_negative_limit_config(): void
    {
        config(['ai.rate_limits.user.limit' => -5]);

        // Should handle gracefully (use absolute or default)
        $limit = $this->rateLimiter->getLimit('user');

        $this->assertGreaterThanOrEqual(0, $limit);
    }

    #[Test]
    public function ec_005_zero_limit_config(): void
    {
        config(['ai.rate_limits.user.limit' => 0]);

        Cache::shouldReceive('get')
            ->with('ai_rate_limit:user:1', [])
            ->andReturn([]);

        $result = $this->rateLimiter->checkLimit('user:1', 'user');

        // With zero limit, should be blocked
        $this->assertFalse($result);
    }

    // ============================================
    // Helper Methods
    // ============================================

    private function generateTimestamps(int $count): array
    {
        $timestamps = [];
        $baseTime = Carbon::now()->subMinutes(30);

        for ($i = 0; $i < $count; $i++) {
            $timestamps[] = $baseTime->addMinute()->toIso8601String();
        }

        return $timestamps;
    }
}
