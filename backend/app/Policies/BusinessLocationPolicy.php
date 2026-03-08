<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BusinessLocation;
use App\Models\Site;

/**
 * @Task TASK-35-13
 * @Description Business Location Authorization Policy
 */
class BusinessLocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own locations
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BusinessLocation $location): bool
    {
        // Owner can view
        if ($location->user_id === $user->id) {
            return true;
        }

        // Admin can view any
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models for a specific site.
     */
    public function create(User $user, Site $site): bool
    {
        // Owner of the site can create locations
        if ($site->user_id === $user->id) {
            return true;
        }

        // Admin can create in any site
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BusinessLocation $location): bool
    {
        // Owner can update
        if ($location->user_id === $user->id) {
            return true;
        }

        // Admin can update any
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BusinessLocation $location): bool
    {
        // Owner can delete
        if ($location->user_id === $user->id) {
            return true;
        }

        // Admin can delete any
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BusinessLocation $location): bool
    {
        // Owner can restore
        if ($location->user_id === $user->id) {
            return true;
        }

        // Admin can restore any
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BusinessLocation $location): bool
    {
        // Owner can force delete
        if ($location->user_id === $user->id) {
            return true;
        }

        // Admin can force delete any
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can initiate GBP connection.
     * 
     * @Task TASK-35-13
     */
    public function connectGbp(User $user, BusinessLocation $location): bool
    {
        // Owner can connect GBP
        if ($location->user_id === $user->id) {
            return true;
        }

        // Admin can connect GBP for any
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can disconnect GBP.
     * 
     * @Task TASK-35-13
     */
    public function disconnectGbp(User $user, BusinessLocation $location): bool
    {
        // Owner can disconnect GBP
        if ($location->user_id === $user->id) {
            return true;
        }

        // Admin can disconnect GBP for any
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can sync GBP data.
     * 
     * @Task TASK-35-13
     */
    public function syncGbp(User $user, BusinessLocation $location): bool
    {
        // Owner can sync GBP
        if ($location->user_id === $user->id) {
            return true;
        }

        // Admin can sync GBP for any
        if ($user->role === 'admin' || $user->role === 'super_admin') {
            return true;
        }

        return false;
    }
}
