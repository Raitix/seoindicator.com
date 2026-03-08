<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Location;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * @Task TASK-35-14
 * @Description Policy for Location authorization
 */
class LocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the location.
     */
    public function view(User $user, Location $location): bool
    {
        return $location->user_id === $user->id;
    }

    /**
     * Determine whether the user can sync the location's GBP data.
     */
    public function sync(User $user, Location $location): bool
    {
        return $location->user_id === $user->id && $location->gbp_connected;
    }

    /**
     * Determine whether the user can respond to reviews for the location.
     */
    public function respondToReviews(User $user, Location $location): bool
    {
        return $location->user_id === $user->id && $location->gbp_connected;
    }

    /**
     * Determine whether the user can update the location.
     */
    public function update(User $user, Location $location): bool
    {
        return $location->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the location.
     */
    public function delete(User $user, Location $location): bool
    {
        return $location->user_id === $user->id;
    }
}
