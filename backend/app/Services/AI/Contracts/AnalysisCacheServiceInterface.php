<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\AnalysisResult;

/**
 * Interface for analysis result caching service.
 *
 * Caches unified analysis results to reduce API calls and improve performance.
 *
 * @see TASK-31-7
 */
interface AnalysisCacheServiceInterface
{
    /**
     * Generate a cache key for the analysis.
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content
     * @return string The cache key
     */
    public function generateKey(string $url, string $html): string;

    /**
     * Generate a hash for the cache key (alias for generateKey).
     *
     * @param string $url The URL being analyzed
     * @param string $html The HTML content
     * @return string The cache key hash
     */
    public function generateHash(string $url, string $html): string;

    /**
     * Get a cached analysis result.
     *
     * @param string $key The cache key
     * @return AnalysisResult|null The cached result or null
     */
    public function get(string $key): ?AnalysisResult;

    /**
     * Store an analysis result in cache.
     *
     * @param string $key The cache key
     * @param AnalysisResult $result The result to cache
     * @param int $ttl Time-to-live in seconds
     * @return void
     */
    public function put(string $key, AnalysisResult $result, int $ttl = 86400): void;

    /**
     * Remove a cached result.
     *
     * @param string $key The cache key
     * @return bool True if removed, false if not found
     */
    public function forget(string $key): bool;

    /**
     * Clear all cached analysis results.
     *
     * @return int Number of entries cleared
     */
    public function clear(): int;
}
