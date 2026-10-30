<?php

namespace App\Services;

use App\Models\Account;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * TeamService - Team Management Service.
 *
 * Handles team collaboration features including:
 * - Adding members with invitation tokens
 * - Updating member roles
 * - Removing members
 * - Token validation
 *
 * @see TASK-11-3
 */
class TeamService
{
    /**
     * Permission validator instance.
     */
    protected PermissionValidator $permissionValidator;

    /**
     * Create a new TeamService instance.
     */
    public function __construct(PermissionValidator $permissionValidator)
    {
        $this->permissionValidator = $permissionValidator;
    }

    /**
     * Add a new member to a team with an invitation.
     *
     * @param Account $account The team/account to add the member to
     * @param array $data Invitation data:
     *   - email: string (required) - Email of the user to invite
     *   - role: string (optional, default 'viewer') - Role to assign
     *   - invited_by: int (required) - User ID of the inviter
     * @return TeamMember The created team member (pending)
     * @throws RuntimeException If validation fails
     */
    public function addMember(Account $account, array $data): TeamMember
    {
        // Validate required fields
        if (empty($data['email'])) {
            throw new RuntimeException('Email is required.');
        }

        if (empty($data['invited_by'])) {
            throw new RuntimeException('Inviter is required.');
        }

        // Get inviter
        $inviter = User::find($data['invited_by']);
        if (!$inviter) {
            throw new RuntimeException('Inviter not found.');
        }

        // Check if inviter is a member of the account with management permissions
        $inviterMember = $account->getMember($inviter);
        if (!$inviterMember || !$inviterMember->canManageTeam()) {
            throw new RuntimeException('Insufficient permissions to invite members.');
        }

        // Normalize email
        $email = strtolower(trim($data['email']));

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email format.');
        }

        // Check email length
        if (strlen($email) > 254) {
            throw new RuntimeException('Email is too long.');
        }

        // Check for self-invitation
        if ($email === strtolower($inviter->email)) {
            throw new RuntimeException('Cannot invite yourself.');
        }

        // Set default role
        $role = $data['role'] ?? TeamMember::ROLE_VIEWER;

        // Validate role
        if (!TeamMember::isValidRole($role)) {
            throw new RuntimeException('Invalid role. Valid roles: ' . implode(', ', TeamMember::getValidRoles()));
        }

        // Owner role cannot be assigned via invitation
        if ($role === TeamMember::ROLE_OWNER) {
            throw new RuntimeException('Cannot assign owner role via invitation.');
        }

        // Check if inviter can invite with this role
        // Set the role on the user object for PermissionValidator
        $inviter->role = $inviterMember->role;
        $canInvite = $this->permissionValidator->canInviteWithRole($inviter, $role, 'team');
        if (!$canInvite['allowed']) {
            throw new RuntimeException($canInvite['reason'] ?? 'Insufficient permissions to invite with this role.');
        }

        // Find or create user by email
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        // Check if user is already a member
        if ($user) {
            $existingMember = $account->getMember($user);
            if ($existingMember) {
                if ($existingMember->isPending()) {
                    throw new RuntimeException('Invitation already pending for this user.');
                }
                throw new RuntimeException('User is already a team member.');
            }
        }

        // Generate secure invitation token
        $token = TeamMember::generateToken();
        $expiresAt = TeamMember::getDefaultExpiry();

