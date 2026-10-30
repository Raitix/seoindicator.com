<?php

namespace App\Services;

use App\Models\PWAAnalysis;

/**
 * Service for calculating PWA readiness score.
 *
 * Calculates a score from 0-100 based on manifest analysis results.
 * Determines readiness level (none, basic, enhanced, full).
 *
 * @see TASK-24-6
 * @see SPEC-024
 * @see TEST-24-6
 */
class PWAScorerService
{
    /**
     * Scoring constants.
     * 
     * Total: 20 + 15 + 15 + 25 + 10 = 85
     * Normalized to 0-100 scale.
     */
    private const MANIFEST_PRESENCE_POINTS = 10;
    private const MANIFEST_VALIDITY_POINTS = 10;
    private const REQUIRED_FIELD_POINTS = 3.75; // 4 fields × 3.75 = 15
    private const RECOMMENDED_FIELD_POINTS = 5; // 3 fields × 5 = 15
    private const ICON_192_POINTS = 10;
    private const ICON_512_POINTS = 10;
    private const ICON_180_POINTS = 5;
    private const ICON_VALIDITY_MAX_POINTS = 10;
    
    /**
     * Maximum raw score (before normalization).
     */
    private const MAX_RAW_SCORE = 85;

    /**
     * Calculate PWA readiness score (0-100).
     *
     * Scoring breakdown:
     * - Manifest: 20 pts (10 presence + 10 validity)
     * - Required fields: 15 pts (name, short_name, start_url, display - 3.75 each)
     * - Recommended fields: 15 pts (background_color, theme_color, description - 5 each)
     * - Required icons: 25 pts (192x192: 10, 512x512: 10, 180x180: 5)
     * - Icon validity bonus: 10 pts (proportional to icons_valid/icons_total)
     */
    public function calculateScore(PWAAnalysis $analysis): int
    {
        $score = 0;

        // Manifest scoring (20 pts max)
        $score += $this->calculateManifestScore($analysis);

        // Required fields scoring (15 pts max)
        $score += $this->calculateRequiredFieldsScore($analysis);

        // Recommended fields scoring (15 pts max)
        $score += $this->calculateRecommendedFieldsScore($analysis);

        // Icon size scoring (25 pts max)
        $score += $this->calculateIconSizeScore($analysis);

        // Icon validity bonus (10 pts max)
        $score += $this->calculateIconValidityScore($analysis);

        // Ensure score is within bounds
        return (int) max(0, min(100, round($score)));
    }

