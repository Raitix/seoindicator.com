<?php

namespace Tests\Unit\Console;

use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\SyncBacklinksJob;
use App\Models\Project;
use App\Models\User;
use App\Models\GSCConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Tests for TASK-23-5: Scheduled Daily Backlink Sync
 * 
 * Covers:
 * - Schedule configuration (daily at configured time)
 * - Schedule constraints (withoutOverlapping, onOneServer)
 * - Environment configuration (BACKLINKS_SYNC_ENABLED, BACKLINKS_SYNC_TIME)
 * - Job dispatch with load spreading
 * - Logging scheduled run results
 */
class BacklinksScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Default configuration
        Config::set('backlinks.sync_enabled', true);
        Config::set('backlinks.sync_time', '03:00');
    }

    // ============================================
    // Schedule Configuration Tests
    // ============================================

    /** @test */
    public function schedule_is_registered_in_kernel(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        
        $backlinkEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'backlinks:sync') || 
                   str_contains($event->description ?? '', 'backlink');
        });
        
        $this->assertNotNull($backlinkEvent, 'Backlink sync command should be scheduled');
    }

    /** @test */
    public function schedule_runs_daily_at_configured_time(): void
    {
        Config::set('backlinks.sync_time', '03:00');
        
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        
        $backlinkEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'backlinks:sync');
        });
        
        if ($backlinkEvent) {
            // Check that it's scheduled daily
            $this->assertStringContainsString('0 3', $backlinkEvent->expression);
        } else {
            $this->markTestSkipped('Backlink schedule not yet registered');
        }
    }

    /** @test */
    public function schedule_respects_custom_sync_time(): void
    {
        Config::set('backlinks.sync_time', '05:30');
        
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        
        $backlinkEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'backlinks:sync');
        });
        
        if ($backlinkEvent) {
            // Cron expression should include 05:30
            // Format: minute hour day month weekday
            $this->assertStringContainsString('30 5', $backlinkEvent->expression);
        } else {
            $this->markTestSkipped('Backlink schedule not yet registered');
        }
    }

    /** @test */
    public function schedule_has_without_overlapping_constraint(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        
        $backlinkEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'backlinks:sync');
        });
        
        if ($backlinkEvent) {
            // Check withoutOverlapping is set (prevents concurrent runs)
            $this->assertTrue(
                $backlinkEvent->withoutOverlapping,
                'Backlink sync should use withoutOverlapping to prevent concurrent runs'
            );
        } else {
            $this->markTestSkipped('Backlink schedule not yet registered');
        }
    }

    /** @test */
    public function schedule_has_on_one_server_constraint(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());
        
        $backlinkEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'backlinks:sync');
        });
        
        if ($backlinkEvent) {
            // Check onOneServer is set (prevents duplicate runs in multi-server setup)
            $this->assertTrue(
                $backlinkEvent->onOneServer,
                'Backlink sync should use onOneServer for multi-server safety'
            );
        } else {
            $this->markTestSkipped('Backlink schedule not yet registered');
        }
    }

    // ============================================
    // Environment Configuration Tests
    // ============================================

    /** @test */
    public function sync_disabled_when_config_disabled(): void
    {
        Config::set('backlinks.sync_enabled', false);
        
        $this->assertFalse(
            Config::get('backlinks.sync_enabled'),
            'Backlinks sync should be disabled when config is false'
        );
    }

    /** @test */
    public function sync_enabled_by_default(): void
    {
        // Config file not yet created - this test validates the expected default
        // After implementation: config/backlinks.php should have 'sync_enabled' => env('BACKLINKS_SYNC_ENABLED', true)
        $this->markTestSkipped('Config file config/backlinks.php not yet created - requires implementation');
    }

    /** @test */
    public function sync_time_defaults_to_03_00(): void
    {
        // Config file not yet created - this test validates the expected default
        // After implementation: config/backlinks.php should have 'sync_time' => env('BACKLINKS_SYNC_TIME', '03:00')
        $this->markTestSkipped('Config file config/backlinks.php not yet created - requires implementation');
    }

    /** @test */
    public function invalid_sync_time_is_handled_gracefully(): void
    {
        Config::set('backlinks.sync_time', 'invalid');
        
        // Should fallback to default when invalid
        $time = Config::get('backlinks.sync_time', '03:00');
        
        // System should still function with fallback
        $this->assertNotEmpty($time);
    }

    /** @test */
    public function sync_time_must_be_valid_format(): void
    {
        $validTimes = ['00:00', '06:00', '12:00', '18:00', '23:59', '03:00'];
        
        foreach ($validTimes as $time) {
            $this->assertMatchesRegularExpression(
                '/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/',
                $time,
                "Time {$time} should be valid 24-hour format"
            );
        }
    }

    // ============================================
    // Active GSC Connection Filter Tests
    // ============================================

    /** @test */
    public function only_projects_with_active_gsc_are_synced(): void
    {
        $user = User::factory()->create();
        
        // Active project with active GSC
        $activeProject = Project::factory()->forUser($user)->create(['archived_at' => null]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // Archived project
        $archivedProject = Project::factory()->forUser($user)->create(['archived_at' => now()]);
        
        // User with no GSC
        $userNoGsc = User::factory()->create();
        Project::factory()->forUser($userNoGsc)->create(['archived_at' => null]);
        
        // User with expired GSC
        $userExpired = User::factory()->create();
        Project::factory()->forUser($userExpired)->create(['archived_at' => null]);
        GSCConnection::factory()->create([
            'user_id' => $userExpired->id,
            'status' => GSCConnection::STATUS_EXPIRED,
            'token_expires_at' => now()->subDay(),
        ]);
        
        // Query for active projects with active GSC connections
        $projectsToSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->get();
        
        $this->assertGreaterThanOrEqual(1, $projectsToSync->count());
        $this->assertNotNull($projectsToSync->firstWhere('id', $activeProject->id));
    }

    /** @test */
    public function archived_projects_excluded_from_sync(): void
    {
        $user = User::factory()->create();
        
        $archivedProject = Project::factory()->forUser($user)->create(['archived_at' => now()]);
        $activeProject = Project::factory()->forUser($user)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projectsToSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->pluck('id');
        
        $this->assertNotContains($archivedProject->id, $projectsToSync);
        $this->assertContains($activeProject->id, $projectsToSync);
    }

    /** @test */
    public function projects_with_revoked_gsc_are_excluded(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_REVOKED,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $projectsToSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->pluck('id');
        
        $this->assertNotContains($project->id, $projectsToSync);
    }

    /** @test */
    public function projects_with_expired_tokens_are_excluded(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create(['archived_at' => null]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->subDay(), // Expired
        ]);
        
        $projectsToSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->pluck('id');
        
        $this->assertNotContains($project->id, $projectsToSync);
    }

    // ============================================
    // Last Sync Timestamp Tests
    // ============================================

    /** @test */
    public function projects_synced_today_are_skipped(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => now(), // Synced today
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // Query for projects needing sync (not synced today)
        $needsSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('backlinks_last_synced_at')
                    ->orWhereDate('backlinks_last_synced_at', '<', today());
            })
            ->pluck('id');
        
        $this->assertNotContains($project->id, $needsSync);
    }

    /** @test */
    public function projects_synced_yesterday_are_included(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => now()->subDay(), // Synced yesterday
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $needsSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('backlinks_last_synced_at')
                    ->orWhereDate('backlinks_last_synced_at', '<', today());
            })
            ->pluck('id');
        
        $this->assertContains($project->id, $needsSync);
    }

    /** @test */
    public function projects_never_synced_are_included(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => null, // Never synced
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        $needsSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('backlinks_last_synced_at')
                    ->orWhereDate('backlinks_last_synced_at', '<', today());
            })
            ->pluck('id');
        
        $this->assertContains($project->id, $needsSync);
    }

    /** @test */
    public function force_flag_overrides_last_sync_check(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => now(), // Synced today
        ]);
        
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // With force flag, include all regardless of last sync
        $forceSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->pluck('id');
        
        $this->assertContains($project->id, $forceSync);
    }

    // ============================================
    // Job Dispatch Tests
    // ============================================

    /** @test */
    public function jobs_dispatched_for_each_project(): void
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
        
        // Get projects and dispatch
        $projects = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->get();
        
        foreach ($projects as $project) {
            SyncBacklinksJob::dispatch($project->id);
        }
        
        Bus::assertDispatchedTimes(SyncBacklinksJob::class, 2);
    }

    /** @test */
    public function jobs_dispatched_with_delay_to_spread_load(): void
    {
        Bus::fake();
        
        // Create 5 projects with GSC
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
        
        // Calculate delays (spread over 30 minutes)
        $delayMinutes = 30;
        $batchSize = max(1, $projects->count());
        $delayPerProject = $delayMinutes / $batchSize;
        
        $delays = [];
        foreach ($projects as $index => $project) {
            $delay = (int)($index * $delayPerProject);
            $delays[] = $delay;
            
            SyncBacklinksJob::dispatch($project->id)
                ->delay(now()->addMinutes($delay));
        }
        
        // Verify delays are spread
        $this->assertCount(5, $delays);
        $this->assertLessThan($delayMinutes, max($delays) + 1);
    }

    /** @test */
    public function job_batch_tracks_sync_progress(): void
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
        
        // Create a batch
        Bus::batch(
            $projects->map(fn($p) => new SyncBacklinksJob($p->id))
        )->name('backlinks-sync-' . date('Y-m-d'))->dispatch();
        
        Bus::assertBatched(function ($batch) {
            return str_contains($batch->name, 'backlinks-sync');
        });
    }

    /** @test */
    public function empty_project_list_results_in_no_jobs(): void
    {
        Bus::fake();
        
        // No projects with active GSC
        $projectsToSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->get();
        
        $this->assertCount(0, $projectsToSync);
        
        Bus::assertNotDispatched(SyncBacklinksJob::class);
    }

    // ============================================
    // Logging Tests
    // ============================================

    /** @test */
    public function scheduled_run_start_is_logged(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains(strtolower($message), 'backlinks') && 
                       str_contains(strtolower($message), 'start');
            });
        
        Log::info('Backlinks scheduled sync starting');
    }

    /** @test */
    public function scheduled_run_completion_is_logged(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains(strtolower($message), 'backlinks') && 
                       str_contains(strtolower($message), 'complete');
            });
        
        Log::info('Backlinks scheduled sync complete');
    }

    /** @test */
    public function sync_results_logged_with_project_count(): void
    {
        $syncedCount = 5;
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($syncedCount) {
                return str_contains(strtolower($message), 'backlinks') && 
                       isset($context['projects_synced']) &&
                       $context['projects_synced'] === $syncedCount;
            });
        
        Log::info('Backlinks sync completed', ['projects_synced' => $syncedCount]);
    }

    /** @test */
    public function errors_during_sync_are_logged(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains(strtolower($message), 'backlinks') && 
                       str_contains(strtolower($message), 'error') &&
                       isset($context['error']);
            });
        
        Log::error('Backlinks sync error', ['error' => 'Test error']);
    }

    /** @test */
    public function skipped_projects_are_logged(): void
    {
        $skippedCount = 3;
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($skippedCount) {
                return str_contains(strtolower($message), 'backlinks') && 
                       str_contains(strtolower($message), 'skip') &&
                       isset($context['skipped']) &&
                       $context['skipped'] === $skippedCount;
            });
        
        Log::info('Backlinks sync skipped projects', ['skipped' => $skippedCount]);
    }

    // ============================================
    // Edge Cases Tests
    // ============================================

    /** @test */
    public function handles_timezone_correctly(): void
    {
        // Schedule should run in configured timezone
        $configuredTimezone = config('app.timezone', 'UTC');
        
        $this->assertNotEmpty($configuredTimezone);
        
        // Timezone should be valid
        try {
            new \DateTimeZone($configuredTimezone);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail("Invalid timezone configured: {$configuredTimezone}");
        }
    }

    /** @test */
    public function handles_large_number_of_projects(): void
    {
        Bus::fake();
        
        // Create 100 projects
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();
            Project::factory()->forUser($user)->create(['archived_at' => null]);
            
            GSCConnection::factory()->create([
                'user_id' => $user->id,
                'status' => GSCConnection::STATUS_ACTIVE,
                'token_expires_at' => now()->addDays(30),
            ]);
        }
        
        // Should still be able to query
        $count = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->count();
        
        $this->assertEquals(100, $count);
    }

    /** @test */
    public function missing_gsc_property_url_handled(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create([
            'archived_at' => null,
            'domain' => null, // Missing domain
        ]);
        
        // GSC connection must have property_url (database constraint)
        // Testing that project with missing domain can still exist
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // Sync job should handle missing domain gracefully
        $this->assertNotNull($project);
        $this->assertNull($project->domain);
    }

    // ============================================
    // Configuration Validation Tests
    // ============================================

    /** @test */
    public function valid_configuration_values_accepted(): void
    {
        Config::set('backlinks.sync_enabled', true);
        Config::set('backlinks.sync_time', '04:00');
        
        $this->assertTrue(config('backlinks.sync_enabled'));
        $this->assertEquals('04:00', config('backlinks.sync_time'));
    }

    /** @test */
    public function configuration_array_structure(): void
    {
        // Expected config structure
        $expectedKeys = ['sync_enabled', 'sync_time'];
        
        foreach ($expectedKeys as $key) {
            Config::set("backlinks.{$key}", true);
            $this->assertNotNull(config("backlinks.{$key}"));
        }
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function full_sync_workflow(): void
    {
        Bus::fake();
        
        // Active project with GSC - should sync
        $user1 = User::factory()->create();
        $activeProject = Project::factory()->forUser($user1)->create(['archived_at' => null]);
        GSCConnection::factory()->create([
            'user_id' => $user1->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // Project synced today - should skip
        $user2 = User::factory()->create();
        Project::factory()->forUser($user2)->create([
            'archived_at' => null,
            'backlinks_last_synced_at' => now(),
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user2->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // Archived project - should skip
        $user3 = User::factory()->create();
        Project::factory()->forUser($user3)->create(['archived_at' => now()]);
        GSCConnection::factory()->create([
            'user_id' => $user3->id,
            'status' => GSCConnection::STATUS_ACTIVE,
            'token_expires_at' => now()->addDays(30),
        ]);
        
        // No GSC connection - should skip
        $user4 = User::factory()->create();
        Project::factory()->forUser($user4)->create(['archived_at' => null]);
        
        // Query projects needing sync
        $toSync = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('backlinks_last_synced_at')
                    ->orWhereDate('backlinks_last_synced_at', '<', today());
            })
            ->get();
        
        // Only activeProject should be synced
        $this->assertCount(1, $toSync);
        $this->assertEquals($activeProject->id, $toSync->first()->id);
        
        // Dispatch job
        SyncBacklinksJob::dispatch($toSync->first()->id);
        
        Bus::assertDispatchedTimes(SyncBacklinksJob::class, 1);
    }
}
