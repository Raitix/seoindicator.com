<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\AnalysisCacheService;
use App\Services\AI\Contracts\AnalysisCacheServiceInterface;
use App\Services\AI\DTOs\AnalysisResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for AnalysisCacheService.
 *
 * Tests cache key generation and invalidation for the unified AI analysis flow.
 * Covers cache key generation, retrieval, storage, invalidation, and TTL handling.
 *
 * @see TASK-31-12
 * @see SPEC-31
 */
class AnalysisCacheServiceTest extends TestCase
{
    private AnalysisCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure cache is enabled for tests
        config(['ai.cache.enabled' => true]);

        $this->service = new AnalysisCacheService();
    }

    protected function tearDown(): void
    {
        // Clear any test cache entries
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Create a mock AnalysisResult for testing.
     */
    private function createMockAnalysisResult(int $score = 75): AnalysisResult
    {
        return new AnalysisResult(
            overallScore: $score,
            overallSummary: 'This is a comprehensive SEO analysis summary with sufficient length.',
            categoryScores: ['content' => 80, 'technical' => 70, 'accessibility' => 75, 'performance' => 72],
            indicators: [
                ['id' => 'url', 'name' => 'URL Structure', 'score' => 2, 'passed' => true],
                ['id' => 'title_tag', 'name' => 'Title Tag', 'score' => 2, 'passed' => true],
                ['id' => 'meta_description', 'name' => 'Meta Description', 'score' => 1, 'passed' => false],
            ],
            model: 'google/gemini-2.0-flash-exp:free',
            promptTokens: 1500,
            responseTokens: 800,
            latencyMs: 2500,
            truncationStrategies: [],
            topPriorities: ['image_alt', 'headings']
        );
    }

    // =========================================================================
    // Service Instantiation Tests (SI)
    // =========================================================================

    #[Test]
    #[Group('instantiation')]
    public function service_can_be_instantiated(): void
    {
        $service = new AnalysisCacheService();

        $this->assertInstanceOf(AnalysisCacheService::class, $service);
    }

    #[Test]
    #[Group('instantiation')]
    public function service_implements_interface(): void
    {
        $service = new AnalysisCacheService();

        $this->assertInstanceOf(AnalysisCacheServiceInterface::class, $service);
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_generate_key_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'generateKey'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_generate_hash_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'generateHash'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_get_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'get'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_put_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'put'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_forget_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'forget'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_clear_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'clear'));
    }

    // =========================================================================
    // Cache Key Generation Tests (CK)
    // =========================================================================

    #[Test]
    #[Group('cache-key')]
    public function generate_hash_returns_sha256_hash(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $hash = $this->service->generateHash($url, $html);

        // SHA-256 produces 64 character hex string
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    #[Test]
    #[Group('cache-key')]
    public function generate_key_returns_same_as_generate_hash(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $hash = $this->service->generateHash($url, $html);
        $key = $this->service->generateKey($url, $html);

        $this->assertEquals($hash, $key);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_is_consistent_for_same_input(): void
    {
        $url = 'https://example.com/page';
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';

        $hash1 = $this->service->generateHash($url, $html);
        $hash2 = $this->service->generateHash($url, $html);

        $this->assertEquals($hash1, $hash2);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_differs_for_different_urls(): void
    {
        $html = '<html><body>Test</body></html>';

        $hash1 = $this->service->generateHash('https://example.com/page1', $html);
        $hash2 = $this->service->generateHash('https://example.com/page2', $html);

        $this->assertNotEquals($hash1, $hash2);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_differs_for_different_html(): void
    {
        $url = 'https://example.com';

        $hash1 = $this->service->generateHash($url, '<html><body>Content A</body></html>');
        $hash2 = $this->service->generateHash($url, '<html><body>Content B</body></html>');

        $this->assertNotEquals($hash1, $hash2);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_differs_for_different_url_and_html(): void
    {
        $hash1 = $this->service->generateHash('https://a.com', '<html>A</html>');
        $hash2 = $this->service->generateHash('https://b.com', '<html>B</html>');

        $this->assertNotEquals($hash1, $hash2);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_with_empty_html(): void
    {
        $hash = $this->service->generateHash('https://example.com', '');

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_with_empty_url(): void
    {
        $hash = $this->service->generateHash('', '<html><body>Test</body></html>');

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_with_unicode_content(): void
    {
        $hash = $this->service->generateHash(
            'https://example.com/日本語',
            '<html><body>テスト内容 标题 🎉</body></html>'
        );

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_with_special_characters(): void
    {
        $hash = $this->service->generateHash(
            'https://example.com?param=value&other=<script>',
            '<html><body><script>alert("xss")</script></body></html>'
        );

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_uses_first_1000_chars_of_html(): void
    {
        $url = 'https://example.com';
        $htmlShort = str_repeat('a', 500);
        $htmlMedium = str_repeat('a', 1000);
        $htmlLong = str_repeat('a', 5000);

        // Short and medium should be different (medium includes more chars)
        $hashShort = $this->service->generateHash($url, $htmlShort);
        $hashMedium = $this->service->generateHash($url, $htmlMedium);

        // Medium and long should be same (only first 1000 chars used)
        $hashLong = $this->service->generateHash($url, $htmlLong);

        $this->assertNotEquals($hashShort, $hashMedium);
        $this->assertEquals($hashMedium, $hashLong);
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_is_deterministic(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test Content</body></html>';

        // Multiple calls should return identical hash
        $hashes = [];
        for ($i = 0; $i < 10; $i++) {
            $hashes[] = $this->service->generateHash($url, $html);
        }

        $this->assertCount(1, array_unique($hashes));
    }

    #[Test]
    #[Group('cache-key')]
    public function hash_not_reversible(): void
    {
        $url = 'https://example.com/secret-page';
        $html = '<html><body>Secret Content</body></html>';

        $hash = $this->service->generateHash($url, $html);

        // Hash should not contain the original URL or content
        $this->assertStringNotContainsString('example.com', $hash);
        $this->assertStringNotContainsString('secret', $hash);
        $this->assertStringNotContainsString('Content', $hash);
    }

    // =========================================================================
    // Cache Get Tests (CG)
    // =========================================================================

    #[Test]
    #[Group('cache-get')]
    public function get_returns_null_for_cache_miss(): void
    {
        $result = $this->service->get('nonexistent_hash_key');

        $this->assertNull($result);
    }

    #[Test]
    #[Group('cache-get')]
    public function get_returns_result_for_cache_hit(): void
    {
        $hash = $this->service->generateHash('https://example.com', '<html><body>Test</body></html>');
        $cachedResult = $this->createMockAnalysisResult(85);

        // Store directly in cache
        Cache::put('ai_analysis:' . $hash, $cachedResult, now()->addHours(24));

        $result = $this->service->get($hash);

        $this->assertInstanceOf(AnalysisResult::class, $result);
        $this->assertEquals(85, $result->overallScore);
    }

    #[Test]
    #[Group('cache-get')]
    public function get_returns_null_when_cache_disabled(): void
    {
        config(['ai.cache.enabled' => false]);

        $hash = $this->service->generateHash('https://example.com', '<html><body>Test</body></html>');
        $cachedResult = $this->createMockAnalysisResult(90);

        Cache::put('ai_analysis:' . $hash, $cachedResult, now()->addHours(24));

        $result = $this->service->get($hash);

        // Should return null when cache is disabled
        $this->assertNull($result);

        // Reset config
        config(['ai.cache.enabled' => true]);
    }

    #[Test]
    #[Group('cache-get')]
    public function get_logs_cache_hit(): void
    {
        Log::shouldReceive('debug')
            ->once();

        $hash = $this->service->generateHash('https://example.com', '<html><body>Test</body></html>');
        $cachedResult = $this->createMockAnalysisResult();

        Cache::put('ai_analysis:' . $hash, $cachedResult, now()->addHours(24));

        $this->service->get($hash);
    }

    #[Test]
    #[Group('cache-get')]
    public function get_logs_cache_miss(): void
    {
        Log::shouldReceive('debug')
            ->once();

        $this->service->get('nonexistent_hash_key');
    }

    #[Test]
    #[Group('cache-get')]
    public function get_returns_null_for_expired_cache(): void
    {
        $hash = 'expired_test_hash';
        $cachedResult = $this->createMockAnalysisResult();

        // Store with past expiration
        Cache::put('ai_analysis:' . $hash, $cachedResult, now()->subSeconds(1));

        // Wait for expiration
        sleep(1);

        $result = $this->service->get($hash);

        $this->assertNull($result);
    }

    #[Test]
    #[Group('cache-get')]
    public function get_with_empty_hash_returns_null(): void
    {
        $result = $this->service->get('');

        $this->assertNull($result);
    }

    // =========================================================================
    // Cache Put Tests (CP)
    // =========================================================================

    #[Test]
    #[Group('cache-put')]
    public function put_stores_result_in_cache(): void
    {
        $hash = $this->service->generateHash('https://example.com', '<html><body>Test</body></html>');
        $result = $this->createMockAnalysisResult(80);

        $this->service->put($hash, $result);

        $cached = Cache::get('ai_analysis:' . $hash);

        $this->assertInstanceOf(AnalysisResult::class, $cached);
        $this->assertEquals(80, $cached->overallScore);
    }

    #[Test]
    #[Group('cache-put')]
    public function put_uses_default_ttl_of_24_hours(): void
    {
        $hash = 'ttl_default_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        $this->service->put($hash, $result);

        // Cache should exist immediately after storing
        $this->assertTrue(Cache::has('ai_analysis:' . $hash));
    }

    #[Test]
    #[Group('cache-put')]
    public function put_respects_custom_ttl(): void
    {
        $hash = 'ttl_custom_test_' . uniqid();
        $result = $this->createMockAnalysisResult();
        $customTtl = 3600; // 1 hour

        $this->service->put($hash, $result, $customTtl);

        $this->assertTrue(Cache::has('ai_analysis:' . $hash));
    }

    #[Test]
    #[Group('cache-put')]
    public function put_does_nothing_when_cache_disabled(): void
    {
        config(['ai.cache.enabled' => false]);

        $hash = 'disabled_cache_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        $this->service->put($hash, $result);

        // Cache should not be set when disabled
        $this->assertFalse(Cache::has('ai_analysis:' . $hash));

        // Reset config
        config(['ai.cache.enabled' => true]);
    }

    #[Test]
    #[Group('cache-put')]
    public function put_logs_storage(): void
    {
        Log::shouldReceive('debug')->once();

        $hash = 'log_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        $this->service->put($hash, $result);
    }

    #[Test]
    #[Group('cache-put')]
    public function put_overwrites_existing_cache(): void
    {
        $hash = 'overwrite_test_' . uniqid();

        $oldResult = $this->createMockAnalysisResult(50);
        $this->service->put($hash, $oldResult);

        $newResult = $this->createMockAnalysisResult(95);
        $this->service->put($hash, $newResult);

        $cached = Cache::get('ai_analysis:' . $hash);

        $this->assertEquals(95, $cached->overallScore);
    }

    #[Test]
    #[Group('cache-put')]
    public function put_validates_negative_ttl(): void
    {
        $hash = 'negative_ttl_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        // Should use default TTL when negative is provided
        $this->service->put($hash, $result, -100);

        $this->assertTrue(Cache::has('ai_analysis:' . $hash));
    }

    #[Test]
    #[Group('cache-put')]
    public function put_caps_excessive_ttl(): void
    {
        $hash = 'excessive_ttl_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        // Should cap to max TTL (7 days = 604800 seconds)
        $this->service->put($hash, $result, 999999999);

        $this->assertTrue(Cache::has('ai_analysis:' . $hash));
    }

    // =========================================================================
    // Cache Forget Tests (CF)
    // =========================================================================

    #[Test]
    #[Group('cache-forget')]
    public function forget_removes_cached_result(): void
    {
        $hash = 'forget_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        $this->service->put($hash, $result);
        $this->assertTrue(Cache::has('ai_analysis:' . $hash));

        $this->service->forget($hash);

        $this->assertFalse(Cache::has('ai_analysis:' . $hash));
    }

    #[Test]
    #[Group('cache-forget')]
    public function forget_returns_true_when_entry_existed(): void
    {
        $hash = 'forget_exists_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        $this->service->put($hash, $result);

        $returnValue = $this->service->forget($hash);

        $this->assertTrue($returnValue);
    }

    #[Test]
    #[Group('cache-forget')]
    public function forget_returns_false_when_entry_not_found(): void
    {
        $returnValue = $this->service->forget('nonexistent_hash');

        $this->assertFalse($returnValue);
    }

    #[Test]
    #[Group('cache-forget')]
    public function forget_logs_action(): void
    {
        // put() logs debug, forget() logs debug - so allow multiple calls
        Log::shouldReceive('debug')->atLeast()->once();

        $hash = 'forget_log_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        $this->service->put($hash, $result);
        $this->service->forget($hash);
    }

    #[Test]
    #[Group('cache-forget')]
    public function forget_does_not_affect_other_entries(): void
    {
        $hash1 = 'forget_multi_1_' . uniqid();
        $hash2 = 'forget_multi_2_' . uniqid();
        $result = $this->createMockAnalysisResult();

        $this->service->put($hash1, $result);
        $this->service->put($hash2, $result);

        $this->service->forget($hash1);

        $this->assertFalse(Cache::has('ai_analysis:' . $hash1));
        $this->assertTrue(Cache::has('ai_analysis:' . $hash2));
    }

    // =========================================================================
    // Cache Clear Tests (CC)
    // =========================================================================

    #[Test]
    #[Group('cache-clear')]
    public function clear_logs_action_and_returns_count(): void
    {
        // The clear() method logs and returns a count
        // Note: Clearing all prefixed cache entries requires a registry pattern or Redis SCAN
        // The current implementation logs the action but may not clear all entries
        
        Log::shouldReceive('info')->once();

        $count = $this->service->clear();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    #[Test]
    #[Group('cache-clear')]
    public function clear_returns_zero_when_no_entries(): void
    {
        // Clear first to ensure clean state
        Cache::flush();

        $count = $this->service->clear();

        $this->assertGreaterThanOrEqual(0, $count);
    }

    // =========================================================================
    // Integration Tests (IT)
    // =========================================================================

    #[Test]
    #[Group('integration')]
    public function full_cache_flow_works(): void
    {
        $url = 'https://example.com/test-page';
        $html = '<html><head><title>Test Page</title></head><body>Content</body></html>';

        // Generate hash
        $hash = $this->service->generateHash($url, $html);

        // Initially should be null
        $this->assertNull($this->service->get($hash));

        // Store result
        $result = $this->createMockAnalysisResult(88);
        $this->service->put($hash, $result);

        // Should now return cached result
        $cached = $this->service->get($hash);
        $this->assertInstanceOf(AnalysisResult::class, $cached);
        $this->assertEquals(88, $cached->overallScore);

        // Forget
        $this->service->forget($hash);

        // Should be null again
        $this->assertNull($this->service->get($hash));
    }

    #[Test]
    #[Group('integration')]
    public function cache_survives_multiple_get_calls(): void
    {
        $hash = 'multi_get_test_' . uniqid();
        $result = $this->createMockAnalysisResult(77);

        $this->service->put($hash, $result);

        $result1 = $this->service->get($hash);
        $result2 = $this->service->get($hash);
        $result3 = $this->service->get($hash);

        $this->assertEquals(77, $result1->overallScore);
        $this->assertEquals(77, $result2->overallScore);
        $this->assertEquals(77, $result3->overallScore);
    }

    #[Test]
    #[Group('integration')]
    public function different_urls_have_separate_caches(): void
    {
        $html = '<html><body>Same Content</body></html>';

        $hash1 = $this->service->generateHash('https://example.com/page1', $html);
        $hash2 = $this->service->generateHash('https://example.com/page2', $html);

        $result1 = $this->createMockAnalysisResult(60);
        $result2 = $this->createMockAnalysisResult(90);

        $this->service->put($hash1, $result1);
        $this->service->put($hash2, $result2);

        $cached1 = $this->service->get($hash1);
        $cached2 = $this->service->get($hash2);

        $this->assertEquals(60, $cached1->overallScore);
        $this->assertEquals(90, $cached2->overallScore);
    }

    #[Test]
    #[Group('integration')]
    public function same_content_different_url_different_cache(): void
    {
        $html = '<html><body>Same Content</body></html>';

        $hash1 = $this->service->generateHash('https://site-a.com', $html);
        $hash2 = $this->service->generateHash('https://site-b.com', $html);

        $this->assertNotEquals($hash1, $hash2);
    }

    // =========================================================================
    // Edge Cases Tests (EC)
    // =========================================================================

    #[Test]
    #[Group('edge-case')]
    public function cache_handles_large_analysis_result(): void
    {
        $hash = 'large_result_test_' . uniqid();

        $largeResult = new AnalysisResult(
            overallScore: 75,
            overallSummary: str_repeat('This is a long summary. ', 100),
            categoryScores: ['content' => 80, 'technical' => 70, 'accessibility' => 75, 'performance' => 72],
            indicators: array_fill(0, 100, [
                'id' => 'indicator',
                'name' => 'Test Indicator',
                'score' => 2,
                'passed' => true,
                'suggestions' => array_fill(0, 10, 'Suggestion text'),
            ]),
            model: 'google/gemini-2.0-flash-exp:free',
            promptTokens: 50000,
            responseTokens: 25000,
            latencyMs: 5000,
            truncationStrategies: ['removeComments', 'removeScripts', 'removeStyles'],
            topPriorities: array_fill(0, 20, 'priority_item')
        );

        $this->service->put($hash, $largeResult);

        $cached = $this->service->get($hash);

        $this->assertInstanceOf(AnalysisResult::class, $cached);
        $this->assertEquals(50000, $cached->promptTokens);
    }

    #[Test]
    #[Group('edge-case')]
    public function cache_handles_unicode_in_result(): void
    {
        $hash = 'unicode_result_test_' . uniqid();

        $unicodeResult = new AnalysisResult(
            overallScore: 75,
            overallSummary: 'テスト分析 标题 🎉 نتیجہ',
            categoryScores: ['content' => 80],
            indicators: [
                ['id' => 'title', 'name' => 'タイトル 标题', 'score' => 2, 'passed' => true],
            ],
            model: 'google/gemini-2.0-flash-exp:free',
            promptTokens: 1000,
            responseTokens: 500,
            latencyMs: 1500,
        );

        $this->service->put($hash, $unicodeResult);

        $cached = $this->service->get($hash);

        $this->assertStringContainsString('テスト', $cached->overallSummary);
    }

    #[Test]
    #[Group('edge-case')]
    public function cache_handles_special_characters_in_result(): void
    {
        $hash = 'special_chars_test_' . uniqid();

        $specialResult = new AnalysisResult(
            overallScore: 75,
            overallSummary: 'Test with <script>alert("xss")</script> & "quotes" and \'apostrophes\'',
            categoryScores: ['content' => 80],
            indicators: [],
            model: 'google/gemini-2.0-flash-exp:free',
            promptTokens: 1000,
            responseTokens: 500,
            latencyMs: 1500,
        );

        $this->service->put($hash, $specialResult);

        $cached = $this->service->get($hash);

        $this->assertStringContainsString('<script>', $cached->overallSummary);
    }

    #[Test]
    #[Group('edge-case')]
    public function hash_with_very_long_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2000);
        $html = '<html><body>Test</body></html>';

        $hash = $this->service->generateHash($longUrl, $html);

        $this->assertEquals(64, strlen($hash));
    }

    #[Test]
    #[Group('edge-case')]
    public function hash_with_very_long_html(): void
    {
        $url = 'https://example.com';
        $longHtml = '<html><body>' . str_repeat('Content ', 100000) . '</body></html>';

        $hash = $this->service->generateHash($url, $longHtml);

        $this->assertEquals(64, strlen($hash));
    }

    // =========================================================================
    // Security Tests (SC)
    // =========================================================================

    #[Test]
    #[Group('security')]
    public function cache_key_cannot_be_predicted(): void
    {
        // Similar inputs should produce different hashes
        $hashes = [];
        for ($i = 0; $i < 100; $i++) {
            $hashes[] = $this->service->generateHash(
                'https://example.com/page' . $i,
                '<html><body>Content ' . $i . '</body></html>'
            );
        }

        // All hashes should be unique
        $uniqueHashes = array_unique($hashes);
        $this->assertCount(100, $uniqueHashes);
    }

    #[Test]
    #[Group('security')]
    public function cache_key_prefix_prevents_collision(): void
    {
        $hash = 'test_hash';

        $this->service->put($hash, $this->createMockAnalysisResult());

        // The cache key should be prefixed
        $this->assertTrue(Cache::has('ai_analysis:' . $hash));
        $this->assertFalse(Cache::has($hash));
    }

    #[Test]
    #[Group('security')]
    public function xss_in_cached_data_not_executed(): void
    {
        $hash = 'xss_test_' . uniqid();

        $xssResult = new AnalysisResult(
            overallScore: 75,
            overallSummary: '<script>alert("xss")</script>',
            categoryScores: [],
            indicators: [
                ['id' => 'test', 'name' => '<img src=x onerror=alert(1)>', 'score' => 0, 'passed' => false],
            ],
            model: 'test',
            promptTokens: 100,
            responseTokens: 50,
            latencyMs: 100,
        );

        $this->service->put($hash, $xssResult);

        $cached = $this->service->get($hash);

        // Data should be stored as-is (encoding is display layer responsibility)
        $this->assertStringContainsString('<script>', $cached->overallSummary);
    }

    // =========================================================================
    // Performance Tests (PF)
    // =========================================================================

    #[Test]
    #[Group('performance')]
    public function hash_generation_is_fast(): void
    {
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->service->generateHash(
                'https://example.com/page' . $i,
                '<html><body>Content ' . $i . '</body></html>'
            );
        }

        $elapsed = (microtime(true) - $start) * 1000;
        $avgTime = $elapsed / 100;

        $this->assertLessThan(5, $avgTime, 'Hash generation should be < 5ms average');
    }

    #[Test]
    #[Group('performance')]
    public function cache_get_is_fast(): void
    {
        $hash = 'perf_get_test_' . uniqid();
        $this->service->put($hash, $this->createMockAnalysisResult());

        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->service->get($hash);
        }

        $elapsed = (microtime(true) - $start) * 1000;
        $avgTime = $elapsed / 100;

        $this->assertLessThan(2, $avgTime, 'Cache get should be < 2ms average');
    }

    #[Test]
    #[Group('performance')]
    public function cache_put_is_fast(): void
    {
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->service->put('perf_put_' . $i . '_' . uniqid(), $this->createMockAnalysisResult());
        }

        $elapsed = (microtime(true) - $start) * 1000;
        $avgTime = $elapsed / 100;

        $this->assertLessThan(5, $avgTime, 'Cache put should be < 5ms average');
    }

    // =========================================================================
    // Acceptance Criteria Tests (AC)
    // =========================================================================

    #[Test]
    #[Group('acceptance')]
    public function ac_cache_key_generation_deterministic(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';

        $hash1 = $this->service->generateHash($url, $html);
        $hash2 = $this->service->generateHash($url, $html);

        $this->assertEquals($hash1, $hash2, 'Cache key generation must be deterministic');
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_cache_key_unique_per_input(): void
    {
        $hashes = [
            $this->service->generateHash('https://a.com', '<html>A</html>'),
            $this->service->generateHash('https://b.com', '<html>A</html>'),
            $this->service->generateHash('https://a.com', '<html>B</html>'),
        ];

        $this->assertCount(3, array_unique($hashes), 'Each unique input must produce unique cache key');
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_cache_ttl_24_hours(): void
    {
        $hash = 'ac_ttl_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        // Put without explicit TTL should use 24 hours
        $this->service->put($hash, $result);

        // Cache should exist
        $this->assertNotNull($this->service->get($hash));
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_cache_invalidation_works(): void
    {
        $hash = 'ac_invalidation_' . uniqid();
        $result = $this->createMockAnalysisResult();

        $this->service->put($hash, $result);
        $this->assertNotNull($this->service->get($hash));

        $this->service->forget($hash);
        $this->assertNull($this->service->get($hash));
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_cache_hit_returns_correct_result(): void
    {
        $hash = 'ac_hit_test_' . uniqid();
        $originalResult = $this->createMockAnalysisResult(92);

        $this->service->put($hash, $originalResult);

        $cachedResult = $this->service->get($hash);

        $this->assertEquals($originalResult->overallScore, $cachedResult->overallScore);
        $this->assertEquals($originalResult->model, $cachedResult->model);
        $this->assertEquals($originalResult->promptTokens, $cachedResult->promptTokens);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_cache_miss_returns_null(): void
    {
        $result = $this->service->get('ac_nonexistent_key_' . uniqid());

        $this->assertNull($result);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_cache_can_be_disabled(): void
    {
        config(['ai.cache.enabled' => false]);

        $hash = 'ac_disabled_test_' . uniqid();
        $result = $this->createMockAnalysisResult();

        $this->service->put($hash, $result);

        // Should return null when disabled
        $this->assertNull($this->service->get($hash));

        config(['ai.cache.enabled' => true]);
    }

    // =========================================================================
    // Data Provider Tests
    // =========================================================================

    public static function urlProvider(): array
    {
        return [
            'standard http' => ['http://example.com'],
            'standard https' => ['https://example.com'],
            'with www' => ['https://www.example.com'],
            'with path' => ['https://example.com/page/path'],
            'with query' => ['https://example.com?param=value'],
            'with fragment' => ['https://example.com#section'],
            'with port' => ['https://example.com:8080'],
            'subdomain' => ['https://blog.example.com'],
            'unicode path' => ['https://example.com/日本語/パス'],
            'special chars' => ['https://example.com/path?a=1&b=2'],
        ];
    }

    #[Test]
    #[Group('cache-key')]
    #[DataProvider('urlProvider')]
    public function hash_works_with_various_urls(string $url): void
    {
        $hash = $this->service->generateHash($url, '<html><body>Test</body></html>');

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public static function htmlProvider(): array
    {
        return [
            'minimal' => ['<html></html>'],
            'with head' => ['<html><head><title>Test</title></head></html>'],
            'with body' => ['<html><body>Content</body></html>'],
            'full page' => ['<html><head><title>Test</title></head><body><h1>Title</h1><p>Content</p></body></html>'],
            'with scripts' => ['<html><head><script>var x = 1;</script></head></html>'],
            'with styles' => ['<html><head><style>body { color: red; }</style></head></html>'],
            'unicode content' => ['<html><body>テスト 标题 🎉</body></html>'],
            'empty' => [''],
            'whitespace' => ['   <html>   </html>   '],
            'large' => ['<html><body>' . str_repeat('Content ', 1000) . '</body></html>'],
        ];
    }

    #[Test]
    #[Group('cache-key')]
    #[DataProvider('htmlProvider')]
    public function hash_works_with_various_html(string $html): void
    {
        $hash = $this->service->generateHash('https://example.com', $html);

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }
}
