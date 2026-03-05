<?php

namespace Tests\Unit\Services\AI;

use App\Exceptions\AIAnalysisException;
use App\Services\AI\AIAnalysisService;
use App\Services\AI\Contracts\AIAnalysisServiceInterface;
use App\Services\AI\Contracts\AnalysisCacheServiceInterface;
use App\Services\AI\Contracts\OpenRouterClientInterface;
use App\Services\AI\Contracts\UnifiedPromptServiceInterface;
use App\Services\AI\ContextWindowManager;
use App\Services\AI\DTOs\AIResponse;
use App\Services\AI\DTOs\AnalysisResult;
use App\Services\AI\DTOs\TruncationResult;
use App\Services\AI\AIAnalysisResponseParser;
use App\Services\AI\TruncationService;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for AIAnalysisService (Unified Caller).
 *
 * Tests the main orchestrator service that coordinates the unified AI
 * analysis flow: building prompts, managing context windows, calling
 * the AI, and parsing responses.
 *
 * @see TASK-31-7
 * @see SPEC-031
 */
class AIAnalysisServiceTest extends TestCase
{
    private AIAnalysisService $service;
    private ContextWindowManager|Mockery\MockInterface $contextManager;
    private TruncationService|Mockery\MockInterface $truncationService;
    private AIAnalysisResponseParser|Mockery\MockInterface $parser;
    private AnalysisCacheServiceInterface|Mockery\MockInterface $cache;
    private UnifiedPromptServiceInterface|Mockery\MockInterface $promptService;
    private OpenRouterClientInterface|Mockery\MockInterface $aiClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies using interfaces
        $this->contextManager = Mockery::mock(ContextWindowManager::class);
        $this->truncationService = Mockery::mock(TruncationService::class);
        $this->parser = Mockery::mock(AIAnalysisResponseParser::class);
        $this->cache = Mockery::mock(AnalysisCacheServiceInterface::class);
        $this->promptService = Mockery::mock(UnifiedPromptServiceInterface::class);
        $this->aiClient = Mockery::mock(OpenRouterClientInterface::class);

