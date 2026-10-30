<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\AnalysisJobService;
use App\Services\ProgressService;
use App\Services\Analysis\Indicators\IndicatorResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

/**
 * Integration tests for complete analysis job lifecycle.
 *
 * Tests the full workflow from job creation through completion,
 * including all service interactions and database state changes.
 *
 * @see TASK-19-16
 */
class AnalysisJobLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;
    protected AnalysisJobService $jobService;
    protected ProgressService $progressService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);

        $this->jobService = app(AnalysisJobService::class);
        $this->progressService = app(ProgressService::class);

        Event::fake();
    }

    // =========================================================================
    // FULL LIFECYCLE TESTS
    // =========================================================================

    /** @test */
    public function complete_successful_job_lifecycle(): void
    {
        // 1. Create job
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->assertNotNull($job);
        $this->assertEquals('pending', $job->status);
        $this->assertEquals(3, $job->total_indicators);

        // Verify all results created
        $this->assertEquals(3, $job->results()->count());
        $this->assertEquals(3, $job->results()->where('status', 'pending')->count());

        // 2. Start job
        $started = $this->jobService->startJob($job->id);
        $this->assertTrue($started);

        $job->refresh();
        $this->assertEquals('running', $job->status);
        $this->assertNotNull($job->started_at);

        // 3. Process each indicator
        $indicators = ['meta_tags', 'headings', 'images'];
        foreach ($indicators as $index => $slug) {
            // Mark as running
            $this->progressService->markRunning($job->id, $slug);

            // Complete with result
            $result = IndicatorResult::success(
                slug: $slug,
                name: ucfirst(str_replace('_', ' ', $slug)),
                score: 80.0 + ($index * 5),
                maxScore: 100.0,
                data: ['tested' => true],
                recommendations: [],
                processingTimeMs: 100 + ($index * 50),
            );

            $this->progressService->markComplete($job->id, $slug, $result);
        }

        // 4. Update progress (this auto-completes the job when all indicators are done)
        $this->jobService->updateProgress($job->id);

        // 5. Verify job auto-completed
        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertEquals(100, $job->progress_percent);
        $this->assertEquals(3, $job->completed_indicators);
        $this->assertEquals(0, $job->failed_indicators);
        $this->assertNotNull($job->completed_at);

        // 6. Verify all results
        foreach ($job->results as $result) {
            $this->assertEquals('completed', $result->status);
            $this->assertNotNull($result->score);
            $this->assertNotNull($result->completed_at);
        }
    }

    /** @test */
    public function job_lifecycle_with_partial_failures(): void
    {
        // Create job
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->jobService->startJob($job->id);

        // Complete first indicator
        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markComplete(
            $job->id,
            'meta_tags',
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 100)
        );

        // Fail second indicator
        $this->progressService->markRunning($job->id, 'headings');
        $this->progressService->markFailed($job->id, 'headings', 'Connection timeout');

        // Complete third indicator
        $this->progressService->markRunning($job->id, 'images');
        $this->progressService->markComplete(
            $job->id,
            'images',
            IndicatorResult::success('images', 'Images', 90.0, 100.0, processingTimeMs: 150)
        );

        // Update progress to auto-complete
        $this->jobService->updateProgress($job->id);

        // Verify final state
        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertEquals(100, $job->progress_percent);
        $this->assertEquals(2, $job->completed_indicators);
        $this->assertEquals(1, $job->failed_indicators);

        // Verify results
        $metaResult = $job->results()->where('indicator_slug', 'meta_tags')->first();
        $this->assertEquals('completed', $metaResult->status);
        $this->assertEquals(85.0, (float) $metaResult->score);

        $headingsResult = $job->results()->where('indicator_slug', 'headings')->first();
        $this->assertEquals('failed', $headingsResult->status);
        $this->assertEquals('Connection timeout', $headingsResult->error_message);

        $imagesResult = $job->results()->where('indicator_slug', 'images')->first();
        $this->assertEquals('completed', $imagesResult->status);
    }

    /** @test */
    public function job_lifecycle_with_all_failures(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings']
        );

        $this->jobService->startJob($job->id);

        // Fail all indicators
        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markFailed($job->id, 'meta_tags', 'Error 1');

        $this->progressService->markRunning($job->id, 'headings');
        $this->progressService->markFailed($job->id, 'headings', 'Error 2');

        // Update progress to auto-complete
        $this->jobService->updateProgress($job->id);

        // Job should still complete
        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertEquals(100, $job->progress_percent);
        $this->assertEquals(0, $job->completed_indicators);
        $this->assertEquals(2, $job->failed_indicators);
    }

    // =========================================================================
    // CANCELLATION TESTS
    // =========================================================================

    /** @test */
    public function cancel_pending_job(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->jobService->cancelJob($job->id, $this->user->id);

        $job->refresh();
        $this->assertEquals('cancelled', $job->status);
        $this->assertNotNull($job->cancelled_at);

        // All pending results should be skipped
        $skippedCount = $job->results()->where('status', 'skipped')->count();
        $this->assertEquals(3, $skippedCount);
    }

    /** @test */
    public function cancel_running_job_mid_processing(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->jobService->startJob($job->id);

        // Complete one indicator
        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markComplete(
            $job->id,
            'meta_tags',
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 100)
        );

        // Start second indicator
        $this->progressService->markRunning($job->id, 'headings');

        // Cancel job
        $this->jobService->cancelJob($job->id, $this->user->id);

        $job->refresh();
        $this->assertEquals('cancelled', $job->status);

        // Verify results: one completed, one running (now skipped), one pending (now skipped)
        $completedCount = $job->results()->where('status', 'completed')->count();
        $skippedCount = $job->results()->where('status', 'skipped')->count();

        $this->assertEquals(1, $completedCount);
        $this->assertEquals(2, $skippedCount);
    }

    /** @test */
    public function cannot_cancel_completed_job(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->jobService->startJob($job->id);
        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markComplete(
            $job->id,
            'meta_tags',
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 100)
        );

        // Complete the job
        $this->jobService->updateProgress($job->id);

        $job->refresh();
        $this->assertEquals('completed', $job->status);

        // Attempt to cancel
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot cancel job');

        $this->jobService->cancelJob($job->id, $this->user->id);
    }

    // =========================================================================
    // SKIPPED INDICATOR TESTS
    // =========================================================================

    /** @test */
    public function job_with_skipped_indicators_completes(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->jobService->startJob($job->id);

        // Complete first
        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markComplete(
            $job->id,
            'meta_tags',
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 100)
        );

        // Skip second
        $this->progressService->markSkipped($job->id, 'headings', 'Dependency failed');

        // Complete third
        $this->progressService->markRunning($job->id, 'images');
        $this->progressService->markComplete(
            $job->id,
            'images',
            IndicatorResult::success('images', 'Images', 90.0, 100.0, processingTimeMs: 150)
        );

        // Manually complete the job (skipped indicators don't count toward auto-completion)
        $this->jobService->completeJob($job->id);

        $job->refresh();
        $this->assertEquals('completed', $job->status);

        $skippedCount = $job->results()->where('status', 'skipped')->count();
        $this->assertEquals(1, $skippedCount);
    }

    // =========================================================================
    // RATE LIMITING INTEGRATION TESTS
    // =========================================================================

    /** @test */
    public function job_creation_enforces_rate_limits(): void
    {
        // Create jobs up to the limit
        for ($i = 0; $i < 2; $i++) {
            $this->jobService->createJob(
                $this->user->id,
                $this->site->id,
                ['meta_tags']
            );
        }

        // Third concurrent job should fail (assuming limit is 2)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('concurrent');

        $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['headings']
        );
    }

    // =========================================================================
    // AUTHORIZATION INTEGRATION TESTS
    // =========================================================================

    /** @test */
    public function user_cannot_access_other_users_job(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $job = $this->jobService->createJob(
            $otherUser->id,
            $otherSite->id,
            ['meta_tags']
        );

        // Current user should not be able to access
        $retrieved = $this->jobService->getJob($job->id, $this->user->id);

        $this->assertNull($retrieved);
    }

    /** @test */
    public function user_cannot_cancel_other_users_job(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $job = AnalysisJob::create([
            'user_id' => $otherUser->id,
            'site_id' => $otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->jobService->cancelJob($job->id, $this->user->id);
    }

    // =========================================================================
    // GET JOB PROGRESS TESTS
    // =========================================================================

    /** @test */
    public function get_job_progress_returns_complete_data(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings']
        );

        $this->jobService->startJob($job->id);

        // Complete one indicator
        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markComplete(
            $job->id,
            'meta_tags',
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 150)
        );

        $progress = $this->jobService->getJobProgress($job->id, $this->user->id);

        $this->assertNotNull($progress);
        $this->assertEquals($job->id, $progress['id']);
        $this->assertEquals('running', $progress['status']);
        $this->assertEquals(2, $progress['total_indicators']);
        $this->assertEquals(1, $progress['completed_indicators']);
        $this->assertEquals(50, $progress['progress_percent']);

        $this->assertCount(2, $progress['results']);

        // Find the completed result by slug
        $metaResult = collect($progress['results'])->firstWhere('indicator_slug', 'meta_tags');
        $this->assertNotNull($metaResult);
        $this->assertEquals('completed', $metaResult['status']);
        $this->assertEquals(85.0, $metaResult['score']);
    }

    /** @test */
    public function get_job_progress_includes_duration(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->jobService->startJob($job->id);
        sleep(1); // Simulate processing time

        $progress = $this->jobService->getJobProgress($job->id, $this->user->id);

        $this->assertNotNull($progress['duration_seconds']);
        $this->assertGreaterThanOrEqual(1, $progress['duration_seconds']);
        $this->assertNotNull($progress['duration_human']);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function job_with_17_indicators(): void
    {
        $indicators = array_keys(AnalysisJobService::INDICATORS);

        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            $indicators
        );

        $this->assertGreaterThanOrEqual(17, $job->total_indicators);
        $this->assertEquals($job->total_indicators, $job->results()->count());
    }

    /** @test */
    public function job_progress_updates_correctly(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images', 'links']
        );

        $this->jobService->startJob($job->id);

        // Complete indicators one by one
        $indicators = ['meta_tags', 'headings', 'images', 'links'];
        foreach ($indicators as $index => $slug) {
            $this->progressService->markRunning($job->id, $slug);
            $this->progressService->markComplete(
                $job->id,
                $slug,
                IndicatorResult::success($slug, ucfirst($slug), 80.0, 100.0, processingTimeMs: 100)
            );

            $job->refresh();
            $expectedProgress = (int) round((($index + 1) / 4) * 100);
            $this->assertEquals($expectedProgress, $job->progress_percent);
        }
    }

    /** @test */
    public function job_duration_increases_over_time(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->jobService->startJob($job->id);

        $initialDuration = $job->duration;

        sleep(1);

        $job->refresh();
        $newDuration = $job->duration;

        $this->assertGreaterThan($initialDuration, $newDuration);
    }

    /** @test */
    public function multiple_jobs_for_same_user(): void
    {
        // Create first job and complete it
        $job1 = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );
        $this->jobService->startJob($job1->id);
        $this->progressService->markRunning($job1->id, 'meta_tags');
        $this->progressService->markComplete(
            $job1->id,
            'meta_tags',
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 100)
        );

        // Create second job (should work since first is completed)
        $job2 = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['headings']
        );

        $this->assertNotEquals($job1->id, $job2->id);

        // Both jobs should exist
        $this->assertEquals(2, AnalysisJob::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function job_with_invalid_indicators_only_valid_processed(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'invalid_indicator', 'headings']
        );

        // Only valid indicators should be processed
        $this->assertEquals(2, $job->total_indicators);
        $this->assertEquals(2, $job->results()->count());

        $slugs = $job->results()->pluck('indicator_slug')->toArray();
        $this->assertContains('meta_tags', $slugs);
        $this->assertContains('headings', $slugs);
        $this->assertNotContains('invalid_indicator', $slugs);
    }
}
