<?php

namespace App\Services\SEO;

use App\Services\SEO\Checkers\IndicatorResult;

/**
 * Service for calculating weighted SEO scores from indicator results.
 *
 * @see TASK-18-7
 */
class ScoreCalculatorService
{
    /**
     * Default indicator weights.
     *
     * @var array<string, float>
     */
    private const DEFAULT_WEIGHTS = [
        // Content indicators
        'title_tag' => 0.10,
        'meta_description' => 0.08,
        'h1_headings' => 0.08,
        'keyword_usage' => 0.06,
        'content_quality' => 0.08,
        
        // Technical indicators
        'url' => 0.08,
        'canonical' => 0.06,
        'robots_txt' => 0.05,
        'sitemap' => 0.05,
        'ssl' => 0.05,
        'mobile_friendly' => 0.08,
        
        // Accessibility indicators
        'image_alt' => 0.05,
        'aria' => 0.04,
        'contrast' => 0.04,
        
        // Performance indicators
        'page_speed' => 0.10,
    ];

    /**
     * Custom weights configuration.
     *
     * @var array<string, float>
     */
    private array $customWeights;

    /**
     * @param array<string, float>|null $customWeights Optional custom indicator weights
     */
    public function __construct(?array $customWeights = null)
    {
        $this->customWeights = $customWeights ?? [];
    }

    /**
     * Calculate scores from indicator results.
     *
     * @param array<IndicatorResult> $indicators
     */
    public function calculate(array $indicators): ScoreResult
    {
        // Validate input
        foreach ($indicators as $indicator) {
            if (!$indicator instanceof IndicatorResult) {
                throw new \InvalidArgumentException('All items must be IndicatorResult instances');
            }
        }

        // Handle empty input
        if (empty($indicators)) {
            return new ScoreResult(
                overallScore: 0,
                categoryScores: [],
                breakdown: [],
                summary: ['passed' => 0, 'warnings' => 0, 'failed' => 0]
            );
        }

        // Filter out N/A indicators (score = 0)
        $activeIndicators = array_filter($indicators, fn ($i) => $i->score > 0);

        // Calculate overall score
        $overallScore = $this->calculateOverallScore($activeIndicators);

        // Calculate category scores
        $categoryScores = $this->calculateCategoryScores($activeIndicators);

        // Build breakdown
        $breakdown = $this->buildBreakdown($indicators);

        // Build summary
        $summary = $this->buildSummary($indicators);

        return new ScoreResult(
            overallScore: $overallScore,
            categoryScores: $categoryScores,
            breakdown: $breakdown,
            summary: $summary
        );
    }

    /**
     * Calculate overall weighted score.
     *
     * @param array<IndicatorResult> $indicators
     */
    private function calculateOverallScore(array $indicators): int
    {
        if (empty($indicators)) {
            return 0;
        }

        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($indicators as $indicator) {
            $weight = $this->getWeight($indicator);
            $weightedSum += $indicator->score * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0.0) {
            return 0;
        }

        // Normalize to 0-100 scale (score is 1-3, so divide by 3 and multiply by 100)
        $averageScore = $weightedSum / $totalWeight;
        $normalizedScore = ($averageScore / 3.0) * 100;

        return (int) round($normalizedScore);
    }

    /**
     * Calculate scores per category.
     *
     * @param array<IndicatorResult> $indicators
     * @return array<string, int|null>
     */
    private function calculateCategoryScores(array $indicators): array
    {
        $categories = ['content', 'technical', 'accessibility', 'performance'];
        $scores = [];

        foreach ($categories as $category) {
            $categoryIndicators = array_filter(
                $indicators,
                fn ($i) => $i->category === $category
            );

            if (empty($categoryIndicators)) {
                $scores[$category] = null;
                continue;
            }

            $weightedSum = 0.0;
            $totalWeight = 0.0;

            foreach ($categoryIndicators as $indicator) {
                $weight = $this->getWeight($indicator);
                $weightedSum += $indicator->score * $weight;
                $totalWeight += $weight;
            }

            if ($totalWeight === 0.0) {
                $scores[$category] = null;
                continue;
            }

            $averageScore = $weightedSum / $totalWeight;
            $normalizedScore = ($averageScore / 3.0) * 100;
            $scores[$category] = (int) round($normalizedScore);
        }

        return $scores;
    }

    /**
     * Build breakdown array with score and weight per indicator.
     *
     * @param array<IndicatorResult> $indicators
     * @return array<string, array{score: int, weight: float}>
     */
    private function buildBreakdown(array $indicators): array
    {
        $breakdown = [];

        foreach ($indicators as $indicator) {
            $breakdown[$indicator->id] = [
                'score' => $indicator->score,
                'weight' => $this->getWeight($indicator),
            ];
        }

        return $breakdown;
    }

    /**
     * Build summary counts.
     *
     * @param array<IndicatorResult> $indicators
     * @return array{passed: int, warnings: int, failed: int}
     */
    private function buildSummary(array $indicators): array
    {
        $passed = 0;
        $warnings = 0;
        $failed = 0;

        foreach ($indicators as $indicator) {
            // Skip N/A indicators from summary
            if ($indicator->score === 0) {
                continue;
            }

            if ($indicator->passed && $indicator->score === 3) {
                $passed++;
            } elseif ($indicator->score === 2) {
                $warnings++;
            } else {
                // score = 1 (poor) or passed = false with score 2
                if ($indicator->score === 1) {
                    $failed++;
                } elseif ($indicator->passed) {
                    $passed++;
                } else {
                    $warnings++;
                }
            }
        }

        // Recalculate based on actual passed status
        $passed = 0;
        $warnings = 0;
        $failed = 0;

        foreach ($indicators as $indicator) {
            // Skip N/A indicators from summary
            if ($indicator->score === 0) {
                continue;
            }

            if ($indicator->passed) {
                $passed++;
            } elseif ($indicator->score === 2) {
                $warnings++;
            } else {
                $failed++;
            }
        }

        return [
            'passed' => $passed,
            'warnings' => $warnings,
            'failed' => $failed,
        ];
    }

    /**
     * Get weight for an indicator, using custom weights or defaults.
     */
    private function getWeight(IndicatorResult $indicator): float
    {
        // Check custom weights first
        if (isset($this->customWeights[$indicator->id])) {
            return $this->customWeights[$indicator->id];
        }

        // Check default weights
        if (isset(self::DEFAULT_WEIGHTS[$indicator->id])) {
            return self::DEFAULT_WEIGHTS[$indicator->id];
        }

        // Fall back to indicator's own weight
        return $indicator->weight;
    }
}
