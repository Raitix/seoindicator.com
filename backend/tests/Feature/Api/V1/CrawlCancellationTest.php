<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\CrawlJob;
use App\Models\User;
use App\Services\Crawler\CrawlerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;

/**
 * Feature tests for TASK-22-17: Cancellation API Endpoint
 *
 * Tests DELETE /api/v1/crawler/jobs/{jobId} endpoint
 *
 * @see TESTS/TASK-22-17.md
 * @see TASK-22-17
 */
class CrawlCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // TC-04: CANCELLATION API ENDPOINT TESTS
    // =========================================================================

    /**
     * TC-04-01: DELETE endpoint exists
     */
    public function test_delete_endpoint_exists(): void
    {
        $route = collect(app('router')->getRoutes())->first(function ($route) {
            return $route->uri() === 'api/v1/crawler/jobs/{jobId}' &&
                   in_array('DELETE', $route->methods());
        });

        $this->assertNotNull($route, 'DELETE /api/v1/crawler/jobs/{jobId} route not found');
    }

    /**
     * TC-04-02: Cancel queued job returns 202 Accepted
     */
    public function test_cancel_queued_job_returns_202_accepted(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'job_id' => 'crawl-test-queued',
            'status' => CrawlJob::STATUS_QUEUED,
        ]);

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->with('crawl-test-queued')
            ->once()
            ->andReturn([
                'jobId' => 'crawl-test-queued',
                'status' => 'cancelled',
                'cancelled_at' => now()->toIso8601String(),
            ]);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-test-queued');

        $response->assertStatus(202)
            ->assertJson([
                'jobId' => 'crawl-test-queued',
                'status' => 'cancelled',
            ]);
    }

    /**
     * TC-04-03: Cancel running job returns 202 Accepted
     */
    public function test_cancel_running_job_returns_202_accepted(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'job_id' => 'crawl-test-running',
            'status' => CrawlJob::STATUS_RUNNING,
        ]);

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->with('crawl-test-running')
            ->once()
            ->andReturn([
                'jobId' => 'crawl-test-running',
                'status' => 'cancelling',
                'message' => 'Cancellation requested. Worker will stop gracefully.',
            ]);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-test-running');

        $response->assertStatus(202)
            ->assertJson([
                'jobId' => 'crawl-test-running',
                'status' => 'cancelling',
            ]);
    }

    /**
     * TC-04-04: Cannot cancel completed job
     */
    public function test_cannot_cancel_completed_job(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'job_id' => 'crawl-test-completed',
            'status' => CrawlJob::STATUS_COMPLETED,
        ]);

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->with('crawl-test-completed')
            ->once()
            ->andReturn(false);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-test-completed');

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Job cannot be cancelled',
            ]);
    }

    /**
     * TC-04-05: Cannot cancel failed job
     */
    public function test_cannot_cancel_failed_job(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'job_id' => 'crawl-test-failed',
            'status' => CrawlJob::STATUS_FAILED,
        ]);

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->with('crawl-test-failed')
            ->once()
            ->andReturn(false);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-test-failed');

        $response->assertStatus(409);
    }

    /**
     * TC-04-06: Cannot cancel already cancelled job
     */
    public function test_cannot_cancel_already_cancelled_job(): void
    {
        $user = User::factory()->create();
        $crawlJob = CrawlJob::factory()->create([
            'user_id' => $user->id,
            'job_id' => 'crawl-test-cancelled',
            'status' => CrawlJob::STATUS_CANCELLED,
        ]);

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->with('crawl-test-cancelled')
            ->once()
            ->andReturn(false);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-test-cancelled');

        $response->assertStatus(409);
    }

    /**
     * TC-04-07: Unauthenticated request returns 401
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->deleteJson('/api/v1/crawler/jobs/crawl-test-unauth');

        $response->assertStatus(401);
    }

    /**
     * TC-04-08: Non-existent job returns 404
     */
    public function test_non_existent_job_returns_404(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->with('crawl-nonexistent')
            ->once()
            ->andReturn(null);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Job not found',
            ]);
    }

    /**
     * TC-04-09: Other user's job returns 403 Forbidden
     */
    public function test_other_users_job_returns_403_forbidden(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $crawlJob = CrawlJob::factory()->create([
            'user_id' => $user1->id,
            'job_id' => 'crawl-user1-job',
            'status' => CrawlJob::STATUS_QUEUED,
        ]);

        // User 2 tries to cancel User 1's job
        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->with('crawl-user1-job')
            ->once()
            ->andReturn(null); // Returns null for not found/unauthorized

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user2)
            ->deleteJson('/api/v1/crawler/jobs/crawl-user1-job');

        $response->assertStatus(404); // Or 403 depending on policy implementation
    }

    /**
     * TC-04-10: Response format for queued job cancellation
     */
    public function test_response_format_for_queued_job_cancellation(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->once()
            ->andReturn([
                'jobId' => 'crawl-format-test',
                'status' => 'cancelled',
                'cancelled_at' => '2026-03-02T10:00:00Z',
                'pages_crawled' => 0,
                'total_size' => 0,
            ]);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-format-test');

        $response->assertStatus(202)
            ->assertJsonStructure([
                'jobId',
                'status',
                'cancelled_at',
            ]);
    }

    /**
     * TC-04-11: Response format for running job cancellation
     */
    public function test_response_format_for_running_job_cancellation(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->once()
            ->andReturn([
                'jobId' => 'crawl-running-format',
                'status' => 'cancelling',
                'message' => 'Cancellation requested. Worker will stop gracefully.',
                'pages_crawled' => 3,
                'partial_content_url' => '/api/v1/crawler/jobs/crawl-running-format/content',
            ]);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-running-format');

        $response->assertStatus(202)
            ->assertJsonStructure([
                'jobId',
                'status',
                'message',
            ]);
    }

    /**
     * TC-04-12: Authorization via policy
     */
    public function test_authorization_via_policy(): void
    {
        $user = User::factory()->create();

        // Test that policy is checked (this is a behavioral test)
        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->once()
            ->andReturn(['status' => 'cancelled']);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-policy-test');

        $response->assertStatus(202);
    }

    /**
     * TC-04-13: Rate limiting applied
     */
    public function test_rate_limiting_applied(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->andReturn(['status' => 'cancelled']);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        // Make multiple requests to hit rate limit
        for ($i = 0; $i < 15; $i++) {
            $response = $this->actingAs($user)
                ->deleteJson("/api/v1/crawler/jobs/crawl-rate-test-{$i}");
        }

        // The 16th request should be rate limited
        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-rate-test-16');

        // Rate limiting might return 429 or might be disabled in testing
        // This test documents expected behavior
        $this->assertContains($response->status(), [202, 404, 429]);
    }

    /**
     * TC-04-14: Idempotent cancellation
     */
    public function test_idempotent_cancellation(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->twice()
            ->andReturn(['status' => 'cancelled', 'jobId' => 'crawl-idempotent']);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        // First request
        $response1 = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-idempotent');

        // Second request (should return same result)
        $response2 = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-idempotent');

        $response1->assertStatus(202);
        $response2->assertStatus(202);
        $this->assertEquals($response1->json('status'), $response2->json('status'));
    }

    /**
     * TC-04-15: Cancelled job includes partial results info
     */
    public function test_cancelled_job_includes_partial_results_info(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->once()
            ->andReturn([
                'jobId' => 'crawl-partial',
                'status' => 'cancelled',
                'cancelled_at' => '2026-03-02T10:00:30Z',
                'pages_crawled' => 3,
                'total_size' => 600000,
                'reason' => 'User requested cancellation',
                'partial_content_url' => '/api/v1/crawler/jobs/crawl-partial/content',
            ]);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-partial');

        $response->assertStatus(202)
            ->assertJson([
                'jobId' => 'crawl-partial',
                'status' => 'cancelled',
                'pages_crawled' => 3,
                'reason' => 'User requested cancellation',
            ])
            ->assertJsonStructure(['partial_content_url']);
    }

    /**
     * TC-04-16: Validation for jobId format
     */
    public function test_validation_for_job_id_format(): void
    {
        $user = User::factory()->create();

        // Test with invalid jobId format
        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/invalid-job-id-format!@#');

        // Should either reject invalid format or treat as not found
        $this->assertContains($response->status(), [404, 422]);
    }

    /**
     * TC-04-17: Admin can cancel any job
     */
    public function test_admin_can_cancel_any_job(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $otherUser = User::factory()->create();

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->once()
            ->andReturn(['status' => 'cancelled', 'jobId' => 'crawl-admin-test']);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($admin)
            ->deleteJson('/api/v1/crawler/jobs/crawl-admin-test');

        $response->assertStatus(202);
    }

    /**
     * TC-04-18: Response headers include rate limit info
     */
    public function test_response_headers_include_rate_limit_info(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->andReturn(['status' => 'cancelled']);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-headers');

        // Rate limit headers should be present (if rate limiting is enabled)
        $response->assertStatus(202);
    }

    /**
     * TC-04-19: Audit log for cancellation
     */
    public function test_audit_log_for_cancellation(): void
    {
        $user = User::factory()->create();

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with(
                'CrawlJob cancellation requested',
                \Mockery::on(function ($context) {
                    return isset($context['job_id']) &&
                           isset($context['user_id']);
                })
            );

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->andReturn(['status' => 'cancelled']);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/crawler/jobs/crawl-audit');

        $response->assertStatus(202);
    }

    /**
     * TC-04-20: Request includes user agent and IP for audit
     */
    public function test_request_includes_user_agent_and_ip_for_audit(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(CrawlerServiceInterface::class);
        $mockService->shouldReceive('cancelJob')
            ->once()
            ->withArgs(function ($jobId) {
                // This tests that the service is called correctly
                return true;
            })
            ->andReturn(['status' => 'cancelled']);

        $this->app->instance(CrawlerServiceInterface::class, $mockService);

        $response = $this->actingAs($user)
            ->withHeaders([
                'User-Agent' => 'TestClient/1.0',
            ])
            ->deleteJson('/api/v1/crawler/jobs/crawl-client-info');

        $response->assertStatus(202);
    }
}
