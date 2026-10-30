<?php

namespace App\Http\Resources\V1\AIVisibility;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for AI Visibility Alert.
 *
 * @see TASK-34-20
 * @see SPEC-034
 */
class AIVisibilityAlertResource extends JsonResource
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
            'project_id' => $this->project_id,
            'project_name' => $this->project?->brand_name,
            'alert_type' => $this->alert_type,
            'threshold' => (float) $this->threshold,
            'previous_value' => (float) $this->previous_value,
            'current_value' => (float) $this->current_value,
            'change_percent' => (float) $this->change_percent,
            'is_read' => $this->is_read,
            'is_resolved' => $this->is_resolved,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'triggered_at' => $this->triggered_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
