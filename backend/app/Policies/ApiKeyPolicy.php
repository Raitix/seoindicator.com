<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ApiKey;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for API Key authorization.
 *
 * TASK-6-4: Authorization for API Key Management Endpoints.
 */
class ApiKeyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can list their own keys
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Authenticated users can create keys
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can revoke the model.
     */
    public function revoke(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }
}
