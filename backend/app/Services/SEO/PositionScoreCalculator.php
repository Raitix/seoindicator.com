<?php

namespace App\Services\SEO;

use Illuminate\Support\Collection;

/**
 * Position Score Calculator
 *
 * Calculates position-based scores for keyword rankings and computes
 * volume-weighted average scores across multiple keywords.
 *
 * Scoring Rules:
 * - Top 3 positions: Score = 100
 * - Positions 4-10: Weighted deduction based on rank
 * - Positions 11-20: Gradual score reduction
 * - Positions 21-50: Slower reduction
 * - Positions 51-100: Minimal scores
 * - Outside top 100: No score
 *
 * @see TASK-10-2
 * @see SPEC-010
 */
class PositionScoreCalculator
{
    /**
     * Calculate position-based score for a single position.
     *
     * @param int $position The search position (1-100 for valid rankings)
     * @return int Score from 0-100
     */
    public function calculate(int $position): int
    {
        // Handle invalid positions
        if ($position <= 0 || $position > 100) {
            return 0;
        }

        // Calculate score based on position ranges
        $score = match (true) {
            $position <= 3 => 100,
            $position <= 10 => 100 - (($position - 3) * 3.33),
            $position <= 20 => 80 - (($position - 10) * 3),
            $position <= 50 => 50 - (($position - 20) * 1),
            $position <= 100 => 20 - (($position - 50) * 0.38),
            default => 0,
        };

        // Ensure score is within bounds and return as integer
        return (int) max(0, min(100, round($score)));
    }

    /**
     * Calculate volume-weighted average score across multiple keywords.
     *
     * @param Collection $keywords Collection of keyword objects with:
     *   - current_position: int (the ranking position)
     *   - search_volume: int|null (defaults to 1 if null)
     * @return float Weighted average score (0.00 - 100.00)
     */
    public function calculateWeightedAverage(Collection $keywords): float
    {
        // Handle empty collection
        if ($keywords->isEmpty()) {
            return 0.00;
        }

        $weightedSum = 0;
        $totalVolume = 0;

        foreach ($keywords as $keyword) {
            // Get position (handle both object and array access)
            $position = $this->getProperty($keyword, 'current_position', 0);

            // Get volume (default to 1 if null or missing)
            $volume = $this->getProperty($keyword, 'search_volume', 1);
            if ($volume === null || $volume <= 0) {
                $volume = 1;
            }

            // Calculate score for this position
            $score = $this->calculate((int) $position);

            // Add to weighted sum
            $weightedSum += $score * $volume;
            $totalVolume += $volume;
        }

        // Avoid division by zero
        if ($totalVolume <= 0) {
            return 0.00;
        }

        // Return weighted average rounded to 2 decimal places
        return round($weightedSum / $totalVolume, 2);
    }

    /**
     * Allow the calculator to be invoked directly for position scoring.
     *
     * @param int $position
     * @return int
     */
    public function __invoke(int $position): int
    {
        return $this->calculate($position);
    }

    /**
     * Get a property from an object or array.
     *
     * @param mixed $item
     * @param string $property
     * @param mixed $default
     * @return mixed
     */
    private function getProperty(mixed $item, string $property, mixed $default): mixed
    {
        if (is_object($item)) {
            return $item->$property ?? $default;
        }

        if (is_array($item)) {
            return $item[$property] ?? $default;
        }

        return $default;
    }
}
