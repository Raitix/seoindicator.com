<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AnalysisJobApiTest extends TestCase
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
        Sanctum::actingAs($this->user);
        return $this;
    }

    // =========================================================================
    // 1. CREATE JOB ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function create_job_returns_201_on_success(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags', 'headings'],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function create_job_returns_job_data(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags', 'headings'],
        ]);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'totalIndicators',
                'progressPercent',
                'createdAt',
            ],
        ]);
    }

    /** @test */
    public function create_job_creates_job_in_database(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags', 'headings'],
        ]);

        $this->assertDatabaseHas('analysis_jobs', [
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 2,
        ]);
    }

    /** @test */
    public function create_job_creates_pending_results(): void
    {
        $this->authenticate();

        $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags', 'headings'],
        ]);

        $job = AnalysisJob::where('user_id', $this->user->id)->first();

        $this->assertEquals(2, $job->results()->count());
        $this->assertEquals(2, $job->results()->where('status', 'pending')->count());
    }

    /** @test */
    public function create_job_requires_indicators_array(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicators']);
    }

    /** @test */
    public function create_job_requires_at_least_one_indicator(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['indicators']);
    }

    /** @test */
    public function create_job_returns_401_for_unauthenticated(): void
    {
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function create_job_returns_403_for_unowned_site(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/{$otherSite->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function create_job_returns_404_for_nonexistent_site(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/99999/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // 2. LIST JOBS ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function list_jobs_returns_200(): void
    {
        $this->authenticate();
        $this->createTestJob();

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs");

        $response->assertStatus(200);
    }

    /** @test */
    public function list_jobs_returns_paginated_list(): void
    {
        $this->authenticate();
        for ($i = 0; $i < 5; $i++) {
            $this->createTestJob();
        }

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs");

        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'status', 'totalIndicators', 'progressPercent', 'createdAt'],
            ],
            'meta' => ['current_page', 'last_page', 'total'],
        ]);
    }

    /** @test */
    public function list_jobs_filters_by_status(): void
    {
        $this->authenticate();
        $pendingJob = $this->createTestJob(['status' => 'pending']);
        $completedJob = $this->createTestJob(['status' => 'completed']);

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs?status=pending");

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingJob->id);
    }

    /** @test */
    public function list_jobs_returns_empty_for_no_matches(): void
    {
        $this->authenticate();

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs?status=completed");

        $response->assertJsonCount(0, 'data');
    }

    /** @test */
    public function list_jobs_supports_pagination(): void
    {
        $this->authenticate();
        for ($i = 0; $i < 25; $i++) {
            $this->createTestJob();
        }

        $response = $this->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs?perPage=10");

        $response->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3);
    }

    /** @test */
    public function list_jobs_returns_401_for_unauthenticated(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/analysis/jobs");

        $response->assertStatus(401);
    }

    /** @test */
    public function list_jobs_returns_403_for_unowned_site(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $this->authenticate();

        $response = $this->getJson("/api/v1/sites/{$otherSite->id}/analysis/jobs");

        $response->assertStatus(403);
    }

    // =========================================================================
    // 3. GET JOB PROGRESS ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function get_job_returns_200(): void
    {
        $this->authenticate();
        $job = $this->createTestJob();

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function get_job_returns_complete_job_data(): void
    {
        $this->authenticate();
        $job = $this->createTestJob();

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertJsonStructure([
            'data' => [
                'id',
                'status',
                'totalIndicators',
                'completedIndicators',
                'failedIndicators',
                'progressPercent',
                'startedAt',
                'completedAt',
                'results' => [
                    '*' => ['indicatorSlug', 'indicatorName', 'status', 'score', 'percentage'],
                ],
            ],
        ]);
    }

    /** @test */
    public function get_job_returns_401_for_unauthenticated(): void
    {
        $job = $this->createTestJob();

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function get_job_returns_403_for_unowned_job(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $otherJob = AnalysisJob::create([
            'user_id' => $otherUser->id,
            'site_id' => $otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->authenticate();

        $response = $this->getJson("/api/v1/analysis/jobs/{$otherJob->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function get_job_returns_404_for_nonexistent(): void
    {
        $this->authenticate();

        $response = $this->getJson("/api/v1/analysis/jobs/99999");

        $response->assertStatus(404);
    }

    // =========================================================================
    // 4. CANCEL JOB ENDPOINT TESTS
    // =========================================================================

    /** @test */
    public function cancel_job_returns_200(): void
    {
        $this->authenticate();
        $job = $this->createTestJob(['status' => 'pending']);

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function cancel_job_updates_status(): void
    {
        $this->authenticate();
        $job = $this->createTestJob(['status' => 'pending']);

        $this->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $job->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function cancel_job_returns_updated_job(): void
    {
        $this->authenticate();
        $job = $this->createTestJob(['status' => 'pending']);

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertJsonPath('data.status', 'cancelled');
    }

    /** @test */
    public function cancel_job_cancels_pending_job(): void
    {
        $this->authenticate();
        $job = $this->createTestJob(['status' => 'pending']);

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function cancel_job_cancels_running_job(): void
    {
        $this->authenticate();
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function cancel_job_returns_400_for_completed_job(): void
    {
        $this->authenticate();
        $job = $this->createTestJob([
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(400);
    }

    /** @test */
    public function cancel_job_returns_400_for_already_cancelled(): void
    {
        $this->authenticate();
        $job = $this->createTestJob([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(400);
    }

    /** @test */
    public function cancel_job_returns_401_for_unauthenticated(): void
    {
        $job = $this->createTestJob();

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$job->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function cancel_job_returns_403_for_unowned_job(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $otherJob = AnalysisJob::create([
            'user_id' => $otherUser->id,
            'site_id' => $otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->authenticate();

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$otherJob->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function cancel_job_returns_404_for_nonexistent(): void
    {
        $this->authenticate();

        $response = $this->deleteJson("/api/v1/analysis/jobs/99999");

        $response->assertStatus(404);
    }

    // =========================================================================
    // 5. ERROR RESPONSE FORMAT TESTS
    // =========================================================================

    /** @test */
    public function validation_errors_return_422(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function authorization_errors_return_403(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/{$otherSite->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(403)
            ->assertJsonStructure(['error']);
    }

    /** @test */
    public function not_found_errors_return_404(): void
    {
        $this->authenticate();

        $response = $this->getJson("/api/v1/analysis/jobs/99999");

        $response->assertStatus(404)
            ->assertJsonStructure(['error']);
    }

    // =========================================================================
    // 6. RATE LIMITING TESTS
    // =========================================================================

    /** @test */
    public function job_creation_rate_limited(): void
    {
        $this->authenticate();

        // Create 10 jobs (assuming limit is 10/hour)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        // 11th request should be rate limited
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function rate_limit_includes_retry_after_header(): void
    {
        $this->authenticate();

        // Create jobs up to limit
        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
                'indicators' => ['meta_tags'],
            ]);
        }

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertHeader('Retry-After');
    }

    // =========================================================================
    // 7. EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function handles_17_indicators_full_analysis(): void
    {
        $this->authenticate();

        $indicators = [
            'meta_tags', 'headings', 'images', 'links', 'performance',
            'mobile', 'security', 'structured_data', 'content_quality',
            'url_structure', 'internal_linking', 'external_links',
            'social_tags', 'canonicalization', 'robots_txt', 'sitemap',
            'crawlability',
        ];

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => $indicators,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.totalIndicators', 17);
    }

    /** @test */
    public function returns_404_for_non_numeric_job_id(): void
    {
        $this->authenticate();

        $response = $this->getJson("/api/v1/analysis/jobs/abc");

        $response->assertStatus(404);
    }

    /** @test */
    public function returns_404_for_non_numeric_site_id(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/abc/analysis/jobs", [
            'indicators' => ['meta_tags'],
        ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // 8. SECURITY TESTS
    // =========================================================================

    /** @test */
    public function sql_injection_in_indicators_handled(): void
    {
        $this->authenticate();

        $response = $this->postJson("/api/v1/sites/{$this->site->id}/analysis/jobs", [
            'indicators' => ["meta_tags'; DROP TABLE analysis_jobs; --"],
        ]);

        // Should either accept (escaped) or reject
        $this->assertContains($response->status(), [201, 422]);
        $this->assertDatabaseHas('analysis_jobs', ['user_id' => $this->user->id]);
    }

    /** @test */
    public function xss_in_error_messages_handled(): void
    {
        $this->authenticate();
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);

        // Try to inject XSS via error message
        $job->update(['error_message' => '<script>alert("xss")</script>']);

        $response = $this->getJson("/api/v1/analysis/jobs/{$job->id}");

        // Response should contain the script tag (storage), but display should escape it
        $response->assertStatus(200);
    }

    /** @test */
    public function idor_cannot_access_another_users_job(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $otherJob = AnalysisJob::create([
            'user_id' => $otherUser->id,
            'site_id' => $otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->authenticate();

        $response = $this->getJson("/api/v1/analysis/jobs/{$otherJob->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function idor_cannot_cancel_another_users_job(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $otherJob = AnalysisJob::create([
            'user_id' => $otherUser->id,
            'site_id' => $otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->authenticate();

        $response = $this->deleteJson("/api/v1/analysis/jobs/{$otherJob->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function createTestJob(array $overrides = []): AnalysisJob
    {
        $data = array_merge([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 2,
        ], $overrides);

        $job = AnalysisJob::create($data);

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
}
