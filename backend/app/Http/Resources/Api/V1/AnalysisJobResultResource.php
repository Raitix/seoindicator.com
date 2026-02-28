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
            'indicatorSlug' => $this->indicator_slug,
            'indicatorName' => $this->indicator_name,
            'status' => $this->status,
            'score' => $this->score ? (float) $this->score : null,
            'maxScore' => $this->max_score ? (float) $this->max_score : null,
            'percentage' => $this->percentage,
            'data' => $this->data,
            'recommendations' => $this->recommendations,
            'errorMessage' => $this->error_message,
            'startedAt' => $this->started_at?->toISOString(),
            'completedAt' => $this->completed_at?->toISOString(),
            'processingTimeMs' => $this->processing_time_ms,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
