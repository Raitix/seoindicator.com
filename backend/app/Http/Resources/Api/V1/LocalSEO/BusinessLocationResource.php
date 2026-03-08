<?php

namespace App\Http\Resources\Api\V1\LocalSEO;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @Task TASK-35-13
 * @Description Business Location API Resource (Detail View)
 * 
 * @Security GBP tokens are NEVER exposed in API responses
 */
class BusinessLocationResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'website' => $this->website,
            'category' => $this->category,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'hours' => $this->settings['hours'] ?? null,
            'is_gbp_connected' => $this->gbp_connected ?? false,
            'gbp_location_id' => $this->gbp_place_id,
            'is_active' => $this->is_active ?? true,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'insights_summary' => $this->when(
                $this->relationLoaded('insightsSummary') || isset($this->insights_summary),
                fn() => $this->insights_summary ?? [
                    'total_queries' => 0,
                    'total_views' => 0,
                    'total_actions' => 0,
                    'last_sync_at' => null,
                ]
            ),
            'review_stats' => $this->when(
                $this->relationLoaded('reviewStats') || isset($this->review_stats),
                fn() => $this->review_stats ?? [
                    'average_rating' => 0,
                    'total_reviews' => 0,
                    'response_rate' => 0,
                ]
            ),
            'last_gbp_sync_at' => $this->gbp_token_expires_at?->toISOString(),
        ];
    }
}
