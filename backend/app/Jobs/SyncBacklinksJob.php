<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\Backlinks\BacklinkSnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to sync backlinks from Google Search Console.
 * 
 * TASK-23-4: Backlink Sync Command and Job
 * TASK-23-11: Dispatched by BacklinkSyncController
 * 
 * @see SPEC-023
 * 
 * @property int $projectId
 * @property bool $force
 */
class SyncBacklinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The project ID to sync.
     */
    public int $projectId;

    /**
     * Whether to force sync even if recently synced.
     */
    public bool $force;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(int $projectId, bool $force = false)
    {
        $this->projectId = $projectId;
        $this->force = $force;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "backlink-sync-{$this->projectId}";
    }

    /**
     * Execute the job.
     */
    public function handle(BacklinkSnapshotService $snapshotService): void
    {
        $project = Project::find($this->projectId);

        if (!$project) {
            Log::warning("SyncBacklinksJob: Project not found", ['project_id' => $this->projectId]);
            return;
        }

        if (!$project->domain) {
            Log::warning("SyncBacklinksJob: Project has no domain", ['project_id' => $this->projectId]);
            return;
        }

        // Check for GSC connection
        $hasGscConnection = $project->gscConnections()->active()->exists();
        if (!$hasGscConnection) {
            Log::warning("SyncBacklinksJob: No active GSC connection", ['project_id' => $this->projectId]);
            return;
        }

        Log::info("SyncBacklinksJob: Starting sync", ['project_id' => $this->projectId]);

        try {
            // TODO: Implement actual backlink fetching from GSC
            // For now, we'll just create a snapshot with current data
            $snapshot = $snapshotService->createSnapshot($project);

            Log::info("SyncBacklinksJob: Sync completed", [
                'project_id' => $this->projectId,
                'snapshot_id' => $snapshot->id,
                'total_backlinks' => $snapshot->total_backlinks,
            ]);
        } catch (\Exception $e) {
            Log::error("SyncBacklinksJob: Sync failed", [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SyncBacklinksJob: Job failed", [
            'project_id' => $this->projectId,
            'error' => $exception->getMessage(),
        ]);
    }
}
