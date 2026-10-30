<?php

namespace App\Jobs\ContentDecay;

use App\Models\ContentDecayProject;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayDigestServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Send Content Decay Digest Job.
 *
 * Background job that generates and sends weekly digest notifications
 * for content decay detection results.
 *
 * @see TASK-36-9
 * @see SPEC-036
 */
class SendContentDecayDigestJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * The project ID to generate digest for.
     */
    public int $projectId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $projectId)
    {
        $this->projectId = $projectId;
        $this->onQueue('notifications');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->projectId;
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
    public function handle(ContentDecayDigestServiceInterface $digestService): void
    {
        Log::info('Content decay digest job started', [
            'project_id' => $this->projectId,
            'attempt' => $this->attempts(),
        ]);

        // Validate project ID
        if ($this->projectId <= 0) {
            Log::error('Invalid project ID for digest', [
                'project_id' => $this->projectId,
            ]);
            return;
        }

        // Load project
        $project = ContentDecayProject::find($this->projectId);

        if (!$project) {
            Log::warning('Project not found for digest', [
                'project_id' => $this->projectId,
            ]);
            return;
        }

        // Check if digest is enabled
        if (!$project->digest_enabled) {
            Log::info('Digest disabled for project', [
                'project_id' => $this->projectId,
            ]);
            return;
        }

        // Check if digest should be sent (frequency check)
        if (!$digestService->shouldSendDigest($project)) {
            Log::info('Digest should not be sent for project', [
                'project_id' => $this->projectId,
            ]);
            return;
        }

        // Load project owner
        $user = User::find($project->user_id);

        if (!$user) {
            Log::warning('Owner not found for project', [
                'project_id' => $this->projectId,
                'user_id' => $project->user_id,
            ]);
            return;
        }

        // Generate digest
        Log::info('Generating content decay digest', [
            'project_id' => $this->projectId,
            'user_id' => $user->id,
        ]);

        $digest = $digestService->generateDigest($project);

        // Check if digest has content
        if ($this->isEmptyDigest($digest)) {
            Log::info('Empty digest, skipping notification', [
                'project_id' => $this->projectId,
            ]);
            return;
        }

        // Send digest
        $digestService->sendDigest($user, $digest);

        // Update last_digest_sent_at
        $project->update(['last_digest_sent_at' => now()]);

        Log::info('Content decay digest sent successfully', [
            'project_id' => $this->projectId,
            'user_id' => $user->id,
            'new_decay_count' => $digest['new_decay_count'] ?? 0,
            'critical_count' => $digest['critical_count'] ?? 0,
            'recovered_count' => $digest['recovered_count'] ?? 0,
        ]);
    }

    /**
     * Check if digest is empty (no content to report).
     */
    protected function isEmptyDigest(array $digest): bool
    {
        $newDecayCount = $digest['new_decay_count'] ?? 0;
        $criticalCount = $digest['critical_count'] ?? 0;
        $recoveredCount = $digest['recovered_count'] ?? 0;

        return $newDecayCount === 0 && $criticalCount === 0 && $recoveredCount === 0;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Content decay digest job failed', [
            'project_id' => $this->projectId,
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
            'project:' . $this->projectId,
            'content-decay:digest',
        ];
    }
}
