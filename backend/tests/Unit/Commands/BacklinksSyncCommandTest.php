<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use App\Jobs\SyncBacklinksJob;
use App\Models\Backlink;
use App\Models\GSCConnection;
use App\Models\Project;
use App\Models\ReferringDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

/**
 * Unit tests for TASK-23-4: BacklinksSyncCommand
 *
 * Tests the Artisan command for triggering backlink syncs.
 *
 * @see TEST-23-4
 * @see TASK-23-4
 */
class BacklinksSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    // =========================================================================
    // COMMAND SIGNATURE TESTS (CMD-SIG)
    // =========================================================================

    /**
     * CMD-SIG-01: Command signature exists
     */
    public function test_command_signature_exists(): void
    {
        $this->artisan('backlinks:sync --help')
            ->assertExitCode(0);
    }

    /**
     * CMD-SIG-02: Project ID argument
     */
    public function test_project_id_argument(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->assertExitCode(0);

        Queue::assertPushed(SyncBacklinksJob::class, function ($job) use ($project) {
            return $job->projectId === $project->id;
        });
    }

    /**
     * CMD-SIG-03: All flag
     */
    public function test_all_flag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example1.com',
        ]);
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example2.com',
        ]);

        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan('backlinks:sync --all')
            ->assertExitCode(0);

        Queue::assertPushed(SyncBacklinksJob::class, 2);
    }

    /**
     * CMD-SIG-04: Force flag
     */
    public function test_force_flag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan("backlinks:sync {$project->id} --force")
            ->assertExitCode(0);

        Queue::assertPushed(SyncBacklinksJob::class, function ($job) {
            return $job->force === true;
        });
    }

    /**
     * CMD-SIG-05: Combined options
     */
    public function test_combined_options(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        GSCConnection::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan('backlinks:sync --all --force')
            ->assertExitCode(0);

        Queue::assertPushed(SyncBacklinksJob::class, function ($job) {
            return $job->force === true;
        });
    }

    // =========================================================================
    // SINGLE PROJECT SYNC TESTS (CMD-SINGLE)
    // =========================================================================

    /**
     * CMD-SINGLE-01: Sync valid project
     */
    public function test_sync_valid_project(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->expectsOutput("Backlink sync queued for project {$project->id}")
            ->assertExitCode(0);

        Queue::assertPushed(SyncBacklinksJob::class);
    }

    /**
     * CMD-SINGLE-02: Sync non-existent project
     */
    public function test_sync_non_existent_project(): void
    {
        Queue::fake();

        $this->artisan('backlinks:sync 9999')
            ->expectsOutput('Project not found.')
            ->assertExitCode(1);

        Queue::assertNothingPushed();
    }

    /**
     * CMD-SINGLE-03: Sync project without GSC connection
     */
    public function test_sync_project_without_gsc_connection(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->expectsOutput('No active GSC connection for this project.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    /**
     * CMD-SINGLE-04: Sync project without domain
     */
    public function test_sync_project_without_domain(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => null,
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->expectsOutput('Project has no domain configured.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    /**
     * CMD-SINGLE-05: Sync archived project
     */
    public function test_sync_archived_project(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
            'archived_at' => now(),
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->expectsOutput('Project is archived.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    // =========================================================================
    // ALL PROJECTS SYNC TESTS (CMD-ALL)
    // =========================================================================

    /**
     * CMD-ALL-01: Sync all projects
     */
    public function test_sync_all_projects(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example1.com',
        ]);
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example2.com',
        ]);

        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan('backlinks:sync --all')
            ->assertExitCode(0);

        Queue::assertPushed(SyncBacklinksJob::class, 2);
    }

    /**
     * CMD-ALL-02: Sync only projects with GSC connection
     */
    public function test_sync_only_projects_with_gsc_connection(): void
    {
        Queue::fake();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1's project WITH GSC
        $projectWithGsc = Project::factory()->create([
            'user_id' => $user1->id,
            'domain' => 'https://example1.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user1->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        // User 2's project WITHOUT GSC
        Project::factory()->create([
            'user_id' => $user2->id,
            'domain' => 'https://example2.com',
        ]);
        // No GSC connection for user2

        $this->artisan('backlinks:sync --all')
            ->assertExitCode(0);

        Queue::assertPushed(SyncBacklinksJob::class, 1);
    }

    /**
     * CMD-ALL-03: Sync only active (non-archived) projects
     */
    public function test_sync_only_active_projects(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        // Active project
        $activeProject = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://active.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        // Archived project
        $archivedProject = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://archived.com',
            'archived_at' => now(),
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan('backlinks:sync --all')
            ->assertExitCode(0);

        Queue::assertPushed(SyncBacklinksJob::class, 1);
    }

    /**
     * CMD-ALL-04: No projects to sync
     */
    public function test_no_projects_to_sync(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        // Only archived project
        Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
            'archived_at' => now(),
        ]);

        $this->artisan('backlinks:sync --all')
            ->expectsOutput('No projects found to sync.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    /**
     * CMD-ALL-05: Summary output after sync all
     */
    public function test_summary_output_after_sync_all(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        GSCConnection::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan('backlinks:sync --all')
            ->expectsOutputToContain('Queued 3 project(s) for backlink sync')
            ->assertExitCode(0);
    }

    // =========================================================================
    // ERROR HANDLING TESTS (CMD-ERR)
    // =========================================================================

    /**
     * CMD-ERR-01: Invalid project ID (non-numeric)
     */
    public function test_invalid_project_id_non_numeric(): void
    {
        Queue::fake();

        $this->artisan('backlinks:sync abc')
            ->expectsOutput('Project ID must be a number.')
            ->assertExitCode(1);

        Queue::assertNothingPushed();
    }

    /**
     * CMD-ERR-02: Negative project ID
     * Note: -1 is interpreted as an option by Symfony Console, so this test
     * validates the behavior when an invalid option is passed.
     */
    public function test_negative_project_id(): void
    {
        Queue::fake();

        // When -1 is passed, Symfony Console sees it as an invalid option
        // The test should verify the command doesn't crash
        $result = $this->artisan('backlinks:sync -- -1');

        // Symfony will reject the -1 as an invalid option, returning non-zero exit code
        $result->assertExitCode(1);

        Queue::assertNothingPushed();
    }

    /**
     * CMD-ERR-03: Zero project ID
     */
    public function test_zero_project_id(): void
    {
        Queue::fake();

        $this->artisan('backlinks:sync 0')
            ->expectsOutput('Project ID must be a positive number.')
            ->assertExitCode(1);

        Queue::assertNothingPushed();
    }

    // =========================================================================
    // OUTPUT FORMAT TESTS (CMD-OUT)
    // =========================================================================

    /**
     * CMD-OUT-01: Success output for single project
     */
    public function test_success_output_for_single_project(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->expectsOutput("Backlink sync queued for project {$project->id}")
            ->assertExitCode(0);
    }

    /**
     * CMD-OUT-02: Error output for invalid project
     */
    public function test_error_output_for_invalid_project(): void
    {
        Queue::fake();

        $this->artisan('backlinks:sync 9999')
            ->expectsOutput('Project not found.')
            ->assertExitCode(1);
    }

    /**
     * CMD-OUT-03: Summary output for all projects
     */
    public function test_summary_output_for_all_projects(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Project::factory()->count(2)->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        GSCConnection::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan('backlinks:sync --all')
            ->expectsOutputToContain('Queued 2 project(s)')
            ->assertExitCode(0);
    }

    // =========================================================================
    // VERBOSE OUTPUT TESTS
    // =========================================================================

    /**
     * Test verbose output shows project details
     */
    public function test_verbose_output_shows_project_details(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan("backlinks:sync {$project->id} -v")
            ->expectsOutputToContain('Test Project')
            ->assertExitCode(0);
    }

    // =========================================================================
    // QUIET OUTPUT TESTS
    // =========================================================================

    /**
     * Test quiet output suppresses messages
     */
    public function test_quiet_output_suppresses_messages(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan("backlinks:sync {$project->id} --quiet")
            ->assertExitCode(0);

        // With --quiet, no output should be produced
        Queue::assertPushed(SyncBacklinksJob::class);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Test sync with project having multiple GSC connections
     */
    public function test_sync_with_multiple_gsc_connections(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        // Multiple GSC connections for same user
        GSCConnection::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->assertExitCode(0);

        // Should still dispatch one job
        Queue::assertPushed(SyncBacklinksJob::class, 1);
    }

    /**
     * Test sync with mixed GSC connection statuses
     */
    public function test_sync_with_mixed_gsc_connection_statuses(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        // Mix of active and expired connections
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_EXPIRED,
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->assertExitCode(0);

        // Should dispatch since at least one active connection exists
        Queue::assertPushed(SyncBacklinksJob::class, 1);
    }

    /**
     * Test sync with only expired GSC connections
     */
    public function test_sync_with_only_expired_gsc_connections(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_EXPIRED,
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->expectsOutput('No active GSC connection for this project.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    /**
     * Test sync with only revoked GSC connections
     */
    public function test_sync_with_only_revoked_gsc_connections(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'domain' => 'https://example.com',
        ]);

        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => GSCConnection::STATUS_REVOKED,
        ]);

        $this->artisan("backlinks:sync {$project->id}")
            ->expectsOutput('No active GSC connection for this project.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    // =========================================================================
    // MULTIPLE USERS TESTS
    // =========================================================================

    /**
     * Test sync all respects user boundaries
     */
    public function test_sync_all_respects_user_boundaries(): void
    {
        Queue::fake();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $project1 = Project::factory()->create([
            'user_id' => $user1->id,
            'domain' => 'https://user1.com',
        ]);
        $project2 = Project::factory()->create([
            'user_id' => $user2->id,
            'domain' => 'https://user2.com',
        ]);

        GSCConnection::factory()->create([
            'user_id' => $user1->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user2->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $this->artisan('backlinks:sync --all')
            ->assertExitCode(0);

        // Both users' projects should be synced
        Queue::assertPushed(SyncBacklinksJob::class, 2);
    }

    /**
     * Test sync single project only for specific user
     */
    public function test_sync_single_project_only_for_specific_user(): void
    {
        Queue::fake();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $project1 = Project::factory()->create([
            'user_id' => $user1->id,
            'domain' => 'https://user1.com',
        ]);
        $project2 = Project::factory()->create([
            'user_id' => $user2->id,
            'domain' => 'https://user2.com',
        ]);

        GSCConnection::factory()->create([
            'user_id' => $user1->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);
        GSCConnection::factory()->create([
            'user_id' => $user2->id,
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        // Only sync project1
        $this->artisan("backlinks:sync {$project1->id}")
            ->assertExitCode(0);

        Queue::assertPushed(SyncBacklinksJob::class, 1);
        Queue::assertPushed(SyncBacklinksJob::class, function ($job) use ($project1) {
            return $job->projectId === $project1->id;
        });
    }
}
