<?php

namespace App\Services\Analysis\Indicators;

/**
 * Data Transfer Object for indicator processing results.
 */
class IndicatorResult
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly float $score,
        public readonly float $maxScore,
        public readonly int $percentage,
        public readonly array $data = [],
        public readonly array $recommendations = [],
        public readonly int $processingTimeMs = 0,
        public readonly ?string $error = null,
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $slug,
        string $name,
        float $score,
        float $maxScore,
        array $data = [],
        array $recommendations = [],
        int $processingTimeMs = 0,
    ): self {
        $percentage = $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0;

        return new self(
            slug: $slug,
            name: $name,
            score: $score,
            maxScore: $maxScore,
            percentage: $percentage,
            data: $data,
            recommendations: $recommendations,
            processingTimeMs: $processingTimeMs,
        );
    }

    /**
     * Create a failed result.
     */
    public static function failed(
        string $slug,
        string $name,
        float $maxScore,
        string $error,
        int $processingTimeMs = 0,
    ): self {
        return new self(
            slug: $slug,
            name: $name,
            score: 0,
            maxScore: $maxScore,
            percentage: 0,
            data: [],
            recommendations: [
                [
                    'priority' => 'high',
                    'message' => 'Analysis failed',
                    'details' => $error,
                ],
            ],
            processingTimeMs: $processingTimeMs,
            error: $error,
        );
    }

    /**
     * Convert to array format for API responses.
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'score' => round($this->score, 2),
            'max_score' => round($this->maxScore, 2),
            'percentage' => $this->percentage,
            'data' => $this->data,
            'recommendations' => $this->recommendations,
            'processing_time_ms' => $this->processingTimeMs,
            'error' => $this->error,
        ];
    }
}
