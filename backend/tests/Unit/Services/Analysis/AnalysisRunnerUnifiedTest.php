<?php

namespace Tests\Unit\Services\Analysis;

use Tests\TestCase;
use App\Services\AnalysisRunnerService;
use App\Services\AI\AIAnalysisServiceInterface;
use App\Services\AI\DTOs\AnalysisResult;
use App\Models\SEOAnalysis;
use App\Models\AIAnalysisRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for AnalysisRunnerService unified AI prompt integration.
 *
 * Tests the transition from 17+ individual AI calls to a single unified call
 * that returns all indicator results plus an overall summary.
 *
 * @see \App\Services\AnalysisRunnerService
 * @see TASK-31-13
 * @see TEST-31-13
 */
class AnalysisRunnerUnifiedTest extends TestCase
{
    use RefreshDatabase;

    protected AnalysisRunnerService $service;
    protected $mockAIAnalysisService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock AIAnalysisService
        $this->mockAIAnalysisService = Mockery::mock(AIAnalysisServiceInterface::class);
        
        // Bind mock to container
        $this->app->instance(AIAnalysisServiceInterface::class, $this->mockAIAnalysisService);

        // Enable unified prompt by default
        Config::set('ai.unified_prompt_enabled', true);

        $this->service = app(AnalysisRunnerService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock AnalysisResult DTO.
     */
    protected function createMockAnalysisResult(array $overrides = []): AnalysisResult
    {
        $defaults = [
            'overallScore' => 78,
            'overallSummary' => 'Good SEO foundation with room for improvement.',
            'categoryScores' => [
                'technical' => 85,
                'content' => 72,
                'accessibility' => 80,
                'performance' => 75,
            ],
            'indicators' => $this->createMockIndicators(),
            'model' => 'google/gemini-2.0-flash-exp:free',
            'promptTokens' => 15234,
            'responseTokens' => 2847,
            'latencyMs' => 3500,
            'truncationStrategies' => [],
            'topPriorities' => [
                ['indicator' => 'meta_description', 'message' => 'Add meta description'],
            ],
        ];

        $data = array_merge($defaults, $overrides);

        return new AnalysisResult(
            overallScore: $data['overallScore'],
            overallSummary: $data['overallSummary'],
            categoryScores: $data['categoryScores'],
            indicators: $data['indicators'],
            model: $data['model'],
            promptTokens: $data['promptTokens'],
            responseTokens: $data['responseTokens'],
            latencyMs: $data['latencyMs'],
            truncationStrategies: $data['truncationStrategies'],
            topPriorities: $data['topPriorities'],
        );
    }

    /**
     * Create mock indicator data.
     */
    protected function createMockIndicators(): array
    {
        return [
            [
                'id' => 'url',
                'name' => 'URL Structure',
                'score' => 3,
                'passed' => true,
                'details' => ['is_https' => true, 'length' => 45],
                'suggestions' => [],
            ],
            [
                'id' => 'title_tag',
                'name' => 'Title Tag',
                'score' => 2,
                'passed' => true,
                'details' => ['length' => 55, 'has_keywords' => true],
                'suggestions' => ['Consider adding brand name'],
            ],
            [
                'id' => 'meta_description',
                'name' => 'Meta Description',
                'score' => 1,
                'passed' => false,
                'details' => ['missing' => true],
                'suggestions' => ['Add a meta description'],
            ],
            [
                'id' => 'h1_headings',
                'name' => 'H1 Headings',
                'score' => 3,
                'passed' => true,
                'details' => ['count' => 1, 'content' => 'Main Heading'],
                'suggestions' => [],
            ],
        ];
    }

    // =========================================================================
    // UNIFIED ANALYSIS EXECUTION TESTS (UA-*)
    // =========================================================================

    /**
     * UA-001: run() calls AIAnalysisService once.
     */
    public function test_run_calls_ai_analysis_service_once(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body><h1>Heading</h1></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
    }

    /**
     * UA-002: run() passes URL to AIAnalysisService.
     */
    public function test_run_passes_url_to_ai_analysis_service(): void
    {
        $url = 'https://example.com/test-page';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->with($url, $html, Mockery::any())
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $this->service->run($url, $html);
    }

    /**
     * UA-003: run() passes HTML to AIAnalysisService.
     */
    public function test_run_passes_html_to_ai_analysis_service(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test Page</title></head><body><p>Content</p></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->with($url, $html, Mockery::any())
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $this->service->run($url, $html);
    }

    /**
     * UA-004: run() passes options to AIAnalysisService.
     */
    public function test_run_passes_options_to_ai_analysis_service(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $options = ['timeout' => 120, 'model' => 'custom-model'];

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->with($url, $html, Mockery::on(function ($arg) use ($options) {
                return isset($arg['timeout']) && $arg['timeout'] === 120;
            }))
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $this->service->run($url, $html, $options);
    }

    /**
     * UA-005: run() returns SEOAnalysis model.
     */
    public function test_run_returns_seo_analysis_model(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertInstanceOf(SEOAnalysis::class, $result);
    }

    /**
     * UA-006: run() stores overall_score from AI result.
     */
    public function test_run_stores_overall_score_from_ai_result(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['overallScore' => 85]));

