<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalysisJobStreamTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;

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

    protected function getStreamUrl(int $jobId): string
    {
        return "/api/v1/analysis/jobs/{$jobId}/stream";
    }

    // =========================================================================
    // 1. CONNECTION TESTS
    // =========================================================================

    /** @test */
    public function stream_returns_200_status_code(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $response = $this->getJson($this->getStreamUrl($job->id));

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_returns_content_type_event_stream(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $response = $this->getJson($this->getStreamUrl($job->id));

        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function stream_returns_cache_control_no_cache(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $response = $this->getJson($this->getStreamUrl($job->id));

        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }

    /** @test */
    public function stream_returns_x_accel_buffering_no(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $response = $this->getJson($this->getStreamUrl($job->id));

        $this->assertEquals('no', $response->headers->get('X-Accel-Buffering'));
    }

    /** @test */
    public function stream_returns_connection_keep_alive(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        $response = $this->getJson($this->getStreamUrl($job->id));

        $this->assertEquals('keep-alive', $response->headers->get('Connection'));
    }

    // =========================================================================
    // 2. AUTHORIZATION TESTS
    // =========================================================================

    /** @test */
    public function stream_returns_401_for_unauthenticated(): void
    {
        $job = $this->createRunningJob();

        $response = $this->getJson($this->getStreamUrl($job->id));

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
        $response = $this->getJson($this->getStreamUrl($job->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_returns_404_for_nonexistent_job(): void
    {
        $this->authenticate();

        $response = $this->getJson($this->getStreamUrl(99999));

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_returns_404_for_non_numeric_job_id(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/v1/analysis/jobs/abc/stream');

        $response->assertStatus(404);
    }

    // =========================================================================
    // 3. TERMINAL STATE TESTS
    // =========================================================================

    /** @test */
    public function stream_handles_completed_job(): void
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

        $response = $this->getJson($this->getStreamUrl($job->id));

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_handles_failed_job(): void
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

        $response = $this->getJson($this->getStreamUrl($job->id));

        $response->assertStatus(200);
    }

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

        $response = $this->getJson($this->getStreamUrl($job->id));

        $response->assertStatus(200);
    }

    // =========================================================================
    // 4. SECURITY TESTS
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
        $response = $this->getJson($this->getStreamUrl($job->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_handles_sql_injection_in_job_id(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/v1/analysis/jobs/1-1/stream');

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_handles_negative_job_id(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/v1/analysis/jobs/-1/stream');

        $response->assertStatus(404);
    }

    // =========================================================================
    // 5. EDGE CASES
    // =========================================================================

    /** @test */
    public function stream_handles_pending_job(): void
    {
        $this->authenticate();
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'indicators' => [],
            'total_indicators' => 0,
            'completed_indicators' => 0,
        ]);

        $response = $this->getJson($this->getStreamUrl($job->id));

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_handles_job_with_results(): void
    {
        $this->authenticate();
        $job = $this->createRunningJob();

        // Create a completed result
        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
            'score' => 85,
            'max_score' => 100,
            'percentage' => 85.0,
        ]);

        $response = $this->getJson($this->getStreamUrl($job->id));

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

        $response = $this->getJson($this->getStreamUrl($job->id));

        $response->assertStatus(200);
    }
}
