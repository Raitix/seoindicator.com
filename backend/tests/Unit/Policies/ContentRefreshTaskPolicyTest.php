<?php

namespace Tests\Unit\Policies;

use App\Policies\ContentRefreshTaskPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group content-decay
 * @group policy
 */
class ContentRefreshTaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ContentRefreshTaskPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ContentRefreshTaskPolicy();
    }

    /**
     * Create a mock task object with nested candidate and project
     */
    protected function createMockTask(int $userId, int $candidateId = 1): object
    {
        return (object) [
            'id' => 1,
            'candidate_id' => $candidateId,
            'title' => 'Test task',
            'status' => 'todo',
            'candidate' => (object) [
                'id' => $candidateId,
                'project_id' => 1,
                'project' => (object) [
                    'id' => 1,
                    'user_id' => $userId,
                    'site_id' => 1,
                ],
            ],
        ];
    }

    // ==========================================
    // VIEW POLICY TESTS
    // ==========================================

    /** @test */
    public function view_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $task = $this->createMockTask($user->id);

        $result = $this->policy->view($user, $task);

        $this->assertTrue($result);
    }

    /** @test */
    public function view_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $task = $this->createMockTask($owner->id);

        $result = $this->policy->view($nonOwner, $task);

        $this->assertFalse($result);
    }

    /** @test */
    public function view_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $task = $this->createMockTask($owner->id);

        $result = $this->policy->view($admin, $task);

        $this->assertTrue($result);
    }

    // ==========================================
    // UPDATE POLICY TESTS
    // ==========================================

    /** @test */
    public function update_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $task = $this->createMockTask($user->id);

        $result = $this->policy->update($user, $task);

        $this->assertTrue($result);
    }

    /** @test */
    public function update_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $task = $this->createMockTask($owner->id);

        $result = $this->policy->update($nonOwner, $task);

        $this->assertFalse($result);
    }

    /** @test */
    public function update_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $task = $this->createMockTask($owner->id);

        $result = $this->policy->update($admin, $task);

        $this->assertTrue($result);
    }

    // ==========================================
    // DELETE POLICY TESTS (IF APPLICABLE)
    // ==========================================

    /** @test */
    public function delete_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $task = $this->createMockTask($user->id);

        $result = $this->policy->delete($user, $task);

        $this->assertTrue($result);
    }

    /** @test */
    public function delete_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $task = $this->createMockTask($owner->id);

        $result = $this->policy->delete($nonOwner, $task);

        $this->assertFalse($result);
    }

    /** @test */
    public function delete_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $task = $this->createMockTask($owner->id);

        $result = $this->policy->delete($admin, $task);

        $this->assertTrue($result);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function all_actions_denied_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $task = $this->createMockTask($owner->id);

        $this->assertFalse($this->policy->view($nonOwner, $task));
        $this->assertFalse($this->policy->update($nonOwner, $task));
        $this->assertFalse($this->policy->delete($nonOwner, $task));
    }

    /** @test */
    public function all_actions_allowed_for_owner(): void
    {
        $user = User::factory()->create();
        $task = $this->createMockTask($user->id);

        $this->assertTrue($this->policy->view($user, $task));
        $this->assertTrue($this->policy->update($user, $task));
        $this->assertTrue($this->policy->delete($user, $task));
    }

    /** @test */
    public function all_actions_allowed_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $task = $this->createMockTask($owner->id);

        $this->assertTrue($this->policy->view($admin, $task));
        $this->assertTrue($this->policy->update($admin, $task));
        $this->assertTrue($this->policy->delete($admin, $task));
    }

    /** @test */
    public function policy_checks_user_id_through_nested_relationships(): void
    {
        $owner = User::factory()->create();
        $differentUser = User::factory()->create();

        $task = $this->createMockTask($owner->id);

        // Owner can access
        $this->assertTrue($this->policy->view($owner, $task));

        // Different user cannot
        $this->assertFalse($this->policy->view($differentUser, $task));
    }

    /** @test */
    public function policy_works_with_different_task_statuses(): void
    {
        $user = User::factory()->create();

        // Task with todo status
        $todoTask = $this->createMockTask($user->id);
        $todoTask->status = 'todo';
        $this->assertTrue($this->policy->update($user, $todoTask));

        // Task with done status
        $doneTask = $this->createMockTask($user->id);
        $doneTask->status = 'done';
        $this->assertTrue($this->policy->view($user, $doneTask));
    }

    /** @test */
    public function policy_works_with_assigned_owner(): void
    {
        $projectOwner = User::factory()->create();
        $taskOwner = User::factory()->create();

        $task = $this->createMockTask($projectOwner->id);
        $task->owner_user_id = $taskOwner->id;

        // Project owner can still access
        $this->assertTrue($this->policy->view($projectOwner, $task));
        $this->assertTrue($this->policy->update($projectOwner, $task));

        // Task owner (different from project owner) should not have access
        // unless they're the same as project owner
        $this->assertFalse($this->policy->view($taskOwner, $task));
    }
}
