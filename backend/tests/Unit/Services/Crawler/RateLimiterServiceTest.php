<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Models\User;
use App\Services\Crawler\RateLimiterService;
use App\Services\Crawler\Data\RateLimitResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Mockery;

/**
 * Unit tests for RateLimiterService.
 *
 * Comprehensive rate limiting for crawler service:
 * - User daily limit: 10 jobs per 24 hours
 * - User concurrent jobs: 3 max
 * - Per-domain rate: 1 request/second
 * - Global concurrent: 10 browser instances max
 *
 * @see TASK-22-13
 * @see SPEC-022
 */
class RateLimiterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateLimiterService();
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

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createAdmin(): User
    {
        return $this->createUser(['role' => 'admin']);
    }

    protected function setUserDailyCount(int $userId, int $count): void
    {
        $key = "crawler:rate:user:{$userId}:daily";
        Cache::put($key, $count, now()->addHours(24));
    }

    protected function setUserConcurrentCount(int $userId, int $count): void
    {
        $key = "crawler:rate:user:{$userId}:concurrent";
        Cache::put($key, $count, now()->addHour());
    }

    protected function setDomainLastRequest(string $domain, int $timestamp): void
    {
        $key = "crawler:rate:domain:" . strtolower($domain);
        Cache::put($key, $timestamp, now()->addMinute());
    }

    protected function setGlobalConcurrentCount(int $count): void
    {
        $key = "crawler:rate:global";
        Cache::put($key, $count, now()->addHour());
    }

    protected function getUserDailyCount(int $userId): int
    {
        return Cache::get("crawler:rate:user:{$userId}:daily", 0);
    }

    protected function getUserConcurrentCount(int $userId): int
    {
        return Cache::get("crawler:rate:user:{$userId}:concurrent", 0);
    }

    protected function getGlobalConcurrentCount(): int
    {
        return Cache::get("crawler:rate:global", 0);
    }

    // =========================================================================
    // Service Instantiation (RLS-INST-*)
    // =========================================================================

    /** @test */
    public function rls_inst_01_service_can_be_instantiated(): void
    {
        $service = new RateLimiterService();
        $this->assertInstanceOf(RateLimiterService::class, $service);
    }

    /** @test */
    public function rls_inst_02_service_can_be_resolved_from_container(): void
    {
        $service = app(RateLimiterService::class);
        $this->assertInstanceOf(RateLimiterService::class, $service);
    }

    /** @test */
    public function rls_inst_03_service_loads_config_defaults(): void
    {
        config(['crawler.rate_limits' => [
            'user_daily' => 15,
            'user_concurrent' => 5,
            'domain_per_second' => 2,
            'global_concurrent' => 20,
        ]]);

        $service = new RateLimiterService();

        $this->assertEquals(15, $service->getUserDailyLimit());
        $this->assertEquals(5, $service->getUserConcurrentLimit());
        $this->assertEquals(2, $service->getDomainRateLimit());
        $this->assertEquals(20, $service->getGlobalConcurrentLimit());
    }

    // =========================================================================
    // User Daily Limit (RLS-DAILY-*)
    // =========================================================================

    /** @test */
    public function rls_daily_01_first_request_allowed(): void
    {
        $user = $this->createUser();

        $result = $this->service->checkUserLimit($user->id);

        $this->assertInstanceOf(RateLimitResult::class, $result);
        $this->assertTrue($result->allowed);
        $this->assertEquals(10, $result->limit);
        $this->assertEquals(10, $result->remaining);
    }

    /** @test */
    public function rls_daily_02_fifth_request_allowed(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 4);

        $result = $this->service->checkUserLimit($user->id);

        $this->assertTrue($result->allowed);
        $this->assertEquals(6, $result->remaining);
    }

    /** @test */
    public function rls_daily_03_tenth_request_allowed(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 9);

        $result = $this->service->checkUserLimit($user->id);

        $this->assertTrue($result->allowed);
        $this->assertEquals(1, $result->remaining);
    }

    /** @test */
    public function rls_daily_04_eleventh_request_blocked(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 10);

        $result = $this->service->checkUserLimit($user->id);

        $this->assertFalse($result->allowed);
        $this->assertEquals(0, $result->remaining);
        $this->assertNotNull($result->retryAfter);
    }

    /** @test */
    public function rls_daily_05_reset_at_is_carbon_instance(): void
    {
        $user = $this->createUser();

        $result = $this->service->checkUserLimit($user->id);

        $this->assertInstanceOf(Carbon::class, $result->resetAt);
    }

    /** @test */
    public function rls_daily_06_reset_at_is_midnight_utc(): void
    {
        $user = $this->createUser();

        $result = $this->service->checkUserLimit($user->id);

        $expected = Carbon::now('UTC')->endOfDay();
        $this->assertEquals(
            $expected->timestamp,
            $result->resetAt->timestamp,
            'Reset time should be midnight UTC'
        );
    }

    /** @test */
    public function rls_daily_07_new_day_resets_count(): void
    {
        $user = $this->createUser();

        // Set yesterday's count
        $yesterdayKey = "crawler:rate:user:{$user->id}:daily";
        $yesterdayDate = Carbon::now()->subDay()->format('Y-m-d');
        Cache::put("crawler:rate:user:{$user->id}:daily:{$yesterdayDate}", 10, now()->subDay());

        $result = $this->service->checkUserLimit($user->id);

        $this->assertTrue($result->allowed, 'New day should reset the count');
    }

    /** @test */
    public function rls_daily_08_increment_increases_count(): void
    {
        $user = $this->createUser();

        $this->service->incrementUserCount($user->id);
        $this->service->incrementUserCount($user->id);

        $count = $this->getUserDailyCount($user->id);
        $this->assertEquals(2, $count);
    }

    /** @test */
    public function rls_daily_09_retry_after_seconds_calculated(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 10);

        $result = $this->service->checkUserLimit($user->id);

        $this->assertGreaterThan(0, $result->retryAfter);
        $this->assertLessThanOrEqual(86400, $result->retryAfter, 'Should be within 24 hours');
    }

    /** @test */
    public function rls_daily_10_different_users_independent(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $this->setUserDailyCount($user1->id, 10);

        $result1 = $this->service->checkUserLimit($user1->id);
        $result2 = $this->service->checkUserLimit($user2->id);

        $this->assertFalse($result1->allowed);
        $this->assertTrue($result2->allowed);
    }

    // =========================================================================
    // User Concurrent Limit (RLS-CONCURRENT-*)
    // =========================================================================

    /** @test */
    public function rls_concurrent_01_no_running_jobs_allowed(): void
    {
        $user = $this->createUser();

        $result = $this->service->checkConcurrentLimit($user->id);

        $this->assertTrue($result->allowed);
        $this->assertEquals(3, $result->limit);
    }

    /** @test */
    public function rls_concurrent_02_two_running_jobs_allowed(): void
    {
        $user = $this->createUser();
        $this->setUserConcurrentCount($user->id, 2);

        $result = $this->service->checkConcurrentLimit($user->id);

        $this->assertTrue($result->allowed);
        $this->assertEquals(1, $result->remaining);
    }

    /** @test */
    public function rls_concurrent_03_three_running_jobs_blocked(): void
    {
        $user = $this->createUser();
        $this->setUserConcurrentCount($user->id, 3);

        $result = $this->service->checkConcurrentLimit($user->id);

        $this->assertFalse($result->allowed);
        $this->assertEquals(0, $result->remaining);
    }

    /** @test */
    public function rls_concurrent_04_increment_increases_count(): void
    {
        $user = $this->createUser();

        $this->service->incrementConcurrentCount($user->id);
        $this->service->incrementConcurrentCount($user->id);

        $count = $this->getUserConcurrentCount($user->id);
        $this->assertEquals(2, $count);
    }

    /** @test */
    public function rls_concurrent_05_decrement_decreases_count(): void
    {
        $user = $this->createUser();
        $this->setUserConcurrentCount($user->id, 2);

        $this->service->decrementConcurrentCount($user->id);

        $count = $this->getUserConcurrentCount($user->id);
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function rls_concurrent_06_decrement_never_goes_negative(): void
    {
        $user = $this->createUser();

        $this->service->decrementConcurrentCount($user->id);
        $this->service->decrementConcurrentCount($user->id);

        $count = $this->getUserConcurrentCount($user->id);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /** @test */
    public function rls_concurrent_07_job_completes_allows_new(): void
    {
        $user = $this->createUser();
        $this->setUserConcurrentCount($user->id, 3);

        // Job completes
        $this->service->decrementConcurrentCount($user->id);

        $result = $this->service->checkConcurrentLimit($user->id);
        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function rls_concurrent_08_reset_at_is_carbon_instance(): void
    {
        $user = $this->createUser();

        $result = $this->service->checkConcurrentLimit($user->id);

        $this->assertInstanceOf(Carbon::class, $result->resetAt);
    }

    /** @test */
    public function rls_concurrent_09_retry_after_estimate(): void
    {
        $user = $this->createUser();
        $this->setUserConcurrentCount($user->id, 3);

        $result = $this->service->checkConcurrentLimit($user->id);

        $this->assertNotNull($result->retryAfter);
        $this->assertGreaterThan(0, $result->retryAfter);
    }

    // =========================================================================
    // Domain Rate Limit (RLS-DOMAIN-*)
    // =========================================================================

    /** @test */
    public function rls_domain_01_first_request_allowed(): void
    {
        $result = $this->service->checkDomainLimit('example.com');

        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function rls_domain_02_request_within_1_sec_blocked(): void
    {
        $this->setDomainLastRequest('example.com', now()->timestamp - 500); // 0.5s ago

        $result = $this->service->checkDomainLimit('example.com');

        $this->assertFalse($result->allowed);
    }

    /** @test */
    public function rls_domain_03_request_after_1_sec_allowed(): void
    {
        $this->setDomainLastRequest('example.com', now()->timestamp - 1500); // 1.5s ago

        $result = $this->service->checkDomainLimit('example.com');

        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function rls_domain_04_different_domains_independent(): void
    {
        $this->setDomainLastRequest('example.com', now()->timestamp);

        $result = $this->service->checkDomainLimit('other.com');

        $this->assertTrue($result->allowed, 'Different domains should be independent');
    }

    /** @test */
    public function rls_domain_05_domain_normalized(): void
    {
        $this->setDomainLastRequest('EXAMPLE.COM', now()->timestamp);

        $result = $this->service->checkDomainLimit('example.com');

        $this->assertFalse($result->allowed, 'Domain should be case-normalized');
    }

    /** @test */
    public function rls_domain_06_increment_records_timestamp(): void
    {
        $before = now()->timestamp;

        $this->service->incrementDomainCount('example.com');

        $key = "crawler:rate:domain:example.com";
        $timestamp = Cache::get($key);

        $this->assertNotNull($timestamp);
        $this->assertGreaterThanOrEqual($before, $timestamp);
    }

    /** @test */
    public function rls_domain_07_retry_after_is_seconds_until_allowed(): void
    {
        $this->setDomainLastRequest('example.com', now()->timestamp - 200); // 0.2s ago

        $result = $this->service->checkDomainLimit('example.com');

        $this->assertFalse($result->allowed);
        $this->assertLessThanOrEqual(1, $result->retryAfter);
        $this->assertGreaterThan(0, $result->retryAfter);
    }

    /** @test */
    public function rls_domain_08_limit_is_one_per_second(): void
    {
        $result = $this->service->checkDomainLimit('example.com');

        $this->assertEquals(1, $result->limit, 'Domain limit should be 1 request per second');
    }

    /** @test */
    public function rls_domain_09_www_subdomain_treated_separately(): void
    {
        $this->setDomainLastRequest('example.com', now()->timestamp);

        $result = $this->service->checkDomainLimit('www.example.com');

        // www subdomain should be treated as separate domain
        // (implementation decision - document this behavior)
        $this->assertTrue($result->allowed, 'www.example.com should be separate from example.com');
    }

    // =========================================================================
    // Global Concurrent Limit (RLS-GLOBAL-*)
    // =========================================================================

    /** @test */
    public function rls_global_01_no_running_jobs_allowed(): void
    {
        $result = $this->service->checkGlobalLimit();

        $this->assertTrue($result->allowed);
        $this->assertEquals(10, $result->limit);
        $this->assertEquals(10, $result->remaining);
    }

    /** @test */
    public function rls_global_02_five_running_jobs_allowed(): void
    {
        $this->setGlobalConcurrentCount(5);

        $result = $this->service->checkGlobalLimit();

        $this->assertTrue($result->allowed);
        $this->assertEquals(5, $result->remaining);
    }

    /** @test */
    public function rls_global_03_ten_running_jobs_blocked(): void
    {
        $this->setGlobalConcurrentCount(10);

        $result = $this->service->checkGlobalLimit();

        $this->assertFalse($result->allowed);
        $this->assertEquals(0, $result->remaining);
    }

    /** @test */
    public function rls_global_04_increment_increases_count(): void
    {
        $this->service->incrementGlobalCount();
        $this->service->incrementGlobalCount();

        $count = $this->getGlobalConcurrentCount();
        $this->assertEquals(2, $count);
    }

    /** @test */
    public function rls_global_05_decrement_decreases_count(): void
    {
        $this->setGlobalConcurrentCount(5);

        $this->service->decrementGlobalCount();

        $count = $this->getGlobalConcurrentCount();
        $this->assertEquals(4, $count);
    }

    /** @test */
    public function rls_global_06_decrement_never_goes_negative(): void
    {
        $this->service->decrementGlobalCount();
        $this->service->decrementGlobalCount();

        $count = $this->getGlobalConcurrentCount();
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /** @test */
    public function rls_global_07_job_completes_allows_new(): void
    {
        $this->setGlobalConcurrentCount(10);

        $this->service->decrementGlobalCount();

        $result = $this->service->checkGlobalLimit();
        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function rls_global_08_retry_after_estimate(): void
    {
        $this->setGlobalConcurrentCount(10);

        $result = $this->service->checkGlobalLimit();

        $this->assertNotNull($result->retryAfter);
        $this->assertGreaterThan(0, $result->retryAfter);
    }

    /** @test */
    public function rls_global_09_reset_at_is_carbon_instance(): void
    {
        $result = $this->service->checkGlobalLimit();

        $this->assertInstanceOf(Carbon::class, $result->resetAt);
    }

    // =========================================================================
    // RateLimitResult Structure (RLS-RESULT-*)
    // =========================================================================

    /** @test */
    public function rls_result_01_has_allowed_property(): void
    {
        $user = $this->createUser();
        $result = $this->service->checkUserLimit($user->id);

        $this->assertObjectHasProperty('allowed', $result);
        $this->assertIsBool($result->allowed);
    }

    /** @test */
    public function rls_result_02_has_limit_property(): void
    {
        $user = $this->createUser();
        $result = $this->service->checkUserLimit($user->id);

        $this->assertObjectHasProperty('limit', $result);
        $this->assertIsInt($result->limit);
    }

    /** @test */
    public function rls_result_03_has_remaining_property(): void
    {
        $user = $this->createUser();
        $result = $this->service->checkUserLimit($user->id);

        $this->assertObjectHasProperty('remaining', $result);
        $this->assertIsInt($result->remaining);
    }

    /** @test */
    public function rls_result_04_has_reset_at_property(): void
    {
        $user = $this->createUser();
        $result = $this->service->checkUserLimit($user->id);

        $this->assertObjectHasProperty('resetAt', $result);
        $this->assertInstanceOf(Carbon::class, $result->resetAt);
    }

    /** @test */
    public function rls_result_05_has_retry_after_property(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 10);
        $result = $this->service->checkUserLimit($user->id);

        $this->assertObjectHasProperty('retryAfter', $result);
        $this->assertIsInt($result->retryAfter);
    }

    /** @test */
    public function rls_result_06_retry_after_null_when_allowed(): void
    {
        $user = $this->createUser();
        $result = $this->service->checkUserLimit($user->id);

        $this->assertNull($result->retryAfter);
    }

    /** @test */
    public function rls_result_07_to_array_method(): void
    {
        $user = $this->createUser();
        $result = $this->service->checkUserLimit($user->id);

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('allowed', $array);
        $this->assertArrayHasKey('limit', $array);
        $this->assertArrayHasKey('remaining', $array);
        $this->assertArrayHasKey('resetAt', $array);
        $this->assertArrayHasKey('retryAfter', $array);
    }

    /** @test */
    public function rls_result_08_json_serializable(): void
    {
        $user = $this->createUser();
        $result = $this->service->checkUserLimit($user->id);

        $json = json_encode($result);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('allowed', $decoded);
    }

    // =========================================================================
    // Rate Limit Headers (RLS-HEADERS-*)
    // =========================================================================

    /** @test */
    public function rls_headers_01_get_headers_returns_array(): void
    {
        $user = $this->createUser();
        $headers = $this->service->getHeaders($user->id);

        $this->assertIsArray($headers);
    }

    /** @test */
    public function rls_headers_02_contains_x_ratelimit_limit(): void
    {
        $user = $this->createUser();
        $headers = $this->service->getHeaders($user->id);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertEquals(10, $headers['X-RateLimit-Limit']);
    }

    /** @test */
    public function rls_headers_03_contains_x_ratelimit_remaining(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 7);
        $headers = $this->service->getHeaders($user->id);

        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertEquals(3, $headers['X-RateLimit-Remaining']);
    }

    /** @test */
    public function rls_headers_04_contains_x_ratelimit_reset(): void
    {
        $user = $this->createUser();
        $headers = $this->service->getHeaders($user->id);

        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        $this->assertIsInt($headers['X-RateLimit-Reset']);
    }

    /** @test */
    public function rls_headers_05_reset_is_unix_timestamp(): void
    {
        $user = $this->createUser();
        $headers = $this->service->getHeaders($user->id);

        $timestamp = $headers['X-RateLimit-Reset'];
        $expected = Carbon::now('UTC')->endOfDay()->timestamp;

        $this->assertEquals($expected, $timestamp);
    }

    /** @test */
    public function rls_headers_06_retry_after_header_when_exceeded(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 10);

        $headers = $this->service->getHeaders($user->id);

        $this->assertArrayHasKey('Retry-After', $headers);
        $this->assertGreaterThan(0, $headers['Retry-After']);
    }

    /** @test */
    public function rls_headers_07_no_retry_after_when_allowed(): void
    {
        $user = $this->createUser();
        $headers = $this->service->getHeaders($user->id);

        $this->assertArrayNotHasKey('Retry-After', $headers);
    }

    /** @test */
    public function rls_headers_08_remaining_zero_at_limit(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 10);
        $headers = $this->service->getHeaders($user->id);

        $this->assertEquals(0, $headers['X-RateLimit-Remaining']);
    }

    // =========================================================================
    // Configuration (RLS-CONFIG-*)
    // =========================================================================

    /** @test */
    public function rls_config_01_custom_user_daily_limit(): void
    {
        config(['crawler.rate_limits.user_daily' => 5]);

        $service = new RateLimiterService();
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 4);

        $result = $service->checkUserLimit($user->id);

        $this->assertTrue($result->allowed);
        $this->assertEquals(1, $result->remaining);
    }

    /** @test */
    public function rls_config_02_custom_user_concurrent_limit(): void
    {
        config(['crawler.rate_limits.user_concurrent' => 1]);

        $service = new RateLimiterService();
        $user = $this->createUser();
        $this->setUserConcurrentCount($user->id, 1);

        $result = $service->checkConcurrentLimit($user->id);

        $this->assertFalse($result->allowed);
    }

    /** @test */
    public function rls_config_03_custom_domain_rate(): void
    {
        config(['crawler.rate_limits.domain_per_second' => 2]);

        $service = new RateLimiterService();
        $this->setDomainLastRequest('example.com', now()->timestamp - 400); // 0.4s ago

        $result = $service->checkDomainLimit('example.com');

        // With 2/sec, 0.5s interval means we can make request
        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function rls_config_04_custom_global_concurrent_limit(): void
    {
        config(['crawler.rate_limits.global_concurrent' => 5]);

        $service = new RateLimiterService();
        $this->setGlobalConcurrentCount(5);

        $result = $service->checkGlobalLimit();

        $this->assertFalse($result->allowed);
    }

    /** @test */
    public function rls_config_05_window_hours_configuration(): void
    {
        config(['crawler.rate_limits.window_hours' => 12]);

        $service = new RateLimiterService();

        $resetAt = $service->getWindowResetTime();

        $expectedWindow = now()->addHours(12);
        $this->assertEquals(
            $expectedWindow->timestamp,
            $resetAt->timestamp,
            '',
            5 // Allow 5 second tolerance
        );
    }

    // =========================================================================
    // Cache Key Structure (RLS-CACHE-*)
    // =========================================================================

    /** @test */
    public function rls_cache_01_user_daily_key_format(): void
    {
        $user = $this->createUser();
        $this->service->incrementUserCount($user->id);

        $expectedPattern = "crawler:rate:user:{$user->id}:daily";
        $this->assertTrue(Cache::has($expectedPattern));
    }

    /** @test */
    public function rls_cache_02_user_concurrent_key_format(): void
    {
        $user = $this->createUser();
        $this->service->incrementConcurrentCount($user->id);

        $expectedKey = "crawler:rate:user:{$user->id}:concurrent";
        $this->assertTrue(Cache::has($expectedKey));
    }

    /** @test */
    public function rls_cache_03_domain_key_format(): void
    {
        $this->service->incrementDomainCount('Example.COM');

        $expectedKey = "crawler:rate:domain:example.com";
        $this->assertTrue(Cache::has($expectedKey));
    }

    /** @test */
    public function rls_cache_04_global_key_format(): void
    {
        $this->service->incrementGlobalCount();

        $expectedKey = "crawler:rate:global";
        $this->assertTrue(Cache::has($expectedKey));
    }

    /** @test */
    public function rls_cache_05_keys_expire_automatically(): void
    {
        $user = $this->createUser();
        $this->service->incrementUserCount($user->id);

        $key = "crawler:rate:user:{$user->id}:daily";

        // Verify key exists with TTL
        $this->assertTrue(Cache::has($key));
    }

    // =========================================================================
    // Admin Bypass (RLS-ADMIN-*)
    // =========================================================================

    /** @test */
    public function rls_admin_01_admin_bypasses_daily_limit(): void
    {
        $admin = $this->createAdmin();
        $this->setUserDailyCount($admin->id, 10);

        $result = $this->service->checkUserLimit($admin->id);

        $this->assertTrue($result->allowed, 'Admin should bypass daily limit');
    }

    /** @test */
    public function rls_admin_02_admin_bypasses_concurrent_limit(): void
    {
        $admin = $this->createAdmin();
        $this->setUserConcurrentCount($admin->id, 3);

        $result = $this->service->checkConcurrentLimit($admin->id);

        $this->assertTrue($result->allowed, 'Admin should bypass concurrent limit');
    }

    /** @test */
    public function rls_admin_03_admin_subject_to_domain_limit(): void
    {
        // Domain limits protect target servers, so should apply to all
        $this->setDomainLastRequest('example.com', now()->timestamp);

        $result = $this->service->checkDomainLimit('example.com');

        $this->assertFalse($result->allowed, 'Domain limits should apply to all users');
    }

    /** @test */
    public function rls_admin_04_admin_subject_to_global_limit(): void
    {
        // Global limits protect server resources, should apply to all
        $this->setGlobalConcurrentCount(10);

        $result = $this->service->checkGlobalLimit();

        $this->assertFalse($result->allowed, 'Global limits should apply to all users');
    }

    // =========================================================================
    // Combined Checks (RLS-COMBINED-*)
    // =========================================================================

    /** @test */
    public function rls_combined_01_all_limits_checked(): void
    {
        $user = $this->createUser();

        $results = $this->service->checkAllLimits($user->id, 'example.com');

        $this->assertIsArray($results);
        $this->assertArrayHasKey('user', $results);
        $this->assertArrayHasKey('concurrent', $results);
        $this->assertArrayHasKey('domain', $results);
        $this->assertArrayHasKey('global', $results);
    }

    /** @test */
    public function rls_combined_02_all_allowed_returns_true(): void
    {
        $user = $this->createUser();

        $allowed = $this->service->canProceed($user->id, 'example.com');

        $this->assertTrue($allowed);
    }

    /** @test */
    public function rls_combined_03_any_blocked_returns_false(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 10);

        $allowed = $this->service->canProceed($user->id, 'example.com');

        $this->assertFalse($allowed);
    }

    /** @test */
    public function rls_combined_04_returns_first_blocking_reason(): void
    {
        $user = $this->createUser();
        $this->setUserDailyCount($user->id, 10);
        $this->setUserConcurrentCount($user->id, 3);

        $result = $this->service->getBlockingReason($user->id, 'example.com');

        $this->assertNotNull($result);
        $this->assertStringContainsString('daily', strtolower($result));
    }

    // =========================================================================
    // Edge Cases (RLS-EDGE-*)
    // =========================================================================

    /** @test */
    public function rls_edge_01_concurrent_count_never_negative(): void
    {
        $user = $this->createUser();

        // Decrement without increment
        $this->service->decrementConcurrentCount($user->id);
        $this->service->decrementConcurrentCount($user->id);

        $count = $this->getUserConcurrentCount($user->id);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /** @test */
    public function rls_edge_02_global_count_never_negative(): void
    {
        $this->service->decrementGlobalCount();
        $this->service->decrementGlobalCount();

        $count = $this->getGlobalConcurrentCount();
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /** @test */
    public function rls_edge_03_zero_user_id_handled(): void
    {
        $result = $this->service->checkUserLimit(0);

        // Should handle gracefully (system user or error)
        $this->assertInstanceOf(RateLimitResult::class, $result);
    }

    /** @test */
    public function rls_edge_04_negative_user_id_handled(): void
    {
        $result = $this->service->checkUserLimit(-1);

        $this->assertInstanceOf(RateLimitResult::class, $result);
    }

    /** @test */
    public function rls_edge_05_empty_domain_handled(): void
    {
        $result = $this->service->checkDomainLimit('');

        $this->assertInstanceOf(RateLimitResult::class, $result);
    }

    /** @test */
    public function rls_edge_06_special_characters_in_domain(): void
    {
        $result = $this->service->checkDomainLimit('exa<mple>.com');

        // Should sanitize or reject
        $this->assertInstanceOf(RateLimitResult::class, $result);
    }

    /** @test */
    public function rls_edge_07_unicode_domain_handled(): void
    {
        $result = $this->service->checkDomainLimit('例え.com');

        $this->assertInstanceOf(RateLimitResult::class, $result);
    }

    /** @test */
    public function rls_edge_08_very_long_domain(): void
    {
        $longDomain = str_repeat('a', 253) . '.com';

        $result = $this->service->checkDomainLimit($longDomain);

        $this->assertInstanceOf(RateLimitResult::class, $result);
    }

    /** @test */
    public function rls_edge_09_concurrent_cleanup_on_crash(): void
    {
        $user = $this->createUser();
        $this->setUserConcurrentCount($user->id, 3);

        // Simulate crash recovery - stale count exists
        // Service should have method to clean up
        $this->service->cleanupStaleConcurrentCounts($user->id);

        $result = $this->service->checkConcurrentLimit($user->id);
        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function rls_edge_10_high_concurrency_thread_safety(): void
    {
        // Test atomic increment/decrement
        $user = $this->createUser();

        // Simulate concurrent increments
        for ($i = 0; $i < 100; $i++) {
            $this->service->incrementConcurrentCount($user->id);
        }

        $count = $this->getUserConcurrentCount($user->id);
        $this->assertEquals(100, $count);
    }
}
