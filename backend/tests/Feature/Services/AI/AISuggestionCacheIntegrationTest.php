<?php

namespace Tests\Feature\Services\AI;

use App\Services\AI\AISuggestionCache;
use App\Services\AI\AISuggestionService;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\DTOs\SuggestionResult;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Feature tests for AI Suggestion Cache integration.
 * 
 * AC-4: Integration with AISuggestionService
 * AC-8: Feature Tests
 * 
 * @group ai
 * @group task-21-6
 * @group cache
 * @group feature
 */
class AISuggestionCacheIntegrationTest extends TestCase
{
    private const SERVICE_CLASS = 'App\Services\AI\AISuggestionService';
    private const CACHE_CLASS = 'App\Services\AI\AISuggestionCache';
    private const INTERFACE_CLASS = 'App\Services\AI\Contracts\AIProviderInterface';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Create a mock provider that returns a specific result.
     */
    private function createMockProvider(string $providerName = 'openrouter', ?SuggestionResult $result = null): AIProviderInterface
    {
        $mock = $this->createMock(self::INTERFACE_CLASS);
        
        $mock->method('getName')->willReturn($providerName);
        
        if ($result) {
            $mock->method('generate')->willReturn(
                new \App\Services\AI\DTOs\AIResponse(
                    success: true,
                    content: json_encode($result->jsonSerialize()),
                    tokensUsed: 100,
                    latencyMs: 500,
                    errorMessage: null
                )
            );
        }
        
        return $mock;
    }