        // Create service with mocked dependencies
        $this->service = $this->createService();
    }

    private function createService(): AIAnalysisService
    {
        return new AIAnalysisService(
            promptService: $this->promptService,
            contextManager: $this->contextManager,
            truncationService: $this->truncationService,
            aiClient: $this->aiClient,
            parser: $this->parser,
            cache: $this->cache,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock AnalysisResult for testing.
     */
    private function createMockAnalysisResult(int $score = 75): AnalysisResult
    {
        return new AnalysisResult(
            overallScore: $score,
            overallSummary: 'This is a comprehensive SEO analysis summary.',
            categoryScores: ['content' => 80, 'technical' => 70],
            indicators: [
                ['id' => 'url', 'name' => 'URL Structure', 'score' => 2, 'passed' => true],
                ['id' => 'title_tag', 'name' => 'Title Tag', 'score' => 2, 'passed' => true],
            ],
            model: 'google/gemini-2.0-flash-exp:free',
            promptTokens: 1500,
            responseTokens: 800,
            latencyMs: 2500,
            truncationStrategies: [],
            topPriorities: ['image_alt']
        );
    }

    /**
     * Setup mocks for a full analysis flow.
     */
    private function setupFullAnalysisMocks(): void
    {
        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('google/gemini-2.0-flash-exp:free');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt content');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{"overall_score": 75}', 2300, 2500)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');
    }

    /**
     * Setup basic mocks for error/retry tests.
     */
    private function setupBasicMocks(): void
    {
        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('google/gemini-2.0-flash-exp:free');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);
        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
    }

    // =========================================================================
    // Service Instantiation Tests (SI)
    // =========================================================================

    #[Test]
    #[Group('instantiation')]
    public function service_can_be_instantiated(): void
    {
        $service = $this->createService();

        $this->assertInstanceOf(AIAnalysisService::class, $service);
    }

    #[Test]
    #[Group('instantiation')]
    public function service_implements_interface(): void
    {
        $service = $this->createService();

        $this->assertInstanceOf(AIAnalysisServiceInterface::class, $service);
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_analyze_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'analyze'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_analyze_with_model_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'analyzeWithModel'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_get_available_models_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAvailableModels'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_get_default_model_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'getDefaultModel'));
    }

    #[Test]
    #[Group('instantiation')]
    public function service_has_get_analysis_metadata_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAnalysisMetadata'));
    }

    #[Test]
    #[Group('instantiation')]
    public function constructor_has_six_dependencies(): void
    {
        $reflection = new \ReflectionClass(AIAnalysisService::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertCount(6, $params);
    }

    // =========================================================================
    // Cache Hit Flow Tests (CH)
    // =========================================================================

    #[Test]
    #[Group('cache')]
    public function cache_hit_returns_cached_result(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $cacheKey = hash('sha256', $url . md5($html));

        $cachedResult = $this->createMockAnalysisResult();

        $this->cache->shouldReceive('generateHash')
            ->once()
            ->andReturn($cacheKey);

        $this->cache->shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn($cachedResult);

        $result = $this->service->analyze($url, $html);

        $this->assertSame($cachedResult, $result);
    }

    #[Test]
    #[Group('cache')]
    public function cache_hit_skips_ai_call(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $cacheKey = 'cache-key-123';

        $cachedResult = $this->createMockAnalysisResult();

        $this->cache->shouldReceive('generateHash')->andReturn($cacheKey);
        $this->cache->shouldReceive('get')->andReturn($cachedResult);

        // AI client should NOT be called
        $this->aiClient->shouldNotReceive('generate');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('cache')]
    public function cache_hit_skips_truncation(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $cacheKey = 'cache-key-123';

        $cachedResult = $this->createMockAnalysisResult();

        $this->cache->shouldReceive('generateHash')->andReturn($cacheKey);
        $this->cache->shouldReceive('get')->andReturn($cachedResult);

        // TruncationService should NOT be called
        $this->truncationService->shouldNotReceive('truncate');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('cache')]
    public function cache_hit_skips_prompt_building(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $cacheKey = 'cache-key-123';

        $cachedResult = $this->createMockAnalysisResult();

        $this->cache->shouldReceive('generateHash')->andReturn($cacheKey);
        $this->cache->shouldReceive('get')->andReturn($cachedResult);

        // PromptService should NOT be called
        $this->promptService->shouldNotReceive('buildPrompt');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('cache')]
    public function force_refresh_bypasses_cache(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        // Even with cached result, force_refresh should ignore it
        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldNotReceive('get'); // Cache should not be checked

        $this->setupFullAnalysisMocks();

        $result = $this->service->analyze($url, $html, ['force_refresh' => true]);

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    #[Test]
    #[Group('cache')]
    public function cache_stores_result_after_analysis(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $cacheKey = 'cache-key-123';

        $this->cache->shouldReceive('generateHash')->andReturn($cacheKey);
        $this->cache->shouldReceive('get')->andReturn(null); // Cache miss

        // Set up mocks individually to avoid conflict with put expectation
        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('google/gemini-2.0-flash-exp:free');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);
        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt content');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{"overall_score": 75}', 2300, 2500)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());

        // Cache::put should be called with 24 hour TTL
        $this->cache->shouldReceive('put')
            ->once()
            ->with($cacheKey, Mockery::type(AnalysisResult::class), 86400);

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('cache')]
    public function cache_ttl_is_24_hours(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $cacheKey = 'cache-key-123';

        $this->cache->shouldReceive('generateHash')->andReturn($cacheKey);
        $this->cache->shouldReceive('get')->andReturn(null);

        // Set up mocks individually to avoid conflict with put capture
        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('google/gemini-2.0-flash-exp:free');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);
        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt content');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{"overall_score": 75}', 2300, 2500)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());

        $capturedTtl = null;
        $this->cache->shouldReceive('put')
            ->once()
            ->with($cacheKey, Mockery::type(AnalysisResult::class), Mockery::capture($capturedTtl));

        $this->service->analyze($url, $html);

        $this->assertEquals(86400, $capturedTtl); // 24 hours in seconds
    }

    // =========================================================================
    // Full Analysis Flow Tests (FA)
    // =========================================================================

    #[Test]
    #[Group('flow')]
    public function full_flow_executes_all_steps(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null); // Cache miss

        $this->setupFullAnalysisMocks();

        $result = $this->service->analyze($url, $html);

        $this->assertInstanceOf(AnalysisResult::class, $result);
        $this->assertEquals(75, $result->overallScore);
    }

    #[Test]
    #[Group('flow')]
    public function token_estimation_called(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')
            ->once()
            ->with($html)
            ->andReturn(1000);

        $this->contextManager->shouldReceive('selectOptimalModel')
            ->andReturn('google/gemini-2.0-flash-exp:free');

        $this->contextManager->shouldReceive('getEffectiveLimit')
            ->andReturn(896600);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{"overall_score": 75}', 2300, 2500)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('flow')]
    public function model_selection_called(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')
            ->once()
            ->with(1000 + 3400) // tokens + overhead
            ->andReturn('meta-llama/llama-3-8b-instruct:free');
        // Set effective limit large enough to not trigger truncation
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{"overall_score": 75}', 2300, 2500)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('flow')]
    public function prompt_building_called(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('gemini');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        $this->promptService->shouldReceive('buildPrompt')
            ->once()
            ->with($url, $html, Mockery::type('array'))
            ->andReturn('Built prompt content');

        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{"overall_score": 75}', 2300, 2500)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('flow')]
    public function ai_call_executed(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('gemini');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');

        $this->aiClient->shouldReceive('generate')
            ->once()
            ->with('prompt', Mockery::type('array'))
            ->andReturn(AIResponse::success('{"overall_score": 75}', 2300, 2500));

        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('flow')]
    public function response_parsed(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $rawResponse = '{"overall_score": 75, "overall_summary": "Test", "indicators": []}';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('gemini');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success($rawResponse, 2300, 2500)
        );

        $this->parser->shouldReceive('parse')
            ->once()
            ->with($rawResponse)
            ->andReturn($this->createMockAnalysisResult());

        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('flow')]
    public function result_cached(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $cacheKey = 'cache-key-123';

        $this->cache->shouldReceive('generateHash')->andReturn($cacheKey);
        $this->cache->shouldReceive('get')->andReturn(null);

        // Set up mocks individually to avoid conflict with put capture
        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('google/gemini-2.0-flash-exp:free');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);
        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt content');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{"overall_score": 75}', 2300, 2500)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());

        $capturedResult = null;
        $this->cache->shouldReceive('put')
            ->once()
            ->with($cacheKey, Mockery::capture($capturedResult), 86400);

        $this->service->analyze($url, $html);

        $this->assertInstanceOf(AnalysisResult::class, $capturedResult);
    }

    #[Test]
    #[Group('flow')]
    public function metadata_populated(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('gemini-model');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{"overall_score": 75}', 1500, 800)
        );

        $result = $this->createMockAnalysisResult();
        $this->parser->shouldReceive('parse')->andReturn($result);
        $this->cache->shouldReceive('put');

        $analysisResult = $this->service->analyze($url, $html);

        // Model should be the one returned by selectOptimalModel
        $this->assertEquals('gemini-model', $analysisResult->model);
        $this->assertGreaterThanOrEqual(0, $analysisResult->promptTokens);
        $this->assertGreaterThanOrEqual(0, $analysisResult->responseTokens);
    }

    // =========================================================================
    // Model Selection Tests (MS)
    // =========================================================================

    #[Test]
    #[Group('model-selection')]
    public function auto_select_for_small_content(): void
    {
        $url = 'https://example.com';
        $html = str_repeat('a', 1000); // Small content

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(250);
        $this->contextManager->shouldReceive('selectOptimalModel')
            ->with(250 + 3400)
            ->andReturn('meta-llama/llama-3-8b-instruct:free');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(3972);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->with('prompt', Mockery::on(function ($options) {
                return $options['model'] === 'meta-llama/llama-3-8b-instruct:free';
            }))
            ->andReturn(AIResponse::success('{}', 100, 100));

        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('model-selection')]
    public function override_with_options_model(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldNotReceive('selectOptimalModel'); // Should not be called
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(26091);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->with('prompt', Mockery::on(function ($options) {
                return $options['model'] === 'qwen/qwen-2-7b-instruct:free';
            }))
            ->andReturn(AIResponse::success('{}', 100, 100));

        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html, ['model' => 'qwen/qwen-2-7b-instruct:free']);
    }

    #[Test]
    #[Group('model-selection')]
    public function analyze_with_model_uses_specified(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $specificModel = 'mistralai/mistral-7b-instruct:free';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldNotReceive('selectOptimalModel');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(26091);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->with('prompt', Mockery::on(function ($options) use ($specificModel) {
                return $options['model'] === $specificModel;
            }))
            ->andReturn(AIResponse::success('{}', 100, 100));

        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyzeWithModel($url, $html, $specificModel);
    }

    #[Test]
    #[Group('model-selection')]
    public function get_available_models_returns_array(): void
    {
        $models = $this->service->getAvailableModels();

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);

        // Each model should have required keys
        foreach ($models as $id => $model) {
            $this->assertArrayHasKey('id', $model);
            $this->assertArrayHasKey('name', $model);
            $this->assertArrayHasKey('context_window', $model);
            $this->assertArrayHasKey('is_free', $model);
        }
    }

    #[Test]
    #[Group('model-selection')]
    public function get_default_model_returns_string(): void
    {
        $defaultModel = $this->service->getDefaultModel();

        $this->assertIsString($defaultModel);
        $this->assertNotEmpty($defaultModel);
        $this->assertStringContainsString('/', $defaultModel); // Model IDs contain /
    }

    // =========================================================================
    // Truncation Flow Tests (TF)
    // =========================================================================

    #[Test]
    #[Group('truncation')]
    public function no_truncation_when_fits(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Small content</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('gemini');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        // Truncation should NOT be called since content fits
        $this->truncationService->shouldNotReceive('truncate');

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{}', 100, 100)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('truncation')]
    public function truncation_when_exceeds_limit(): void
    {
        $url = 'https://example.com';
        $html = str_repeat('a', 5000000); // Large content

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1250000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('gemini');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        // Truncation SHOULD be called since content exceeds limit
        $truncationResult = new TruncationResult(
            html: '<html><body>Truncated</body></html>',
            originalTokens: 1250000,
            finalTokens: 890000,
            strategiesApplied: ['removeComments', 'removeScripts', 'removeStyles'],
            reductionPercent: 28.8
        );

        $this->truncationService->shouldReceive('truncate')
            ->once()
            ->with($html, Mockery::type('int'))
            ->andReturn($truncationResult);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{}', 100, 100)
        );

        $result = new AnalysisResult(
            overallScore: 75,
            overallSummary: 'Test',
            categoryScores: [],
            indicators: [],
            model: 'gemini',
            promptTokens: 100,
            responseTokens: 100,
            latencyMs: 100,
            truncationStrategies: ['removeComments', 'removeScripts', 'removeStyles']
        );
        $this->parser->shouldReceive('parse')->andReturn($result);
        $this->cache->shouldReceive('put');

        $analysisResult = $this->service->analyze($url, $html);

        $this->assertNotEmpty($analysisResult->truncationStrategies);
    }

    // =========================================================================
    // Retry Logic Tests (RL)
    // =========================================================================

    #[Test]
    #[Group('retry')]
    public function retry_on_429_rate_limit(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->setupBasicMocks();

        // First call: 429 rate limit
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::error('Rate limited', 100));

        // Second call: success
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::success('{"overall_score": 75}', 100, 200));

        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $result = $this->service->analyze($url, $html);

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    #[Test]
    #[Group('retry')]
    public function retry_on_500_server_error(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->setupBasicMocks();

        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::error('Internal server error', 100));

        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::success('{}', 100, 200));

        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('retry')]
    public function max_three_retries(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->setupBasicMocks();

        // All calls fail (initial + 3 retries = 4 calls)
        $this->aiClient->shouldReceive('generate')
            ->times(4)
            ->andReturn(AIResponse::error('Rate limited', 100));

        $this->expectException(AIAnalysisException::class);

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('retry')]
    public function no_retry_on_400_bad_request(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->setupBasicMocks();

        // Only one call should be made
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::error('Bad request', 100));

        $this->expectException(AIAnalysisException::class);

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('retry')]
    public function success_after_retry(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->setupBasicMocks();

        // Fail twice, succeed on third
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::error('Rate limited', 100));

        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::error('Rate limited', 100));

        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::success('{"overall_score": 85}', 500, 1500));

        $result = $this->createMockAnalysisResult(85);
        $this->parser->shouldReceive('parse')->andReturn($result);
        $this->cache->shouldReceive('put');

        $analysisResult = $this->service->analyze($url, $html);

        $this->assertEquals(85, $analysisResult->overallScore);
    }

    // =========================================================================
    // Error Handling Tests (EH)
    // =========================================================================

    #[Test]
    #[Group('error')]
    public function ai_analysis_exception_on_failure(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->setupBasicMocks();

        // Non-retryable error - should only call once
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::error('API error', 100));

        $this->expectException(AIAnalysisException::class);

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('error')]
    public function empty_html_handled(): void
    {
        $url = 'https://example.com';
        $html = '';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(0);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('gemini');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{}', 100, 100)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $result = $this->service->analyze($url, $html);

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    #[Test]
    #[Group('error')]
    public function null_html_throws_exception(): void
    {
        $url = 'https://example.com';

        $this->expectException(\TypeError::class);

        $this->service->analyze($url, null);
    }

    #[Test]
    #[Group('error')]
    public function empty_url_throws_exception(): void
    {
        $url = '';
        $html = '<html><body>Test</body></html>';

        $this->expectException(\InvalidArgumentException::class);

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('error')]
    public function malformed_html_handled(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Unclosed tags <div><p>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->setupFullAnalysisMocks();

        // Should not throw, should continue analysis
        $result = $this->service->analyze($url, $html);

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    // =========================================================================
    // Acceptance Criteria Tests (AC)
    // =========================================================================

    #[Test]
    #[Group('acceptance')]
    public function ac_analyze_orchestrates_complete_flow(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        // Verify all steps called
        $this->contextManager->shouldReceive('estimateTokens')->once()->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->once()->andReturn('gemini');
        $this->contextManager->shouldReceive('getEffectiveLimit')->once()->andReturn(896600);
        $this->promptService->shouldReceive('buildPrompt')->once()->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')->once()->andReturn(
            AIResponse::success('{}', 100, 100)
        );
        $this->parser->shouldReceive('parse')->once()->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put')->once();

        $result = $this->service->analyze($url, $html);

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_cache_checked_before_ai_call(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $cachedResult = $this->createMockAnalysisResult();

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->once()->andReturn($cachedResult);

        // AI should NOT be called
        $this->aiClient->shouldNotReceive('generate');

        $result = $this->service->analyze($url, $html);

        $this->assertSame($cachedResult, $result);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_model_selection_automatic(): void
    {
        $url = 'https://example.com';
        $html = str_repeat('a', 50000);

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(12500);
        $this->contextManager->shouldReceive('selectOptimalModel')
            ->once()
            ->with(12500 + 3400)
            ->andReturn('qwen/qwen-2-7b-instruct:free');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(26091);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->with('prompt', Mockery::on(fn($opts) => str_contains($opts['model'], 'qwen')))
            ->andReturn(AIResponse::success('{}', 100, 100));

        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_truncation_applied_when_needed(): void
    {
        $url = 'https://example.com';
        $html = str_repeat('a', 5000000);

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1250000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('gemini');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        $this->truncationService->shouldReceive('truncate')
            ->once()
            ->andReturn(new TruncationResult(
                html: '<html></html>',
                originalTokens: 1250000,
                finalTokens: 890000,
                strategiesApplied: ['removeScripts'],
                reductionPercent: 28.8
            ));

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')->andReturn(AIResponse::success('{}', 100, 100));

        $result = new AnalysisResult(
            overallScore: 75,
            overallSummary: 'Test',
            categoryScores: [],
            indicators: [],
            model: 'gemini',
            promptTokens: 100,
            responseTokens: 100,
            latencyMs: 100,
            truncationStrategies: ['removeScripts']
        );
        $this->parser->shouldReceive('parse')->andReturn($result);
        $this->cache->shouldReceive('put');

        $analysisResult = $this->service->analyze($url, $html);

        $this->assertNotEmpty($analysisResult->truncationStrategies);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_response_parsed_and_validated(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $rawResponse = '{"overall_score": 75}';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->setupBasicMocks();

        $this->aiClient->shouldReceive('generate')
            ->andReturn(AIResponse::success($rawResponse, 100, 100));

        $this->parser->shouldReceive('parse')
            ->once()
            ->with($rawResponse)
            ->andReturn($this->createMockAnalysisResult());

        $this->cache->shouldReceive('put');

        $this->service->analyze($url, $html);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_result_cached_24_hours(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';
        $cacheKey = 'test-cache-key';

        $this->cache->shouldReceive('generateHash')->andReturn($cacheKey);
        $this->cache->shouldReceive('get')->andReturn(null);

        // Set up mocks individually without using setupFullAnalysisMocks to avoid conflict with put expectation
        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1000);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('google/gemini-2.0-flash-exp:free');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);
        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt content');
        $this->aiClient->shouldReceive('generate')->andReturn(
            AIResponse::success('{"overall_score": 75}', 2300, 2500)
        );
        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());

        $capturedTtl = null;
        $this->cache->shouldReceive('put')
            ->once()
            ->with($cacheKey, Mockery::type(AnalysisResult::class), Mockery::capture($capturedTtl));

        $this->service->analyze($url, $html);

        $this->assertEquals(86400, $capturedTtl);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_retry_policy_transient_errors(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->setupBasicMocks();

        // 429 should trigger retry
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::error('Rate limited', 100));

        // Second attempt succeeds
        $this->aiClient->shouldReceive('generate')
            ->once()
            ->andReturn(AIResponse::success('{}', 100, 100));

        $this->parser->shouldReceive('parse')->andReturn($this->createMockAnalysisResult());
        $this->cache->shouldReceive('put');

        $result = $this->service->analyze($url, $html);

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    #[Test]
    #[Group('acceptance')]
    public function ac_all_metadata_stored(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        $this->cache->shouldReceive('generateHash')->andReturn('cache-key');
        $this->cache->shouldReceive('get')->andReturn(null);

        $this->contextManager->shouldReceive('estimateTokens')->andReturn(1500);
        $this->contextManager->shouldReceive('selectOptimalModel')->andReturn('google/gemini-2.0-flash-exp:free');
        $this->contextManager->shouldReceive('getEffectiveLimit')->andReturn(896600);

        $this->promptService->shouldReceive('buildPrompt')->andReturn('prompt');
        $this->aiClient->shouldReceive('generate')
            ->andReturn(AIResponse::success('{}', 2000, 5000));

        $result = $this->createMockAnalysisResult();
        $this->parser->shouldReceive('parse')->andReturn($result);
        $this->cache->shouldReceive('put');

        $analysisResult = $this->service->analyze($url, $html);

        // Verify all metadata present
        $this->assertNotEmpty($analysisResult->model);
        $this->assertGreaterThanOrEqual(0, $analysisResult->promptTokens);
        $this->assertGreaterThanOrEqual(0, $analysisResult->responseTokens);
        $this->assertGreaterThanOrEqual(0, $analysisResult->latencyMs);
        $this->assertIsArray($analysisResult->truncationStrategies);
    }
}
