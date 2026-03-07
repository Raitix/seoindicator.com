<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\AIVisibilityProjectPolicy;
use Tests\TestCase;

/**
 * AIVisibilityProjectPolicy Test - TASK-34-6
 * 
 * Tests for authorization policy controlling access to AI Visibility projects.
 * 
 * @see TESTS/TEST-34-6.md for full test documentation
 */
class AIVisibilityProjectPolicyTest extends TestCase
{
    protected AIVisibilityProjectPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AIVisibilityProjectPolicy();
    }

    // =========================================================================
    // View Policy Tests
    // =========================================================================

    /**
     * Test user can view their own project.
     */
    public function test_user_can_view_own_project(): void
    {
        $user = User::factory()->create();
        $project = $this->mockProject(['user_id' => $user->id]);

        $result = $this->policy->view($user, $project);

        $this->assertTrue($result);
    }

    /**
     * Test user cannot view another user's project.
     */
    public function test_user_cannot_view_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->mockProject(['user_id' => $otherUser->id]);

        $result = $this->policy->view($user, $project);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Update Policy Tests
    // =========================================================================

    /**
     * Test user can update their own project.
     */
    public function test_user_can_update_own_project(): void
    {
        $user = User::factory()->create();
        $project = $this->mockProject(['user_id' => $user->id]);

        $result = $this->policy->update($user, $project);

        $this->assertTrue($result);
    }

    /**
     * Test user cannot update another user's project.
     */
    public function test_user_cannot_update_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->mockProject(['user_id' => $otherUser->id]);

        $result = $this->policy->update($user, $project);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Delete Policy Tests
    // =========================================================================

    /**
     * Test user can delete their own project.
     */
    public function test_user_can_delete_own_project(): void
    {
        $user = User::factory()->create();
        $project = $this->mockProject(['user_id' => $user->id]);

        $result = $this->policy->delete($user, $project);

        $this->assertTrue($result);
    }

    /**
     * Test user cannot delete another user's project.
     */
    public function test_user_cannot_delete_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->mockProject(['user_id' => $otherUser->id]);

        $result = $this->policy->delete($user, $project);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Run Policy Tests
    // =========================================================================

    /**
     * Test user can run queries on their own project.
     */
    public function test_user_can_run_own_project(): void
    {
        $user = User::factory()->create();
        $project = $this->mockProject(['user_id' => $user->id]);

        $result = $this->policy->run($user, $project);

        $this->assertTrue($result);
    }

    /**
     * Test user cannot run queries on another user's project.
     */
    public function test_user_cannot_run_another_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->mockProject(['user_id' => $otherUser->id]);

        $result = $this->policy->run($user, $project);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Policy Method Existence Tests
    // =========================================================================

    /**
     * Test policy has view method.
     */
    public function test_policy_has_view_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'view'));
    }

    /**
     * Test policy has update method.
     */
    public function test_policy_has_update_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'update'));
    }

    /**
     * Test policy has delete method.
     */
    public function test_policy_has_delete_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'delete'));
    }

    /**
     * Test policy has run method.
     */
    public function test_policy_has_run_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'run'));
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * Test policy with same user object.
     */
    public function test_policy_with_same_user_object(): void
    {
        $user = User::factory()->create();
        $project = $this->mockProject(['user_id' => $user->id]);

        $this->assertTrue($this->policy->view($user, $project));
        $this->assertTrue($this->policy->update($user, $project));
        $this->assertTrue($this->policy->delete($user, $project));
        $this->assertTrue($this->policy->run($user, $project));
    }

    /**
     * Test policy with different user objects same ID.
     */
    public function test_policy_with_different_user_objects_same_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::find($user1->id); // Same user, different object

        $project = $this->mockProject(['user_id' => $user1->id]);

        $this->assertTrue($this->policy->view($user2, $project));
    }

    /**
     * Test policy denies all actions for non-owner.
     */
    public function test_policy_denies_all_actions_for_non_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = $this->mockProject(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->view($user, $project));
        $this->assertFalse($this->policy->update($user, $project));
        $this->assertFalse($this->policy->delete($user, $project));
        $this->assertFalse($this->policy->run($user, $project));
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a mock project object.
     */
    protected function mockProject(array $attributes = []): object
    {
        return new class($attributes) {
            public int $user_id;

            public function __construct(array $attributes = [])
            {
                $this->user_id = $attributes['user_id'] ?? 1;
            }
        };
    }
}
