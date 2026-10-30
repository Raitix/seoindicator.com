<?php

namespace App\Policies;

use App\Models\AIVisibility\AIVisibilityAlert;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for AI Visibility Alert authorization.
 *
 * @see TASK-34-20
 * @see SPEC-034
 */
class AIVisibilityAlertPolicy
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
    public function view(User $user, AIVisibilityAlert $alert): bool
    {
        return $this->userOwnsAlert($user, $alert);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AIVisibilityAlert $alert): bool
    {
        return $this->userOwnsAlert($user, $alert);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AIVisibilityAlert $alert): bool
    {
        return $this->userOwnsAlert($user, $alert);
    }

    /**
     * Check if the user owns the alert through the project relationship.
     */
    protected function userOwnsAlert(User $user, AIVisibilityAlert $alert): bool
    {
        return $alert->project && $alert->project->user_id === $user->id;
    }
}
