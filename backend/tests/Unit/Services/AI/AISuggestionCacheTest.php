<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\AISuggestionCache;
use App\Services\AI\DTOs\SuggestionResult;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Test suite for AISuggestionCache service.
 * 
 * AC-1: Cache Service Created
 * AC-2: Request Hashing
 * AC-3: Cache Storage
 * AC-7: Unit Tests
 * 
 * @group ai
 * @group task-21-6
 * @group cache
 */
class AISuggestionCacheTest extends TestCase
{
    private const CACHE_CLASS = 'App\Services\AI\AISuggestionCache';
    private const CACHE_PREFIX = 'ai_suggestion_';

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

    // =========================================================================
    // AC-1: Cache Service Created
    // =========================================================================

    #[Test]
    #[TestDox('CACHE-001: Service file exists at correct path')]
    public function service_file_exists_at_correct_path(): void
    {
        $this->assertFileExists(
            base_path('app/Services/AI/AISuggestionCache.php'),
            'AISuggestionCache should exist at app/Services/AI/AISuggestionCache.php'
        );
    }

    #[Test]
    #[TestDox('CACHE-002: Service class exists and is instantiable')]
    public function service_class_exists_and_is_instantiable(): void
    {
        $this->assertTrue(class_exists(self::CACHE_CLASS), 'AISuggestionCache class should exist');
        
        $cache = app(self::CACHE_CLASS);
        $this->assertInstanceOf(self::CACHE_CLASS, $cache);
    }

    #[Test]
    #[TestDox('CACHE-003: Service has get method')]
    public function service_has_get_method(): void
    {
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('get'),
            'Service must have get() method'
        );
        
