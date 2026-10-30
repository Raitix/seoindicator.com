<?php

namespace App\Http\Resources\Api\V1\LocalSEO;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @Task TASK-35-13
 * @Description Business Location API Resource (List View)
 * 
 * @Security GBP tokens are NEVER exposed in API responses
 */
class BusinessLocationListResource extends JsonResource
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
            'site_id' => $this->site_id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'website' => $this->website,
            'category' => $this->category,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_gbp_connected' => $this->gbp_connected ?? false,
            'gbp_location_id' => $this->gbp_place_id,
            'is_active' => $this->is_active ?? true,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
