<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for ProjectPolicy authorization.
 *
 * @see TEST-8-6
 * @see TASK-8-6
 */
class ProjectPolicyTest extends TestCase
{
    private ProjectPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ProjectPolicy();
    }

    // ==========================================
    // Policy Method Existence Tests (PM-001 to PM-007)
    // ==========================================

    #[Test]
    public function policy_has_view_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'view'));
    }

    #[Test]
    public function policy_has_update_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'update'));
    }

    #[Test]
    public function policy_has_delete_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'delete'));
    }

    #[Test]
    public function policy_has_restore_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'restore'));
    }

    #[Test]
    public function policy_has_view_any_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'viewAny'));
    }

    #[Test]
    public function policy_has_create_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'create'));
    }

    #[Test]
    public function policy_has_before_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'before'));
    }

    // ==========================================
    // viewAny and create Tests (PM-016, PM-017)
    // ==========================================

    #[Test]
    public function view_any_returns_true_for_authenticated_user(): void
    {
        $user = User::factory()->make();

        $result = $this->policy->viewAny($user);

        $this->assertTrue($result);
    }

    #[Test]
    public function create_returns_true_for_authenticated_user(): void
    {
        $user = User::factory()->make();

        $result = $this->policy->create($user);

        $this->assertTrue($result);
    }

    // ==========================================
    // Owner Authorization Tests (PM-008, PM-010, PM-012, PM-014)
    // ==========================================

    #[Test]
    public function view_returns_true_for_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 1]);

        $result = $this->policy->view($user, $project);

        $this->assertTrue($result);
    }

    #[Test]
    public function update_returns_true_for_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 1]);

        $result = $this->policy->update($user, $project);

        $this->assertTrue($result);
    }

    #[Test]
    public function delete_returns_true_for_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 1]);

        $result = $this->policy->delete($user, $project);

        $this->assertTrue($result);
    }

    #[Test]
    public function restore_returns_true_for_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 1]);

        $result = $this->policy->restore($user, $project);

        $this->assertTrue($result);
    }

    // ==========================================
    // Non-Owner Authorization Tests (PM-009, PM-011, PM-013, PM-015)
    // ==========================================

    #[Test]
    public function view_returns_false_for_non_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 2]);

        $result = $this->policy->view($user, $project);

        $this->assertFalse($result);
    }

    #[Test]
    public function update_returns_false_for_non_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 2]);

        $result = $this->policy->update($user, $project);

        $this->assertFalse($result);
    }

    #[Test]
    public function delete_returns_false_for_non_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 2]);

        $result = $this->policy->delete($user, $project);

        $this->assertFalse($result);
    }

    #[Test]
    public function restore_returns_false_for_non_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 2]);

        $result = $this->policy->restore($user, $project);

        $this->assertFalse($result);
    }

    // ==========================================
    // Edge Case Tests (EC-001)
    // ==========================================

    #[Test]
    public function view_returns_false_for_project_with_null_user_id(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => null]);

        $result = $this->policy->view($user, $project);

        $this->assertFalse($result);
    }

    #[Test]
    public function update_returns_false_for_project_with_null_user_id(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => null]);

        $result = $this->policy->update($user, $project);

        $this->assertFalse($result);
    }

    // ==========================================
    // Role Extensibility Tests (RE-002, RE-003)
    // ==========================================

    #[Test]
    public function before_returns_true_for_admin_user(): void
    {
        $user = User::factory()->make(['id' => 1, 'role' => 'admin']);
        $project = Project::factory()->make(['user_id' => 2]);

        $result = $this->policy->before($user, 'view');

        $this->assertTrue($result);
    }

    #[Test]
    public function before_returns_null_for_non_admin_user(): void
    {
        $user = User::factory()->make(['id' => 1, 'role' => 'user']);
        $project = Project::factory()->make(['user_id' => 2]);

        $result = $this->policy->before($user, 'view');

        $this->assertNull($result);
    }

    #[Test]
    public function before_returns_null_for_user_without_role(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 2]);

        $result = $this->policy->before($user, 'view');

        $this->assertNull($result);
    }

    #[Test]
    public function admin_can_access_any_project(): void
    {
        $user = User::factory()->make(['id' => 1, 'role' => 'admin']);
        $project = Project::factory()->make(['user_id' => 2]);

        // The before() hook should return true for admin
        // So even though user is not owner, they can access
        $beforeResult = $this->policy->before($user, 'view');
        $viewResult = $this->policy->view($user, $project);

        // If before returns true, Laravel won't call the actual method
        // But we can verify both behaviors
        $this->assertTrue($beforeResult);
        $this->assertFalse($viewResult); // Actual method still enforces ownership
    }

    // ==========================================
    // Force Delete Tests
    // ==========================================

    #[Test]
    public function force_delete_returns_true_for_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 1]);

        $result = $this->policy->forceDelete($user, $project);

        $this->assertTrue($result);
    }

    #[Test]
    public function force_delete_returns_false_for_non_owner(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $project = Project::factory()->make(['user_id' => 2]);

        $result = $this->policy->forceDelete($user, $project);

        $this->assertFalse($result);
    }
}
