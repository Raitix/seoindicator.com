<?php

namespace App\Events;

use App\Models\AnalysisJobResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an individual indicator analysis completes.
 */
class AnalysisIndicatorComplete implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $jobId;
    public string $indicatorSlug;
    public string $indicatorName;
    public string $status;
    public ?float $score;
    public ?float $maxScore;
    public ?int $percentage;
    public int $processingTimeMs;

    /**
     * Create a new event instance.
     */
    public function __construct(AnalysisJobResult $result)
    {
        $this->jobId = $result->job_id;
        $this->indicatorSlug = $result->indicator_slug;
        $this->indicatorName = $result->indicator_name;
        $this->status = $result->status;
        $this->score = $result->score ? (float) $result->score : null;
        $this->maxScore = $result->max_score ? (float) $result->max_score : null;
        $this->percentage = $result->percentage;
        $this->processingTimeMs = $result->processing_time_ms ?? 0;
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
        return 'indicator.complete';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'indicator_slug' => $this->indicatorSlug,
            'indicator_name' => $this->indicatorName,
            'status' => $this->status,
            'score' => $this->score,
            'max_score' => $this->maxScore,
            'percentage' => $this->percentage,
            'processing_time_ms' => $this->processingTimeMs,
        ];
    }
}
