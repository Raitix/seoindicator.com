<?php

namespace App\Services\SEO;

use App\Models\Project;
use App\Models\VisibilityScore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Visibility Score Service
 *
 * Calculates overall visibility scores for projects by combining multiple
 * SEO components into a single 0-100 score.
 *
 * Component Weights:
 * - Position Component: 50% - Based on keyword ranking positions
 * - SERP Feature Component: 25% - Based on SERP feature presence
 * - Volume Distribution Component: 15% - Based on search volume distribution
 * - PWA Component: 10% - Based on PWA readiness score
 *
 * @see TASK-24-13
 * @see SPEC-010
 */
class VisibilityScoreService
{
    /**
     * Component weights (must sum to 1.0).
     */
    protected const POSITION_WEIGHT = 0.50;
    protected const SERP_WEIGHT = 0.25;
    protected const VOLUME_WEIGHT = 0.15;
    protected const PWA_WEIGHT = 0.10;

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Calculate the overall visibility score for a project.
     *
     * @param Project $project The project to calculate score for
     * @param array $components Pre-calculated component scores (optional)
     *   - position: float (0-100)
     *   - serp: float (0-100)
     *   - volume: float (0-100)
     *   - pwa: float (0-100) - if not provided, calculated from PWAAnalysis
     * @param bool $force Force recalculation (bypass cache)
     * @return int Overall visibility score (0-100)
     * @throws \InvalidArgumentException If project is null
     */
    public function calculate(Project $project, array $components = [], bool $force = false): int
    {
        if ($project === null) {
            throw new \InvalidArgumentException('Project cannot be null');
        }

        // Check cache first (unless forced)
        if (!$force) {
            $cached = Cache::get($this->getCacheKey($project->id));
            if ($cached !== null) {
                return (int) $cached;
            }
        }

        // Calculate component scores
        $positionScore = $components['position'] ?? 0.0;
        $serpScore = $components['serp'] ?? 0.0;
        $volumeScore = $components['volume'] ?? 0.0;
        $pwaScore = $components['pwa'] ?? $this->calculatePWAComponent($project);

        // Clamp scores to valid range
        $positionScore = $this->clamp($positionScore, 0, 100);
        $serpScore = $this->clamp($serpScore, 0, 100);
        $volumeScore = $this->clamp($volumeScore, 0, 100);

        // Calculate weighted total
        $total = ($positionScore * self::POSITION_WEIGHT)
            + ($serpScore * self::SERP_WEIGHT)
            + ($volumeScore * self::VOLUME_WEIGHT)
            + ($pwaScore * self::PWA_WEIGHT);

        // Round and clamp final score
        $score = (int) round($this->clamp($total, 0, 100));

        // Cache the result
        Cache::put($this->getCacheKey($project->id), $score, self::CACHE_TTL);

        return $score;
    }

    /**
     * Calculate and store visibility score for a project.
     *
     * @param Project $project The project to calculate score for
     * @param array $components Pre-calculated component scores (optional)
     * @return int The calculated visibility score
     */
    public function calculateAndStore(Project $project, array $components = []): int
    {
        // Calculate component scores
        $positionScore = $this->clamp($components['position'] ?? 0.0, 0, 100);
        $serpScore = $this->clamp($components['serp'] ?? 0.0, 0, 100);
        $volumeScore = $this->clamp($components['volume'] ?? 0.0, 0, 100);
        $pwaScore = $components['pwa'] ?? $this->calculatePWAComponent($project);

        // Calculate weighted total
        $total = ($positionScore * self::POSITION_WEIGHT)
            + ($serpScore * self::SERP_WEIGHT)
            + ($volumeScore * self::VOLUME_WEIGHT)
            + ($pwaScore * self::PWA_WEIGHT);

        $score = (int) round($this->clamp($total, 0, 100));

        // Calculate component contributions
        $positionComponent = round($positionScore * self::POSITION_WEIGHT, 2);
        $serpComponent = round($serpScore * self::SERP_WEIGHT, 2);
        $volumeComponent = round($volumeScore * self::VOLUME_WEIGHT, 2);
        $pwaComponent = round($pwaScore, 2);

        // Store in database
        VisibilityScore::create([
            'project_id' => $project->id,
            'score' => $score,
            'position_component' => $positionComponent,
            'serp_component' => $serpComponent,
            'volume_component' => $volumeComponent,
            'pwa_component' => $pwaComponent,
            'keywords_count' => $project->keywords()->count() ?? 0,
            'calculated_at' => now(),
        ]);

        // Update cache
        Cache::put($this->getCacheKey($project->id), $score, self::CACHE_TTL);

        return $score;
    }

