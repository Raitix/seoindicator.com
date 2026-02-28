<?php

namespace App\Jobs;

use App\Models\AnalysisJob;
use App\Models\AnalysisJobResult;
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
    public function handle(AnalysisJobService $service): void
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
            ->then(function (Batch $batch) use ($job) {
                // All jobs completed successfully
                $job->refresh();
                if (!$job->isCompleted()) {
                    $job->markAsCompleted();
                }
                Log::info('Analysis batch completed', [
                    'job_id' => $job->id,
                    'batch_id' => $batch->id,
                ]);
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($job) {
                // Batch failed
                Log::error('Analysis batch failed', [
                    'job_id' => $job->id,
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            })
            ->finally(function (Batch $batch) use ($job) {
                // Always runs - update final status
                $job->refresh();
                if ($job->isRunning()) {
                    $job->markAsCompleted();
                }
            })
            ->allowFailures() // Continue even if some jobs fail
            ->onQueue('analysis')
            ->dispatch();

        Log::info('Analysis batch started', [
            'job_id' => $job->id,
            'batch_id' => $batch->id,
            'indicators' => count($indicatorJobs),
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
