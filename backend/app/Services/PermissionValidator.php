<?php

namespace App\Services;

use App\Models\User;

/**
 * Permission Validator Service for TASK-11-4.
 *
 * Validates role-based access permissions for team collaboration.
 * Ensures operational boundaries are upheld (e.g., Admins cannot modify Owners).
 *
 * Role Hierarchy (highest to lowest):
 * - owner: Full access, cannot be modified by others
 * - admin: Can manage editors/viewers, cannot modify owners or other admins
 * - editor: Can modify content, cannot manage members
 * - viewer: Read-only access
 */
class PermissionValidator
{
    /**
     * Role hierarchy for permission checks.
     * Higher number = higher privilege.
     */
    protected const ROLE_HIERARCHY = [
        'owner' => 100,
        'admin' => 75,
        'editor' => 50,
        'viewer' => 25,
    ];

    /**
     * Roles available at the team/account level.
     */
    protected const TEAM_ROLES = ['owner', 'admin', 'editor', 'viewer'];

    /**
     * Roles available at the project level.
     */
    protected const PROJECT_ROLES = ['editor', 'viewer'];

    /**
     * Check if a user can update another user's role.
     *
     * @param User $actor The user attempting the action
     * @param User $target The user whose role is being changed
     * @param string $newRole The new role to assign
     * @param string $scope 'team' or 'project'
     * @return array{allowed: bool, reason: string|null}
     */
    public function canUpdateRole(User $actor, User $target, string $newRole, string $scope = 'team'): array
    {
        // Validate scope
        if (!in_array($scope, ['team', 'project'])) {
            return [
                'allowed' => false,
                'reason' => 'Invalid scope. Must be "team" or "project".',
            ];
        }

        // Validate new role for scope
        $validRoles = $scope === 'team' ? self::TEAM_ROLES : self::PROJECT_ROLES;
        if (!in_array($newRole, $validRoles)) {
            return [
                'allowed' => false,
                'reason' => "Invalid role '{$newRole}' for {$scope} scope. Valid roles: " . implode(', ', $validRoles),
            ];
        }

        // Self-modification rules
        if ($actor->id === $target->id) {
            return $this->validateSelfRoleChange($actor, $newRole);
        }

        // Get actor's role (would be fetched from team_members/project_members in production)
        $actorRole = $actor->role ?? 'viewer';
        $targetRole = $target->role ?? 'viewer';

        // Check if actor has sufficient privilege
        $actorLevel = self::ROLE_HIERARCHY[$actorRole] ?? 0;
        $targetLevel = self::ROLE_HIERARCHY[$targetRole] ?? 0;
        $newRoleLevel = self::ROLE_HIERARCHY[$newRole] ?? 0;

        // Rule: Actors cannot modify users with equal or higher roles
        if ($actorLevel <= $targetLevel) {
            return [
                'allowed' => false,
                'reason' => "Cannot modify user with equal or higher role ({$targetRole}).",
            ];
        }

        // Rule: Actors cannot assign roles higher than their own
        if ($newRoleLevel >= $actorLevel) {
            return [
                'allowed' => false,
                'reason' => "Cannot assign role equal to or higher than your own ({$actorRole}).",
            ];
        }

        // Rule: Only owners can promote to admin
        if ($newRole === 'admin' && $actorRole !== 'owner') {
            return [
                'allowed' => false,
                'reason' => 'Only owners can promote members to admin.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate self-role change attempts.
     *
     * @param User $actor The user attempting self-modification
     * @param string $newRole The new role they want to assign to themselves
     * @return array{allowed: bool, reason: string|null}
     */
    protected function validateSelfRoleChange(User $actor, string $newRole): array
    {
        $currentRole = $actor->role ?? 'viewer';
        $currentLevel = self::ROLE_HIERARCHY[$currentRole] ?? 0;
        $newLevel = self::ROLE_HIERARCHY[$newRole] ?? 0;

        // Rule: Owners cannot change their own role
        if ($currentRole === 'owner') {
            return [
                'allowed' => false,
                'reason' => 'Owners cannot change their own role.',
            ];
        }

        // Rule: No self-promotion
        if ($newLevel > $currentLevel) {
            return [
                'allowed' => false,
                'reason' => 'Cannot promote yourself to a higher role.',
            ];
        }

        // Rule: Self-demotion is allowed for non-owners
        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Check if a user can remove another user from a team/project.
     *
     * @param User $actor The user attempting the removal
     * @param User $target The user being removed
     * @param string $scope 'team' or 'project'
     * @return array{allowed: bool, reason: string|null}
     */
    public function canRemoveMember(User $actor, User $target, string $scope = 'team'): array
    {
        // Validate scope
        if (!in_array($scope, ['team', 'project'])) {
            return [
                'allowed' => false,
                'reason' => 'Invalid scope. Must be "team" or "project".',
            ];
        }

        // Self-removal rules
        if ($actor->id === $target->id) {
            return $this->validateSelfRemoval($actor);
        }

        // Get actor's and target's roles
        $actorRole = $actor->role ?? 'viewer';
        $targetRole = $target->role ?? 'viewer';

        // Rule: Cannot remove the last owner
        if ($targetRole === 'owner') {
            return [
                'allowed' => false,
                'reason' => 'Cannot remove an owner. Transfer ownership first.',
            ];
        }

        $actorLevel = self::ROLE_HIERARCHY[$actorRole] ?? 0;
        $targetLevel = self::ROLE_HIERARCHY[$targetRole] ?? 0;

        // Rule: Cannot remove users with equal or higher roles
        if ($actorLevel <= $targetLevel) {
            return [
                'allowed' => false,
                'reason' => "Cannot remove user with equal or higher role ({$targetRole}).",
            ];
        }

        // Admins can remove editors and viewers
        if ($actorRole === 'admin' && in_array($targetRole, ['editor', 'viewer'])) {
            return [
                'allowed' => true,
                'reason' => null,
            ];
        }

        // Owners can remove anyone except other owners
        if ($actorRole === 'owner' && $targetRole !== 'owner') {
            return [
                'allowed' => true,
                'reason' => null,
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Insufficient permissions to remove this member.',
        ];
    }

    /**
     * Validate self-removal attempts.
     *
     * @param User $actor The user attempting self-removal
     * @return array{allowed: bool, reason: string|null}
     */
    protected function validateSelfRemoval(User $actor): array
    {
        $actorRole = $actor->role ?? 'viewer';

        // Rule: Owners cannot remove themselves (must transfer ownership first)
        if ($actorRole === 'owner') {
            return [
                'allowed' => false,
                'reason' => 'Owners cannot remove themselves. Transfer ownership to another member first.',
            ];
        }

        // Non-owners can remove themselves (leave the team/project)
        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Check if a user has permission to perform an action in a scope.
     *
     * @param User $user The user to check
     * @param string $action The action to check (view, edit, manage, admin)
     * @param string $scope 'team' or 'project'
     * @return bool
     */
    public function hasPermission(User $user, string $action, string $scope = 'team'): bool
    {
        $role = $user->role ?? 'viewer';
        $roleLevel = self::ROLE_HIERARCHY[$role] ?? 0;

        $actionLevels = [
            'view' => 25,      // viewer+
            'edit' => 50,      // editor+
            'manage' => 75,    // admin+
            'admin' => 100,    // owner only
        ];

        if (!isset($actionLevels[$action])) {
            return false;
        }

        return $roleLevel >= $actionLevels[$action];
    }

    /**
     * Get the role hierarchy level for a given role.
     *
     * @param string $role
     * @return int
     */
    public function getRoleLevel(string $role): int
    {
        return self::ROLE_HIERARCHY[$role] ?? 0;
    }

    /**
     * Get all available roles for a scope.
     *
     * @param string $scope 'team' or 'project'
     * @return array
     */
    public function getAvailableRoles(string $scope = 'team'): array
    {
        return $scope === 'team' ? self::TEAM_ROLES : self::PROJECT_ROLES;
    }

    /**
     * Validate role transition is allowed.
     *
     * @param string $currentRole The user's current role
     * @param string $newRole The desired new role
     * @return array{valid: bool, reason: string|null}
     */
    public function validateRoleTransition(string $currentRole, string $newRole): array
    {
        $currentLevel = self::ROLE_HIERARCHY[$currentRole] ?? 0;
        $newLevel = self::ROLE_HIERARCHY[$newRole] ?? 0;

        // Check if roles are valid
        if (!isset(self::ROLE_HIERARCHY[$currentRole])) {
            return [
                'valid' => false,
                'reason' => "Invalid current role: {$currentRole}",
            ];
        }

        if (!isset(self::ROLE_HIERARCHY[$newRole])) {
            return [
                'valid' => false,
                'reason' => "Invalid new role: {$newRole}",
            ];
        }

        // All transitions between valid roles are allowed
        // (permission to make the transition is checked separately)
        return [
            'valid' => true,
            'reason' => null,
        ];
    }

    /**
     * Check if an actor can invite a user with a specific role.
     *
     * @param User $actor The user sending the invitation
     * @param string $inviteRole The role to assign to the invited user
     * @param string $scope 'team' or 'project'
     * @return array{allowed: bool, reason: string|null}
     */
    public function canInviteWithRole(User $actor, string $inviteRole, string $scope = 'team'): array
    {
        // Validate scope
        if (!in_array($scope, ['team', 'project'])) {
            return [
                'allowed' => false,
                'reason' => 'Invalid scope. Must be "team" or "project".',
            ];
        }

        // Validate role for scope
        $validRoles = $scope === 'team' ? self::TEAM_ROLES : self::PROJECT_ROLES;
        if (!in_array($inviteRole, $validRoles)) {
            return [
                'allowed' => false,
                'reason' => "Invalid role '{$inviteRole}' for {$scope} scope.",
            ];
        }

        $actorRole = $actor->role ?? 'viewer';
        $actorLevel = self::ROLE_HIERARCHY[$actorRole] ?? 0;
        $inviteLevel = self::ROLE_HIERARCHY[$inviteRole] ?? 0;

        // Rule: Cannot invite users to roles equal or higher than your own
        if ($inviteLevel >= $actorLevel) {
            return [
                'allowed' => false,
                'reason' => "Cannot invite users to role equal to or higher than your own.",
            ];
        }

        // Rule: Only owners can invite admins
        if ($inviteRole === 'admin' && $actorRole !== 'owner') {
            return [
                'allowed' => false,
                'reason' => 'Only owners can invite users as admins.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Get the highest role a user can assign.
     *
     * @param User $actor
     * @param string $scope
     * @return string|null
     */
    public function getMaxAssignableRole(User $actor, string $scope = 'team'): ?string
    {
        $actorRole = $actor->role ?? 'viewer';
        $actorLevel = self::ROLE_HIERARCHY[$actorRole] ?? 0;

        $validRoles = $scope === 'team' ? self::TEAM_ROLES : self::PROJECT_ROLES;
        $maxAssignable = null;
        $maxLevel = 0;

        foreach ($validRoles as $role) {
            $roleLevel = self::ROLE_HIERARCHY[$role] ?? 0;

            // Must be lower than actor's level
            if ($roleLevel < $actorLevel && $roleLevel > $maxLevel) {
                // Special case: only owners can assign admin
                if ($role === 'admin' && $actorRole !== 'owner') {
                    continue;
                }
                $maxAssignable = $role;
                $maxLevel = $roleLevel;
            }
        }

        return $maxAssignable;
    }
}
