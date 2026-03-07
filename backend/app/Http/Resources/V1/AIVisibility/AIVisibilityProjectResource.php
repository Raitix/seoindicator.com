<?php

namespace App\Http\Resources\V1\AIVisibility;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for AI Visibility Project.
 *
 * @see TASK-34-6
 * @see SPEC-034
 */
class AIVisibilityProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_name' => $this->brand_name,
            'keywords' => $this->keywords ?? [],
            'competitors' => $this->competitors ?? [],
            'query_templates' => $this->query_templates,
            'tracking_frequency' => $this->tracking_frequency,
            'is_active' => $this->is_active,
            'last_queried_at' => $this->last_queried_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
