<?php

namespace App\Services\SEO;

use Illuminate\Support\Collection;

/**
 * Calculates visibility bonuses based on SERP feature presence.
 *
 * Assigns weighted bonus points for SERP features like featured snippets,
 * local packs, etc. Bonuses are capped per keyword and normalized to 0-100.
 *
 * @see TASK-10-3
 * @see SPEC-010
 */
class SerpFeatureScoreCalculator
{
    /**
     * Bonus points per SERP feature type.
     */
    private const BONUS_POINTS = [
        'featured_snippet' => 20,
        'local_pack' => 15,
        'sitelinks' => 10,
        'reviews' => 5,
        'video' => 5,
        'image_pack' => 5,
        'people_also_ask' => 3,
    ];

    /**
     * Maximum bonus points per keyword.
     */
    private const MAX_BONUS_PER_KEYWORD = 50;

    /**
     * Maximum final score.
     */
    private const MAX_SCORE = 100;

    /**
     * Calculate SERP feature bonus score.
     *
     * For each keyword:
     * 1. Sum bonus points for each SERP feature present
     * 2. Cap keyword bonus at 50 points
     *
     * Then:
     * 3. Average bonuses across keywords with features
     * 4. Scale to 0-100 range
     *
     * @param Collection $keywords Collection of keyword objects with serp_features property
     * @return float Score between 0-100
     */
    public function calculate(Collection $keywords): float
    {
        $totalBonus = 0;
        $keywordsWithFeatures = 0;

        foreach ($keywords as $keyword) {
            $keywordBonus = $this->calculateKeywordBonus($keyword);

            if ($keywordBonus > 0) {
                $totalBonus += min($keywordBonus, self::MAX_BONUS_PER_KEYWORD);
                $keywordsWithFeatures++;
            }
        }

        if ($keywordsWithFeatures === 0) {
            return 0.0;
        }

        $averageBonus = $totalBonus / $keywordsWithFeatures;
        $score = $averageBonus * 2;

        return min(self::MAX_SCORE, (float) $score);
    }

    /**
     * Get the bonus points for a specific SERP feature.
     *
     * @param string $feature The SERP feature name
     * @return int Bonus points (0 if unknown feature)
     */
    public function getFeatureBonus(string $feature): int
    {
        return self::BONUS_POINTS[$feature] ?? 0;
    }

    /**
     * Get all configured SERP features and their bonus points.
     *
     * @return array<string, int>
     */
    public function getAllFeatureBonuses(): array
    {
        return self::BONUS_POINTS;
    }

    /**
     * Calculate bonus for a single keyword.
     *
     * @param object $keyword Keyword object with serp_features property
     * @return int Total bonus points for this keyword
     */
    private function calculateKeywordBonus(object $keyword): int
    {
        $features = $keyword->serp_features ?? [];
        $bonus = 0;

        foreach ($features as $feature) {
            $bonus += $this->getFeatureBonus($feature);
        }

        return $bonus;
    }
}
