<?php

namespace App\Http\Resources\Api\V1\LocalSEO;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @Task TASK-35-16
 * @Description Local SEO Alert API Resource
 *
 * Formats LocalSEOAlert model for API responses.
 */
class LocalSEOAlertResource extends JsonResource
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
            'type' => $this->alert_type,
            'severity' => $this->severity,
            'title' => $this->title,
            'message' => $this->message,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'location' => $this->when(
                $this->relationLoaded('location') || isset($this->location),
                fn() => [
                    'id' => $this->location?->id,
                    'name' => $this->location?->name,
                ]
            ),
        ];
    }
}
