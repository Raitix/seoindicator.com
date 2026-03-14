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
 * - Position Component: 40% - Based on keyword ranking positions
 * - SERP Feature Component: 30% - Based on SERP feature presence
 * - Backlink Component: 20% - Based on backlink profile health score
 * - Volume Distribution Component: 10% - Based on search volume distribution
 *
 * When backlink data is not available, weights are redistributed:
 * - Position: 50%, SERP: 37.5%, Volume: 12.5%
 *
 * @see TASK-23-15
 * @see SPEC-010
 */
class VisibilityScoreService
{
    /**
     * Component weights (must sum to 1.0).
     */
    protected const POSITION_WEIGHT = 0.40;
    protected const SERP_WEIGHT = 0.30;
    protected const BACKLINK_WEIGHT = 0.20;
    protected const VOLUME_WEIGHT = 0.10;

    /**
     * Redistributed weights when backlink data is missing.
     */
    protected const REDISTRIBUTED_POSITION_WEIGHT = 0.50;
    protected const REDISTRIBUTED_SERP_WEIGHT = 0.375;
    protected const REDISTRIBUTED_VOLUME_WEIGHT = 0.125;

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Backlink profile scorer instance.
     *
     * @var BacklinkProfileScorer|null
     */
    protected ?BacklinkProfileScorer $backlinkScorer;

    /**
     * Create a new VisibilityScoreService instance.
     *
     * @param BacklinkProfileScorer|null $backlinkScorer
     */
    public function __construct(?BacklinkProfileScorer $backlinkScorer = null)
    {
        $this->backlinkScorer = $backlinkScorer;
    }

