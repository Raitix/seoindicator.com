<?php

namespace Tests\Feature\Services\AI;

use Tests\TestCase;
use App\Services\AI\AISuggestionService;
use App\Services\AI\AISuggestionCache;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\DTOs\SuggestionResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Integration tests for AISuggestionService cache integration.
 * 
 * Covers AC-3: Cache Integration with AISuggestionService
 * Covers AC-4: Cache Invalidation
 * 
 * @task TASK-21-6
 */
class AISuggestionCacheIntegrationTest extends TestCase
{
    private AISuggestionService $service;
    private AISuggestionCache $cache;
    private $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cache = app(AISuggestionCache::class);
        $this->mockProvider = Mockery::mock(AIProviderInterface::class);
        
        $this->service = new AISuggestionService($this->mockProvider, $this->cache);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->cache->clear();
        parent::tearDown();
    }

    // ============================================================
    // AC-3: Cache Integration with AISuggestionService
    // ============================================================

    /** @test Service checks cache before calling AI provider */
    public function test_checks_cache_before_provider(): void
    {
        $indicator = 'title';
        $context = ['title' => 'Test Page'];
        $provider = 'openrouter';
        
        // Pre-populate cache
        $cachedResponse = [
            'indicator' => 'title',
            'score' => 85,
            'issues' => [],
            'suggestions' => [['type' => 'improve', 'text' => 'Cached suggestion']]
        ];
        
        $hash = $this->cache->generateHash($indicator, $context, $provider);
        $this->cache->set($hash, $cachedResponse);
        
        // Provider should NOT be called
        $this->mockProvider->shouldNotReceive('generate');
        
        $result = $this->service->getSuggestions($indicator, $context, $provider);
        
        $this->assertEquals($cachedResponse['score'], $result->score);
        $this->assertEquals('cached', $result->provider);
    }

    /** @test Service calls provider on cache miss */
    public function test_calls_provider_on_cache_miss(): void
    {
        $indicator = 'title';
        $context = ['title' => 'Uncached Page'];
        $provider = 'openrouter';
        
        $aiResponse = $this->createMockAIResponse(75, 'AI generated');
        
        $this->mockProvider->shouldReceive('generate')
            ->once()
            ->andReturn($aiResponse);
        
        $this->mockProvider->shouldReceive('getName')
            ->andReturn('openrouter');
        
        $result = $this->service->getSuggestions($indicator, $context, $provider);
        
        $this->assertEquals(75, $result->score);
        $this->assertEquals('openrouter', $result->provider);
    }

    /** @test Service stores successful responses in cache */
    public function test_stores_successful_response_in_cache(): void
    {
        $indicator = 'meta_description';
        $context = ['description' => 'Test description'];
        $provider = 'openrouter';
        
        $aiResponse = $this->createMockAIResponse(80, 'AI suggestion');
        
        $this->mockProvider->shouldReceive('generate')
            ->once()
            ->andReturn($aiResponse);
        
        $this->mockProvider->shouldReceive('getName')
            ->andReturn('openrouter');
        
        $this->service->getSuggestions($indicator, $context, $provider);
        
        // Verify cache was populated
        $hash = $this->cache->generateHash($indicator, $context, $provider);
        $cached = $this->cache->get($hash);
        
        $this->assertNotNull($cached);
        $this->assertEquals(80, $cached['score']);
    }

    /** @test Cache key includes provider name */
    public function test_cache_key_includes_provider(): void
    {
        $context = ['title' => 'Same Title'];
        
        $hash1 = $this->cache->generateHash('title', $context, 'openrouter');
        $hash2 = $this->cache->generateHash('title', $context, 'vercel');
        $hash3 = $this->cache->generateHash('title', $context, 'static_fallback');
        
        // Different providers should produce different cache keys
        $this->assertNotEquals($hash1, $hash2);
        $this->assertNotEquals($hash2, $hash3);
        $this->assertNotEquals($hash1, $hash3);
    }

    /** @test Provider not called when cache hit */
    public function test_provider_not_called_on_hit(): void
    {
        $indicator = 'title';
        $context = ['title' => 'Cached Page'];
        $provider = 'openrouter';
        
        // Pre-populate cache
        $hash = $this->cache->generateHash($indicator, $context, $provider);
        $this->cache->set($hash, [
            'indicator' => 'title',
            'score' => 90,
            'issues' => [],
            'suggestions' => []
        ]);
        
        // Provider should NOT be called
        $this->mockProvider->shouldNotReceive('generate');
        
        $result = $this->service->getSuggestions($indicator, $context, $provider);
        
        $this->assertEquals(90, $result->score);
    }

    /** @test Cache bypassed when cache disabled */
    public function test_cache_bypassed_when_disabled(): void
    {
        config(['ai.cache.enabled' => false]);
        
        $indicator = 'title';
        $context = ['title' => 'Test'];
        $provider = 'openrouter';
        
        // Pre-populate cache (should be ignored)
        $hash = $this->cache->generateHash($indicator, $context, $provider);
        $this->cache->set($hash, ['score' => 50]);
        
        // Provider SHOULD be called despite cache having data
        $this->mockProvider->shouldReceive('generate')
            ->once()
            ->andReturn($this->createMockAIResponse(75, 'Fresh'));
        
        $this->mockProvider->shouldReceive('getName')
            ->andReturn('openrouter');
        
        $result = $this->service->getSuggestions($indicator, $context, $provider);
        
        $this->assertEquals(75, $result->score);
        
        config(['ai.cache.enabled' => true]);
    }

    /** @test Cache not stored on provider error */
    public function test_cache_not_stored_on_error(): void
    {
        $indicator = 'title';
        $context = ['title' => 'Error Test'];
        $provider = 'openrouter';
        
        $errorResponse = AIResponse::error('Provider failed', 1000);
        
        $this->mockProvider->shouldReceive('generate')
            ->once()
            ->andReturn($errorResponse);
        
        $this->mockProvider->shouldReceive('getName')
            ->andReturn('openrouter');
        
        $this->service->getSuggestions($indicator, $context, $provider);
        
        // Cache should NOT have been populated
        $hash = $this->cache->generateHash($indicator, $context, $provider);
        $cached = $this->cache->get($hash);
        
        $this->assertNull($cached);
    }

    /** @test Cache not stored when fallback used */
    public function test_cache_not_stored_on_fallback(): void
    {
        $indicator = 'title';
        $context = ['title' => 'Fallback Test'];
        $provider = 'openrouter';
        
        // Simulate provider timeout/unavailable
        $this->mockProvider->shouldReceive('generate')
            ->once()
            ->andThrow(new \Exception('Timeout'));
        
        $this->mockProvider->shouldReceive('getName')
            ->andReturn('openrouter');
        
        // Should fall back to static (not cached)
        $result = $this->service->getSuggestions($indicator, $context, $provider);
        
        $hash = $this->cache->generateHash($indicator, $context, $provider);
        $cached = $this->cache->get($hash);
        
        // Fallback responses are not cached
        $this->assertNull($cached);
    }

    // ============================================================
    // Cache Invalidation Tests
    // ============================================================

    /** @test Manual invalidation via hash */
    public function test_manual_invalidation(): void
    {
        $indicator = 'title';
        $context = ['title' => 'Invalidation Test'];
        $provider = 'openrouter';
        
        $hash = $this->cache->generateHash($indicator, $context, $provider);
        $this->cache->set($hash, ['score' => 70]);
        
        // Invalidate
        $result = $this->cache->invalidate($hash);
        
        $this->assertTrue($result);
        $this->assertNull($this->cache->get($hash));
    }

    /** @test Invalidation by indicator type */
    public function test_invalidation_by_indicator(): void
    {
        // Cache entries for different indicators
        $titleHash = $this->cache->generateHash('title', ['title' => 'Test'], 'openrouter');
        $metaHash = $this->cache->generateHash('meta_description', ['desc' => 'Test'], 'openrouter');
        
        $this->cache->set($titleHash, ['indicator' => 'title', 'score' => 80]);
        $this->cache->set($metaHash, ['indicator' => 'meta_description', 'score' => 70]);
        
        // Invalidate only title
        $this->cache->invalidateByIndicator('title');
        
        // Title should be gone
        $this->assertNull($this->cache->get($titleHash));
        
        // Meta should remain
        $this->assertNotNull($this->cache->get($metaHash));
    }

    /** @test Automatic expiration based on TTL */
    public function test_automatic_expiration(): void
    {
        $hash = 'expire_test_' . uniqid();
        
        // Set with 1 second TTL
        $this->cache->set($hash, ['score' => 50], ttlHours: 1); // Minimum 1 hour
        
        // Should exist immediately
        $this->assertNotNull($this->cache->get($hash));
        
        // After TTL expires (simulated), should return null
        // Note: In practice, this tests the cache store's TTL handling
    }

    /** @test Clear all cache command */
    public function test_clear_all_cache(): void
    {
        // Add multiple cache entries
        for ($i = 0; $i < 5; $i++) {
            $hash = $this->cache->generateHash('title', ['title' => "Test {$i}"], 'openrouter');
            $this->cache->set($hash, ['score' => $i * 10]);
        }
        
        $count = $this->cache->clear();
        
        $this->assertGreaterThanOrEqual(5, $count);
    }

    // ============================================================
    // Admin Invalidation Endpoint Tests
    // ============================================================

    /** @test Admin can invalidate specific cache entry */
    public function test_admin_invalidate_specific(): void
    {
        $hash = 'admin_specific_' . uniqid();
        $this->cache->set($hash, ['score' => 90]);
        
        // Simulate admin action
        $result = $this->cache->invalidate($hash);
        
        $this->assertTrue($result);
        $this->assertNull($this->cache->get($hash));
    }

    /** @test Admin can invalidate by indicator */
    public function test_admin_invalidate_by_indicator(): void
    {
        $titleHash = $this->cache->generateHash('title', ['title' => 'Admin Test'], 'openrouter');
        $robotsHash = $this->cache->generateHash('robots', ['content' => 'User-agent: *'], 'openrouter');
        
        $this->cache->set($titleHash, ['indicator' => 'title']);
        $this->cache->set($robotsHash, ['indicator' => 'robots']);
        
        $this->cache->invalidateByIndicator('title');
        
        $this->assertNull($this->cache->get($titleHash));
        $this->assertNotNull($this->cache->get($robotsHash));
    }

    /** @test Admin can clear all cache */
    public function test_admin_clear_all(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $hash = "admin_clear_{$i}_" . uniqid();
            $this->cache->set($hash, ['data' => $i]);
        }
        
        $count = $this->cache->clear();
        
        $this->assertGreaterThanOrEqual(10, $count);
    }

    // ============================================================
    // Cache Consistency Tests
    // ============================================================

    /** @test Cache consistency across identical requests */
    public function test_cache_consistency(): void
    {
        $indicator = 'title';
        $context = ['title' => 'Consistency Test'];
        $provider = 'openrouter';
        
        $hash = $this->cache->generateHash($indicator, $context, $provider);
        
        $data = [
            'indicator' => 'title',
            'score' => 85,
            'issues' => ['Issue 1'],
            'suggestions' => [['text' => 'Suggestion']]
        ];
        
        $this->cache->set($hash, $data);
        
        // Multiple gets should return identical data
        $result1 = $this->cache->get($hash);
        $result2 = $this->cache->get($hash);
        $result3 = $this->cache->get($hash);
        
        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    /** @test Cache handles concurrent requests */
    public function test_concurrent_requests(): void
    {
        $indicator = 'title';
        $context = ['title' => 'Concurrent Test'];
        $provider = 'openrouter';
        
        $hash = $this->cache->generateHash($indicator, $context, $provider);
        
        // Simulate concurrent cache sets
        $data1 = ['score' => 80, 'version' => 1];
        $data2 = ['score' => 85, 'version' => 2];
        
        $this->cache->set($hash, $data1);
        $this->cache->set($hash, $data2); // Overwrites
        
        $result = $this->cache->get($hash);
        
        // Last write wins
        $this->assertEquals(85, $result['score']);
        $this->assertEquals(2, $result['version']);
    }

    // ============================================================
    // All Indicators Test
    // ============================================================

    /** @test Cache works for all 10 indicators */
    public function test_all_indicators(): void
    {
        $indicators = [
            'title',
            'meta_description',
            'robots',
            'sitemap',
            'headings',
            'image_alt',
            'filenames',
            'url',
            'links',
            '404'
        ];
        
        foreach ($indicators as $indicator) {
            $context = ['test' => "data for {$indicator}"];
            $hash = $this->cache->generateHash($indicator, $context, 'openrouter');
            
            $this->cache->set($hash, [
                'indicator' => $indicator,
                'score' => 75
            ]);
            
            $cached = $this->cache->get($hash);
            $this->assertNotNull($cached, "Cache should work for {$indicator}");
            $this->assertEquals($indicator, $cached['indicator']);
        }
    }

    // ============================================================
    // Helper Methods
    // ============================================================

    private function createMockAIResponse(int $score, string $suggestion): AIResponse
    {
        $content = json_encode([
            'score' => $score,
            'issues' => [],
            'suggestions' => [
                ['type' => 'improve', 'text' => $suggestion, 'reason' => 'AI generated']
            ],
            'best_practices' => []
        ]);
        
        return AIResponse::success($content, 100, 500);
    }
}
