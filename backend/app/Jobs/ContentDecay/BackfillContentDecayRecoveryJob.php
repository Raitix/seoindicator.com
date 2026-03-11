<?php

namespace App\Jobs\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecaySignal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Backfill Content Decay Recovery Job.
 *
 * Background job that evaluates refreshed candidates for recovery status.
 * Compares post-refresh signals against baseline to determine if content has recovered.
 *
 * Recovery criteria (must meet 2 of 3):
 * - Clicks improved: postRefresh > baseline * 1.1
 * - CTR improved: postRefresh > baseline * 1.05
 * - Position improved: postRefresh < baseline - 2
 *
 * @see TASK-36-9
 * @see SPEC-036
 */
class BackfillContentDecayRecoveryJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * The candidate ID to evaluate.
     */
    public int $candidateId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $candidateId)
    {
        $this->candidateId = $candidateId;
        $this->onQueue('content-decay');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->candidateId;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300]; // 30s, 2 min, 5 min
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
    public function handle(): void
    {
        Log::info('Content decay recovery evaluation started', [
            'candidate_id' => $this->candidateId,
            'attempt' => $this->attempts(),
        ]);

        // Validate candidate ID
        if ($this->candidateId <= 0) {
            Log::error('Invalid candidate ID for recovery evaluation', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Load candidate
        $candidate = ContentDecayCandidate::find($this->candidateId);

        if (!$candidate) {
            Log::warning('Candidate not found for recovery evaluation', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Check if candidate is in 'refreshed' status
        if ($candidate->status !== 'refreshed') {
            Log::info('Candidate not in refreshed status, skipping recovery evaluation', [
                'candidate_id' => $this->candidateId,
                'status' => $candidate->status,
            ]);
            return;
        }

        // Load the latest signal for baseline data
        $baselineSignal = ContentDecaySignal::where('candidate_id', $this->candidateId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$baselineSignal) {
            Log::warning('No baseline signal found for candidate', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Get post-refresh data from evidence_json
        $evidence = $candidate->evidence_json ?? [];
        $postRefreshData = $evidence['post_refresh_signals'] ?? [];

        if (empty($postRefreshData)) {
            Log::info('No post-refresh signals found for candidate', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        // Evaluate recovery criteria
        $criteriaMet = $this->evaluateRecoveryCriteria($baselineSignal, $postRefreshData);

        Log::info('Recovery criteria evaluation result', [
            'candidate_id' => $this->candidateId,
            'criteria_met' => $criteriaMet,
        ]);

        // Mark as recovered if 2 or more criteria are met
        if (count(array_filter($criteriaMet)) >= 2) {
            $this->markAsRecovered($candidate, $criteriaMet, $postRefreshData);

            Log::info('Candidate marked as recovered', [
                'candidate_id' => $this->candidateId,
                'criteria_met' => array_keys(array_filter($criteriaMet)),
            ]);
        } else {
            Log::info('Candidate does not meet recovery criteria', [
                'candidate_id' => $this->candidateId,
                'criteria_met' => array_keys(array_filter($criteriaMet)),
            ]);
        }
    }

    /**
     * Evaluate recovery criteria.
     *
     * @param ContentDecaySignal $baseline The baseline signal
     * @param array $postRefresh The post-refresh data
     * @return array<string, bool>
     */
    protected function evaluateRecoveryCriteria(ContentDecaySignal $baseline, array $postRefresh): array
    {
        $criteria = [
            'clicks_improved' => false,
            'ctr_improved' => false,
            'position_improved' => false,
        ];

        // Clicks improvement: > 10%
        $baselineClicks = $baseline->previous_clicks ?? 0;
        $postRefreshClicks = $postRefresh['clicks'] ?? 0;
        if ($baselineClicks > 0) {
            $criteria['clicks_improved'] = $postRefreshClicks > ($baselineClicks * 1.1);
        }

        // CTR improvement: > 5%
        $baselineCtr = (float) ($baseline->previous_ctr ?? 0);
        $postRefreshCtr = (float) ($postRefresh['ctr'] ?? 0);
        if ($baselineCtr > 0) {
            $criteria['ctr_improved'] = $postRefreshCtr > ($baselineCtr * 1.05);
        }

        // Position improvement: >= 2 positions better (lower is better)
        $baselinePosition = (float) ($baseline->previous_avg_position ?? 0);
        $postRefreshPosition = (float) ($postRefresh['avg_position'] ?? 0);
        if ($baselinePosition > 0) {
            $criteria['position_improved'] = $postRefreshPosition < ($baselinePosition - 2);
        }

        return $criteria;
    }

    /**
     * Mark the candidate as recovered.
     */
    protected function markAsRecovered(
        ContentDecayCandidate $candidate,
        array $criteriaMet,
        array $postRefreshData
    ): void {
        // Update status
        $candidate->markAsRecovered();

        // Update evidence_json with recovery details
        $evidence = $candidate->evidence_json ?? [];
        $evidence['recovery'] = [
            'criteria_met' => array_keys(array_filter($criteriaMet)),
            'recovered_at' => now()->toIso8601String(),
            'post_refresh_signals' => $postRefreshData,
        ];

        $candidate->update(['evidence_json' => $evidence]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Content decay recovery evaluation job failed', [
            'candidate_id' => $this->candidateId,
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
            'candidate:' . $this->candidateId,
            'content-decay:recovery',
        ];
    }
}
