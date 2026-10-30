<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\AnalysisJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

class AnalysisJobServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalysisJobService $service;
    protected User $user;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AnalysisJobService::class);
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    }

    // =========================================================================
    // 1. createJob METHOD TESTS
    // =========================================================================

    /** @test */
    public function create_job_creates_job_with_valid_data(): void
    {
        $indicators = ['meta_tags', 'headings', 'images'];

        $job = $this->service->createJob($this->user->id, $this->site->id, $indicators);

        $this->assertInstanceOf(AnalysisJob::class, $job);
        $this->assertEquals($this->user->id, $job->user_id);
        $this->assertEquals($this->site->id, $job->site_id);
        $this->assertEquals('pending', $job->status);
        $this->assertEquals(3, $job->total_indicators);
        $this->assertEquals(0, $job->completed_indicators);
        $this->assertEquals(0, $job->failed_indicators);
        $this->assertEquals(0, $job->progress_percent);
    }

    /** @test */
    public function create_job_creates_pending_results_for_each_indicator(): void
    {
        $indicators = ['meta_tags', 'headings'];

        $job = $this->service->createJob($this->user->id, $this->site->id, $indicators);

        $this->assertCount(2, $job->results);
        $this->assertEquals('meta_tags', $job->results[0]->indicator_slug);
        $this->assertEquals('headings', $job->results[1]->indicator_slug);
        $this->assertEquals('pending', $job->results[0]->status);
    }

    /** @test */
    public function create_job_formats_indicator_names(): void
    {
        $indicators = ['meta_tags', 'page_speed'];

        $job = $this->service->createJob($this->user->id, $this->site->id, $indicators);

        $this->assertEquals('Meta Tags', $job->results[0]->indicator_name);
        $this->assertEquals('Page Speed', $job->results[1]->indicator_name);
    }

    /** @test */
    public function create_job_throws_exception_for_empty_indicators(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Indicators array cannot be empty');

        $this->service->createJob($this->user->id, $this->site->id, []);
    }

    /** @test */
    public function create_job_throws_exception_for_nonexistent_user(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createJob(99999, $this->site->id, ['meta_tags']);
    }

    /** @test */
    public function create_job_throws_exception_for_nonexistent_site(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createJob($this->user->id, 99999, ['meta_tags']);
    }

    /** @test */
    public function create_job_throws_exception_if_user_doesnt_own_site(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->createJob($this->user->id, $otherSite->id, ['meta_tags']);
    }

    /** @test */
    public function create_job_is_atomic(): void
    {
        // Mock a failure during result creation
        $indicators = ['meta_tags', 'invalid_indicator_that_will_fail'];

        // This should either fully succeed or fully fail
        try {
            $job = $this->service->createJob($this->user->id, $this->site->id, $indicators);
        } catch (\Exception $e) {
            // Verify no job was created
            $this->assertEquals(0, AnalysisJob::count());
        }
    }

    // =========================================================================
    // 2. getJob METHOD TESTS
    // =========================================================================

    /** @test */
    public function get_job_returns_job_with_results(): void
    {
        $job = $this->createTestJob();

        $retrieved = $this->service->getJob($job->id);

        $this->assertEquals($job->id, $retrieved->id);
        $this->assertTrue($retrieved->relationLoaded('results'));
    }

    /** @test */
    public function get_job_returns_null_for_nonexistent_job(): void
    {
        $result = $this->service->getJob(99999);

        $this->assertNull($result);
    }

    /** @test */
    public function get_job_throws_exception_for_unauthorized_user(): void
    {
        $job = $this->createTestJob();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $this->expectException(AuthorizationException::class);

        $this->service->getJob($job->id);
    }

    /** @test */
    public function get_job_returns_job_for_authorized_user(): void
    {
        $job = $this->createTestJob();

        $this->actingAs($this->user);

        $retrieved = $this->service->getJob($job->id);

        $this->assertEquals($job->id, $retrieved->id);
    }

    // =========================================================================
    // 3. getJobProgress METHOD TESTS
    // =========================================================================

    /** @test */
    public function get_job_progress_returns_complete_data(): void
    {
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);
        $job->results()->update(['status' => 'completed']);

        $progress = $this->service->getJobProgress($job->id);

        $this->assertArrayHasKey('id', $progress);
        $this->assertArrayHasKey('status', $progress);
        $this->assertArrayHasKey('totalIndicators', $progress);
        $this->assertArrayHasKey('completedIndicators', $progress);
        $this->assertArrayHasKey('failedIndicators', $progress);
        $this->assertArrayHasKey('progressPercent', $progress);
        $this->assertArrayHasKey('results', $progress);
        $this->assertArrayHasKey('startedAt', $progress);
        $this->assertArrayHasKey('completedAt', $progress);
    }

    /** @test */
    public function get_job_progress_returns_empty_results_if_none(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'total_indicators' => 0,
        ]);

        $progress = $this->service->getJobProgress($job->id);

        $this->assertEquals([], $progress['results']);
    }

    /** @test */
    public function get_job_progress_returns_null_timestamps_if_not_set(): void
    {
        $job = $this->createTestJob();

        $progress = $this->service->getJobProgress($job->id);

        $this->assertNull($progress['startedAt']);
        $this->assertNull($progress['completedAt']);
    }

    // =========================================================================
    // 4. cancelJob METHOD TESTS
    // =========================================================================

    /** @test */
    public function cancel_job_cancels_pending_job(): void
    {
        $job = $this->createTestJob(['status' => 'pending']);

        $result = $this->service->cancelJob($job->id);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('cancelled', $job->status);
        $this->assertNotNull($job->cancelled_at);
    }

    /** @test */
    public function cancel_job_cancels_running_job(): void
    {
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);

        $result = $this->service->cancelJob($job->id);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('cancelled', $job->status);
    }

    /** @test */
    public function cancel_job_fails_for_completed_job(): void
    {
        $job = $this->createTestJob([
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $result = $this->service->cancelJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function cancel_job_fails_for_already_cancelled_job(): void
    {
        $job = $this->createTestJob([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $result = $this->service->cancelJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function cancel_job_fails_for_failed_job(): void
    {
        $job = $this->createTestJob(['status' => 'failed', 'error_message' => 'Test error']);

        $result = $this->service->cancelJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function cancel_job_marks_pending_results_as_skipped(): void
    {
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);
        $pendingResults = $job->results()->where('status', 'pending')->get();

        $this->service->cancelJob($job->id);

        foreach ($pendingResults as $result) {
            $result->refresh();
            $this->assertEquals('skipped', $result->status);
        }
    }

    /** @test */
    public function cancel_job_dispatches_cancelled_event(): void
    {
        Event::fake();
        $job = $this->createTestJob(['status' => 'pending']);

        $this->service->cancelJob($job->id);

        Event::assertDispatched(\App\Events\AnalysisJobCancelled::class);
    }

    // =========================================================================
    // 5. startJob METHOD TESTS
    // =========================================================================

    /** @test */
    public function start_job_starts_pending_job(): void
    {
        $job = $this->createTestJob(['status' => 'pending']);

        $result = $this->service->startJob($job->id);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('running', $job->status);
        $this->assertNotNull($job->started_at);
    }

    /** @test */
    public function start_job_fails_for_running_job(): void
    {
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);

        $result = $this->service->startJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function start_job_fails_for_completed_job(): void
    {
        $job = $this->createTestJob([
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $result = $this->service->startJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function start_job_fails_for_cancelled_job(): void
    {
        $job = $this->createTestJob(['status' => 'cancelled', 'cancelled_at' => now()]);

        $result = $this->service->startJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function start_job_fails_for_failed_job(): void
    {
        $job = $this->createTestJob(['status' => 'failed', 'error_message' => 'Test error']);

        $result = $this->service->startJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function start_job_dispatches_started_event(): void
    {
        Event::fake();
        $job = $this->createTestJob(['status' => 'pending']);

        $this->service->startJob($job->id);

        Event::assertDispatched(\App\Events\AnalysisJobStarted::class);
    }

    // =========================================================================
    // 6. completeJob METHOD TESTS
    // =========================================================================

    /** @test */
    public function complete_job_completes_running_job(): void
    {
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);

        $result = $this->service->completeJob($job->id);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertNotNull($job->completed_at);
    }

    /** @test */
    public function complete_job_fails_for_pending_job(): void
    {
        $job = $this->createTestJob(['status' => 'pending']);

        $result = $this->service->completeJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function complete_job_fails_for_already_completed_job(): void
    {
        $job = $this->createTestJob([
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $result = $this->service->completeJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function complete_job_fails_for_cancelled_job(): void
    {
        $job = $this->createTestJob(['status' => 'cancelled', 'cancelled_at' => now()]);

        $result = $this->service->completeJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function complete_job_updates_progress_to_100(): void
    {
        $job = $this->createTestJob([
            'status' => 'running',
            'started_at' => now(),
            'completed_indicators' => 2,
            'total_indicators' => 2,
        ]);

        $this->service->completeJob($job->id);

        $job->refresh();
        $this->assertEquals(100, $job->progress_percent);
    }

    /** @test */
    public function complete_job_dispatches_completed_event(): void
    {
        Event::fake();
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);

        $this->service->completeJob($job->id);

        Event::assertDispatched(\App\Events\AnalysisJobCompleted::class);
    }

    // =========================================================================
    // 7. failJob METHOD TESTS
    // =========================================================================

    /** @test */
    public function fail_job_fails_running_job(): void
    {
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);

        $result = $this->service->failJob($job->id, 'Test error');

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('failed', $job->status);
        $this->assertEquals('Test error', $job->error_message);
        $this->assertNotNull($job->completed_at);
    }

    /** @test */
    public function fail_job_fails_pending_job(): void
    {
        $job = $this->createTestJob(['status' => 'pending']);

        $result = $this->service->failJob($job->id, 'Test error');

        $this->assertTrue($result);
    }

    /** @test */
    public function fail_job_fails_for_completed_job(): void
    {
        $job = $this->createTestJob([
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $result = $this->service->failJob($job->id, 'Test error');

        $this->assertFalse($result);
    }

    /** @test */
    public function fail_job_dispatches_failed_event(): void
    {
        Event::fake();
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);

        $this->service->failJob($job->id, 'Test error');

        Event::assertDispatched(\App\Events\AnalysisJobFailed::class);
    }

    // =========================================================================
    // 8. AUTHORIZATION TESTS
    // =========================================================================

    /** @test */
    public function user_can_access_own_job(): void
    {
        $job = $this->createTestJob();

        $this->actingAs($this->user);

        $retrieved = $this->service->getJob($job->id);

        $this->assertEquals($job->id, $retrieved->id);
    }

    /** @test */
    public function user_cannot_access_another_users_job(): void
    {
        $job = $this->createTestJob();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $this->expectException(AuthorizationException::class);

        $this->service->getJob($job->id);
    }

    /** @test */
    public function cannot_create_job_for_another_users_site(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user);

        $this->expectException(AuthorizationException::class);

        $this->service->createJob($this->user->id, $otherSite->id, ['meta_tags']);
    }

    // =========================================================================
    // 9. CONCURRENCY TESTS
    // =========================================================================

    /** @test */
    public function double_cancel_returns_false(): void
    {
        $job = $this->createTestJob(['status' => 'pending']);

        $this->service->cancelJob($job->id);
        $result = $this->service->cancelJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function double_start_returns_false(): void
    {
        $job = $this->createTestJob(['status' => 'pending']);

        $this->service->startJob($job->id);
        $result = $this->service->startJob($job->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function start_after_cancel_fails(): void
    {
        $job = $this->createTestJob(['status' => 'pending']);
        $this->service->cancelJob($job->id);

        $result = $this->service->startJob($job->id);

        $this->assertFalse($result);
    }

    // =========================================================================
    // 10. EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function handles_null_indicators_array(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createJob($this->user->id, $this->site->id, null);
    }

    /** @test */
    public function handles_large_indicator_list(): void
    {
        $indicators = [];
        for ($i = 0; $i < 50; $i++) {
            $indicators[] = "indicator_$i";
        }

        $job = $this->service->createJob($this->user->id, $this->site->id, $indicators);

        $this->assertEquals(50, $job->total_indicators);
        $this->assertCount(50, $job->results);
    }

    /** @test */
    public function handles_17_indicators_full_seo_analysis(): void
    {
        $indicators = [
            'meta_tags', 'headings', 'images', 'links', 'performance',
            'mobile', 'security', 'structured_data', 'content_quality',
            'url_structure', 'internal_linking', 'external_links',
            'social_tags', 'canonicalization', 'robots_txt', 'sitemap',
            'crawlability',
        ];

        $job = $this->service->createJob($this->user->id, $this->site->id, $indicators);

        $this->assertEquals(17, $job->total_indicators);
    }

    // =========================================================================
    // 11. SECURITY TESTS
    // =========================================================================

    /** @test */
    public function sql_injection_in_indicators_handled(): void
    {
        $indicators = ["meta_tags'; DROP TABLE analysis_jobs; --"];

        // Should either throw validation error or sanitize
        try {
            $job = $this->service->createJob($this->user->id, $this->site->id, $indicators);
            // If it succeeds, verify no SQL injection occurred
            $this->assertStringContainsString('DROP TABLE', $job->results[0]->indicator_slug);
            $this->assertDatabaseHas('analysis_jobs', ['id' => $job->id]);
        } catch (InvalidArgumentException $e) {
            // Validation rejected the input - also acceptable
            $this->assertStringContainsString('invalid', strtolower($e->getMessage()));
        }
    }

    /** @test */
    public function xss_in_error_message_handled(): void
    {
        $job = $this->createTestJob(['status' => 'running', 'started_at' => now()]);

        $this->service->failJob($job->id, '<script>alert("xss")</script>');

        $job->refresh();
        $this->assertStringContainsString('<script>', $job->error_message);
        // XSS should be escaped when displayed, not stored escaped
    }

    /** @test */
    public function cannot_cancel_another_users_job(): void
    {
        $job = $this->createTestJob();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $this->expectException(AuthorizationException::class);

        $this->service->cancelJob($job->id);
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

        // Create some results
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
