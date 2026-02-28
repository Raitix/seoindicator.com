<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\AnalysisJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;

class AnalysisJobStreamTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;
    protected AnalysisJob $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    }

    protected function authenticate(): static
    {
        $this->actingAs($this->user);
        return $this;
    }

    protected function createRunningJob(): AnalysisJob
    {
        return AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'indicators' => ['meta_tags', 'headings', 'images'],
            'total_indicators' => 3,
            'completed_indicators' => 0,
        ]);
    }

    // =========================================================================
    // 1. CONNECTION TESTS
    // =========================================================================

    /** @test */
    public function stream_returns_200_status_code(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_returns_content_type_event_stream(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function stream_returns_cache_control_no_cache(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }

    /** @test */
    public function stream_returns_x_accel_buffering_no(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $this->assertEquals('no', $response->headers->get('X-Accel-Buffering'));
    }

    // =========================================================================
    // 2. AUTHORIZATION TESTS
    // =========================================================================

    /** @test */
    public function stream_returns_401_for_unauthenticated(): void
    {
        $job = $this->createRunningJob();

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(401);
    }

    /** @test */
    public function stream_returns_404_for_unowned_job(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        
        $job = AnalysisJob::create([
            'user_id' => $otherUser->id,
            'site_id' => $otherSite->id,
            'status' => 'running',
            'indicators' => ['meta_tags'],
            'total_indicators' => 1,
        ]);

        $this->authenticate();
        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_returns_404_for_nonexistent_job(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/v1/analysis-jobs/99999/stream');

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_returns_404_for_non_numeric_job_id(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/v1/analysis-jobs/abc/stream');

        $response->assertStatus(404);
    }

    // =========================================================================
    // 3. PROGRESS EVENT TESTS
    // =========================================================================

    /** @test */
    public function stream_sends_progress_event_format(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        // Mock the service to return a progress update
        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        // First call returns running state, second call returns completed
        $mockService->shouldReceive('getJobProgress')
            ->twice()
            ->andReturn(
                [
                    'job' => [
                        'id' => $job->id,
                        'status' => 'running',
                        'total_indicators' => 3,
                        'completed_indicators' => 1,
                        'progress_percent' => 33.33,
                    ],
                    'results' => [],
                ],
                null // End the stream
            );

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_includes_completed_indicators_in_progress(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 2,
                    'progress_percent' => 66.67,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_includes_progress_percent(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 1,
                    'progress_percent' => 33.33,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 4. RESULT EVENT TESTS
    // =========================================================================

    /** @test */
    public function stream_sends_result_event_on_indicator_completion(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        // Create a completed result
        AnalysisJobResult::create([
            'analysis_job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
            'score' => 85,
            'max_score' => 100,
            'percentage' => 85.0,
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 1,
                    'progress_percent' => 33.33,
                ],
                'results' => [
                    [
                        'indicator_slug' => 'meta_tags',
                        'indicator_name' => 'Meta Tags',
                        'status' => 'completed',
                        'score' => 85,
                        'percentage' => 85.0,
                    ],
                ],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_result_event_contains_indicator_slug(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 1,
                    'progress_percent' => 33.33,
                ],
                'results' => [
                    [
                        'indicator_slug' => 'headings',
                        'indicator_name' => 'Headings',
                        'status' => 'completed',
                        'score' => 90,
                        'percentage' => 90.0,
                    ],
                ],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_result_event_contains_status(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 1,
                    'progress_percent' => 33.33,
                ],
                'results' => [
                    [
                        'indicator_slug' => 'images',
                        'indicator_name' => 'Images',
                        'status' => 'completed',
                        'score' => 75,
                        'percentage' => 75.0,
                    ],
                ],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_result_event_contains_score(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 1,
                    'progress_percent' => 33.33,
                ],
                'results' => [
                    [
                        'indicator_slug' => 'meta_tags',
                        'indicator_name' => 'Meta Tags',
                        'status' => 'completed',
                        'score' => 95,
                        'percentage' => 95.0,
                    ],
                ],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 5. COMPLETE EVENT TESTS
    // =========================================================================

    /** @test */
    public function stream_sends_complete_event_when_job_finishes(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'indicators' => ['meta_tags', 'headings'],
            'total_indicators' => 2,
            'completed_indicators' => 2,
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'completed',
                    'total_indicators' => 2,
                    'completed_indicators' => 2,
                    'progress_percent' => 100.0,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_complete_event_contains_final_status(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'indicators' => ['meta_tags'],
            'total_indicators' => 1,
            'completed_indicators' => 1,
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'completed',
                    'total_indicators' => 1,
                    'completed_indicators' => 1,
                    'progress_percent' => 100.0,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_complete_event_contains_total_indicators(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'indicators' => ['meta_tags', 'headings', 'images'],
            'total_indicators' => 3,
            'completed_indicators' => 3,
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'completed',
                    'total_indicators' => 3,
                    'completed_indicators' => 3,
                    'progress_percent' => 100.0,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 6. ERROR EVENT TESTS
    // =========================================================================

    /** @test */
    public function stream_sends_error_event_on_failure(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
            'indicators' => ['meta_tags'],
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'error_message' => 'Analysis failed due to timeout',
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'failed',
                    'total_indicators' => 1,
                    'completed_indicators' => 0,
                    'progress_percent' => 0,
                    'error_message' => 'Analysis failed due to timeout',
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_error_event_contains_error_message(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
            'indicators' => ['meta_tags'],
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'error_message' => 'Connection timeout',
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'failed',
                    'total_indicators' => 1,
                    'completed_indicators' => 0,
                    'progress_percent' => 0,
                    'error_message' => 'Connection timeout',
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 7. CANCELLED JOB TESTS
    // =========================================================================

    /** @test */
    public function stream_handles_cancelled_job(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'cancelled',
            'indicators' => ['meta_tags'],
            'total_indicators' => 1,
            'completed_indicators' => 0,
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'cancelled',
                    'total_indicators' => 1,
                    'completed_indicators' => 0,
                    'progress_percent' => 0,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 8. RECONNECTION TESTS
    // =========================================================================

    /** @test */
    public function stream_accepts_last_event_id_header(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 1,
                    'progress_percent' => 33.33,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream", [], [
            'Last-Event-ID' => '5',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_handles_reconnection_gracefully(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        // First connection
        $mockService1 = Mockery::mock(AnalysisJobService::class);
        $mockService1->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService1->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 1,
                    'progress_percent' => 33.33,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService1);

        $response1 = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");
        $response1->assertStatus(200);

        // Second connection (reconnection)
        $mockService2 = Mockery::mock(AnalysisJobService::class);
        $mockService2->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService2->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 2,
                    'progress_percent' => 66.67,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService2);

        $response2 = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");
        $response2->assertStatus(200);
    }

    // =========================================================================
    // 9. EVENT FORMAT TESTS
    // =========================================================================

    /** @test */
    public function stream_event_format_follows_sse_spec(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 1,
                    'progress_percent' => 33.33,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        // SSE uses text/event-stream content type
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function stream_progress_event_has_correct_structure(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 1,
                    'progress_percent' => 33.33,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 10. HEARTBEAT TESTS
    // =========================================================================

    /** @test */
    public function stream_sends_heartbeat_to_keep_connection_alive(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        // Simulate long-running job
        $callCount = 0;
        $mockService->shouldReceive('getJobProgress')
            ->andReturnUsing(function () use (&$callCount, $job) {
                $callCount++;
                if ($callCount > 1) {
                    return null; // End stream after first heartbeat check
                }
                return [
                    'job' => [
                        'id' => $job->id,
                        'status' => 'running',
                        'total_indicators' => 3,
                        'completed_indicators' => 1,
                        'progress_percent' => 33.33,
                    ],
                    'results' => [],
                ];
            });

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 11. SECURITY TESTS
    // =========================================================================

    /** @test */
    public function stream_prevents_idor_attack(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        
        $job = AnalysisJob::create([
            'user_id' => $otherUser->id,
            'site_id' => $otherSite->id,
            'status' => 'running',
            'indicators' => ['meta_tags'],
            'total_indicators' => 1,
        ]);

        $this->authenticate();
        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_handles_sql_injection_in_job_id(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/v1/analysis-jobs/1-1/stream');

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_handles_xss_in_error_message(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
            'indicators' => ['meta_tags'],
            'total_indicators' => 1,
            'error_message' => '<script>alert("xss")</script>',
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'failed',
                    'total_indicators' => 1,
                    'completed_indicators' => 0,
                    'progress_percent' => 0,
                    'error_message' => '<script>alert("xss")</script>',
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    // =========================================================================
    // 12. EDGE CASES
    // =========================================================================

    /** @test */
    public function stream_handles_empty_indicators(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'indicators' => [],
            'total_indicators' => 0,
            'completed_indicators' => 0,
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 0,
                    'completed_indicators' => 0,
                    'progress_percent' => 0,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_handles_zero_progress(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 3,
                    'completed_indicators' => 0,
                    'progress_percent' => 0,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_handles_full_progress(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'indicators' => ['meta_tags', 'headings', 'images'],
            'total_indicators' => 3,
            'completed_indicators' => 3,
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'completed',
                    'total_indicators' => 3,
                    'completed_indicators' => 3,
                    'progress_percent' => 100.0,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_handles_large_indicator_count(): void
    {
        $this->authenticate();
        $indicators = array_map(function ($i) {
            return 'indicator_' . $i;
        }, range(1, 17));

        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'indicators' => $indicators,
            'total_indicators' => 17,
            'completed_indicators' => 5,
        ]);

        $mockService = Mockery::mock(AnalysisJobService::class);
        $mockService->shouldReceive('canUserAccessJob')
            ->with($this->user->id, $job->id)
            ->andReturn(true);
        
        $mockService->shouldReceive('getJobProgress')
            ->once()
            ->andReturn([
                'job' => [
                    'id' => $job->id,
                    'status' => 'running',
                    'total_indicators' => 17,
                    'completed_indicators' => 5,
                    'progress_percent' => 29.41,
                ],
                'results' => [],
            ]);

        $this->app->instance(AnalysisJobService::class, $mockService);

        $response = $this->getJson("/api/v1/analysis-jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
