<?php

namespace App\Services;

use App\Events\AnalysisIndicatorComplete;
use App\Events\AnalysisProgressUpdated;
use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\Analysis\Indicators\IndicatorResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProgressService - Manages analysis job progress tracking.
 *
 * Updates job state and broadcasts progress events for real-time UI updates.
 */
class ProgressService
{
    /**
     * Update progress after an indicator completes.
     */
    public function updateProgress(int $jobId, string $indicatorSlug, float $score): void
    {
        $job = AnalysisJob::with('results')->find($jobId);

        if (!$job) {
            Log::warning('Progress update failed: job not found', [
                'job_id' => $jobId,
                'indicator_slug' => $indicatorSlug,
            ]);
            return;
        }

        // Recalculate progress
        $job->progress_percent = $job->calculateProgress();
        $job->save();

        // Broadcast progress event
        event(new AnalysisProgressUpdated($job));

        Log::debug('Progress updated', [
            'job_id' => $jobId,
            'indicator_slug' => $indicatorSlug,
            'progress_percent' => $job->progress_percent,
        ]);
    }

    /**
     * Mark an indicator as complete with results.
     */
    public function markComplete(int $jobId, string $indicatorSlug, IndicatorResult $result): void
    {
        DB::transaction(function () use ($jobId, $indicatorSlug, $result) {
            $jobResult = AnalysisJobResult::where('job_id', $jobId)
                ->where('indicator_slug', $indicatorSlug)
                ->first();

            if (!$jobResult) {
                Log::warning('Mark complete failed: result not found', [
                    'job_id' => $jobId,
                    'indicator_slug' => $indicatorSlug,
                ]);
                return;
            }

            // Update result
            $jobResult->status = AnalysisJobResult::STATUS_COMPLETED;
            $jobResult->score = $result->score;
            $jobResult->max_score = $result->maxScore;
            $jobResult->percentage = $result->percentage;
            $jobResult->data = $result->data;
            $jobResult->recommendations = $result->recommendations;
            $jobResult->processing_time_ms = $result->processingTimeMs;
            $jobResult->completed_at = now();
            $jobResult->save();

            // Update job progress
            $job = AnalysisJob::find($jobId);
            $job->completed_indicators = $job->results()
                ->where('status', AnalysisJobResult::STATUS_COMPLETED)
                ->count();
            $job->failed_indicators = $job->results()
                ->where('status', AnalysisJobResult::STATUS_FAILED)
                ->count();
            $job->progress_percent = $job->calculateProgress();
            $job->save();

            // Broadcast events
            event(new AnalysisIndicatorComplete($jobResult));
            event(new AnalysisProgressUpdated($job));

            Log::info('Indicator completed', [
                'job_id' => $jobId,
                'indicator_slug' => $indicatorSlug,
                'score' => $result->score,
                'percentage' => $result->percentage,
            ]);
        });
    }

    /**
     * Mark an indicator as failed.
     */
    public function markFailed(int $jobId, string $indicatorSlug, string $error): void
    {
        DB::transaction(function () use ($jobId, $indicatorSlug, $error) {
            $jobResult = AnalysisJobResult::where('job_id', $jobId)
                ->where('indicator_slug', $indicatorSlug)
                ->first();

            if (!$jobResult) {
                Log::warning('Mark failed failed: result not found', [
                    'job_id' => $jobId,
                    'indicator_slug' => $indicatorSlug,
                ]);
                return;
            }

            // Update result
            $jobResult->status = AnalysisJobResult::STATUS_FAILED;
            $jobResult->error_message = $error;
            $jobResult->completed_at = now();
            $jobResult->save();

            // Update job progress
            $job = AnalysisJob::find($jobId);
            $job->completed_indicators = $job->results()
                ->where('status', AnalysisJobResult::STATUS_COMPLETED)
                ->count();
            $job->failed_indicators = $job->results()
                ->where('status', AnalysisJobResult::STATUS_FAILED)
                ->count();
            $job->progress_percent = $job->calculateProgress();
            $job->save();

            // Broadcast events
            event(new AnalysisIndicatorComplete($jobResult));
            event(new AnalysisProgressUpdated($job));

            Log::warning('Indicator failed', [
                'job_id' => $jobId,
                'indicator_slug' => $indicatorSlug,
                'error' => $error,
            ]);
        });
    }

    /**
     * Mark an indicator as running.
     */
    public function markRunning(int $jobId, string $indicatorSlug): void
    {
        AnalysisJobResult::where('job_id', $jobId)
            ->where('indicator_slug', $indicatorSlug)
            ->update([
                'status' => AnalysisJobResult::STATUS_RUNNING,
                'started_at' => now(),
            ]);

        Log::debug('Indicator running', [
            'job_id' => $jobId,
            'indicator_slug' => $indicatorSlug,
        ]);
    }

    /**
     * Check if job is cancelled.
     */
    public function isJobCancelled(int $jobId): bool
    {
        return AnalysisJob::where('id', $jobId)
            ->where('status', AnalysisJob::STATUS_CANCELLED)
            ->exists();
    }
}
