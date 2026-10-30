<?php

namespace App\Http\Resources\Api\V1\GBP;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @Task TASK-35-14
 * @Description API Resource for GBP Trend responses
 */
class GBPTrendResource extends JsonResource
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
            'wow' => $data['wow'] ?? [],
            'mom' => $data['mom'] ?? [],
        ];
    }
}
