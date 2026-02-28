<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\SyncBacklinksJob;
use App\Models\Backlink;
use App\Models\BacklinkSnapshot;
use App\Models\GSCConnection;
use App\Models\Project;
use App\Models\ReferringDomain;
use App\Models\User;
use App\Services\Backlinks\BacklinkSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;

/**
 * Unit tests for TASK-23-4: SyncBacklinksJob
 *
 * Tests the queued job responsible for syncing backlink data from GSC.
 *
 * @see TEST-23-4
 * @see TASK-23-4
 */
class SyncBacklinksJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    // =========================================================================
    // JOB CONSTRUCTION TESTS (JOB-CON)
    // =========================================================================

    /**
     * JOB-CON-01: Create with project ID
     */
    public function test_create_with_project_id(): void
    {
        $job = new SyncBacklinksJob(1);

        $this->assertEquals(1, $job->projectId);
    }

    /**
     * JOB-CON-02: Create with force flag
     */
    public function test_create_with_force_flag(): void
    {
        $job = new SyncBacklinksJob(1, true);

        $this->assertTrue($job->force);
    }

    /**
     * JOB-CON-03: Default force flag
     */
    public function test_default_force_flag(): void
    {
        $job = new SyncBacklinksJob(1);

        $this->assertFalse($job->force);
    }

    /**
     * JOB-CON-04: Job implements ShouldQueue
     */
    public function test_job_implements_should_queue(): void
    {
        $job = new SyncBacklinksJob(1);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /**
     * JOB-CON-05: Job uses Queueable trait
     */
    public function test_job_uses_queueable_trait(): void
    {
        $job = new SyncBacklinksJob(1);

        $this->assertContains(\Illuminate\Bus\Queueable::class, class_uses_recursive($job));
    }

    /**
     * JOB-CON-06: Job tries configuration
     */
    public function test_job_tries_configuration(): void
    {
        $job = new SyncBacklinksJob(1);

        $this->assertEquals(3, $job->tries);
    }

    /**
     * JOB-CON-07: Job timeout configuration
     */
    public function test_job_timeout_configuration(): void
    {
        $job = new SyncBacklinksJob(1);

        $this->assertEquals(300, $job->timeout);
    }

    /**
     * JOB-CON-08: Unique job ID
     */
    public function test_unique_job_id(): void
    {
        $job = new SyncBacklinksJob(123);

        $this->assertEquals('backlink-sync-123', $job->uniqueId());
    }

    /**
     * JOB-CON-09: Different projects have different unique IDs
     */
    public function test_different_projects_have_different_unique_ids(): void
    {
        $job1 = new SyncBacklinksJob(1);
        $job2 = new SyncBacklinksJob(2);

        $this->assertNotEquals($job1->uniqueId(), $job2->uniqueId());
    }

    // =========================================================================
    // JOB EXECUTION TESTS (JOB-EXEC)
    // =========================================================================

    /**
     * JOB-EXEC-01: Execute with valid project
     */
    public function test_execute_with_valid_project(): void
    {
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        // Create some existing backlink data
        Backlink::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        ReferringDomain::factory()->create([
            'project_id' => $project->id,
        ]);

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);
        $snapshotService->shouldReceive('createSnapshot')
            ->once()
            ->andReturn(BacklinkSnapshot::create([
                'project_id' => $project->id,
                'total_backlinks' => 1,
                'referring_domains_count' => 1,
                'dofollow_count' => 1,
                'nofollow_count' => 0,
                'avg_domain_trust' => 50,
                'anchor_diversity_score' => 75,
                'snapshot_date' => now()->toDateString(),
                'created_at' => now(),
            ]));

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        // Verify snapshot was created
        $this->assertDatabaseHas('backlink_snapshots', [
            'project_id' => $project->id,
        ]);
    }

    /**
     * JOB-EXEC-02: Execute with non-existent project
     */
    public function test_execute_with_non_existent_project(): void
    {
        Log::shouldReceive('warning')->once()->with(
            'SyncBacklinksJob: Project not found',
            Mockery::hasKey('project_id')
        );

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);
        $snapshotService->shouldNotReceive('createSnapshot');

        $job = new SyncBacklinksJob(9999);
        $job->handle($snapshotService);

        // Should return early without creating snapshot
        $this->assertEquals(0, BacklinkSnapshot::count());
    }

    /**
     * JOB-EXEC-03: Execute with project without domain
     */
    public function test_execute_with_project_without_domain(): void
    {
        Log::shouldReceive('warning')->once()->with(
            'SyncBacklinksJob: Project has no domain',
            Mockery::hasKey('project_id')
        );

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => null,
        ]);

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);
        $snapshotService->shouldNotReceive('createSnapshot');

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        $this->assertEquals(0, BacklinkSnapshot::count());
    }

    /**
     * JOB-EXEC-04: Execute with no GSC connection
     */
    public function test_execute_with_no_gsc_connection(): void
    {
        Log::shouldReceive('warning')->once()->with(
            'SyncBacklinksJob: No active GSC connection',
            Mockery::hasKey('project_id')
        );

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);
        $snapshotService->shouldNotReceive('createSnapshot');

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        $this->assertEquals(0, BacklinkSnapshot::count());
    }

    /**
     * JOB-EXEC-05: Execute with expired GSC connection
     */
    public function test_execute_with_expired_gsc_connection(): void
    {
        Log::shouldReceive('warning')->once()->with(
            'SyncBacklinksJob: No active GSC connection',
            Mockery::hasKey('project_id')
        );

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_EXPIRED,
        ]);

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);
        $snapshotService->shouldNotReceive('createSnapshot');

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        $this->assertEquals(0, BacklinkSnapshot::count());
    }

    /**
     * JOB-EXEC-06: Execute with revoked GSC connection
     */
    public function test_execute_with_revoked_gsc_connection(): void
    {
        Log::shouldReceive('warning')->once()->with(
            'SyncBacklinksJob: No active GSC connection',
            Mockery::hasKey('project_id')
        );

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_REVOKED,
        ]);

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);
        $snapshotService->shouldNotReceive('createSnapshot');

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        $this->assertEquals(0, BacklinkSnapshot::count());
    }

    /**
     * JOB-EXEC-07: Execute creates snapshot
     */
    public function test_execute_creates_snapshot(): void
    {
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        // Create test data
        Backlink::factory()->count(5)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        ReferringDomain::factory()->count(2)->create([
            'project_id' => $project->id,
        ]);

        $snapshotService = app(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        $this->assertEquals(1, BacklinkSnapshot::where('project_id', $project->id)->count());
    }

    /**
     * JOB-EXEC-08: Execute with exception
     */
    public function test_execute_with_exception(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);
        $snapshotService->shouldReceive('createSnapshot')
            ->once()
            ->andThrow(new \Exception('GSC API error'));

        $job = new SyncBacklinksJob($project->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GSC API error');

        $job->handle($snapshotService);
    }

    /**
     * JOB-EXEC-09: Execute respects queue
     */
    public function test_execute_respects_queue(): void
    {
        Queue::fake();

        SyncBacklinksJob::dispatch(1);

        Queue::assertPushed(SyncBacklinksJob::class, function ($job) {
            return $job->projectId === 1;
        });
    }

    /**
     * JOB-EXEC-10: Execute with multiple GSC connections
     */
    public function test_execute_with_multiple_gsc_connections(): void
    {
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        // Create multiple connections (one active, one expired)
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_EXPIRED,
        ]);

        Backlink::factory()->create(['project_id' => $project->id]);
        ReferringDomain::factory()->create(['project_id' => $project->id]);

        $snapshotService = app(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        // Should still work with at least one active connection
        $this->assertEquals(1, BacklinkSnapshot::where('project_id', $project->id)->count());
    }

    // =========================================================================
    // LOGGING TESTS (JOB-LOG)
    // =========================================================================

    /**
     * JOB-LOG-01: Log sync start
     */
    public function test_log_sync_start(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with(
                'SyncBacklinksJob: Starting sync',
                Mockery::hasKey('project_id')
            );

        Log::shouldReceive('info')->once(); // Completion log

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        Backlink::factory()->create(['project_id' => $project->id]);
        ReferringDomain::factory()->create(['project_id' => $project->id]);

        $snapshotService = app(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);
    }

    /**
     * JOB-LOG-02: Log sync completion
     */
    public function test_log_sync_completion(): void
    {
        // We need to capture the info calls without strict argument matching
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        Backlink::factory()->create(['project_id' => $project->id]);
        ReferringDomain::factory()->create(['project_id' => $project->id]);

        $snapshotService = app(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        // Verify snapshot was created (indicates completion)
        $this->assertDatabaseHas('backlink_snapshots', [
            'project_id' => $project->id,
        ]);
    }

    /**
     * JOB-LOG-03: Log sync failure
     */
    public function test_log_sync_failure(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->with(
                'SyncBacklinksJob: Sync failed',
                Mockery::on(function ($context) {
                    return isset($context['project_id']) &&
                           isset($context['error']);
                })
            );

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);
        $snapshotService->shouldReceive('createSnapshot')
            ->once()
            ->andThrow(new \Exception('Test error'));

        $job = new SyncBacklinksJob($project->id);

        try {
            $job->handle($snapshotService);
        } catch (\Exception $e) {
            // Expected
        }
    }

    /**
     * JOB-LOG-04: Log project not found
     */
    public function test_log_project_not_found(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                'SyncBacklinksJob: Project not found',
                ['project_id' => 9999]
            );

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob(9999);
        $job->handle($snapshotService);
    }

    /**
     * JOB-LOG-05: Log no GSC connection
     */
    public function test_log_no_gsc_connection(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                'SyncBacklinksJob: No active GSC connection',
                Mockery::hasKey('project_id')
            );

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        $snapshotService = Mockery::mock(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);
    }

    // =========================================================================
    // FAILED JOB HANDLING TESTS (JOB-FAIL)
    // =========================================================================

    /**
     * JOB-FAIL-01: failed() method logs error
     */
    public function test_failed_method_logs_error(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(
                'SyncBacklinksJob: Job failed',
                Mockery::on(function ($context) {
                    return isset($context['project_id']) &&
                           isset($context['error']);
                })
            );

        $job = new SyncBacklinksJob(1);
        $job->failed(new \Exception('Test failure'));
    }

    /**
     * JOB-FAIL-02: Failed job includes project context
     */
    public function test_failed_job_includes_project_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(
                'SyncBacklinksJob: Job failed',
                Mockery::on(function ($context) {
                    return $context['project_id'] === 123;
                })
            );

        $job = new SyncBacklinksJob(123);
        $job->failed(new \Exception('Test failure'));
    }

    /**
     * JOB-FAIL-03: Failed job handles exception message
     */
    public function test_failed_job_handles_exception_message(): void
    {
        $exceptionMessage = 'GSC API rate limit exceeded';

        Log::shouldReceive('error')
            ->once()
            ->with(
                'SyncBacklinksJob: Job failed',
                Mockery::on(function ($context) use ($exceptionMessage) {
                    return $context['error'] === $exceptionMessage;
                })
            );

        $job = new SyncBacklinksJob(1);
        $job->failed(new \Exception($exceptionMessage));
    }

    // =========================================================================
    // DISPATCH TESTS
    // =========================================================================

    /**
     * Test job can be dispatched
     */
    public function test_job_can_be_dispatched(): void
    {
        Queue::fake();

        SyncBacklinksJob::dispatch(1, false);

        Queue::assertPushed(SyncBacklinksJob::class);
    }

    /**
     * Test job can be dispatched with force
     */
    public function test_job_can_be_dispatched_with_force(): void
    {
        Queue::fake();

        SyncBacklinksJob::dispatch(1, true);

        Queue::assertPushed(SyncBacklinksJob::class, function ($job) {
            return $job->force === true;
        });
    }

    /**
     * Test job dispatches to queue
     */
    public function test_job_dispatches_to_queue(): void
    {
        Queue::fake();

        SyncBacklinksJob::dispatch(1);

        Queue::assertPushed(SyncBacklinksJob::class);
    }

    // =========================================================================
    // SNAPSHOT INTEGRATION TESTS
    // =========================================================================

    /**
     * Test snapshot has correct backlink count
     */
    public function test_snapshot_has_correct_backlink_count(): void
    {
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        // Create exactly 10 active backlinks
        Backlink::factory()->count(10)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        ReferringDomain::factory()->create(['project_id' => $project->id]);

        $snapshotService = app(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        $snapshot = BacklinkSnapshot::where('project_id', $project->id)->first();
        $this->assertEquals(10, $snapshot->total_backlinks);
    }

    /**
     * Test snapshot has correct referring domains count
     */
    public function test_snapshot_has_correct_referring_domains_count(): void
    {
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        Backlink::factory()->create(['project_id' => $project->id]);

        // Create exactly 5 referring domains
        ReferringDomain::factory()->count(5)->create([
            'project_id' => $project->id,
        ]);

        $snapshotService = app(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        $snapshot = BacklinkSnapshot::where('project_id', $project->id)->first();
        $this->assertEquals(5, $snapshot->referring_domains_count);
    }

    /**
     * Test snapshot has correct dofollow/nofollow counts
     */
    public function test_snapshot_has_correct_link_type_counts(): void
    {
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        // Create 7 dofollow and 3 nofollow
        Backlink::factory()->count(7)->create([
            'project_id' => $project->id,
            'link_type' => 'dofollow',
            'is_active' => true,
        ]);
        Backlink::factory()->count(3)->create([
            'project_id' => $project->id,
            'link_type' => 'nofollow',
            'is_active' => true,
        ]);

        ReferringDomain::factory()->create(['project_id' => $project->id]);

        $snapshotService = app(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        $snapshot = BacklinkSnapshot::where('project_id', $project->id)->first();
        $this->assertEquals(7, $snapshot->dofollow_count);
        $this->assertEquals(3, $snapshot->nofollow_count);
    }

    /**
     * Test snapshot excludes inactive backlinks
     */
    public function test_snapshot_excludes_inactive_backlinks(): void
    {
        Log::shouldReceive('info')->twice();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        // Create 5 active and 3 inactive
        Backlink::factory()->count(5)->create([
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        Backlink::factory()->count(3)->create([
            'project_id' => $project->id,
            'is_active' => false,
        ]);

        ReferringDomain::factory()->create(['project_id' => $project->id]);

        $snapshotService = app(BacklinkSnapshotService::class);

        $job = new SyncBacklinksJob($project->id);
        $job->handle($snapshotService);

        $snapshot = BacklinkSnapshot::where('project_id', $project->id)->first();
        // Only active backlinks should be counted
        $this->assertEquals(5, $snapshot->total_backlinks);
    }
}
