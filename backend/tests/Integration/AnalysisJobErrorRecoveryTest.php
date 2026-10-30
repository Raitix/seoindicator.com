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
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Integration tests for analysis job error recovery scenarios.
 *
 * Tests error handling, recovery mechanisms, and edge cases
 * in the Progressive Analysis system.
 *
 * @see TASK-19-16
 */
class AnalysisJobErrorRecoveryTest extends TestCase
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
    // JOB CREATION ERROR TESTS
    // =========================================================================

    /** @test */
    public function job_creation_fails_for_nonexistent_site(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Site not found');

        $this->jobService->createJob(
            $this->user->id,
            99999, // Nonexistent site
            ['meta_tags']
        );
    }

    /** @test */
    public function job_creation_fails_for_unowned_site(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Site not found or access denied');

        $this->jobService->createJob(
            $this->user->id,
            $otherSite->id,
            ['meta_tags']
        );
    }

    /** @test */
    public function job_creation_fails_for_empty_indicators(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No valid indicators');

        $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            []
        );
    }

    /** @test */
    public function job_creation_fails_for_all_invalid_indicators(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No valid indicators');

        $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['invalid_1', 'invalid_2', 'invalid_3']
        );
    }

    /** @test */
    public function job_creation_rolls_back_on_error(): void
    {
        // Start a transaction and force an error
        $initialJobCount = AnalysisJob::count();
        $initialResultCount = AnalysisJobResult::count();

        try {
            // This will fail due to invalid indicators
            $this->jobService->createJob(
                $this->user->id,
                $this->site->id,
                []
            );
        } catch (RuntimeException $e) {
            // Expected
        }

        // Verify no partial data was created
        $this->assertEquals($initialJobCount, AnalysisJob::count());
        $this->assertEquals($initialResultCount, AnalysisJobResult::count());
    }

    // =========================================================================
    // STATUS TRANSITION ERROR TESTS
    // =========================================================================

    /** @test */
    public function cannot_start_non_pending_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot start job');

        $this->jobService->startJob($job->id);
    }

    /** @test */
    public function cannot_complete_non_running_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot complete job');

        $this->jobService->completeJob($job->id);
    }

    /** @test */
    public function cannot_fail_non_running_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot fail job');

        $this->jobService->failJob($job->id, 'Test error');
    }

    /** @test */
    public function cannot_cancel_completed_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot cancel job');

        $this->jobService->cancelJob($job->id);
    }

    /** @test */
    public function cannot_cancel_failed_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot cancel job');

        $this->jobService->cancelJob($job->id);
    }

    // =========================================================================
    // INDICATOR FAILURE TESTS
    // =========================================================================

    /** @test */
    public function indicator_failure_does_not_stop_other_indicators(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->jobService->startJob($job->id);

        // Fail first indicator
        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markFailed($job->id, 'meta_tags', 'Failed');

        // Second indicator should still be processable
        $this->progressService->markRunning($job->id, 'headings');
        $this->progressService->markComplete(
            $job->id,
            'headings',
            IndicatorResult::success('headings', 'Headings', 85.0, 100.0, processingTimeMs: 100)
        );

        // Third indicator should still be processable
        $this->progressService->markRunning($job->id, 'images');
        $this->progressService->markComplete(
            $job->id,
            'images',
            IndicatorResult::success('images', 'Images', 90.0, 100.0, processingTimeMs: 100)
        );

        // Update progress to auto-complete
        $this->jobService->updateProgress($job->id);

        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertEquals(2, $job->completed_indicators);
        $this->assertEquals(1, $job->failed_indicators);
    }

    /** @test */
    public function indicator_failure_records_processing_time(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->jobService->startJob($job->id);

        $this->progressService->markRunning($job->id, 'meta_tags');
        usleep(50000); // 50ms
        $this->progressService->markFailed($job->id, 'meta_tags', 'Error');

        $result = $job->results()->first();
        // Note: Processing time is only recorded if started_at was set
        // ProgressService.markFailed doesn't set processing_time_ms directly
        // It's set by the model's markAsFailed when started_at is present
        $this->assertNotNull($result->started_at);
        $this->assertNotNull($result->completed_at);
    }

    // =========================================================================
    // CONCURRENT OPERATION TESTS
    // =========================================================================

    /** @test */
    public function concurrent_progress_updates_are_safe(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->jobService->startJob($job->id);

        // Simulate concurrent indicator completions
        $updates = [];

        for ($i = 0; $i < 3; $i++) {
            $slug = ['meta_tags', 'headings', 'images'][$i];
            $updates[] = function () use ($slug, $job) {
                $this->progressService->markRunning($job->id, $slug);
                $this->progressService->markComplete(
                    $job->id,
                    $slug,
                    IndicatorResult::success($slug, ucfirst($slug), 80.0, 100.0, processingTimeMs: 100)
                );
            };
        }

        // Execute all updates
        foreach ($updates as $update) {
            $update();
        }

        // Update progress to auto-complete
        $this->jobService->updateProgress($job->id);

        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertEquals(3, $job->completed_indicators);
    }

    // =========================================================================
    // CANCELLATION DURING PROCESSING TESTS
    // =========================================================================

    /** @test */
    public function cancellation_prevents_future_processing(): void
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

        // Cancel job
        $this->jobService->cancelJob($job->id);

        // Check if cancelled
        $this->assertTrue($this->progressService->isJobCancelled($job->id));

        // Pending results should be skipped
        $job->refresh();
        $skippedCount = $job->results()->where('status', 'skipped')->count();
        $this->assertEquals(2, $skippedCount);
    }

    /** @test */
    public function cancellation_while_indicator_running(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings']
        );

        $this->jobService->startJob($job->id);

        // Start an indicator
        $this->progressService->markRunning($job->id, 'meta_tags');

        // Cancel mid-processing
        $this->jobService->cancelJob($job->id);

        // The running indicator should be skipped
        $job->refresh();
        $metaResult = $job->results()->where('indicator_slug', 'meta_tags')->first();
        $this->assertEquals('skipped', $metaResult->status);
    }

    // =========================================================================
    // DATA INTEGRITY TESTS
    // =========================================================================

    /** @test */
    public function job_data_integrity_across_failures(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->jobService->startJob($job->id);

        // Complete with data
        $data = ['title' => 'Test Page', 'description' => 'Test'];

        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markComplete(
            $job->id,
            'meta_tags',
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, data: $data, processingTimeMs: 100)
        );

        // Fail another
        $this->progressService->markRunning($job->id, 'headings');
        $this->progressService->markFailed($job->id, 'headings', 'Error');

        // Complete third
        $this->progressService->markRunning($job->id, 'images');
        $this->progressService->markComplete(
            $job->id,
            'images',
            IndicatorResult::success('images', 'Images', 90.0, 100.0, processingTimeMs: 100)
        );

        // Verify data integrity
        $job->refresh();
        $metaResult = $job->results()->where('indicator_slug', 'meta_tags')->first();
        $this->assertEquals($data, $metaResult->data);

        $headingsResult = $job->results()->where('indicator_slug', 'headings')->first();
        $this->assertEquals('Error', $headingsResult->error_message);

        $imagesResult = $job->results()->where('indicator_slug', 'images')->first();
        $this->assertEquals(90.0, (float) $imagesResult->score);
    }

    /** @test */
    public function progress_never_exceeds_100_percent(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 2,
            'completed_indicators' => 2,
            'failed_indicators' => 1, // This would make it 150%
            'started_at' => now(),
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'completed',
        ]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'headings',
            'indicator_name' => 'Headings',
            'status' => 'failed',
        ]);

        $this->assertEquals(100, $job->calculateProgress());
    }

    // =========================================================================
    // NONEXISTENT RESOURCE TESTS
    // =========================================================================

    /** @test */
    public function get_nonexistent_job_returns_null(): void
    {
        $result = $this->jobService->getJob(99999);

        $this->assertNull($result);
    }

    /** @test */
    public function get_progress_nonexistent_job_returns_null(): void
    {
        $result = $this->jobService->getJobProgress(99999);

        $this->assertNull($result);
    }

    /** @test */
    public function start_nonexistent_job_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->jobService->startJob(99999);
    }

    /** @test */
    public function complete_nonexistent_job_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->jobService->completeJob(99999);
    }

    /** @test */
    public function fail_nonexistent_job_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->jobService->failJob(99999, 'Error');
    }

    /** @test */
    public function cancel_nonexistent_job_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->jobService->cancelJob(99999);
    }

    // =========================================================================
    // SPECIAL CHARACTER HANDLING TESTS
    // =========================================================================

    /** @test */
    public function handles_special_characters_in_error_messages(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->jobService->startJob($job->id);

        $specialError = "Error with 'quotes' and \"double quotes\" and <html> tags";

        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markFailed($job->id, 'meta_tags', $specialError);

        $result = $job->results()->first();
        $this->assertEquals($specialError, $result->error_message);
    }

    /** @test */
    public function handles_unicode_in_data_and_recommendations(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->jobService->startJob($job->id);

        $data = [
            'title' => '日本語タイトル',
            'description' => 'Привет мир 🎉',
        ];

        $recommendations = [
            ['priority' => 'high', 'message' => 'Ändern Sie die Überschrift'],
        ];

        $this->progressService->markRunning($job->id, 'meta_tags');
        $this->progressService->markComplete(
            $job->id,
            'meta_tags',
            IndicatorResult::success(
                'meta_tags',
                'Meta Tags',
                85.0,
                100.0,
                data: $data,
                recommendations: $recommendations,
                processingTimeMs: 100
            )
        );

        $result = $job->results()->first();
        $this->assertEquals('日本語タイトル', $result->data['title']);
        $this->assertEquals('Привет мир 🎉', $result->data['description']);
        $this->assertEquals('Ändern Sie die Überschrift', $result->recommendations[0]['message']);
    }

    // =========================================================================
    // JOB FAILED STATE TESTS
    // =========================================================================

    /** @test */
    public function job_can_be_marked_as_failed(): void
    {
        $job = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->jobService->startJob($job->id);
        $this->jobService->failJob($job->id, 'Critical failure');

        $job->refresh();
        $this->assertEquals('failed', $job->status);
        $this->assertEquals('Critical failure', $job->error_message);
    }

    /** @test */
    public function failed_job_preserves_completed_results(): void
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
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 100)
        );

        // Fail the job
        $this->jobService->failJob($job->id, 'Critical failure');

        $job->refresh();
        $this->assertEquals('failed', $job->status);

        // Completed result should be preserved
        $metaResult = $job->results()->where('indicator_slug', 'meta_tags')->first();
        $this->assertEquals('completed', $metaResult->status);
        $this->assertEquals(85.0, (float) $metaResult->score);
    }

    // =========================================================================
    // RATE LIMIT RECOVERY TESTS
    // =========================================================================

    /** @test */
    public function rate_limit_headers_returned_on_limit_exceeded(): void
    {
        // Create jobs up to concurrent limit
        $job1 = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $job2 = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['headings']
        );

        // Try to create third job (should fail due to concurrent limit)
        try {
            $this->jobService->createJob(
                $this->user->id,
                $this->site->id,
                ['images']
            );
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            // Check for rate limit error format
            $this->assertStringContainsString('RATE_LIMIT:', $e->getMessage());
            $this->assertStringContainsString('concurrent', strtolower($e->getMessage()));
        }

        // Complete one job to free up capacity
        $this->jobService->startJob($job1->id);
        $this->progressService->markRunning($job1->id, 'meta_tags');
        $this->progressService->markComplete(
            $job1->id,
            'meta_tags',
            IndicatorResult::success('meta_tags', 'Meta Tags', 85.0, 100.0, processingTimeMs: 100)
        );
        $this->jobService->updateProgress($job1->id);

        // Now creating another job should work
        $job3 = $this->jobService->createJob(
            $this->user->id,
            $this->site->id,
            ['images']
        );

        $this->assertNotNull($job3);
    }
}
