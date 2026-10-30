<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Analysis Cost Resource.
 *
 * Transforms AnalysisCost model into a consistent API response format.
 *
 * @covers TASK-25-16
 */
class AnalysisCostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'analysisId' => $this->analysis_id,
            'costType' => $this->cost_type,
            'costUnits' => $this->cost_units,
            'costAmount' => $this->cost_amount,
            'costCurrency' => $this->cost_currency,
            'description' => $this->description,
            'formattedAmount' => $this->formatted_amount,
        ];
    }
}