        return DB::transaction(function () use ($account, $user, $email, $role, $inviter, $token, $expiresAt) {
            // Create user if doesn't exist (placeholder user for invitation)
            if (!$user) {
                $user = User::create([
                    'name' => substr($email, 0, strpos($email, '@')),
                    'email' => $email,
                    'password' => Str::random(32), // Placeholder password
                ]);
            }

            // Create team member with pending invitation
            $member = TeamMember::create([
                'account_id' => $account->id,
                'user_id' => $user->id,
                'role' => $role,
                'invited_by' => $inviter->id,
                'invited_at' => now(),
                'accepted_at' => null,
                'invitation_token' => $token,
                'invitation_expires_at' => $expiresAt,
            ]);

            // Log the invitation
            Log::info('Team member invited', [
                'account_id' => $account->id,
                'user_id' => $user->id,
                'role' => $role,
                'invited_by' => $inviter->id,
            ]);

            return $member;
        });
    }

    /**
     * Update a team member's role.
     *
     * @param int $teamMemberId The ID of the team member to update
     * @param string $newRole The new role to assign
     * @param int $actorId The ID of the user making the change
     * @return bool True if successful
     * @throws RuntimeException If validation fails
     */
    public function updateRole(int $teamMemberId, string $newRole, int $actorId): bool
    {
        // Validate role
        if (empty($newRole)) {
            throw new RuntimeException('Role is required.');
        }

        if (!TeamMember::isValidRole($newRole)) {
            throw new RuntimeException('Invalid role. Valid roles: ' . implode(', ', TeamMember::getValidRoles()));
        }

        // Find team member
        $teamMember = TeamMember::find($teamMemberId);
        if (!$teamMember) {
            throw new RuntimeException('Member not found.');
        }

        // Get actor
        $actor = User::find($actorId);
        if (!$actor) {
            throw new RuntimeException('Actor not found.');
        }

        // Get actor's membership in the same account
        $actorMember = TeamMember::where('account_id', $teamMember->account_id)
            ->where('user_id', $actorId)
            ->first();

        if (!$actorMember) {
            throw new RuntimeException('Actor is not a member of this team.');
        }

        // Check if updating own role
        if ($teamMember->user_id === $actorId) {
            throw new RuntimeException('Cannot change your own role.');
        }

        // Cannot modify owner's role
        if ($teamMember->isOwner()) {
            throw new RuntimeException('Cannot modify owner role.');
        }

        // Check if actor can manage team
        if (!$actorMember->canManageTeam()) {
            throw new RuntimeException('Insufficient permissions to update roles.');
        }

        // Check permission using PermissionValidator
        // Set the roles on the user objects for PermissionValidator
        $targetUser = $teamMember->user;
        $actor->role = $actorMember->role;
        $targetUser->role = $teamMember->role;
        $canUpdate = $this->permissionValidator->canUpdateRole($actor, $targetUser, $newRole, 'team');
        if (!$canUpdate['allowed']) {
            throw new RuntimeException($canUpdate['reason'] ?? 'Insufficient permissions.');
        }

        // Check for last admin protection
        if ($teamMember->isAdmin() && $newRole !== TeamMember::ROLE_ADMIN) {
            $adminCount = TeamMember::where('account_id', $teamMember->account_id)
                ->where('role', TeamMember::ROLE_ADMIN)
                ->count();
            
            // Check if there's an owner (owner counts as admin-like)
            $hasOwner = TeamMember::where('account_id', $teamMember->account_id)
                ->where('role', TeamMember::ROLE_OWNER)
                ->exists();

            if ($adminCount <= 1 && !$hasOwner) {
                throw new RuntimeException('Cannot remove the last admin. Assign another admin first.');
            }
        }

        // No change needed
        if ($teamMember->role === $newRole) {
            return true;
        }

        $oldRole = $teamMember->role;
        $teamMember->role = $newRole;
        $teamMember->save();

        Log::info('Team member role updated', [
            'team_member_id' => $teamMember->id,
            'account_id' => $teamMember->account_id,
            'user_id' => $teamMember->user_id,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'updated_by' => $actorId,
        ]);

        return true;
    }

    /**
     * Remove a team member.
     *
     * @param int $teamMemberId The ID of the team member to remove
     * @param int $actorId The ID of the user making the removal
     * @return bool True if successful
     * @throws RuntimeException If validation fails
     */
    public function removeMember(int $teamMemberId, int $actorId): bool
    {
        // Find team member
        $teamMember = TeamMember::find($teamMemberId);
        if (!$teamMember) {
            throw new RuntimeException('Member not found.');
        }

        // Get actor
        $actor = User::find($actorId);
        if (!$actor) {
            throw new RuntimeException('Actor not found.');
        }

        // Get actor's membership in the same account
        $actorMember = TeamMember::where('account_id', $teamMember->account_id)
            ->where('user_id', $actorId)
            ->first();

        if (!$actorMember) {
            throw new RuntimeException('Actor is not a member of this team.');
        }

        // Check if removing self
        if ($teamMember->user_id === $actorId) {
            throw new RuntimeException('Cannot remove yourself from the team.');
        }

        // Cannot remove owner
        if ($teamMember->isOwner()) {
            throw new RuntimeException('Cannot remove the owner. Transfer ownership first.');
        }

        // Check if actor can manage team
        if (!$actorMember->canManageTeam()) {
            throw new RuntimeException('Insufficient permissions to remove members.');
        }

        // Check permission using PermissionValidator
        // Set the roles on the user objects for PermissionValidator
        $targetUser = $teamMember->user;
        $actor->role = $actorMember->role;
        $targetUser->role = $teamMember->role;
        $canRemove = $this->permissionValidator->canRemoveMember($actor, $targetUser, 'team');
        if (!$canRemove['allowed']) {
            throw new RuntimeException($canRemove['reason'] ?? 'Insufficient permissions.');
        }

        // Check for last admin protection
        if ($teamMember->isAdmin()) {
            $adminCount = TeamMember::where('account_id', $teamMember->account_id)
                ->where('role', TeamMember::ROLE_ADMIN)
                ->count();

            $hasOwner = TeamMember::where('account_id', $teamMember->account_id)
                ->where('role', TeamMember::ROLE_OWNER)
                ->exists();

            if ($adminCount <= 1 && !$hasOwner) {
                throw new RuntimeException('Cannot remove the last admin. Assign another admin first.');
            }
        }

        return DB::transaction(function () use ($teamMember, $actorId) {
            $accountId = $teamMember->account_id;
            $userId = $teamMember->user_id;

            // Remove from project_members as well
            DB::table('project_members')
                ->whereIn('project_id', function ($query) use ($accountId) {
                    $query->select('id')
                        ->from('projects')
                        ->where('account_id', $accountId);
                })
                ->where('user_id', $userId)
                ->delete();

            // Remove the team member
            $teamMember->delete();

            Log::info('Team member removed', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'removed_by' => $actorId,
            ]);

            return true;
        });
    }

    /**
     * Validate an invitation token.
     *
     * @param string $token The invitation token to validate
     * @return TeamMember The team member if valid
     * @throws RuntimeException If validation fails
     */
    public function validateInvitationToken(string $token): TeamMember
    {
        if (empty($token)) {
            throw new RuntimeException('Token is required.');
        }

        // Find member by token
        $member = TeamMember::where('invitation_token', $token)->first();

        if (!$member) {
            throw new RuntimeException('Invalid invitation token.');
        }

        // Check if already accepted
        if ($member->accepted_at !== null) {
            throw new RuntimeException('Invitation already accepted.');
        }

        // Check if expired
        if ($member->isExpired()) {
            throw new RuntimeException('Invitation has expired.');
        }

        // Check if account still exists
        if (!$member->account) {
            throw new RuntimeException('Team no longer exists.');
        }

        return $member;
    }

    /**
     * Accept an invitation.
     *
     * @param string $token The invitation token
     * @param int $userId The user accepting the invitation
     * @return TeamMember The accepted team member
     * @throws RuntimeException If validation fails
     */
    public function acceptInvitation(string $token, int $userId): TeamMember
    {
        $member = $this->validateInvitationToken($token);

        // Verify the user matches the invitation
        if ($member->user_id !== $userId) {
            throw new RuntimeException('This invitation is not for you.');
        }

        $member->accept();

        Log::info('Team invitation accepted', [
            'team_member_id' => $member->id,
            'account_id' => $member->account_id,
            'user_id' => $userId,
        ]);

        return $member;
    }

    /**
     * Check if a user can manage a team.
     *
     * @param User $user The user to check
     * @param Account $account The account to check against
     * @return bool
     */
    public function canManageTeam(User $user, Account $account): bool
    {
        $member = $account->getMember($user);

        if (!$member) {
            return false;
        }

        return $member->canManageTeam();
    }

    /**
     * Cancel a pending invitation.
     *
     * @param int $teamMemberId The ID of the pending member
     * @param int $actorId The ID of the user canceling
     * @return bool
     * @throws RuntimeException If validation fails
     */
    public function cancelInvitation(int $teamMemberId, int $actorId): bool
    {
        $member = TeamMember::find($teamMemberId);

        if (!$member) {
            throw new RuntimeException('Member not found.');
        }

        if (!$member->isPending()) {
            throw new RuntimeException('Cannot cancel an accepted invitation.');
        }

        // Get actor
        $actorMember = TeamMember::where('account_id', $member->account_id)
            ->where('user_id', $actorId)
            ->first();

        if (!$actorMember || !$actorMember->canManageTeam()) {
            throw new RuntimeException('Insufficient permissions to cancel invitation.');
        }

        $member->delete();

        Log::info('Team invitation cancelled', [
            'team_member_id' => $teamMemberId,
            'account_id' => $member->account_id,
            'cancelled_by' => $actorId,
        ]);

        return true;
    }
}
