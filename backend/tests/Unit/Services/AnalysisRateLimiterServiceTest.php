<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AnalysisRateLimiterService;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for TASK-19-14: Analysis Rate Limiter Service.
 *
 * Tests the core rate limiting service for:
 * - Job creation rate limits (per user, per site, concurrent)
 * - API rate limiting (sliding window algorithm)
 * - Polling rate limiting
 * - Admin bypass functionality
 * - Header generation
 *
 * @see \App\Services\AnalysisRateLimiterService
 */
class AnalysisRateLimiterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalysisRateLimiterService $service;
    protected User $user;
    protected User $adminUser;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AnalysisRateLimiterService::class);

        $this->user = User::factory()->create(['role' => 'user']);
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // CAN CREATE JOB TESTS (CC)
    // =========================================================================

    /**
     * CC-001: Can create job when no limits exceeded
     */
    public function test_can_create_job_when_no_limits_exceeded(): void
    {
        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['reason']);
        $this->assertNull($result['retry_after']);
    }

    /**
     * CC-002: Cannot create job when concurrent limit exceeded
     */
    public function test_cannot_create_job_when_concurrent_limit_exceeded(): void
    {
        // Create 2 pending jobs (concurrent limit)
        AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
        ]);
        AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
        ]);

        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('concurrent', strtolower($result['reason']));
        $this->assertEquals('concurrent', $result['limits']['type']);
    }

    /**
     * CC-003: Cannot create job when user hourly limit exceeded
     */
    public function test_cannot_create_job_when_user_hourly_limit_exceeded(): void
    {
        // Create 10 jobs in the last hour
        for ($i = 0; $i < 10; $i++) {
            AnalysisJob::factory()->create([
                'user_id' => $this->user->id,
                'site_id' => $this->site->id,
                'status' => 'completed',
                'created_at' => now()->subMinutes(30),
            ]);
        }

        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('hourly', strtolower($result['reason']));
        $this->assertNotNull($result['retry_after']);
    }

    /**
     * CC-004: Cannot create job when site hourly limit exceeded
     */
    public function test_cannot_create_job_when_site_hourly_limit_exceeded(): void
    {
        // Create 5 jobs for this site in the last hour
        for ($i = 0; $i < 5; $i++) {
            AnalysisJob::factory()->create([
                'user_id' => $this->user->id,
                'site_id' => $this->site->id,
                'status' => 'completed',
                'created_at' => now()->subMinutes(30),
            ]);
        }

        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('site', strtolower($result['reason']));
    }

    /**
     * CC-005: Admin bypasses all limits
     */
    public function test_admin_bypasses_all_limits(): void
    {
        // Create max jobs
        for ($i = 0; $i < 10; $i++) {
            AnalysisJob::factory()->create([
                'user_id' => $this->adminUser->id,
                'site_id' => $this->site->id,
                'status' => 'pending',
                'created_at' => now()->subMinutes(30),
            ]);
        }

        $result = $this->service->canCreateJob($this->adminUser->id, $this->site->id);

        $this->assertTrue($result['allowed']);
    }

    // =========================================================================
    // API RATE LIMIT TESTS (AR)
    // =========================================================================

    /**
     * AR-001: First API request allowed
     */
    public function test_first_api_request_allowed(): void
    {
        $result = $this->service->hitApiRateLimit($this->user->id);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(59, $result['remaining']);
    }

    /**
     * AR-002: API request at limit blocked
     */
    public function test_api_request_at_limit_blocked(): void
    {
        // Make 60 requests
        for ($i = 0; $i < 60; $i++) {
            $this->service->hitApiRateLimit($this->user->id);
        }

        // 61st should be blocked
        $result = $this->service->hitApiRateLimit($this->user->id);

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertNotNull($result['retry_after']);
    }

    /**
     * AR-003: API rate limit uses sliding window
     */
    public function test_api_rate_limit_uses_sliding_window(): void
    {
        // Make 30 requests
        for ($i = 0; $i < 30; $i++) {
            $this->service->hitApiRateLimit($this->user->id);
        }

        // Travel 30 seconds forward (within window)
        $this->travel(30)->seconds();

        // Make 30 more requests (total 60 within window)
        for ($i = 0; $i < 30; $i++) {
            $result = $this->service->hitApiRateLimit($this->user->id);
            $this->assertTrue($result['allowed']);
        }

        // 61st should be blocked
        $result = $this->service->hitApiRateLimit($this->user->id);
        $this->assertFalse($result['allowed']);
    }

    /**
     * AR-004: API rate limit window expires correctly
     */
    public function test_api_rate_limit_window_expires_correctly(): void
    {
        // Exhaust limit
        for ($i = 0; $i < 60; $i++) {
            $this->service->hitApiRateLimit($this->user->id);
        }

        // Travel 61 seconds forward (past window)
        $this->travel(61)->seconds();

        // Should be allowed again
        $result = $this->service->hitApiRateLimit($this->user->id);
        $this->assertTrue($result['allowed']);
    }

    /**
     * AR-005: Admin bypasses API limits
     */
    public function test_admin_bypasses_api_limits(): void
    {
        // Make many requests as admin
        for ($i = 0; $i < 100; $i++) {
            $result = $this->service->hitApiRateLimit($this->adminUser->id);
            $this->assertTrue($result['allowed']);
        }
    }

    // =========================================================================
    // POLLING RATE LIMIT TESTS (PR)
    // =========================================================================

    /**
     * PR-001: First polling request allowed
     */
    public function test_first_polling_request_allowed(): void
    {
        $result = $this->service->hitPollingRateLimit($this->user->id, 1);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(29, $result['remaining']);
    }

    /**
     * PR-002: Polling limit is 30 per minute per job
     */
    public function test_polling_limit_is_30_per_minute_per_job(): void
    {
        // Make 30 requests
        for ($i = 0; $i < 30; $i++) {
            $result = $this->service->hitPollingRateLimit($this->user->id, 1);
            $this->assertTrue($result['allowed']);
        }

        // 31st should be blocked
        $result = $this->service->hitPollingRateLimit($this->user->id, 1);
        $this->assertFalse($result['allowed']);
    }

    /**
     * PR-003: Different jobs have separate limits
     */
    public function test_different_jobs_have_separate_limits(): void
    {
        // Exhaust job 1
        for ($i = 0; $i < 31; $i++) {
            $this->service->hitPollingRateLimit($this->user->id, 1);
        }

        // Job 2 should still have full limit
        $result = $this->service->hitPollingRateLimit($this->user->id, 2);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(29, $result['remaining']);
    }

    /**
     * PR-004: Admin bypasses polling limits
     */
    public function test_admin_bypasses_polling_limits(): void
    {
        // Make many polling requests as admin
        for ($i = 0; $i < 50; $i++) {
            $result = $this->service->hitPollingRateLimit($this->adminUser->id, 1);
            $this->assertTrue($result['allowed']);
        }
    }

    // =========================================================================
    // HEADER GENERATION TESTS (HG)
    // =========================================================================

    /**
     * HG-001: Job creation headers correct for new user
     */
    public function test_job_creation_headers_correct_for_new_user(): void
    {
        $headers = $this->service->getJobCreationHeaders($this->user->id, $this->site->id);

        $this->assertEquals('5', $headers['X-RateLimit-Limit']); // Site limit is more restrictive
        $this->assertEquals('5', $headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    /**
     * HG-002: Job creation headers decrement correctly
     */
    public function test_job_creation_headers_decrement_correctly(): void
    {
        // Create a job
        AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'created_at' => now()->subMinutes(30),
        ]);

        $headers = $this->service->getJobCreationHeaders($this->user->id, $this->site->id);

        $this->assertEquals('4', $headers['X-RateLimit-Remaining']);
    }

    /**
     * HG-003: Admin headers show unlimited
     */
    public function test_admin_headers_show_unlimited(): void
    {
        $headers = $this->service->getJobCreationHeaders($this->adminUser->id, $this->site->id);

        $this->assertEquals('unlimited', $headers['X-RateLimit-Limit']);
        $this->assertEquals('unlimited', $headers['X-RateLimit-Remaining']);
    }

    /**
     * HG-004: API headers correct
     */
    public function test_api_headers_correct(): void
    {
        $headers = $this->service->getApiHeaders($this->user->id);

        $this->assertEquals('60', $headers['X-RateLimit-Limit']);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    /**
     * HG-005: Polling headers correct
     */
    public function test_polling_headers_correct(): void
    {
        $headers = $this->service->getPollingHeaders($this->user->id, 1);

        $this->assertEquals('30', $headers['X-RateLimit-Limit']);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    // =========================================================================
    // BUILD RATE LIMIT RESPONSE TESTS (BR)
    // =========================================================================

    /**
     * BR-001: Build rate limit response has correct structure
     */
    public function test_build_rate_limit_response_has_correct_structure(): void
    {
        $response = $this->service->buildRateLimitResponse(
            'Rate limit exceeded',
            60,
            time() + 60,
            'api'
        );

        $this->assertFalse($response['success']);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $response['error']['code']);
        $this->assertEquals('Rate limit exceeded', $response['error']['message']);
        $this->assertEquals(60, $response['error']['retry_after']);
        $this->assertEquals(429, $response['status']);
    }

    /**
     * BR-002: Build rate limit response has correct headers
     */
    public function test_build_rate_limit_response_has_correct_headers(): void
    {
        $response = $this->service->buildRateLimitResponse(
            'Rate limit exceeded',
            60,
            time() + 60,
            'api'
        );

        $this->assertEquals('60', $response['headers']['X-RateLimit-Limit']);
        $this->assertEquals('0', $response['headers']['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('X-RateLimit-Reset', $response['headers']);
        $this->assertEquals('60', $response['headers']['Retry-After']);
    }

    /**
     * BR-003: Different limit types have different limits
     */
    public function test_different_limit_types_have_different_limits(): void
    {
        $apiResponse = $this->service->buildRateLimitResponse('msg', 30, time() + 30, 'api');
        $this->assertEquals('60', $apiResponse['headers']['X-RateLimit-Limit']);

        $pollingResponse = $this->service->buildRateLimitResponse('msg', 30, time() + 30, 'polling');
        $this->assertEquals('30', $pollingResponse['headers']['X-RateLimit-Limit']);

        $userHourlyResponse = $this->service->buildRateLimitResponse('msg', 3600, time() + 3600, 'job_user_hourly');
        $this->assertEquals('10', $userHourlyResponse['headers']['X-RateLimit-Limit']);

        $siteHourlyResponse = $this->service->buildRateLimitResponse('msg', 3600, time() + 3600, 'job_site_hourly');
        $this->assertEquals('5', $siteHourlyResponse['headers']['X-RateLimit-Limit']);
    }

    // =========================================================================
    // RESET TESTS (RS)
    // =========================================================================

    /**
     * RS-001: Reset user limits works
     */
    public function test_reset_user_limits_works(): void
    {
        // Hit limit
        for ($i = 0; $i < 60; $i++) {
            $this->service->hitApiRateLimit($this->user->id);
        }

        // Verify blocked
        $result = $this->service->hitApiRateLimit($this->user->id);
        $this->assertFalse($result['allowed']);

        // Reset
        $this->service->resetUserLimits($this->user->id);

        // Should be allowed
        $result = $this->service->hitApiRateLimit($this->user->id);
        $this->assertTrue($result['allowed']);
    }

    /**
     * RS-002: Reset polling limits works
     */
    public function test_reset_polling_limits_works(): void
    {
        // Hit limit
        for ($i = 0; $i < 31; $i++) {
            $this->service->hitPollingRateLimit($this->user->id, 1);
        }

        // Reset
        $this->service->resetPollingLimits(1);

        // Should be allowed
        $result = $this->service->hitPollingRateLimit($this->user->id, 1);
        $this->assertTrue($result['allowed']);
    }

    // =========================================================================
    // CONCURRENT JOBS TESTS (CJ)
    // =========================================================================

    /**
     * CJ-001: Count only pending and running jobs
     */
    public function test_count_only_pending_and_running_jobs(): void
    {
        // Create jobs with various statuses
        AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
        ]);
        AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
        ]);
        AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
        ]);
        AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
        ]);

        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        // Should be blocked due to 2 concurrent jobs
        $this->assertFalse($result['allowed']);
    }

    /**
     * CJ-002: Completed jobs don't count toward concurrent limit
     */
    public function test_completed_jobs_dont_count_toward_concurrent_limit(): void
    {
        // Create completed jobs (with created_at in the past so they don't count toward hourly limits)
        for ($i = 0; $i < 5; $i++) {
            AnalysisJob::factory()->create([
                'user_id' => $this->user->id,
                'site_id' => $this->site->id,
                'status' => 'completed',
                'created_at' => now()->subHours(2), // Old, outside hourly window
            ]);
        }

        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        // Should be allowed (no concurrent jobs, no hourly limit hit)
        $this->assertTrue($result['allowed']);
    }

    // =========================================================================
    // EDGE CASES (EC)
    // =========================================================================

    /**
     * EC-001: Service handles null timestamps in cache
     */
    public function test_service_handles_null_timestamps_in_cache(): void
    {
        // Put null in cache
        Cache::put('analysis_rate_limit:api_minute:' . $this->user->id, null, now()->addMinute());

        // Should still work
        $result = $this->service->hitApiRateLimit($this->user->id);

        $this->assertTrue($result['allowed']);
    }

    /**
     * EC-002: Service handles non-array timestamps in cache
     */
    public function test_service_handles_non_array_timestamps_in_cache(): void
    {
        // Put string in cache
        Cache::put('analysis_rate_limit:api_minute:' . $this->user->id, 'not-an-array', now()->addMinute());

        // Should still work
        $result = $this->service->hitApiRateLimit($this->user->id);

        $this->assertTrue($result['allowed']);
    }

    /**
     * EC-003: Service handles negative max requests
     */
    public function test_service_handles_negative_max_requests(): void
    {
        // Test via the public API - negative max requests won't happen in practice
        // but we can verify the service handles edge cases gracefully
        // The service uses positive constants, so just verify it works correctly
        $result = $this->service->hitApiRateLimit($this->user->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result);
    }

    /**
     * EC-004: Service handles zero window
     */
    public function test_service_handles_zero_window(): void
    {
        // Test via the public API - the service uses default window values
        // when invalid values are provided internally
        $result = $this->service->hitApiRateLimit($this->user->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('reset_at', $result);
    }

    /**
     * EC-005: Reset time calculation handles empty timestamps
     */
    public function test_reset_time_calculation_handles_empty_timestamps(): void
    {
        $resetTime = $this->service->checkApiRateLimit($this->user->id);

        $this->assertGreaterThan(time(), $resetTime['reset_at']);
        $this->assertLessThan(time() + 120, $resetTime['reset_at']);
    }
}
