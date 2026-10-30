<?php

namespace App\Jobs;

use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
use App\Services\Analysis\ErrorHandlingService;
use App\Services\AnalysisJobService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * Start an analysis job by dispatching indicator processing jobs.
 *
 * Uses Laravel Bus batching for concurrent processing with limits.
 * Handles partial completion gracefully - jobs can complete even if some
 * indicators fail.
 * 
 * @see TASK-19-7
 * @see TASK-19-13 Error handling and partial completion
 */
class StartAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Analysis job ID.
     */
    public int $analysisJobId;

    /**
     * Maximum concurrent indicator jobs.
     */
    public int $concurrencyLimit = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(int $analysisJobId)
    {
        $this->analysisJobId = $analysisJobId;
        $this->onQueue('analysis');
    }

    /**
     * Execute the job.
     */
    public function handle(AnalysisJobService $service, ErrorHandlingService $errorHandler): void
    {
        $job = AnalysisJob::with('results')->find($this->analysisJobId);

        if (!$job) {
            Log::error('StartAnalysisJob: Job not found', [
                'job_id' => $this->analysisJobId,
            ]);
            return;
        }

        // Check if job is already cancelled
        if ($job->isCancelled()) {
            Log::info('StartAnalysisJob: Job already cancelled', [
                'job_id' => $this->analysisJobId,
            ]);
            return;
        }

        // Start the job
        $job->markAsRunning();

        // Get pending indicators
        $pendingResults = $job->results()->pending()->get();

        if ($pendingResults->isEmpty()) {
            Log::warning('StartAnalysisJob: No pending indicators', [
                'job_id' => $this->analysisJobId,
            ]);
            $job->markAsCompleted();
            return;
        }

        // Create batch of indicator jobs
        $indicatorJobs = $pendingResults->map(function ($result) {
            return new ProcessIndicatorJob($result->job_id, $result->indicator_slug);
        })->toArray();

        // Dispatch as a batch with concurrency limit
        $batch = Bus::batch($indicatorJobs)
            ->then(function (Batch $batch) use ($job, $errorHandler) {
                // All jobs completed successfully
                $job->refresh();
                
                // Check for partial completion
                $summary = $errorHandler->getPartialCompletionSummary($job);
                
                if ($summary['is_partial']) {
                    Log::info('Analysis batch completed with partial results', [
                        'job_id' => $job->id,
                        'batch_id' => $batch->id,
                        'completed' => $summary['completed_count'],
                        'failed' => $summary['failed_count'],
                        'success_rate' => $summary['success_rate'] . '%',
                    ]);
                } else {
                    Log::info('Analysis batch completed successfully', [
                        'job_id' => $job->id,
                        'batch_id' => $batch->id,
                    ]);
                }
                
                if (!$job->isCompleted()) {
                    $job->markAsCompleted();
                }
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($job, $errorHandler) {
                // Batch failed - but we allow failures, so this is for critical errors only
                Log::error('Analysis batch critical failure', [
                    'job_id' => $job->id,
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);

                // Check if we can still provide partial results
                if ($errorHandler->canContinueWithPartialResults($job)) {
                    $summary = $errorHandler->getPartialCompletionSummary($job);
                    Log::info('Providing partial results after batch failure', [
                        'job_id' => $job->id,
                        'completed' => $summary['completed_count'],
                        'failed' => $summary['failed_count'],
                    ]);
                    $job->markAsCompleted();
                } else {
                    $job->markAsFailed($errorHandler->getUserFriendlyMessage($e));
                }
            })
            ->finally(function (Batch $batch) use ($job, $errorHandler) {
                // Always runs - update final status
                $job->refresh();
                if ($job->isRunning()) {
                    // Calculate final summary
                    $summary = $errorHandler->getPartialCompletionSummary($job);
                    
                    // Log final state
                    Log::info('Analysis job final state', [
                        'job_id' => $job->id,
                        'batch_id' => $batch->id,
                        'total_indicators' => $summary['total_indicators'],
                        'completed' => $summary['completed_count'],
                        'failed' => $summary['failed_count'],
                        'success_rate' => $summary['success_rate'] . '%',
                        'average_score' => $summary['average_score'],
                        'is_partial' => $summary['is_partial'],
                    ]);
                    
                    $job->markAsCompleted();
                }
            })
            ->allowFailures() // Continue even if some jobs fail - this enables partial completion
            ->onQueue('analysis')
            ->dispatch();

        Log::info('Analysis batch started', [
            'job_id' => $job->id,
            'batch_id' => $batch->id,
            'indicators' => count($indicatorJobs),
            'concurrency_limit' => $this->concurrencyLimit,
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['analysis-job:' . $this->analysisJobId];
    }
}
