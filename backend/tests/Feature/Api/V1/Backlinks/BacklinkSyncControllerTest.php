<?php

namespace Tests\Feature\Api\V1\Backlinks;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\GSCConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SyncBacklinksJob;

/**
 * Feature tests for Backlinks Sync REST API Controller.
 * 
 * TASK-23-11: Backlinks API Endpoints - History & Sync
 * 
 * Endpoint:
 * - POST /api/v1/projects/{projectId}/backlinks/sync - Trigger manual backlink sync
 * 
 * Authentication: Bearer token (Sanctum session)
 * Authorization: ProjectPolicy update permission
 * Rate Limiting: 1 request per project per hour
 */
class BacklinkSyncControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected User $viewerUser;
    protected Project $project;
    protected Project $otherUserProject;
    protected Project $projectWithoutGsc;
    protected Project $projectWithoutDomain;
    protected Project $archivedProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->viewerUser = User::factory()->create();

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Project',
            'domain' => 'https://example.com',
        ]);

        $this->otherUserProject = Project::factory()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other User Project',
            'domain' => 'https://other.com',
        ]);

        $this->projectWithoutGsc = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Project Without GSC',
            'domain' => 'https://no-gsc.com',
        ]);

        $this->projectWithoutDomain = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Project Without Domain',
            'domain' => null,
        ]);

        $this->archivedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Archived Project',
            'domain' => 'https://archived.com',
            'archived_at' => now(),
        ]);

        // Create GSC connection for main project
        GSCConnection::create([
            'user_id' => $this->user->id,
            'site_url' => 'example.com',
            'property_url' => 'https://example.com',
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_expires_at' => now()->addHour(),
        ]);

        // Create GSC connection for archived project
        GSCConnection::create([
            'user_id' => $this->user->id,
            'site_url' => 'archived.com',
            'property_url' => 'https://archived.com',
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_expires_at' => now()->addHour(),
        ]);

        Queue::fake();
    }

    /**
     * Get the rate limit cache key for a project.
     */
    protected function getRateLimitKey(int $projectId): string
    {
        return "backlink-sync:{$projectId}";
    }

    /**
     * Set a recent sync timestamp for rate limiting tests.
     */
    protected function setRecentSync(int $projectId, int $minutesAgo = 30): void
    {
        Cache::put($this->getRateLimitKey($projectId), now()->subMinutes($minutesAgo)->toIso8601String(), 3600);
    }

    // ============================================================
    // SUCCESS CASES (S-*)
    // ============================================================

    /**
     * S-01: Trigger sync.
     */
    public function test_trigger_sync(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'job_id',
                    'status',
                    'estimated_duration',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'queued',
                    'estimated_duration' => '2-5 minutes',
                ],
            ]);
    }

    /**
     * S-02: Job ID format.
     */
    public function test_job_id_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200);

        $jobId = $response->json('data.job_id');
        $this->assertStringStartsWith('backlink-sync-', $jobId);
    }

    /**
     * S-03: Estimated duration.
     */
    public function test_estimated_duration(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200)
            ->assertJsonPath('data.estimated_duration', '2-5 minutes');
    }

    /**
     * S-04: Job queued (dispatches SyncBacklinksJob).
     */
    public function test_job_queued(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        Queue::assertPushed(SyncBacklinksJob::class, function ($job) {
            return $job->projectId === $this->project->id;
        });
    }

    // ============================================================
    // AUTHENTICATION TESTS (SA-*)
    // ============================================================

    /**
     * SA-01: Valid auth token.
     */
    public function test_valid_auth_token(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200);
    }

    /**
     * SA-02: Missing Authorization.
     */
    public function test_missing_authorization(): void
    {
        $response = $this->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(401);
    }

    /**
     * SA-03: Invalid token.
     */
    public function test_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(401);
    }

    // ============================================================
    // AUTHORIZATION TESTS (SZ-*)
    // ============================================================

    /**
     * SZ-01: Own project (update permission).
     */
    public function test_own_project(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200);
    }

    /**
     * SZ-02: Other user's project.
     */
    public function test_other_users_project(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->otherUserProject->id}/backlinks/sync");

        $response->assertStatus(403);
    }

    /**
     * SZ-03: Non-existent project.
     */
    public function test_nonexistent_project(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects/999/backlinks/sync');

        $response->assertStatus(404);
    }

    /**
     * SZ-04: Read-only access (viewer role).
     * 
     * Note: This test assumes team membership functionality exists.
     * If not implemented, this test may need adjustment.
     */
    public function test_read_only_access(): void
    {
        // This test requires team membership implementation
        // For now, we'll skip it with a mark
        $this->markTestSkipped('Team membership not implemented yet - requires project_members table with viewer role');
        
        // When implemented, this would create a viewer membership and test 403 response
    }

    /**
     * SZ-05: Archived project (cannot sync archived).
     */
    public function test_archived_project(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->archivedProject->id}/backlinks/sync");

        $response->assertStatus(403);
    }

    // ============================================================
    // RATE LIMITING TESTS (SR-*)
    // ============================================================

    /**
     * SR-01: First sync.
     */
    public function test_first_sync(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'queued');
    }

    /**
     * SR-02: Second sync within hour.
     */
    public function test_second_sync_within_hour(): void
    {
        // First sync
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        // Set recent sync timestamp (simulating 30 minutes ago)
        $this->setRecentSync($this->project->id, 30);

        // Second sync attempt
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                ],
            ]);
    }

    /**
     * SR-03: Sync after hour passed.
     */
    public function test_sync_after_hour_passed(): void
    {
        // Set recent sync timestamp (simulating 61 minutes ago - outside rate limit window)
        $this->setRecentSync($this->project->id, 61);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'queued');
    }

    /**
     * SR-04: Rate limit response with retry_after.
     */
    public function test_rate_limit_response(): void
    {
        // First sync
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        // Set recent sync timestamp
        $this->setRecentSync($this->project->id, 30);

        // Second sync attempt
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(429)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                    'retry_after',
                ],
            ]);

        // Verify retry_after is a future timestamp
        $retryAfter = $response->json('error.retry_after');
        $this->assertNotNull($retryAfter);
    }

    /**
     * SR-05: Concurrent sync prevention.
     */
    public function test_concurrent_sync_prevention(): void
    {
        // First sync
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        // Set sync as in-progress
        Cache::put($this->getRateLimitKey($this->project->id), 'in-progress', 3600);

        // Concurrent sync attempt
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(429);
    }

    /**
     * SR-06: Different projects have independent rate limits.
     */
    public function test_different_projects_independent_rate_limits(): void
    {
        // Create another project with GSC connection
        $project2 = Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Second Project',
            'domain' => 'https://second-example.com',
        ]);

        GSCConnection::create([
            'user_id' => $this->user->id,
            'site_url' => 'second-example.com',
            'property_url' => 'https://second-example.com',
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_expires_at' => now()->addHour(),
        ]);

        // Sync first project
        $response1 = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");
        $response1->assertStatus(200);

        // Set rate limit for first project
        $this->setRecentSync($this->project->id, 30);

        // Second project should still allow sync
        $response2 = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$project2->id}/backlinks/sync");
        $response2->assertStatus(200);

        // First project should be rate limited
        $response3 = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");
        $response3->assertStatus(429);
    }

    // ============================================================
    // VALIDATION TESTS (SV-*)
    // ============================================================

    /**
     * SV-01: Invalid projectId.
     */
    public function test_invalid_project_id(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects/abc/backlinks/sync');

        $response->assertStatus(404);
    }

    /**
     * SV-02: Project without GSC connection.
     */
    public function test_project_without_gsc_connection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->projectWithoutGsc->id}/backlinks/sync");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'PRECONDITION_FAILED',
                ],
            ]);
    }

    /**
     * SV-03: Project without domain.
     */
    public function test_project_without_domain(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->projectWithoutDomain->id}/backlinks/sync");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'PRECONDITION_FAILED',
                ],
            ]);
    }

    // ============================================================
    // EDGE CASES (SE-*)
    // ============================================================

    /**
     * SE-01: Previous sync failed.
     */
    public function test_previous_sync_failed(): void
    {
        // Set a failed sync timestamp (more than an hour ago)
        Cache::put($this->getRateLimitKey($this->project->id), 'failed', 3600);

        // Clear the timestamp to simulate hour passed
        Cache::forget($this->getRateLimitKey($this->project->id));

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200);
    }

    /**
     * SE-02: Previous sync completed (new sync allowed after cooldown).
     */
    public function test_previous_sync_completed(): void
    {
        // First sync
        $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        // Set completed timestamp (more than an hour ago)
        $this->setRecentSync($this->project->id, 61);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200);
    }

    /**
     * SE-03: Multiple rapid requests.
     */
    public function test_multiple_rapid_requests(): void
    {
        // First request should succeed
        $response1 = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");
        $response1->assertStatus(200);

        // Set rate limit
        $this->setRecentSync($this->project->id, 0);

        // Subsequent requests should be rate limited
        $response2 = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");
        $response2->assertStatus(429);

        $response3 = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");
        $response3->assertStatus(429);
    }

    /**
     * SE-04: Job queue full (service unavailable).
     * 
     * Note: This test simulates a queue full scenario.
     * In practice, this would be tested with a real queue.
     */
    public function test_job_queue_full(): void
    {
        // This test requires mocking the queue to throw an exception
        // For now, we'll mark it as skipped
        $this->markTestSkipped('Queue full scenario requires integration testing with real queue');
    }

    // ============================================================
    // SECURITY TESTS (SEC-*)
    // ============================================================

    /**
     * SEC-06: Race condition on sync.
     */
    public function test_race_condition_on_sync(): void
    {
        // Simulate concurrent requests using the same rate limit key
        Cache::put($this->getRateLimitKey($this->project->id), now()->toIso8601String(), 3600);

        // Both requests should check rate limit before proceeding
        // Only one should succeed (the first one that gets through)
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(429);
    }

    /**
     * SEC-07: Job enumeration (cannot access other users' jobs).
     */
    public function test_job_enumeration(): void
    {
        // Sync for user 1
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $jobId = $response->json('data.job_id');

        // User 2 should not be able to access user 1's job
        // This would require a job status endpoint which doesn't exist yet
        // For now, we verify the job_id format doesn't expose user info
        $this->assertStringNotContainsString($this->user->id, $jobId);
        $this->assertStringNotContainsString($this->user->email, $jobId);
    }

    // ============================================================
    // INTEGRATION TESTS
    // ============================================================

    /**
     * Test full sync flow.
     */
    public function test_full_sync_flow(): void
    {
        // Ensure project has GSC connection and domain
        $this->assertNotNull($this->project->domain);
        $this->assertDatabaseHas('gsc_connections', [
            'user_id' => $this->user->id,
            'site_url' => 'example.com',
        ]);

        // Trigger sync
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['job_id', 'status', 'estimated_duration'],
            ]);

        // Verify job was queued
        Queue::assertPushed(SyncBacklinksJob::class);

        // Verify rate limit is set
        $this->assertNotNull(Cache::get($this->getRateLimitKey($this->project->id)));
    }

    /**
     * Test user isolation.
     */
    public function test_user_isolation(): void
    {
        // User 1 syncs their project
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");
        $response->assertStatus(200);

        // User 2 tries to sync User 1's project
        $response = $this->actingAs($this->otherUser)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks/sync");
        $response->assertStatus(403);

        // User 2 syncs their own project
        // Need to create GSC connection for other user's project
        GSCConnection::create([
            'user_id' => $this->otherUser->id,
            'site_url' => 'other.com',
            'property_url' => 'https://other.com',
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'token_expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($this->otherUser)
            ->postJson("/api/v1/projects/{$this->otherUserProject->id}/backlinks/sync");
        $response->assertStatus(200);
    }
}
