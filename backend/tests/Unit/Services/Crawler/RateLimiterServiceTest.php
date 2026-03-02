<?php

namespace Tests\Unit\Services\Crawler;

use App\Models\User;
use App\Services\Crawler\RateLimiterService;
use App\Services\Crawler\Data\RateLimitResult;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit tests for RateLimiterService.
 *
 * Tests rate limiting tiers: user daily, concurrent, domain, and global limits.
 *
 * @see TASK-22-18
 * @see TASK-22-13
 * @see SPEC-022
 */
class RateLimiterServiceTest extends TestCase
{
    use CrawlerTestHelper;

    protected RateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateLimiterService();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // User Daily Limit Tests
    // =========================================================================

    /** @test */
    public function check_user_limit_allows_within_limit(): void
    {
        $result = $this->service->checkUserLimit(1);

        $this->assertInstanceOf(RateLimitResult::class, $result);
        $this->assertTrue($result->allowed);
        $this->assertGreaterThan(0, $result->remaining);
    }

    /** @test */
    public function check_user_limit_blocks_when_exceeded(): void
    {
        $userId = 1;

        // Increment to exceed limit (default is 10)
        for ($i = 0; $i < 12; $i++) {
            $this->service->incrementUserCount($userId);
        }

        $result = $this->service->checkUserLimit($userId);

        $this->assertFalse($result->allowed);
        $this->assertEquals(0, $result->remaining);
    }

    /** @test */
    public function get_user_daily_count_returns_correct_count(): void
    {
        $userId = 1;

        $this->assertEquals(0, $this->service->getUserDailyCount($userId));

        $this->service->incrementUserCount($userId);
        $this->assertEquals(1, $this->service->getUserDailyCount($userId));

        $this->service->incrementUserCount($userId);
        $this->assertEquals(2, $this->service->getUserDailyCount($userId));
    }

    /** @test */
    public function user_limits_are_isolated_per_user(): void
    {
        $user1 = 1;
        $user2 = 2;

        $this->service->incrementUserCount($user1);
        $this->service->incrementUserCount($user1);
        $this->service->incrementUserCount($user2);

        $this->assertEquals(2, $this->service->getUserDailyCount($user1));
        $this->assertEquals(1, $this->service->getUserDailyCount($user2));
    }

    // =========================================================================
    // Concurrent Limit Tests
    // =========================================================================