    /**
     * Calculate the overall visibility score for a project.
     *
     * @param Project $project The project to calculate score for
     * @param array $components Pre-calculated component scores (optional)
     *   - position: float (0-100)
     *   - serp: float (0-100)
     *   - volume: float (0-100)
     *   - backlink: float (0-100) - if not provided, calculated from BacklinkProfileScorer
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
        $positionScore = $this->clamp($components['position'] ?? 0.0, 0, 100);
        $serpScore = $this->clamp($components['serp'] ?? 0.0, 0, 100);
        $volumeScore = $this->clamp($components['volume'] ?? 0.0, 0, 100);
        
        // Calculate backlink component
        $backlinkScore = $components['backlink'] ?? null;
        if ($backlinkScore === null) {
            $backlinkScore = $this->calculateBacklinkComponent($project);
        }
        $backlinkScore = $this->clamp($backlinkScore, 0, 100);

        // Determine if we have backlink data (non-zero score indicates data exists)
        $hasBacklinkData = $backlinkScore > 0;

        // Calculate weighted total
        if ($hasBacklinkData) {
            $total = ($positionScore * self::POSITION_WEIGHT)
                + ($serpScore * self::SERP_WEIGHT)
                + ($backlinkScore * self::BACKLINK_WEIGHT)
                + ($volumeScore * self::VOLUME_WEIGHT);
        } else {
            // Redistribute weights when no backlink data
            $total = ($positionScore * self::REDISTRIBUTED_POSITION_WEIGHT)
                + ($serpScore * self::REDISTRIBUTED_SERP_WEIGHT)
                + ($volumeScore * self::REDISTRIBUTED_VOLUME_WEIGHT);
        }

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
        
        // Calculate backlink component
        $backlinkScore = $components['backlink'] ?? null;
        if ($backlinkScore === null) {
            $backlinkScore = $this->calculateBacklinkComponent($project);
        }
        $backlinkScore = $this->clamp($backlinkScore, 0, 100);

        // Determine if we have backlink data
        $hasBacklinkData = $backlinkScore > 0;

        // Calculate weighted total
        if ($hasBacklinkData) {
            $total = ($positionScore * self::POSITION_WEIGHT)
                + ($serpScore * self::SERP_WEIGHT)
                + ($backlinkScore * self::BACKLINK_WEIGHT)
                + ($volumeScore * self::VOLUME_WEIGHT);
        } else {
            // Redistribute weights when no backlink data
            $total = ($positionScore * self::REDISTRIBUTED_POSITION_WEIGHT)
                + ($serpScore * self::REDISTRIBUTED_SERP_WEIGHT)
                + ($volumeScore * self::REDISTRIBUTED_VOLUME_WEIGHT);
        }

        $score = (int) round($this->clamp($total, 0, 100));

        // Calculate component contributions
        $positionComponent = round($positionScore * ($hasBacklinkData ? self::POSITION_WEIGHT : self::REDISTRIBUTED_POSITION_WEIGHT), 2);
        $serpComponent = round($serpScore * ($hasBacklinkData ? self::SERP_WEIGHT : self::REDISTRIBUTED_SERP_WEIGHT), 2);
        $volumeComponent = round($volumeScore * ($hasBacklinkData ? self::VOLUME_WEIGHT : self::REDISTRIBUTED_VOLUME_WEIGHT), 2);
        $backlinkComponent = round($backlinkScore * self::BACKLINK_WEIGHT, 2);

        // Store in database
        VisibilityScore::create([
            'project_id' => $project->id,
            'score' => $score,
            'position_component' => $positionComponent,
            'serp_component' => $serpComponent,
            'volume_component' => $volumeComponent,
            'backlink_component' => $backlinkComponent,
            'pwa_component' => 0.0, // PWA component deprecated, kept for backward compatibility
            'keywords_count' => $project->keywords()->count() ?? 0,
            'calculated_at' => now(),
        ]);

        // Update cache
        Cache::put($this->getCacheKey($project->id), $score, self::CACHE_TTL);

        return $score;
    }

    /**
     * Calculate backlink component contribution to visibility score.
     *
     * Calls the BacklinkProfileScorer to get the overall profile score,
     * then applies the 20% weight. Returns 0 if no backlink data or
     * scorer is unavailable.
     *
     * @param Project $project
     * @return float Backlink component score (0-20, representing weighted contribution)
     */
    public function calculateBacklinkComponent(Project $project): float
    {
        try {
            // Check if scorer is available
            if ($this->backlinkScorer === null) {
                return 0.0;
            }

            // Get the profile score from the scorer
            $result = $this->backlinkScorer->score($project);

            // Handle null result (no backlink data)
            if ($result === null) {
                return 0.0;
            }

            // Handle array result (full scorer output)
            if (is_array($result)) {
                $profileScore = $result['overall_score'] ?? 0.0;
            } else {
                $profileScore = (float) $result;
            }

            // Clamp to valid range
            $profileScore = $this->clamp($profileScore, 0, 100);

            // Return the weighted contribution (20% of profile score)
            return $profileScore * self::BACKLINK_WEIGHT;
        } catch (\Exception $e) {
            Log::warning('Backlink component calculation failed', [
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
        
        $backlinkScore = $components['backlink'] ?? null;
        if ($backlinkScore === null) {
            $backlinkContribution = $this->calculateBacklinkComponent($project);
            $backlinkScore = $backlinkContribution > 0 ? $backlinkContribution / self::BACKLINK_WEIGHT : 0.0;
        }
        $backlinkScore = $this->clamp($backlinkScore, 0, 100);

        $hasBacklinkData = $backlinkScore > 0;

        if ($hasBacklinkData) {
            $totalScore = ($positionScore * self::POSITION_WEIGHT)
                + ($serpScore * self::SERP_WEIGHT)
                + ($backlinkScore * self::BACKLINK_WEIGHT)
                + ($volumeScore * self::VOLUME_WEIGHT);
        } else {
            $totalScore = ($positionScore * self::REDISTRIBUTED_POSITION_WEIGHT)
                + ($serpScore * self::REDISTRIBUTED_SERP_WEIGHT)
                + ($volumeScore * self::REDISTRIBUTED_VOLUME_WEIGHT);
        }

        return [
            'overall_score' => (int) round($this->clamp($totalScore, 0, 100)),
            'components' => [
                'position' => [
                    'score' => round($positionScore, 2),
                    'weight' => (int) (($hasBacklinkData ? self::POSITION_WEIGHT : self::REDISTRIBUTED_POSITION_WEIGHT) * 100),
                    'contribution' => round($positionScore * ($hasBacklinkData ? self::POSITION_WEIGHT : self::REDISTRIBUTED_POSITION_WEIGHT), 2),
                ],
                'serp_features' => [
                    'score' => round($serpScore, 2),
                    'weight' => (int) (($hasBacklinkData ? self::SERP_WEIGHT : self::REDISTRIBUTED_SERP_WEIGHT) * 100),
                    'contribution' => round($serpScore * ($hasBacklinkData ? self::SERP_WEIGHT : self::REDISTRIBUTED_SERP_WEIGHT), 2),
                ],
                'backlinks' => [
                    'score' => round($backlinkScore, 2),
                    'weight' => (int) (self::BACKLINK_WEIGHT * 100),
                    'contribution' => round($backlinkScore * self::BACKLINK_WEIGHT, 2),
                ],
                'volume_distribution' => [
                    'score' => round($volumeScore, 2),
                    'weight' => (int) (($hasBacklinkData ? self::VOLUME_WEIGHT : self::REDISTRIBUTED_VOLUME_WEIGHT) * 100),
                    'contribution' => round($volumeScore * ($hasBacklinkData ? self::VOLUME_WEIGHT : self::REDISTRIBUTED_VOLUME_WEIGHT), 2),
                ],
            ],
            'has_backlink_data' => $hasBacklinkData,
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
     * Get backlink component weight.
     *
     * @return float
     */
    public function getBacklinkWeight(): float
    {
        return self::BACKLINK_WEIGHT;
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
     * Get all component weights.
     *
     * @return array
     */
    public function getWeights(): array
    {
        return [
            'position' => self::POSITION_WEIGHT,
            'serp' => self::SERP_WEIGHT,
            'backlink' => self::BACKLINK_WEIGHT,
            'volume' => self::VOLUME_WEIGHT,
        ];
    }

    /**
     * Get redistributed weights when backlink data is missing.
     *
     * @return array
     */
    public function getRedistributedWeights(): array
    {
        return [
            'position' => self::REDISTRIBUTED_POSITION_WEIGHT,
            'serp' => self::REDISTRIBUTED_SERP_WEIGHT,
            'volume' => self::REDISTRIBUTED_VOLUME_WEIGHT,
        ];
    }
}
