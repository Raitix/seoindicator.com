<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the TeamMember model.
 *
 * Validates model behavior, relationships, fillable attributes,
 * casts, role constants, and helper methods.
 *
 * @see TASK-11-1
 * @see TEST-11-1
 */
class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // Model Existence and Configuration Tests
    // ========================================

    /**
     * Test that TeamMember model exists.
     * TM-MD-001
     */
    public function test_team_member_model_exists(): void
    {
        $this->assertTrue(class_exists(TeamMember::class));
    }

    /**
     * Test that model has correct fillable attributes.
     * TM-MD-002
     */
    public function test_model_has_correct_fillable_attributes(): void
    {
        $teamMember = new TeamMember();
        $expectedFillable = [
            'account_id',
            'user_id',
            'role',
            'invited_by',
            'invited_at',
            'accepted_at',
            'invitation_token',
            'invitation_expires_at',
        ];

        $this->assertEquals($expectedFillable, $teamMember->getFillable());
    }

    /**
     * Test that model has correct cast types.
     * TM-MD-003
     */
    public function test_model_has_correct_cast_types(): void
    {
        $teamMember = new TeamMember();
        $casts = $teamMember->getCasts();

        $this->assertEquals('integer', $casts['account_id']);
        $this->assertEquals('integer', $casts['user_id']);
        $this->assertEquals('integer', $casts['invited_by']);
        $this->assertEquals('datetime', $casts['invited_at']);
        $this->assertEquals('datetime', $casts['accepted_at']);
        $this->assertEquals('datetime', $casts['invitation_expires_at']);
    }

    /**
     * Test that model can be created with factory.
     * TM-MD-004
     */
    public function test_model_can_be_created_with_factory(): void
    {
        $teamMember = TeamMember::factory()->create();

        $this->assertDatabaseHas('team_members', [
            'id' => $teamMember->id,
        ]);
    }

    // ========================================
    // Role Constants Tests
    // ========================================

    /**
     * Test that role constants are defined correctly.
     * TM-MD-005
     */
    public function test_role_constants_are_defined_correctly(): void
    {
        $this->assertEquals('owner', TeamMember::ROLE_OWNER);
        $this->assertEquals('admin', TeamMember::ROLE_ADMIN);
        $this->assertEquals('editor', TeamMember::ROLE_EDITOR);
        $this->assertEquals('viewer', TeamMember::ROLE_VIEWER);
    }

    /**
     * Test getValidRoles returns all valid roles.
     * TM-MD-006
     */
    public function test_get_valid_roles_returns_all_valid_roles(): void
    {
        $validRoles = TeamMember::getValidRoles();

        $this->assertCount(4, $validRoles);
        $this->assertContains('owner', $validRoles);
        $this->assertContains('admin', $validRoles);
        $this->assertContains('editor', $validRoles);
        $this->assertContains('viewer', $validRoles);
    }

    /**
     * Test isValidRole returns true for valid roles.
     * TM-MD-007
     */
    public function test_is_valid_role_returns_true_for_valid_roles(): void
    {
        $this->assertTrue(TeamMember::isValidRole('owner'));
        $this->assertTrue(TeamMember::isValidRole('admin'));
        $this->assertTrue(TeamMember::isValidRole('editor'));
        $this->assertTrue(TeamMember::isValidRole('viewer'));
    }

    /**
     * Test isValidRole returns false for invalid roles.
     * TM-MD-008
     */
    public function test_is_valid_role_returns_false_for_invalid_roles(): void
    {
        $this->assertFalse(TeamMember::isValidRole('super_admin'));
        $this->assertFalse(TeamMember::isValidRole('member'));
        $this->assertFalse(TeamMember::isValidRole('guest'));
        $this->assertFalse(TeamMember::isValidRole(''));
        $this->assertFalse(TeamMember::isValidRole('Owner')); // Case sensitive
    }

    // ========================================
    // Token Generation Tests
    // ========================================

    /**
     * Test generateToken creates a 64 character token.
     * TM-MD-009
     */
    public function test_generate_token_creates_64_character_token(): void
    {
        $token = TeamMember::generateToken();

        $this->assertEquals(64, strlen($token));
    }

    /**
     * Test generateToken creates unique tokens.
     * TM-MD-010
     */
    public function test_generate_token_creates_unique_tokens(): void
    {
        $token1 = TeamMember::generateToken();
        $token2 = TeamMember::generateToken();

        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test getDefaultExpiry returns timestamp 24 hours in future.
     * TM-MD-011
     */
    public function test_get_default_expiry_returns_timestamp_24_hours_in_future(): void
    {
        $expiry = TeamMember::getDefaultExpiry();

        $this->assertEquals(24, TeamMember::INVITATION_EXPIRY_HOURS);
        $this->assertTrue($expiry->isFuture());
        $this->assertTrue($expiry->lte(now()->addHours(24)->addSecond()));
        $this->assertTrue($expiry->gte(now()->addHours(24)->subSecond()));
    }

    // ========================================
    // Relationship Tests
    // ========================================

    /**
     * Test TeamMember belongs to Account relationship.
     * TM-MD-012
     */
    public function test_team_member_belongs_to_account_relationship(): void
    {
        $account = Account::factory()->create();
        $teamMember = TeamMember::factory()->forAccount($account)->create();

        $this->assertInstanceOf(Account::class, $teamMember->account);
        $this->assertEquals($account->id, $teamMember->account->id);
    }

    /**
     * Test TeamMember belongs to User relationship.
     * TM-MD-013
     */
    public function test_team_member_belongs_to_user_relationship(): void
    {
        $user = User::factory()->create();
        $teamMember = TeamMember::factory()->forUser($user)->create();

        $this->assertInstanceOf(User::class, $teamMember->user);
        $this->assertEquals($user->id, $teamMember->user->id);
    }

    /**
     * Test TeamMember belongs to inviter (User) relationship.
     * TM-MD-014
     */
    public function test_team_member_belongs_to_inviter_relationship(): void
    {
        $inviter = User::factory()->create();
        $teamMember = TeamMember::factory()->invitedBy($inviter)->create();

        $this->assertInstanceOf(User::class, $teamMember->inviter);
        $this->assertEquals($inviter->id, $teamMember->inviter->id);
    }

    /**
     * Test inviter relationship returns null when no inviter.
     * TM-MD-015
     */
    public function test_inviter_relationship_returns_null_when_no_inviter(): void
    {
        $teamMember = TeamMember::factory()->create(['invited_by' => null]);

        $this->assertNull($teamMember->inviter);
    }

    // ========================================
    // Role Check Methods Tests
    // ========================================

    /**
     * Test isOwner returns true for owner role.
     * TM-MD-016
     */
    public function test_is_owner_returns_true_for_owner_role(): void
    {
        $teamMember = TeamMember::factory()->owner()->create();

        $this->assertTrue($teamMember->isOwner());
        $this->assertFalse($teamMember->isAdmin());
        $this->assertFalse($teamMember->isEditor());
        $this->assertFalse($teamMember->isViewer());
    }

    /**
     * Test isAdmin returns true for admin role.
     * TM-MD-017
     */
    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $teamMember = TeamMember::factory()->admin()->create();

        $this->assertFalse($teamMember->isOwner());
        $this->assertTrue($teamMember->isAdmin());
        $this->assertFalse($teamMember->isEditor());
        $this->assertFalse($teamMember->isViewer());
    }

    /**
     * Test isEditor returns true for editor role.
     * TM-MD-018
     */
    public function test_is_editor_returns_true_for_editor_role(): void
    {
        $teamMember = TeamMember::factory()->editor()->create();

        $this->assertFalse($teamMember->isOwner());
        $this->assertFalse($teamMember->isAdmin());
        $this->assertTrue($teamMember->isEditor());
        $this->assertFalse($teamMember->isViewer());
    }

    /**
     * Test isViewer returns true for viewer role.
     * TM-MD-019
     */
    public function test_is_viewer_returns_true_for_viewer_role(): void
    {
        $teamMember = TeamMember::factory()->viewer()->create();

        $this->assertFalse($teamMember->isOwner());
        $this->assertFalse($teamMember->isAdmin());
        $this->assertFalse($teamMember->isEditor());
        $this->assertTrue($teamMember->isViewer());
    }

    /**
     * Test canManageTeam returns true for owner and admin.
     * TM-MD-020
     */
    public function test_can_manage_team_returns_true_for_owner_and_admin(): void
    {
        $owner = TeamMember::factory()->owner()->create();
        $admin = TeamMember::factory()->admin()->create();
        $editor = TeamMember::factory()->editor()->create();
        $viewer = TeamMember::factory()->viewer()->create();

        $this->assertTrue($owner->canManageTeam());
        $this->assertTrue($admin->canManageTeam());
        $this->assertFalse($editor->canManageTeam());
        $this->assertFalse($viewer->canManageTeam());
    }

    // ========================================
    // Invitation Status Tests
    // ========================================

    /**
     * Test isPending returns true when accepted_at is null.
     * TM-MD-021
     */
    public function test_is_pending_returns_true_when_accepted_at_is_null(): void
    {
        $teamMember = TeamMember::factory()->pending()->create();

        $this->assertTrue($teamMember->isPending());
    }

    /**
     * Test isPending returns false when accepted_at is set.
     * TM-MD-022
     */
    public function test_is_pending_returns_false_when_accepted_at_is_set(): void
    {
        $teamMember = TeamMember::factory()->create(['accepted_at' => now()]);

        $this->assertFalse($teamMember->isPending());
    }

    /**
     * Test isExpired returns true when invitation_expires_at is in past.
     * TM-MD-023
     */
    public function test_is_expired_returns_true_when_invitation_expires_at_is_in_past(): void
    {
        $teamMember = TeamMember::factory()->expired()->create();

        $this->assertTrue($teamMember->isExpired());
    }

    /**
     * Test isExpired returns false when invitation_expires_at is in future.
     * TM-MD-024
     */
    public function test_is_expired_returns_false_when_invitation_expires_at_is_in_future(): void
    {
        $teamMember = TeamMember::factory()->pending()->create();

        $this->assertFalse($teamMember->isExpired());
    }

    /**
     * Test isExpired returns false when invitation_expires_at is null.
     * TM-MD-025
     */
    public function test_is_expired_returns_false_when_invitation_expires_at_is_null(): void
    {
        $teamMember = TeamMember::factory()->create([
            'invitation_expires_at' => null,
        ]);

        $this->assertFalse($teamMember->isExpired());
    }

    // ========================================
    // Token Validation Tests
    // ========================================

    /**
     * Test isValidToken returns true for matching valid token.
     * TM-MD-026
     */
    public function test_is_valid_token_returns_true_for_matching_valid_token(): void
    {
        $token = TeamMember::generateToken();
        $teamMember = TeamMember::factory()->create([
            'invitation_token' => $token,
            'invitation_expires_at' => now()->addHours(24),
        ]);

        $this->assertTrue($teamMember->isValidToken($token));
    }

    /**
     * Test isValidToken returns false for non-matching token.
     * TM-MD-027
     */
    public function test_is_valid_token_returns_false_for_non_matching_token(): void
    {
        $teamMember = TeamMember::factory()->create([
            'invitation_token' => TeamMember::generateToken(),
            'invitation_expires_at' => now()->addHours(24),
        ]);

        $this->assertFalse($teamMember->isValidToken('invalid-token-123456789012345678901234567890'));
    }

    /**
     * Test isValidToken returns false when token is null.
     * TM-MD-028
     */
    public function test_is_valid_token_returns_false_when_token_is_null(): void
    {
        $teamMember = TeamMember::factory()->create([
            'invitation_token' => null,
            'invitation_expires_at' => now()->addHours(24),
        ]);

        $this->assertFalse($teamMember->isValidToken('any-token'));
    }

    /**
     * Test isValidToken returns false when invitation is expired.
     * TM-MD-029
     */
    public function test_is_valid_token_returns_false_when_invitation_is_expired(): void
    {
        $token = TeamMember::generateToken();
        $teamMember = TeamMember::factory()->create([
            'invitation_token' => $token,
            'invitation_expires_at' => now()->subHour(),
        ]);

        $this->assertFalse($teamMember->isValidToken($token));
    }

    /**
     * Test isValidToken uses timing-safe comparison.
     * TM-MD-030
     */
    public function test_is_valid_token_uses_timing_safe_comparison(): void
    {
        $token = TeamMember::generateToken();
        $teamMember = TeamMember::factory()->create([
            'invitation_token' => $token,
            'invitation_expires_at' => now()->addHours(24),
        ]);

        // Test with slightly different token - should still fail but not throw
        $wrongToken = substr($token, 0, -1) . 'x';
        $this->assertFalse($teamMember->isValidToken($wrongToken));
    }

    // ========================================
    // Accept Invitation Tests
    // ========================================

    /**
     * Test accept method sets accepted_at and clears token.
     * TM-MD-031
     */
    public function test_accept_method_sets_accepted_at_and_clears_token(): void
    {
        $token = TeamMember::generateToken();
        $teamMember = TeamMember::factory()->create([
            'invitation_token' => $token,
            'invitation_expires_at' => now()->addHours(24),
            'accepted_at' => null,
        ]);

        $result = $teamMember->accept();

        $this->assertTrue($result);
        $this->assertNotNull($teamMember->fresh()->accepted_at);
        $this->assertNull($teamMember->fresh()->invitation_token);
        $this->assertNull($teamMember->fresh()->invitation_expires_at);
    }

    /**
     * Test accept method returns false if already accepted.
     * TM-MD-032
     */
    public function test_accept_method_returns_false_if_already_accepted(): void
    {
        $teamMember = TeamMember::factory()->create([
            'accepted_at' => now(),
        ]);

        $result = $teamMember->accept();

        $this->assertFalse($result);
    }

    /**
     * Test clearInvitation method clears token and expiry.
     * TM-MD-033
     */
    public function test_clear_invitation_method_clears_token_and_expiry(): void
    {
        $teamMember = TeamMember::factory()->create([
            'invitation_token' => TeamMember::generateToken(),
            'invitation_expires_at' => now()->addHours(24),
        ]);

        $teamMember->clearInvitation();
        $teamMember->save();

        $this->assertNull($teamMember->fresh()->invitation_token);
        $this->assertNull($teamMember->fresh()->invitation_expires_at);
    }

    // ========================================
    // Factory States Tests
    // ========================================

    /**
     * Test owner factory state creates correct role.
     * TM-MD-034
     */
    public function test_owner_factory_state_creates_correct_role(): void
    {
        $teamMember = TeamMember::factory()->owner()->create();

        $this->assertEquals(TeamMember::ROLE_OWNER, $teamMember->role);
        $this->assertNotNull($teamMember->accepted_at);
    }

    /**
     * Test admin factory state creates correct role.
     * TM-MD-035
     */
    public function test_admin_factory_state_creates_correct_role(): void
    {
        $teamMember = TeamMember::factory()->admin()->create();

        $this->assertEquals(TeamMember::ROLE_ADMIN, $teamMember->role);
        $this->assertNotNull($teamMember->accepted_at);
    }

    /**
     * Test editor factory state creates correct role.
     * TM-MD-036
     */
    public function test_editor_factory_state_creates_correct_role(): void
    {
        $teamMember = TeamMember::factory()->editor()->create();

        $this->assertEquals(TeamMember::ROLE_EDITOR, $teamMember->role);
        $this->assertNotNull($teamMember->accepted_at);
    }

    /**
     * Test viewer factory state creates correct role.
     * TM-MD-037
     */
    public function test_viewer_factory_state_creates_correct_role(): void
    {
        $teamMember = TeamMember::factory()->viewer()->create();

        $this->assertEquals(TeamMember::ROLE_VIEWER, $teamMember->role);
        $this->assertNotNull($teamMember->accepted_at);
    }

    /**
     * Test pending factory state creates pending invitation.
     * TM-MD-038
     */
    public function test_pending_factory_state_creates_pending_invitation(): void
    {
        $teamMember = TeamMember::factory()->pending()->create();

        $this->assertNull($teamMember->accepted_at);
        $this->assertNotNull($teamMember->invitation_token);
        $this->assertNotNull($teamMember->invitation_expires_at);
        $this->assertTrue($teamMember->invitation_expires_at->isFuture());
    }

    /**
     * Test expired factory state creates expired invitation.
     * TM-MD-039
     */
    public function test_expired_factory_state_creates_expired_invitation(): void
    {
        $teamMember = TeamMember::factory()->expired()->create();

        $this->assertNull($teamMember->accepted_at);
        $this->assertNotNull($teamMember->invitation_token);
        $this->assertNotNull($teamMember->invitation_expires_at);
        $this->assertTrue($teamMember->invitation_expires_at->isPast());
    }

    // ========================================
    // Edge Cases Tests
    // ========================================

    /**
     * Test model handles null invited_at gracefully.
     * TM-MD-040
     */
    public function test_model_handles_null_invited_at_gracefully(): void
    {
        $teamMember = TeamMember::factory()->create(['invited_at' => null]);

        $this->assertNull($teamMember->invited_at);
    }

    /**
     * Test model timestamps are auto-managed.
     * TM-MD-041
     */
    public function test_model_timestamps_are_auto_managed(): void
    {
        $teamMember = TeamMember::factory()->create();

        $this->assertNotNull($teamMember->created_at);
        $this->assertNotNull($teamMember->updated_at);
    }

    /**
     * Test updated_at changes on model update.
     * TM-MD-042
     */
    public function test_updated_at_changes_on_model_update(): void
    {
        $teamMember = TeamMember::factory()->create();
        $originalUpdatedAt = $teamMember->updated_at;

        sleep(1); // Ensure time difference
        $teamMember->update(['role' => TeamMember::ROLE_ADMIN]);

        $this->assertNotEquals($originalUpdatedAt, $teamMember->fresh()->updated_at);
    }
}
