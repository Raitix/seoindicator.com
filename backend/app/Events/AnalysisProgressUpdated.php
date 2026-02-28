<?php

namespace App\Events;

use App\Models\AnalysisJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when analysis progress is updated.
 *
 * Broadcasts progress updates to the job owner for real-time UI updates.
 */
class AnalysisProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $jobId;
    public int $completedIndicators;
    public int $totalIndicators;
    public int $progressPercent;
    public string $status;

    /**
     * Create a new event instance.
     */
    public function __construct(AnalysisJob $job)
    {
        $this->jobId = $job->id;
        $this->completedIndicators = $job->completed_indicators + $job->failed_indicators;
        $this->totalIndicators = $job->total_indicators;
        $this->progressPercent = $job->calculateProgress();
        $this->status = $job->status;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("analysis-job.{$this->jobId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'progress.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'completed_indicators' => $this->completedIndicators,
            'total_indicators' => $this->totalIndicators,
            'progress_percent' => $this->progressPercent,
            'status' => $this->status,
        ];
    }
}
