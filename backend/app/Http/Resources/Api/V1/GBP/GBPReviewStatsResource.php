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
        $data = $this->resource;

        return [
            'average_rating' => $data['average_rating'] ?? 0,
            'previous_rating' => $data['previous_rating'] ?? null,
            'total_count' => $data['total_count'] ?? 0,
            'previous_count' => $data['previous_count'] ?? null,
            'distribution' => $data['distribution'] ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            'sentiment_breakdown' => $data['sentiment_breakdown'] ?? ['positive' => 0, 'neutral' => 0, 'negative' => 0],
            'response_rate' => $data['response_rate'] ?? 0,
            'average_response_time_hours' => $data['average_response_time_hours'] ?? 0,
        ];
    }
}
