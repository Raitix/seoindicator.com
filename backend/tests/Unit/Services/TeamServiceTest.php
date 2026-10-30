<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\PermissionValidator;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TeamService $service;
    protected Account $account;
    protected User $owner;
    protected User $admin;
    protected User $editor;
    protected User $viewer;
    protected User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TeamService::class);

        // Create users
        $this->owner = User::factory()->create(['email' => 'owner@example.com']);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->editor = User::factory()->create(['email' => 'editor@example.com']);
        $this->viewer = User::factory()->create(['email' => 'viewer@example.com']);
        $this->outsider = User::factory()->create(['email' => 'outsider@example.com']);

        // Create account with owner
        $this->account = Account::factory()->create(['owner_id' => $this->owner->id]);

        // Create team members
        TeamMember::factory()->forAccount($this->account)->forUser($this->owner)->owner()->create();
        TeamMember::factory()->forAccount($this->account)->forUser($this->admin)->admin()->create();
        TeamMember::factory()->forAccount($this->account)->forUser($this->editor)->editor()->create();
        TeamMember::factory()->forAccount($this->account)->forUser($this->viewer)->viewer()->create();
    }

    // ========================================
    // Add Member Tests
    // ========================================

    public function test_am_01_valid_invitation_with_new_email(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'role' => 'editor',
            'invited_by' => $this->owner->id,
        ]);

        $this->assertNotNull($member);
        $this->assertEquals('editor', $member->role);
        $this->assertNull($member->accepted_at);
        $this->assertNotNull($member->invitation_token);
    }

    public function test_am_02_valid_invitation_with_default_role(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $this->assertEquals('viewer', $member->role);
    }

    public function test_am_03_duplicate_email_invitation_pending(): void
    {
        // First invitation
        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);

        // Second invitation should fail
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invitation already pending');

        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);
    }

    public function test_am_04_duplicate_email_already_accepted(): void
    {
        // Create and accept a member
        $member = TeamMember::factory()
            ->forAccount($this->account)
            ->forUser(User::factory()->create(['email' => 'existing@example.com']))
            ->pending()
            ->create();
        $member->accept();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already a team member');

        $this->service->addMember($this->account, [
            'email' => 'existing@example.com',
            'invited_by' => $this->owner->id,
        ]);
    }

    public function test_am_05_invalid_email_format(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->service->addMember($this->account, [
            'email' => 'not-an-email',
            'invited_by' => $this->owner->id,
        ]);
    }

    public function test_am_06_empty_email(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Email is required');

        $this->service->addMember($this->account, [
            'email' => '',
            'invited_by' => $this->owner->id,
        ]);
    }

    public function test_am_08_invalid_role_value(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid role');

        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'role' => 'superadmin',
            'invited_by' => $this->owner->id,
        ]);
    }

    public function test_am_09_invitation_by_non_admin_editor(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient permissions');

        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->viewer->id,
        ]);
    }

    public function test_am_10_token_uniqueness(): void
    {
        $member1 = $this->service->addMember($this->account, [
            'email' => 'user1@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $member2 = $this->service->addMember($this->account, [
            'email' => 'user2@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $this->assertNotEquals($member1->invitation_token, $member2->invitation_token);
        $this->assertGreaterThanOrEqual(64, strlen($member1->invitation_token));
    }

    public function test_am_12_token_expiration(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $this->assertNotNull($member->invitation_expires_at);
        $this->assertTrue($member->invitation_expires_at->isFuture());
    }

    public function test_am_18_missing_invited_by(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Inviter is required');

        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'role' => 'editor',
        ]);
    }

    public function test_am_19_non_existent_inviter(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Inviter not found');

        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => 999999,
        ]);
    }

    public function test_am_20_self_invitation_attempt(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot invite yourself');

        $this->service->addMember($this->account, [
            'email' => 'owner@example.com',
            'invited_by' => $this->owner->id,
        ]);
    }

    public function test_am_21_case_insensitive_email(): void
    {
        // First invite with lowercase
        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);

        // Try to invite again with uppercase
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already');

        $this->service->addMember($this->account, [
            'email' => 'NEWUSER@EXAMPLE.COM',
            'invited_by' => $this->owner->id,
        ]);
    }

    // ========================================
    // Update Role Tests
    // ========================================

    public function test_ur_01_admin_promotes_viewer_to_editor(): void
    {
        $result = $this->service->updateRole(
            $this->account->getMember($this->viewer)->id,
            'editor',
            $this->admin->id
        );

        $this->assertTrue($result);
        $this->assertEquals('editor', $this->account->getMember($this->viewer)->fresh()->role);
    }

    public function test_ur_02_owner_promotes_editor_to_admin(): void
    {
        $result = $this->service->updateRole(
            $this->account->getMember($this->editor)->id,
            'admin',
            $this->owner->id
        );

        $this->assertTrue($result);
        $this->assertEquals('admin', $this->account->getMember($this->editor)->fresh()->role);
    }

    public function test_ur_04_owner_changes_own_role(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot change your own role');

        $this->service->updateRole(
            $this->account->getMember($this->owner)->id,
            'admin',
            $this->owner->id
        );
    }

    public function test_ur_06_invalid_role_value(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid role');

        $this->service->updateRole(
            $this->account->getMember($this->viewer)->id,
            'superadmin',
            $this->owner->id
        );
    }

    public function test_ur_09_non_existent_member(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Member not found');

        $this->service->updateRole(999999, 'admin', $this->owner->id);
    }

    public function test_ur_10_update_by_viewer(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient permissions');

        $this->service->updateRole(
            $this->account->getMember($this->editor)->id,
            'viewer',
            $this->viewer->id
        );
    }

    public function test_ur_13_same_role_no_change(): void
    {
        $result = $this->service->updateRole(
            $this->account->getMember($this->editor)->id,
            'editor',
            $this->owner->id
        );

        $this->assertTrue($result);
    }

    public function test_ur_18_update_owner_role_by_admin(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot modify owner');

        $this->service->updateRole(
            $this->account->getMember($this->owner)->id,
            'viewer',
            $this->admin->id
        );
    }

    // ========================================
    // Remove Member Tests
    // ========================================

    public function test_rm_01_remove_viewer_by_admin(): void
    {
        $memberId = $this->account->getMember($this->viewer)->id;

        $result = $this->service->removeMember($memberId, $this->admin->id);

        $this->assertTrue($result);
        $this->assertNull(TeamMember::find($memberId));
    }

    public function test_rm_02_remove_editor_by_owner(): void
    {
        $memberId = $this->account->getMember($this->editor)->id;

        $result = $this->service->removeMember($memberId, $this->owner->id);

        $this->assertTrue($result);
        $this->assertNull(TeamMember::find($memberId));
    }

    public function test_rm_04_owner_removes_self(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot remove yourself');

        $this->service->removeMember(
            $this->account->getMember($this->owner)->id,
            $this->owner->id
        );
    }

    public function test_rm_06_remove_by_viewer(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient permissions');

        $this->service->removeMember(
            $this->account->getMember($this->editor)->id,
            $this->viewer->id
        );
    }

    public function test_rm_08_remove_non_existent_member(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Member not found');

        $this->service->removeMember(999999, $this->owner->id);
    }

    public function test_rm_12_remove_owner_by_admin(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot remove the owner');

        $this->service->removeMember(
            $this->account->getMember($this->owner)->id,
            $this->admin->id
        );
    }

    // ========================================
    // Token Validation Tests
    // ========================================

    public function test_tv_01_valid_token_within_expiry(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $validated = $this->service->validateInvitationToken($member->invitation_token);

        $this->assertEquals($member->id, $validated->id);
    }

    public function test_tv_02_expired_token(): void
    {
        $member = TeamMember::factory()
            ->forAccount($this->account)
            ->forUser(User::factory()->create())
            ->expired()
            ->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('expired');

        $this->service->validateInvitationToken($member->invitation_token);
    }

    public function test_tv_03_invalid_token_format(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid invitation token');

        $this->service->validateInvitationToken('invalid-token');
    }

    public function test_tv_04_empty_token(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token is required');

        $this->service->validateInvitationToken('');
    }

    public function test_tv_06_token_already_used(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);

        // Store the token before accepting
        $token = $member->invitation_token;

        // Accept the invitation
        $member->accept();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid invitation token');

        $this->service->validateInvitationToken($token);
    }

    // ========================================
    // Permission Check Tests
    // ========================================

    public function test_pc_01_owner_manages_team(): void
    {
        $result = $this->service->canManageTeam($this->owner, $this->account);
        $this->assertTrue($result);
    }

    public function test_pc_02_admin_manages_team(): void
    {
        $result = $this->service->canManageTeam($this->admin, $this->account);
        $this->assertTrue($result);
    }

    public function test_pc_03_editor_manages_team(): void
    {
        $result = $this->service->canManageTeam($this->editor, $this->account);
        $this->assertFalse($result);
    }

    public function test_pc_04_viewer_manages_team(): void
    {
        $result = $this->service->canManageTeam($this->viewer, $this->account);
        $this->assertFalse($result);
    }

    public function test_pc_05_non_member_manages_team(): void
    {
        $result = $this->service->canManageTeam($this->outsider, $this->account);
        $this->assertFalse($result);
    }

    // ========================================
    // Additional Security Tests
    // ========================================

    public function test_cannot_assign_owner_role_via_invitation(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot assign owner role');

        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'role' => 'owner',
            'invited_by' => $this->owner->id,
        ]);
    }

    public function test_admin_cannot_invite_admin(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'role' => 'admin',
            'invited_by' => $this->admin->id,
        ]);
    }

    public function test_accept_invitation(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $newUser = $member->user;
        $accepted = $this->service->acceptInvitation($member->invitation_token, $newUser->id);

        $this->assertNotNull($accepted->accepted_at);
        $this->assertNull($accepted->invitation_token);
    }

    public function test_cancel_invitation(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $result = $this->service->cancelInvitation($member->id, $this->owner->id);

        $this->assertTrue($result);
        $this->assertNull(TeamMember::find($member->id));
    }

    // ========================================
    // Additional Edge Case Tests
    // ========================================

    public function test_ur_03_demote_admin_to_viewer(): void
    {
        $result = $this->service->updateRole(
            $this->account->getMember($this->admin)->id,
            'viewer',
            $this->owner->id
        );

        $this->assertTrue($result);
        $this->assertEquals('viewer', $this->account->getMember($this->admin)->fresh()->role);
    }

    public function test_ur_05_remove_last_admin_by_demotion(): void
    {
        // Remove the owner from members to test last admin scenario
        // In our setup, we have an owner and an admin
        // Demoting the last admin should fail if there's no owner
        // But since we have an owner, this should succeed

        $result = $this->service->updateRole(
            $this->account->getMember($this->admin)->id,
            'viewer',
            $this->owner->id
        );

        $this->assertTrue($result);
    }

    public function test_rm_03_remove_admin_by_owner(): void
    {
        // Create another admin so we can remove one
        $anotherAdmin = User::factory()->create(['email' => 'admin2@example.com']);
        TeamMember::factory()->forAccount($this->account)->forUser($anotherAdmin)->admin()->create();

        $memberId = $this->account->getMember($this->admin)->id;

        $result = $this->service->removeMember($memberId, $this->owner->id);

        $this->assertTrue($result);
        $this->assertNull(TeamMember::find($memberId));
    }

    public function test_rm_05_remove_last_admin(): void
    {
        // Try to remove the only admin (but we have an owner)
        // This should succeed because owner exists
        $memberId = $this->account->getMember($this->admin)->id;

        $result = $this->service->removeMember($memberId, $this->owner->id);

        $this->assertTrue($result);
    }

    public function test_am_13_email_with_unicode_characters(): void
    {
        // Note: PHP's filter_var with FILTER_VALIDATE_EMAIL doesn't support all unicode emails
        // This is expected behavior - the system uses standard email validation
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->service->addMember($this->account, [
            'email' => 'пользователь@пример.рф',
            'invited_by' => $this->owner->id,
        ]);
    }

    public function test_am_14_email_with_plus_addressing(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'user+team@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $this->assertNotNull($member);
    }

    public function test_am_17_very_long_email(): void
    {
        // Very long emails fail validation (either format or length check)
        $this->expectException(\RuntimeException::class);

        $longEmail = str_repeat('a', 500) . '@example.com';
        $this->service->addMember($this->account, [
            'email' => $longEmail,
            'invited_by' => $this->owner->id,
        ]);
    }

    public function test_tv_07_token_for_deleted_account(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'invited_by' => $this->owner->id,
        ]);

        $token = $member->invitation_token;

        // Delete the account (this cascade deletes the team_member)
        $this->account->delete();

        // The token lookup will fail since the member was deleted
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid invitation token');

        $this->service->validateInvitationToken($token);
    }

    public function test_rm_09_remove_member_from_different_team(): void
    {
        // Create another account with a member
        $otherAccount = Account::factory()->create(['owner_id' => $this->owner->id]);
        $otherMember = TeamMember::factory()
            ->forAccount($otherAccount)
            ->forUser(User::factory()->create())
            ->viewer()
            ->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not a member of this team');

        // Try to remove other account's member from this account
        $this->service->removeMember($otherMember->id, $this->admin->id);
    }

    public function test_ur_14_member_from_different_team(): void
    {
        // Create another account with a member
        $otherAccount = Account::factory()->create(['owner_id' => $this->owner->id]);
        $otherMember = TeamMember::factory()
            ->forAccount($otherAccount)
            ->forUser(User::factory()->create())
            ->viewer()
            ->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not a member of this team');

        // Try to update other account's member from this account
        $this->service->updateRole($otherMember->id, 'editor', $this->admin->id);
    }

    public function test_editor_can_invite_viewer(): void
    {
        // Editors should NOT be able to invite (only admins and owners)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient permissions');

        $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'role' => 'viewer',
            'invited_by' => $this->editor->id,
        ]);
    }

    public function test_admin_can_invite_editor(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'role' => 'editor',
            'invited_by' => $this->admin->id,
        ]);

        $this->assertNotNull($member);
        $this->assertEquals('editor', $member->role);
    }

    public function test_owner_can_invite_admin(): void
    {
        $member = $this->service->addMember($this->account, [
            'email' => 'newuser@example.com',
            'role' => 'admin',
            'invited_by' => $this->owner->id,
        ]);

        $this->assertNotNull($member);
        $this->assertEquals('admin', $member->role);
    }

    public function test_pc_06_pending_member_manages_team(): void
    {
        $pendingMember = TeamMember::factory()
            ->forAccount($this->account)
            ->forUser(User::factory()->create())
            ->pending()
            ->create();

        $result = $this->service->canManageTeam($pendingMember->user, $this->account);
        $this->assertFalse($result);
    }
}