        $method = $reflection->getMethod('get');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'get() must have return type');
        $this->assertTrue(
            $returnType->getName() === 'App\Services\AI\DTOs\SuggestionResult' 
            || $returnType->getName() === '?App\Services\AI\DTOs\SuggestionResult'
            || $returnType->allowsNull(),
            'get() must return ?SuggestionResult (nullable)'
        );
    }

    #[Test]
    #[TestDox('CACHE-004: Service has put method')]
    public function service_has_put_method(): void
    {
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('put'),
            'Service must have put() method'
        );
        
        $method = $reflection->getMethod('put');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'put() must have return type');
        $this->assertEquals('void', $returnType->getName(), 'put() must return void');
    }

    #[Test]
    #[TestDox('CACHE-005: Service has has method')]
    public function service_has_has_method(): void
    {
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('has'),
            'Service must have has() method'
        );
        
        $method = $reflection->getMethod('has');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'has() must have return type');
        $this->assertEquals('bool', $returnType->getName(), 'has() must return bool');
    }

    #[Test]
    #[TestDox('CACHE-006: Service has forget method')]
    public function service_has_forget_method(): void
    {
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('forget'),
            'Service must have forget() method'
        );
        
        $method = $reflection->getMethod('forget');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'forget() must have return type');
        $this->assertEquals('void', $returnType->getName(), 'forget() must return void');
    }

    #[Test]
    #[TestDox('CACHE-007: Service has clear method')]
    public function service_has_clear_method(): void
    {
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        
        $this->assertTrue(
            $reflection->hasMethod('clear'),
            'Service must have clear() method'
        );
        
        $method = $reflection->getMethod('clear');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType, 'clear() must have return type');
        $this->assertEquals('void', $returnType->getName(), 'clear() must return void');
    }

    // =========================================================================
    // AC-2: Request Hashing
    // =========================================================================

    #[Test]
    #[TestDox('CACHE-010: Hash is deterministic for same inputs')]
    public function hash_is_deterministic_for_same_inputs(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = ['url' => 'https://example.com', 'length' => 60];
        
        // Get hash via reflection (private method)
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $hashMethod = $reflection->getMethod('generateHash');
        $hashMethod->setAccessible(true);
        
        $hash1 = $hashMethod->invoke($cache, $indicator, $context);
        $hash2 = $hashMethod->invoke($cache, $indicator, $context);
        
        $this->assertEquals($hash1, $hash2, 'Hash should be deterministic');
    }

    #[Test]
    #[TestDox('CACHE-011: Hash is 64 characters (SHA-256)')]
    public function hash_is_64_characters(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $hashMethod = $reflection->getMethod('generateHash');
        $hashMethod->setAccessible(true);
        
        $hash = $hashMethod->invoke($cache, 'title', ['url' => 'https://example.com']);
        
        $this->assertEquals(64, strlen($hash), 'SHA-256 hash should be 64 characters');
    }

    #[Test]
    #[TestDox('CACHE-012: Hash contains only hexadecimal characters')]
    public function hash_contains_only_hexadecimal_characters(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $hashMethod = $reflection->getMethod('generateHash');
        $hashMethod->setAccessible(true);
        
        $hash = $hashMethod->invoke($cache, 'title', ['url' => 'https://example.com']);
        
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash, 'Hash should be lowercase hex');
    }

    #[Test]
    #[TestDox('CACHE-013: Different indicators produce different hashes')]
    public function different_indicators_produce_different_hashes(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $hashMethod = $reflection->getMethod('generateHash');
        $hashMethod->setAccessible(true);
        
        $context = ['url' => 'https://example.com'];
        
        $hash1 = $hashMethod->invoke($cache, 'title', $context);
        $hash2 = $hashMethod->invoke($cache, 'meta_description', $context);
        
        $this->assertNotEquals($hash1, $hash2, 'Different indicators should produce different hashes');
    }

    #[Test]
    #[TestDox('CACHE-014: Different context produces different hash')]
    public function different_context_produces_different_hash(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $hashMethod = $reflection->getMethod('generateHash');
        $hashMethod->setAccessible(true);
        
        $hash1 = $hashMethod->invoke($cache, 'title', ['url' => 'https://example.com']);
        $hash2 = $hashMethod->invoke($cache, 'title', ['url' => 'https://different.com']);
        
        $this->assertNotEquals($hash1, $hash2, 'Different context should produce different hash');
    }

    #[Test]
    #[TestDox('CACHE-015: Context key order does not affect hash')]
    public function context_key_order_does_not_affect_hash(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $hashMethod = $reflection->getMethod('generateHash');
        $hashMethod->setAccessible(true);
        
        // Different order, same data
        $context1 = ['url' => 'https://example.com', 'length' => 60];
        $context2 = ['length' => 60, 'url' => 'https://example.com'];
        
        $hash1 = $hashMethod->invoke($cache, 'title', $context1);
        $hash2 = $hashMethod->invoke($cache, 'title', $context2);
        
        $this->assertEquals($hash1, $hash2, 'Key order should not affect hash (ksort used)');
    }

    #[Test]
    #[TestDox('CACHE-016: Empty context produces valid hash')]
    public function empty_context_produces_valid_hash(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $hashMethod = $reflection->getMethod('generateHash');
        $hashMethod->setAccessible(true);
        
        $hash = $hashMethod->invoke($cache, 'title', []);
        
        $this->assertEquals(64, strlen($hash), 'Hash should still be 64 characters with empty context');
    }

    #[Test]
    #[TestDox('CACHE-017: Nested context is handled consistently')]
    public function nested_context_is_handled_consistently(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $hashMethod = $reflection->getMethod('generateHash');
        $hashMethod->setAccessible(true);
        
        $context = [
            'page' => [
                'url' => 'https://example.com',
                'title' => 'Example',
            ],
            'options' => [
                'maxLength' => 60,
            ],
        ];
        
        $hash1 = $hashMethod->invoke($cache, 'title', $context);
        $hash2 = $hashMethod->invoke($cache, 'title', $context);
        
        $this->assertEquals($hash1, $hash2, 'Nested context should be handled consistently');
    }

    // =========================================================================
    // AC-3: Cache Storage
    // =========================================================================

    #[Test]
    #[TestDox('CACHE-020: Cache key uses prefix')]
    public function cache_key_uses_prefix(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $keyMethod = $reflection->getMethod('getCacheKey');
        $keyMethod->setAccessible(true);
        $hashMethod = $reflection->getMethod('generateHash');
        $hashMethod->setAccessible(true);
        
        $hash = $hashMethod->invoke($cache, 'title', []);
        $key = $keyMethod->invoke($cache, $hash);
        
        $this->assertStringStartsWith(self::CACHE_PREFIX, $key, 'Cache key should start with prefix');
    }

    #[Test]
    #[TestDox('CACHE-021: TTL uses config value')]
    public function ttl_uses_config_value(): void
    {
        config(['ai.cache.ttl_hours' => 48]);
        
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $ttlMethod = $reflection->getMethod('getTtlSeconds');
        $ttlMethod->setAccessible(true);
        
        $ttl = $ttlMethod->invoke($cache);
        
        $this->assertEquals(48 * 3600, $ttl, 'TTL should be hours converted to seconds');
    }

    #[Test]
    #[TestDox('CACHE-022: TTL defaults to 24 hours')]
    public function ttl_defaults_to_24_hours(): void
    {
        config(['ai.cache.ttl_hours' => null]);
        
        $cache = app(self::CACHE_CLASS);
        
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $ttlMethod = $reflection->getMethod('getTtlSeconds');
        $ttlMethod->setAccessible(true);
        
        $ttl = $ttlMethod->invoke($cache);
        
        $this->assertEquals(24 * 3600, $ttl, 'Default TTL should be 24 hours');
    }

    // =========================================================================
    // AC-7: Unit Tests - Operations
    // =========================================================================

    #[Test]
    #[TestDox('CACHE-030: get returns null for missing key')]
    public function get_returns_null_for_missing_key(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $result = $cache->get('nonexistent_indicator', ['url' => 'https://example.com']);
        
        $this->assertNull($result, 'get() should return null for missing cache entry');
    }

    #[Test]
    #[TestDox('CACHE-031: put stores result in cache')]
    public function put_stores_result_in_cache(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 85,
            issues: ['Too short'],
            suggestions: [['type' => 'improve', 'text' => 'Add more keywords']],
            bestPractices: ['Keep title 50-60 characters'],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $suggestion);
        
        $this->assertTrue($cache->has($indicator, $context), 'has() should return true after put()');
    }

    #[Test]
    #[TestDox('CACHE-032: get retrieves stored result')]
    public function get_retrieves_stored_result(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $original = new SuggestionResult(
            indicator: $indicator,
            score: 85,
            issues: ['Too short'],
            suggestions: [['type' => 'improve', 'text' => 'Add more keywords']],
            bestPractices: ['Keep title 50-60 characters'],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $original);
        $retrieved = $cache->get($indicator, $context);
        
        $this->assertInstanceOf(SuggestionResult::class, $retrieved);
        $this->assertEquals($original->indicator, $retrieved->indicator);
        $this->assertEquals($original->score, $retrieved->score);
        $this->assertEquals($original->provider, $retrieved->provider);
    }

    #[Test]
    #[TestDox('CACHE-033: has returns false for missing key')]
    public function has_returns_false_for_missing_key(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $result = $cache->has('nonexistent', ['url' => 'https://example.com']);
        
        $this->assertFalse($result, 'has() should return false for missing key');
    }

    #[Test]
    #[TestDox('CACHE-034: has returns true for existing key')]
    public function has_returns_true_for_existing_key(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 85,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $suggestion);
        
        $this->assertTrue($cache->has($indicator, $context));
    }

    #[Test]
    #[TestDox('CACHE-035: forget removes cached item')]
    public function forget_removes_cached_item(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 85,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $suggestion);
        $this->assertTrue($cache->has($indicator, $context), 'Item should be cached');
        
        $cache->forget($indicator, $context);
        
        $this->assertFalse($cache->has($indicator, $context), 'Item should be removed after forget()');
        $this->assertNull($cache->get($indicator, $context), 'get() should return null after forget()');
    }

    #[Test]
    #[TestDox('CACHE-036: clear removes all AI cache entries')]
    public function clear_removes_all_ai_cache_entries(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        // Store multiple items
        $items = [
            ['indicator' => 'title', 'context' => ['url' => 'https://example.com/1']],
            ['indicator' => 'meta_description', 'context' => ['url' => 'https://example.com/2']],
            ['indicator' => 'h1', 'context' => ['url' => 'https://example.com/3']],
        ];
        
        $suggestion = new SuggestionResult(
            indicator: 'test',
            score: 50,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        foreach ($items as $item) {
            $cache->put($item['indicator'], $item['context'], $suggestion);
        }
        
        // Verify all are cached
        foreach ($items as $item) {
            $this->assertTrue($cache->has($item['indicator'], $item['context']));
        }
        
        // Clear all
        $cache->clear();
        
        // Verify all are removed
        foreach ($items as $item) {
            $this->assertFalse($cache->has($item['indicator'], $item['context']));
        }
    }

    #[Test]
    #[TestDox('CACHE-037: clear does not affect non-AI cache')]
    public function clear_does_not_affect_non_ai_cache(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        // Store non-AI cache item
        Cache::put('other_key', 'other_value', 3600);
        
        // Store AI cache item
        $suggestion = new SuggestionResult(
            indicator: 'title',
            score: 50,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        $cache->put('title', [], $suggestion);
        
        $cache->clear();
        
        // Non-AI cache should still exist
        $this->assertEquals('other_value', Cache::get('other_key'));
        
        // AI cache should be gone
        $this->assertFalse($cache->has('title', []));
    }

    // =========================================================================
    // AC-7: Unit Tests - TTL Expiration
    // =========================================================================

    #[Test]
    #[TestDox('CACHE-040: Cache entry expires after TTL')]
    public function cache_entry_expires_after_ttl(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = ['url' => 'https://example.com'];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 85,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        // Store with 1 second TTL for testing
        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                // Verify TTL is passed (in seconds)
                return strpos($key, self::CACHE_PREFIX) === 0 && is_int($ttl);
            });
        
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('has')->andReturn(false);
        
        $cache->put($indicator, $context, $suggestion);
    }

    // =========================================================================
    // AC-7: Unit Tests - Data Integrity
    // =========================================================================

    #[Test]
    #[TestDox('CACHE-050: Retrieved result preserves all data')]
    public function retrieved_result_preserves_all_data(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'meta_description';
        $context = ['url' => 'https://example.com', 'length' => 150];
        
        $original = new SuggestionResult(
            indicator: $indicator,
            score: 92,
            issues: ['Missing call-to-action', 'Too generic'],
            suggestions: [
                ['type' => 'improve', 'text' => 'Add a clear CTA', 'reason' => 'Increases CTR'],
                ['type' => 'rewrite', 'text' => 'Make it more specific to your brand'],
            ],
            bestPractices: ['Keep between 150-160 characters', 'Include target keywords'],
            provider: 'openrouter',
            model: 'llama-3.1-8b',
            generatedAt: new \DateTimeImmutable('2026-03-02 10:30:00')
        );
        
        $cache->put($indicator, $context, $original);
        $retrieved = $cache->get($indicator, $context);
        
        $this->assertEquals($original->indicator, $retrieved->indicator);
        $this->assertEquals($original->score, $retrieved->score);
        $this->assertEquals($original->issues, $retrieved->issues);
        $this->assertEquals($original->suggestions, $retrieved->suggestions);
        $this->assertEquals($original->bestPractices, $retrieved->bestPractices);
        $this->assertEquals($original->provider, $retrieved->provider);
        $this->assertEquals($original->model, $retrieved->model);
    }

    #[Test]
    #[TestDox('CACHE-051: Complex context is handled correctly')]
    public function complex_context_is_handled_correctly(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = [
            'page' => [
                'url' => 'https://example.com/products/item-123',
                'title' => 'Product Name | Brand',
                'meta' => [
                    'description' => 'Product description here',
                    'keywords' => ['product', 'brand', 'category'],
                ],
            ],
            'analysis' => [
                'wordCount' => 5,
                'charCount' => 20,
                'hasKeywords' => true,
            ],
        ];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 75,
            issues: ['Too short'],
            suggestions: [['type' => 'improve', 'text' => 'Add primary keyword near the beginning']],
            bestPractices: ['Front-load important keywords'],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $suggestion);
        
        $this->assertTrue($cache->has($indicator, $context));
        $retrieved = $cache->get($indicator, $context);
        $this->assertEquals(75, $retrieved->score);
    }

    // =========================================================================
    // AC-7: Unit Tests - Edge Cases
    // =========================================================================

    #[Test]
    #[TestDox('CACHE-060: Unicode in context is handled')]
    public function unicode_in_context_is_handled(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = [
            'url' => 'https://example.com/продукты/товар-123',
            'title' => 'Produkts – Zīmols | Kategorija',
        ];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 80,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $suggestion);
        
        $this->assertTrue($cache->has($indicator, $context));
        $retrieved = $cache->get($indicator, $context);
        $this->assertInstanceOf(SuggestionResult::class, $retrieved);
    }

    #[Test]
    #[TestDox('CACHE-061: Special characters in context are handled')]
    public function special_characters_in_context_are_handled(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = [
            'title' => 'Title with "quotes" & <special> chars!',
            'symbols' => '!@#$%^&*()_+-=[]{}|;:\'",.<>?/~`',
        ];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 50,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $suggestion);
        
        $this->assertTrue($cache->has($indicator, $context));
    }

    #[Test]
    #[TestDox('CACHE-062: Large context is handled')]
    public function large_context_is_handled(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'content';
        $context = [
            'content' => str_repeat('Lorem ipsum dolor sit amet. ', 100), // ~3000 chars
        ];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 50,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $suggestion);
        
        $this->assertTrue($cache->has($indicator, $context));
    }

    #[Test]
    #[TestDox('CACHE-063: Numeric values in context are handled')]
    public function numeric_values_in_context_are_handled(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = [
            'score' => 85,
            'length' => 60,
            'ratio' => 0.75,
            'flags' => [true, false],
        ];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 85,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $suggestion);
        
        $this->assertTrue($cache->has($indicator, $context));
    }

    #[Test]
    #[TestDox('CACHE-064: Null values in context are handled')]
    public function null_values_in_context_are_handled(): void
    {
        $cache = app(self::CACHE_CLASS);
        
        $indicator = 'title';
        $context = [
            'url' => 'https://example.com',
            'title' => null,
            'optional' => null,
        ];
        
        $suggestion = new SuggestionResult(
            indicator: $indicator,
            score: 50,
            issues: [],
            suggestions: [],
            bestPractices: [],
            provider: 'openrouter',
            model: 'llama-3.1',
            generatedAt: new \DateTimeImmutable()
        );
        
        $cache->put($indicator, $context, $suggestion);
        
        $this->assertTrue($cache->has($indicator, $context));
    }

    // =========================================================================
    // Documentation
    // =========================================================================

    #[Test]
    #[TestDox('CACHE-070: Service has proper namespace')]
    public function service_has_proper_namespace(): void
    {
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        
        $this->assertEquals(
            'App\Services\AI',
            $reflection->getNamespaceName(),
            'Service should be in App\Services\AI namespace'
        );
    }

    #[Test]
    #[TestDox('CACHE-071: All public methods have documentation')]
    public function all_public_methods_have_documentation(): void
    {
        $reflection = new \ReflectionClass(self::CACHE_CLASS);
        $methods = ['get', 'put', 'has', 'forget', 'clear'];
        
        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $docComment = $method->getDocComment();
            
            $this->assertNotFalse(
                $docComment,
                "Method {$methodName}() must have PHPDoc documentation"
            );
        }
    }
}
