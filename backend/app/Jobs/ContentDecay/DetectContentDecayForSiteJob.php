<?php

namespace App\Jobs\ContentDecay;

use App\Models\ContentDecayProject;
use App\Models\Site;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Detect Content Decay For Site Job.
 *
 * Background job that runs decay detection for a specific site.
 * Gathers signals from multiple sources, compares windows, and creates/updates decay candidates.
 *
 * @see TASK-36-9
 * @see SPEC-036
 */
class DetectContentDecayForSiteJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600;

    /**
     * The site ID to process.
     */
    public int $siteId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $siteId)
    {
        $this->siteId = $siteId;
        $this->onQueue('content-decay');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->siteId;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 min, 5 min, 15 min
    }

    /**
     * Determine if the job should be unique until processing begins.
     */
    public function uniqueFor(): int
    {
        return 3600; // 1 hour
    }

    /**
     * Execute the job.
     */
    public function handle(ContentDecayDetectionServiceInterface $detectionService): void
    {
        Log::info('Content decay detection started', [
            'site_id' => $this->siteId,
            'attempt' => $this->attempts(),
        ]);

        // Validate site ID
        if ($this->siteId <= 0) {
            Log::error('Invalid site ID for decay detection', [
                'site_id' => $this->siteId,
            ]);
            return;
        }

        // Load site
        $site = Site::find($this->siteId);

        if (!$site) {
            Log::warning('Site not found for decay detection', [
                'site_id' => $this->siteId,
            ]);
            return;
        }

        // Load project
        $project = ContentDecayProject::where('site_id', $this->siteId)->first();

        if (!$project) {
            Log::warning('Project not found for decay detection', [
                'site_id' => $this->siteId,
            ]);
            return;
        }

        // Check if detection is enabled
        if (!$project->detection_enabled) {
            Log::info('Detection disabled for project', [
                'project_id' => $project->id,
                'site_id' => $this->siteId,
            ]);
            return;
        }

        // Run detection
        $result = $detectionService->detectForSite($site);

        // Update last_scanned_at
        $project->update(['last_scanned_at' => now()]);

        Log::info('Content decay detection completed', [
            'site_id' => $this->siteId,
            'project_id' => $project->id,
            'candidates_created' => $result['candidates_created'] ?? 0,
            'candidates_updated' => $result['candidates_updated'] ?? 0,
            'candidates_archived' => $result['candidates_archived'] ?? 0,
            'processed_pages' => $result['processed_pages'] ?? 0,
            'eligible_pages' => $result['eligible_pages'] ?? 0,
            'errors' => $result['errors'] ?? 0,
            'duration_ms' => $result['duration_ms'] ?? 0,
        ]);

        // Check for errors in result
        if (($result['errors'] ?? 0) > 0 && isset($result['error'])) {
            Log::error('Content decay detection had errors', [
                'site_id' => $this->siteId,
                'error' => $result['error'],
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Content decay detection job failed', [
            'site_id' => $this->siteId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'site:' . $this->siteId,
            'content-decay:detection',
        ];
    }
}
