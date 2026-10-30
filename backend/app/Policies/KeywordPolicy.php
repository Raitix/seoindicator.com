<?php

namespace App\Policies;

use App\Models\Keyword;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Keyword Policy for REST API authorization.
 * 
 * TASK-6-8: Keywords API Endpoints
 */
class KeywordPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own keywords
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Keyword $keyword): bool
    {
        return $keyword->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Users can create keywords for their sites
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Keyword $keyword): bool
    {
        return $keyword->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Keyword $keyword): bool
    {
        return $keyword->user_id === $user->id;
    }
}
