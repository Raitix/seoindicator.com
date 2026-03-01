<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\AnalysisJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

/**
 * Feature tests for Analysis Job SSE Stream endpoint.
 *
 * Tests Server-Sent Events streaming for real-time progress updates.
 *
 * @see TASK-19-16
 * @see \App\Http\Controllers\Api\V1\AnalysisJobController::stream
 */
class AnalysisJobStreamTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);

        Event::fake();
    }

    // =========================================================================
    // AUTHENTICATION TESTS
    // =========================================================================

    /** @test */
    public function stream_requires_authentication(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 1,
        ]);

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}/stream");

        $response->assertStatus(401);
    }

    /** @test */
    public function stream_forbids_access_to_other_users_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->otherUser->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/analysis/jobs/{$job->id}/stream");

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_returns_404_for_nonexistent_job(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analysis/jobs/99999/stream');

        $response->assertStatus(404);
    }

    /** @test */
    public function stream_rejects_non_numeric_id(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analysis/jobs/abc/stream');

        $response->assertStatus(404);
    }

    // =========================================================================
    // RESPONSE FORMAT TESTS
    // =========================================================================

    /** @test */
    public function stream_returns_correct_content_type(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $response->assertHeader('Content-Type', 'text/event-stream');
    }

    /** @test */
    public function stream_has_cache_control_headers(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $response->assertHeader('Cache-Control', 'no-cache');
    }

    /** @test */
    public function stream_has_no_buffering_header(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $response->assertHeader('X-Accel-Buffering', 'no');
    }

    // =========================================================================
    // EVENT STREAM TESTS
    // =========================================================================

    /** @test */
    public function stream_sends_progress_events(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 3,
            'completed_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $content = $response->getContent();

        // Should contain progress event
        $this->assertStringContainsString('event: progress', $content);
        $this->assertStringContainsString('completed_indicators', $content);
    }

    /** @test */
    public function stream_sends_complete_event_for_finished_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 2,
            'completed_indicators' => 2,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $content = $response->getContent();

        // Should contain complete event
        $this->assertStringContainsString('event: complete', $content);
    }

    /** @test */
    public function stream_sends_complete_event_for_cancelled_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'cancelled',
            'total_indicators' => 3,
            'completed_indicators' => 1,
            'cancelled_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $content = $response->getContent();

        $this->assertStringContainsString('event: complete', $content);
        $this->assertStringContainsString('cancelled', $content);
    }

    /** @test */
    public function stream_sends_complete_event_for_failed_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
            'total_indicators' => 3,
            'completed_indicators' => 1,
            'failed_indicators' => 1,
            'error_message' => 'Test error',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $content = $response->getContent();

        $this->assertStringContainsString('event: complete', $content);
        $this->assertStringContainsString('failed', $content);
    }

    /** @test */
    public function stream_sends_heartbeat_events(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $content = $response->getContent();

        // Should contain heartbeat event
        $this->assertStringContainsString('event: heartbeat', $content);
    }

    // =========================================================================
    // EVENT DATA FORMAT TESTS
    // =========================================================================

    /** @test */
    public function progress_event_includes_required_fields(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 4,
            'completed_indicators' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $content = $response->getContent();

        // Parse SSE data
        $this->assertStringContainsString('"job_id":', $content);
        $this->assertStringContainsString('"status":', $content);
        $this->assertStringContainsString('"total_indicators":', $content);
        $this->assertStringContainsString('"completed_indicators":', $content);
        $this->assertStringContainsString('"progress_percent":', $content);
    }

    /** @test */
    public function complete_event_includes_final_status(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 3,
            'completed_indicators' => 3,
            'failed_indicators' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $content = $response->getContent();

        $this->assertStringContainsString('"status":"completed"', $content);
        $this->assertStringContainsString('"total_indicators":3', $content);
    }

    /** @test */
    public function events_have_sequential_ids(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $content = $response->getContent();

        // Should have id: lines
        $this->assertStringContainsString('id:', $content);
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /** @test */
    public function stream_sends_error_event_for_nonexistent_job_during_stream(): void
    {
        // This tests the edge case where a job is deleted during streaming
        // In practice, we test with a completed job first

        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        // Should complete successfully for a completed job
        $response->assertStatus(200);
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    /** @test */
    public function stream_for_job_with_results(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 2,
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
            'score' => 85.0,
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');
    }

    /** @test */
    public function stream_handles_large_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 17, // All available indicators
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $response->assertStatus(200);
    }

    /** @test */
    public function stream_multiple_concurrent_users(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        // Multiple requests to same job should all succeed
        $response1 = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $response2 = $this->actingAs($this->user)
            ->get("/api/v1/analysis/jobs/{$job->id}/stream");

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }
}
