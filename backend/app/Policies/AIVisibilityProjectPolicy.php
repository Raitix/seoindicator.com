<?php

namespace App\Policies;

use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for AI Visibility Project authorization.
 *
 * @see TASK-34-6
 * @see SPEC-034
 */
class AIVisibilityProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AIVisibilityProject $project): bool
    {
        return $project->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AIVisibilityProject $project): bool
    {
        return $project->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AIVisibilityProject $project): bool
    {
        return $project->user_id === $user->id;
    }

    /**
     * Determine whether the user can run queries on the model.
     */
    public function runQueries(User $user, AIVisibilityProject $project): bool
    {
        return $project->user_id === $user->id;
    }
}
