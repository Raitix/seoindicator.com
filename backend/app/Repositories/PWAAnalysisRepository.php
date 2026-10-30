<?php

namespace App\Repositories;

use App\Models\PWAAnalysis;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for PWAAnalysis model.
 *
 * Handles CRUD operations and common queries for PWA analyses.
 *
 * @see TASK-24-2
 * @see SPEC-024
 */
class PWAAnalysisRepository
{
    /**
     * Valid readiness levels.
     */
    protected const VALID_READINESS_LEVELS = ['none', 'basic', 'enhanced', 'full'];

    /**
     * Create a new PWA analysis.
     */
    public function create(array $data): PWAAnalysis
    {
        $this->validateReadinessLevel($data['readiness_level'] ?? null);

        return PWAAnalysis::create($data);
    }

    /**
     * Find an analysis by ID.
     */
    public function find(int $id): ?PWAAnalysis
    {
        return PWAAnalysis::find($id);
    }

    /**
     * Update an analysis.
     *
     * @return PWAAnalysis|false Returns the updated model or false if not found
     */
    public function update(int $id, array $data): PWAAnalysis|bool
    {
        $analysis = PWAAnalysis::find($id);

        if (!$analysis) {
            return false;
        }

        if (isset($data['readiness_level'])) {
            $this->validateReadinessLevel($data['readiness_level']);
        }

        $analysis->update($data);

        return $analysis->fresh();
    }

    /**
     * Delete an analysis.
     */
    public function delete(int $id): bool
    {
        $analysis = PWAAnalysis::find($id);

        if (!$analysis) {
            return false;
        }

        return $analysis->delete();
    }

    /**
     * Find the most recent analysis for a site.
     */
    public function findBySiteId(int $siteId): ?PWAAnalysis
    {
        return PWAAnalysis::where('site_id', $siteId)
            ->orderBy('analyzed_at', 'desc')
            ->first();
    }

    /**
     * Get the latest analysis for a site.
     *
     * Alias for findBySiteId for semantic clarity.
     */
    public function getLatestForSite(int $siteId): ?PWAAnalysis
    {
        return $this->findBySiteId($siteId);
    }

    /**
     * Find all analyses for a site.
     *
     * @return Collection<int, PWAAnalysis>
     */
    public function findAllForSite(int $siteId): Collection
    {
        return PWAAnalysis::where('site_id', $siteId)
            ->orderBy('analyzed_at', 'desc')
            ->get();
    }

    /**
     * Find analyses with manifest.
     *
     * @return Collection<int, PWAAnalysis>
     */
    public function findWithManifest(): Collection
    {
        return PWAAnalysis::where('has_manifest', true)
            ->orderBy('analyzed_at', 'desc')
            ->get();
    }

    /**
     * Find analyses without manifest.
     *
     * @return Collection<int, PWAAnalysis>
     */
    public function findWithoutManifest(): Collection
    {
        return PWAAnalysis::where('has_manifest', false)
            ->orderBy('analyzed_at', 'desc')
            ->get();
    }

    /**
     * Find analyses by readiness level.
     *
     * @return Collection<int, PWAAnalysis>
     */
    public function findByReadinessLevel(string $level): Collection
    {
        return PWAAnalysis::where('readiness_level', $level)
            ->orderBy('analyzed_at', 'desc')
            ->get();
    }

    /**
     * Find analyses with minimum score.
     *
     * @return Collection<int, PWAAnalysis>
     */
    public function findWithMinScore(int $minScore): Collection
    {
        return PWAAnalysis::where('pwa_score', '>=', $minScore)
            ->orderBy('pwa_score', 'desc')
            ->get();
    }

    /**
     * Get or create the latest analysis for a site.
     */
    public function getOrCreateForSite(int $siteId, array $defaults = []): PWAAnalysis
    {
        $analysis = $this->findBySiteId($siteId);

        if ($analysis) {
            return $analysis;
        }

        return $this->create(array_merge(['site_id' => $siteId], $defaults));
    }

    /**
     * Delete all analyses for a site.
     *
     * @return int Number of deleted records
     */
    public function deleteAllForSite(int $siteId): int
    {
        return PWAAnalysis::where('site_id', $siteId)->delete();
    }

    /**
     * Get statistics for a site's PWA analyses.
     */
    public function getStatsForSite(int $siteId): array
    {
        $analyses = PWAAnalysis::where('site_id', $siteId);

        return [
            'total' => (clone $analyses)->count(),
            'with_manifest' => (clone $analyses)->where('has_manifest', true)->count(),
            'installable' => (clone $analyses)->get()->filter(fn ($a) => $a->isInstallable())->count(),
            'average_score' => (clone $analyses)->avg('pwa_score') ?? 0,
            'latest_score' => $this->findBySiteId($siteId)?->pwa_score ?? 0,
        ];
    }

    /**
     * Validate the readiness level.
     *
     * @throws \InvalidArgumentException If the readiness level is invalid
     */
    protected function validateReadinessLevel(?string $level): void
    {
        if ($level !== null && !in_array($level, self::VALID_READINESS_LEVELS, true)) {
            throw new \InvalidArgumentException(
                "Invalid readiness level: {$level}. Valid values are: " . implode(', ', self::VALID_READINESS_LEVELS)
            );
        }
    }
}
