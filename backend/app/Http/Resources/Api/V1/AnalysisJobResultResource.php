<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AnalysisJobResult API Resource
 *
 * Transforms analysis job result data for API responses.
 *
 * @see TASK-19-4
 */
class AnalysisJobResultResource extends JsonResource
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
            'job_id' => $this->job_id,
            'indicator_slug' => $this->indicator_slug,
            'indicator_name' => $this->indicator_name,
            'status' => $this->status,
            'score' => $this->score ? (float) $this->score : null,
            'max_score' => $this->max_score ? (float) $this->max_score : null,
            'percentage' => $this->percentage,
            'data' => $this->data,
            'recommendations' => $this->recommendations,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'processing_time_ms' => $this->processing_time_ms,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
