<?php

namespace App\Http\Resources\Api\V1\GBP;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @Task TASK-35-14
 * @Description API Resource for GBP Insights responses
 */
class GBPInsightsResource extends JsonResource
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
        $totals = $data['totals'] ?? [];

        return [
            'daily_breakdown' => $data['daily_breakdown'] ?? [],
            'totals' => [
                'queries' => $totals['queries'] ?? 0,
                'views' => $totals['views'] ?? 0,
                'actions' => $totals['actions'] ?? 0,
                'photo_views' => $totals['photo_views'] ?? 0,
                'driving_directions' => $totals['driving_directions'] ?? 0,
                'phone_calls' => $totals['phone_calls'] ?? 0,
                'map_views' => $totals['map_views'] ?? 0,
                'search_views' => $totals['search_views'] ?? 0,
            ],
        ];
    }
}
