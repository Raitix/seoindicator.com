<?php

namespace Tests\Feature\Authorization;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for ProjectPolicy Gate integration.
 *
 * Tests the Laravel Gate authorization system integration with ProjectPolicy.
 *
 * @see TEST-8-6
 * @see TASK-8-6
 */
class ProjectAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Gate Integration Tests (GI-001 to GI-008)
    // ==========================================

    #[Test]
    public function gate_allows_view_for_owner(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('view', $project));
    }

    #[Test]
    public function gate_denies_view_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->forUser($owner)->create();

        $this->actingAs($otherUser);

        $this->assertTrue(Gate::denies('view', $project));
    }

    #[Test]
    public function gate_allows_update_for_owner(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('update', $project));
    }

    #[Test]
    public function gate_denies_update_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->forUser($owner)->create();

        $this->actingAs($otherUser);

        $this->assertTrue(Gate::denies('update', $project));
    }

    #[Test]
    public function gate_allows_delete_for_owner(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('delete', $project));
    }

    #[Test]
    public function gate_denies_delete_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->forUser($owner)->create();

        $this->actingAs($otherUser);

        $this->assertTrue(Gate::denies('delete', $project));
    }

    #[Test]
    public function gate_allows_restore_for_owner(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('restore', $project));
    }

    #[Test]
    public function gate_denies_restore_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->forUser($owner)->create();

        $this->actingAs($otherUser);

        $this->assertTrue(Gate::denies('restore', $project));
    }

    // ==========================================
    // Unauthenticated Gate Test (GI-009)
    // ==========================================

    #[Test]
    public function gate_check_returns_false_for_unauthenticated(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->forUser($owner)->create();

        // No actingAs - unauthenticated
        $this->assertFalse(Gate::check('view', $project));
    }

    // ==========================================
    // Policy Registration Test (GI-010)
    // ==========================================

    #[Test]
    public function policy_is_registered_for_project_model(): void
    {
        // Verify that ProjectPolicy is registered for Project model
        $policy = Gate::getPolicyFor(Project::class);

        $this->assertNotNull($policy);
        $this->assertInstanceOf(\App\Policies\ProjectPolicy::class, $policy);
    }

    // ==========================================
    // Archived Project Tests (AP-001 to AP-006)
    // ==========================================

    #[Test]
    public function owner_can_view_archived_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->archived()->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('view', $project));
    }

    #[Test]
    public function owner_can_update_archived_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->archived()->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('update', $project));
    }

    #[Test]
    public function owner_can_delete_archived_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->archived()->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('delete', $project));
    }

    #[Test]
    public function owner_can_restore_archived_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->forUser($user)->archived()->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('restore', $project));
    }

    #[Test]
    public function non_owner_cannot_view_archived_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->forUser($owner)->archived()->create();

        $this->actingAs($otherUser);

        $this->assertTrue(Gate::denies('view', $project));
    }

    #[Test]
    public function non_owner_cannot_restore_archived_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->forUser($owner)->archived()->create();

        $this->actingAs($otherUser);

        $this->assertTrue(Gate::denies('restore', $project));
    }

    // ==========================================
    // Role Extensibility Tests (RE-001 to RE-007)
    // ==========================================

    #[Test]
    public function admin_role_can_bypass_ownership_check(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->forUser($owner)->create();

        $this->actingAs($admin);

        // Admin should be able to view any project via before() hook
        $this->assertTrue(Gate::allows('view', $project));
        $this->assertTrue(Gate::allows('update', $project));
        $this->assertTrue(Gate::allows('delete', $project));
    }

    #[Test]
    public function policy_has_before_hook_for_role_extension(): void
    {
        $policy = new \App\Policies\ProjectPolicy();
        $this->assertTrue(method_exists($policy, 'before'));
    }

    // ==========================================
    // Edge Cases (EC)
    // ==========================================

    #[Test]
    public function gate_denies_access_to_project_with_null_user_id(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => null]);

        $this->actingAs($user);

        $this->assertTrue(Gate::denies('view', $project));
        $this->assertTrue(Gate::denies('update', $project));
        $this->assertTrue(Gate::denies('delete', $project));
    }

    #[Test]
    public function user_with_no_projects_can_still_create(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('create', Project::class));
    }

    #[Test]
    public function user_with_no_projects_can_view_any(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('viewAny', Project::class));
    }
}
