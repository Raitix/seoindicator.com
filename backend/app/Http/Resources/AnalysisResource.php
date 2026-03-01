<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Analysis History Resource.
 *
 * Transforms AnalysisHistory model into a consistent API response format.
 *
 * @covers TASK-25-16
 */
class AnalysisResource extends JsonResource
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
            'toolName' => $this->tool_name,
            'inputUrl' => $this->input_url,
            'status' => $this->status,
            'results' => $this->results,
            'errorMessage' => $this->error_message,
            'analysisDurationMs' => $this->analysis_duration_ms,
            'siteId' => $this->site_id,
            'analyzedAt' => $this->analyzed_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'cost' => $this->getCostSummary(),
            'costs' => $this->getCostsArray(),
        ];
    }

    /**
     * Get the cost summary for this analysis.
     *
     * @return array
     */
    protected function getCostSummary(): array
    {
        if (!$this->relationLoaded('costs') || !$this->costs || $this->costs->isEmpty()) {
            return [
                'total' => '0.000000',
                'currency' => 'USD',
            ];
        }

        $total = $this->costs->sum('cost_amount');
        $currency = $this->costs->first()->cost_currency ?? 'USD';

        return [
            'total' => number_format((float) $total, 6, '.', ''),
            'currency' => $currency,
        ];
    }

    /**
     * Get the costs array for this analysis.
     *
     * @return array
     */
    protected function getCostsArray(): array
    {
        if (!$this->relationLoaded('costs') || !$this->costs) {
            return [];
        }

        return $this->costs->map(fn($cost) => [
            'id' => $cost->id,
            'costType' => $cost->cost_type,
            'costUnits' => $cost->cost_units,
            'costAmount' => $cost->cost_amount,
            'costCurrency' => $cost->cost_currency,
            'description' => $cost->description,
        ])->toArray();
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function with($request): array
    {
        return [
            'success' => true,
        ];
    }
}
