<?php

namespace App\Policies;

use App\Models\CrawlJob;
use App\Models\User;
use App\Services\Crawler\CrawlRateLimiter;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for CrawlJob model.
 *
 * Rules:
 * - View: Owner or admin only
 * - Update: Owner with queued status only
 * - Delete/Cancel: Owner with any status
 * - Create: Within rate limits
 *
 * @see TASK-22-12
 */
class CrawlJobPolicy
{
    use HandlesAuthorization;

    protected CrawlRateLimiter $rateLimiter;

    /**
     * Create a new policy instance.
     */
    public function __construct(?CrawlRateLimiter $rateLimiter = null)
    {
        $this->rateLimiter = $rateLimiter ?? app(CrawlRateLimiter::class);
    }

    /**
     * Determine whether the user can view the crawl job.
     */
    public function view(?User $user, CrawlJob $crawlJob): bool
    {
        // Must be authenticated
        if ($user === null) {
            return false;
        }

        // Admin can view any job
        if ($this->isAdmin($user)) {
            return true;
        }

        // Owner can view their own job
        return $crawlJob->user_id === $user->id;
    }

    /**
     * Determine whether the user can create crawl jobs.
     */
    public function create(?User $user): bool
    {
        // Must be authenticated
        if ($user === null) {
            return false;
        }

        // Admin bypasses rate limits
        if ($this->isAdmin($user)) {
            return true;
        }

        // Check rate limits
        $result = $this->rateLimiter->canCreateJob($user);
        return $result['allowed'];
    }

    /**
     * Determine whether the user can update the crawl job.
     */
    public function update(?User $user, CrawlJob $crawlJob): bool
    {
        // Must be authenticated
        if ($user === null) {
            return false;
        }

        // Admin can update any job
        if ($this->isAdmin($user)) {
            return true;
        }

        // Must be owner
        if ($crawlJob->user_id !== $user->id) {
            return false;
        }

        // Can only update queued jobs
        return $crawlJob->status === 'queued';
    }

    /**
     * Determine whether the user can delete the crawl job.
     */
    public function delete(?User $user, CrawlJob $crawlJob): bool
    {
        // Must be authenticated
        if ($user === null) {
            return false;
        }

        // Admin can delete any job
        if ($this->isAdmin($user)) {
            return true;
        }

        // Owner can delete their own job (any status)
        return $crawlJob->user_id === $user->id;
    }

    /**
     * Determine whether the user can cancel the crawl job.
     */
    public function cancel(?User $user, CrawlJob $crawlJob): bool
    {
        // Same rules as delete - owner can cancel any of their jobs
        return $this->delete($user, $crawlJob);
    }

    /**
     * Determine whether the user can view any crawl jobs.
     */
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can restore the crawl job.
     */
    public function restore(?User $user, CrawlJob $crawlJob): bool
    {
        return $this->delete($user, $crawlJob);
    }

    /**
     * Determine whether the user can permanently delete the crawl job.
     */
    public function forceDelete(?User $user, CrawlJob $crawlJob): bool
    {
        return $this->delete($user, $crawlJob);
    }

    /**
     * Check if user is an admin.
     */
    protected function isAdmin(User $user): bool
    {
        return $user->is_admin === true;
    }
}
