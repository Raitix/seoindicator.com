<?php

namespace App\Services\AI\DTOs;

use JsonSerializable;

/**
 * Data Transfer Object for unified AI analysis results.
 *
 * Contains all indicator results, scores, and metadata from
 * a single unified AI analysis call.
 *
 * @property-read int $overallScore Weighted overall SEO score (0-100)
 * @property-read string $overallSummary Executive summary of the analysis
 * @property-read array $categoryScores Scores by category (technical, content, etc.)
 * @property-read array $indicators All indicator results
 * @property-read string $model The AI model used
 * @property-read int $promptTokens Tokens used in the prompt
 * @property-read int $responseTokens Tokens in the response
 * @property-read int $latencyMs Response latency in milliseconds
 * @property-read array $truncationStrategies Truncation strategies applied
 * @property-read array $topPriorities Top priority recommendations
 *
 * @see TASK-31-7
 * @see TASK-31-13
 */
readonly class AnalysisResult implements JsonSerializable
{
    /**
     * Create a new AnalysisResult instance.
     *
     * @param int $overallScore Weighted overall SEO score (0-100)
     * @param string $overallSummary Executive summary
     * @param array $categoryScores Scores by category
     * @param array $indicators All indicator results
     * @param string $model The AI model used
     * @param int $promptTokens Tokens used in the prompt
     * @param int $responseTokens Tokens in the response
     * @param int $latencyMs Response latency in milliseconds
     * @param array $truncationStrategies Truncation strategies applied
     * @param array $topPriorities Top priority recommendations
     */
    public function __construct(
        public readonly int $overallScore,
        public readonly string $overallSummary,
        public readonly array $categoryScores,
        public readonly array $indicators,
        public readonly string $model,
        public readonly int $promptTokens,
        public readonly int $responseTokens,
        public readonly int $latencyMs,
        public readonly array $truncationStrategies = [],
        public readonly array $topPriorities = [],
    ) {
        // Validate score range
        if ($overallScore < 0 || $overallScore > 100) {
            throw new \InvalidArgumentException('overallScore must be between 0 and 100');
        }

        // Validate token counts
        if ($promptTokens < 0 || $responseTokens < 0) {
            throw new \InvalidArgumentException('Token counts must be non-negative');
        }

        // Validate latency
        if ($latencyMs < 0) {
            throw new \InvalidArgumentException('latencyMs must be non-negative');
        }
    }

    /**
     * Get total tokens used.
     *
     * @return int Total tokens (prompt + response)
     */
    public function getTotalTokens(): int
    {
        return $this->promptTokens + $this->responseTokens;
    }

    /**
     * Check if truncation was applied.
     *
     * @return bool True if truncation strategies were applied
     */
    public function wasTruncated(): bool
    {
        return !empty($this->truncationStrategies);
    }

    /**
     * Get a specific indicator by ID.
     *
     * @param string $id Indicator ID
     * @return array|null Indicator data or null if not found
     */
    public function getIndicator(string $id): ?array
    {
        foreach ($this->indicators as $indicator) {
            if (isset($indicator['id']) && $indicator['id'] === $id) {
                return $indicator;
            }
        }
        return null;
    }

    /**
     * Get category score by name.
     *
     * @param string $category Category name
     * @return int|null Score or null if not found
     */
    public function getCategoryScore(string $category): ?int
    {
        return $this->categoryScores[$category] ?? null;
    }

    /**
     * Create an instance from array data.
     *
     * @param array $data The data array
     * @return self
     * @throws \InvalidArgumentException If required fields are missing
     */
    public static function fromArray(array $data): self
    {
        $required = ['overallScore', 'overallSummary', 'indicators'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        return new self(
            overallScore: (int) $data['overallScore'],
            overallSummary: (string) $data['overallSummary'],
            categoryScores: $data['categoryScores'] ?? [],
            indicators: $data['indicators'],
            model: $data['model'] ?? 'unknown',
            promptTokens: (int) ($data['promptTokens'] ?? 0),
            responseTokens: (int) ($data['responseTokens'] ?? 0),
            latencyMs: (int) ($data['latencyMs'] ?? 0),
            truncationStrategies: $data['truncationStrategies'] ?? [],
            topPriorities: $data['topPriorities'] ?? [],
        );
    }

    /**
     * Create a copy with additional metadata.
     *
     * @param string|null $model Model name
     * @param int|null $promptTokens Prompt tokens
     * @param int|null $responseTokens Response tokens
     * @param int|null $latencyMs Latency in milliseconds
     * @param array|null $truncationStrategies Truncation strategies
     * @return self New instance with updated metadata
     */
    public function withMetadata(
        ?string $model = null,
        ?int $promptTokens = null,
        ?int $responseTokens = null,
        ?int $latencyMs = null,
        ?array $truncationStrategies = null,
    ): self {
        return new self(
            overallScore: $this->overallScore,
            overallSummary: $this->overallSummary,
            categoryScores: $this->categoryScores,
            indicators: $this->indicators,
            model: $model ?? $this->model,
            promptTokens: $promptTokens ?? $this->promptTokens,
            responseTokens: $responseTokens ?? $this->responseTokens,
            latencyMs: $latencyMs ?? $this->latencyMs,
            truncationStrategies: $truncationStrategies ?? $this->truncationStrategies,
            topPriorities: $this->topPriorities,
        );
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'overallScore' => $this->overallScore,
            'overallSummary' => $this->overallSummary,
            'categoryScores' => $this->categoryScores,
            'indicators' => $this->indicators,
            'model' => $this->model,
            'promptTokens' => $this->promptTokens,
            'responseTokens' => $this->responseTokens,
            'totalTokens' => $this->getTotalTokens(),
            'latencyMs' => $this->latencyMs,
            'truncationStrategies' => $this->truncationStrategies,
            'topPriorities' => $this->topPriorities,
        ];
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
