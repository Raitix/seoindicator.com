<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AnalysisJob API Resource
 *
 * Transforms analysis job data for API responses.
 *
 * @see TASK-19-4
 */
class AnalysisJobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'totalIndicators' => $this->total_indicators,
            'completedIndicators' => $this->completed_indicators,
            'failedIndicators' => $this->failed_indicators,
            'progressPercent' => $this->calculateProgress(),
            'startedAt' => $this->started_at?->toISOString(),
            'completedAt' => $this->completed_at?->toISOString(),
            'cancelledAt' => $this->cancelled_at?->toISOString(),
            'errorMessage' => $this->error_message,
            'durationSeconds' => $this->duration,
            'durationHuman' => $this->duration_human,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'results' => AnalysisJobResultResource::collection($this->whenLoaded('results')),
        ];
    }
}
