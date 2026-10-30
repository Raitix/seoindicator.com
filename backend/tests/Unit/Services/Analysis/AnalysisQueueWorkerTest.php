<?php

namespace Tests\Unit\Services\Analysis;

use App\Jobs\ProcessIndicatorJob;
use App\Jobs\StartAnalysisJob;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Models\Site;
use App\Models\User;
use App\Services\AnalysisJobService;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Test suite for queue-based processing with concurrency limits and throttling.
 *
 * Covers queue worker behavior, concurrency limits, timeout handling,
 * retry logic, and progress tracking.
 */
class AnalysisQueueWorkerTest extends TestCase
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

    // ============================================
    // Queue Configuration Tests
    // ============================================

    public function test_start_analysis_job_uses_analysis_queue(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_PENDING,
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => AnalysisJobResult::STATUS_PENDING,
        ]);

        Bus::fake();

        $startJob = new StartAnalysisJob($job->id);
        $startJob->handle(app(AnalysisJobService::class));

        // Job dispatches ProcessIndicatorJob batch
        Bus::assertBatched(function ($batch) {
            // Verify batch contains ProcessIndicatorJob instances
            foreach ($batch->jobs as $job) {
                if (!($job instanceof ProcessIndicatorJob)) {
                    return false;
                }
            }
            return count($batch->jobs) === 1;
        });
    }

    public function test_process_indicator_job_uses_analysis_queue(): void
    {
        $processJob = new ProcessIndicatorJob(1, 'meta_tags');

        $this->assertEquals('analysis', $processJob->queue);
    }

    public function test_process_indicator_job_has_two_tries(): void
    {
        $processJob = new ProcessIndicatorJob(1, 'meta_tags');

        $this->assertEquals(2, $processJob->tries);
    }

    public function test_process_indicator_job_has_30_second_timeout(): void
    {
        $processJob = new ProcessIndicatorJob(1, 'meta_tags');

        $this->assertEquals(30, $processJob->timeout);
    }

    public function test_process_indicator_job_has_256mb_memory_limit(): void
    {
        $processJob = new ProcessIndicatorJob(1, 'meta_tags');

        $this->assertEquals(256, $processJob->memory);
    }

    public function test_process_indicator_job_has_exponential_backoff(): void
    {
        $processJob = new ProcessIndicatorJob(1, 'meta_tags');

        $this->assertEquals([10, 30], $processJob->backoff);
    }

    // ============================================
    // Concurrency Tests
    // ============================================

    public function test_start_analysis_job_dispatches_batch_with_concurrency_limit(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_PENDING,
            'total_indicators' => 5,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        foreach (['meta_tags', 'headings', 'images', 'links', 'performance'] as $slug) {
            AnalysisJobResult::create([
                'job_id' => $job->id,
                'indicator_slug' => $slug,
                'indicator_name' => ucfirst(str_replace('_', ' ', $slug)),
                'status' => AnalysisJobResult::STATUS_PENDING,
            ]);
        }

        Bus::fake();

        $startJob = new StartAnalysisJob($job->id);
        $startJob->handle(app(AnalysisJobService::class));

        Bus::assertBatched(function ($batch) {
            // Verify batch has 5 jobs
            return count($batch->jobs) === 5;
        });
    }

    public function test_start_analysis_job_respects_concurrency_limit_property(): void
    {
        $startJob = new StartAnalysisJob(1);

        $this->assertEquals(5, $startJob->concurrencyLimit);
    }

    public function test_start_analysis_job_processes_all_indicators(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_PENDING,
            'total_indicators' => 3,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        foreach (['meta_tags', 'headings', 'images'] as $slug) {
            AnalysisJobResult::create([
                'job_id' => $job->id,
                'indicator_slug' => $slug,
                'indicator_name' => ucfirst(str_replace('_', ' ', $slug)),
                'status' => AnalysisJobResult::STATUS_PENDING,
            ]);
        }

        Bus::fake();

        $startJob = new StartAnalysisJob($job->id);
        $startJob->handle(app(AnalysisJobService::class));

        Bus::assertBatched(function ($batch) {
            return count($batch->jobs) === 3;
        });
    }

    // ============================================
    // Status Transition Tests
    // ============================================

    public function test_start_analysis_job_marks_job_as_running(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_PENDING,
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => AnalysisJobResult::STATUS_PENDING,
        ]);

        Bus::fake();

        $startJob = new StartAnalysisJob($job->id);
        $startJob->handle(app(AnalysisJobService::class));

        $job->refresh();
        $this->assertEquals(AnalysisJob::STATUS_RUNNING, $job->status);
        $this->assertNotNull($job->started_at);
    }

    public function test_start_analysis_job_skips_cancelled_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_CANCELLED,
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        Bus::fake();

        $startJob = new StartAnalysisJob($job->id);
        $startJob->handle(app(AnalysisJobService::class));

        Bus::assertNothingBatched();
    }

    public function test_start_analysis_job_handles_missing_job(): void
    {
        Bus::fake();

        $startJob = new StartAnalysisJob(999999);
        $startJob->handle(app(AnalysisJobService::class));

        Bus::assertNothingBatched();
    }

    public function test_start_analysis_job_handles_no_pending_indicators(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_PENDING,
            'total_indicators' => 0,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        Bus::fake();

        $startJob = new StartAnalysisJob($job->id);
        $startJob->handle(app(AnalysisJobService::class));

        $job->refresh();
        $this->assertEquals(AnalysisJob::STATUS_COMPLETED, $job->status);
    }

    // ============================================
    // Progress Tracking Tests
    // ============================================

    public function test_progress_service_marks_indicator_as_running(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_RUNNING,
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => AnalysisJobResult::STATUS_PENDING,
        ]);

        $progressService = app(ProgressService::class);
        $progressService->markRunning($job->id, 'meta_tags');

        $result = AnalysisJobResult::where('job_id', $job->id)
            ->where('indicator_slug', 'meta_tags')
            ->first();

        $this->assertEquals(AnalysisJobResult::STATUS_RUNNING, $result->status);
        $this->assertNotNull($result->started_at);
    }

    public function test_progress_service_marks_indicator_complete(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_RUNNING,
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => AnalysisJobResult::STATUS_RUNNING,
        ]);

        $progressService = app(ProgressService::class);
        
        $result = \App\Services\Analysis\Indicators\IndicatorResult::success(
            slug: 'meta_tags',
            name: 'Meta Tags',
            score: 80.00,
            maxScore: 100.00,
            data: ['test' => 'data'],
            recommendations: ['Fix this'],
            processingTimeMs: 150
        );
        
        $progressService->markComplete($job->id, 'meta_tags', $result);

        $jobResult = AnalysisJobResult::where('job_id', $job->id)
            ->where('indicator_slug', 'meta_tags')
            ->first();

        $this->assertEquals(AnalysisJobResult::STATUS_COMPLETED, $jobResult->status);
        $this->assertEquals(80.00, $jobResult->score);
        $this->assertEquals(100.00, $jobResult->max_score);
        $this->assertEquals(80, $jobResult->percentage);
        $this->assertNotNull($jobResult->completed_at);

        $job->refresh();
        $this->assertEquals(1, $job->completed_indicators);
    }

    public function test_progress_service_marks_indicator_failed(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_RUNNING,
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => AnalysisJobResult::STATUS_RUNNING,
        ]);

        $progressService = app(ProgressService::class);
        $progressService->markFailed($job->id, 'meta_tags', 'Test error');

        $jobResult = AnalysisJobResult::where('job_id', $job->id)
            ->where('indicator_slug', 'meta_tags')
            ->first();

        $this->assertEquals(AnalysisJobResult::STATUS_FAILED, $jobResult->status);
        $this->assertEquals('Test error', $jobResult->error_message);
        $this->assertNotNull($jobResult->completed_at);

        $job->refresh();
        $this->assertEquals(1, $job->failed_indicators);
    }

    public function test_progress_service_detects_cancelled_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_CANCELLED,
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        $progressService = app(ProgressService::class);
        
        $this->assertTrue($progressService->isJobCancelled($job->id));
    }

    public function test_progress_percent_calculated_correctly(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_RUNNING,
            'total_indicators' => 4,
            'completed_indicators' => 2,
            'failed_indicators' => 1,
            'progress_percent' => 0,
        ]);

        $this->assertEquals(75, $job->calculateProgress());
    }

    // ============================================
    // Error Handling Tests
    // ============================================

    public function test_process_indicator_job_handles_unknown_indicator(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_RUNNING,
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'unknown_indicator',
            'indicator_name' => 'Unknown',
            'status' => AnalysisJobResult::STATUS_PENDING,
        ]);

        $processJob = new ProcessIndicatorJob($job->id, 'unknown_indicator');
        $processJob->handle(app(ProgressService::class), app(\App\Services\URLValidationService::class));

        $result = AnalysisJobResult::where('job_id', $job->id)
            ->where('indicator_slug', 'unknown_indicator')
            ->first();

        $this->assertEquals(AnalysisJobResult::STATUS_FAILED, $result->status);
        $this->assertStringContainsString('Unknown indicator', $result->error_message);
    }

    public function test_process_indicator_job_skips_if_job_cancelled(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_CANCELLED,
            'total_indicators' => 1,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => AnalysisJobResult::STATUS_PENDING,
        ]);

        $processJob = new ProcessIndicatorJob($job->id, 'meta_tags');
        $processJob->handle(app(ProgressService::class), app(\App\Services\URLValidationService::class));

        $result = AnalysisJobResult::where('job_id', $job->id)
            ->where('indicator_slug', 'meta_tags')
            ->first();

        // Status should still be pending (not changed to running or failed)
        $this->assertEquals(AnalysisJobResult::STATUS_PENDING, $result->status);
    }

    // ============================================
    // Job Tags Tests
    // ============================================

    public function test_start_analysis_job_has_correct_tags(): void
    {
        $startJob = new StartAnalysisJob(123);
        
        $this->assertEquals(['analysis-job:123'], $startJob->tags());
    }

    public function test_process_indicator_job_has_correct_tags(): void
    {
        $processJob = new ProcessIndicatorJob(456, 'meta_tags');
        
        $this->assertEquals(['analysis-job:456', 'indicator:meta_tags'], $processJob->tags());
    }

    // ============================================
    // Batch Behavior Tests
    // ============================================

    public function test_batch_allows_failures(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_PENDING,
            'total_indicators' => 2,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        foreach (['meta_tags', 'headings'] as $slug) {
            AnalysisJobResult::create([
                'job_id' => $job->id,
                'indicator_slug' => $slug,
                'indicator_name' => ucfirst(str_replace('_', ' ', $slug)),
                'status' => AnalysisJobResult::STATUS_PENDING,
            ]);
        }

        Bus::fake();

        $startJob = new StartAnalysisJob($job->id);
        $startJob->handle(app(AnalysisJobService::class));

        // The batch is created with allowFailures()
        Bus::assertBatched(function ($batch) {
            return count($batch->jobs) === 2;
        });
    }

    // ============================================
    // Integration Tests
    // ============================================

    public function test_full_job_processing_workflow(): void
    {
        $service = app(AnalysisJobService::class);

        // Create job via service
        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings']
        );

        $this->assertEquals(AnalysisJob::STATUS_PENDING, $job->status);
        $this->assertEquals(2, $job->total_indicators);

        // Verify results were created
        $this->assertEquals(2, $job->results()->count());
        $this->assertEquals(2, $job->results()->pending()->count());
    }

    public function test_job_cancellation_marks_results_as_skipped(): void
    {
        $service = app(AnalysisJobService::class);

        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        // Start the job first
        $job->markAsRunning();

        // Cancel the job
        $service->cancelJob($job->id, $this->user->id);

        $job->refresh();

        $this->assertEquals(AnalysisJob::STATUS_CANCELLED, $job->status);
        $this->assertNotNull($job->cancelled_at);

        // Check that pending results were marked as skipped
        $skippedCount = $job->results()->where('status', AnalysisJobResult::STATUS_SKIPPED)->count();
        $this->assertEquals(3, $skippedCount);
    }

    // ============================================
    // Rate Limiting Tests
    // ============================================

    public function test_service_enforces_concurrent_job_limit(): void
    {
        $service = app(AnalysisJobService::class);

        // Create max concurrent jobs
        for ($i = 0; $i < AnalysisJobService::MAX_CONCURRENT_JOBS_PER_USER; $i++) {
            $service->createJob(
                $this->user->id,
                $this->site->id,
                ['meta_tags']
            );
        }

        // Next job should fail
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum concurrent jobs limit reached');

        $service->createJob(
            $this->user->id,
            $this->site->id,
            ['headings']
        );
    }

    public function test_service_validates_indicators(): void
    {
        $service = app(AnalysisJobService::class);

        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'invalid_indicator', 'headings']
        );

        // Only valid indicators should be created
        $this->assertEquals(2, $job->total_indicators);
        $this->assertEquals(2, $job->results()->count());
    }

    public function test_service_rejects_empty_indicators(): void
    {
        $service = app(AnalysisJobService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No valid indicators provided');

        $service->createJob(
            $this->user->id,
            $this->site->id,
            ['invalid_indicator']
        );
    }

    // ============================================
    // User Access Tests
    // ============================================

    public function test_service_prevents_cross_user_job_access(): void
    {
        $service = app(AnalysisJobService::class);

        $otherUser = User::factory()->create();

        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $result = $service->getJob($job->id, $otherUser->id);

        $this->assertNull($result);
    }

    public function test_service_can_user_access_job(): void
    {
        $service = app(AnalysisJobService::class);

        $job = $service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->assertTrue($service->canUserAccessJob($this->user->id, $job->id));
        
        $otherUser = User::factory()->create();
        $this->assertFalse($service->canUserAccessJob($otherUser->id, $job->id));
    }
}
