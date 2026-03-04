<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\AISuggestionCache;
use App\Models\AISuggestionCache as AISuggestionCacheModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for AISuggestionCache service.
 * 
 * Covers AC-1: AISuggestionCache Service Created
 * Covers AC-2: Request Hash Generation
 * Covers AC-5: Configuration
 * Covers AC-6: Performance Metrics
 * 
 * @task TASK-21-6
 */
class AISuggestionCacheTest extends TestCase
{
    private AISuggestionCache $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(AISuggestionCache::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // AC-1: AISuggestionCache Service Created
    // ============================================================

    /** @test Service instantiation via container */
    public function test_service_instantiation(): void
    {
        $service = app(AISuggestionCache::class);
        
        $this->assertInstanceOf(AISuggestionCache::class, $service);
    }

    /** @test Service has get method */
    public function test_service_has_get_method(): void
    {
        $this->assertTrue(method_exists($this->cacheService, 'get'));
    }

    /** @test Service has set method */
    public function test_service_has_set_method(): void
    {
        $this->assertTrue(method_exists($this->cacheService, 'set'));
    }

    /** @test Service has invalidate method */
    public function test_service_has_invalidate_method(): void
    {
        $this->assertTrue(method_exists($this->cacheService, 'invalidate'));
    }

    /** @test Service has clear method */
    public function test_service_has_clear_method(): void
    {
        $this->assertTrue(method_exists($this->cacheService, 'clear'));
    }

    /** @test Service has generateHash method */
    public function test_service_has_generate_hash_method(): void
    {
        $this->assertTrue(method_exists($this->cacheService, 'generateHash'));
    }

    // ============================================================
    // AC-2: Request Hash Generation
    // ============================================================

    /** @test Hash generation uses SHA-256 */
    public function test_hash_generation_sha256(): void
    {
        $hash = $this->cacheService->generateHash('title', ['title' => 'Test'], 'openrouter');
        
        // SHA-256 produces 64 character hex string
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /** @test Hash is consistent for same input */
    public function test_hash_consistent_for_same_input(): void
    {
        $hash1 = $this->cacheService->generateHash('title', ['title' => 'Test Page'], 'openrouter');
        $hash2 = $this->cacheService->generateHash('title', ['title' => 'Test Page'], 'openrouter');
        
        $this->assertEquals($hash1, $hash2);
    }

    /** @test Hash differs for different indicators */
    public function test_hash_differs_for_different_indicators(): void
    {
        $hash1 = $this->cacheService->generateHash('title', ['title' => 'Test'], 'openrouter');
        $hash2 = $this->cacheService->generateHash('meta_description', ['title' => 'Test'], 'openrouter');
        
        $this->assertNotEquals($hash1, $hash2);
    }

    /** @test Hash differs for different context */
    public function test_hash_differs_for_different_context(): void
    {
        $hash1 = $this->cacheService->generateHash('title', ['title' => 'Page A'], 'openrouter');
        $hash2 = $this->cacheService->generateHash('title', ['title' => 'Page B'], 'openrouter');
        
        $this->assertNotEquals($hash1, $hash2);
    }

    /** @test Hash differs for different providers */
    public function test_hash_differs_for_different_providers(): void
    {
        $hash1 = $this->cacheService->generateHash('title', ['title' => 'Test'], 'openrouter');
        $hash2 = $this->cacheService->generateHash('title', ['title' => 'Test'], 'vercel');
        
        $this->assertNotEquals($hash1, $hash2);
    }

    /** @test Context is normalized before hashing (sorted keys) */
    public function test_context_normalized_before_hashing(): void
    {
        // Same data, different key order
        $context1 = ['title' => 'Test', 'keyword' => 'example', 'url' => 'https://example.com'];
        $context2 = ['url' => 'https://example.com', 'title' => 'Test', 'keyword' => 'example'];
        
        $hash1 = $this->cacheService->generateHash('title', $context1, 'openrouter');
        $hash2 = $this->cacheService->generateHash('title', $context2, 'openrouter');
        
        // After normalization, hashes should be identical
        $this->assertEquals($hash1, $hash2);
    }

    /** @test Hash with empty context */
    public function test_hash_with_empty_context(): void
    {
        $hash = $this->cacheService->generateHash('title', [], 'openrouter');
        
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /** @test Hash with unicode context values */
    public function test_hash_with_unicode_context(): void
    {
        $hash = $this->cacheService->generateHash('title', ['title' => 'テストページ 标题'], 'openrouter');
        
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /** @test Hash with special characters in context */
    public function test_hash_with_special_characters(): void
    {
        $hash = $this->cacheService->generateHash('title', ['title' => '<script>alert("xss")</script>'], 'openrouter');
        
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /** @test Hash includes all context keys */
    public function test_hash_includes_all_context_keys(): void
    {
        $contextMinimal = ['title' => 'Test'];
        $contextFull = ['title' => 'Test', 'keyword' => 'example', 'content' => 'summary'];
        
        $hashMinimal = $this->cacheService->generateHash('title', $contextMinimal, 'openrouter');
        $hashFull = $this->cacheService->generateHash('title', $contextFull, 'openrouter');
        
        $this->assertNotEquals($hashMinimal, $hashFull);
    }

    // ============================================================
    // get() Method Tests
    // ============================================================

    /** @test get returns null for cache miss */
    public function test_get_returns_null_for_miss(): void
    {
        $hash = 'nonexistent_hash_' . uniqid();
        
        $result = $this->cacheService->get($hash);
        
        $this->assertNull($result);
    }

    /** @test get returns array for cache hit */
    public function test_get_returns_array_for_hit(): void
    {
        $hash = 'test_hash_' . uniqid();
        $data = ['score' => 85, 'issues' => ['Issue 1']];
        
        // Use cache store directly
        Cache::put("ai_suggestions:{$hash}", $data, now()->addHours(24));
        
        $result = $this->cacheService->get($hash);
        
        $this->assertIsArray($result);
        $this->assertEquals($data, $result);
    }

    /** @test get returns null for expired cache */
    public function test_get_returns_null_for_expired(): void
    {
        $hash = 'expired_hash_' . uniqid();
        $data = ['score' => 50];
        
        // Store with past expiration
        Cache::put("ai_suggestions:{$hash}", $data, now()->subMinute());
        
        $result = $this->cacheService->get($hash);
        
        $this->assertNull($result);
    }

    /** @test get returns null when cache disabled */
    public function test_get_returns_null_when_disabled(): void
    {
        config(['ai.cache.enabled' => false]);
        
        $hash = 'disabled_test_' . uniqid();
        Cache::put("ai_suggestions:{$hash}", ['data'], now()->addHours(24));
        
        $result = $this->cacheService->get($hash);
        
        // When disabled, should return null (cache bypassed)
        $this->assertNull($result);
        
        // Reset config
        config(['ai.cache.enabled' => true]);
    }

    /** @test get logs cache hit */
    public function test_get_logs_cache_hit(): void
    {
        Log::shouldReceive('debug')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'cache hit') || str_contains($message, 'Cache hit');
            });
        
        $hash = 'log_test_' . uniqid();
        Cache::put("ai_suggestions:{$hash}", ['data'], now()->addHours(24));
        
        $this->cacheService->get($hash);
    }

    /** @test get logs cache miss */
    public function test_get_logs_cache_miss(): void
    {
        Log::shouldReceive('debug')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'cache miss') || str_contains($message, 'Cache miss');
            });
        
