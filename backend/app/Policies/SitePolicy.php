<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

/**
 * Policy for Site authorization.
 *
 * Enforces site ownership, ensuring users can only view and manage
 * their own sites. Critical for multi-tenant data isolation.
 *
 * @see TASK-5-7
 */
class SitePolicy
{
    /**
     * Perform pre-authorization checks.
     *
     * Allows admins to bypass all ownership checks.
     *
     * @param User $user
     * @param string $ability
     * @return bool|null
     */
    public function before(User $user, string $ability): ?bool
    {
        if (isset($user->role) && $user->role === 'admin') {
            return true;
        }

        return null;
    }

    /**
     * Determine if the user can view any sites.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the site.
     *
     * User must be the owner of the site.
     *
     * @param User $user
     * @param Site $site
     * @return bool
     */
    public function view(User $user, Site $site): bool
    {
        return $this->isOwner($user, $site);
    }

    /**
     * Determine if the user can create sites.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the site.
     *
     * User must be the owner of the site.
     *
     * @param User $user
     * @param Site $site
     * @return bool
     */
    public function update(User $user, Site $site): bool
    {
        return $this->isOwner($user, $site);
    }

    /**
     * Determine if the user can delete the site.
     *
     * User must be the owner of the site.
     *
     * @param User $user
     * @param Site $site
     * @return bool
     */
    public function delete(User $user, Site $site): bool
    {
        return $this->isOwner($user, $site);
    }

    /**
     * Check if the user is the owner of the site.
     *
     * @param User $user
     * @param Site $site
     * @return bool
     */
    protected function isOwner(User $user, Site $site): bool
    {
        if ($site->user_id === null) {
            return false;
        }

        return $user->id === $site->user_id;
    }
}
