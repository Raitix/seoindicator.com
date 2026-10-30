<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use App\Models\Site;

/**
 * Policy for Report authorization.
 *
 * Ensures users can only access reports for sites they own.
 */
class ReportPolicy
{
    /**
     * Determine if the user can view any reports for a site.
     */
    public function viewAny(User $user, Site $site): bool
    {
        return $site->user_id === $user->id;
    }

    /**
     * Determine if the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        return $report->site && $report->site->user_id === $user->id;
    }

    /**
     * Determine if the user can create reports for a site.
     */
    public function create(User $user, Site $site): bool
    {
        return $site->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the report.
     */
    public function delete(User $user, Report $report): bool
    {
        return $report->site && $report->site->user_id === $user->id;
    }

    /**
     * Determine if the user can download the report.
     */
    public function download(User $user, Report $report): bool
    {
        return $report->site && $report->site->user_id === $user->id;
    }
}