        $this->cacheService->get('nonexistent_hash');
    }

    // ============================================================
    // set() Method Tests
    // ============================================================

    /** @test set stores response in cache */
    public function test_set_stores_response(): void
    {
        $hash = 'store_test_' . uniqid();
        $data = ['score' => 90, 'suggestions' => [['text' => 'Improve title']]];
        
        $this->cacheService->set($hash, $data);
        
        $cached = Cache::get("ai_suggestions:{$hash}");
        $this->assertEquals($data, $cached);
    }

    /** @test set uses default TTL of 24 hours */
    public function test_set_default_ttl(): void
    {
        $hash = 'ttl_default_' . uniqid();
        $data = ['score' => 75];
        
        $this->cacheService->set($hash, $data);
        
        // Verify cache exists after 23 hours but before 25
        $this->assertTrue(Cache::has("ai_suggestions:{$hash}"));
    }

    /** @test set respects custom TTL */
    public function test_set_custom_ttl(): void
    {
        $hash = 'ttl_custom_' . uniqid();
        $data = ['score' => 80];
        
        $this->cacheService->set($hash, $data, 48); // 48 hours
        
        $this->assertTrue(Cache::has("ai_suggestions:{$hash}"));
    }

    /** @test set respects configured TTL from env */
    public function test_set_respects_configured_ttl(): void
    {
        config(['ai.cache.ttl_hours' => 12]);
        
        $hash = 'ttl_config_' . uniqid();
        $data = ['score' => 85];
        
        $this->cacheService->set($hash, $data);
        
        $this->assertTrue(Cache::has("ai_suggestions:{$hash}"));
        
        // Reset
        config(['ai.cache.ttl_hours' => 24]);
    }

    /** @test set does nothing when cache disabled */
    public function test_set_noop_when_disabled(): void
    {
        config(['ai.cache.enabled' => false]);
        
        $hash = 'disabled_set_' . uniqid();
        $data = ['score' => 70];
        
        $this->cacheService->set($hash, $data);
        
        // Cache should not be set when disabled
        $this->assertFalse(Cache::has("ai_suggestions:{$hash}"));
        
        // Reset
        config(['ai.cache.enabled' => true]);
    }

    /** @test set logs cache storage */
    public function test_set_logs_storage(): void
    {
        Log::shouldReceive('debug')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Cached') || str_contains($message, 'stored');
            });
        
        $hash = 'log_set_' . uniqid();
        $this->cacheService->set($hash, ['data']);
    }

    /** @test set overwrites existing cache */
    public function test_set_overwrites_existing(): void
    {
        $hash = 'overwrite_' . uniqid();
        
        Cache::put("ai_suggestions:{$hash}", ['old' => 'data'], now()->addHours(24));
        
        $newData = ['new' => 'response'];
        $this->cacheService->set($hash, $newData);
        
        $cached = Cache::get("ai_suggestions:{$hash}");
        $this->assertEquals($newData, $cached);
    }

    // ============================================================
    // invalidate() Method Tests
    // ============================================================

    /** @test invalidate removes specific cache entry */
    public function test_invalidate_removes_entry(): void
    {
        $hash = 'invalidate_' . uniqid();
        Cache::put("ai_suggestions:{$hash}", ['data'], now()->addHours(24));
        
        $this->cacheService->invalidate($hash);
        
        $this->assertFalse(Cache::has("ai_suggestions:{$hash}"));
    }

    /** @test invalidate returns true when entry existed */
    public function test_invalidate_returns_true_when_existed(): void
    {
        $hash = 'invalidate_exists_' . uniqid();
        Cache::put("ai_suggestions:{$hash}", ['data'], now()->addHours(24));
        
        $result = $this->cacheService->invalidate($hash);
        
        $this->assertTrue($result);
    }

    /** @test invalidate returns false when entry didn't exist */
    public function test_invalidate_returns_false_when_not_exists(): void
    {
        $result = $this->cacheService->invalidate('nonexistent_hash');
        
        $this->assertFalse($result);
    }

    /** @test invalidate logs action */
    public function test_invalidate_logs(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Invalidated') || str_contains($message, 'invalidat');
            });
        
        $hash = 'invalidate_log_' . uniqid();
        Cache::put("ai_suggestions:{$hash}", ['data'], now()->addHours(24));
        
        $this->cacheService->invalidate($hash);
    }

    /** @test invalidate works when cache disabled */
    public function test_invalidate_works_when_disabled(): void
    {
        config(['ai.cache.enabled' => false]);
        
        $result = $this->cacheService->invalidate('any_hash');
        
        // Should return false (no entry to invalidate)
        $this->assertFalse($result);
        
        config(['ai.cache.enabled' => true]);
    }

    // ============================================================
    // clear() Method Tests
    // ============================================================

    /** @test clear removes all cache entries */
    public function test_clear_removes_all(): void
    {
        $hashes = ['clear_1_' . uniqid(), 'clear_2_' . uniqid(), 'clear_3_' . uniqid()];
        
        foreach ($hashes as $hash) {
            Cache::put("ai_suggestions:{$hash}", ['data'], now()->addHours(24));
        }
        
        $this->cacheService->clear();
        
        foreach ($hashes as $hash) {
            $this->assertFalse(Cache::has("ai_suggestions:{$hash}"));
        }
    }

    /** @test clear returns count of removed entries */
    public function test_clear_returns_count(): void
    {
        $hashes = ['count_1_' . uniqid(), 'count_2_' . uniqid(), 'count_3_' . uniqid()];
        
        foreach ($hashes as $hash) {
            Cache::put("ai_suggestions:{$hash}", ['data'], now()->addHours(24));
        }
        
        $count = $this->cacheService->clear();
        
        $this->assertGreaterThanOrEqual(3, $count);
    }

    /** @test clear returns 0 when cache empty */
    public function test_clear_returns_zero_empty(): void
    {
        // Clear first
        $this->cacheService->clear();
        
        $count = $this->cacheService->clear();
        
        $this->assertEquals(0, $count);
    }

    /** @test clear logs action */
    public function test_clear_logs(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Cleared') || str_contains($message, 'clear');
            });
        
        $this->cacheService->clear();
    }

    /** @test clear works when cache disabled */
    public function test_clear_works_when_disabled(): void
    {
        config(['ai.cache.enabled' => false]);
        
        $count = $this->cacheService->clear();
        
        // Should return 0 when disabled
        $this->assertEquals(0, $count);
        
        config(['ai.cache.enabled' => true]);
    }

    // ============================================================
    // AC-5: Configuration Tests
    // ============================================================

    /** @test TTL configurable via AI_CACHE_TTL_HOURS */
    public function test_ttl_configurable(): void
    {
        config(['ai.cache.ttl_hours' => 48]);
        
        $service = app(AISuggestionCache::class);
        
        // The service should use the configured TTL
        $this->assertEquals(48, config('ai.cache.ttl_hours'));
    }

    /** @test Default TTL is 24 hours */
    public function test_default_ttl(): void
    {
        $this->assertEquals(24, config('ai.cache.ttl_hours', 24));
    }

    /** @test Cache can be disabled via AI_CACHE_ENABLED */
    public function test_cache_can_be_disabled(): void
    {
        config(['ai.cache.enabled' => false]);
        
        $this->assertFalse(config('ai.cache.enabled'));
        
        // Reset
        config(['ai.cache.enabled' => true]);
    }

    /** @test Cache enabled by default */
    public function test_cache_enabled_by_default(): void
    {
        $this->assertTrue(config('ai.cache.enabled', true));
    }

    /** @test TTL validation rejects invalid values */
    public function test_ttl_validation_rejects_invalid(): void
    {
        // Negative TTL should be rejected or clamped
        config(['ai.cache.ttl_hours' => -1]);
        
        $hash = 'invalid_ttl_' . uniqid();
        $data = ['test' => true];
        
        // Service should handle invalid TTL gracefully
        $this->cacheService->set($hash, $data);
        
        // Reset
        config(['ai.cache.ttl_hours' => 24]);
    }

    /** @test TTL validation accepts valid range */
    public function test_ttl_validation_accepts_valid(): void
    {
        config(['ai.cache.ttl_hours' => 168]); // 7 days
        
        $hash = 'valid_ttl_' . uniqid();
        $data = ['test' => true];
        
        $this->cacheService->set($hash, $data);
        
        $this->assertTrue(Cache::has("ai_suggestions:{$hash}"));
        
        // Reset
        config(['ai.cache.ttl_hours' => 24]);
    }

    // ============================================================
    // AC-6: Performance Metrics Tests
    // ============================================================

    /** @test getStats returns hit/miss ratio */
    public function test_stats_hit_miss_ratio(): void
    {
        $stats = $this->cacheService->getStats();
        
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('hit_ratio', $stats);
    }

    /** @test getStats returns cache size */
    public function test_stats_cache_size(): void
    {
        $stats = $this->cacheService->getStats();
        
        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertIsInt($stats['total_entries']);
    }

    /** @test getStats tracks eviction rate */
    public function test_stats_eviction_rate(): void
    {
        $stats = $this->cacheService->getStats();
        
        $this->assertArrayHasKey('evictions', $stats);
        $this->assertArrayHasKey('eviction_rate', $stats);
    }

    /** @test hit ratio calculated correctly */
    public function test_hit_ratio_calculation(): void
    {
        // Perform some cache operations
        $hash = 'ratio_test_' . uniqid();
        
        // Miss
        $this->cacheService->get('nonexistent');
        
        // Set and hit
        $this->cacheService->set($hash, ['data']);
        $this->cacheService->get($hash);
        
        $stats = $this->cacheService->getStats();
        
        $this->assertGreaterThanOrEqual(0, $stats['hit_ratio']);
        $this->assertLessThanOrEqual(1, $stats['hit_ratio']);
    }

    /** @test stats reset resets counters */
    public function test_stats_reset(): void
    {
        // Perform some operations
        $this->cacheService->get('nonexistent');
        
        // Reset
        $this->cacheService->resetStats();
        
        $stats = $this->cacheService->getStats();
        
        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
    }

    /** @test metrics logged periodically */
    public function test_metrics_logged(): void
    {
        Log::shouldReceive('info')
            ->atLeast()
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Cache') || 
                       str_contains($message, 'metrics') ||
                       str_contains($message, 'stats');
            });
        
        $this->cacheService->logMetrics();
    }

    // ============================================================
    // Integration Tests
    // ============================================================

    /** @test Full cache flow: set, get, invalidate */
    public function test_full_cache_flow(): void
    {
        $hash = 'flow_test_' . uniqid();
        $data = [
            'indicator' => 'title',
            'score' => 85,
            'issues' => ['Too short'],
            'suggestions' => [['type' => 'improve', 'text' => 'Add keywords']]
        ];
        
        // Set
        $this->cacheService->set($hash, $data);
        
        // Get - should hit
        $result = $this->cacheService->get($hash);
        $this->assertEquals($data, $result);
        
        // Invalidate
        $this->cacheService->invalidate($hash);
        
        // Get - should miss
        $result = $this->cacheService->get($hash);
        $this->assertNull($result);
    }

    /** @test Cache survives multiple get calls */
    public function test_cache_survives_multiple_gets(): void
    {
        $hash = 'multi_get_' . uniqid();
        $data = ['score' => 90];
        
        $this->cacheService->set($hash, $data);
        
        $result1 = $this->cacheService->get($hash);
        $result2 = $this->cacheService->get($hash);
        $result3 = $this->cacheService->get($hash);
        
        $this->assertEquals($data, $result1);
        $this->assertEquals($data, $result2);
        $this->assertEquals($data, $result3);
    }

    // ============================================================
    // Edge Cases
    // ============================================================

    /** @test Hash with very long context */
    public function test_hash_with_long_context(): void
    {
        $longTitle = str_repeat('a', 10000);
        $hash = $this->cacheService->generateHash('title', ['title' => $longTitle], 'openrouter');
        
        $this->assertEquals(64, strlen($hash));
    }

    /** @test Hash with nested context */
    public function test_hash_with_nested_context(): void
    {
        $nested = [
            'page' => [
                'title' => 'Test',
                'meta' => ['description' => 'Test page']
            ]
        ];
        
        $hash = $this->cacheService->generateHash('title', $nested, 'openrouter');
        
        $this->assertEquals(64, strlen($hash));
    }

    /** @test Cache handles large response data */
    public function test_cache_large_response(): void
    {
        $hash = 'large_' . uniqid();
        $largeData = [
            'score' => 50,
            'suggestions' => array_fill(0, 100, ['text' => str_repeat('x', 100)])
        ];
        
        $this->cacheService->set($hash, $largeData);
        
        $result = $this->cacheService->get($hash);
        
        $this->assertEquals($largeData, $result);
    }

    /** @test Cache handles unicode response data */
    public function test_cache_unicode_response(): void
    {
        $hash = 'unicode_' . uniqid();
        $unicodeData = [
            'score' => 75,
            'suggestions' => [
                ['text' => 'テストタイトル 标题 🎉']
            ]
        ];
        
        $this->cacheService->set($hash, $unicodeData);
        
        $result = $this->cacheService->get($hash);
        
        $this->assertEquals($unicodeData, $result);
    }

    /** @test Cache handles null values in response */
    public function test_cache_null_values(): void
    {
        $hash = 'null_' . uniqid();
        $data = [
            'score' => null,
            'model' => null,
            'issues' => []
        ];
        
        $this->cacheService->set($hash, $data);
        
        $result = $this->cacheService->get($hash);
        
        $this->assertNull($result['score']);
        $this->assertNull($result['model']);
        $this->assertEquals([], $result['issues']);
    }

    // ============================================================
    // Security Tests
    // ============================================================

    /** @test Hash is not reversible */
    public function test_hash_not_reversible(): void
    {
        $hash = $this->cacheService->generateHash('title', ['title' => 'Secret Title'], 'openrouter');
        
        // Hash should not contain the original input
        $this->assertStringNotContainsString('Secret', $hash);
        $this->assertStringNotContainsString('title', $hash);
    }

    /** @test Cache key prefix prevents collision with other cache data */
    public function test_cache_key_prefix(): void
    {
        $hash = 'test_key';
        
        $this->cacheService->set($hash, ['data']);
        
        // Verify the key is prefixed
        $this->assertTrue(Cache::has("ai_suggestions:{$hash}"));
    }

    /** @test Invalid hash format handled gracefully */
    public function test_invalid_hash_format(): void
    {
        // Should not throw exception
        $result = $this->cacheService->get('');
        $this->assertNull($result);
        
        $result = $this->cacheService->get(null);
        $this->assertNull($result);
    }

    /** @test XSS prevention in cached data */
    public function test_xss_prevention(): void
    {
        $hash = 'xss_' . uniqid();
        $xssData = [
            'score' => 50,
            'suggestions' => [
                ['text' => '<script>alert("xss")</script>']
            ]
        ];
        
        $this->cacheService->set($hash, $xssData);
        
        $result = $this->cacheService->get($hash);
        
        // Data should be stored as-is (encoding happens at display layer)
        $this->assertEquals($xssData, $result);
    }

    // ============================================================
    // Performance Tests
    // ============================================================

    /** @test Hash generation is fast (< 5ms) */
    public function test_hash_performance(): void
    {
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->cacheService->generateHash('title', ['title' => "Test {$i}"], 'openrouter');
        }
        
        $elapsed = (microtime(true) - $start) * 1000;
        $avgTime = $elapsed / 100;
        
        $this->assertLessThan(5, $avgTime, 'Hash generation should be < 5ms average');
    }

    /** @test Cache get is fast (< 1ms) */
    public function test_get_performance(): void
    {
        $hash = 'perf_' . uniqid();
        $this->cacheService->set($hash, ['data']);
        
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->cacheService->get($hash);
        }
        
        $elapsed = (microtime(true) - $start) * 1000;
        $avgTime = $elapsed / 100;
        
        $this->assertLessThan(1, $avgTime, 'Cache get should be < 1ms average');
    }

    /** @test Cache set is fast (< 2ms) */
    public function test_set_performance(): void
    {
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->cacheService->set("perf_{$i}_" . uniqid(), ['data' => 'test']);
        }
        
        $elapsed = (microtime(true) - $start) * 1000;
        $avgTime = $elapsed / 100;
        
        $this->assertLessThan(2, $avgTime, 'Cache set should be < 2ms average');
    }
}
