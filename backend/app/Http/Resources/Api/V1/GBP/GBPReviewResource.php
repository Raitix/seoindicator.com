<?php

namespace App\Http\Resources\Api\V1\GBP;

use Carbon\Carbon;
use Carbon\CarbonInterface;
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
     * Supports both Eloquent GBPReview models and query-builder rows with
     * database column names so the frontend receives a stable payload.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $resource = $this->resource;
        $responseText = data_get($resource, 'response_text')
            ?? data_get($resource, 'owner_response')
            ?? data_get($resource, 'response.text');

        $respondedAt = data_get($resource, 'responded_at')
            ?? data_get($resource, 'owner_response_date')
            ?? data_get($resource, 'response.responded_at');

        $hasResponse = filter_var(
            data_get($resource, 'has_response', !empty($responseText)),
            FILTER_VALIDATE_BOOLEAN
        );

        return [
            'id' => data_get($resource, 'id'),
            'gbp_review_id' => data_get($resource, 'gbp_review_id') ?? data_get($resource, 'google_review_id'),
            'rating' => (int) data_get($resource, 'rating', 0),
            'text' => data_get($resource, 'text') ?? data_get($resource, 'review_text', ''),
            'author' => data_get($resource, 'author') ?? data_get($resource, 'author_name', 'Anonymous reviewer'),
            'avatar_url' => data_get($resource, 'avatar_url') ?? data_get($resource, 'author_photo'),
            'reviewed_at' => $this->toIsoString(data_get($resource, 'reviewed_at') ?? data_get($resource, 'review_date')),
            'sentiment' => data_get($resource, 'sentiment') ?? data_get($resource, 'review_text_sentiment'),
            'has_response' => $hasResponse,
            'response' => $this->when($hasResponse && !empty($responseText), [
                'text' => $responseText,
                'responded_at' => $this->toIsoString($respondedAt),
                'responder_name' => data_get($resource, 'response.responder_name'),
            ]),
            'created_at' => $this->toIsoString(data_get($resource, 'created_at')),
            'updated_at' => $this->toIsoString(data_get($resource, 'updated_at')),
        ];
    }

    /**
     * Convert various datetime value types into ISO-8601 strings.
     */
    protected function toIsoString(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->toIso8601String();
            } catch (\Throwable) {
                return $value;
            }
        }

        return null;
    }
}
