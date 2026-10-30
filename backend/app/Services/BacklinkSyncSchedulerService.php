<?php

namespace App\Services;

use App\Jobs\SyncBacklinksJob;
use App\Models\GSCConnection;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * Backlink Sync Scheduler Service for TASK-23-5.
 * 
 * Handles scheduled backlink sync operations:
 * - Querying projects needing sync
 * - Dispatching jobs with load spreading
 * - Logging results
 * 
 * @see SPEC-023
 */
class BacklinkSyncSchedulerService
{
    /**
     * Get projects that need backlink sync.
     * 
     * Returns active projects with valid GSC connections that haven't
     * been synced today (unless force is true).
     *
     * @param bool $force Include projects already synced today
     * @return Collection<int, Project>
     */
    public function getProjectsNeedingSync(bool $force = false): Collection
    {
        $query = Project::query()
            ->whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->with('user');

        if (!$force) {
            $query->where(function ($query) {
                $query->whereNull('backlinks_last_synced_at')
                    ->orWhereDate('backlinks_last_synced_at', '<', today());
            });
        }

        return $query->get();
    }

    /**
     * Dispatch sync jobs for the given projects.
     * 
     * Spreads jobs over the configured duration to avoid rate limits.
     *
     * @param Collection<int, Project> $projects
     * @param int $spreadMinutes Minutes to spread jobs over (default from config)
     * @return array{dispatched: int, skipped: int, batch_id: string|null}
     */
    public function dispatchSyncJobs(Collection $projects, int $spreadMinutes = 0): array
    {
        if ($projects->isEmpty()) {
            return [
                'dispatched' => 0,
                'skipped' => 0,
                'batch_id' => null,
            ];
        }

        $spreadMinutes = $spreadMinutes > 0 ? $spreadMinutes : config('backlinks.spread_duration', 30);
        $projectCount = $projects->count();
        $delayPerProject = $spreadMinutes / max(1, $projectCount);

        $jobs = $projects->map(function ($project, $index) use ($delayPerProject) {
            $delay = (int) ($index * $delayPerProject);
            return (new SyncBacklinksJob($project->id))->delay(now()->addMinutes($delay));
        })->toArray();

        $batch = Bus::batch($jobs)
            ->name('backlinks-sync-' . date('Y-m-d'))
            ->dispatch();

        Log::info('Backlinks scheduled sync dispatched', [
            'projects_count' => $projectCount,
            'spread_minutes' => $spreadMinutes,
            'batch_id' => $batch->id,
        ]);

        return [
            'dispatched' => $projectCount,
            'skipped' => 0,
            'batch_id' => $batch->id,
        ];
    }

    /**
     * Run the scheduled sync for all eligible projects.
     *
     * @return array{dispatched: int, skipped: int, total: int, reason: string|null}
     */
    public function runScheduledSync(): array
    {
        if (!config('backlinks.sync_enabled', true)) {
            Log::info('Backlinks scheduled sync skipped - disabled');

            return [
                'dispatched' => 0,
                'skipped' => 0,
                'total' => 0,
                'reason' => 'disabled',
            ];
        }

        Log::info('Backlinks scheduled sync starting');

        try {
            $projects = $this->getProjectsNeedingSync();
            $total = $projects->count();

            if ($total === 0) {
                Log::info('Backlinks scheduled sync complete - no projects need sync');

                return [
                    'dispatched' => 0,
                    'skipped' => 0,
                    'total' => 0,
                    'reason' => 'no_projects',
                ];
            }

            $result = $this->dispatchSyncJobs($projects);

            Log::info('Backlinks scheduled sync complete', [
                'projects_synced' => $result['dispatched'],
                'batch_id' => $result['batch_id'],
            ]);

            return [
                'dispatched' => $result['dispatched'],
                'skipped' => $result['skipped'],
                'total' => $total,
                'reason' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Backlinks scheduled sync error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'dispatched' => 0,
                'skipped' => 0,
                'total' => 0,
                'reason' => 'error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Force sync all projects with active GSC connections.
     * 
     * Includes projects already synced today.
     *
     * @return array{dispatched: int, skipped: int, total: int}
     */
    public function forceSync(): array
    {
        Log::info('Backlinks force sync starting');

        $projects = $this->getProjectsNeedingSync(force: true);
        $total = $projects->count();

        if ($total === 0) {
            Log::info('Backlinks force sync complete - no eligible projects');

            return [
                'dispatched' => 0,
                'skipped' => 0,
                'total' => 0,
            ];
        }

        $result = $this->dispatchSyncJobs($projects);

        Log::info('Backlinks force sync complete', [
            'projects_synced' => $result['dispatched'],
            'batch_id' => $result['batch_id'],
        ]);

        return [
            'dispatched' => $result['dispatched'],
            'skipped' => $result['skipped'],
            'total' => $total,
        ];
    }

    /**
     * Get statistics about sync status.
     *
     * @return array{total_projects: int, projects_with_gsc: int, projects_needing_sync: int, synced_today: int}
     */
    public function getSyncStats(): array
    {
        $totalProjects = Project::whereNull('archived_at')->count();

        $projectsWithGsc = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->count();

        $projectsNeedingSync = $this->getProjectsNeedingSync()->count();

        $syncedToday = Project::whereNull('archived_at')
            ->whereHas('user.gscConnections', function ($query) {
                $query->where('status', GSCConnection::STATUS_ACTIVE)
                    ->where('token_expires_at', '>', now());
            })
            ->whereDate('backlinks_last_synced_at', '>=', today())
            ->count();

        return [
            'total_projects' => $totalProjects,
            'projects_with_gsc' => $projectsWithGsc,
            'projects_needing_sync' => $projectsNeedingSync,
            'synced_today' => $syncedToday,
        ];
    }
}
