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
        return [
            'daily_breakdown' => $this->daily_breakdown ?? [],
            'totals' => [
                'queries' => $this->totals['queries'] ?? 0,
                'views' => $this->totals['views'] ?? 0,
                'actions' => $this->totals['actions'] ?? 0,
                'photo_views' => $this->totals['photo_views'] ?? 0,
                'driving_directions' => $this->totals['driving_directions'] ?? 0,
                'phone_calls' => $this->totals['phone_calls'] ?? 0,
                'map_views' => $this->totals['map_views'] ?? 0,
                'search_views' => $this->totals['search_views'] ?? 0,
            ],
        ];
    }
}
