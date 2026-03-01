<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use App\Models\User;
use App\Models\CrawlJob;
use App\Policies\CrawlJobPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Unit tests for CrawlJobPolicy authorization.
 *
 * Tests authorization rules for crawl job operations:
 * - View: Owner or admin only
 * - Update: Owner with queued status only
 * - Delete/Cancel: Owner with queued/running status
 * - Create: Within rate limits
 *
 * @see TASK-22-12
 * @see \App\Policies\CrawlJobPolicy
 */
class CrawlJobPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CrawlJobPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CrawlJobPolicy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a mock user.
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Create a mock admin user.
     */
    protected function createAdmin(): User
    {
        return $this->createUser(['role' => 'admin']);
    }

    /**
     * Create a mock crawl job.
     */
    protected function createCrawlJob(array $attributes = []): CrawlJob
    {
        return CrawlJob::factory()->make($attributes);
    }

    // =========================================================================
    // View Job Tests (POLICY-VIEW-*)
    // =========================================================================

    /** @test */
    public function policy_view_01_owner_views_own_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id]);

        $result = $this->policy->view($user, $job);

        $this->assertTrue($result);
    }

    /** @test */
    public function policy_view_02_user_views_others_job(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user2->id]);

        $result = $this->policy->view($user1, $job);

        $this->assertFalse($result);
    }

    /** @test */
    public function policy_view_03_admin_views_any_job(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id]);

        $result = $this->policy->view($admin, $job);

        $this->assertTrue($result);
    }

    /** @test */
    public function policy_view_04_guest_cannot_view_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id]);

        // Guest = unauthenticated user (null)
        $result = $this->policy->view(null, $job);

        $this->assertFalse($result);
    }

    /** @test */
    public function policy_view_05_job_status_does_not_affect_view(): void
    {
        $user = $this->createUser();

        foreach (['queued', 'running', 'completed', 'failed', 'cancelled'] as $status) {
            $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => $status]);
            $result = $this->policy->view($user, $job);
            $this->assertTrue($result, "Owner should be able to view {$status} job");
        }
    }

    // =========================================================================
    // Update Job Tests (POLICY-UPDATE-*)
    // =========================================================================

    /** @test */
    public function policy_update_01_update_queued_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'queued']);

        $result = $this->policy->update($user, $job);

        $this->assertTrue($result);
    }

    /** @test */
    public function policy_update_02_update_running_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'running']);

        $result = $this->policy->update($user, $job);

        $this->assertFalse($result, 'Cannot update running job');
    }

    /** @test */
    public function policy_update_03_update_completed_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'completed']);

        $result = $this->policy->update($user, $job);

        $this->assertFalse($result, 'Cannot update completed job');
    }

    /** @test */
    public function policy_update_04_update_failed_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'failed']);

        $result = $this->policy->update($user, $job);

        $this->assertFalse($result, 'Cannot update failed job');
    }

    /** @test */
    public function policy_update_05_update_cancelled_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'cancelled']);

        $result = $this->policy->update($user, $job);

        $this->assertFalse($result, 'Cannot update cancelled job');
    }

    /** @test */
    public function policy_update_06_admin_updates_any(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'queued']);

        $result = $this->policy->update($admin, $job);

        $this->assertTrue($result);
    }

    /** @test */
    public function policy_update_07_non_owner_cannot_update(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user2->id, 'status' => 'queued']);

        $result = $this->policy->update($user1, $job);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Delete/Cancel Job Tests (POLICY-DELETE-*)
    // =========================================================================

    /** @test */
    public function policy_delete_01_cancel_queued_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'queued']);

        $result = $this->policy->delete($user, $job);

        $this->assertTrue($result);
    }

    /** @test */
    public function policy_delete_02_cancel_running_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'running']);

        $result = $this->policy->delete($user, $job);

        $this->assertTrue($result, 'Owner can cancel running job');
    }

    /** @test */
    public function policy_delete_03_delete_completed_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'completed']);

        $result = $this->policy->delete($user, $job);

        $this->assertTrue($result, 'Owner can delete completed job');
    }

    /** @test */
    public function policy_delete_04_delete_failed_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'failed']);

        $result = $this->policy->delete($user, $job);

        $this->assertTrue($result, 'Owner can delete failed job');
    }

    /** @test */
    public function policy_delete_05_delete_cancelled_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'cancelled']);

        $result = $this->policy->delete($user, $job);

        $this->assertTrue($result, 'Owner can delete cancelled job');
    }

    /** @test */
    public function policy_delete_06_cancel_others_job(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user2->id, 'status' => 'queued']);

        $result = $this->policy->delete($user1, $job);

        $this->assertFalse($result);
    }

    /** @test */
    public function policy_delete_07_admin_cancels_any(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id, 'status' => 'running']);

        $result = $this->policy->delete($admin, $job);

        $this->assertTrue($result);
    }

    // =========================================================================
    // Create Job Tests (POLICY-CREATE-*)
    // =========================================================================

    /** @test */
    public function policy_create_01_within_daily_limit(): void
    {
        $user = $this->createUser();

        // Mock rate limiter or use actual implementation
        // For unit test, we assume the policy checks rate limits
        $result = $this->policy->create($user);

        // Default: should be true when no jobs created
        $this->assertTrue($result);
    }

    /** @test */
    public function policy_create_02_admin_bypasses_limits(): void
    {
        $admin = $this->createAdmin();

        $result = $this->policy->create($admin);

        $this->assertTrue($result, 'Admin should bypass rate limits');
    }

    // =========================================================================
    // Edge Cases (POLICY-EDGE-*)
    // =========================================================================

    /** @test */
    public function policy_edge_01_null_user_handling(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id]);

        $viewResult = $this->policy->view(null, $job);
        $updateResult = $this->policy->update(null, $job);
        $deleteResult = $this->policy->delete(null, $job);
        $createResult = $this->policy->create(null);

        $this->assertFalse($viewResult);
        $this->assertFalse($updateResult);
        $this->assertFalse($deleteResult);
        $this->assertFalse($createResult);
    }

    /** @test */
    public function policy_edge_02_job_without_owner(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => null]);

        $result = $this->policy->view($user, $job);

        // Should deny access to job without owner
        $this->assertFalse($result);
    }

    /** @test */
    public function policy_edge_03_admin_with_non_admin_flag(): void
    {
        $user = $this->createUser(['role' => 'user']);
        $otherUser = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $otherUser->id]);

        $result = $this->policy->view($user, $job);

        $this->assertFalse($result, 'Non-admin cannot view other user job');
    }

    /** @test */
    public function policy_edge_04_soft_deleted_job(): void
    {
        $user = $this->createUser();
        $job = $this->createCrawlJob(['user_id' => $user->id]);
        // Simulate soft delete
        $job->deleted_at = now();

        $result = $this->policy->view($user, $job);

        // Policy should handle soft deleted jobs appropriately
        // Implementation decision: deny or allow based on requirements
        $this->assertFalse($result, 'Cannot view soft deleted job');
    }
}
