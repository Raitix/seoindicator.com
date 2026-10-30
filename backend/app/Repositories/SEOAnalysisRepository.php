<?php

namespace App\Repositories;

use App\Models\SEOAnalysis;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Repository for SEOAnalysis model.
 *
 * Handles CRUD operations, caching, and common queries for SEO analyses.
 *
 * @see TASK-18-2
 * @see SPEC-018
 */
class SEOAnalysisRepository
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    protected int $cacheTtl = 3600;

    /**
     * Create a new SEO analysis.
     */
    public function create(array $data): SEOAnalysis
    {
        $analysis = SEOAnalysis::create($data);
        $this->cacheAnalysis($analysis);

        return $analysis;
    }

    /**
     * Find an analysis by ID.
     */
    public function find(int $id): ?SEOAnalysis
    {
        return Cache::remember(
            "seo_analysis.{$id}",
            $this->cacheTtl,
            fn() => SEOAnalysis::find($id)
        );
    }

    /**
     * Update an analysis.
     */
    public function update(int $id, array $data): ?SEOAnalysis
    {
        $analysis = SEOAnalysis::find($id);

        if (!$analysis) {
            return null;
        }

        $analysis->update($data);
        $this->clearAnalysisCache($id);

        return $analysis->fresh();
    }

    /**
     * Delete an analysis.
     */
    public function delete(int $id): bool
    {
        $analysis = SEOAnalysis::find($id);

        if (!$analysis) {
            return false;
        }

        $this->clearAnalysisCache($id);
        return $analysis->delete();
    }

    /**
     * Find analyses by domain.
     *
     * @return Collection<int, SEOAnalysis>
     */
    public function findByDomain(string $domain): Collection
    {
        return SEOAnalysis::forDomain($domain)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find a cached (non-expired, completed) analysis for a domain.
     */
    public function findCached(string $domain): ?SEOAnalysis
    {
        return SEOAnalysis::forDomain($domain)
            ->completed()
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Find analyses by user.
     *
     * @return Collection<int, SEOAnalysis>
     */
    public function findByUser(int $userId): Collection
    {
        return SEOAnalysis::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find analyses by session.
     *
     * @return Collection<int, SEOAnalysis>
     */
    public function findBySession(string $sessionId): Collection
    {
        return SEOAnalysis::bySession($sessionId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the latest analysis for a domain.
     */
    public function getLatest(string $domain): ?SEOAnalysis
    {
        return SEOAnalysis::forDomain($domain)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get all pending analyses.
     *
     * @return Collection<int, SEOAnalysis>
     */
    public function getPending(): Collection
    {
        return SEOAnalysis::pending()
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get all expired analyses.
     *
     * @return Collection<int, SEOAnalysis>
     */
    public function getExpired(): Collection
    {
        return SEOAnalysis::expired()
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Find analyses by domain for a specific user.
     *
     * @return Collection<int, SEOAnalysis>
     */
    public function findByDomainForUser(string $domain, int $userId): Collection
    {
        return SEOAnalysis::forDomain($domain)
            ->byUser($userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find a cached analysis by domain for a session.
     */
    public function findCachedBySession(string $domain, string $sessionId): ?SEOAnalysis
    {
        return SEOAnalysis::forDomain($domain)
            ->bySession($sessionId)
            ->completed()
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get paginated analyses.
     */
    public function paginate(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return SEOAnalysis::orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get analysis statistics for a user.
     */
    public function getStats(int $userId): array
    {
        $baseQuery = SEOAnalysis::byUser($userId);

        return [
            'total' => (clone $baseQuery)->count(),
            'completed' => (clone $baseQuery)->completed()->count(),
            'failed' => (clone $baseQuery)->failed()->count(),
            'pending' => (clone $baseQuery)->pending()->count(),
        ];
    }

    /**
     * Clean up expired analyses.
     *
     * @return int Number of deleted records
     */
    public function cleanupExpired(): int
    {
        return SEOAnalysis::expired()->delete();
    }

    /**
     * Cache an analysis.
     */
    protected function cacheAnalysis(SEOAnalysis $analysis): void
    {
        Cache::put(
            "seo_analysis.{$analysis->id}",
            $analysis,
            $this->cacheTtl
        );
    }

    /**
     * Clear the cache for an analysis.
     */
    protected function clearAnalysisCache(int $id): void
    {
        Cache::forget("seo_analysis.{$id}");
    }
}
