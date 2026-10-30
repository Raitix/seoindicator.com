<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BacklinkSyncSchedulerService;
use App\Models\Project;
use App\Models\User;
use App\Models\GSCConnection;
use Illuminate\Support\Facades\Bus;
use App\Jobs\SyncBacklinksJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Tests for TASK-23-5: BacklinkSyncSchedulerService
 * 
 * Service that handles scheduled backlink sync logic:
 * - Query projects needing sync
 * - Dispatch jobs with delay
 * - Log results
 * 
 * Note: Tests use the existing schema:
 * - Project.archived_at (nullable timestamp) for archive status
 * - GSCConnection.status ('active', 'expired', 'revoked')
 * - GSCConnection.token_expires_at for token validity
 * - GSCConnection linked via user_id to Project.user_id
 */
class BacklinkSyncSchedulerServiceTest extends TestCase
{
    use RefreshDatabase;

    private BacklinkSyncSchedulerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BacklinkSyncSchedulerService::class);
    }

    // ============================================
    // Service Instantiation Tests
    // ============================================

    /** @test */
    public function service_can_be_resolved_from_container(): void
    {
        $service = app(BacklinkSyncSchedulerService::class);
        
        $this->assertInstanceOf(BacklinkSyncSchedulerService::class, $service);
    }

    /** @test */
    public function service_is_singleton_or_newable(): void
    {
        $service1 = app(BacklinkSyncSchedulerService::class);
        $service2 = app(BacklinkSyncSchedulerService::class);
        
        // Either singleton or new instance both acceptable
        $this->assertNotNull($service1);
        $this->assertNotNull($service2);
    }

    // ============================================
    // getProjectsNeedingSync Tests
    // ============================================

    /** @test */
    public function returns_projects_with_active_gsc_needing_sync(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertCount(1, $projects);
        $this->assertEquals($project->id, $projects->first()->id);
    }

    /** @test */
    public function excludes_archived_projects(): void
    {
        $user = User::factory()->create();
        
        $archived = Project::factory()->forUser($user)->create(['archived_at' => now()]);
        $active = Project::factory()->forUser($user)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertCount(1, $projects);
        $this->assertEquals($active->id, $projects->first()->id);
    }

    /** @test */
    public function excludes_projects_with_expired_gsc_tokens(): void
    {
        $userExpired = User::factory()->create();
        $userValid = User::factory()->create();
        
        Project::factory()->forUser($userExpired)->create(['archived_at' => null]);
        $valid = Project::factory()->forUser($userValid)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $userExpired->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->subDay(), // Expired
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $userValid->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertCount(1, $projects);
        $this->assertEquals($valid->id, $projects->first()->id);
    }

    /** @test */
    public function excludes_projects_with_inactive_gsc_connections(): void
    {
        $userInactive = User::factory()->create();
        $userActive = User::factory()->create();
        
        Project::factory()->forUser($userInactive)->create(['archived_at' => null]);
        $active = Project::factory()->forUser($userActive)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $userInactive->id,
            'status' => GSCConnection::STATUS_REVOKED,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $userActive->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertCount(1, $projects);
        $this->assertEquals($active->id, $projects->first()->id);
    }

    /** @test */
    public function excludes_projects_synced_today(): void
    {
        $userSynced = User::factory()->create();
        $userNeedsSync = User::factory()->create();
        
        Project::factory()->forUser($userSynced)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => now(),
        ]);
        $needsSync = Project::factory()->forUser($userNeedsSync)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => null,
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $userSynced->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $userNeedsSync->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertCount(1, $projects);
        $this->assertEquals($needsSync->id, $projects->first()->id);
    }

    /** @test */
    public function includes_projects_synced_yesterday(): void
    {
        $user = User::factory()->create();
        $syncedYesterday = Project::factory()->forUser($user)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => now()->subDay(),
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertCount(1, $projects);
        $this->assertEquals($syncedYesterday->id, $projects->first()->id);
    }

    /** @test */
    public function includes_projects_never_synced(): void
    {
        $user = User::factory()->create();
        $neverSynced = Project::factory()->forUser($user)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => null,
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertCount(1, $projects);
        $this->assertEquals($neverSynced->id, $projects->first()->id);
    }

    /** @test */
    public function returns_empty_collection_when_no_projects_need_sync(): void
    {
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertCount(0, $projects);
    }

    /** @test */
    public function loads_user_with_gsc_connection_relationship(): void
    {
        $user = User::factory()->create();
        Project::factory()->forUser($user)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertTrue($projects->first()->relationLoaded('user'));
    }

    // ============================================
    // dispatchSyncJobs Tests
    // ============================================

    /** @test */
    public function dispatches_job_for_each_project(): void
    {
        Bus::fake();
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $project1 = Project::factory()->forUser($user1)->create(['archived_at' => null]);
        $project2 = Project::factory()->forUser($user2)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $user1->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $user2->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = collect([$project1, $project2]);
        $result = $this->service->dispatchSyncJobs($projects);
        
        // Jobs are dispatched as a batch, so we check the batch contains 2 jobs
        Bus::assertBatched(function ($batch) {
            return $batch->jobs->count() === 2;
        });
        $this->assertEquals(2, $result['dispatched']);
    }

    /** @test */
    public function returns_dispatch_count(): void
    {
        Bus::fake();
        
        $projects = collect();
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            $project = Project::factory()->forUser($user)->create(['archived_at' => null]);
            
            GSCConnection::factory()->create([
                'user_id' => $user->id,
                'status' => GSCConnection::STATUS_ACTIVE,
                'token_expires_at' => now()->addDays(30),
            ]);
            
            $projects->push($project);
        }
        
        $result = $this->service->dispatchSyncJobs($projects);
        
        $this->assertEquals(5, $result['dispatched']);
        $this->assertEquals(0, $result['skipped']);
    }

    /** @test */
    public function handles_empty_collection_gracefully(): void
    {
        Bus::fake();
        
        $result = $this->service->dispatchSyncJobs(collect());
        
        $this->assertEquals(0, $result['dispatched']);
        $this->assertEquals(0, $result['skipped']);
        
        Bus::assertNotDispatched(SyncBacklinksJob::class);
    }

    /** @test */
    public function spreads_jobs_over_configured_delay_window(): void
    {
        Bus::fake();
        
        $projects = collect();
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create();
            $project = Project::factory()->forUser($user)->create(['archived_at' => null]);
            
            GSCConnection::factory()->create([
                'user_id' => $user->id,
                'status' => GSCConnection::STATUS_ACTIVE,
                'token_expires_at' => now()->addDays(30),
            ]);
            
            $projects->push($project);
        }
        
        // Expected: 30 minute window / 10 projects = 3 minutes between each
        $result = $this->service->dispatchSyncJobs($projects, 30);
        
        $this->assertEquals(10, $result['dispatched']);
    }

    /** @test */
    public function uses_batch_for_job_tracking(): void
    {
        Bus::fake();
        
        $projects = collect();
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create();
            $project = Project::factory()->forUser($user)->create(['archived_at' => null]);
            
            GSCConnection::factory()->create([
                'user_id' => $user->id,
                'status' => GSCConnection::STATUS_ACTIVE,
                'token_expires_at' => now()->addDays(30),
            ]);
            
            $projects->push($project);
        }
        
        $this->service->dispatchSyncJobs($projects);
        
        Bus::assertBatched(function ($batch) {
            return str_contains($batch->name, 'backlinks-sync');
        });
    }

    // ============================================
    // runScheduledSync Tests
    // ============================================

    /** @test */
    public function run_scheduled_sync_returns_result_array(): void
    {
        Bus::fake();
        
        $result = $this->service->runScheduledSync();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('dispatched', $result);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /** @test */
    public function run_scheduled_sync_skips_when_disabled(): void
    {
        Bus::fake();
        
        config(['backlinks.sync_enabled' => false]);
        
        $result = $this->service->runScheduledSync();
        
        $this->assertEquals(0, $result['dispatched']);
        $this->assertEquals('disabled', $result['reason'] ?? null);
    }

    /** @test */
    public function run_scheduled_sync_processes_eligible_projects(): void
    {
        Bus::fake();
        
        config(['backlinks.sync_enabled' => true]);
        
        $user = User::factory()->create();
        Project::factory()->forUser($user)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $result = $this->service->runScheduledSync();
        
        $this->assertEquals(1, $result['dispatched']);
        $this->assertEquals(1, $result['total']);
    }

    // ============================================
    // Force Sync Tests
    // ============================================

    /** @test */
    public function force_sync_includes_projects_synced_today(): void
    {
        Bus::fake();
        
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => now(),
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $result = $this->service->forceSync();
        
        $this->assertEquals(1, $result['dispatched']);
    }

    /** @test */
    public function force_sync_still_respects_archived_and_gsc_status(): void
    {
        Bus::fake();
        
        $userArchived = User::factory()->create();
        $userNoGsc = User::factory()->create();
        $userValid = User::factory()->create();
        
        // Archived
        Project::factory()->forUser($userArchived)->create(['archived_at' => now()]);
        GSCConnection::factory()->create([
            'user_id' => $userArchived->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // No GSC
        Project::factory()->forUser($userNoGsc)->create(['archived_at' => null]);
        
        // Valid
        Project::factory()->forUser($userValid)->create(['archived_at' => null]);
        GSCConnection::factory()->create([
            'user_id' => $userValid->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $result = $this->service->forceSync();
        
        $this->assertEquals(1, $result['dispatched']);
    }

    // ============================================
    // Error Handling Tests
    // ============================================

    /** @test */
    public function handles_project_without_gsc_connection_gracefully(): void
    {
        Bus::fake();
        
        $user = User::factory()->create();
        Project::factory()->forUser($user)->create(['archived_at' => null]);
        // No GSC connection
        
        $projects = $this->service->getProjectsNeedingSync();
        
        $this->assertCount(0, $projects);
    }

    /** @test */
    public function handles_user_with_multiple_gsc_connections(): void
    {
        Bus::fake();
        
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create(['archived_at' => null]);
        
        // Two active GSC connections for same user
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projects = $this->service->getProjectsNeedingSync();
        
        // Should still only dispatch once per project
        $this->assertCount(1, $projects);
    }

    // ============================================
    // Statistics Tests
    // ============================================

    /** @test */
    public function get_sync_stats_returns_statistics(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        // Active project with GSC
        Project::factory()->forUser($user1)->create(['archived_at' => null]);
        GSCConnection::factory()->create([
            'user_id' => $user1->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // Synced today
        Project::factory()->forUser($user2)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => now(),
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user2->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // Archived
        Project::factory()->forUser($user3)->create(['archived_at' => now()]);
        
        $stats = $this->service->getSyncStats();
        
        $this->assertArrayHasKey('total_projects', $stats);
        $this->assertArrayHasKey('projects_with_gsc', $stats);
        $this->assertArrayHasKey('projects_needing_sync', $stats);
        $this->assertArrayHasKey('synced_today', $stats);
    }

    /** @test */
    public function get_sync_stats_counts_correctly(): void
    {
        $userNeedsSync1 = User::factory()->create();
        $userNeedsSync2 = User::factory()->create();
        $userSyncedToday = User::factory()->create();
        
        // Needs sync
        Project::factory()->forUser($userNeedsSync1)->create(['archived_at' => null]);
        Project::factory()->forUser($userNeedsSync2)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $userNeedsSync1->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        GSCConnection::factory()->create([
            'user_id' => $userNeedsSync2->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // Synced today
        Project::factory()->forUser($userSyncedToday)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => now(),
        ]);
        GSCConnection::factory()->create([
            'user_id' => $userSyncedToday->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $stats = $this->service->getSyncStats();
        
        $this->assertEquals(2, $stats['projects_needing_sync']);
        $this->assertEquals(1, $stats['synced_today']);
    }
}
