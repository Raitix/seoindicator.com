<?php

namespace App\Http\Resources\Api\V1\GBP;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @Task TASK-35-14
 * @Description API Resource for GBP Review responses
 */
class GBPReviewResource extends JsonResource
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
            'gbp_review_id' => $this->gbp_review_id,
            'rating' => $this->rating,
            'text' => $this->text,
            'author' => $this->author,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'sentiment' => $this->sentiment,
            'has_response' => $this->has_response,
            'response' => $this->when(
                $this->has_response && $this->response_text,
                [
                    'text' => $this->response_text,
                    'responded_at' => $this->responded_at?->toIso8601String(),
                ]
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
