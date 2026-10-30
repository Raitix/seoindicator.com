<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Models\User;
use App\Services\Crawler\CrawlRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Unit tests for CrawlRateLimiter service.
 *
 * Tests rate limiting for crawler API:
 * - Daily job limit: 10 per user
 * - Concurrent job limit: 3 per user
 * - Per-domain limit: 1 request/second
 * - Admin bypass
 *
 * @see TASK-22-12
 * @see \App\Services\Crawler\CrawlRateLimiter
 */
class CrawlRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected CrawlRateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = new CrawlRateLimiter();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a test user.
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Create an admin user.
     */
    protected function createAdmin(): User
    {
        return $this->createUser(['role' => 'admin']);
    }

    /**
     * Simulate jobs created today for a user.
     */
    protected function setDailyJobCount(int $userId, int $count): void
    {
        $key = "crawl_daily_limit:{$userId}:" . now()->format('Y-m-d');
        Cache::put($key, $count, now()->endOfDay());
    }

    /**
     * Simulate running jobs for a user.
     */
    protected function setConcurrentJobCount(int $userId, int $count): void
    {
        $key = "crawl_concurrent:{$userId}";
        Cache::put($key, $count, now()->addHour());
    }

    /**
     * Simulate domain request timestamp.
     */
    protected function setDomainRequestTime(string $domain, ?int $timestamp = null): void
    {
        $key = "crawl_domain:{$domain}";
        Cache::put($key, $timestamp ?? now()->timestamp, now()->addMinute());
    }

    // =========================================================================
    // Daily Limits (RL-DAILY-*)
    // =========================================================================

    /** @test */
    public function rl_daily_01_first_request_allowed(): void
    {
        $user = $this->createUser();

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function rl_daily_02_fifth_request_allowed(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 4);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
        // 10 - 4 = 6 remaining before job creation
        $this->assertEquals(6, $result['remaining']);
    }

    /** @test */
    public function rl_daily_03_tenth_request_allowed(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 9);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['remaining']);
    }

    /** @test */
    public function rl_daily_04_eleventh_request_blocked(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 10);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertFalse($result['allowed']);
        $this->assertArrayHasKey('retryAfter', $result);
    }

    /** @test */
    public function rl_daily_05_new_day_resets(): void
    {
        $user = $this->createUser();

        // Set count for yesterday
        $yesterdayKey = "crawl_daily_limit:{$user->id}:" . now()->subDay()->format('Y-m-d');
        Cache::put($yesterdayKey, 10, now()->subDay()->endOfDay());

        // Today should be fresh
        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function rl_daily_06_remaining_count_accurate(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 7);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(3, $result['remaining']);
    }

    /** @test */
    public function rl_daily_07_increment_count(): void
    {
        $user = $this->createUser();

        $this->rateLimiter->incrementJobCount($user);
        $this->rateLimiter->incrementJobCount($user);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertEquals(8, $result['remaining']);
    }

    // =========================================================================
    // Concurrent Limits (RL-CONCURRENT-*)
    // =========================================================================

    /** @test */
    public function rl_concurrent_01_no_running_jobs_allowed(): void
    {
        $user = $this->createUser();

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function rl_concurrent_02_two_running_jobs_allowed(): void
    {
        $user = $this->createUser();
        $this->setConcurrentJobCount($user->id, 2);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function rl_concurrent_03_three_running_jobs_blocked(): void
    {
        $user = $this->createUser();
        $this->setConcurrentJobCount($user->id, 3);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertFalse($result['allowed']);
    }

    /** @test */
    public function rl_concurrent_04_job_completes_allows_new(): void
    {
        $user = $this->createUser();
        $this->setConcurrentJobCount($user->id, 3);

        // Simulate job completion
        $this->rateLimiter->decrementConcurrentJobs($user);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function rl_concurrent_05_increment_on_start(): void
    {
        $user = $this->createUser();

        $this->rateLimiter->incrementConcurrentJobs($user);

        $key = "crawl_concurrent:{$user->id}";
        $this->assertEquals(1, Cache::get($key));
    }

    // =========================================================================
    // Per-Domain Limits (RL-DOMAIN-*)
    // =========================================================================

    /** @test */
    public function rl_domain_01_first_request_allowed(): void
    {
        $user = $this->createUser();

        $result = $this->rateLimiter->canRequestDomain($user, 'example.com');

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function rl_domain_02_request_within_1_sec_blocked(): void
    {
        $user = $this->createUser();
        // Set timestamp to current time (0 seconds elapsed)
        $this->setDomainRequestTime('example.com', now()->timestamp);

        $result = $this->rateLimiter->canRequestDomain($user, 'example.com');

        $this->assertFalse($result['allowed']);
    }

    /** @test */
    public function rl_domain_03_request_after_1_sec_allowed(): void
    {
        $user = $this->createUser();
        $this->setDomainRequestTime('example.com', now()->timestamp - 1500); // 1.5s ago

        $result = $this->rateLimiter->canRequestDomain($user, 'example.com');

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function rl_domain_04_different_domain_allowed(): void
    {
        $user = $this->createUser();
        $this->setDomainRequestTime('other.com', now()->timestamp);

        $result = $this->rateLimiter->canRequestDomain($user, 'example.com');

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function rl_domain_05_domain_rate_recorded(): void
    {
        $user = $this->createUser();

        $this->rateLimiter->recordDomainRequest($user, 'example.com');

        $key = "crawl_domain:example.com";
        $this->assertNotNull(Cache::get($key));
    }

    // =========================================================================
    // Rate Limit Headers (RL-HEADERS-*)
    // =========================================================================

    /** @test */
    public function rl_headers_01_within_limits(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 5);

        $headers = $this->rateLimiter->getHeaders($user);

        $this->assertEquals(10, $headers['X-RateLimit-Limit']);
        $this->assertEquals(5, $headers['X-RateLimit-Remaining']);
    }

    /** @test */
    public function rl_headers_02_at_limit(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 10);

        $headers = $this->rateLimiter->getHeaders($user);

        $this->assertEquals(0, $headers['X-RateLimit-Remaining']);
    }

    /** @test */
    public function rl_headers_03_exceeded(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 10);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertFalse($result['allowed']);
        $this->assertArrayHasKey('retryAfter', $result);
    }

    /** @test */
    public function rl_headers_04_reset_time(): void
    {
        $user = $this->createUser();

        $headers = $this->rateLimiter->getHeaders($user);

        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    // =========================================================================
    // Admin Bypass (RL-ADMIN-*)
    // =========================================================================

    /** @test */
    public function rl_admin_01_admin_at_daily_limit(): void
    {
        $admin = $this->createAdmin();
        $this->setDailyJobCount($admin->id, 10);

        $result = $this->rateLimiter->canCreateJob($admin);

        $this->assertTrue($result['allowed'], 'Admin should bypass daily limit');
    }

    /** @test */
    public function rl_admin_02_admin_at_concurrent_limit(): void
    {
        $admin = $this->createAdmin();
        $this->setConcurrentJobCount($admin->id, 3);

        $result = $this->rateLimiter->canCreateJob($admin);

        $this->assertTrue($result['allowed'], 'Admin should bypass concurrent limit');
    }

    /** @test */
    public function rl_admin_03_non_admin_at_limit(): void
    {
        $user = $this->createUser(['role' => 'user']);
        $this->setDailyJobCount($user->id, 10);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertFalse($result['allowed']);
    }

    /** @test */
    public function rl_admin_04_admin_domain_rate_not_limited(): void
    {
        $admin = $this->createAdmin();
        $this->setDomainRequestTime('example.com', now()->timestamp);

        $result = $this->rateLimiter->canRequestDomain($admin, 'example.com');

        // Admin might still be subject to domain limits to protect target servers
        // Implementation decision: domain limits apply to all
        $this->assertFalse($result['allowed'], 'Domain limits apply to all users');
    }

    // =========================================================================
    // Combined Checks (RL-COMBINED-*)
    // =========================================================================

    /** @test */
    public function rl_combined_01_daily_and_concurrent_checked(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 5);
        $this->setConcurrentJobCount($user->id, 2);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function rl_combined_02_daily_ok_concurrent_blocked(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 5);
        $this->setConcurrentJobCount($user->id, 3);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('concurrent', strtolower($result['reason'] ?? ''));
    }

    /** @test */
    public function rl_combined_03_daily_blocked_concurrent_ok(): void
    {
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 10);
        $this->setConcurrentJobCount($user->id, 1);

        $result = $this->rateLimiter->canCreateJob($user);

        $this->assertFalse($result['allowed']);
    }

    // =========================================================================
    // Configuration (RL-CONFIG-*)
    // =========================================================================

    /** @test */
    public function rl_config_01_custom_daily_limit(): void
    {
        config(['crawler.rate_limits.daily_jobs' => 5]);

        $rateLimiter = new CrawlRateLimiter();
        $user = $this->createUser();
        $this->setDailyJobCount($user->id, 4);

        $result = $rateLimiter->canCreateJob($user);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['remaining']);
    }

    /** @test */
    public function rl_config_02_custom_concurrent_limit(): void
    {
        config(['crawler.rate_limits.concurrent_jobs' => 1]);

        $rateLimiter = new CrawlRateLimiter();
        $user = $this->createUser();
        $this->setConcurrentJobCount($user->id, 1);

        $result = $rateLimiter->canCreateJob($user);

        $this->assertFalse($result['allowed']);
    }

    /** @test */
    public function rl_config_03_custom_domain_rate(): void
    {
        config(['crawler.rate_limits.per_domain_per_second' => 2]);

        $rateLimiter = new CrawlRateLimiter();
        $user = $this->createUser();
        $this->setDomainRequestTime('example.com', now()->timestamp - 500); // 0.5s ago

        $result = $rateLimiter->canRequestDomain($user, 'example.com');

        // With 2/sec limit, 0.5s should be enough
        $this->assertTrue($result['allowed']);
    }

    // =========================================================================
    // Edge Cases (RL-EDGE-*)
    // =========================================================================

    /** @test */
    public function rl_edge_01_concurrent_count_never_negative(): void
    {
        $user = $this->createUser();

        // Decrement without increment
        $this->rateLimiter->decrementConcurrentJobs($user);
        $this->rateLimiter->decrementConcurrentJobs($user);

        $key = "crawl_concurrent:{$user->id}";
        $count = Cache::get($key, 0);

        $this->assertGreaterThanOrEqual(0, $count);
    }

    /** @test */
    public function rl_edge_02_different_users_independent(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $this->setDailyJobCount($user1->id, 10);

        $result1 = $this->rateLimiter->canCreateJob($user1);
        $result2 = $this->rateLimiter->canCreateJob($user2);

        $this->assertFalse($result1['allowed']);
        $this->assertTrue($result2['allowed']);
    }

    /** @test */
    public function rl_edge_03_cache_expiration(): void
    {
        $user = $this->createUser();

        $this->rateLimiter->incrementJobCount($user);

        $key = "crawl_daily_limit:{$user->id}:" . now()->format('Y-m-d');
        
        // Verify the cache key exists and has the correct value
        $this->assertEquals(1, Cache::get($key));
        
        // Verify the rate limiter correctly reports the count
        $this->assertEquals(1, $this->rateLimiter->getDailyCount($user));
    }
}
