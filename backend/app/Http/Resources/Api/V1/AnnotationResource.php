<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Annotation API Resource
 *
 * Transforms annotation data for API responses.
 *
 * @see TASK-9-4
 */
class AnnotationResource extends JsonResource
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
            'keyword_id' => $this->keyword_id,
            'project_id' => $this->project_id,
            'date' => $this->date?->format('Y-m-d'),
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
