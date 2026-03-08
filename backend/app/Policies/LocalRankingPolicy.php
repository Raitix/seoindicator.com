<?php

namespace App\Policies;

use App\Models\BusinessLocation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * LocalRanking Policy for API authorization.
 *
 * TASK-35-15: Local Rankings API Controller
 */
class LocalRankingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view rankings for the location.
     */
    public function viewRankings(User $user, BusinessLocation $location): bool
    {
        return $location->user_id === $user->id;
    }

    /**
     * Determine whether the user can trigger ranking checks.
     */
    public function checkRankings(User $user, BusinessLocation $location): bool
    {
        return $location->user_id === $user->id;
    }

    /**
     * Determine whether the user can view quota usage.
     */
    public function viewQuota(User $user, BusinessLocation $location): bool
    {
        return $location->user_id === $user->id;
    }

    /**
     * Determine whether the user can view competitors.
     */
    public function viewCompetitors(User $user, BusinessLocation $location): bool
    {
        return $location->user_id === $user->id;
    }

    /**
     * Determine whether the user can add a competitor.
     */
    public function addCompetitor(User $user, BusinessLocation $location): bool
    {
        return $location->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete a competitor.
     */
    public function deleteCompetitor(User $user, BusinessLocation $location): bool
    {
        return $location->user_id === $user->id;
    }
}
