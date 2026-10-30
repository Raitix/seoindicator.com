<?php

namespace App\Services\AI\DTOs;

/**
 * Data Transfer Object for truncation results.
 * 
 * Contains the truncated HTML content and metadata about
 * the truncation process.
 * 
 * @see TASK-31-3
 * @see SPEC-031
 */
readonly class TruncationResult
{
    /**
     * @param string $html The truncated HTML content
     * @param int $originalTokens Token count before truncation
     * @param int $finalTokens Token count after truncation
     * @param array<string> $strategiesApplied List of strategies that were applied
     * @param float $reductionPercent Percentage of content reduced (0-100)
     */
    public function __construct(
        public string $html,
        public int $originalTokens,
        public int $finalTokens,
        public array $strategiesApplied,
        public float $reductionPercent,
    ) {}
}
