<?php

namespace App\Http\Resources\Api\V1\GBP;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @Task TASK-35-14
 * @Description API Resource for GBP Review Stats responses
 */
class GBPReviewStatsResource extends JsonResource
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
            'average_rating' => $this->average_rating ?? 0,
            'previous_rating' => $this->previous_rating ?? null,
            'total_count' => $this->total_count ?? 0,
            'previous_count' => $this->previous_count ?? null,
            'distribution' => $this->distribution ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            'sentiment_breakdown' => $this->sentiment_breakdown ?? ['positive' => 0, 'neutral' => 0, 'negative' => 0],
            'response_rate' => $this->response_rate ?? 0,
            'average_response_time_hours' => $this->average_response_time_hours ?? 0,
        ];
    }
}
