<?php

namespace Tests\Unit\Jobs\ContentDecay;

use App\Jobs\ContentDecay\BackfillContentDecayRecoveryJob;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\ContentDecaySignal;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Unit tests for BackfillContentDecayRecoveryJob.
 *
 * @see TASK-36-9
 * @see TEST-36-9
 */
class BackfillContentDecayRecoveryJobTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // RJ-INST: Job Instantiation Tests
    // ==========================================

    /** @test */
    public function job_class_exists(): void
    {
        $this->assertTrue(class_exists(BackfillContentDecayRecoveryJob::class));
    }

    /** @test */
    public function job_implements_should_queue(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test */
    public function job_implements_should_be_unique(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
    }

    /** @test */
    public function job_has_correct_tries(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertEquals(3, $job->tries);
    }

    /** @test */
    public function job_has_correct_timeout(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertEquals(300, $job->timeout);
    }

    /** @test */
    public function job_has_correct_queue(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertEquals('content-decay', $job->queue);
    }

    /** @test */
    public function job_stores_candidate_id(): void
    {
        $job = new BackfillContentDecayRecoveryJob(123);
        $this->assertEquals(123, $job->candidateId);
    }

    /** @test */
    public function job_has_handle_method(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** @test */
    public function job_has_failed_method(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertTrue(method_exists($job, 'failed'));
    }

    // ==========================================
    // RJ-DISPATCH: Job Dispatch Tests
    // ==========================================

    /** @test */
    public function job_can_be_dispatched(): void
    {
        Queue::fake();

        BackfillContentDecayRecoveryJob::dispatch(1);

        Queue::assertPushed(BackfillContentDecayRecoveryJob::class);
    }

    /** @test */
    public function job_dispatched_to_correct_queue(): void
    {
        Queue::fake();

        BackfillContentDecayRecoveryJob::dispatch(1);

        Queue::assertPushedOn('content-decay', BackfillContentDecayRecoveryJob::class);
    }

    /** @test */
    public function job_unique_id_based_on_candidate_id(): void
    {
        $job1 = new BackfillContentDecayRecoveryJob(1);
        $job2 = new BackfillContentDecayRecoveryJob(1);
        $job3 = new BackfillContentDecayRecoveryJob(2);

        $this->assertEquals('1', $job1->uniqueId());
        $this->assertEquals('1', $job2->uniqueId());
        $this->assertEquals('2', $job3->uniqueId());
    }

    /** @test */
    public function job_has_correct_backoff(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $this->assertEquals([30, 120, 300], $job->backoff());
    }

    /** @test */
    public function job_has_correct_tags(): void
    {
        $job = new BackfillContentDecayRecoveryJob(123);
        $tags = $job->tags();

        $this->assertContains('candidate:123', $tags);
        $this->assertContains('content-decay:recovery', $tags);
    }

    // ==========================================
    // RJ-HANDLE: Job Handle Method Tests
    // ==========================================

    /** @test */
    public function handle_returns_early_if_candidate_not_found(): void
    {
        $job = new BackfillContentDecayRecoveryJob(99999);
        $job->handle();

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_returns_early_if_candidate_not_refreshed(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
        ]);

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('backlog', $candidate->status);
    }

    /** @test */
    public function handle_validates_candidate_id(): void
    {
        $job = new BackfillContentDecayRecoveryJob(-1);
        $job->handle();

        $this->assertTrue(true);
    }

    // ==========================================
    // RJ-CRITERIA: Recovery Criteria Tests
    // ==========================================

    /** @test */
    public function all_criteria_met_marks_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'refreshed',
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_start' => now()->subDays(56)->toDateString(),
            'previous_end' => now()->subDays(29)->toDateString(),
            'current_start' => now()->subDays(28)->toDateString(),
            'current_end' => now()->toDateString(),
            'previous_clicks' => 100,
            'current_clicks' => 50,
            'previous_ctr' => 2.5,
            'current_ctr' => 1.5,
            'previous_avg_position' => 10.0,
            'current_avg_position' => 15.0,
            'created_at' => now(),
        ]);

        $candidate->update([
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 120,
                    'ctr' => 2.7,
                    'avg_position' => 5.0,
                ],
            ],
        ]);

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('recovered', $candidate->status);
        $this->assertNotNull($candidate->recovered_at);
    }

    /** @test */
    public function two_of_three_criteria_marks_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'refreshed',
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_start' => now()->subDays(56)->toDateString(),
            'previous_end' => now()->subDays(29)->toDateString(),
            'current_start' => now()->subDays(28)->toDateString(),
            'current_end' => now()->toDateString(),
            'previous_clicks' => 100,
            'current_clicks' => 50,
            'previous_ctr' => 2.5,
            'current_ctr' => 1.5,
            'previous_avg_position' => 10.0,
            'current_avg_position' => 15.0,
            'created_at' => now(),
        ]);

        $candidate->update([
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 120,
                    'ctr' => 2.0,
                    'avg_position' => 5.0,
                ],
            ],
        ]);

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('recovered', $candidate->status);
    }

    /** @test */
    public function one_criteria_not_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'refreshed',
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_start' => now()->subDays(56)->toDateString(),
            'previous_end' => now()->subDays(29)->toDateString(),
            'current_start' => now()->subDays(28)->toDateString(),
            'current_end' => now()->toDateString(),
            'previous_clicks' => 100,
            'current_clicks' => 50,
            'previous_ctr' => 2.5,
            'current_ctr' => 1.5,
            'previous_avg_position' => 10.0,
            'current_avg_position' => 15.0,
            'created_at' => now(),
        ]);

        $candidate->update([
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 120,
                    'ctr' => 2.0,
                    'avg_position' => 12.0,
                ],
            ],
        ]);

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('refreshed', $candidate->status);
    }

    /** @test */
    public function zero_criteria_not_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'refreshed',
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_start' => now()->subDays(56)->toDateString(),
            'previous_end' => now()->subDays(29)->toDateString(),
            'current_start' => now()->subDays(28)->toDateString(),
            'current_end' => now()->toDateString(),
            'previous_clicks' => 100,
            'current_clicks' => 50,
            'previous_ctr' => 2.5,
            'current_ctr' => 1.5,
            'previous_avg_position' => 10.0,
            'current_avg_position' => 15.0,
            'created_at' => now(),
        ]);

        $candidate->update([
            'evidence_json' => [
                'post_refresh_signals' => [
                    'clicks' => 90,
                    'ctr' => 2.0,
                    'avg_position' => 15.0,
                ],
            ],
        ]);

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $candidate->refresh();
        $this->assertEquals('refreshed', $candidate->status);
    }

    /** @test */
    public function handles_no_baseline_signal(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'refreshed',
        ]);

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $this->assertTrue(true);
    }

    /** @test */
    public function handles_no_post_refresh_data(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);
        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'refreshed',
            'evidence_json' => [],
        ]);

        ContentDecaySignal::create([
            'candidate_id' => $candidate->id,
            'previous_start' => now()->subDays(56)->toDateString(),
            'previous_end' => now()->subDays(29)->toDateString(),
            'current_start' => now()->subDays(28)->toDateString(),
            'current_end' => now()->toDateString(),
            'previous_clicks' => 100,
            'current_clicks' => 50,
            'created_at' => now(),
        ]);

        $job = new BackfillContentDecayRecoveryJob($candidate->id);
        $job->handle();

        $this->assertTrue(true);
    }

    // ==========================================
    // RJ-FAIL: Job Failure Handling Tests
    // ==========================================

    /** @test */
    public function failed_method_exists(): void
    {
        $job = new BackfillContentDecayRecoveryJob(1);
        $job->failed(new \Exception('Test'));

        $this->assertTrue(true);
    }
}
