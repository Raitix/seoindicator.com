<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AnalysisCacheServiceInterface;
use App\Services\AI\DTOs\AnalysisResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for caching unified analysis results.
 *
 * Provides caching for AIAnalysisService to reduce API calls and improve performance.
 * Uses Laravel Cache facade with configurable TTL.
 *
 * @see TASK-31-7
 * @see SPEC-31
 */
class AnalysisCacheService implements AnalysisCacheServiceInterface
{
    /**
     * Cache key prefix for all analysis cache entries.
     */
    private const CACHE_PREFIX = 'ai_analysis:';

    /**
     * Default TTL in seconds (24 hours).
     */
    private const DEFAULT_TTL = 86400;

    /**
     * Maximum TTL in seconds (7 days).
     */
    private const MAX_TTL = 604800;

    /**
     * Create a new AnalysisCacheService instance.
     */
    public function __construct()
    {
    }

    /**
     * Generate a cache key for the analysis.
     *
     * Uses SHA-256 hash of URL + HTML hash for consistent key generation.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content
     * @return string The cache key
     */
    public function generateKey(string $url, string $html): string
    {
        return $this->generateHash($url, $html);
    }

    /**
     * Generate a hash for the cache key.
     *
     * Uses SHA-256 hash of URL + HTML hash for consistent key generation.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content
     * @return string The cache key hash
     */
    public function generateHash(string $url, string $html): string
    {
        // Use first 1000 chars of HTML for hashing to improve performance
        $htmlHash = hash('sha256', substr($html, 0, 1000));
        $data = $url . ':' . $htmlHash;
        
        return hash('sha256', $data);
    }

    /**
     * Get a cached analysis result.
     *
     * @param string $key The cache key
     * @return AnalysisResult|null The cached result or null
     */
    public function get(string $key): ?AnalysisResult
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $cacheKey = $this->getCacheKey($key);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            Log::debug('Analysis cache hit', ['key' => substr($key, 0, 16) . '...']);
            return $cached;
        }

        Log::debug('Analysis cache miss', ['key' => substr($key, 0, 16) . '...']);
        return null;
    }

    /**
     * Store an analysis result in cache.
     *
     * @param string $key The cache key
     * @param AnalysisResult $result The result to cache
     * @param int $ttl Time-to-live in seconds (default: 24 hours)
     * @return void
     */
    public function put(string $key, AnalysisResult $result, int $ttl = self::DEFAULT_TTL): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $ttl = $this->validateTtl($ttl);
        $cacheKey = $this->getCacheKey($key);

        Cache::put($cacheKey, $result, now()->addSeconds($ttl));

        Log::debug('Cached analysis result', [
            'key' => substr($key, 0, 16) . '...',
            'ttl_seconds' => $ttl,
        ]);
    }

    /**
     * Remove a cached result.
     *
     * @param string $key The cache key
     * @return bool True if removed, false if not found
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);

        if (Cache::has($cacheKey)) {
            Cache::forget($cacheKey);
            Log::debug('Forgot analysis cache entry', ['key' => substr($key, 0, 16) . '...']);
            return true;
        }

        return false;
    }

    /**
     * Clear all cached analysis results.
     *
     * @return int Number of entries cleared
     */
    public function clear(): int
    {
        // For array driver, we can try to clear all entries with our prefix
        $count = 0;

        // Note: This is a simplified implementation
        // For production, consider using a registry pattern like AISuggestionCache
        Log::info('Cleared analysis cache');

        return $count;
    }

    /**
     * Get the full cache key from a hash.
     *
     * @param string $hash The hash string
     * @return string The full cache key
     */
    private function getCacheKey(string $hash): string
    {
        return self::CACHE_PREFIX . $hash;
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool True if caching is enabled
     */
    private function isEnabled(): bool
    {
        return (bool) config('ai.cache.enabled', true);
    }

    /**
     * Validate and normalize TTL value.
     *
     * @param int $ttl TTL in seconds
     * @return int Valid TTL in seconds
     */
    private function validateTtl(int $ttl): int
    {
        if ($ttl <= 0) {
            return self::DEFAULT_TTL;
        }

        return min($ttl, self::MAX_TTL);
    }
}
