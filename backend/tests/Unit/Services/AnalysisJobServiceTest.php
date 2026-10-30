<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\AnalysisJobService;
use App\Services\AnalysisRateLimiterService;
use App\Services\Analysis\Indicators\IndicatorResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;

/**
 * Unit tests for AnalysisJobService.
 *
 * Tests job creation, status transitions, cancellation,
 * and progress tracking for the Progressive Analysis system.
 *
 * @see TASK-19-16
 * @see \App\Services\AnalysisJobService
 */
class AnalysisJobServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;
    protected AnalysisJobService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->service = app(AnalysisJobService::class);

        Event::fake();
    }

    // =========================================================================
    // CREATE JOB TESTS
    // =========================================================================

    /** @test */
    public function create_job_creates_job_with_valid_indicators(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->assertNotNull($job);
        $this->assertEquals('pending', $job->status);
        $this->assertEquals(3, $job->total_indicators);
        $this->assertEquals(0, $job->completed_indicators);
        $this->assertEquals(0, $job->failed_indicators);
        $this->assertEquals(0, $job->progress_percent);
    }

    /** @test */
    public function create_job_creates_pending_results_for_each_indicator(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings']
        );

        $results = $job->results;

        $this->assertEquals(2, $results->count());
        $this->assertTrue($results->contains('indicator_slug', 'meta_tags'));
        $this->assertTrue($results->contains('indicator_slug', 'headings'));

        foreach ($results as $result) {
            $this->assertEquals('pending', $result->status);
        }
    }

    /** @test */
    public function create_job_filters_invalid_indicators(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'invalid_indicator', 'headings']
        );

        $this->assertEquals(2, $job->total_indicators);
        $this->assertEquals(2, $job->results()->count());

        $slugs = $job->results()->pluck('indicator_slug')->toArray();
        $this->assertContains('meta_tags', $slugs);
        $this->assertContains('headings', $slugs);
        $this->assertNotContains('invalid_indicator', $slugs);
    }

    /** @test */
    public function create_job_throws_for_nonexistent_site(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Site not found');

        $this->service->createJob(
            $this->user->id,
            99999,
            ['meta_tags']
        );
    }

    /** @test */
    public function create_job_throws_for_unowned_site(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Site not found');

        $this->service->createJob(
            $this->user->id,
            $otherSite->id,
            ['meta_tags']
        );
    }

    /** @test */
    public function create_job_throws_for_empty_indicators(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No valid indicators');

        $this->service->createJob(
            $this->user->id,
            $this->site->id,
            []
        );
    }

    /** @test */
    public function create_job_throws_for_all_invalid_indicators(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No valid indicators');

        $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['invalid_1', 'invalid_2']
        );
    }

    /** @test */
    public function create_job_deduplicates_indicators(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'meta_tags', 'headings']
        );

        $this->assertEquals(2, $job->total_indicators);
        $this->assertEquals(2, $job->results()->count());
    }

    /** @test */
    public function create_job_sets_correct_indicator_names(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings']
        );

        $metaResult = $job->results()->where('indicator_slug', 'meta_tags')->first();
        $this->assertEquals('Meta Tags', $metaResult->indicator_name);

        $headingsResult = $job->results()->where('indicator_slug', 'headings')->first();
        $this->assertEquals('Headings Structure', $headingsResult->indicator_name);
    }

    // =========================================================================
    // GET JOB TESTS
    // =========================================================================

    /** @test */
    public function get_job_returns_job_with_results(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $retrieved = $this->service->getJob($job->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals($job->id, $retrieved->id);
        $this->assertTrue($retrieved->relationLoaded('results'));
    }

    /** @test */
    public function get_job_returns_null_for_nonexistent(): void
    {
        $retrieved = $this->service->getJob(99999);

        $this->assertNull($retrieved);
    }

    /** @test */
    public function get_job_with_user_id_filters_by_ownership(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $retrieved = $this->service->getJob($job->id, $this->user->id);
        $this->assertNotNull($retrieved);

        $otherUser = User::factory()->create();
        $retrieved = $this->service->getJob($job->id, $otherUser->id);
        $this->assertNull($retrieved);
    }

    // =========================================================================
    // GET JOB PROGRESS TESTS
    // =========================================================================

    /** @test */
    public function get_job_progress_returns_complete_data(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings']
        );

        $this->service->startJob($job->id);

        $progress = $this->service->getJobProgress($job->id);

        $this->assertNotNull($progress);
        $this->assertEquals($job->id, $progress['id']);
        $this->assertEquals('running', $progress['status']);
        $this->assertEquals(2, $progress['total_indicators']);
        $this->assertEquals(0, $progress['completed_indicators']);
        $this->assertNotNull($progress['started_at']);
    }

    /** @test */
    public function get_job_progress_returns_null_for_nonexistent(): void
    {
        $progress = $this->service->getJobProgress(99999);

        $this->assertNull($progress);
    }

    /** @test */
    public function get_job_progress_includes_results(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings']
        );

        $progress = $this->service->getJobProgress($job->id);

        $this->assertArrayHasKey('results', $progress);
        $this->assertCount(2, $progress['results']);
    }

    /** @test */
    public function get_job_progress_with_user_id_filters_by_ownership(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $progress = $this->service->getJobProgress($job->id, $this->user->id);
        $this->assertNotNull($progress);

        $otherUser = User::factory()->create();
        $progress = $this->service->getJobProgress($job->id, $otherUser->id);
        $this->assertNull($progress);
    }

    // =========================================================================
    // START JOB TESTS
    // =========================================================================

    /** @test */
    public function start_job_changes_status_to_running(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $result = $this->service->startJob($job->id);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('running', $job->status);
        $this->assertNotNull($job->started_at);
    }

    /** @test */
    public function start_job_throws_for_nonexistent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->service->startJob(99999);
    }

    /** @test */
    public function start_job_throws_for_non_pending_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot start job');

        $this->service->startJob($job->id);
    }

    // =========================================================================
    // COMPLETE JOB TESTS
    // =========================================================================

    /** @test */
    public function complete_job_changes_status_to_completed(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->service->startJob($job->id);
        $result = $this->service->completeJob($job->id);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertNotNull($job->completed_at);
        $this->assertEquals(100, $job->progress_percent);
    }

    /** @test */
    public function complete_job_throws_for_nonexistent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->service->completeJob(99999);
    }

    /** @test */
    public function complete_job_throws_for_non_running_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot complete job');

        $this->service->completeJob($job->id);
    }

    // =========================================================================
    // FAIL JOB TESTS
    // =========================================================================

    /** @test */
    public function fail_job_changes_status_to_failed(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->service->startJob($job->id);
        $result = $this->service->failJob($job->id, 'Critical error');

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('failed', $job->status);
        $this->assertEquals('Critical error', $job->error_message);
    }

    /** @test */
    public function fail_job_throws_for_nonexistent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->service->failJob(99999, 'Error');
    }

    /** @test */
    public function fail_job_throws_for_non_running_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot fail job');

        $this->service->failJob($job->id, 'Error');
    }

    // =========================================================================
    // CANCEL JOB TESTS
    // =========================================================================

    /** @test */
    public function cancel_job_changes_status_to_cancelled(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $result = $this->service->cancelJob($job->id);

        $this->assertTrue($result);
        $job->refresh();
        $this->assertEquals('cancelled', $job->status);
        $this->assertNotNull($job->cancelled_at);
    }

    /** @test */
    public function cancel_job_skips_pending_results(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->service->cancelJob($job->id);

        $skippedCount = $job->results()->where('status', 'skipped')->count();
        $this->assertEquals(3, $skippedCount);
    }

    /** @test */
    public function cancel_job_throws_for_nonexistent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->service->cancelJob(99999);
    }

    /** @test */
    public function cancel_job_throws_for_completed_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot cancel job');

        $this->service->cancelJob($job->id);
    }

    /** @test */
    public function cancel_job_throws_for_failed_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
            'total_indicators' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot cancel job');

        $this->service->cancelJob($job->id);
    }

    /** @test */
    public function cancel_job_with_user_id_verifies_ownership(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $otherUser = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job not found');

        $this->service->cancelJob($job->id, $otherUser->id);
    }

    // =========================================================================
    // UPDATE PROGRESS TESTS
    // =========================================================================

    /** @test */
    public function update_progress_updates_job_counters(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings', 'images']
        );

        $this->service->startJob($job->id);

        // Mark one as completed
        $result = $job->results()->first();
        $result->update(['status' => 'completed', 'score' => 85.0]);

        $this->service->updateProgress($job->id);

        $job->refresh();
        $this->assertEquals(1, $job->completed_indicators);
        $this->assertEquals(33, $job->progress_percent);
    }

    /** @test */
    public function update_progress_auto_completes_when_all_done(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags', 'headings']
        );

        $this->service->startJob($job->id);

        // Mark all as completed
        $job->results()->update(['status' => 'completed']);

        $this->service->updateProgress($job->id);

        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertEquals(100, $job->progress_percent);
    }

    /** @test */
    public function update_progress_handles_nonexistent_job(): void
    {
        // Should not throw
        $this->service->updateProgress(99999);
        $this->assertTrue(true);
    }

    // =========================================================================
    // CAN USER ACCESS JOB TESTS
    // =========================================================================

    /** @test */
    public function can_user_access_job_returns_true_for_owner(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->assertTrue($this->service->canUserAccessJob($this->user->id, $job->id));
    }

    /** @test */
    public function can_user_access_job_returns_false_for_non_owner(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $otherUser = User::factory()->create();

        $this->assertFalse($this->service->canUserAccessJob($otherUser->id, $job->id));
    }

    /** @test */
    public function can_user_access_job_returns_false_for_nonexistent(): void
    {
        $this->assertFalse($this->service->canUserAccessJob($this->user->id, 99999));
    }

    // =========================================================================
    // GET AVAILABLE INDICATORS TESTS
    // =========================================================================

    /** @test */
    public function get_available_indicators_returns_all_indicators(): void
    {
        $indicators = $this->service->getAvailableIndicators();

        $this->assertIsArray($indicators);
        $this->assertArrayHasKey('meta_tags', $indicators);
        $this->assertArrayHasKey('headings', $indicators);
        $this->assertArrayHasKey('images', $indicators);
        $this->assertArrayHasKey('links', $indicators);
    }

    /** @test */
    public function get_available_indicators_has_correct_structure(): void
    {
        $indicators = $this->service->getAvailableIndicators();

        foreach ($indicators as $slug => $data) {
            $this->assertArrayHasKey('name', $data);
            $this->assertArrayHasKey('max_score', $data);
            $this->assertIsString($data['name']);
            $this->assertIsNumeric($data['max_score']);
        }
    }

    // =========================================================================
    // GET PENDING/RUNNING JOBS TESTS
    // =========================================================================

    /** @test */
    public function get_pending_jobs_returns_only_pending(): void
    {
        $job1 = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $job2 = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['headings']
        );
        $this->service->startJob($job2->id);

        $pendingJobs = $this->service->getPendingJobs();

        $this->assertEquals(1, $pendingJobs->count());
        $this->assertEquals($job1->id, $pendingJobs->first()->id);
    }

    /** @test */
    public function get_running_jobs_returns_only_running(): void
    {
        $job1 = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $job2 = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['headings']
        );
        $this->service->startJob($job2->id);

        $runningJobs = $this->service->getRunningJobs();

        $this->assertEquals(1, $runningJobs->count());
        $this->assertEquals($job2->id, $runningJobs->first()->id);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function job_with_all_17_indicators(): void
    {
        $indicators = array_keys(AnalysisJobService::INDICATORS);

        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            $indicators
        );

        $this->assertEquals(count($indicators), $job->total_indicators);
        $this->assertEquals(count($indicators), $job->results()->count());
    }

    /** @test */
    public function job_duration_is_calculated(): void
    {
        $job = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $this->service->startJob($job->id);

        $job->refresh();
        $this->assertNotNull($job->duration);
    }

    /** @test */
    public function multiple_jobs_for_different_users(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $job1 = $this->service->createJob(
            $this->user->id,
            $this->site->id,
            ['meta_tags']
        );

        $job2 = $this->service->createJob(
            $otherUser->id,
            $otherSite->id,
            ['headings']
        );

        $this->assertNotEquals($job1->id, $job2->id);
        $this->assertEquals($this->user->id, $job1->user_id);
        $this->assertEquals($otherUser->id, $job2->user_id);
    }
}
