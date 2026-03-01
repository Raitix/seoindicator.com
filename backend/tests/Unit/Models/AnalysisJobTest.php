<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for AnalysisJob model.
 *
 * @see TASK-19-16
 * @see \App\Models\AnalysisJob
 */
class AnalysisJobTest extends TestCase
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

    // =========================================================================
    // STATUS CONSTANTS TESTS
    // =========================================================================

    /** @test */
    public function status_constants_are_defined(): void
    {
        $this->assertEquals('pending', AnalysisJob::STATUS_PENDING);
        $this->assertEquals('running', AnalysisJob::STATUS_RUNNING);
        $this->assertEquals('completed', AnalysisJob::STATUS_COMPLETED);
        $this->assertEquals('cancelled', AnalysisJob::STATUS_CANCELLED);
        $this->assertEquals('failed', AnalysisJob::STATUS_FAILED);
    }

    // =========================================================================
    // MODEL CREATION TESTS
    // =========================================================================

    /** @test */
    public function can_create_analysis_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => AnalysisJob::STATUS_PENDING,
            'total_indicators' => 5,
        ]);

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $job->id,
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 5,
        ]);
    }

    /** @test */
    public function default_status_is_pending(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'total_indicators' => 1,
        ]);

        $this->assertEquals('pending', $job->status);
    }

    /** @test */
    public function default_indicator_counts_are_zero(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'total_indicators' => 5,
        ]);

        $this->assertEquals(0, $job->completed_indicators);
        $this->assertEquals(0, $job->failed_indicators);
    }

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    /** @test */
    public function belongs_to_user(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'total_indicators' => 1,
        ]);

        $this->assertTrue($job->user->is($this->user));
    }

    /** @test */
    public function belongs_to_site(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'total_indicators' => 1,
        ]);

        $this->assertTrue($job->site->is($this->site));
    }

    /** @test */
    public function has_many_results(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'total_indicators' => 2,
        ]);

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

        $this->assertCount(2, $job->results);
    }

    // =========================================================================
    // SCOPE TESTS
    // =========================================================================

    /** @test */
    public function scope_pending_filters_correctly(): void
    {
        $pendingJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $runningJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 1,
        ]);

        $results = AnalysisJob::pending()->get();

        $this->assertTrue($results->contains($pendingJob));
        $this->assertFalse($results->contains($runningJob));
    }

    /** @test */
    public function scope_running_filters_correctly(): void
    {
        $pendingJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $runningJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 1,
        ]);

        $results = AnalysisJob::running()->get();

        $this->assertTrue($results->contains($runningJob));
        $this->assertFalse($results->contains($pendingJob));
    }

    /** @test */
    public function scope_completed_filters_correctly(): void
    {
        $completedJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $pendingJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $results = AnalysisJob::completed()->get();

        $this->assertTrue($results->contains($completedJob));
        $this->assertFalse($results->contains($pendingJob));
    }

    /** @test */
    public function scope_cancelled_filters_correctly(): void
    {
        $cancelledJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'cancelled',
            'total_indicators' => 1,
        ]);

        $pendingJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $results = AnalysisJob::cancelled()->get();

        $this->assertTrue($results->contains($cancelledJob));
        $this->assertFalse($results->contains($pendingJob));
    }

    /** @test */
    public function scope_failed_filters_correctly(): void
    {
        $failedJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
            'total_indicators' => 1,
        ]);

        $pendingJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $results = AnalysisJob::failed()->get();

        $this->assertTrue($results->contains($failedJob));
        $this->assertFalse($results->contains($pendingJob));
    }

    /** @test */
    public function scope_by_user_filters_correctly(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);

        $userJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $otherJob = AnalysisJob::create([
            'user_id' => $otherUser->id,
            'site_id' => $otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $results = AnalysisJob::byUser($this->user->id)->get();

        $this->assertTrue($results->contains($userJob));
        $this->assertFalse($results->contains($otherJob));
    }

    /** @test */
    public function scope_by_site_filters_correctly(): void
    {
        $otherSite = Site::factory()->create(['user_id' => $this->user->id]);

        $siteJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $otherJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $otherSite->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $results = AnalysisJob::bySite($this->site->id)->get();

        $this->assertTrue($results->contains($siteJob));
        $this->assertFalse($results->contains($otherJob));
    }

    /** @test */
    public function scope_recent_filters_by_days(): void
    {
        $recentJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        // Create old job and manually set created_at in the past
        $oldJob = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);
        
        // Use raw update to bypass model timestamp handling
        \DB::table('analysis_jobs')
            ->where('id', $oldJob->id)
            ->update(['created_at' => now()->subDays(10)]);

        $results = AnalysisJob::recent(7)->get();

        $this->assertTrue($results->contains($recentJob));
        $this->assertFalse($results->contains($oldJob));
    }

    // =========================================================================
    // STATUS HELPER TESTS
    // =========================================================================

    /** @test */
    public function is_pending_returns_true_for_pending_status(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->assertTrue($job->isPending());
        $this->assertFalse($job->isRunning());
        $this->assertFalse($job->isCompleted());
        $this->assertFalse($job->isCancelled());
        $this->assertFalse($job->isFailed());
    }

    /** @test */
    public function is_running_returns_true_for_running_status(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 1,
        ]);

        $this->assertFalse($job->isPending());
        $this->assertTrue($job->isRunning());
        $this->assertFalse($job->isCompleted());
        $this->assertFalse($job->isCancelled());
        $this->assertFalse($job->isFailed());
    }

    /** @test */
    public function is_completed_returns_true_for_completed_status(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $this->assertFalse($job->isPending());
        $this->assertFalse($job->isRunning());
        $this->assertTrue($job->isCompleted());
        $this->assertFalse($job->isCancelled());
        $this->assertFalse($job->isFailed());
    }

    /** @test */
    public function is_cancelled_returns_true_for_cancelled_status(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'cancelled',
            'total_indicators' => 1,
        ]);

        $this->assertFalse($job->isPending());
        $this->assertFalse($job->isRunning());
        $this->assertFalse($job->isCompleted());
        $this->assertTrue($job->isCancelled());
        $this->assertFalse($job->isFailed());
    }

    /** @test */
    public function is_failed_returns_true_for_failed_status(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
            'total_indicators' => 1,
        ]);

        $this->assertFalse($job->isPending());
        $this->assertFalse($job->isRunning());
        $this->assertFalse($job->isCompleted());
        $this->assertFalse($job->isCancelled());
        $this->assertTrue($job->isFailed());
    }

    // =========================================================================
    // STATUS TRANSITION TESTS
    // =========================================================================

    /** @test */
    public function mark_as_running_updates_status_and_sets_started_at(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $result = $job->markAsRunning();

        $this->assertTrue($result);
        $this->assertEquals('running', $job->status);
        $this->assertNotNull($job->started_at);
    }

    /** @test */
    public function mark_as_completed_updates_status_and_sets_completed_at(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'started_at' => now(),
            'total_indicators' => 1,
        ]);

        $result = $job->markAsCompleted();

        $this->assertTrue($result);
        $this->assertEquals('completed', $job->status);
        $this->assertNotNull($job->completed_at);
        $this->assertEquals(100, $job->progress_percent);
    }

    /** @test */
    public function mark_as_cancelled_updates_status_and_sets_cancelled_at(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $result = $job->markAsCancelled();

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $job->status);
        $this->assertNotNull($job->cancelled_at);
    }

    /** @test */
    public function mark_as_failed_updates_status_and_error_message(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'started_at' => now(),
            'total_indicators' => 1,
        ]);

        $result = $job->markAsFailed('Something went wrong');

        $this->assertTrue($result);
        $this->assertEquals('failed', $job->status);
        $this->assertEquals('Something went wrong', $job->error_message);
    }

    // =========================================================================
    // PROGRESS CALCULATION TESTS
    // =========================================================================

    /** @test */
    public function calculate_progress_returns_zero_when_no_indicators(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 0,
        ]);

        $this->assertEquals(0, $job->calculateProgress());
    }

    /** @test */
    public function calculate_progress_returns_correct_percentage(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 10,
            'completed_indicators' => 5,
            'failed_indicators' => 2,
        ]);

        // (5 + 2) / 10 * 100 = 70%
        $this->assertEquals(70, $job->calculateProgress());
    }

    /** @test */
    public function calculate_progress_caps_at_100(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 10,
            'completed_indicators' => 10,
            'failed_indicators' => 5, // This would make it 150%
        ]);

        $this->assertEquals(100, $job->calculateProgress());
    }

    /** @test */
    public function calculate_progress_never_negative(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 10,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
        ]);

        $this->assertEquals(0, $job->calculateProgress());
    }

    // =========================================================================
    // DURATION CALCULATION TESTS
    // =========================================================================

    /** @test */
    public function duration_returns_null_when_not_started(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'pending',
            'total_indicators' => 1,
        ]);

        $this->assertNull($job->duration);
    }

    /** @test */
    public function duration_returns_seconds_since_start_for_running_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'started_at' => now()->subSeconds(30),
            'total_indicators' => 1,
        ]);

        $this->assertGreaterThanOrEqual(29, $job->duration);
        $this->assertLessThanOrEqual(32, $job->duration);
    }

    /** @test */
    public function duration_returns_correct_value_for_completed_job(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'started_at' => now()->subSeconds(120),
            'completed_at' => now()->subSeconds(60),
            'total_indicators' => 1,
        ]);

        $this->assertGreaterThanOrEqual(58, $job->duration);
        $this->assertLessThanOrEqual(62, $job->duration);
    }

    /** @test */
    public function duration_human_formats_seconds(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'started_at' => now()->subSeconds(45),
            'total_indicators' => 1,
        ]);

        $this->assertMatchesRegularExpression('/^\d+s$/', $job->duration_human);
    }

    /** @test */
    public function duration_human_formats_minutes_and_seconds(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'started_at' => now()->subSeconds(125),
            'total_indicators' => 1,
        ]);

        $this->assertMatchesRegularExpression('/^\d+m \d+s$/', $job->duration_human);
    }

    /** @test */
    public function duration_human_formats_hours_and_minutes(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'started_at' => now()->subSeconds(3725), // 1h 2m 5s
            'total_indicators' => 1,
        ]);

        $this->assertMatchesRegularExpression('/^\d+h \d+m$/', $job->duration_human);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function can_handle_large_indicator_count(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 100,
            'completed_indicators' => 50,
        ]);

        $this->assertEquals(50, $job->calculateProgress());
    }

    /** @test */
    public function progress_percent_accessor_uses_stored_value(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'running',
            'total_indicators' => 10,
            'completed_indicators' => 3,
            'progress_percent' => 75, // Explicitly set
        ]);

        $this->assertEquals(75, $job->progress_percent);
    }

    /** @test */
    public function can_store_error_message(): void
    {
        $longError = str_repeat('Error message. ', 100);

        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'failed',
            'error_message' => $longError,
            'total_indicators' => 1,
        ]);

        $this->assertEquals($longError, $job->error_message);
    }

    /** @test */
    public function cancelled_at_is_null_for_non_cancelled_jobs(): void
    {
        $job = AnalysisJob::create([
            'user_id' => $this->user->id,
            'site_id' => $this->site->id,
            'status' => 'completed',
            'total_indicators' => 1,
        ]);

        $this->assertNull($job->cancelled_at);
    }
}
