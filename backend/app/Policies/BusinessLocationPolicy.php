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
        return $this->ownsLocationOrIsAdmin($user, $location);
    }

    /**
     * Determine whether the user can view local rankings for the location.
     *
     * @Task TASK-35-15
     */
    public function viewRankings(User $user, BusinessLocation $location): bool
    {
        return $this->view($user, $location);
    }

    /**
     * Determine whether the user can trigger local ranking checks.
     *
     * @Task TASK-35-15
     */
    public function checkRankings(User $user, BusinessLocation $location): bool
    {
        return $this->update($user, $location);
    }

    /**
     * Determine whether the user can view local rank quota usage.
     *
     * @Task TASK-35-15
     */
    public function viewQuota(User $user, BusinessLocation $location): bool
    {
        return $this->view($user, $location);
    }

    /**
     * Determine whether the user can view tracked competitors.
     *
     * @Task TASK-35-15
     */
    public function viewCompetitors(User $user, BusinessLocation $location): bool
    {
        return $this->view($user, $location);
    }

    /**
     * Determine whether the user can add tracked competitors.
     *
     * @Task TASK-35-15
     */
    public function addCompetitor(User $user, BusinessLocation $location): bool
    {
        return $this->update($user, $location);
    }

    /**
     * Determine whether the user can delete tracked competitors.
     *
     * @Task TASK-35-15
     */
    public function deleteCompetitor(User $user, BusinessLocation $location): bool
    {
        return $this->update($user, $location);
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
        return $this->ownsLocationOrIsAdmin($user, $location);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BusinessLocation $location): bool
    {
        return $this->ownsLocationOrIsAdmin($user, $location);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BusinessLocation $location): bool
    {
        return $this->ownsLocationOrIsAdmin($user, $location);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BusinessLocation $location): bool
    {
        return $this->ownsLocationOrIsAdmin($user, $location);
    }

    /**
     * Determine whether the user can initiate GBP connection.
     * 
     * @Task TASK-35-13
     */
    public function connectGbp(User $user, BusinessLocation $location): bool
    {
        return $this->ownsLocationOrIsAdmin($user, $location);
    }

    /**
     * Determine whether the user can disconnect GBP.
     * 
     * @Task TASK-35-13
     */
    public function disconnectGbp(User $user, BusinessLocation $location): bool
    {
        return $this->ownsLocationOrIsAdmin($user, $location);
    }

    /**
     * Determine whether the user can sync GBP data.
     * 
     * @Task TASK-35-13
     */
    public function syncGbp(User $user, BusinessLocation $location): bool
    {
        return $this->ownsLocationOrIsAdmin($user, $location);
    }

    protected function ownsLocationOrIsAdmin(User $user, BusinessLocation $location): bool
    {
        return $location->user_id === $user->id
            || $user->role === 'admin'
            || $user->role === 'super_admin';
    }
}