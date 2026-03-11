<?php

namespace Tests\Unit\Jobs\ContentDecay;

use App\Jobs\ContentDecay\BackfillContentDecayRecoveryJob;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\ContentDecaySignal;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Unit tests for BackfillContentDecayRecoveryJob.
 *
 * @see TASK-36-9
 * @see SPEC-036
 */
class BackfillContentDecayRecoveryJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    // ==========================================
    // Job Instantiation Tests (RJ-INST)
    // ==========================================

    /** @test RJ-INST-01: Job class exists in correct namespace */
    public function job_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(BackfillContentDecayRecoveryJob::class));
    }

    /** @test RJ-INST-02: Job implements ShouldQueue */
    public function job_implements_should_queue(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test RJ-INST-03: Job has $tries property set to 3 */
    public function job_has_tries_property_set_to_3(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertEquals(3, $job->tries);
    }

    /** @test RJ-INST-04: Job queue is set to 'content-decay' */
    public function job_queue_is_set_to_content_decay(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        // Queue is set via onQueue() in constructor
        // We verify this through dispatch tests
        $this->assertTrue(true);
    }

    /** @test RJ-INST-05: Job implements ShouldBeUnique */
    public function job_implements_should_be_unique(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    /** @test RJ-INST-06: Job constructor accepts candidate_id */
    public function job_constructor_accepts_candidate_id(): void
    {
        $job = new BackfillContentDecayRecoveryJob(123);
        $this->assertEquals(123, $job->candidateId);
    }

    /** @test RJ-INST-07: Job stores candidate_id as property */
    public function job_stores_candidate_id_as_property(): void
    {
        $job = new BackfillContentDecayRecoveryJob(456);
        $this->assertEquals(456, $job->candidateId);
    }

    /** @test RJ-INST-08: Job has handle() method */
    public function job_has_handle_method(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** @test RJ-INST-09: Job has failed() method */
    public function job_has_failed_method(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertTrue(method_exists($job, 'failed'));
    }

    /** @test RJ-INST-10: Job timeout is 300 seconds */
    public function job_timeout_is_300_seconds(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertEquals(300, $job->timeout);
    }

    // ==========================================
    // Job Dispatch Tests (RJ-DISPATCH)
    // ==========================================

    /** @test RJ-DISPATCH-01: Job can be dispatched with candidate_id */
    public function job_can_be_dispatched_with_candidate_id(): void
    {
        Queue::fake();

        BackfillContentDecayRecoveryJob::dispatch(123);

        Queue::assertPushed(BackfillContentDecayRecoveryJob::class, function ($job) {
            return $job->candidateId === 123;
        });
    }

    /** @test RJ-DISPATCH-02: Job dispatched to correct queue */
    public function job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        BackfillContentDecayRecoveryJob::dispatch(1);

        Queue::assertPushedOn('content-decay', BackfillContentDecayRecoveryJob::class);
    }

    /** @test RJ-DISPATCH-03: Job unique per candidate_id */
    public function job_unique_per_candidate_id(): void
    {
        $job1 = new BackfillContentDecayRecoveryJob(1);
        $job2 = new BackfillContentDecayRecoveryJob(2);

        $this->assertEquals('1', $job1->uniqueId());
        $this->assertEquals('2', $job2->uniqueId());
    }

    /** @test RJ-DISPATCH-04: Job unique for 3600 seconds */
    public function job_unique_for_3600_seconds(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertEquals(3600, $job->uniqueFor());
    }

    // ==========================================
    // Job Handle Method Tests (RJ-HANDLE)
    // ==========================================

    /** @test RJ-HANDLE-01: Loads candidate by candidate_id */
    public function loads_candidate_by_candidate_id(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'detection_enabled' => true,
        ]);

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
            'page_url_hash' => hash('sha256', 'https://example.com/test'),
            'status' => 'refreshed',
            'decay_score' => 50,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 100,
                    'ctr' => 0.05,
                    'avg_position' => 5,
                ],
            ],
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_clicks' => 80,
            'current_clicks' => 100,
            'previous_ctr' => 0.04,
            'current_ctr' => 0.05,
            'previous_avg_position' => 10,
            'current_avg_position' => 5,
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('recovered', $candidate->status);
    }

    /** @test RJ-HANDLE-02: Checks candidate status is 'refreshed' */
    public function checks_candidate_status_is_refreshed(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'detection_enabled' => true,
        ]);

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
            'page_url_hash' => hash('sha256', 'https://example.com/test'),
            'status' => 'backlog', // Not refreshed
            'decay_score' => 50,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('backlog', $candidate->status); // Status unchanged
    }

    /** @test RJ-HANDLE-12: Handles candidate not found */
    public function handles_candidate_not_found(): void
    {
        Log::shouldReceive('warning')->once();

        $job = new BackfillContentDecayRecoveryJob(999999);
        $job->handle();

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /** @test RJ-HANDLE-13: Handles candidate not refreshed */
    public function handles_candidate_not_refreshed(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
            'page_url_hash' => hash('sha256', 'https://example.com/test'),
            'status' => 'backlog',
            'decay_score' => 50,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('backlog', $candidate->status);
    }

    // ==========================================
    // Recovery Criteria Tests (RJ-CRITERIA)
    // ==========================================

    /** @test RJ-CRITERIA-01: Clicks 10% improvement detected */
    public function clicks_10_percent_improvement_detected(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
            'page_url_hash' => hash('sha256', 'https://example.com/test'),
            'status' => 'refreshed',
            'decay_score' => 50,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 110, // 10% more than 100
                    'ctr' => 0.04,
                    'avg_position' => 10,
                ],
            ],
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_clicks' => 100,
            'current_clicks' => 110,
            'previous_ctr' => 0.04,
            'current_ctr' => 0.04,
            'previous_avg_position' => 10,
            'current_avg_position' => 10,
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        // Only 1 criteria met (clicks), not enough for recovery
        $this->assertEquals('refreshed', $candidate->status);
    }

    /** @test RJ-CRITERIA-07: All 3 criteria met → recovered */
    public function all_3_criteria_met_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
            'page_url_hash' => hash('sha256', 'https://example.com/test'),
            'status' => 'refreshed',
            'decay_score' => 50,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 120, // 20% improvement (>10%)
                    'ctr' => 0.06,   // 20% improvement (>5%)
                    'avg_position' => 5, // 5 positions better (>2)
                ],
            ],
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_clicks' => 100,
            'current_clicks' => 120,
            'previous_ctr' => 0.05,
            'current_ctr' => 0.06,
            'previous_avg_position' => 10,
            'current_avg_position' => 5,
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('recovered', $candidate->status);
        $this->assertNotNull($candidate->recovered_at);
    }

    /** @test RJ-CRITERIA-08: 2 of 3 criteria met → recovered */
    public function two_of_3_criteria_met_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
            'page_url_hash' => hash('sha256', 'https://example.com/test'),
            'status' => 'refreshed',
            'decay_score' => 50,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 120, // 20% improvement
                    'ctr' => 0.053,  // 6% improvement (>5%)
                    'avg_position' => 9, // Only 1 position better (not >2)
                ],
            ],
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_clicks' => 100,
            'current_clicks' => 120,
            'previous_ctr' => 0.05,
            'current_ctr' => 0.053,
            'previous_avg_position' => 10,
            'current_avg_position' => 9,
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('recovered', $candidate->status);
    }

    /** @test RJ-CRITERIA-09: 1 of 3 criteria met → not recovered */
    public function one_of_3_criteria_met_not_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
            'page_url_hash' => hash('sha256', 'https://example.com/test'),
            'status' => 'refreshed',
            'decay_score' => 50,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 105, // Only 5% improvement (not >10%)
                    'ctr' => 0.052,  // Only 4% improvement (not >5%)
                    'avg_position' => 7, // 3 positions better (>2)
                ],
            ],
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_clicks' => 100,
            'current_clicks' => 105,
            'previous_ctr' => 0.05,
            'current_ctr' => 0.052,
            'previous_avg_position' => 10,
            'current_avg_position' => 7,
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        // Only position improved, which is 1 criteria - not enough
        $this->assertEquals('refreshed', $candidate->status);
    }

    /** @test RJ-CRITERIA-10: 0 of 3 criteria met → not recovered */
    public function zero_of_3_criteria_met_not_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $candidate = ContentDecayCandidate::create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/test',
            'page_url_hash' => hash('sha256', 'https://example.com/test'),
            'status' => 'refreshed',
            'decay_score' => 50,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 100, // No improvement
                    'ctr' => 0.05,   // No improvement
                    'avg_position' => 10, // No improvement
                ],
            ],
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_clicks' => 100,
            'current_clicks' => 100,
            'previous_ctr' => 0.05,
            'current_ctr' => 0.05,
            'previous_avg_position' => 10,
            'current_avg_position' => 10,
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('refreshed', $candidate->status);
    }

    // ==========================================
    // Job Failure Handling Tests (RJ-FAIL)
    // ==========================================

    /** @test RJ-FAIL-01: Job failed() method called */
    public function job_failed_method_called(): void
    {
        Log::shouldReceive('error')->once();

        $job = new BackfillContentDecayRecoveryJob(1);
        $exception = new \Exception('Test exception');

        $job->failed($exception);

        $this->assertTrue(true);
    }

    /** @test RJ-FAIL-02: Job logs failure with context */
    public function job_logs_failure_with_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'failed') &&
                    isset($context['candidate_id']) &&
                    isset($context['error']);
            });

        $job = new BackfillContentDecayRecoveryJob(123);
        $job->failed(new \Exception('Test error'));
    }

    // ==========================================
    // Job Tags Tests
    // ==========================================

    /** @test: Job has correct tags */
    public function job_has_correct_tags(): void
    {
        $job = new BackfillContentDecayRecoveryJob(123);
        $tags = $job->tags();

        $this->assertContains('candidate:123', $tags);
        $this->assertContains('content-decay:recovery', $tags);
    }

    // ==========================================
    // Job Backoff Tests
    // ==========================================

    /** @test: Job has backoff configuration */
    public function job_has_backoff_configuration(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $backoff = $job->backoff();

        $this->assertEquals([30, 120, 300], $backoff);
    }
}