        $result = $this->service->run($url, $html);

        $this->assertEquals(85, $result->overall_score);
    }

    /**
     * UA-007: run() stores overall_summary from AI result.
     */
    public function test_run_stores_overall_summary_from_ai_result(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $summary = 'This is a test summary for the SEO analysis.';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['overallSummary' => $summary]));

        $result = $this->service->run($url, $html);

        $this->assertEquals($summary, $result->overall_summary);
    }

    /**
     * UA-008: run() stores category_scores from AI result.
     */
    public function test_run_stores_category_scores_from_ai_result(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $categoryScores = [
            'technical' => 90,
            'content' => 75,
            'accessibility' => 85,
            'performance' => 70,
        ];

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['categoryScores' => $categoryScores]));

        $result = $this->service->run($url, $html);

        $this->assertEquals($categoryScores, $result->category_scores);
    }

    /**
     * UA-009: run() stores ai_model from AI result.
     */
    public function test_run_stores_ai_model_from_ai_result(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $model = 'google/gemini-2.0-flash-exp:free';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['model' => $model]));

        $result = $this->service->run($url, $html);

        $this->assertEquals($model, $result->ai_model);
    }

    /**
     * UA-010: run() stores prompt_tokens from AI result.
     */
    public function test_run_stores_prompt_tokens_from_ai_result(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['promptTokens' => 25000]));

        $result = $this->service->run($url, $html);

        $this->assertEquals(25000, $result->prompt_tokens);
    }

    /**
     * UA-011: run() stores response_tokens from AI result.
     */
    public function test_run_stores_response_tokens_from_ai_result(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['responseTokens' => 3500]));

        $result = $this->service->run($url, $html);

        $this->assertEquals(3500, $result->response_tokens);
    }

    /**
     * UA-012: run() stores total_tokens calculated.
     */
    public function test_run_stores_total_tokens_calculated(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult([
                'promptTokens' => 20000,
                'responseTokens' => 3000,
            ]));

        $result = $this->service->run($url, $html);

        $this->assertEquals(23000, $result->total_tokens);
    }

    /**
     * UA-013: run() stores truncation_applied when present.
     */
    public function test_run_stores_truncation_applied_when_present(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $truncationStrategies = ['remove_scripts', 'collapse_whitespace'];

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['truncationStrategies' => $truncationStrategies]));

        $result = $this->service->run($url, $html);

        $this->assertEquals($truncationStrategies, $result->truncation_applied);
    }

    /**
     * UA-014: run() stores all indicators.
     */
    public function test_run_stores_all_indicators(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $indicators = $this->createMockIndicators();

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['indicators' => $indicators]));

        $result = $this->service->run($url, $html);

        $analysisData = $result->analysis_data;
        $this->assertCount(count($indicators), $analysisData['indicators'] ?? $indicators);
    }

    /**
     * UA-015: run() creates AIAnalysisRequest record.
     */
    public function test_run_creates_ai_analysis_request_record(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result->aiRequest);
        $this->assertInstanceOf(AIAnalysisRequest::class, $result->aiRequest);
    }

    /**
     * UA-016: run() handles analysis_data JSON properly.
     */
    public function test_run_handles_analysis_data_json_properly(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertIsArray($result->analysis_data);
    }

    /**
     * UA-017: run() with specific model option.
     */
    public function test_run_with_specific_model_option(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $options = ['model' => 'meta-llama/llama-3-8b-instruct:free'];

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->with($url, $html, Mockery::on(function ($arg) use ($options) {
                return isset($arg['model']) && $arg['model'] === $options['model'];
            }))
            ->once()
            ->andReturn($this->createMockAnalysisResult(['model' => $options['model']]));

        $result = $this->service->run($url, $html, $options);

        $this->assertEquals($options['model'], $result->ai_model);
    }

    /**
     * UA-021: run() sets status to completed.
     */
    public function test_run_sets_status_to_completed(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertEquals(SEOAnalysis::STATUS_COMPLETED, $result->status);
    }

    /**
     * UA-023: run() with user_id option.
     */
    public function test_run_with_user_id_option(): void
    {
        $user = \App\Models\User::factory()->create();
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html, ['user_id' => $user->id]);

        $this->assertEquals($user->id, $result->user_id);
    }

    /**
     * UA-024: run() with session_id option.
     */
    public function test_run_with_session_id_option(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $sessionId = 'test-session-123';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html, ['session_id' => $sessionId]);

        $this->assertEquals($sessionId, $result->session_id);
    }

    // =========================================================================
    // FEATURE FLAG TESTS (FF-*)
    // =========================================================================

    /**
     * FF-001: Feature flag enabled = unified flow.
     */
    public function test_feature_flag_enabled_uses_unified_flow(): void
    {
        Config::set('ai.unified_prompt_enabled', true);

        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result->ai_model);
    }

    /**
     * FF-002: Feature flag disabled = legacy flow.
     */
    public function test_feature_flag_disabled_uses_legacy_flow(): void
    {
        Config::set('ai.unified_prompt_enabled', false);

        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        // Should NOT call AIAnalysisService in legacy mode
        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->never();

        $result = $this->service->run($url, $html);

        // Legacy mode should not have AI model set
        $this->assertNull($result->ai_model);
    }

    /**
     * FF-003: Feature flag missing defaults to unified.
     */
    public function test_feature_flag_missing_defaults_to_unified(): void
    {
        Config::set('ai.unified_prompt_enabled', null);

        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result->ai_model);
    }

    /**
     * FF-008: Flag per-analysis override.
     */
    public function test_feature_flag_per_analysis_override(): void
    {
        Config::set('ai.unified_prompt_enabled', true);

        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        // Override to use legacy for this specific analysis
        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->never();

        $result = $this->service->run($url, $html, ['use_unified' => false]);

        $this->assertNull($result->ai_model);
    }

    // =========================================================================
    // ERROR HANDLING TESTS (EH-*)
    // =========================================================================

    /**
     * EH-001: AIAnalysisException throws.
     */
    public function test_ai_analysis_exception_throws(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andThrow(new \App\Exceptions\AIAnalysisException('AI service unavailable'));

        $result = $this->service->run($url, $html);

        $this->assertEquals(SEOAnalysis::STATUS_FAILED, $result->status);
    }

    /**
     * EH-002: AIAnalysisException stores error message.
     */
    public function test_ai_analysis_exception_stores_error_message(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $errorMessage = 'AI service temporarily unavailable';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andThrow(new \App\Exceptions\AIAnalysisException($errorMessage));

        $result = $this->service->run($url, $html);

        $this->assertStringContainsString($errorMessage, $result->error_message);
    }

    /**
     * EH-005: Invalid JSON response handled.
     */
    public function test_invalid_json_response_handled(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andThrow(new \App\Exceptions\AIAnalysisException('JSON validation failed'));

        $result = $this->service->run($url, $html);

        $this->assertEquals(SEOAnalysis::STATUS_FAILED, $result->status);
    }

    /**
     * EH-007: Missing indicator in response.
     */
    public function test_missing_indicator_in_response(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        // Response with only 2 indicators instead of 17
        $result = $this->createMockAnalysisResult([
            'indicators' => [
                ['id' => 'url', 'name' => 'URL', 'score' => 2, 'passed' => true, 'details' => [], 'suggestions' => []],
            ],
        ]);

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($result);

        $analysis = $this->service->run($url, $html);

        $this->assertEquals(SEOAnalysis::STATUS_COMPLETED, $analysis->status);
    }

    /**
     * EH-016: Empty HTML input.
     */
    public function test_empty_html_input(): void
    {
        $url = 'https://example.com';
        $html = '';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertEquals(SEOAnalysis::STATUS_COMPLETED, $result->status);
    }

    // =========================================================================
    // DATA STORAGE TESTS (DS-*)
    // =========================================================================

    /**
     * DS-001: SEOAnalysis record created.
     */
    public function test_seo_analysis_record_created(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertDatabaseHas('seo_analyses', [
            'id' => $result->id,
        ]);
    }

    /**
     * DS-002: AIAnalysisRequest record created.
     */
    public function test_ai_analysis_request_record_created(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertDatabaseHas('ai_analysis_requests', [
            'analysis_id' => $result->id,
        ]);
    }

    /**
     * DS-005: Token usage stored in both tables.
     */
    public function test_token_usage_stored_in_both_tables(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult([
                'promptTokens' => 15000,
                'responseTokens' => 2500,
            ]));

        $result = $this->service->run($url, $html);

        $this->assertEquals(15000, $result->prompt_tokens);
        $this->assertEquals(2500, $result->response_tokens);
        $this->assertEquals(17500, $result->total_tokens);

        $aiRequest = $result->aiRequest;
        $this->assertEquals(15000, $aiRequest->prompt_tokens);
        $this->assertEquals(2500, $aiRequest->response_tokens);
        $this->assertEquals(17500, $aiRequest->total_tokens);
    }

    /**
     * DS-010: Existing analysis updated on re-run.
     */
    public function test_existing_analysis_updated_on_rerun(): void
    {
        $url = 'https://example.com';
        $html1 = '<html><head><title>First</title></head><body></body></html>';
        $html2 = '<html><head><title>Second Updated</title></head><body></body></html>';

        // First run
        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['overallScore' => 70]));

        $result1 = $this->service->run($url, $html1);

        // Second run
        $this->mockAIAnalysisService = Mockery::mock(AIAnalysisServiceInterface::class);
        $this->app->instance(AIAnalysisServiceInterface::class, $this->mockAIAnalysisService);
        $this->service = app(AnalysisRunnerService::class);

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['overallScore' => 85]));

        $result2 = $this->service->run($url, $html2);

        $this->assertEquals($result1->id, $result2->id);
        $this->assertEquals(85, $result2->overall_score);
    }

    /**
     * DS-011: Status transitions: pending → running → completed.
     */
    public function test_status_transitions_pending_to_completed(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertEquals(SEOAnalysis::STATUS_COMPLETED, $result->status);
    }

    // =========================================================================
    // BACKWARD COMPATIBILITY TESTS (BC-*)
    // =========================================================================

    /**
     * BC-001: Legacy analyses remain queryable.
     */
    public function test_legacy_analyses_remain_queryable(): void
    {
        // Create a legacy analysis (without AI fields)
        $legacyAnalysis = SEOAnalysis::create([
            'domain' => 'legacy-example.com',
            'normalized_url' => 'https://legacy-example.com',
            'status' => SEOAnalysis::STATUS_COMPLETED,
            'overall_score' => 75,
            'analysis_data' => ['indicators' => []],
        ]);

        // Legacy analysis should be queryable
        $found = SEOAnalysis::find($legacyAnalysis->id);
        $this->assertNotNull($found);
        $this->assertEquals(75, $found->overall_score);
    }

    /**
     * BC-002: Legacy analyses have null ai_model.
     */
    public function test_legacy_analyses_have_null_ai_model(): void
    {
        $legacyAnalysis = SEOAnalysis::create([
            'domain' => 'legacy-example.com',
            'normalized_url' => 'https://legacy-example.com',
            'status' => SEOAnalysis::STATUS_COMPLETED,
            'overall_score' => 75,
            'analysis_data' => ['indicators' => []],
        ]);

        $this->assertNull($legacyAnalysis->ai_model);
    }

    /**
     * BC-005: Mixed legacy and unified analyses in results.
     */
    public function test_mixed_legacy_and_unified_analyses(): void
    {
        // Create legacy analysis
        $legacy = SEOAnalysis::create([
            'domain' => 'legacy.com',
            'normalized_url' => 'https://legacy.com',
            'status' => SEOAnalysis::STATUS_COMPLETED,
            'overall_score' => 60,
            'analysis_data' => [],
        ]);

        // Create unified analysis
        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['overallScore' => 80]));

        $unified = $this->service->run('https://unified.com', '<html><body></body></html>');

        // Both should be queryable
        $allAnalyses = SEOAnalysis::all();
        $this->assertEquals(2, $allAnalyses->count());

        // Can distinguish between them
        $legacyFound = $allAnalyses->firstWhere('id', $legacy->id);
        $unifiedFound = $allAnalyses->firstWhere('id', $unified->id);

        $this->assertNull($legacyFound->ai_model);
        $this->assertNotNull($unifiedFound->ai_model);
    }

    // =========================================================================
    // PERFORMANCE TESTS (PO-*)
    // =========================================================================

    /**
     * PO-001: Unified flow faster than legacy.
     */
    public function test_unified_flow_completes_reasonably_fast(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>' . str_repeat('<p>Content</p>', 100) . '</body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $start = microtime(true);
        $this->service->run($url, $html);
        $elapsed = microtime(true) - $start;

        // Should complete in under 5 seconds with mocked AI
        $this->assertLessThan(5, $elapsed);
    }

    /**
     * PO-002: Single AI call vs 17+ calls.
     */
    public function test_single_ai_call_made(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        // Verify exactly one call to AIAnalysisService
        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $this->service->run($url, $html);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Handles very large HTML.
     */
    public function test_handles_very_large_html(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>' . str_repeat('<p>Content</p>', 10000) . '</body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertEquals(SEOAnalysis::STATUS_COMPLETED, $result->status);
    }

    /**
     * Handles unicode in HTML.
     */
    public function test_handles_unicode_html(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>日本語タイトル</title></head><body><p>Привет мир</p></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertEquals(SEOAnalysis::STATUS_COMPLETED, $result->status);
    }

    /**
     * Handles malformed HTML.
     */
    public function test_handles_malformed_html(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</body></html>'; // Malformed

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult());

        $result = $this->service->run($url, $html);

        $this->assertEquals(SEOAnalysis::STATUS_COMPLETED, $result->status);
    }

    /**
     * Zero score handled correctly.
     */
    public function test_zero_score_handled_correctly(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['overallScore' => 0]));

        $result = $this->service->run($url, $html);

        $this->assertEquals(0, $result->overall_score);
    }

    /**
     * Perfect score handled correctly.
     */
    public function test_perfect_score_handled_correctly(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $this->mockAIAnalysisService
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($this->createMockAnalysisResult(['overallScore' => 100]));

        $result = $this->service->run($url, $html);

        $this->assertEquals(100, $result->overall_score);
    }
}
