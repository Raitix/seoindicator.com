<?php

namespace App\Services\ContentDecay\Contracts;

/**
 * Interface for Content Decay Scoring Service.
 *
 * Calculates 0-100 decay scores from normalized signals with weight renormalization
 * when optional signals are missing or have no contribution.
 *
 * @see TASK-36-3
 * @see SPEC-036
 */
interface ContentDecayScoringServiceInterface
{
    /**
     * Calculate the decay score from normalized signals.
     *
     * @param array<string, mixed> $signals Normalized signal values (0-1)
     * @return array{
     *     decay_score: float,
     *     severity: string,
     *     detection_confidence: float,
     *     contributing_factors: array<int, array{factor: string, contribution: float}>,
     *     score_breakdown: array<string, array{value: float, weight: float, contribution: float}>
     * }
     */
    public function calculateDecayScore(array $signals): array;

    /**
     * Get the base weights for decay factors.
     *
     * @return array<string, float>
     */
    public function getBaseWeights(): array;

    /**
     * Get renormalized weights based on available signals.
     *
     * @param array<string, mixed> $signals
     * @return array<string, float>
     */
    public function getRenormalizedWeights(array $signals): array;

    /**
     * Normalize click loss signal.
     *
     * @param int|float $previous Previous clicks
     * @param int|float $current Current clicks
     * @return float Normalized value (0-1)
     */
    public function normalizeClickLoss(int|float $previous, int|float $current): float;

    /**
     * Normalize impression loss signal.
     *
     * @param int|float $previous Previous impressions
     * @param int|float $current Current impressions
     * @return float Normalized value (0-1)
     */
    public function normalizeImpressionLoss(int|float $previous, int|float $current): float;

    /**
     * Normalize CTR drop signal.
     *
     * @param int|float $previous Previous CTR (as percentage, e.g., 5.0 for 5%)
     * @param int|float $current Current CTR (as percentage)
     * @return float Normalized value (0-1)
     */
    public function normalizeCtrDrop(int|float $previous, int|float $current): float;

    /**
     * Normalize position decline signal.
     *
     * @param int $previous Previous position (1 is best)
     * @param int $current Current position
     * @return float Normalized value (0-1)
     */
    public function normalizePositionDecline(int $previous, int $current): float;
}