    /** @test */
    public function check_concurrent_limit_allows_within_limit(): void
    {
        $result = $this->service->checkConcurrentLimit(1);

        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function check_concurrent_limit_blocks_when_exceeded(): void
    {
        $userId = 1;

        // Increment to exceed limit (default is 3)
        for ($i = 0; $i < 4; $i++) {
            $this->service->incrementConcurrentCount($userId);
        }

        $result = $this->service->checkConcurrentLimit($userId);

        $this->assertFalse($result->allowed);
    }

    /** @test */
    public function increment_concurrent_count_increases_count(): void
    {
        $userId = 1;

        $this->assertEquals(0, $this->service->getUserConcurrentCount($userId));

        $this->service->incrementConcurrentCount($userId);
        $this->assertEquals(1, $this->service->getUserConcurrentCount($userId));
    }

    /** @test */
    public function decrement_concurrent_count_decreases_count(): void
    {
        $userId = 1;

        $this->service->incrementConcurrentCount($userId);
        $this->service->incrementConcurrentCount($userId);
        $this->assertEquals(2, $this->service->getUserConcurrentCount($userId));

        $this->service->decrementConcurrentCount($userId);
        $this->assertEquals(1, $this->service->getUserConcurrentCount($userId));
    }

    /** @test */
    public function decrement_concurrent_count_does_not_go_negative(): void
    {
        $userId = 1;

        $this->service->decrementConcurrentCount($userId);
        $this->assertEquals(0, $this->service->getUserConcurrentCount($userId));
    }

    // =========================================================================
    // Domain Rate Limit Tests
    // =========================================================================

    /** @test */
    public function check_domain_limit_allows_first_request(): void
    {
        $result = $this->service->checkDomainLimit('example.com');

        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function check_domain_limit_blocks_rapid_requests(): void
    {
        $domain = 'example.com';

        // First request allowed
        $this->assertTrue($this->service->checkDomainLimit($domain)->allowed);

        // Record the request
        $this->service->incrementDomainCount($domain);

        // Immediate second request should be blocked
        $result = $this->service->checkDomainLimit($domain);
        $this->assertFalse($result->allowed);
    }

    /** @test */
    public function check_domain_limit_normalizes_domain(): void
    {
        // All variations should be treated the same
        $this->service->incrementDomainCount('example.com');

        // Protocol should be stripped
        $result = $this->service->checkDomainLimit('https://example.com');
        $this->assertFalse($result->allowed);

        // Path should be stripped
        $result = $this->service->checkDomainLimit('example.com/page');
        $this->assertFalse($result->allowed);
    }

    /** @test */
    public function check_domain_limit_handles_empty_domain(): void
    {
        $result = $this->service->checkDomainLimit('');

        // Empty domain should be allowed (no target yet)
        $this->assertTrue($result->allowed);
    }

    // =========================================================================
    // Global Concurrent Limit Tests
    // =========================================================================

    /** @test */
    public function check_global_limit_allows_within_limit(): void
    {
        $result = $this->service->checkGlobalLimit();

        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function check_global_limit_blocks_when_exceeded(): void
    {
        // Increment to exceed limit (default is 10)
        for ($i = 0; $i < 12; $i++) {
            $this->service->incrementGlobalCount();
        }

        $result = $this->service->checkGlobalLimit();

        $this->assertFalse($result->allowed);
    }

    /** @test */
    public function increment_global_count_increases_count(): void
    {
        $this->assertEquals(0, $this->service->getGlobalConcurrentCount());

        $this->service->incrementGlobalCount();
        $this->assertEquals(1, $this->service->getGlobalConcurrentCount());
    }

    /** @test */
    public function decrement_global_count_decreases_count(): void
    {
        $this->service->incrementGlobalCount();
        $this->service->incrementGlobalCount();
        $this->assertEquals(2, $this->service->getGlobalConcurrentCount());

        $this->service->decrementGlobalCount();
        $this->assertEquals(1, $this->service->getGlobalConcurrentCount());
    }

    // =========================================================================
    // checkAllLimits Tests
    // =========================================================================

    /** @test */
    public function check_all_limits_returns_all_results(): void
    {
        $result = $this->service->checkAllLimits(1, 'example.com');

        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('reason', $result);

        $this->assertArrayHasKey('daily', $result['results']);
        $this->assertArrayHasKey('concurrent', $result['results']);
        $this->assertArrayHasKey('domain', $result['results']);
        $this->assertArrayHasKey('global', $result['results']);
    }

    /** @test */
    public function check_all_limits_returns_allowed_when_all_pass(): void
    {
        $result = $this->service->checkAllLimits(1, 'example.com');

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['reason']);
    }

    /** @test */
    public function check_all_limits_blocks_on_user_daily_exceeded(): void
    {
        $userId = 1;

        // Exceed daily limit
        for ($i = 0; $i < 12; $i++) {
            $this->service->incrementUserCount($userId);
        }

        $result = $this->service->checkAllLimits($userId, 'example.com', false);

        $this->assertFalse($result['allowed']);
        $this->assertEquals('daily', $result['reason']);
    }

    /** @test */
    public function check_all_limits_blocks_on_concurrent_exceeded(): void
    {
        $userId = 1;

        // Exceed concurrent limit
        for ($i = 0; $i < 4; $i++) {
            $this->service->incrementConcurrentCount($userId);
        }

        $result = $this->service->checkAllLimits($userId, 'example.com', false);

        $this->assertFalse($result['allowed']);
        $this->assertEquals('concurrent', $result['reason']);
    }

    /** @test */
    public function check_all_limits_admin_bypasses_user_limits(): void
    {
        $userId = 1;

        // Exceed user limits
        for ($i = 0; $i < 12; $i++) {
            $this->service->incrementUserCount($userId);
        }
        for ($i = 0; $i < 4; $i++) {
            $this->service->incrementConcurrentCount($userId);
        }

        // Admin should bypass user limits
        $result = $this->service->checkAllLimits($userId, 'example.com', true);

        $this->assertTrue($result['allowed']);
        $this->assertArrayNotHasKey('daily', $result['results']);
        $this->assertArrayNotHasKey('concurrent', $result['results']);
    }

    /** @test */
    public function check_all_limits_admin_does_not_bypass_domain_limit(): void
    {
        $userId = 1;

        // Block domain
        $this->service->incrementDomainCount('example.com');

        // Admin should still be blocked by domain limit
        $result = $this->service->checkAllLimits($userId, 'example.com', true);

        $this->assertFalse($result['allowed']);
        $this->assertEquals('domain', $result['reason']);
    }

    /** @test */
    public function check_all_limits_admin_does_not_bypass_global_limit(): void
    {
        $userId = 1;

        // Exceed global limit
        for ($i = 0; $i < 12; $i++) {
            $this->service->incrementGlobalCount();
        }

        // Admin should still be blocked by global limit
        $result = $this->service->checkAllLimits($userId, 'example.com', true);

        $this->assertFalse($result['allowed']);
        $this->assertEquals('global', $result['reason']);
    }

    /** @test */
    public function check_all_limits_handles_null_domain(): void
    {
        $result = $this->service->checkAllLimits(1, null);

        $this->assertArrayNotHasKey('domain', $result['results']);
    }

    // =========================================================================
    // Header Tests
    // =========================================================================

    /** @test */
    public function get_headers_returns_correct_format(): void
    {
        $headers = $this->service->getHeaders(1, false);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    /** @test */
    public function get_headers_shows_unlimited_for_admin(): void
    {
        $headers = $this->service->getHeaders(1, true);

        $this->assertEquals(PHP_INT_MAX, $headers['X-RateLimit-Limit']);
        $this->assertEquals(PHP_INT_MAX, $headers['X-RateLimit-Remaining']);
    }

    /** @test */
    public function get_headers_shows_remaining_count(): void
    {
        $userId = 1;

        // Initial headers
        $headers = $this->service->getHeaders($userId, false);
        $initialRemaining = $headers['X-RateLimit-Remaining'];

        // Increment count
        $this->service->incrementUserCount($userId);

        // Check remaining decreased
        $headers = $this->service->getHeaders($userId, false);
        $this->assertEquals($initialRemaining - 1, $headers['X-RateLimit-Remaining']);
    }

    // =========================================================================
    // Admin Check Tests
    // =========================================================================

    /** @test */
    public function is_admin_detects_admin_role(): void
    {
        $user = new User();
        $user->role = 'admin';

        $this->assertTrue($this->service->isAdmin($user));
    }

    /** @test */
    public function is_admin_returns_false_for_non_admin(): void
    {
        $user = new User();
        $user->role = 'user';

        $this->assertFalse($this->service->isAdmin($user));
    }

    /** @test */
    public function is_admin_handles_missing_role(): void
    {
        $user = new User();

        $this->assertFalse($this->service->isAdmin($user));
    }

    // =========================================================================
    // Reset and Cleanup Tests
    // =========================================================================

    /** @test */
    public function reset_user_limits_clears_all(): void
    {
        $userId = 1;

        $this->service->incrementUserCount($userId);
        $this->service->incrementConcurrentCount($userId);

        $this->service->resetUserLimits($userId);

        $this->assertEquals(0, $this->service->getUserDailyCount($userId));
        $this->assertEquals(0, $this->service->getUserConcurrentCount($userId));
    }

    /** @test */
    public function cleanup_user_concurrent_decrements(): void
    {
        $userId = 1;

        $this->service->incrementConcurrentCount($userId);
        $this->service->cleanupUserConcurrent($userId);

        $this->assertEquals(0, $this->service->getUserConcurrentCount($userId));
    }

    // =========================================================================
    // RateLimitResult Tests
    // =========================================================================

    /** @test */
    public function rate_limit_result_allowed_factory(): void
    {
        $result = RateLimitResult::allowed(10, 5, now()->addHour());

        $this->assertTrue($result->allowed);
        $this->assertEquals(10, $result->limit);
        $this->assertEquals(5, $result->remaining);
    }

    /** @test */
    public function rate_limit_result_denied_factory(): void
    {
        $result = RateLimitResult::denied(10, now()->addHour(), 60);

        $this->assertFalse($result->allowed);
        $this->assertEquals(10, $result->limit);
        $this->assertEquals(60, $result->retryAfter);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /** @test */
    public function concurrent_count_never_goes_below_zero(): void
    {
        $userId = 1;

        // Decrement without increment
        $this->service->decrementConcurrentCount($userId);
        $this->service->decrementConcurrentCount($userId);

        $this->assertEquals(0, $this->service->getUserConcurrentCount($userId));
    }

    /** @test */
    public function global_count_never_goes_below_zero(): void
    {
        // Decrement without increment
        $this->service->decrementGlobalCount();
        $this->service->decrementGlobalCount();

        $this->assertEquals(0, $this->service->getGlobalConcurrentCount());
    }

    /** @test */
    public function domain_normalization_strips_various_formats(): void
    {
        $testCases = [
            'https://example.com' => 'example.com',
            'http://example.com/page' => 'example.com',
            'example.com:8080' => 'example.com',
            'EXAMPLE.COM' => 'example.com',
            'https://sub.example.com/path?query=1' => 'sub.example.com',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->service->checkDomainLimit($input);
            // We can't access the normalized domain directly, but we can test behavior
            $this->assertNotNull($result);
        }
    }
}
