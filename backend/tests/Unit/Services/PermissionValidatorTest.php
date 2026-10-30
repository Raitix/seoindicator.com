<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PermissionValidator;
use App\Models\User;
use Mockery;

/**
 * Unit tests for TASK-11-4: Permission Validator Service
 *
 * Tests verify:
 * - Role hierarchy validation
 * - Role change permissions
 * - Member removal permissions
 * - Self-modification restrictions
 * - Scope validation (team vs project)
 */
class PermissionValidatorTest extends TestCase
{
    protected PermissionValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new PermissionValidator();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Create a mock user with a specific role.
     */
    protected function createUserWithRole(int $id, string $role): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;
        $user->role = $role;
        return $user;
    }

    // ============================================
    // Test 1-4: Role Hierarchy
    // ============================================

    /**
     * Test 1: Role hierarchy levels are correct.
     */
    public function test_role_hierarchy_levels(): void
    {
        $this->assertEquals(100, $this->validator->getRoleLevel('owner'));
        $this->assertEquals(75, $this->validator->getRoleLevel('admin'));
        $this->assertEquals(50, $this->validator->getRoleLevel('editor'));
        $this->assertEquals(25, $this->validator->getRoleLevel('viewer'));
    }

    /**
     * Test 2: Invalid role returns zero level.
     */
    public function test_invalid_role_level(): void
    {
        $this->assertEquals(0, $this->validator->getRoleLevel('invalid'));
        $this->assertEquals(0, $this->validator->getRoleLevel('superadmin'));
        $this->assertEquals(0, $this->validator->getRoleLevel(''));
    }

    /**
     * Test 3: Available roles for team scope.
     */
    public function test_available_team_roles(): void
    {
        $roles = $this->validator->getAvailableRoles('team');

        $this->assertCount(4, $roles);
        $this->assertContains('owner', $roles);
        $this->assertContains('admin', $roles);
        $this->assertContains('editor', $roles);
        $this->assertContains('viewer', $roles);
    }

    /**
     * Test 4: Available roles for project scope.
     */
    public function test_available_project_roles(): void
    {
        $roles = $this->validator->getAvailableRoles('project');

        $this->assertCount(2, $roles);
        $this->assertContains('editor', $roles);
        $this->assertContains('viewer', $roles);
        $this->assertNotContains('owner', $roles);
        $this->assertNotContains('admin', $roles);
    }

    // ============================================
    // Test 5-12: Role Update Permissions
    // ============================================

    /**
     * Test 5: Owner can demote admin to editor.
     */
    public function test_owner_can_demote_admin(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $admin = $this->createUserWithRole(2, 'admin');

        $result = $this->validator->canUpdateRole($owner, $admin, 'editor');

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['reason']);
    }

    /**
     * Test 6: Owner can promote editor to admin.
     */
    public function test_owner_can_promote_editor_to_admin(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $editor = $this->createUserWithRole(2, 'editor');

        $result = $this->validator->canUpdateRole($owner, $editor, 'admin');

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 7: Admin can demote editor to viewer.
     */
    public function test_admin_can_demote_editor(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');
        $editor = $this->createUserWithRole(2, 'editor');

        $result = $this->validator->canUpdateRole($admin, $editor, 'viewer');

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 8: Admin cannot promote editor to admin.
     */
    public function test_admin_cannot_promote_to_admin(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');
        $editor = $this->createUserWithRole(2, 'editor');

        $result = $this->validator->canUpdateRole($admin, $editor, 'admin');

        $this->assertFalse($result['allowed']);
        // Admin cannot assign a role equal to their own level
        $this->assertStringContainsString('equal to or higher', $result['reason']);
    }

    /**
     * Test 9: Admin cannot modify another admin.
     */
    public function test_admin_cannot_modify_admin(): void
    {
        $admin1 = $this->createUserWithRole(1, 'admin');
        $admin2 = $this->createUserWithRole(2, 'admin');

        $result = $this->validator->canUpdateRole($admin1, $admin2, 'editor');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('equal or higher', $result['reason']);
    }

    /**
     * Test 10: Admin cannot modify owner.
     */
    public function test_admin_cannot_modify_owner(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');
        $owner = $this->createUserWithRole(2, 'owner');

        $result = $this->validator->canUpdateRole($admin, $owner, 'viewer');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('equal or higher', $result['reason']);
    }

    /**
     * Test 11: Editor cannot modify anyone.
     * Editor (50) cannot assign 'editor' role (50) - equal to their own.
     */
    public function test_editor_cannot_modify_anyone(): void
    {
        $editor = $this->createUserWithRole(1, 'editor');
        $viewer = $this->createUserWithRole(2, 'viewer');

        // Editor trying to assign 'editor' role - equal to their own
        $result = $this->validator->canUpdateRole($editor, $viewer, 'editor');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('equal to or higher', $result['reason']);
    }

    /**
     * Test 12: Viewer cannot modify anyone.
     */
    public function test_viewer_cannot_modify_anyone(): void
    {
        $viewer1 = $this->createUserWithRole(1, 'viewer');
        $viewer2 = $this->createUserWithRole(2, 'viewer');

        $result = $this->validator->canUpdateRole($viewer1, $viewer2, 'editor');

        $this->assertFalse($result['allowed']);
    }

    // ============================================
    // Test 13-18: Self-Modification Restrictions
    // ============================================

    /**
     * Test 13: Owner cannot change own role.
     */
    public function test_owner_cannot_change_own_role(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');

        $result = $this->validator->canUpdateRole($owner, $owner, 'admin');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Owners cannot change their own role', $result['reason']);
    }

    /**
     * Test 14: Admin can demote self to editor.
     */
    public function test_admin_can_demote_self(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');

        $result = $this->validator->canUpdateRole($admin, $admin, 'editor');

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 15: Admin cannot promote self to owner.
     */
    public function test_admin_cannot_promote_self(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');

        $result = $this->validator->canUpdateRole($admin, $admin, 'owner');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Cannot promote yourself', $result['reason']);
    }

    /**
     * Test 16: Editor can demote self to viewer.
     */
    public function test_editor_can_demote_self(): void
    {
        $editor = $this->createUserWithRole(1, 'editor');

        $result = $this->validator->canUpdateRole($editor, $editor, 'viewer');

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 17: Editor cannot promote self to admin.
     */
    public function test_editor_cannot_promote_self(): void
    {
        $editor = $this->createUserWithRole(1, 'editor');

        $result = $this->validator->canUpdateRole($editor, $editor, 'admin');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Cannot promote yourself', $result['reason']);
    }

    /**
     * Test 18: Viewer cannot promote self.
     */
    public function test_viewer_cannot_promote_self(): void
    {
        $viewer = $this->createUserWithRole(1, 'viewer');

        $result = $this->validator->canUpdateRole($viewer, $viewer, 'editor');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Cannot promote yourself', $result['reason']);
    }

    // ============================================
    // Test 19-24: Member Removal Permissions
    // ============================================

    /**
     * Test 19: Owner can remove admin.
     */
    public function test_owner_can_remove_admin(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $admin = $this->createUserWithRole(2, 'admin');

        $result = $this->validator->canRemoveMember($owner, $admin);

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 20: Owner can remove editor.
     */
    public function test_owner_can_remove_editor(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $editor = $this->createUserWithRole(2, 'editor');

        $result = $this->validator->canRemoveMember($owner, $editor);

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 21: Admin can remove editor.
     */
    public function test_admin_can_remove_editor(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');
        $editor = $this->createUserWithRole(2, 'editor');

        $result = $this->validator->canRemoveMember($admin, $editor);

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 22: Admin can remove viewer.
     */
    public function test_admin_can_remove_viewer(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');
        $viewer = $this->createUserWithRole(2, 'viewer');

        $result = $this->validator->canRemoveMember($admin, $viewer);

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 23: Admin cannot remove another admin.
     */
    public function test_admin_cannot_remove_admin(): void
    {
        $admin1 = $this->createUserWithRole(1, 'admin');
        $admin2 = $this->createUserWithRole(2, 'admin');

        $result = $this->validator->canRemoveMember($admin1, $admin2);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('equal or higher', $result['reason']);
    }

    /**
     * Test 24: No one can remove owner.
     */
    public function test_cannot_remove_owner(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $admin = $this->createUserWithRole(2, 'admin');

        $result = $this->validator->canRemoveMember($admin, $owner);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Cannot remove an owner', $result['reason']);
    }

    // ============================================
    // Test 25-28: Self-Removal Permissions
    // ============================================

    /**
     * Test 25: Owner cannot remove self.
     */
    public function test_owner_cannot_remove_self(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');

        $result = $this->validator->canRemoveMember($owner, $owner);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Transfer ownership', $result['reason']);
    }

    /**
     * Test 26: Admin can remove self (leave team).
     */
    public function test_admin_can_remove_self(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');

        $result = $this->validator->canRemoveMember($admin, $admin);

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 27: Editor can remove self (leave team).
     */
    public function test_editor_can_remove_self(): void
    {
        $editor = $this->createUserWithRole(1, 'editor');

        $result = $this->validator->canRemoveMember($editor, $editor);

        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 28: Viewer can remove self (leave team).
     */
    public function test_viewer_can_remove_self(): void
    {
        $viewer = $this->createUserWithRole(1, 'viewer');

        $result = $this->validator->canRemoveMember($viewer, $viewer);

        $this->assertTrue($result['allowed']);
    }

    // ============================================
    // Test 29-32: Scope Validation
    // ============================================

    /**
     * Test 29: Invalid scope rejected for role update.
     */
    public function test_invalid_scope_role_update(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $viewer = $this->createUserWithRole(2, 'viewer');

        $result = $this->validator->canUpdateRole($owner, $viewer, 'editor', 'invalid');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Invalid scope', $result['reason']);
    }

    /**
     * Test 30: Invalid scope rejected for member removal.
     */
    public function test_invalid_scope_member_removal(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $viewer = $this->createUserWithRole(2, 'viewer');

        $result = $this->validator->canRemoveMember($owner, $viewer, 'invalid');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Invalid scope', $result['reason']);
    }

    /**
     * Test 31: Project scope rejects owner role.
     */
    public function test_project_scope_rejects_owner_role(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $viewer = $this->createUserWithRole(2, 'viewer');

        $result = $this->validator->canUpdateRole($owner, $viewer, 'owner', 'project');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Invalid role', $result['reason']);
    }

    /**
     * Test 32: Project scope rejects admin role.
     */
    public function test_project_scope_rejects_admin_role(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $viewer = $this->createUserWithRole(2, 'viewer');

        $result = $this->validator->canUpdateRole($owner, $viewer, 'admin', 'project');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Invalid role', $result['reason']);
    }

    // ============================================
    // Test 33-36: Permission Checks
    // ============================================

    /**
     * Test 33: Owner has all permissions.
     */
    public function test_owner_has_all_permissions(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');

        $this->assertTrue($this->validator->hasPermission($owner, 'view'));
        $this->assertTrue($this->validator->hasPermission($owner, 'edit'));
        $this->assertTrue($this->validator->hasPermission($owner, 'manage'));
        $this->assertTrue($this->validator->hasPermission($owner, 'admin'));
    }

    /**
     * Test 34: Admin has appropriate permissions.
     */
    public function test_admin_permissions(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');

        $this->assertTrue($this->validator->hasPermission($admin, 'view'));
        $this->assertTrue($this->validator->hasPermission($admin, 'edit'));
        $this->assertTrue($this->validator->hasPermission($admin, 'manage'));
        $this->assertFalse($this->validator->hasPermission($admin, 'admin'));
    }

    /**
     * Test 35: Editor has appropriate permissions.
     */
    public function test_editor_permissions(): void
    {
        $editor = $this->createUserWithRole(1, 'editor');

        $this->assertTrue($this->validator->hasPermission($editor, 'view'));
        $this->assertTrue($this->validator->hasPermission($editor, 'edit'));
        $this->assertFalse($this->validator->hasPermission($editor, 'manage'));
        $this->assertFalse($this->validator->hasPermission($editor, 'admin'));
    }

    /**
     * Test 36: Viewer has appropriate permissions.
     */
    public function test_viewer_permissions(): void
    {
        $viewer = $this->createUserWithRole(1, 'viewer');

        $this->assertTrue($this->validator->hasPermission($viewer, 'view'));
        $this->assertFalse($this->validator->hasPermission($viewer, 'edit'));
        $this->assertFalse($this->validator->hasPermission($viewer, 'manage'));
        $this->assertFalse($this->validator->hasPermission($viewer, 'admin'));
    }

    // ============================================
    // Test 37-40: Role Transition Validation
    // ============================================

    /**
     * Test 37: Valid role transitions.
     */
    public function test_valid_role_transitions(): void
    {
        $transitions = [
            ['viewer', 'editor'],
            ['editor', 'viewer'],
            ['editor', 'admin'],
            ['admin', 'editor'],
            ['admin', 'owner'],
        ];

        foreach ($transitions as [$from, $to]) {
            $result = $this->validator->validateRoleTransition($from, $to);
            $this->assertTrue($result['valid'], "Transition from {$from} to {$to} should be valid");
        }
    }

    /**
     * Test 38: Invalid current role in transition.
     */
    public function test_invalid_current_role_transition(): void
    {
        $result = $this->validator->validateRoleTransition('superadmin', 'viewer');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid current role', $result['reason']);
    }

    /**
     * Test 39: Invalid new role in transition.
     */
    public function test_invalid_new_role_transition(): void
    {
        $result = $this->validator->validateRoleTransition('viewer', 'superadmin');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid new role', $result['reason']);
    }

    /**
     * Test 40: Same role transition is valid.
     */
    public function test_same_role_transition(): void
    {
        $result = $this->validator->validateRoleTransition('admin', 'admin');

        $this->assertTrue($result['valid']);
    }

    // ============================================
    // Test 41-44: Invite Permissions
    // ============================================

    /**
     * Test 41: Owner can invite with any role.
     */
    public function test_owner_can_invite_any_role(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');

        $roles = ['admin', 'editor', 'viewer'];
        foreach ($roles as $role) {
            $result = $this->validator->canInviteWithRole($owner, $role);
            $this->assertTrue($result['allowed'], "Owner should be able to invite as {$role}");
        }
    }

    /**
     * Test 42: Admin can invite editors and viewers.
     */
    public function test_admin_can_invite_editor_viewer(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');

        $result = $this->validator->canInviteWithRole($admin, 'editor');
        $this->assertTrue($result['allowed']);

        $result = $this->validator->canInviteWithRole($admin, 'viewer');
        $this->assertTrue($result['allowed']);
    }

    /**
     * Test 43: Admin cannot invite as admin.
     */
    public function test_admin_cannot_invite_admin(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');

        $result = $this->validator->canInviteWithRole($admin, 'admin');

        $this->assertFalse($result['allowed']);
        // Admin cannot invite someone to a role equal to their own level
        // This check runs before the "only owners can invite admins" check
        $this->assertStringContainsString('equal to or higher', $result['reason']);
    }

    /**
     * Test 44: Editor can invite viewers.
     */
    public function test_editor_can_invite_viewer(): void
    {
        $editor = $this->createUserWithRole(1, 'editor');

        // Editor (50) can invite viewer (25) - viewer is lower level
        $result = $this->validator->canInviteWithRole($editor, 'viewer');

        $this->assertTrue($result['allowed']);
    }

    // ============================================
    // Test 45-48: Max Assignable Role
    // ============================================

    /**
     * Test 45: Owner's max assignable role is admin.
     */
    public function test_owner_max_assignable_role(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');

        $maxRole = $this->validator->getMaxAssignableRole($owner);

        $this->assertEquals('admin', $maxRole);
    }

    /**
     * Test 46: Admin's max assignable role is editor.
     */
    public function test_admin_max_assignable_role(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');

        $maxRole = $this->validator->getMaxAssignableRole($admin);

        $this->assertEquals('editor', $maxRole);
    }

    /**
     * Test 47: Editor's max assignable role is viewer.
     */
    public function test_editor_max_assignable_role(): void
    {
        $editor = $this->createUserWithRole(1, 'editor');

        $maxRole = $this->validator->getMaxAssignableRole($editor);

        // Editor (50) can assign viewer (25)
        $this->assertEquals('viewer', $maxRole);
    }

    /**
     * Test 48: Viewer's max assignable role is null.
     */
    public function test_viewer_max_assignable_role(): void
    {
        $viewer = $this->createUserWithRole(1, 'viewer');

        $maxRole = $this->validator->getMaxAssignableRole($viewer);

        $this->assertNull($maxRole);
    }

    // ============================================
    // Test 49-52: Edge Cases
    // ============================================

    /**
     * Test 49: User without role defaults to viewer.
     */
    public function test_user_without_role_defaults_to_viewer(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->role = null;

        $this->assertFalse($this->validator->hasPermission($user, 'edit'));
        $this->assertTrue($this->validator->hasPermission($user, 'view'));
    }

    /**
     * Test 50: Invalid action for permission check.
     */
    public function test_invalid_action_permission(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');

        $this->assertFalse($this->validator->hasPermission($owner, 'invalid_action'));
    }

    /**
     * Test 51: Project scope invite with team-only role.
     */
    public function test_project_invite_with_team_role(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');

        $result = $this->validator->canInviteWithRole($owner, 'admin', 'project');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Invalid role', $result['reason']);
    }

    /**
     * Test 52: Project scope works correctly.
     */
    public function test_project_scope_role_update(): void
    {
        $owner = $this->createUserWithRole(1, 'owner');
        $viewer = $this->createUserWithRole(2, 'viewer');

        $result = $this->validator->canUpdateRole($owner, $viewer, 'editor', 'project');

        $this->assertTrue($result['allowed']);
    }

    // ============================================
    // Security Tests
    // ============================================

    /**
     * Security: Cannot assign role higher than actor's own.
     */
    public function test_cannot_assign_higher_role(): void
    {
        $admin = $this->createUserWithRole(1, 'admin');
        $viewer = $this->createUserWithRole(2, 'viewer');

        // Admin trying to assign owner role
        $result = $this->validator->canUpdateRole($admin, $viewer, 'owner');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('higher than your own', $result['reason']);
    }

    /**
     * Security: Cannot modify users with same role.
     */
    public function test_cannot_modify_same_role(): void
    {
        $editor1 = $this->createUserWithRole(1, 'editor');
        $editor2 = $this->createUserWithRole(2, 'editor');

        $result = $this->validator->canUpdateRole($editor1, $editor2, 'viewer');

        $this->assertFalse($result['allowed']);
    }

    /**
     * Security: Cannot remove users with higher role.
     */
    public function test_cannot_remove_higher_role(): void
    {
        $editor = $this->createUserWithRole(1, 'editor');
        $admin = $this->createUserWithRole(2, 'admin');

        $result = $this->validator->canRemoveMember($editor, $admin);

        $this->assertFalse($result['allowed']);
    }
}
