<?php

namespace Tests\Unit\Services;

use App\Models\KeywordSuggestion;
use App\Models\Site;
use App\Repositories\KeywordSuggestionRepository;
use App\Services\Fetchers\GoogleSuggestFetcher;
use App\Services\Fetchers\QuestionGenerator;
use App\Services\OpportunityScorer;
use App\Services\SuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for the SuggestionService.
 *
 * Tests the aggregation service that combines multiple suggestion sources
 * and stores them with calculated opportunity scores.
 *
 * @see TASK-5-6
 * @see TEST-5-13
 * @see SPEC-005
 */
class SuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SuggestionService $service;
    protected $mockRepository;
    protected $mockQuestionGenerator;
    protected $mockGoogleSuggestFetcher;
    protected $mockOpportunityScorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(KeywordSuggestionRepository::class);
        $this->mockQuestionGenerator = Mockery::mock(QuestionGenerator::class);
        $this->mockGoogleSuggestFetcher = Mockery::mock(GoogleSuggestFetcher::class);
        $this->mockOpportunityScorer = Mockery::mock(OpportunityScorer::class);

        $this->service = new SuggestionService(
            $this->mockRepository,
            $this->mockQuestionGenerator,
            $this->mockGoogleSuggestFetcher,
            $this->mockOpportunityScorer
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // GENERATE FROM QUESTION SOURCE TESTS
    // =========================================================================

    /**
     * Test generate from question source.
     * SS-001
     */
    public function test_generate_from_question_source(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->with('seo tools')
            ->andReturn(['what is seo tools', 'how to seo tools', 'why seo tools']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->times(3)
            ->andReturn(65, 65, 65);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->times(3)
            ->andReturnUsing(function ($data) {
                return new KeywordSuggestion($data);
            });

        $count = $this->service->generateSuggestions($site->id, 'seo tools', ['question']);

        $this->assertEquals(3, $count);
    }

    /**
     * Test calculate opportunity scores for each suggestion.
     * SS-002
     */
    public function test_calculate_opportunity_scores(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn(['how to seo']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->once()
            ->with('how to seo')
            ->andReturn(75);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->once()
            ->withArgs(function ($data) {
                return $data['opportunity_score'] === 75;
            })
            ->andReturn(new KeywordSuggestion());

        $this->service->generateSuggestions($site->id, 'seo', ['question']);

        // Assertion is in the mock expectation
        $this->assertTrue(true);
    }

    /**
     * Test store in repository.
     * SS-003
     */
    public function test_store_in_repository(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn(['test keyword']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->andReturn(50);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->once()
            ->withArgs(function ($data) use ($site) {
                return $data['site_id'] === $site->id
                    && $data['keyword'] === 'test keyword'
                    && $data['source'] === 'question';
            })
            ->andReturn(new KeywordSuggestion());

        $this->service->generateSuggestions($site->id, 'seo', ['question']);
    }

    // =========================================================================
    // DEDUPLICATION TESTS
    // =========================================================================

    /**
     * Test deduplicate existing suggestions.
     * SS-004
     */
    public function test_deduplicate_existing(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn(['existing keyword']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->andReturn(50);

        // When keyword already exists, update instead of create
        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->once()
            ->andReturnUsing(function ($data) {
                $suggestion = new KeywordSuggestion($data);
                $suggestion->exists = true; // Simulate existing record
                return $suggestion;
            });

        $count = $this->service->generateSuggestions($site->id, 'seo', ['question']);

        $this->assertEquals(1, $count);
    }

    /**
     * Test undismiss rediscovered suggestions.
     * SS-005
     */
    public function test_undismiss_rediscovered(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn(['rediscovered keyword']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->andReturn(50);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->once()
            ->withArgs(function ($data) {
                // Should include is_dismissed = false for rediscovered
                return isset($data['is_dismissed']) && $data['is_dismissed'] === false;
            })
            ->andReturn(new KeywordSuggestion());

        $this->service->generateSuggestions($site->id, 'seo', ['question']);
    }

    // =========================================================================
    // EMPTY RESULTS TESTS
    // =========================================================================

    /**
     * Test handle empty results.
     * SS-006
     */
    public function test_handle_empty_results(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn([]);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->never();

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->never();

        $count = $this->service->generateSuggestions($site->id, 'seo', ['question']);

        $this->assertEquals(0, $count);
    }

    // =========================================================================
    // MULTIPLE SOURCES TESTS
    // =========================================================================

    /**
     * Test multiple sources aggregation.
     * SS-007
     */
    public function test_multiple_sources(): void
    {
        $site = Site::factory()->create();

        // Question generator
        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->with('seo')
            ->andReturn(['what is seo', 'how to seo']);

        // Google Suggest fetcher
        $this->mockGoogleSuggestFetcher
            ->shouldReceive('fetch')
            ->with('seo')
            ->andReturn(['best seo tools', 'free seo tools']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->times(4)
            ->andReturn(65, 65, 60, 60);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->times(4)
            ->andReturn(new KeywordSuggestion());

        $count = $this->service->generateSuggestions($site->id, 'seo', ['question', 'suggest']);

        $this->assertEquals(4, $count);
    }

    /**
     * Test sources are deduplicated across multiple sources.
     */
    public function test_deduplicate_across_sources(): void
    {
        $site = Site::factory()->create();

        // Both sources return same keyword
        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn(['how to seo']);

        $this->mockGoogleSuggestFetcher
            ->shouldReceive('fetch')
            ->andReturn(['how to seo', 'best seo']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->times(2) // Only 2 unique keywords
            ->andReturn(65);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->times(2)
            ->andReturn(new KeywordSuggestion());

        $count = $this->service->generateSuggestions($site->id, 'seo', ['question', 'suggest']);

        $this->assertEquals(2, $count); // Deduplicated
    }

    // =========================================================================
    // LOGGING TESTS
    // =========================================================================

    /**
     * Test log performance.
     * SS-008
     */
    public function test_log_performance(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn(['keyword 1', 'keyword 2']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->andReturn(50);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->andReturn(new KeywordSuggestion());

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($site) {
                return str_contains($message, 'suggestions generated')
                    && isset($context['site_id'])
                    && $context['site_id'] === $site->id
                    && isset($context['count'])
                    && isset($context['duration_ms']);
            });

        $this->service->generateSuggestions($site->id, 'seo', ['question']);
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /**
     * Test handle fetcher exception.
     * SS-009
     */
    public function test_handle_fetcher_exception(): void
    {
        $site = Site::factory()->create();

        // Question generator throws
        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andThrow(new \Exception('Generator failed'));

        // Google Suggest should still work
        $this->mockGoogleSuggestFetcher
            ->shouldReceive('fetch')
            ->andReturn(['fallback keyword']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->once()
            ->andReturn(60);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->once()
            ->andReturn(new KeywordSuggestion());

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'failed');
            });

        Log::shouldReceive('info')
            ->once();

        $count = $this->service->generateSuggestions($site->id, 'seo', ['question', 'suggest']);

        $this->assertEquals(1, $count);
    }

    /**
     * Test transaction rollback on storage failure.
     * SS-010
     */
    public function test_transaction_rollback(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn(['keyword 1']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->andReturn(50);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->andThrow(new \Exception('Storage failed'));

        Log::shouldReceive('error')
            ->once();

        $count = $this->service->generateSuggestions($site->id, 'seo', ['question']);

        $this->assertEquals(0, $count);
    }

    // =========================================================================
    // MAX RESULTS TESTS
    // =========================================================================

    /**
     * Test maxResults limits output.
     */
    public function test_max_results_limits_output(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn(['k1', 'k2', 'k3', 'k4', 'k5']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->times(3)
            ->andReturn(50);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->times(3)
            ->andReturn(new KeywordSuggestion());

        $count = $this->service->generateSuggestions(
            $site->id,
            'seo',
            ['question'],
            maxResults: 3
        );

        $this->assertEquals(3, $count);
    }

    // =========================================================================
    // SOURCE LABEL TESTS
    // =========================================================================

    /**
     * Test correct source label applied.
     */
    public function test_correct_source_label(): void
    {
        $site = Site::factory()->create();

        $this->mockQuestionGenerator
            ->shouldReceive('generate')
            ->andReturn(['question keyword']);

        $this->mockGoogleSuggestFetcher
            ->shouldReceive('fetch')
            ->andReturn(['suggest keyword']);

        $this->mockOpportunityScorer
            ->shouldReceive('calculate')
            ->twice()
            ->andReturn(50);

        $this->mockRepository
            ->shouldReceive('upsertOrCreate')
            ->withArgs(function ($data) {
                static $index = 0;
                $expectedSources = ['question', 'suggest'];
                return $data['source'] === $expectedSources[$index++];
            })
            ->twice()
            ->andReturn(new KeywordSuggestion());

        $this->service->generateSuggestions($site->id, 'seo', ['question', 'suggest']);
    }

    // =========================================================================
    // INTEGRATION TEST (with real dependencies)
    // =========================================================================

    /**
     * Test with real dependencies (integration-style).
     */
    public function test_integration_with_real_dependencies(): void
    {
        // This test uses real dependencies but mocks external services
        $site = Site::factory()->create();

        $realQuestionGenerator = new \App\Services\Fetchers\QuestionGenerator();
        $realOpportunityScorer = new \App\Services\OpportunityScorer();

        $mockGoogleSuggestFetcher = Mockery::mock(GoogleSuggestFetcher::class);
        $mockGoogleSuggestFetcher
            ->shouldReceive('fetch')
            ->andReturn(['best seo tools', 'free seo tools']);

        $mockRepository = Mockery::mock(KeywordSuggestionRepository::class);
        $mockRepository
            ->shouldReceive('upsertOrCreate')
            ->times(9) // 7 from questions + 2 from suggest
            ->andReturn(new KeywordSuggestion());

        $service = new SuggestionService(
            $mockRepository,
            $realQuestionGenerator,
            $mockGoogleSuggestFetcher,
            $realOpportunityScorer
        );

        Log::shouldReceive('info')->once();

        $count = $service->generateSuggestions($site->id, 'seo', ['question', 'suggest']);

        $this->assertEquals(9, $count);
    }
}
