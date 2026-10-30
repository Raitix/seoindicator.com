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
 * @see TASK-31-5
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
        public readonly int $latencyMs = 0,
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
     * Supports both snake_case (from AI responses) and camelCase keys.
     *
     * @param array $data The data array
     * @return self
     * @throws \InvalidArgumentException If required fields are missing
     */
    public static function fromArray(array $data): self
    {
        // Support both snake_case and camelCase keys
        $overallScore = $data['overallScore'] ?? $data['overall_score'] ?? null;
        $overallSummary = $data['overallSummary'] ?? $data['overall_summary'] ?? null;
        $indicators = $data['indicators'] ?? null;

        // Validate required fields
        if ($overallScore === null) {
            throw new \InvalidArgumentException('Missing required field: overallScore');
        }
        if ($overallSummary === null) {
            throw new \InvalidArgumentException('Missing required field: overallSummary');
        }
        if ($indicators === null) {
            throw new \InvalidArgumentException('Missing required field: indicators');
        }

        // Get optional fields with both naming conventions
        $categoryScores = $data['categoryScores'] ?? $data['category_scores'] ?? [];
        $topPriorities = $data['topPriorities'] ?? $data['top_priorities'] ?? [];
        $model = $data['model'] ?? 'unknown';
        $promptTokens = $data['promptTokens'] ?? $data['prompt_tokens'] ?? 0;
        $responseTokens = $data['responseTokens'] ?? $data['response_tokens'] ?? 0;
        $latencyMs = $data['latencyMs'] ?? $data['latency_ms'] ?? 0;
        $truncationStrategies = $data['truncationStrategies'] ?? $data['truncation_strategies'] ?? [];

        // Handle null values for optional fields
        $categoryScores = $categoryScores ?? [];
        $topPriorities = $topPriorities ?? [];

        return new self(
            overallScore: (int) $overallScore,
            overallSummary: (string) $overallSummary,
            categoryScores: (array) $categoryScores,
            indicators: (array) $indicators,
            model: (string) $model,
            promptTokens: (int) $promptTokens,
            responseTokens: (int) $responseTokens,
            latencyMs: (int) $latencyMs,
            truncationStrategies: (array) $truncationStrategies,
            topPriorities: (array) $topPriorities,
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
     * Uses snake_case keys for API compatibility.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'overall_score' => $this->overallScore,
            'overall_summary' => $this->overallSummary,
            'category_scores' => $this->categoryScores,
            'indicators' => $this->indicators,
            'model' => $this->model,
            'prompt_tokens' => $this->promptTokens,
            'response_tokens' => $this->responseTokens,
            'total_tokens' => $this->getTotalTokens(),
            'latency_ms' => $this->latencyMs,
            'truncation_strategies' => $this->truncationStrategies,
            'top_priorities' => $this->topPriorities,
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