    /**
     * Calculate PWA component contribution to visibility score.
     *
     * PWA readiness contributes 10% weight to overall visibility.
     * Formula: (pwa_score / 100) * 10 points
     *
     * @param Project $project
     * @return float PWA component score (0-10)
     */
    public function calculatePWAComponent(Project $project): float
    {
        try {
            $pwaAnalysis = $project->pwaAnalysis;

            // No PWA analysis available
            if ($pwaAnalysis === null) {
                return 0.0;
            }

            $pwaScore = $pwaAnalysis->pwa_score ?? 0;

            // Clamp to valid range
            $pwaScore = $this->clamp($pwaScore, 0, 100);

            // Calculate 10% contribution: (score / 100) * 10 = score * 0.10
            return round($pwaScore * self::PWA_WEIGHT, 2);
        } catch (\Exception $e) {
            Log::warning('PWA component calculation failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    /**
     * Get the score breakdown for a project.
     *
     * @param Project $project
     * @param array $components Pre-calculated component scores (optional)
     * @return array Detailed breakdown of visibility score
     */
    public function getBreakdown(Project $project, array $components = []): array
    {
        $positionScore = $this->clamp($components['position'] ?? 0.0, 0, 100);
        $serpScore = $this->clamp($components['serp'] ?? 0.0, 0, 100);
        $volumeScore = $this->clamp($components['volume'] ?? 0.0, 0, 100);
        $pwaScore = $this->calculatePWAComponent($project);

        $totalScore = ($positionScore * self::POSITION_WEIGHT)
            + ($serpScore * self::SERP_WEIGHT)
            + ($volumeScore * self::VOLUME_WEIGHT)
            + ($pwaScore);

        return [
            'overall_score' => (int) round($this->clamp($totalScore, 0, 100)),
            'components' => [
                'position' => [
                    'score' => round($positionScore, 2),
                    'weight' => (int) (self::POSITION_WEIGHT * 100),
                    'contribution' => round($positionScore * self::POSITION_WEIGHT, 2),
                ],
                'serp_features' => [
                    'score' => round($serpScore, 2),
                    'weight' => (int) (self::SERP_WEIGHT * 100),
                    'contribution' => round($serpScore * self::SERP_WEIGHT, 2),
                ],
                'volume_distribution' => [
                    'score' => round($volumeScore, 2),
                    'weight' => (int) (self::VOLUME_WEIGHT * 100),
                    'contribution' => round($volumeScore * self::VOLUME_WEIGHT, 2),
                ],
                'pwa' => [
                    'score' => round($pwaScore / self::PWA_WEIGHT, 2), // Raw PWA score
                    'weight' => (int) (self::PWA_WEIGHT * 100),
                    'contribution' => round($pwaScore, 2),
                ],
            ],
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Clear the cached visibility score for a project.
     *
     * @param int $projectId
     */
    public function clearCache(int $projectId): void
    {
        Cache::forget($this->getCacheKey($projectId));
    }

    /**
     * Get the cache key for a project's visibility score.
     *
     * @param int $projectId
     * @return string
     */
    protected function getCacheKey(int $projectId): string
    {
        return "visibility_score:{$projectId}";
    }

    /**
     * Clamp a value between min and max.
     *
     * @param float $value
     * @param float $min
     * @param float $max
     * @return float
     */
    protected function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Get position component weight.
     *
     * @return float
     */
    public function getPositionWeight(): float
    {
        return self::POSITION_WEIGHT;
    }

    /**
     * Get SERP component weight.
     *
     * @return float
     */
    public function getSerpWeight(): float
    {
        return self::SERP_WEIGHT;
    }

    /**
     * Get volume component weight.
     *
     * @return float
     */
    public function getVolumeWeight(): float
    {
        return self::VOLUME_WEIGHT;
    }

    /**
     * Get PWA component weight.
     *
     * @return float
     */
    public function getPWAWeight(): float
    {
        return self::PWA_WEIGHT;
    }

    /**
     * Get all component weights.
     *
     * @return array
     */
    public function getWeights(): array
    {
        return [
            'position' => self::POSITION_WEIGHT,
            'serp' => self::SERP_WEIGHT,
            'volume' => self::VOLUME_WEIGHT,
            'pwa' => self::PWA_WEIGHT,
        ];
    }
}
