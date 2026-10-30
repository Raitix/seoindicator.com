<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Policy for Project authorization.
 *
 * Enforces project ownership, ensuring users can only view, edit, delete,
 * and restore their own projects. Critical for multi-tenant data isolation.
 *
 * @see TASK-8-6
 */
class ProjectPolicy
{
    /**
     * Perform pre-authorization checks.
     *
     * Allows admins to bypass all ownership checks.
     * Returns null to defer to the specific policy method for non-admins.
     *
     * @param User $user
     * @param string $ability
     * @return bool|null
     */
    public function before(User $user, string $ability): ?bool
    {
        // Admin role bypasses all checks (extensibility for future roles)
        if (isset($user->role) && $user->role === 'admin') {
            return true;
        }

        return null;
    }

    /**
     * Determine if the user can view any projects.
     *
     * Any authenticated user can view the list of projects.
     * The actual filtering is done at query level to show only owned projects.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can create projects.
     *
     * Any authenticated user can create projects.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the project.
     *
     * User must be the owner of the project.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function view(User $user, Project $project): bool
    {
        return $this->isOwner($user, $project);
    }

    /**
     * Determine if the user can update the project.
     *
     * User must be the owner of the project.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function update(User $user, Project $project): bool
    {
        return $this->isOwner($user, $project);
    }

    /**
     * Determine if the user can delete the project.
     *
     * User must be the owner of the project.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->isOwner($user, $project);
    }

    /**
     * Determine if the user can restore the project.
     *
     * User must be the owner of the project.
     * This is used for restoring soft-deleted or archived projects.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->isOwner($user, $project);
    }

    /**
     * Determine if the user can force delete the project.
     *
     * User must be the owner of the project.
     * This is used for permanently deleting a project.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $this->isOwner($user, $project);
    }

    /**
     * Check if the user is the owner of the project.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    protected function isOwner(User $user, Project $project): bool
    {
        // Handle edge case: project with null user_id has no access
        if ($project->user_id === null) {
            return false;
        }

        return $user->id === $project->user_id;
    }
}