    /**
     * Determine readiness level based on score.
     *
     * Levels:
     * - none: 0-39
     * - basic: 40-69
     * - enhanced: 70-89
     * - full: 90-100
     */
    public function determineReadinessLevel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'full',
            $score >= 70 => 'enhanced',
            $score >= 40 => 'basic',
            default => 'none',
        };
    }

    /**
     * Get detailed score breakdown.
     *
     * Returns structured array with points per category.
     */
    public function getScoreBreakdown(PWAAnalysis $analysis): array
    {
        $manifestBreakdown = $this->getManifestBreakdown($analysis);
        $requiredFieldsBreakdown = $this->getRequiredFieldsBreakdown($analysis);
        $recommendedFieldsBreakdown = $this->getRecommendedFieldsBreakdown($analysis);
        $iconsBreakdown = $this->getIconsBreakdown($analysis);
        $iconValidityBreakdown = $this->getIconValidityBreakdown($analysis);

        $total = $manifestBreakdown['presence'] + $manifestBreakdown['validity']
            + $requiredFieldsBreakdown['earned']
            + $recommendedFieldsBreakdown['earned']
            + $iconsBreakdown['earned']
            + $iconValidityBreakdown['earned'];

        return [
            'manifest' => $manifestBreakdown,
            'required_fields' => $requiredFieldsBreakdown,
            'recommended_fields' => $recommendedFieldsBreakdown,
            'icons' => $iconsBreakdown,
            'icon_validity' => $iconValidityBreakdown,
            'total' => (int) round($total),
            'max_total' => 100,
        ];
    }

    /**
     * Calculate manifest score (presence + validity).
     */
    private function calculateManifestScore(PWAAnalysis $analysis): float
    {
        $score = 0;

        if ($analysis->has_manifest) {
            $score += self::MANIFEST_PRESENCE_POINTS;

            if ($analysis->manifest_valid) {
                $score += self::MANIFEST_VALIDITY_POINTS;
            }
        }

        return $score;
    }

    /**
     * Calculate required fields score.
     */
    private function calculateRequiredFieldsScore(PWAAnalysis $analysis): float
    {
        $fields = [
            $analysis->has_name,
            $analysis->has_short_name,
            $analysis->has_start_url,
            $analysis->has_display,
        ];

        $presentCount = count(array_filter($fields));

        return $presentCount * self::REQUIRED_FIELD_POINTS;
    }

    /**
     * Calculate recommended fields score.
     */
    private function calculateRecommendedFieldsScore(PWAAnalysis $analysis): float
    {
        $fields = [
            $analysis->has_background_color,
            $analysis->has_theme_color,
            $analysis->has_description,
        ];

        $presentCount = count(array_filter($fields));

        return $presentCount * self::RECOMMENDED_FIELD_POINTS;
    }

    /**
     * Calculate icon size score.
     */
    private function calculateIconSizeScore(PWAAnalysis $analysis): float
    {
        $score = 0;

        if ($analysis->icons_192_present) {
            $score += self::ICON_192_POINTS;
        }

        if ($analysis->icons_512_present) {
            $score += self::ICON_512_POINTS;
        }

        if ($analysis->icons_180_present) {
            $score += self::ICON_180_POINTS;
        }

        return $score;
    }

    /**
     * Calculate icon validity bonus score.
     */
    private function calculateIconValidityScore(PWAAnalysis $analysis): float
    {
        $ratio = $analysis->getIconValidityRatio();

        return $ratio * self::ICON_VALIDITY_MAX_POINTS;
    }

    /**
     * Get manifest breakdown.
     */
    private function getManifestBreakdown(PWAAnalysis $analysis): array
    {
        return [
            'presence' => $analysis->has_manifest ? self::MANIFEST_PRESENCE_POINTS : 0,
            'validity' => ($analysis->has_manifest && $analysis->manifest_valid) 
                ? self::MANIFEST_VALIDITY_POINTS 
                : 0,
        ];
    }

    /**
     * Get required fields breakdown.
     */
    private function getRequiredFieldsBreakdown(PWAAnalysis $analysis): array
    {
        $fields = [
            'name' => $analysis->has_name,
            'short_name' => $analysis->has_short_name,
            'start_url' => $analysis->has_start_url,
            'display' => $analysis->has_display,
        ];

        $earned = count(array_filter($fields)) * self::REQUIRED_FIELD_POINTS;

        return [
            'fields' => $fields,
            'earned' => $earned,
            'max' => 4 * self::REQUIRED_FIELD_POINTS,
        ];
    }

    /**
     * Get recommended fields breakdown.
     */
    private function getRecommendedFieldsBreakdown(PWAAnalysis $analysis): array
    {
        $fields = [
            'background_color' => $analysis->has_background_color,
            'theme_color' => $analysis->has_theme_color,
            'description' => $analysis->has_description,
        ];

        $earned = count(array_filter($fields)) * self::RECOMMENDED_FIELD_POINTS;

        return [
            'fields' => $fields,
            'earned' => $earned,
            'max' => 3 * self::RECOMMENDED_FIELD_POINTS,
        ];
    }

    /**
     * Get icons breakdown.
     */
    private function getIconsBreakdown(PWAAnalysis $analysis): array
    {
        $sizes = [
            '192x192' => $analysis->icons_192_present,
            '512x512' => $analysis->icons_512_present,
            '180x180' => $analysis->icons_180_present,
        ];

        $earned = 0;
        if ($analysis->icons_192_present) {
            $earned += self::ICON_192_POINTS;
        }
        if ($analysis->icons_512_present) {
            $earned += self::ICON_512_POINTS;
        }
        if ($analysis->icons_180_present) {
            $earned += self::ICON_180_POINTS;
        }

        return [
            'sizes' => $sizes,
            'earned' => $earned,
            'max' => self::ICON_192_POINTS + self::ICON_512_POINTS + self::ICON_180_POINTS,
        ];
    }

    /**
     * Get icon validity breakdown.
     */
    private function getIconValidityBreakdown(PWAAnalysis $analysis): array
    {
        $ratio = $analysis->getIconValidityRatio();
        $earned = $ratio * self::ICON_VALIDITY_MAX_POINTS;

        return [
            'ratio' => $ratio,
            'earned' => $earned,
            'max' => self::ICON_VALIDITY_MAX_POINTS,
        ];
    }
}