    /**
     * Create a sample suggestion result.
     */
    private function createSampleResult(string $indicator = 'title', int $score = 85): SuggestionResult
    {
        return new SuggestionResult(
            indicator: $indicator,
            score: $score,
            issues: ['Test issue'],
            suggestions: [['type' => 'improve', 'text' => 'Test suggestion']],
            bestPractices: ['Test best practice'],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
    }

    // =========================================================================
    // AC-8: Cache Hit Scenario
    // =========================================================================

    #[Test]
    #[TestDox('INT-001: Cache hit returns cached result without calling provider')]
    public function cache_hit_returns_cached_result_without_calling_provider(): void
    {
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        // Pre-populate cache
        $cachedResult = $this->createSampleResult($indicator, 90);
        $cache = app(self::CACHE_CLASS);
        $cache->put($indicator, $context, $cachedResult);
        
        // Create service with mock provider that should NOT be called
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->expects($this->never())
            ->method('generate');
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // Call should return cached result
        $result = $service->getSuggestions($indicator, $context);
        
        $this->assertEquals(90, $result->score, 'Should return cached result score');
    }

    #[Test]
    #[TestDox('INT-002: Cache hit preserves provider info from cache')]
    public function cache_hit_preserves_provider_info_from_cache(): void
    {
        $indicator = 'meta_description';
        $context = ['url' => 'https://example.com'];
        
        // Pre-populate cache with specific provider
        $cachedResult = new SuggestionResult(
            indicator: $indicator,
            score: 75,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'vercel', // Different provider
            model: 'gpt-4o-mini',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache = app(self::CACHE_CLASS);
        $cache->put($indicator, $context, $cachedResult);
        
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        $result = $service->getSuggestions($indicator, $context);
        
        $this->assertEquals('vercel', $result->provider, 'Should preserve original provider from cache');
        $this->assertEquals('gpt-4o-mini', $result->model, 'Should preserve original model from cache');
    }

    // =========================================================================
    // AC-8: Cache Miss Scenario
    // =========================================================================

    #[Test]
    #[TestDox('INT-010: Cache miss calls provider and caches result')]
    public function cache_miss_calls_provider_and_caches_result(): void
    {
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $sampleResult = $this->createSampleResult($indicator, 80);
        
        // Create mock provider that will be called
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->expects($this->once())
            ->method('generate')
            ->willReturn(new \App\Services\AI\DTOs\AIResponse(
                success: true,
                content: json_encode($sampleResult->jsonSerialize()),
                tokensUsed: 100,
                latencyMs: 500,
                errorMessage: null
            ));
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // First call - cache miss
        $result = $service->getSuggestions($indicator, $context);
        
        $this->assertEquals(80, $result->score);
        
        // Verify result is now cached
        $cache = app(self::CACHE_CLASS);
        $this->assertTrue($cache->has($indicator, $context), 'Result should be cached after generation');
    }

    #[Test]
    #[TestDox('INT-011: Second call with same params returns cached result')]
    public function second_call_with_same_params_returns_cached_result(): void
    {
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $sampleResult = $this->createSampleResult($indicator, 70);
        
        // Mock provider should only be called once
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->expects($this->once())
            ->method('generate')
            ->willReturn(new \App\Services\AI\DTOs\AIResponse(
                success: true,
                content: json_encode($sampleResult->jsonSerialize()),
                tokensUsed: 100,
                latencyMs: 500,
                errorMessage: null
            ));
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // First call
        $result1 = $service->getSuggestions($indicator, $context);
        $this->assertEquals(70, $result1->score);
        
        // Second call - should use cache
        $result2 = $service->getSuggestions($indicator, $context);
        $this->assertEquals(70, $result2->score);
    }

    #[Test]
    #[TestDox('INT-012: Different context triggers new generation')]
    public function different_context_triggers_new_generation(): void
    {
        $indicator = 'title';
        $context1 = ['url' => 'https://example.com/page1'];
        $context2 = ['url' => 'https://example.com/page2'];
        
        // Mock provider should be called twice
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(
                new \App\Services\AI\DTOs\AIResponse(
                    success: true,
                    content: json_encode($this->createSampleResult($indicator, 80)->jsonSerialize()),
                    tokensUsed: 100,
                    latencyMs: 500,
                    errorMessage: null
                ),
                new \App\Services\AI\DTOs\AIResponse(
                    success: true,
                    content: json_encode($this->createSampleResult($indicator, 90)->jsonSerialize()),
                    tokensUsed: 100,
                    latencyMs: 500,
                    errorMessage: null
                )
            );
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        $result1 = $service->getSuggestions($indicator, $context1);
        $result2 = $service->getSuggestions($indicator, $context2);
        
        // Different results for different contexts
        $this->assertEquals(80, $result1->score);
        $this->assertEquals(90, $result2->score);
    }

    // =========================================================================
    // AC-4: Cache Bypass
    // =========================================================================

    #[Test]
    #[TestDox('INT-020: Cache bypass with no_cache=true')]
    public function cache_bypass_with_no_cache_true(): void
    {
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        // Pre-populate cache
        $cachedResult = $this->createSampleResult($indicator, 50);
        $cache = app(self::CACHE_CLASS);
        $cache->put($indicator, $context, $cachedResult);
        
        // Mock provider should be called when bypassing cache
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->expects($this->once())
            ->method('generate')
            ->willReturn(new \App\Services\AI\DTOs\AIResponse(
                success: true,
                content: json_encode($this->createSampleResult($indicator, 99)->jsonSerialize()),
                tokensUsed: 100,
                latencyMs: 500,
                errorMessage: null
            ));
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // Bypass cache
        $result = $service->getSuggestions($indicator, $context, noCache: true);
        
        $this->assertEquals(99, $result->score, 'Should get fresh result when bypassing cache');
    }

    #[Test]
    #[TestDox('INT-021: Cache bypass does not update cache')]
    public function cache_bypass_does_not_update_cache(): void
    {
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        // Pre-populate cache with specific score
        $cachedResult = $this->createSampleResult($indicator, 50);
        $cache = app(self::CACHE_CLASS);
        $cache->put($indicator, $context, $cachedResult);
        
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->method('generate')
            ->willReturn(new \App\Services\AI\DTOs\AIResponse(
                success: true,
                content: json_encode($this->createSampleResult($indicator, 99)->jsonSerialize()),
                tokensUsed: 100,
                latencyMs: 500,
                errorMessage: null
            ));
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // Bypass cache
        $service->getSuggestions($indicator, $context, noCache: true);
        
        // Original cache should still be intact
        $cachedAfterBypass = $cache->get($indicator, $context);
        $this->assertEquals(50, $cachedAfterBypass->score, 'Cache should not be updated during bypass');
    }

    // =========================================================================
    // AC-4: Debug Mode
    // =========================================================================

    #[Test]
    #[TestDox('INT-030: Cache not used in debug mode')]
    public function cache_not_used_in_debug_mode(): void
    {
        config(['ai.debug' => true]);
        
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        // Pre-populate cache
        $cachedResult = $this->createSampleResult($indicator, 50);
        $cache = app(self::CACHE_CLASS);
        $cache->put($indicator, $context, $cachedResult);
        
        // Mock provider should be called in debug mode
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->expects($this->once())
            ->method('generate')
            ->willReturn(new \App\Services\AI\DTOs\AIResponse(
                success: true,
                content: json_encode($this->createSampleResult($indicator, 99)->jsonSerialize()),
                tokensUsed: 100,
                latencyMs: 500,
                errorMessage: null
            ));
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // Debug mode should bypass cache
        $result = $service->getSuggestions($indicator, $context);
        
        $this->assertEquals(99, $result->score, 'Should get fresh result in debug mode');
    }

    #[Test]
    #[TestDox('INT-031: Debug mode does not store to cache')]
    public function debug_mode_does_not_store_to_cache(): void
    {
        config(['ai.debug' => true]);
        
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->method('generate')
            ->willReturn(new \App\Services\AI\DTOs\AIResponse(
                success: true,
                content: json_encode($this->createSampleResult($indicator, 99)->jsonSerialize()),
                tokensUsed: 100,
                latencyMs: 500,
                errorMessage: null
            ));
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        $service->getSuggestions($indicator, $context);
        
        // Cache should be empty after debug mode call
        $cache = app(self::CACHE_CLASS);
        $this->assertFalse($cache->has($indicator, $context), 'Cache should not be populated in debug mode');
    }

    // =========================================================================
    // AC-8: Integration Edge Cases
    // =========================================================================

    #[Test]
    #[TestDox('INT-040: Service correctly uses injected cache')]
    public function service_correctly_uses_injected_cache(): void
    {
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $sampleResult = $this->createSampleResult($indicator, 75);
        
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->method('generate')
            ->willReturn(new \App\Services\AI\DTOs\AIResponse(
                success: true,
                content: json_encode($sampleResult->jsonSerialize()),
                tokensUsed: 100,
                latencyMs: 500,
                errorMessage: null
            ));
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // First call
        $service->getSuggestions($indicator, $context);
        
        // Verify cache was populated
        $cache = app(self::CACHE_CLASS);
        $this->assertTrue($cache->has($indicator, $context));
        
        // Clear cache
        $cache->clear();
        
        // Verify cache is empty
        $this->assertFalse($cache->has($indicator, $context));
    }

    #[Test]
    #[TestDox('INT-041: Multiple indicators cached independently')]
    public function multiple_indicators_cached_independently(): void
    {
        $context = ['url' => 'https://example.com'];
        
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->method('generate')
            ->willReturnOnConsecutiveCalls(
                new \App\Services\AI\DTOs\AIResponse(
                    success: true,
                    content: json_encode($this->createSampleResult('title', 80)->jsonSerialize()),
                    tokensUsed: 100,
                    latencyMs: 500,
                    errorMessage: null
                ),
                new \App\Services\AI\DTOs\AIResponse(
                    success: true,
                    content: json_encode($this->createSampleResult('meta_description', 90)->jsonSerialize()),
                    tokensUsed: 100,
                    latencyMs: 500,
                    errorMessage: null
                ),
                new \App\Services\AI\DTOs\AIResponse(
                    success: true,
                    content: json_encode($this->createSampleResult('h1', 70)->jsonSerialize()),
                    tokensUsed: 100,
                    latencyMs: 500,
                    errorMessage: null
                )
            );
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // Get suggestions for different indicators
        $titleResult = $service->getSuggestions('title', $context);
        $metaResult = $service->getSuggestions('meta_description', $context);
        $h1Result = $service->getSuggestions('h1', $context);
        
        $this->assertEquals(80, $titleResult->score);
        $this->assertEquals(90, $metaResult->score);
        $this->assertEquals(70, $h1Result->score);
        
        // Verify all are cached
        $cache = app(self::CACHE_CLASS);
        $this->assertTrue($cache->has('title', $context));
        $this->assertTrue($cache->has('meta_description', $context));
        $this->assertTrue($cache->has('h1', $context));
    }

    #[Test]
    #[TestDox('INT-042: Cache isolation between different URLs')]
    public function cache_isolation_between_different_urls(): void
    {
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->method('generate')
            ->willReturnOnConsecutiveCalls(
                new \App\Services\AI\DTOs\AIResponse(
                    success: true,
                    content: json_encode($this->createSampleResult('title', 80)->jsonSerialize()),
                    tokensUsed: 100,
                    latencyMs: 500,
                    errorMessage: null
                ),
                new \App\Services\AI\DTOs\AIResponse(
                    success: true,
                    content: json_encode($this->createSampleResult('title', 90)->jsonSerialize()),
                    tokensUsed: 100,
                    latencyMs: 500,
                    errorMessage: null
                )
            );
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // Same indicator, different URLs
        $result1 = $service->getSuggestions('title', ['url' => 'https://example.com/page1']);
        $result2 = $service->getSuggestions('title', ['url' => 'https://example.com/page2']);
        
        $this->assertEquals(80, $result1->score);
        $this->assertEquals(90, $result2->score);
    }

    // =========================================================================
    // AC-8: Performance Tests
    // =========================================================================

    #[Test]
    #[TestDox('INT-050: Cache hit is faster than cache miss')]
    public function cache_hit_is_faster_than_cache_miss(): void
    {
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $sampleResult = $this->createSampleResult($indicator, 75);
        
        $mockProvider = $this->createMock(self::INTERFACE_CLASS);
        $mockProvider->method('generate')
            ->willReturnCallback(function () use ($sampleResult) {
                usleep(10000); // Simulate 10ms API call
                return new \App\Services\AI\DTOs\AIResponse(
                    success: true,
                    content: json_encode($sampleResult->jsonSerialize()),
                    tokensUsed: 100,
                    latencyMs: 10,
                    errorMessage: null
                );
            });
        $mockProvider->method('getName')->willReturn('openrouter');
        
        $service = new (self::SERVICE_CLASS)($mockProvider);
        
        // First call (cache miss)
        $start1 = microtime(true);
        $service->getSuggestions($indicator, $context);
        $time1 = microtime(true) - $start1;
        
        // Second call (cache hit)
        $start2 = microtime(true);
        $service->getSuggestions($indicator, $context);
        $time2 = microtime(true) - $start2;
        
        // Cache hit should be significantly faster
        $this->assertLessThan($time1, $time2 * 10, 'Cache hit should be at least 10x faster than cache miss');
    }
}
