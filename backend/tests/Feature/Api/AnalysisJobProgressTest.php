<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalysisJobProgressTest extends TestCase
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
    }

    protected function authenticate(): static
    {
        $this->actingAs($this->user);
        return $this;
    }

    protected function createJob(array $overrides = []): AnalysisJob
    {
        $job = AnalysisJob::create(array_merge([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 2,
        ], $overrides));

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
            'status' => 'pending',
        ]);

        return $job;
    }

    // =========================================================================
    // PROGRESS ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function progress_endpoint_returns_200(): void
    {
        $this->authenticate();
        $job = $this->createJob();

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertStatus(200);
    }

    /** @test */
    public function progress_endpoint_returns_correct_structure(): void
    {
        $this->authenticate();
        $job = $this->createJob();

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertJsonStructure([
            'id',
            'status',
            'total_indicators',
            'completed_indicators',
            'progress_percent',
            'results',
        ]);
    }

    /** @test */
    public function progress_endpoint_has_cache_control_no_store(): void
    {
        $this->authenticate();
        $job = $this->createJob();

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        // Laravel adds "private" by default, so we check for "no-store" presence
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    /** @test */
    public function progress_endpoint_has_polling_interval_header(): void
    {
        $this->authenticate();
        $job = $this->createJob();

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertHeader('X-Polling-Interval', '2');
    }

    /** @test */
    public function progress_endpoint_returns_401_for_unauthenticated(): void
    {
        $job = $this->createJob();

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertStatus(401);
    }

    /** @test */
    public function progress_endpoint_returns_404_for_nonexistent_job(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/v1/analysis/jobs/99999/progress');

        $response->assertStatus(404);
    }

    /** @test */
    public function progress_endpoint_returns_404_for_unowned_job(): void
    {
        // Create job owned by other user
        $otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
        $otherJob = AnalysisJob::create([
            'user_id' => $this->otherUser->id,
            'site_id' => $otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->authenticate();

        $response = $this->getJson("/api/v1/analysis/jobs/{$otherJob->id}/progress");

        // Currently returns 404, should ideally be 403 but this tests actual behavior
        $response->assertStatus(404);
    }

    /** @test */
    public function progress_endpoint_returns_404_for_non_numeric_job_id(): void
    {
        $this->authenticate();

        $response = $this->getJson('/api/v1/analysis/jobs/abc/progress');

        $response->assertStatus(404);
    }

    /** @test */
    public function progress_endpoint_reflects_current_job_status(): void
    {
        $this->authenticate();
        $job = $this->createJob(['status' => 'running', 'started_at' => now()]);

        // Update a result to completed
        $job->results()->update([
            'status' => 'completed',
            'score' => 85.00,
            'max_score' => 100.00,
        ]);

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'running');
    }
}
