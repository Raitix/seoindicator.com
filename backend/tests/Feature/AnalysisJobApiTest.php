<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\AnalysisJobService;
use App\Services\AnalysisRateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

/**
 * Feature tests for Analysis Job API endpoints.
 *
 * Tests CRUD operations, authorization, rate limiting,
 * and SSE/polling for the Progressive Analysis system.
 *
 * @see TASK-19-16
 * @see \App\Http\Controllers\Api\V1\AnalysisJobController
 */
class AnalysisJobApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Site $otherSite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);

        Event::fake();
    }

    // =========================================================================
    // INDEX (LIST JOBS) TESTS
    // =========================================================================

    /** @test */
    public function list_jobs_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs");

        $response->assertStatus(401);
    }

    /** @test */
    public function list_jobs_returns_user_jobs_only(): void
    {
        $job1 = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $job2 = AnalysisJob::create([
            'user_id' => $this->otherUser->id,
            'site_id' => $this->otherSite->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $job1->id);
    }

    /** @test */
    public function list_jobs_filters_by_status(): void
    {
        AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs?status=pending");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    /** @test */
    public function list_jobs_forbids_access_to_other_users_site(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->otherSite->id}/analysis/jobs");

        $response->assertStatus(403);
    }

    /** @test */
    public function list_jobs_returns_404_for_nonexistent_site(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sites/99999/analysis/jobs');

        $response->assertStatus(404);
    }

    /** @test */
    public function list_jobs_paginates_results(): void
    {
        // Create 20 jobs
        for ($i = 0; $i < 20; $i++) {
            AnalysisJob::create([
                'user_id' => $this->user->id,
                'site_id' => $this->site->id,
                'status' => 'completed',
                'total_indicators' => 1,
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs?perPage=10");

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    // =========================================================================
    // STORE (CREATE JOB) TESTS
    // =========================================================================

    /** @test */
    public function create_job_requires_authentication(): void
    {
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function create_job_creates_job_with_valid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags', 'headings'],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'total_indicators',
                    'results',
                ],
            ]);

        $this->assertDatabaseHas('analysis_jobs', [
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function create_job_validates_indicators_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicators']);
    }

    /** @test */
    public function create_job_validates_indicators_is_array(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => 'meta_tags',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicators']);
    }

    /** @test */
    public function create_job_validates_indicators_not_empty(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicators']);
    }

    /** @test */
    public function create_job_forbids_access_to_other_users_site(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->otherSite->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function create_job_returns_rate_limit_headers(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);

        $response->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    /** @test */
    public function create_job_returns_429_when_rate_limited(): void
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

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);

        $response->assertStatus(429)
            ->assertJsonStructure([
                'error' => ['code', 'message', 'retry_after'],
            ]);
    }

    // =========================================================================
    // SHOW (GET JOB) TESTS
    // =========================================================================

    /** @test */
    public function show_job_requires_authentication(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function show_job_returns_job_with_results(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
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
            'indicator_name' => 'Headings Structure',
            'status' => 'completed',
            'score' => 90.0,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'totalIndicators',
                    'results' => [
                        '*' => ['indicatorSlug', 'indicatorName', 'status', 'score'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function show_job_forbids_access_to_other_users_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->otherUser->id,
            'site_id' => $this->otherSite->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function show_job_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analysis/jobs/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // DESTROY (CANCEL JOB) TESTS
    // =========================================================================

    /** @test */
    public function cancel_job_requires_authentication(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function cancel_job_cancels_pending_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $job->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function cancel_job_cancels_running_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 1,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    /** @test */
    public function cancel_job_fails_for_completed_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(400);
    }

    /** @test */
    public function cancel_job_forbids_access_to_other_users_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->otherUser->id,
            'site_id' => $this->otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // START JOB TESTS
    // =========================================================================

    /** @test */
    public function start_job_requires_authentication(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $response = $this->postJson("/api/v1/analysis/jobs/{$job->id}/start");

        $response->assertStatus(401);
    }

    /** @test */
    public function start_job_dispatches_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/analysis/jobs/{$job->id}/start");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Job started.',
                'job_id' => $job->id,
            ]);
    }

    /** @test */
    public function start_job_fails_for_non_pending_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/analysis/jobs/{$job->id}/start");

        $response->assertStatus(422);
    }

    /** @test */
    public function start_job_forbids_access_to_other_users_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->otherUser->id,
            'site_id' => $this->otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/analysis/jobs/{$job->id}/start");

        $response->assertStatus(404);
    }

    // =========================================================================
    // PROGRESS (POLLING) TESTS
    // =========================================================================

    /** @test */
    public function progress_requires_authentication(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 2,
            'completed_indicators' => 1,
        ]);

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertStatus(401);
    }

    /** @test */
    public function progress_returns_job_progress_data(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 4,
            'completed_indicators' => 2,
            'failed_indicators' => 0,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $job->id,
                'status' => 'running',
                'total_indicators' => 4,
                'completed_indicators' => 2,
            ]);
    }

    /** @test */
    public function progress_returns_rate_limit_headers(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-Polling-Interval');
    }

    /** @test */
    public function progress_forbids_access_to_other_users_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->otherUser->id,
            'site_id' => $this->otherSite->id,
            'status' => 'running',
            'total_indicators' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/analysis/jobs/{$job->id}/progress");

        $response->assertStatus(404);
    }

    /** @test */
    public function progress_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analysis/jobs/99999/progress');

        $response->assertStatus(404);
    }

    // =========================================================================
    // INDICATORS LIST TESTS
    // =========================================================================

    /** @test */
    public function indicators_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/analysis/indicators');

        $response->assertStatus(401);
    }

    /** @test */
    public function indicators_returns_available_indicators(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analysis/indicators');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'indicators' => [
                    '*' => ['slug', 'name', 'max_score'],
                ],
            ]);
    }

    // =========================================================================
    // INVALID ID FORMAT TESTS
    // =========================================================================

    /** @test */
    public function show_job_rejects_non_numeric_id(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analysis/jobs/abc');

        $response->assertStatus(404);
    }

    /** @test */
    public function cancel_job_rejects_non_numeric_id(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/analysis/jobs/abc');

        $response->assertStatus(404);
    }

    /** @test */
    public function progress_rejects_non_numeric_id(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/analysis/jobs/abc/progress');

        $response->assertStatus(404);
    }

    /** @test */
    public function start_job_rejects_non_numeric_id(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/analysis/jobs/abc/start');

        $response->assertStatus(404);
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    /** @test */
    public function full_job_lifecycle_via_api(): void
    {
        // Create job
        $createResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags', 'headings'],
            ]);

        $createResponse->assertStatus(201);
        $jobId = $createResponse->json('data.id');

        // Get job
        $showResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/analysis/jobs/{$jobId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        // Start job
        $startResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/analysis/jobs/{$jobId}/start");

        $startResponse->assertStatus(200);

        // Get progress
        $progressResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/analysis/jobs/{$jobId}/progress");

        $progressResponse->assertStatus(200);
    }

    /** @test */
    public function job_creation_with_all_valid_indicators(): void
    {
        $indicators = [
            'meta_tags', 'headings', 'images', 'links',
            'performance', 'mobile', 'security', 'structured_data',
            'content_quality', 'url_structure', 'canonicalization',
            'robots_txt', 'sitemap', 'internal_linking',
            'external_links', 'social_tags', 'crawlability',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => $indicators,
            ]);

        // Debug: show actual response if test fails
        if ($response->status() !== 201) {
            $this->fail('Response status: ' . $response->status() . ' Body: ' . $response->getContent());
        }

        $response->assertJsonPath('data.totalIndicators', count($indicators));
    }
}
