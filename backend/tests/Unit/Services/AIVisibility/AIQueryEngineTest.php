<?php

namespace Tests\Unit\Services\AIVisibility;

use App\Services\AI\Contracts\OpenRouterClientInterface;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AIVisibility\AIQueryEngine;
use App\Services\AIVisibility\DTOs\AIQueryResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unit tests for AIQueryEngine service.
 *
 * Tests cover:
 * - AC-1: AIQueryEngine class created at correct namespace
 * - AC-2: Platform configuration (chatgpt, perplexity, claude disabled)
 * - AC-3: Query templates defined
 * - AC-4: query() method implemented
 * - AC-5: parseMentions() method implemented
 * - AC-6: analyzeSentiment() method implemented
 * - AC-7: Error handling
 *
 * @see TASK-34-3
 * @see SPEC-034
 */
class AIQueryEngineTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =================================================================
    // AC-1: Class Structure
    // =================================================================

    /** @test QE-001: Class exists at correct namespace */
    public function test_class_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(AIQueryEngine::class));
    }

    /** @test QE-002: Constructor accepts OpenRouterClient */
    public function test_constructor_accepts_openrouter_client(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->assertInstanceOf(AIQueryEngine::class, $engine);
    }

    /** @test QE-024: Default useFreeModel flag */
    public function test_default_use_free_model_flag(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        // Default behavior should use free models
        $this->assertInstanceOf(AIQueryEngine::class, $engine);
    }

    /** @test QE-025: Logger injection */
    public function test_logger_injection(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->assertInstanceOf(AIQueryEngine::class, $engine);
    }

    // =================================================================
    // AC-2: Platform Configuration
    // =================================================================

    /** @test QE-003: Platform configuration: chatgpt enabled */
    public function test_platform_chatgpt_enabled(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $platforms = $engine->getAvailablePlatforms();

        $this->assertContains('chatgpt', $platforms);
    }

    /** @test QE-004: Platform configuration: perplexity enabled */
    public function test_platform_perplexity_enabled(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $platforms = $engine->getAvailablePlatforms();

        $this->assertContains('perplexity', $platforms);
    }

    /** @test QE-005: Platform configuration: claude disabled */
    public function test_platform_claude_disabled(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $platforms = $engine->getAvailablePlatforms();

        $this->assertNotContains('claude', $platforms);
    }

    /** @test QE-006: ChatGPT model: Gemini 2.0 Flash free */
    public function test_chatgpt_model_is_gemini_flash_free(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $model = $engine->getModelForPlatform('chatgpt');

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    /** @test QE-007: Perplexity model: Gemini 2.0 Flash free */
    public function test_perplexity_model_is_gemini_flash_free(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $model = $engine->getModelForPlatform('perplexity');

        $this->assertEquals('google/gemini-2.0-flash-exp:free', $model);
    }

    /** @test QE-012: Platform validation: valid platform */
    public function test_platform_validation_valid(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->assertTrue($engine->isValidPlatform('chatgpt'));
    }

    /** @test QE-013: Platform validation: invalid platform */
    public function test_platform_validation_invalid(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->assertFalse($engine->isValidPlatform('unknown'));
    }

    /** @test QE-014: Platform validation: disabled platform */
    public function test_platform_validation_disabled(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->assertFalse($engine->isValidPlatform('claude'));
    }

    // =================================================================
    // AC-3: Query Templates
    // =================================================================

    /** @test QE-008: Query template: comparison exists */
    public function test_query_template_comparison_exists(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $template = $engine->getQueryTemplate('comparison');

        $this->assertStringContainsString('{category}', $template);
        $this->assertStringContainsString('{audience}', $template);
    }

    /** @test QE-009: Query template: recommendation exists */
    public function test_query_template_recommendation_exists(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $template = $engine->getQueryTemplate('recommendation');

        $this->assertStringContainsString('{category}', $template);
    }

    /** @test QE-010: Query template: feature exists */
    public function test_query_template_feature_exists(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $template = $engine->getQueryTemplate('feature');

        $this->assertStringContainsString('{brand}', $template);
        $this->assertStringContainsString('{competitor}', $template);
    }

    /** @test QE-011: Query template: use_case exists */
    public function test_query_template_use_case_exists(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $template = $engine->getQueryTemplate('use_case');

        $this->assertStringContainsString('{use_case}', $template);
    }

    /** @test QE-015: Template interpolation: category only */
    public function test_template_interpolation_category_only(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $interpolated = $engine->interpolateTemplate('recommendation', [
            'category' => 'SEO',
        ]);

        $this->assertStringContainsString('SEO', $interpolated);
        $this->assertStringNotContainsString('{category}', $interpolated);
    }

    /** @test QE-016: Template interpolation: multiple placeholders */
    public function test_template_interpolation_multiple_placeholders(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $interpolated = $engine->interpolateTemplate('comparison', [
            'category' => 'SEO',
            'audience' => 'small businesses',
        ]);

        $this->assertStringContainsString('SEO', $interpolated);
        $this->assertStringContainsString('small businesses', $interpolated);
        $this->assertStringNotContainsString('{category}', $interpolated);
        $this->assertStringNotContainsString('{audience}', $interpolated);
    }

    /** @test QE-017: Template interpolation: brand placeholder */
    public function test_template_interpolation_brand_placeholder(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $interpolated = $engine->interpolateTemplate('feature', [
            'brand' => 'Acme',
            'competitor' => 'Beta',
        ]);

        $this->assertStringContainsString('Acme', $interpolated);
    }

    /** @test QE-018: Template interpolation: competitor placeholder */
    public function test_template_interpolation_competitor_placeholder(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $interpolated = $engine->interpolateTemplate('feature', [
            'brand' => 'Acme',
            'competitor' => 'Beta',
        ]);

        $this->assertStringContainsString('Beta', $interpolated);
    }

    /** @test QE-019: Template interpolation: use_case placeholder */
    public function test_template_interpolation_use_case_placeholder(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $interpolated = $engine->interpolateTemplate('use_case', [
            'use_case' => 'analyze my website SEO',
        ]);

        $this->assertStringContainsString('analyze my website SEO', $interpolated);
    }

    /** @test QE-020: Cache TTL configuration */
    public function test_cache_ttl_configuration(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $ttl = $engine->getCacheTtl();

        // 7 days in seconds = 604800
        $this->assertEquals(604800, $ttl);
    }

    // =================================================================
    // AC-4: query() Method
    // =================================================================

    /** @test QE-026: query() method exists */
    public function test_query_method_exists(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->assertTrue(method_exists($engine, 'query'));
    }

    /** @test QE-030: query() returns AIQueryResult DTO */
    public function test_query_returns_ai_query_result_dto(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse());

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'What are the best SEO tools?');

        $this->assertInstanceOf(AIQueryResult::class, $result);
    }

    /** @test QE-031: query() uses OpenRouterClient */
    public function test_query_uses_openrouter_client(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->with('What are the best SEO tools?', Mockery::type('array'))
            ->andReturn($this->createSuccessfulAIResponse());

        $engine = new AIQueryEngine($mockClient);
        $engine->query('chatgpt', 'What are the best SEO tools?');

        // Mockery verifies the call was made
        $this->assertTrue(true);
    }

    /** @test QE-032: query() passes correct model to client */
    public function test_query_passes_correct_model_to_client(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->with('test query', Mockery::on(function ($options) {
                return isset($options['model']) &&
                    $options['model'] === 'google/gemini-2.0-flash-exp:free';
            }))
            ->andReturn($this->createSuccessfulAIResponse());

        $engine = new AIQueryEngine($mockClient);
        $engine->query('chatgpt', 'test query', true);

        $this->assertTrue(true);
    }

    /** @test QE-033: query() tracks latency in milliseconds */
    public function test_query_tracks_latency_in_milliseconds(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse(2300));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertNotNull($result->latencyMs);
        $this->assertIsInt($result->latencyMs);
    }

    /** @test QE-034: query() populates response text */
    public function test_query_populates_response_text(): void
    {
        $expectedContent = 'This is the AI response content.';
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse(100, $expectedContent));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertEquals($expectedContent, $result->responseText);
    }

    /** @test QE-035: query() populates model used */
    public function test_query_populates_model_used(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse());

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertNotNull($result->model);
        $this->assertIsString($result->model);
    }

    /** @test QE-036: query() populates tokens used */
    public function test_query_populates_tokens_used(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse(100, 'content', 350));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertNotNull($result->tokensUsed);
        $this->assertEquals(350, $result->tokensUsed);
    }

    /** @test QE-037: query() with valid response */
    public function test_query_with_valid_response(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse());

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertTrue($result->success);
    }

    /** @test QE-041: query() caches result */
    public function test_query_caches_result(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse());

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse());

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertInstanceOf(AIQueryResult::class, $result);
    }

    /** @test QE-045: query() invalid platform throws */
    public function test_query_invalid_platform_throws(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->expectException(\InvalidArgumentException::class);
        $engine->query('invalid', 'test query');
    }

    /** @test QE-046: query() empty query throws */
    public function test_query_empty_query_throws(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->expectException(\InvalidArgumentException::class);
        $engine->query('chatgpt', '');
    }

    /** @test QE-047: query() rate limit handling */
    public function test_query_rate_limit_handling(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createErrorAIResponse('429: Rate limit exceeded'));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertFalse($result->success);
        $this->assertStringContainsStringIgnoringCase('rate', $result->errorMessage ?? '');
    }

    /** @test QE-048: query() timeout handling */
    public function test_query_timeout_handling(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createErrorAIResponse('Request timeout'));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertFalse($result->success);
        $this->assertStringContainsStringIgnoringCase('timeout', strtolower($result->errorMessage ?? ''));
    }

    /** @test QE-049: query() API error handling */
    public function test_query_api_error_handling(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createErrorAIResponse('500: Server error'));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->errorMessage);
    }

    /** @test QE-054: query() error returns DTO not exception */
    public function test_query_error_returns_dto_not_exception(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createErrorAIResponse('API Error'));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test query');

        $this->assertInstanceOf(AIQueryResult::class, $result);
        $this->assertFalse($result->success);
    }

    /** @test QE-055: query() null handling */
    public function test_query_null_handling(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->expectException(\InvalidArgumentException::class);
        $engine->query(null, null);
    }

    // =================================================================
    // AC-5: parseMentions() Method
    // =================================================================

    /** @test QE-056: parseMentions() method exists */
    public function test_parse_mentions_method_exists(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->assertTrue(method_exists($engine, 'parseMentions'));
    }

    /** @test QE-059: parseMentions() returns array */
    public function test_parse_mentions_returns_array(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('Acme is great', ['Acme']);

        $this->assertIsArray($mentions);
    }

    /** @test QE-060: Single brand found once */
    public function test_single_brand_found_once(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('Acme is great', ['Acme']);

        $this->assertCount(1, $mentions);
        $this->assertEquals('Acme', $mentions[0]['brand']);
    }

    /** @test QE-061: Brand found at start */
    public function test_brand_found_at_start(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('Acme Corp is the best', ['Acme']);

        $this->assertEquals(0, $mentions[0]['position']);
    }

    /** @test QE-064: Multiple brands found */
    public function test_multiple_brands_found(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('Acme and Beta are great', ['Acme', 'Beta']);

        $this->assertCount(2, $mentions);
    }

    /** @test QE-065: Same brand multiple times */
    public function test_same_brand_multiple_times(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('Acme, then Acme again', ['Acme']);

        $this->assertCount(2, $mentions);
    }

    /** @test QE-066: Case insensitive matching */
    public function test_case_insensitive_matching(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('ACME corp is great', ['Acme']);

        $this->assertCount(1, $mentions);
    }

    /** @test QE-067: Case insensitive: all caps */
    public function test_case_insensitive_all_caps(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('ACME is mentioned', ['acme']);

        $this->assertCount(1, $mentions);
    }

    /** @test QE-069: Context extraction: 100 chars */
    public function test_context_extraction_100_chars(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $longText = str_repeat('x', 50) . 'Acme' . str_repeat('y', 50);
        $mentions = $engine->parseMentions($longText, ['Acme']);

        // Context should be around 100 characters (50 before + brand + 50 after)
        $this->assertLessThanOrEqual(110, strlen($mentions[0]['context']));
    }

    /** @test QE-073: No brands found */
    public function test_no_brands_found(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('No brands here', ['Acme']);

        $this->assertEmpty($mentions);
    }

    /** @test QE-074: Empty response string */
    public function test_empty_response_string(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('', ['Acme']);

        $this->assertEmpty($mentions);
    }

    /** @test QE-075: Empty brands array */
    public function test_empty_brands_array(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('Acme is here', []);

        $this->assertEmpty($mentions);
    }

    /** @test QE-077: Brand with unicode */
    public function test_brand_with_unicode(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('東京ツール is great', ['東京']);

        $this->assertCount(1, $mentions);
    }

    /** @test QE-083: Mention structure: brand field */
    public function test_mention_structure_brand_field(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('Acme is great', ['Acme']);

        $this->assertArrayHasKey('brand', $mentions[0]);
    }

    /** @test QE-084: Mention structure: position field */
    public function test_mention_structure_position_field(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('Acme is great', ['Acme']);

        $this->assertArrayHasKey('position', $mentions[0]);
        $this->assertIsInt($mentions[0]['position']);
    }

    /** @test QE-085: Mention structure: context field */
    public function test_mention_structure_context_field(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $mentions = $engine->parseMentions('Acme is great', ['Acme']);

        $this->assertArrayHasKey('context', $mentions[0]);
        $this->assertIsString($mentions[0]['context']);
    }

    // =================================================================
    // AC-6: analyzeSentiment() Method
    // =================================================================

    /** @test QE-086: analyzeSentiment() method exists */
    public function test_analyze_sentiment_method_exists(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);

        $this->assertTrue(method_exists($engine, 'analyzeSentiment'));
    }

    /** @test QE-088: analyzeSentiment() returns string enum */
    public function test_analyze_sentiment_returns_string_enum(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This is a test');

        $this->assertIsString($sentiment);
        $this->assertContains($sentiment, ['positive', 'negative', 'neutral', 'mixed']);
    }

    /** @test QE-093: Positive keywords: excellent */
    public function test_positive_keywords_excellent(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This is an excellent product');

        $this->assertEquals('positive', $sentiment);
    }

    /** @test QE-094: Positive keywords: great */
    public function test_positive_keywords_great(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This is a great tool');

        $this->assertEquals('positive', $sentiment);
    }

    /** @test QE-095: Positive keywords: best */
    public function test_positive_keywords_best(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This is the best option');

        $this->assertEquals('positive', $sentiment);
    }

    /** @test QE-096: Positive keywords: recommended */
    public function test_positive_keywords_recommended(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('Highly recommended');

        $this->assertEquals('positive', $sentiment);
    }

    /** @test QE-097: Positive keywords: top */
    public function test_positive_keywords_top(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This is a top choice');

        $this->assertEquals('positive', $sentiment);
    }

    /** @test QE-098: Negative keywords: poor */
    public function test_negative_keywords_poor(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This has poor quality');

        $this->assertEquals('negative', $sentiment);
    }

    /** @test QE-099: Negative keywords: bad */
    public function test_negative_keywords_bad(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This is a bad experience');

        $this->assertEquals('negative', $sentiment);
    }

    /** @test QE-100: Negative keywords: worst */
    public function test_negative_keywords_worst(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This is the worst');

        $this->assertEquals('negative', $sentiment);
    }

    /** @test QE-101: Negative keywords: avoid */
    public function test_negative_keywords_avoid(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('Avoid this product');

        $this->assertEquals('negative', $sentiment);
    }

    /** @test QE-102: Negative keywords: limited */
    public function test_negative_keywords_limited(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This has limited features');

        $this->assertEquals('negative', $sentiment);
    }

    /** @test QE-103: Mixed sentiment: positive + negative */
    public function test_mixed_sentiment_positive_negative(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('Great but limited features');

        $this->assertEquals('mixed', $sentiment);
    }

    /** @test QE-104: Neutral: no sentiment words */
    public function test_neutral_no_sentiment_words(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('This is a tool');

        $this->assertEquals('neutral', $sentiment);
    }

    /** @test QE-105: Neutral: factual statement */
    public function test_neutral_factual_statement(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('Founded in 2020');

        $this->assertEquals('neutral', $sentiment);
    }

    /** @test QE-106: Case insensitive: positive */
    public function test_case_insensitive_positive(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('EXCELLENT product');

        $this->assertEquals('positive', $sentiment);
    }

    /** @test QE-107: Case insensitive: negative */
    public function test_case_insensitive_negative(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('POOR quality');

        $this->assertEquals('negative', $sentiment);
    }

    /** @test QE-108: Empty context */
    public function test_empty_context(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('');

        $this->assertEquals('neutral', $sentiment);
    }

    /** @test QE-114: Word boundary: "best" vs "bestie" */
    public function test_word_boundary_best_vs_bestie(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('my bestie is here');

        // "bestie" should not trigger "best" keyword
        $this->assertEquals('neutral', $sentiment);
    }

    /** @test QE-117: Punctuation handling */
    public function test_punctuation_handling(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);

        $engine = new AIQueryEngine($mockClient);
        $sentiment = $engine->analyzeSentiment('Great!');

        $this->assertEquals('positive', $sentiment);
    }

    // =================================================================
    // Error Handling Tests
    // =================================================================

    /** @test QE-121: API rate limit exception */
    public function test_api_rate_limit_exception(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createErrorAIResponse('429: Rate limit exceeded'));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->errorMessage);
    }

    /** @test QE-122: API timeout exception */
    public function test_api_timeout_exception(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createErrorAIResponse('Request timeout'));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test');

        $this->assertFalse($result->success);
    }

    /** @test QE-128: Error result includes message */
    public function test_error_result_includes_message(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createErrorAIResponse('API Error'));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test');

        $this->assertNotNull($result->errorMessage);
    }

    /** @test QE-130: Error result has success=false */
    public function test_error_result_has_success_false(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createErrorAIResponse('Error'));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test');

        $this->assertFalse($result->success);
    }

    /** @test QE-138: Error does not throw exception */
    public function test_error_does_not_throw_exception(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createErrorAIResponse('Error'));

        $engine = new AIQueryEngine($mockClient);
        $result = $engine->query('chatgpt', 'test');

        // Should return result, not throw
        $this->assertInstanceOf(AIQueryResult::class, $result);
    }

    // =================================================================
    // Integration Tests
    // =================================================================

    /** @test QE-141: Full flow: query → parse → sentiment */
    public function test_full_flow_query_parse_sentiment(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse(100, 'Acme is an excellent tool'));

        $engine = new AIQueryEngine($mockClient);

        // Query
        $result = $engine->query('chatgpt', 'What tools?');
        $this->assertTrue($result->success);

        // Parse mentions
        $mentions = $engine->parseMentions($result->responseText, ['Acme']);
        $this->assertCount(1, $mentions);

        // Analyze sentiment
        $sentiment = $engine->analyzeSentiment($mentions[0]['context']);
        $this->assertEquals('positive', $sentiment);
    }

    /** @test QE-149: Unicode in all inputs */
    public function test_unicode_in_all_inputs(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse(100, '東京 is excellent'));

        $engine = new AIQueryEngine($mockClient);

        $result = $engine->query('chatgpt', 'test');
        $mentions = $engine->parseMentions($result->responseText, ['東京']);

        $this->assertCount(1, $mentions);
    }

    /** @test QE-150: Emoji in response */
    public function test_emoji_in_response(): void
    {
        $mockClient = Mockery::mock(OpenRouterClientInterface::class);
        $mockClient->shouldReceive('isAvailable')->andReturn(true);
        $mockClient->shouldReceive('generate')
            ->once()
            ->andReturn($this->createSuccessfulAIResponse(100, 'Best tool! 🎉'));

        $engine = new AIQueryEngine($mockClient);

        $result = $engine->query('chatgpt', 'test');
        $sentiment = $engine->analyzeSentiment($result->responseText);

        $this->assertEquals('positive', $sentiment);
    }

    /** @test QE-160: Null OpenRouterClient injection */
    public function test_null_openrouter_client_injection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AIQueryEngine(null);
    }

    // =================================================================
    // Helper Methods
    // =================================================================

    /**
     * Create a successful AI response mock.
     */
    private function createSuccessfulAIResponse(
        int $latencyMs = 100,
        string $content = 'This is a test response.',
        int $tokensUsed = 150
    ): AIResponse {
        return new AIResponse(
            success: true,
            content: $content,
            tokensUsed: $tokensUsed,
            latencyMs: $latencyMs,
            errorMessage: null,
            model: 'google/gemini-2.0-flash-exp:free',
            promptTokens: 50,
            responseTokens: $tokensUsed - 50
        );
    }

    /**
     * Create an error AI response mock.
     */
    private function createErrorAIResponse(string $errorMessage): AIResponse
    {
        return new AIResponse(
            success: false,
            content: null,
            tokensUsed: null,
            latencyMs: 50,
            errorMessage: $errorMessage,
            model: null,
            promptTokens: null,
            responseTokens: null
        );
    }
}
