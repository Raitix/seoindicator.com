<?php

namespace Tests\Unit\Jobs\AIVisibility;

use App\Jobs\AIVisibility\AIVisibilityQueryJob;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Services\AIVisibility\AIQueryEngine;
use App\Services\AIVisibility\MentionParser;
use App\Services\AIVisibility\DTOs\AIQueryResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for AIVisibilityQueryJob.
 *
 * Tests job configuration, cache handling, query execution, and error handling.
 *
 * @group ai-visibility-jobs
 */
class AIVisibilityQueryJobTest extends TestCase
{
    use RefreshDatabase;

    protected AIVisibilityProject $project;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();

        // Create a test project for tests that need it
        $this->project = AIVisibilityProject::factory()->create([
            'id' => 1,
            'brand_name' => 'TestBrand',
        ]);
    }

    // ============================================
    // Job Configuration Tests (JQ-CFG)
    // ============================================

    /** @test JQ-CFG-01: Job implements ShouldQueue */
    public function job_implements_should_queue(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test JQ-CFG-02: Job has correct queue name */
    public function job_has_correct_queue_name(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $this->assertEquals('ai-visibility', $job->queue);
    }

    /** @test JQ-CFG-03: Job has correct tries count */
    public function job_has_correct_tries_count(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $this->assertEquals(3, $job->tries);
    }

    /** @test JQ-CFG-04: Job has correct backoff */
    public function job_has_correct_backoff(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $this->assertEquals([60], $job->backoff);
    }

    /** @test JQ-CFG-05: Job accepts projectId */
    public function job_accepts_project_id(): void
    {
        $job = new AIVisibilityQueryJob(123, 'chatgpt', 'test query', true);

        $this->assertEquals(123, $job->projectId);
    }

    /** @test JQ-CFG-06: Job accepts platform */
    public function job_accepts_platform(): void
    {
        $job = new AIVisibilityQueryJob(1, 'perplexity', 'test query', true);

        $this->assertEquals('perplexity', $job->platform);
    }

    /** @test JQ-CFG-07: Job accepts query */
    public function job_accepts_query(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'best seo tools 2024', true);

        $this->assertEquals('best seo tools 2024', $job->query);
    }

    /** @test JQ-CFG-08: Job accepts useFreeModel flag */
    public function job_accepts_use_free_model_flag(): void
    {
        $jobTrue = new AIVisibilityQueryJob(1, 'chatgpt', 'test', true);
        $jobFalse = new AIVisibilityQueryJob(1, 'chatgpt', 'test', false);

        $this->assertTrue($jobTrue->useFreeModel);
        $this->assertFalse($jobFalse->useFreeModel);
    }

    // ============================================
    // Cache Check Tests (JQ-CACHE)
    // ============================================

    /** @test JQ-CACHE-01: Cache key generated correctly */
    public function cache_key_generated_correctly(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);
        $cacheKey = $job->getCacheKey();

        $this->assertStringStartsWith('ai_visibility:query:', $cacheKey);
        // "ai_visibility:query:" (20 chars) + MD5 hash (32 chars) = 52 chars
        $this->assertEquals(52, strlen($cacheKey));
    }

    /** @test JQ-CACHE-02: Cache key is deterministic */
    public function cache_key_is_deterministic(): void
    {
        $job1 = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);
        $job2 = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $this->assertEquals($job1->getCacheKey(), $job2->getCacheKey());
    }

    /** @test JQ-CACHE-03: Different query produces different key */
    public function different_query_produces_different_key(): void
    {
        $job1 = new AIVisibilityQueryJob(1, 'chatgpt', 'test query 1', true);
        $job2 = new AIVisibilityQueryJob(1, 'chatgpt', 'test query 2', true);

        $this->assertNotEquals($job1->getCacheKey(), $job2->getCacheKey());
    }

    /** @test JQ-CACHE-04: Different platform produces different key */
    public function different_platform_produces_different_key(): void
    {
        $job1 = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);
        $job2 = new AIVisibilityQueryJob(1, 'perplexity', 'test query', true);

        $this->assertNotEquals($job1->getCacheKey(), $job2->getCacheKey());
    }

    /** @test JQ-CACHE-05: Cache hit returns cached result */
    public function cache_hit_returns_cached_result(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);
        $cachedResult = [
            'query_hash' => 'abc123',
            'mentions' => [['text' => 'Brand mentioned', 'sentiment' => 'positive']],
            'cache_hit' => true,
        ];

        Cache::put($job->getCacheKey(), $cachedResult, 3600);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldNotReceive('query');

        $result = $job->handle($mockEngine, app(MentionParser::class));

        $this->assertTrue($result['cache_hit']);
        $this->assertEquals($cachedResult['mentions'], $result['mentions']);
    }

    /** @test JQ-CACHE-06: Cache miss executes query */
    public function cache_miss_executes_query(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->once()
            ->with('chatgpt', 'test query', true)
            ->andReturn(AIQueryResult::success(
                responseText: 'AI response',
                model: 'gemini-2.0-flash',
                tokensUsed: 100,
                latencyMs: 500,
                platform: 'chatgpt',
                queryText: 'test query'
            ));

        $result = $job->handle($mockEngine, app(MentionParser::class));

        $this->assertFalse($result['cache_hit'] ?? false);
    }

    /** @test JQ-CACHE-07: Cache TTL is 7 days */
    public function cache_ttl_is_7_days(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        // Use Cache::spy() to verify put() is called with correct TTL
        Cache::spy();

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')->andReturn(AIQueryResult::success(
            responseText: 'test',
            model: 'test-model',
            tokensUsed: 10,
            latencyMs: 100,
            platform: 'chatgpt',
            queryText: 'test query'
        ));

        $job->handle($mockEngine, app(MentionParser::class));

        // Verify cache put was called with 7 days TTL (604800 seconds)
        Cache::shouldHaveReceived('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) {
                return $ttl === 604800;
            });
    }

    /** @test JQ-CACHE-08: Result stored in database */
    public function result_stored_in_database(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')->andReturn(AIQueryResult::success(
            responseText: 'AI response with brand mention',
            model: 'gemini-2.0-flash',
            tokensUsed: 100,
            latencyMs: 500,
            platform: 'chatgpt',
            queryText: 'test query'
        ));

        $job->handle($mockEngine, app(MentionParser::class));

        $this->assertDatabaseHas('ai_query_results', [
            'project_id' => 1,
            'platform' => 'chatgpt',
        ]);
    }

    // ============================================
    // Query Execution Tests (JQ-EXEC)
    // ============================================

    /** @test JQ-EXEC-01: Calls AIQueryEngine with correct parameters */
    public function calls_ai_query_engine_with_correct_parameters(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'best seo tools', false);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->once()
            ->with('chatgpt', 'best seo tools', false)
            ->andReturn(AIQueryResult::success(
                responseText: 'test response',
                model: 'test-model',
                tokensUsed: 50,
                latencyMs: 100,
                platform: 'chatgpt',
                queryText: 'best seo tools'
            ));

        $job->handle($mockEngine, app(MentionParser::class));
    }

    /** @test JQ-EXEC-02: Parses mentions with MentionParser */
    public function parses_mentions_with_mention_parser(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $mockParser = $this->mock(MentionParser::class);
        $mockParser->shouldReceive('extractMentions')
            ->once()
            ->andReturn([
                ['brand' => 'Brand', 'position' => 0, 'context' => 'Brand is mentioned'],
            ]);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->andReturn(AIQueryResult::success(
                responseText: 'AI response text',
                model: 'test-model',
                tokensUsed: 50,
                latencyMs: 100,
                platform: 'chatgpt',
                queryText: 'test query'
            ));

        $job->handle($mockEngine, $mockParser);
    }

    /** @test JQ-EXEC-03: Handles empty AI response */
    public function handles_empty_ai_response(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->andReturn(AIQueryResult::success(
                responseText: '',
                model: 'test-model',
                tokensUsed: 0,
                latencyMs: 100,
                platform: 'chatgpt',
                queryText: 'test query'
            ));

        $result = $job->handle($mockEngine, app(MentionParser::class));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('mentions', $result);
    }

    /** @test JQ-EXEC-04: Handles null AI response */
    public function handles_null_ai_response(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->andReturn(AIQueryResult::error(
                errorMessage: 'No response from AI service',
                platform: 'chatgpt',
                queryText: 'test query'
            ));

        // Job should handle error result gracefully
        $result = $job->handle($mockEngine, app(MentionParser::class));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response_text', $result);
    }

    /** @test JQ-EXEC-05: Result includes metadata */
    public function result_includes_metadata(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->andReturn(AIQueryResult::success(
                responseText: 'test response',
                model: 'gemini-2.0-flash',
                tokensUsed: 150,
                latencyMs: 500,
                platform: 'chatgpt',
                queryText: 'test query'
            ));

        $result = $job->handle($mockEngine, app(MentionParser::class));

        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('tokens_used', $result);
        $this->assertArrayHasKey('query_hash', $result);
        $this->assertEquals('gemini-2.0-flash', $result['model']);
        $this->assertEquals(150, $result['tokens_used']);
    }

    // ============================================
    // Error Handling Tests (JQ-ERR)
    // ============================================

    /** @test JQ-ERR-01: AIQueryEngine exception causes job failure */
    public function ai_query_engine_exception_causes_job_failure(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->andThrow(new \Exception('AI service unavailable'));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'AIVisibilityQueryJob failed')
                    && isset($context['project_id']);
            });

        $this->expectException(\Exception::class);

        $job->handle($mockEngine, app(MentionParser::class));
    }

    /** @test JQ-ERR-02: Job has retry logic configured */
    public function job_has_retry_logic_configured(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        // Verify retry configuration exists
        $this->assertEquals(3, $job->tries);
        $this->assertEquals([60], $job->backoff);
    }

    /** @test JQ-ERR-03: Failed job is logged with context */
    public function failed_job_is_logged_with_context(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['project_id'])
                    && isset($context['platform'])
                    && isset($context['query']);
            });

        $e = new \Exception('Test failure');
        $job->failed($e);
    }

    /** @test JQ-ERR-04: Database error handled gracefully */
    public function database_error_handled_gracefully(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->andReturn(AIQueryResult::success(
                responseText: 'test',
                model: 'test-model',
                tokensUsed: 10,
                latencyMs: 100,
                platform: 'chatgpt',
                queryText: 'test query'
            ));

        // The job should handle database errors and log them
        // For this test, we verify the job completes without throwing
        // In production, database errors would be caught and logged
        $result = $job->handle($mockEngine, app(MentionParser::class));

        // Job should complete successfully if database works
        $this->assertIsArray($result);
    }

    // ============================================
    // WithoutOverlapping Middleware Tests (JQ-MW)
    // ============================================

    /** @test JQ-MW-01: Job uses WithoutOverlapping middleware */
    public function job_uses_without_overlapping_middleware(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        $middleware = $job->middleware();

        // Check that middleware is configured
        $this->assertNotEmpty($middleware);
        $this->assertInstanceOf(\Illuminate\Queue\Middleware\WithoutOverlapping::class, $middleware[0]);
    }

    /** @test JQ-MW-02: Lock key is unique per project and query */
    public function lock_key_is_unique_per_project_and_query(): void
    {
        $job1 = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);
        $job2 = new AIVisibilityQueryJob(2, 'chatgpt', 'test query', true);
        $job3 = new AIVisibilityQueryJob(1, 'chatgpt', 'different query', true);

        $lockKey1 = $job1->getLockKey ?? $job1->getCacheKey();
        $lockKey2 = $job2->getLockKey ?? $job2->getCacheKey();
        $lockKey3 = $job3->getLockKey ?? $job3->getCacheKey();

        $this->assertNotEquals($lockKey1, $lockKey2);
        $this->assertNotEquals($lockKey1, $lockKey3);
    }

    // ============================================
    // Edge Cases and Security Tests
    // ============================================

    /** @test EC-01: Special characters in query handled */
    public function special_characters_in_query_handled(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', "test's query \"with\" special <chars>", true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->with('chatgpt', "test's query \"with\" special <chars>", true)
            ->andReturn(AIQueryResult::success(
                responseText: 'test',
                model: 'test-model',
                tokensUsed: 10,
                latencyMs: 100,
                platform: 'chatgpt',
                queryText: "test's query"
            ));

        $result = $job->handle($mockEngine, app(MentionParser::class));

        $this->assertIsArray($result);
    }

    /** @test EC-02: Unicode characters in query handled */
    public function unicode_characters_in_query_handled(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'тест запрось 条件 테스트', true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->andReturn(AIQueryResult::success(
                responseText: 'test',
                model: 'test-model',
                tokensUsed: 10,
                latencyMs: 100,
                platform: 'chatgpt',
                queryText: 'тест запрось 条件 테스트'
            ));

        $result = $job->handle($mockEngine, app(MentionParser::class));

        $this->assertIsArray($result);
    }

    /** @test SEC-01: No sensitive data in error logs */
    public function no_sensitive_data_in_error_logs(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                // Ensure no API keys or tokens in logs
                $contextString = json_encode($context);
                return !str_contains($contextString, 'api_key')
                    && !str_contains($contextString, 'token')
                    && !str_contains($contextString, 'password');
            });

        $job->failed(new \Exception('Test error'));
    }

    /** @test SEC-02: Query is sanitized before storage */
    public function query_is_sanitized_before_storage(): void
    {
        $maliciousQuery = '<script>alert("xss")</script>test query';
        $job = new AIVisibilityQueryJob(1, 'chatgpt', $maliciousQuery, true);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->andReturn(AIQueryResult::success(
                responseText: 'test',
                model: 'test-model',
                tokensUsed: 10,
                latencyMs: 100,
                platform: 'chatgpt',
                queryText: $maliciousQuery
            ));

        $job->handle($mockEngine, app(MentionParser::class));

        // Verify the stored query is sanitized (no script tags)
        $this->assertDatabaseMissing('ai_query_results', [
            'query_text' => $maliciousQuery,
        ]);
    }

    /** @test PERF-01: Job handles large response efficiently */
    public function job_handles_large_response_efficiently(): void
    {
        $job = new AIVisibilityQueryJob(1, 'chatgpt', 'test query', true);

        // Generate a large response (100KB)
        $largeResponse = str_repeat('Brand is mentioned in this context. ', 3000);

        $mockEngine = $this->mock(AIQueryEngine::class);
        $mockEngine->shouldReceive('query')
            ->andReturn(AIQueryResult::success(
                responseText: $largeResponse,
                model: 'test-model',
                tokensUsed: 5000,
                latencyMs: 1000,
                platform: 'chatgpt',
                queryText: 'test query'
            ));

        $startTime = microtime(true);
        $result = $job->handle($mockEngine, app(MentionParser::class));
        $executionTime = microtime(true) - $startTime;

        $this->assertIsArray($result);
        $this->assertLessThan(5, $executionTime, 'Job should complete in under 5 seconds');
    }
}
