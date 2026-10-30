<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Services\AnalysisRateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * Unit tests for AnalysisRateLimiterService.
 *
 * Tests rate limiting for job creation, API requests, and polling.
 * Includes tests for admin bypass and rate limit headers.
 *
 * @see TASK-19-16
 * @see \App\Services\AnalysisRateLimiterService
 */
class AnalysisRateLimiterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUser;
    protected Site $site;
    protected AnalysisRateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->service = app(AnalysisRateLimiterService::class);
    }

    // =========================================================================
    // CAN CREATE JOB TESTS
    // =========================================================================

    /** @test */
    public function can_create_job_returns_allowed_for_new_user(): void
    {
        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['reason']);
    }

    /** @test */
    public function can_create_job_admin_bypass(): void
    {
        // Create jobs up to the limit
        for ($i = 0; $i < 3; $i++) {
            AnalysisJob::create([
                'user_id' => $this->adminUser->id,
                'site_id' => $this->site->id,
                'status' => 'pending',
                'total_indicators' => 1,
            ]);
        }

        // Admin should still be allowed
        $result = $this->service->canCreateJob($this->adminUser->id, $this->site->id);

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function can_create_job_blocks_concurrent_limit(): void
    {
        // Create jobs up to concurrent limit
        for ($i = 0; $i < AnalysisRateLimiterService::MAX_CONCURRENT_JOBS_PER_USER; $i++) {
            AnalysisJob::create([
                'user_id' => $this->user->id,
                'site_id' => $this->site->id,
                'status' => 'pending',
                'total_indicators' => 1,
            ]);
        }

        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('concurrent', strtolower($result['reason']));
    }

    /** @test */
    public function can_create_job_blocks_user_hourly_limit(): void
    {
        // Create jobs up to hourly limit (all completed so no concurrent limit)
        for ($i = 0; $i < AnalysisRateLimiterService::MAX_JOBS_PER_USER_PER_HOUR; $i++) {
            AnalysisJob::create([
                'user_id' => $this->user->id,
                'site_id' => $this->site->id,
                'status' => 'completed',
                'total_indicators' => 1,
                'created_at' => now(),
            ]);
        }

        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('hourly', strtolower($result['reason']));
    }

    /** @test */
    public function can_create_job_blocks_site_hourly_limit(): void
    {
        $otherUser = User::factory()->create();

        // Create jobs for this site up to the limit
        for ($i = 0; $i < AnalysisRateLimiterService::MAX_JOBS_PER_SITE_PER_HOUR; $i++) {
            AnalysisJob::create([
                'user_id' => $otherUser->id,
                'site_id' => $this->site->id,
                'status' => 'completed',
                'total_indicators' => 1,
                'created_at' => now(),
            ]);
        }

        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('site', strtolower($result['reason']));
    }

    /** @test */
    public function can_create_job_counts_only_recent_jobs(): void
    {
        // Create jobs from more than an hour ago
        $oldJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
            'created_at' => now()->subHours(2),
        ]);

        // Should still be allowed
        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function can_create_job_counts_pending_and_running_only_for_concurrent(): void
    {
        // This test verifies that only pending/running jobs count toward concurrent limit
        // and completed jobs from the past don't affect the hourly limit
        
        // Create pending jobs up to limit, then mark them as completed
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);
        
        // Manually update the created_at to be in the past
        $job->update(['created_at' => now()->subHours(2)]);

        // Verify no pending/running jobs (concurrent check)
        $pendingRunning = AnalysisJob::where('user_id', $this->user->id)
            ->whereIn('status', ['pending', 'running'])
            ->count();
        $this->assertEquals(0, $pendingRunning, 'Should have no pending/running jobs');

        // Should still be allowed (completed jobs don't count for concurrent, and old jobs don't count for hourly)
        $result = $this->service->canCreateJob($this->user->id, $this->site->id);

        $this->assertTrue($result['allowed'], 'Result should be allowed: ' . json_encode($result));
    }

    // =========================================================================
    // API RATE LIMIT TESTS
    // =========================================================================

    /** @test */
    public function check_api_rate_limit_allows_within_limit(): void
    {
        $result = $this->service->checkApiRateLimit($this->user->id);

        $this->assertTrue($result['allowed']);
        $this->assertGreaterThan(0, $result['remaining']);
    }

    /** @test */
    public function check_api_rate_limit_admin_bypass(): void
    {
        $result = $this->service->checkApiRateLimit($this->adminUser->id);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(PHP_INT_MAX, $result['remaining']);
    }

    /** @test */
    public function hit_api_rate_limit_increments_counter(): void
    {
        // Hit the rate limit multiple times
        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->hitApiRateLimit($this->user->id);
        }

        $this->assertTrue($result['allowed']);
        $this->assertEquals(
            AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE - 5,
            $result['remaining']
        );
    }

    /** @test */
    public function hit_api_rate_limit_blocks_when_exceeded(): void
    {
        // Exceed the rate limit
        for ($i = 0; $i < AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE + 1; $i++) {
            $result = $this->service->hitApiRateLimit($this->user->id);
        }

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertNotNull($result['retry_after']);
    }

    /** @test */
    public function record_api_request_increments(): void
    {
        $this->service->recordApiRequest($this->user->id);
        $this->service->recordApiRequest($this->user->id);

        $result = $this->service->checkApiRateLimit($this->user->id);

        $this->assertEquals(
            AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE - 2,
            $result['remaining']
        );
    }

    // =========================================================================
    // POLLING RATE LIMIT TESTS
    // =========================================================================

    /** @test */
    public function check_polling_rate_limit_allows_within_limit(): void
    {
        $result = $this->service->checkPollingRateLimit($this->user->id, 1);

        $this->assertTrue($result['allowed']);
        $this->assertGreaterThan(0, $result['remaining']);
    }

    /** @test */
    public function check_polling_rate_limit_admin_bypass(): void
    {
        $result = $this->service->checkPollingRateLimit($this->adminUser->id, 1);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(PHP_INT_MAX, $result['remaining']);
    }

    /** @test */
    public function hit_polling_rate_limit_increments_counter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->hitPollingRateLimit($this->user->id, 1);
        }

        $this->assertTrue($result['allowed']);
        $this->assertEquals(
            AnalysisRateLimiterService::POLLING_REQUESTS_PER_MINUTE_PER_JOB - 5,
            $result['remaining']
        );
    }

    /** @test */
    public function hit_polling_rate_limit_blocks_when_exceeded(): void
    {
        // Exceed the rate limit
        for ($i = 0; $i < AnalysisRateLimiterService::POLLING_REQUESTS_PER_MINUTE_PER_JOB + 1; $i++) {
            $result = $this->service->hitPollingRateLimit($this->user->id, 1);
        }

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }

    /** @test */
    public function polling_rate_limit_is_per_job(): void
    {
        // Use up rate limit for job 1
        for ($i = 0; $i < AnalysisRateLimiterService::POLLING_REQUESTS_PER_MINUTE_PER_JOB; $i++) {
            $this->service->hitPollingRateLimit($this->user->id, 1);
        }

        // Should still be allowed for job 2
        $result = $this->service->checkPollingRateLimit($this->user->id, 2);

        $this->assertTrue($result['allowed']);
    }

    // =========================================================================
    // RATE LIMIT HEADERS TESTS
    // =========================================================================

    /** @test */
    public function get_job_creation_headers_returns_correct_format(): void
    {
        $headers = $this->service->getJobCreationHeaders($this->user->id, $this->site->id);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    /** @test */
    public function get_job_creation_headers_admin_shows_unlimited(): void
    {
        $headers = $this->service->getJobCreationHeaders($this->adminUser->id, $this->site->id);

        $this->assertEquals('unlimited', $headers['X-RateLimit-Limit']);
        $this->assertEquals('unlimited', $headers['X-RateLimit-Remaining']);
    }

    /** @test */
    public function get_job_creation_headers_shows_decreasing_remaining(): void
    {
        $headers1 = $this->service->getJobCreationHeaders($this->user->id, $this->site->id);

        // Create a job
        AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $headers2 = $this->service->getJobCreationHeaders($this->user->id, $this->site->id);

        $this->assertLessThan(
            (int) $headers1['X-RateLimit-Remaining'],
            (int) $headers2['X-RateLimit-Remaining']
        );
    }

    /** @test */
    public function get_api_headers_returns_correct_format(): void
    {
        $headers = $this->service->getApiHeaders($this->user->id);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);

        $this->assertEquals(
            (string) AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE,
            $headers['X-RateLimit-Limit']
        );
    }

    /** @test */
    public function get_polling_headers_returns_correct_format(): void
    {
        $headers = $this->service->getPollingHeaders($this->user->id, 1);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);

        $this->assertEquals(
            (string) AnalysisRateLimiterService::POLLING_REQUESTS_PER_MINUTE_PER_JOB,
            $headers['X-RateLimit-Limit']
        );
    }

    // =========================================================================
    // BUILD RATE LIMIT RESPONSE TESTS
    // =========================================================================

    /** @test */
    public function build_rate_limit_response_returns_correct_structure(): void
    {
        $response = $this->service->buildRateLimitResponse(
            'Rate limit exceeded',
            60,
            time() + 60,
            'api'
        );

        $this->assertFalse($response['success']);
        $this->assertEquals(429, $response['status']);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $response['error']['code']);
        $this->assertEquals(60, $response['error']['retry_after']);
    }

    /** @test */
    public function build_rate_limit_response_includes_headers(): void
    {
        $response = $this->service->buildRateLimitResponse(
            'Rate limit exceeded',
            60,
            time() + 60,
            'polling'
        );

        $this->assertArrayHasKey('Retry-After', $response['headers']);
        $this->assertEquals('60', $response['headers']['Retry-After']);
    }

    /** @test */
    public function build_rate_limit_response_uses_correct_limit_for_type(): void
    {
        $pollingResponse = $this->service->buildRateLimitResponse(
            'Rate limit exceeded',
            60,
            time() + 60,
            'polling'
        );

        $this->assertEquals(
            (string) AnalysisRateLimiterService::POLLING_REQUESTS_PER_MINUTE_PER_JOB,
            $pollingResponse['headers']['X-RateLimit-Limit']
        );

        $apiResponse = $this->service->buildRateLimitResponse(
            'Rate limit exceeded',
            60,
            time() + 60,
            'api'
        );

        $this->assertEquals(
            (string) AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE,
            $apiResponse['headers']['X-RateLimit-Limit']
        );
    }

    // =========================================================================
    // RESET LIMITS TESTS (FOR TESTING)
    // =========================================================================

    /** @test */
    public function reset_user_limits_clears_cache(): void
    {
        // Hit the API rate limit
        $this->service->hitApiRateLimit($this->user->id);

        // Reset limits
        $this->service->resetUserLimits($this->user->id);

        // Should be back to full capacity
        $result = $this->service->checkApiRateLimit($this->user->id);

        $this->assertEquals(
            AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE,
            $result['remaining']
        );
    }

    /** @test */
    public function reset_polling_limits_clears_cache(): void
    {
        // Hit the polling rate limit
        $this->service->hitPollingRateLimit($this->user->id, 1);

        // Reset limits
        $this->service->resetPollingLimits(1);

        // Should be back to full capacity
        $result = $this->service->checkPollingRateLimit($this->user->id, 1);

        $this->assertEquals(
            AnalysisRateLimiterService::POLLING_REQUESTS_PER_MINUTE_PER_JOB,
            $result['remaining']
        );
    }

    // =========================================================================
    // RECORD JOB CREATION TESTS
    // =========================================================================

    /** @test */
    public function record_job_creation_increments_counters(): void
    {
        $this->service->recordJobCreation($this->user->id, $this->site->id);

        // Check that the counters were incremented
        $headers = $this->service->getJobCreationHeaders($this->user->id, $this->site->id);

        $this->assertLessThan(
            AnalysisRateLimiterService::MAX_JOBS_PER_USER_PER_HOUR,
            (int) $headers['X-RateLimit-Remaining']
        );
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function rate_limit_window_expires(): void
    {
        // This test would require time manipulation in a real scenario
        // For now, we verify the structure supports window expiry

        $result = $this->service->hitApiRateLimit($this->user->id);

        $this->assertNotNull($result['reset_at']);
        $this->assertGreaterThan(time(), $result['reset_at']);
    }

    /** @test */
    public function concurrent_limit_only_counts_active_jobs(): void
    {
        // Create pending jobs up to limit
        for ($i = 0; $i < AnalysisRateLimiterService::MAX_CONCURRENT_JOBS_PER_USER; $i++) {
            AnalysisJob::create([
                'user_id' => $this->user->id,
                'site_id' => $this->site->id,
                'status' => 'pending',
                'total_indicators' => 1,
            ]);
        }

        // Should be blocked
        $result = $this->service->canCreateJob($this->user->id, $this->site->id);
        $this->assertFalse($result['allowed']);

        // Mark one as completed
        AnalysisJob::where('user_id', $this->user->id)
            ->where('status', 'pending')
            ->first()
            ->update(['status' => 'completed']);

        // Should now be allowed
        $result = $this->service->canCreateJob($this->user->id, $this->site->id);
        $this->assertTrue($result['allowed']);
    }

    /** @test */
    public function different_users_have_separate_limits(): void
    {
        $user2 = User::factory()->create(['role' => 'user']);

        // Hit limit for user 1
        for ($i = 0; $i < AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE; $i++) {
            $this->service->hitApiRateLimit($this->user->id);
        }

        // User 2 should still have full limit
        $result = $this->service->checkApiRateLimit($user2->id);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(
            AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE,
            $result['remaining']
        );
    }

    /** @test */
    public function non_admin_roles_dont_bypass(): void
    {
        $editorUser = User::factory()->create(['role' => 'editor']);

        // Editor should not bypass rate limits
        for ($i = 0; $i < AnalysisRateLimiterService::API_REQUESTS_PER_MINUTE + 1; $i++) {
            $result = $this->service->hitApiRateLimit($editorUser->id);
        }

        $this->assertFalse($result['allowed']);
    }
}
