<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI Suggestion Cache Service.
 *
 * Caches AI suggestion responses for identical requests to reduce
 * API calls, improve response times, and conserve free tier quotas.
 *
 * Uses Laravel Cache facade for storage. Stats are tracked in-memory
 * for the current request lifecycle.
 *
 * @package App\Services\AI
 */
class AISuggestionCache
{
    /**
     * Cache key prefix for all AI suggestion cache entries.
     */
    private const CACHE_PREFIX = 'ai_suggestions:';

    /**
     * Registry key for tracking all cache entry hashes.
     */
    private const REGISTRY_KEY = 'ai_suggestions:_registry';

    /**
     * Stats cache key for persistent tracking.
     */
    private const STATS_KEY = 'ai_suggestions:_stats';

    /**
     * Valid indicator types for validation.
     */
    private const VALID_INDICATORS = [
        'title',
        'meta_description',
        'robots',
        'sitemap',
        'headings',
        'image_alt',
        'filenames',
        'url',
        'links',
        '404',
    ];

    /**
     * In-memory stats for the current request.
     */
    private array $memoryStats = [
        'hits' => 0,
        'misses' => 0,
        'evictions' => 0,
    ];

    /**
     * Generate a deterministic hash for the cache key.
     *
     * @param string $indicator The indicator type (e.g., 'title', 'meta_description')
     * @param array<string, mixed> $context Context data for the indicator
     * @param string $provider The AI provider name
     * @return string SHA-256 hash (64 characters)
     */
    public function generateHash(string $indicator, array $context, string $provider): string
    {
        // Normalize context for consistent hashing
        $normalizedContext = $this->normalizeContext($context);
        
        $data = [
            'indicator' => $indicator,
            'context' => $normalizedContext,
            'provider' => $provider,
        ];
        
        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get a cached suggestion result.
     *
     * @param string|null $hash The cache hash
     * @return array<string, mixed>|null The cached result or null if not found
     */
    public function get(?string $hash): ?array
    {
        // Handle null/empty hash
        if (empty($hash)) {
            return null;
        }
        
        // Check if cache is enabled
        if (!$this->isEnabled()) {
            return null;
        }
        
        $key = $this->getCacheKey($hash);
        $cached = Cache::get($key);
        
        if ($cached !== null) {
            $this->incrementStat('hits');
            Log::debug('AI suggestion cache hit', ['hash' => substr($hash, 0, 16) . '...']);
            return $cached;
        }
        
        $this->incrementStat('misses');
        Log::debug('AI suggestion cache miss', ['hash' => substr($hash, 0, 16) . '...']);
        
        return null;
    }

    /**
     * Store a suggestion result in cache.
     *
     * @param string $hash The cache hash
     * @param array<string, mixed> $response The response data to cache
     * @param int $ttlHours Time-to-live in hours (default: 24)
     * @return void
     */
    public function set(string $hash, array $response, int $ttlHours = 24): void
    {
        // Check if cache is enabled
        if (!$this->isEnabled()) {
            return;
        }
        
        // Validate TTL
        $ttlHours = $this->validateTtl($ttlHours);
        
        $key = $this->getCacheKey($hash);
        $ttlSeconds = $ttlHours * 3600;
        
        Cache::put($key, $response, now()->addSeconds($ttlSeconds));
        
        // Register this hash for tracking
        $this->registerHash($hash, $response, $ttlHours);
        
        Log::debug('Cached AI suggestion', [
            'hash' => substr($hash, 0, 16) . '...',
            'ttl_hours' => $ttlHours,
        ]);
    }

    /**
     * Remove a cached result.
     *
     * @param string $hash The cache hash
     * @return bool True if entry existed and was removed, false otherwise
     */
    public function invalidate(string $hash): bool
    {
        $key = $this->getCacheKey($hash);
        
        if (Cache::has($key)) {
            Cache::forget($key);
            $this->unregisterHash($hash);
            Log::info('Invalidated AI suggestion cache entry', ['hash' => substr($hash, 0, 16) . '...']);
            return true;
        }
        
        return false;
    }

    /**
     * Invalidate all cache entries for a specific indicator type.
     *
     * @param string $indicator The indicator type
     * @return int Number of entries invalidated
     */
    public function invalidateByIndicator(string $indicator): int
    {
        if (!in_array($indicator, self::VALID_INDICATORS, true)) {
            Log::warning('Invalid indicator for cache invalidation', ['indicator' => $indicator]);
            return 0;
        }
        
        // Get all registered hashes for this indicator
        $registry = $this->getRegistry();
        $count = 0;
        
        foreach ($registry as $hash => $meta) {
            if (isset($meta['indicator']) && $meta['indicator'] === $indicator) {
                if ($this->invalidate($hash)) {
                    $count++;
                }
            }
        }
        
        Log::info('Invalidated AI suggestion cache by indicator', [
            'indicator' => $indicator,
            'count' => $count,
        ]);
        
        return $count;
    }

    /**
     * Clear all AI suggestion cache entries.
     *
     * @return int Number of entries cleared
     */
    public function clear(): int
    {
        if (!$this->isEnabled()) {
            return 0;
        }
        
        // Get all registered hashes
        $registry = $this->getRegistry();
        $count = 0;
        
        // Clear each cache entry
        foreach (array_keys($registry) as $hash) {
            $key = $this->getCacheKey($hash);
            if (Cache::has($key)) {
                Cache::forget($key);
                $count++;
            }
        }
        
        // Also scan for any entries not in registry (direct cache sets)
        // This handles entries set directly via Cache::put() without using the service
        $count += $this->clearUnregisteredEntries();
        
        // Clear registry
        Cache::forget(self::REGISTRY_KEY);
        
        Log::info('Cleared AI suggestion cache', ['count' => $count]);
        
        return $count;
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed> Cache statistics
     */
    public function getStats(): array
    {
        $registry = $this->getRegistry();
        $totalEntries = count($registry);
        
        // Get persisted stats
        $persistedStats = Cache::get(self::STATS_KEY, [
            'hits' => 0,
            'misses' => 0,
            'evictions' => 0,
        ]);
        
        // Merge with in-memory stats
        $hits = $persistedStats['hits'] + $this->memoryStats['hits'];
        $misses = $persistedStats['misses'] + $this->memoryStats['misses'];
        $evictions = $persistedStats['evictions'] + $this->memoryStats['evictions'];
        
        $totalRequests = $hits + $misses;
        $hitRatio = $totalRequests > 0 ? round($hits / $totalRequests, 4) : 0;
        $evictionRate = $totalRequests > 0 ? round($evictions / $totalRequests, 4) : 0;
        
        // Get per-indicator breakdown
        $byIndicator = [];
        foreach ($registry as $meta) {
            if (isset($meta['indicator'])) {
                $ind = $meta['indicator'];
                $byIndicator[$ind] = ($byIndicator[$ind] ?? 0) + 1;
            }
        }
        
        return [
            'total_entries' => $totalEntries,
            'hits' => $hits,
            'misses' => $misses,
            'hit_ratio' => $hitRatio,
            'evictions' => $evictions,
            'eviction_rate' => $evictionRate,
            'by_indicator' => $byIndicator,
        ];
    }

    /**
     * Reset statistics counters.
     *
     * @return void
     */
    public function resetStats(): void
    {
        $this->memoryStats = [
            'hits' => 0,
            'misses' => 0,
            'evictions' => 0,
        ];
        
        Cache::forget(self::STATS_KEY);
    }

    /**
     * Log cache metrics.
     *
     * @return void
     */
    public function logMetrics(): void
    {
        $stats = $this->getStats();
        
        Log::info('AI suggestion cache metrics', [
            'total_entries' => $stats['total_entries'],
            'hits' => $stats['hits'],
            'misses' => $stats['misses'],
            'hit_ratio' => $stats['hit_ratio'],
            'evictions' => $stats['evictions'],
        ]);
    }

    /**
     * Check if a string is a valid indicator.
     *
     * @param string $indicator The indicator to check
     * @return bool True if valid
     */
    public function isValidIndicator(string $indicator): bool
    {
        return in_array($indicator, self::VALID_INDICATORS, true);
    }

    /**
     * Get list of valid indicators.
     *
     * @return array<string> List of valid indicators
     */
    public function getValidIndicators(): array
    {
        return self::VALID_INDICATORS;
    }

    /**
     * Normalize context array for consistent hashing.
     *
     * Sorts keys recursively to ensure identical data produces identical hashes.
     *
     * @param array<string, mixed> $context The context array
     * @return array<string, mixed> Normalized context
     */
    private function normalizeContext(array $context): array
    {
        // Sort keys alphabetically
        ksort($context);
        
        // Recursively sort nested arrays
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = $this->normalizeContext($value);
            }
        }
        
        return $context;
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
     * @param int $ttlHours Proposed TTL in hours
     * @return int Valid TTL in hours
     */
    private function validateTtl(int $ttlHours): int
    {
        // Get configured default TTL
        $defaultTtl = (int) config('ai.cache.ttl_hours', 24);
        
        // Use configured TTL if parameter is invalid
        if ($ttlHours <= 0) {
            return max(1, $defaultTtl); // Minimum 1 hour
        }
        
        // Cap at 168 hours (7 days) to prevent stale cache
        return min($ttlHours, 168);
    }

    /**
     * Increment a stat counter.
     *
     * @param string $stat The stat name (hits, misses, evictions)
     * @return void
     */
    private function incrementStat(string $stat): void
    {
        // Increment in-memory stat
        $this->memoryStats[$stat] = ($this->memoryStats[$stat] ?? 0) + 1;
        
        // Also increment persisted stat
        $stats = Cache::get(self::STATS_KEY, [
            'hits' => 0,
            'misses' => 0,
            'evictions' => 0,
        ]);
        
        $stats[$stat] = ($stats[$stat] ?? 0) + 1;
        Cache::put(self::STATS_KEY, $stats, now()->addDays(30));
    }

    /**
     * Get the registry of all cache entries.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getRegistry(): array
    {
        return Cache::get(self::REGISTRY_KEY, []);
    }

    /**
     * Register a hash in the registry.
     *
     * @param string $hash The cache hash
     * @param array<string, mixed> $response The cached response
     * @param int $ttlHours The TTL in hours
     * @return void
     */
    private function registerHash(string $hash, array $response, int $ttlHours): void
    {
        $registry = $this->getRegistry();
        
        $registry[$hash] = [
            'indicator' => $response['indicator'] ?? 'unknown',
            'provider' => $response['provider'] ?? 'unknown',
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addHours($ttlHours)->toIso8601String(),
        ];
        
        Cache::put(self::REGISTRY_KEY, $registry, now()->addDays(30));
    }

    /**
     * Unregister a hash from the registry.
     *
     * @param string $hash The cache hash
     * @return void
     */
    private function unregisterHash(string $hash): void
    {
        $registry = $this->getRegistry();
        unset($registry[$hash]);
        Cache::put(self::REGISTRY_KEY, $registry, now()->addDays(30));
    }

    /**
     * Clear entries that were set directly without using the service.
     * Only works with array cache driver.
     *
     * @return int Number of entries cleared
     */
    private function clearUnregisteredEntries(): int
    {
        $driver = config('cache.default');
        
        // For array driver, we can access the underlying store
        if ($driver === 'array') {
            $store = Cache::getStore();
            if (method_exists($store, 'getStore')) {
                $underlyingStore = $store->getStore();
                if (is_object($underlyingStore) && property_exists($underlyingStore, 'storage')) {
                    $storage = $underlyingStore->storage ?? [];
                    $count = 0;
                    $prefix = self::CACHE_PREFIX;
                    
                    // Find and remove keys with our prefix that aren't in registry
                    $registry = $this->getRegistry();
                    foreach (array_keys($storage) as $key) {
                        if (str_starts_with($key, $prefix) && !str_ends_with($key, '_registry') && !str_ends_with($key, '_stats')) {
                            // Extract hash from key
                            $hash = substr($key, strlen($prefix));
                            if (!isset($registry[$hash])) {
                                Cache::forget($key);
                                $count++;
                            }
                        }
                    }
                    
                    return $count;
                }
            }
        }
        
        // For Redis, we could use pattern matching
        // For other drivers, we can't easily scan
        
        return 0;
    }
}
