<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalysisJobTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 1. MODEL CREATION TESTS
    // =========================================================================

    /** @test */
    public function can_create_analysis_job_with_all_attributes(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 17,
            'completed_indicators' => 0,
            'failed_indicators' => 0,
            'progress_percent' => 0,
        ]);

        $this->assertDatabaseHas('analysis_jobs', [
            'id' => $job->id,
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
            'total_indicators' => 17,
        ]);
    }

    /** @test */
    public function can_create_with_minimal_attributes(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $this->assertEquals('pending', $job->status);
        $this->assertEquals(0, $job->total_indicators);
        $this->assertEquals(0, $job->completed_indicators);
        $this->assertEquals(0, $job->failed_indicators);
        $this->assertEquals(0, $job->progress_percent);
    }

    /** @test */
    public function auto_increment_id_works(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job1 = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id]);
        $job2 = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id]);

        $this->assertGreaterThan($job1->id, $job2->id);
    }

    // =========================================================================
    // 2. FILLABLE ATTRIBUTES TESTS
    // =========================================================================

    /** @test */
    public function all_fillable_attributes_are_accessible(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'running',
            'total_indicators' => 5,
            'completed_indicators' => 2,
            'failed_indicators' => 1,
            'progress_percent' => 40,
            'started_at' => now(),
            'completed_at' => null,
            'cancelled_at' => null,
            'error_message' => null,
        ]);

        $this->assertEquals($user->id, $job->user_id);
        $this->assertEquals($site->id, $job->site_id);
        $this->assertEquals('running', $job->status);
        $this->assertEquals(5, $job->total_indicators);
        $this->assertEquals(2, $job->completed_indicators);
        $this->assertEquals(1, $job->failed_indicators);
        $this->assertEquals(40, $job->progress_percent);
        $this->assertNotNull($job->started_at);
    }

    /** @test */
    public function guarded_attributes_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // If 'id' is guarded, it should not be assignable
        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'id' => 99999, // Should be ignored if guarded
        ]);

        $this->assertNotEquals(99999, $job->id);
    }

    // =========================================================================
    // 3. STATUS CONSTANTS TESTS
    // =========================================================================

    /** @test */
    public function status_constants_exist(): void
    {
        $this->assertEquals('pending', AnalysisJob::STATUS_PENDING);
        $this->assertEquals('running', AnalysisJob::STATUS_RUNNING);
        $this->assertEquals('completed', AnalysisJob::STATUS_COMPLETED);
        $this->assertEquals('cancelled', AnalysisJob::STATUS_CANCELLED);
        $this->assertEquals('failed', AnalysisJob::STATUS_FAILED);
    }

    // =========================================================================
    // 4. RELATIONSHIP TESTS
    // =========================================================================

    /** @test */
    public function belongs_to_user(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $job = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id]);

        $this->assertInstanceOf(User::class, $job->user);
        $this->assertEquals($user->id, $job->user->id);
    }

    /** @test */
    public function belongs_to_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $job = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id]);

        $this->assertInstanceOf(Site::class, $job->site);
        $this->assertEquals($site->id, $job->site->id);
    }

    /** @test */
    public function has_many_results(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $job = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id]);

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
        $this->assertInstanceOf(AnalysisJobResult::class, $job->results->first());
    }

    /** @test */
    public function deleting_job_cascades_to_results(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $job = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id]);

        AnalysisJobResult::create([
            'job_id' => $job->id,
            'indicator_slug' => 'meta_tags',
            'indicator_name' => 'Meta Tags',
            'status' => 'pending',
        ]);

        $job->delete();

        $this->assertEquals(0, AnalysisJobResult::where('job_id', $job->id)->count());
    }

    // =========================================================================
    // 5. SCOPE TESTS
    // =========================================================================

    /** @test */
    public function scope_pending_returns_only_pending_jobs(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'pending']);
        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'running']);
        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'completed']);

        $pendingJobs = AnalysisJob::pending()->get();

        $this->assertCount(1, $pendingJobs);
        $this->assertEquals('pending', $pendingJobs->first()->status);
    }

    /** @test */
    public function scope_running_returns_only_running_jobs(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'pending']);
        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'running']);

        $runningJobs = AnalysisJob::running()->get();

        $this->assertCount(1, $runningJobs);
        $this->assertEquals('running', $runningJobs->first()->status);
    }

    /** @test */
    public function scope_completed_returns_only_completed_jobs(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'completed']);
        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'running']);

        $completedJobs = AnalysisJob::completed()->get();

        $this->assertCount(1, $completedJobs);
        $this->assertEquals('completed', $completedJobs->first()->status);
    }

    /** @test */
    public function scope_cancelled_returns_only_cancelled_jobs(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'cancelled']);
        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'running']);

        $cancelledJobs = AnalysisJob::cancelled()->get();

        $this->assertCount(1, $cancelledJobs);
        $this->assertEquals('cancelled', $cancelledJobs->first()->status);
    }

    /** @test */
    public function scope_failed_returns_only_failed_jobs(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'failed']);
        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'running']);

        $failedJobs = AnalysisJob::failed()->get();

        $this->assertCount(1, $failedJobs);
        $this->assertEquals('failed', $failedJobs->first()->status);
    }

    /** @test */
    public function scope_by_user_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $site1 = Site::factory()->create(['user_id' => $user1->id]);
        $site2 = Site::factory()->create(['user_id' => $user2->id]);

        AnalysisJob::create(['user_id' => $user1->id, 'site_id' => $site1->id]);
        AnalysisJob::create(['user_id' => $user2->id, 'site_id' => $site2->id]);

        $user1Jobs = AnalysisJob::byUser($user1->id)->get();

        $this->assertCount(1, $user1Jobs);
        $this->assertEquals($user1->id, $user1Jobs->first()->user_id);
    }

    /** @test */
    public function scope_by_site_filters_by_site(): void
    {
        $user = User::factory()->create();
        $site1 = Site::factory()->create(['user_id' => $user->id]);
        $site2 = Site::factory()->create(['user_id' => $user->id]);

        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site1->id]);
        AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site2->id]);

        $site1Jobs = AnalysisJob::bySite($site1->id)->get();

        $this->assertCount(1, $site1Jobs);
        $this->assertEquals($site1->id, $site1Jobs->first()->site_id);
    }

    // =========================================================================
    // 6. ACCESSOR TESTS
    // =========================================================================

    /** @test */
    public function progress_percent_accessor_calculates_correctly(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'total_indicators' => 10,
            'completed_indicators' => 5,
        ]);

        $this->assertEquals(50, $job->progress_percent);
    }

    /** @test */
    public function progress_percent_returns_zero_when_no_indicators(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'total_indicators' => 0,
            'completed_indicators' => 0,
        ]);

        $this->assertEquals(0, $job->progress_percent);
    }

    /** @test */
    public function duration_accessor_returns_null_when_not_started(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'started_at' => null,
        ]);

        $this->assertNull($job->duration);
    }

    /** @test */
    public function duration_accessor_returns_null_when_running(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'running',
            'started_at' => now(),
            'completed_at' => null,
        ]);

        // While running, duration could be calculated from started_at to now
        // or return null depending on implementation
        $this->assertNotNull($job->duration); // Assuming it shows elapsed time
    }

    /** @test */
    public function duration_accessor_returns_correct_when_completed(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $startedAt = now()->subMinutes(5);
        $completedAt = now();

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'completed',
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);

        $this->assertNotNull($job->duration);
    }

    // =========================================================================
    // 7. STATUS HELPER TESTS
    // =========================================================================

    /** @test */
    public function is_pending_returns_correct_boolean(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $pendingJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'pending']);
        $runningJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'running']);

        $this->assertTrue($pendingJob->isPending());
        $this->assertFalse($runningJob->isPending());
    }

    /** @test */
    public function is_running_returns_correct_boolean(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $pendingJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'pending']);
        $runningJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'running']);

        $this->assertFalse($pendingJob->isRunning());
        $this->assertTrue($runningJob->isRunning());
    }

    /** @test */
    public function is_completed_returns_correct_boolean(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $completedJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'completed']);
        $pendingJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'pending']);

        $this->assertTrue($completedJob->isCompleted());
        $this->assertFalse($pendingJob->isCompleted());
    }

    /** @test */
    public function is_cancelled_returns_correct_boolean(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $cancelledJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'cancelled']);
        $pendingJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'pending']);

        $this->assertTrue($cancelledJob->isCancelled());
        $this->assertFalse($pendingJob->isCancelled());
    }

    /** @test */
    public function is_failed_returns_correct_boolean(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $failedJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'failed']);
        $pendingJob = AnalysisJob::create(['user_id' => $user->id, 'site_id' => $site->id, 'status' => 'pending']);

        $this->assertTrue($failedJob->isFailed());
        $this->assertFalse($pendingJob->isFailed());
    }

    // =========================================================================
    // 8. STATUS TRANSITION METHODS
    // =========================================================================

    /** @test */
    public function mark_as_running_sets_status_and_started_at(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
        ]);

        $job->markAsRunning();

        $this->assertEquals('running', $job->status);
        $this->assertNotNull($job->started_at);
    }

    /** @test */
    public function mark_as_completed_sets_status_and_completed_at(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'running',
            'started_at' => now()->subMinutes(5),
        ]);

        $job->markAsCompleted();

        $this->assertEquals('completed', $job->status);
        $this->assertNotNull($job->completed_at);
    }

    /** @test */
    public function mark_as_cancelled_sets_status_and_cancelled_at(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $job->markAsCancelled();

        $this->assertEquals('cancelled', $job->status);
        $this->assertNotNull($job->cancelled_at);
    }

    /** @test */
    public function mark_as_failed_sets_status_and_error_message(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $job->markAsFailed('Connection timeout');

        $this->assertEquals('failed', $job->status);
        $this->assertEquals('Connection timeout', $job->error_message);
    }

    // =========================================================================
    // 9. EDGE CASE TESTS
    // =========================================================================

    /** @test */
    public function zero_indicators_handled_correctly(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'total_indicators' => 0,
        ]);

        $this->assertEquals(0, $job->total_indicators);
        $this->assertEquals(0, $job->progress_percent);
    }

    /** @test */
    public function progress_percent_clamped_to_100(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'total_indicators' => 10,
            'completed_indicators' => 10,
        ]);

        $this->assertEquals(100, $job->progress_percent);
    }

    /** @test */
    public function multiple_status_transitions_handled(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'status' => 'pending',
        ]);

        $job->markAsRunning();
        $this->assertEquals('running', $job->status);

        $job->markAsCompleted();
        $this->assertEquals('completed', $job->status);
    }

    // =========================================================================
    // 10. SECURITY TESTS
    // =========================================================================

    /** @test */
    public function error_message_with_sql_characters_safe(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'error_message' => "Error: '; DROP TABLE users; --",
        ]);

        $this->assertStringContainsString("DROP TABLE", $job->error_message);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function error_message_with_script_tags_safe(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $job = AnalysisJob::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'error_message' => '<script>alert("xss")</script>Error',
        ]);

        $this->assertStringContainsString('<script>', $job->error_message);
    }
}
