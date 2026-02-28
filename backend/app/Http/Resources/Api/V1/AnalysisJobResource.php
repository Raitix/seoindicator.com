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
            'user_id' => $this->user_id,
            'site_id' => $this->site_id,
            'status' => $this->status,
            'total_indicators' => $this->total_indicators,
            'completed_indicators' => $this->completed_indicators,
            'failed_indicators' => $this->failed_indicators,
            'progress_percent' => $this->calculateProgress(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'error_message' => $this->error_message,
            'duration_seconds' => $this->duration,
            'duration_human' => $this->duration_human,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'results' => AnalysisJobResultResource::collection($this->whenLoaded('results')),
        ];
    }
}
