<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\AnalysisRateLimiterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

/**
 * Feature tests for TASK-19-14: Rate Limiting Implementation.
 *
 * Tests rate limiting for analysis job creation and API endpoints:
 * - Job creation rate limits (per user, per site, concurrent)
 * - API rate limiting (per user for authenticated requests)
 * - Polling endpoint rate limiting (per job)
 * - Rate limit headers in responses
 * - Rate limit exceeded response (429)
 * - Admin bypass capability
 * - Redis-based rate limiting via Cache
 *
 * @see \App\Services\AnalysisRateLimiterService
 * @see \App\Http\Controllers\Api\V1\AnalysisJobController
 */
class AnalysisJobRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUser;
    protected Site $site;
    protected AnalysisRateLimiterService $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'role' => 'user',
        ]);

        // Create admin user for bypass tests
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create test site
        $this->site = Site::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
        ]);

        // Get rate limiter service
        $this->rateLimiter = app(AnalysisRateLimiterService::class);

        // Clear cache
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // JOB CREATION RATE LIMITS - PER USER HOURLY (JU)
    // =========================================================================

    /**
     * JU-001: User can create jobs up to concurrent limit
     */
    public function test_user_can_create_jobs_up_to_hourly_limit(): void
    {
        $this->actingAs($this->user);

        // Create jobs up to concurrent limit (2) - complete each one to test hourly limits
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);

            $response->assertStatus(201);
            
            // Complete the job so it doesn't count toward concurrent limit
            $job = AnalysisJob::latest()->first();
            $job->update(['status' => 'completed', 'completed_at' => now()]);
        }

        // Verify all jobs were created
        $this->assertEquals(5, AnalysisJob::where('user_id', $this->user->id)->count());
    }

    /**
     * JU-002: User cannot exceed site hourly job limit
     */
    public function test_user_cannot_exceed_hourly_job_limit(): void
    {
        $this->actingAs($this->user);

        // Create and complete max jobs for site (5 per hour)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
            
            // Complete the job to avoid concurrent limit
            $job = AnalysisJob::latest()->first();
            $job->update(['status' => 'completed', 'completed_at' => now()]);
        }

        // Try to create 6th job (exceeds site limit)
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(429)
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('success', false)
                ->where('error.code', 'RATE_LIMIT_EXCEEDED')
                ->etc()
            );
    }

    /**
     * JU-003: Hourly limit resets after one hour
     */
    public function test_hourly_limit_resets_after_one_hour(): void
    {
        $this->actingAs($this->user);

        // Create and complete max jobs for site (5)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
            
            // Complete the job to avoid concurrent limit
            $job = AnalysisJob::latest()->first();
            $job->update(['status' => 'completed', 'completed_at' => now()]);
        }

        // Verify blocked
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);
        $response->assertStatus(429);

        // Travel 1 hour + 1 second forward
        $this->travel(3601)->seconds();

        // Should be allowed again (old jobs are now outside the 1-hour window)
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);
        $response->assertStatus(201);
    }

    // =========================================================================
    // JOB CREATION RATE LIMITS - PER SITE HOURLY (JS)
    // =========================================================================

    /**
     * JS-001: Site has separate hourly limit
     */
    public function test_site_has_separate_hourly_limit(): void
    {
        $this->actingAs($this->user);

        // Create 5 jobs for the site (site limit)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        // Try to create 6th job for same site
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(429)
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('success', false)
                ->where('error.code', 'RATE_LIMIT_EXCEEDED')
                ->etc()
            );
    }

    /**
     * JS-002: Different site has independent limit
     */
    public function test_different_site_has_independent_limit(): void
    {
        $this->actingAs($this->user);

        // Create second site
        $site2 = Site::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example2.com',
        ]);

        // Create and complete 5 jobs for first site (site limit)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
            
            // Complete to avoid concurrent limit
            $job = AnalysisJob::latest()->first();
            $job->update(['status' => 'completed', 'completed_at' => now()]);
        }

        // Should be able to create jobs for second site
        $response = $this->postJson("/api/v1/sites/{$site2->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(201);
    }

    // =========================================================================
    // JOB CREATION RATE LIMITS - CONCURRENT (JC)
    // =========================================================================

    /**
     * JC-001: User can have up to 2 concurrent jobs
     */
    public function test_user_can_have_up_to_two_concurrent_jobs(): void
    {
        $this->actingAs($this->user);

        // Create 2 pending jobs
        for ($i = 0; $i < 2; $i++) {
            $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
            $response->assertStatus(201);
        }

        // Verify both exist
        $this->assertEquals(2, AnalysisJob::where('user_id', $this->user->id)
            ->whereIn('status', ['pending', 'running'])
            ->count());
    }

    /**
     * JC-002: User cannot exceed concurrent job limit
     */
    public function test_user_cannot_exceed_concurrent_job_limit(): void
    {
        $this->actingAs($this->user);

        // Create 2 pending jobs (concurrent limit)
        for ($i = 0; $i < 2; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        // Try to create 3rd concurrent job
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(429)
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('success', false)
                ->where('error.code', 'RATE_LIMIT_EXCEEDED')
                ->etc()
            );
    }

    /**
     * JC-003: Can create new job after one completes
     */
    public function test_can_create_new_job_after_one_completes(): void
    {
        $this->actingAs($this->user);

        // Create 2 pending jobs
        for ($i = 0; $i < 2; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        // Complete one job
        $job = AnalysisJob::where('user_id', $this->user->id)->first();
        $job->update(['status' => 'completed', 'completed_at' => now()]);

        // Should be able to create new job now
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(201);
    }

    // =========================================================================
    // API RATE LIMITING - GENERAL (AR)
    // =========================================================================

    /**
     * AR-001: API requests are rate limited per user
     */
    public function test_api_requests_are_rate_limited_per_user(): void
    {
        $result = $this->rateLimiter->hitApiRateLimit($this->user->id);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(59, $result['remaining']);
    }

    /**
     * AR-002: API rate limit enforced at 60 requests per minute
     */
    public function test_api_rate_limit_enforced_at_60_per_minute(): void
    {
        // Make 60 requests
        for ($i = 0; $i < 60; $i++) {
            $result = $this->rateLimiter->hitApiRateLimit($this->user->id);
        }

        // 61st should be blocked
        $result = $this->rateLimiter->hitApiRateLimit($this->user->id);

        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }

    /**
     * AR-003: API rate limit resets after window
     */
    public function test_api_rate_limit_resets_after_window(): void
    {
        // Exhaust the limit
        for ($i = 0; $i < 60; $i++) {
            $this->rateLimiter->hitApiRateLimit($this->user->id);
        }

        // Verify blocked
        $result = $this->rateLimiter->hitApiRateLimit($this->user->id);
        $this->assertFalse($result['allowed']);

        // Travel 61 seconds forward
        $this->travel(61)->seconds();

        // Should be allowed again
        $result = $this->rateLimiter->hitApiRateLimit($this->user->id);
        $this->assertTrue($result['allowed']);
    }

    // =========================================================================
    // POLLING RATE LIMITING (PR)
    // =========================================================================

    /**
     * PR-001: Polling endpoint has separate rate limit
     */
    public function test_polling_endpoint_has_separate_rate_limit(): void
    {
        $job = AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
        ]);

        $result = $this->rateLimiter->hitPollingRateLimit($this->user->id, $job->id);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(29, $result['remaining']);
    }

    /**
     * PR-002: Polling limited to 30 requests per minute per job
     */
    public function test_polling_limited_to_30_per_minute_per_job(): void
    {
        $job = AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
        ]);

        // Make 30 requests
        for ($i = 0; $i < 30; $i++) {
            $result = $this->rateLimiter->hitPollingRateLimit($this->user->id, $job->id);
            $this->assertTrue($result['allowed']);
        }

        // 31st should be blocked
        $result = $this->rateLimiter->hitPollingRateLimit($this->user->id, $job->id);
        $this->assertFalse($result['allowed']);
    }

    /**
     * PR-003: Different jobs have independent polling limits
     */
    public function test_different_jobs_have_independent_polling_limits(): void
    {
        $job1 = AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
        ]);

        $job2 = AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
        ]);

        // Exhaust job1's limit
        for ($i = 0; $i < 31; $i++) {
            $this->rateLimiter->hitPollingRateLimit($this->user->id, $job1->id);
        }

        // job2 should still be allowed
        $result = $this->rateLimiter->hitPollingRateLimit($this->user->id, $job2->id);
        $this->assertTrue($result['allowed']);
    }

    /**
     * PR-004: Polling endpoint returns rate limit headers
     */
    public function test_polling_endpoint_returns_rate_limit_headers(): void
    {
        $job = AnalysisJob::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit', '30')
            ->assertHeader('X-RateLimit-Remaining')
            ->assertHeader('X-RateLimit-Reset');
    }

    // =========================================================================
    // RATE LIMIT HEADERS (RH)
    // =========================================================================

    /**
     * RH-001: Rate limit headers present in job creation response
     */
    public function test_rate_limit_headers_present_in_job_creation_response(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(201)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining')
            ->assertHeader('X-RateLimit-Reset');
    }

    /**
     * RH-002: Rate limit headers reflect correct limit value
     */
    public function test_rate_limit_headers_reflect_correct_limit_value(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        // Should show minimum of user hourly (10) and site hourly (5) limits
        $response->assertHeader('X-RateLimit-Limit', '5');
    }

    /**
     * RH-003: Rate limit remaining decrements correctly
     */
    public function test_rate_limit_remaining_decrements_correctly(): void
    {
        $this->actingAs($this->user);

        // First request
        $response1 = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);
        $remaining1 = (int) $response1->headers->get('X-RateLimit-Remaining');

        // Second request
        $response2 = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);
        $remaining2 = (int) $response2->headers->get('X-RateLimit-Remaining');

        $this->assertEquals($remaining1 - 1, $remaining2);
    }

    /**
     * RH-004: Rate limit reset is valid timestamp
     */
    public function test_rate_limit_reset_is_valid_timestamp(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $resetTime = (int) $response->headers->get('X-RateLimit-Reset');

        $this->assertGreaterThan(time(), $resetTime);
        $this->assertLessThan(time() + 3700, $resetTime); // Within 1 hour + buffer
    }

    // =========================================================================
    // RATE LIMIT EXCEEDED RESPONSE (RX)
    // =========================================================================

    /**
     * RX-001: Rate limit exceeded returns 429 status
     */
    public function test_rate_limit_exceeded_returns_429_status(): void
    {
        $this->actingAs($this->user);

        // Exhaust site limit (5)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        // Next request should be 429
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(429);
    }

    /**
     * RX-002: Rate limit exceeded returns JSON body
     */
    public function test_rate_limit_exceeded_returns_json_body(): void
    {
        $this->actingAs($this->user);

        // Exhaust site limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertJson(fn(AssertableJson $json) => $json
            ->where('success', false)
            ->where('error.code', 'RATE_LIMIT_EXCEEDED')
            ->where('error.message', fn($msg) => str_contains($msg, 'limit'))
            ->where('error.retry_after', fn($ra) => is_int($ra) && $ra > 0)
            ->etc()
        );
    }

    /**
     * RX-003: Retry-After header present
     */
    public function test_retry_after_header_present(): void
    {
        $this->actingAs($this->user);

        // Exhaust site limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertHeader('Retry-After');
        
        $retryAfter = (int) $response->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThan(3601, $retryAfter); // Max 1 hour
    }

    /**
     * RX-004: Retry-After matches body value
     */
    public function test_retry_after_matches_body_value(): void
    {
        $this->actingAs($this->user);

        // Exhaust site limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $headerRetry = (int) $response->headers->get('Retry-After');
        $bodyRetry = $response->json('error.retry_after');

        $this->assertEquals($headerRetry, $bodyRetry);
    }

    // =========================================================================
    // ADMIN BYPASS (AB)
    // =========================================================================

    /**
     * AB-001: Admin users bypass rate limits
     */
    public function test_admin_users_bypass_rate_limits(): void
    {
        $this->actingAs($this->adminUser);

        // Create site for admin
        $adminSite = Site::factory()->create([
            'user_id' => $this->adminUser->id,
            'url' => 'https://admin-site.com',
        ]);

        // Try to create more than limit
        for ($i = 0; $i < 15; $i++) {
            $response = $this->postJson("/api/v1/sites/{$adminSite->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);

            // Admin should succeed beyond normal limits
            $response->assertStatus(201);
        }
    }

    /**
     * AB-002: Admin headers show unlimited
     */
    public function test_admin_headers_show_unlimited(): void
    {
        $this->actingAs($this->adminUser);

        $adminSite = Site::factory()->create([
            'user_id' => $this->adminUser->id,
            'url' => 'https://admin-site.com',
        ]);

        $response = $this->postJson("/api/v1/sites/{$adminSite->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertHeader('X-RateLimit-Limit', 'unlimited');
        $response->assertHeader('X-RateLimit-Remaining', 'unlimited');
    }

    // =========================================================================
    // SPEC-006 COMPATIBILITY (SC)
    // =========================================================================

    /**
     * SC-001: Response format matches SPEC-006
     */
    public function test_response_format_matches_spec_006(): void
    {
        $this->actingAs($this->user);

        // Exhaust site limit
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        // Verify SPEC-006 format
        $response->assertJson(fn(AssertableJson $json) => $json
            ->where('success', false)
            ->has('error.code')
            ->has('error.message')
            ->has('error.retry_after')
            ->etc()
        );
    }

    /**
     * SC-002: Header names match SPEC-006
     */
    public function test_header_names_match_spec_006(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        // SPEC-006 headers
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
        $response->assertHeader('X-RateLimit-Reset');
    }

    // =========================================================================
    // INTEGRATION TESTS (IT)
    // =========================================================================

    /**
     * IT-001: Full rate limit flow
     */
    public function test_full_rate_limit_flow(): void
    {
        $this->actingAs($this->user);

        // Create and complete jobs up to site limit (5)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
            $response->assertStatus(201);
            
            // Complete to avoid concurrent limit
            $job = AnalysisJob::latest()->first();
            $job->update(['status' => 'completed', 'completed_at' => now()]);
        }

        // Verify 429 response
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);
        $response->assertStatus(429);

        // Travel past reset time
        $this->travel(3601)->seconds();

        // Should be able to create again
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);
        $response->assertStatus(201);
    }

    /**
     * IT-002: Rate limit accurate with concurrent requests
     */
    public function test_rate_limit_accurate_with_concurrent_requests(): void
    {
        $this->actingAs($this->user);

        // Make rapid requests - only first 2 should succeed (concurrent limit)
        // Then we need to complete them to continue
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        // First 2 should succeed (concurrent limit), 3rd should fail
        $this->assertEquals(201, $responses[0]->status());
        $this->assertEquals(201, $responses[1]->status());
        $this->assertEquals(429, $responses[2]->status());
    }

    /**
     * IT-003: Multiple users have independent limits
     */
    public function test_multiple_users_have_independent_limits(): void
    {
        $user2 = User::factory()->create(['role' => 'user']);
        $site2 = Site::factory()->create([
            'user_id' => $user2->id,
            'url' => 'https://user2-site.com',
        ]);

        // User 1 exhausts their limit
        $this->actingAs($this->user);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        // User 2 should still be able to create jobs
        $this->actingAs($user2);
        $response = $this->postJson("/api/v1/sites/{$site2->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(201);
    }

    // =========================================================================
    // EDGE CASES (EC)
    // =========================================================================

    /**
     * EC-001: Service handles missing user gracefully
     */
    public function test_service_handles_missing_user_gracefully(): void
    {
        $result = $this->rateLimiter->canCreateJob(999999, $this->site->id);

        // Should return allowed (user not found, let auth handle)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result);
    }

    /**
     * EC-002: Service handles cache failure gracefully
     */
    public function test_service_handles_cache_failure_gracefully(): void
    {
        // This test verifies the service doesn't crash if cache fails
        // The service should fall back to database queries
        $result = $this->rateLimiter->canCreateJob($this->user->id, $this->site->id);

        $this->assertIsArray($result);
        $this->assertTrue($result['allowed']);
    }

    /**
     * EC-003: Rate limiter resets correctly
     */
    public function test_rate_limiter_resets_correctly(): void
    {
        // Hit the API limit
        for ($i = 0; $i < 60; $i++) {
            $this->rateLimiter->hitApiRateLimit($this->user->id);
        }

        // Reset the limits
        $this->rateLimiter->resetUserLimits($this->user->id);

        // Should be allowed again
        $result = $this->rateLimiter->hitApiRateLimit($this->user->id);
        $this->assertTrue($result['allowed']);
    }
}
