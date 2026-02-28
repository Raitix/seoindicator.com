<?php

namespace App\Console\Commands;

use App\Jobs\SyncBacklinksJob;
use App\Models\GSCConnection;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

/**
 * Artisan command to sync backlink data from Google Search Console.
 *
 * TASK-23-4: Backlink Sync Command and Job
 *
 * @see SPEC-023
 *
 * Usage:
 *   php artisan backlinks:sync {project?} [--all] [--force]
 *
 * Arguments:
 *   project       The project ID to sync
 *
 * Options:
 *   --all         Sync all projects with active GSC connections
 *   --force       Force sync even if recently synced
 */
class BacklinksSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backlinks:sync
        {project? : The project ID to sync}
        {--all : Sync all projects with active GSC connections}
        {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync backlink data from Google Search Console';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->argument('project');
        $syncAll = $this->option('all');
        $force = $this->option('force');

        // Validate project ID if provided
        if ($projectId !== null) {
            if (!is_numeric($projectId)) {
                $this->error('Project ID must be a number.');
                return self::FAILURE;
            }
            if ((int) $projectId <= 0) {
                $this->error('Project ID must be a positive number.');
                return self::FAILURE;
            }
            $projectId = (int) $projectId;
        }

        // Sync all projects
        if ($syncAll) {
            return $this->syncAllProjects($force);
        }

        // Sync single project
        if ($projectId !== null) {
            return $this->syncSingleProject($projectId, $force);
        }

        // No project specified, show help
        $this->error('Please specify a project ID or use --all to sync all projects.');
        return self::FAILURE;
    }

    /**
     * Sync a single project.
     */
    protected function syncSingleProject(int $projectId, bool $force): int
    {
        $project = Project::find($projectId);

        if (!$project) {
            $this->error('Project not found.');
            return self::FAILURE;
        }

        // Check if archived
        if ($project->archived_at) {
            $this->info('Project is archived.');
            return self::SUCCESS;
        }

        // Check if domain is configured
        if (!$project->domain) {
            $this->info('Project has no domain configured.');
            return self::SUCCESS;
        }

        // Check for active GSC connection
        $hasActiveConnection = GSCConnection::where('user_id', $project->user_id)
            ->where('status', GSCConnection::STATUS_ACTIVE)
            ->exists();

        if (!$hasActiveConnection) {
            $this->info('No active GSC connection for this project.');
            return self::SUCCESS;
        }

        // Show verbose info
        if ($this->output->isVerbose()) {
            $this->info("Project: {$project->name}");
            $this->info("Domain: {$project->domain}");
        }

        // Dispatch the sync job
        SyncBacklinksJob::dispatch($projectId, $force);

        if (!$this->option('quiet')) {
            $this->info("Backlink sync queued for project {$projectId}");
        }

        return self::SUCCESS;
    }

    /**
     * Sync all projects with active GSC connections.
     */
    protected function syncAllProjects(bool $force): int
    {
        // Get user IDs that have active GSC connections
        $userIdsWithGsc = GSCConnection::where('status', GSCConnection::STATUS_ACTIVE)
            ->distinct()
            ->pluck('user_id');

        // Get all active projects from those users
        $projects = Project::active()
            ->whereNotNull('domain')
            ->whereIn('user_id', $userIdsWithGsc)
            ->get();

        if ($projects->isEmpty()) {
            $this->info('No projects found to sync.');
            return self::SUCCESS;
        }

        $count = 0;
        $bar = $this->output->createProgressBar($projects->count());

        foreach ($projects as $project) {
            SyncBacklinksJob::dispatch($project->id, $force);
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if (!$this->option('quiet')) {
            $this->info("Queued {$count} project(s) for backlink sync");
        }

        return self::SUCCESS;
    }
}
