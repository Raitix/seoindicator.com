<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Annotation Collection Resource
 *
 * Transforms annotation collections for API responses.
 *
 * @see TASK-9-4
 */
class AnnotationCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => AnnotationResource::collection($this->resource->items()),
            'meta' => [
                'total' => (int) $this->resource->total(),
                'page' => (int) $this->resource->currentPage(),
                'perPage' => (int) $this->resource->perPage(),
                'last_page' => (int) $this->resource->lastPage(),
            ],
        ];
    }
}
