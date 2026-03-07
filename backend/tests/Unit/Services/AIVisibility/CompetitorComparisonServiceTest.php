<?php

namespace Tests\Unit\Services\AIVisibility;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use App\Services\AIVisibility\CompetitorComparisonService;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Unit tests for CompetitorComparisonService.
 *
 * Tests cover:
 * - AC-1: CompetitorComparisonService class created at correct namespace
 * - AC-2: getHeadToHead() method implemented
 * - AC-3: getCompetitorRanking() method implemented
 * - AC-4: getCompetitorMentionDetails() method implemented
 * - AC-5: trackCompetitorTrend() method implemented
 * - AC-6: Performance optimization (caching, single queries)
 * - AC-7: Error handling
 *
 * @see TASK-34-15
 * @see SPEC-034
 */
class CompetitorComparisonServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected CompetitorComparisonService $service;
    protected $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->service = new CompetitorComparisonService($this->mockLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =================================================================
    // AC-1: Class Structure
    // =================================================================

    /** @test CC-001: Class exists at correct namespace */
    public function test_class_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(CompetitorComparisonService::class));
    }

    /** @test CC-002: Class is instantiable */
    public function test_class_is_instantiable(): void
    {
        $service = new CompetitorComparisonService($this->mockLogger);
        $this->assertInstanceOf(CompetitorComparisonService::class, $service);
    }

    /** @test CC-003: Constructor accepts dependencies */
    public function test_constructor_accepts_dependencies(): void
    {
        $service = new CompetitorComparisonService($this->mockLogger);
        $this->assertInstanceOf(CompetitorComparisonService::class, $service);
    }

    /** @test CC-004: Dependency injection support */
    public function test_dependency_injection_support(): void
    {
        $this->app->instance(\Psr\Log\LoggerInterface::class, $this->mockLogger);
        
        $service = $this->app->make(CompetitorComparisonService::class);
        
        $this->assertInstanceOf(CompetitorComparisonService::class, $service);
    }

    /** @test CC-005: Service can be resolved from container */
    public function test_service_can_be_resolved_from_container(): void
    {
        $this->app->bind(CompetitorComparisonService::class, function ($app) {
            return new CompetitorComparisonService($app->make(\Psr\Log\LoggerInterface::class));
        });
        
        $service = $this->app->make(CompetitorComparisonService::class);
        
        $this->assertInstanceOf(CompetitorComparisonService::class, $service);
    }

    // =================================================================
    // AC-2: getHeadToHead() Method
    // =================================================================

    /** @test CC-006: Method exists and is public */
    public function test_get_head_to_head_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getHeadToHead'));
        
        $reflection = new \ReflectionMethod($this->service, 'getHeadToHead');
        $this->assertTrue($reflection->isPublic());
    }

    /** @test CC-007: Accepts projectId and competitorName parameters */
    public function test_get_head_to_head_accepts_parameters(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getHeadToHead');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('projectId', $parameters[0]->getName());
        $this->assertEquals('competitorName', $parameters[1]->getName());
    }

    /** @test CC-008: Returns array with required structure */
    public function test_get_head_to_head_returns_required_structure(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [
                ['name' => 'Ahrefs', 'keywords' => ['ahrefs']],
            ],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'platform' => 'chatgpt',
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('brand', $result);
        $this->assertArrayHasKey('competitor', $result);
        $this->assertArrayHasKey('co_mentions', $result);
        $this->assertArrayHasKey('brand_advantage', $result);
        $this->assertArrayHasKey('competitor_advantage', $result);
    }

    /** @test CC-009: Returns brand object with mentions, rate, sentiment */
    public function test_get_head_to_head_returns_brand_object(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertIsArray($result['brand']);
        $this->assertArrayHasKey('mentions', $result['brand']);
        $this->assertArrayHasKey('rate', $result['brand']);
        $this->assertArrayHasKey('sentiment', $result['brand']);
    }

    /** @test CC-010: Returns competitor object with mentions, rate, sentiment */
    public function test_get_head_to_head_returns_competitor_object(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertIsArray($result['competitor']);
        $this->assertArrayHasKey('mentions', $result['competitor']);
        $this->assertArrayHasKey('rate', $result['competitor']);
        $this->assertArrayHasKey('sentiment', $result['competitor']);
    }

    /** @test CC-011: Returns co_mentions count */
    public function test_get_head_to_head_returns_co_mentions(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'comparison', 'sentiment' => 'positive'],
                ['brand' => 'Ahrefs', 'type' => 'comparison', 'sentiment' => 'neutral'],
            ],
        ]);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertIsInt($result['co_mentions']);
        $this->assertGreaterThanOrEqual(0, $result['co_mentions']);
    }

    /** @test CC-014: Throws exception for non-existent project */
    public function test_get_head_to_head_throws_for_nonexistent_project(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->getHeadToHead(99999, 'Ahrefs');
    }

    /** @test CC-015: Throws exception for non-existent competitor */
    public function test_get_head_to_head_throws_for_nonexistent_competitor(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        $this->expectException(InvalidArgumentException::class);
        
        $this->service->getHeadToHead($project->id, 'NonExistentCompetitor');
    }

    /** @test CC-016: Handles project with no competitors defined */
    public function test_get_head_to_head_handles_no_competitors(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [],
        ]);

        $this->expectException(InvalidArgumentException::class);
        
        $this->service->getHeadToHead($project->id, 'Ahrefs');
    }

    /** @test CC-017: Handles brand with zero mentions */
    public function test_get_head_to_head_handles_zero_brand_mentions(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertEquals(0, $result['brand']['mentions']);
        $this->assertEquals(0.0, $result['brand']['rate']);
    }

    /** @test CC-018: Handles competitor with zero mentions */
    public function test_get_head_to_head_handles_zero_competitor_mentions(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertEquals(0, $result['competitor']['mentions']);
        $this->assertEquals(0.0, $result['competitor']['rate']);
    }

    /** @test CC-019: Handles co-mentions detection */
    public function test_get_head_to_head_detects_co_mentions(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        // 3 results with co-mentions
        for ($i = 0; $i < 3; $i++) {
            AIQueryResult::factory()->create([
                'project_id' => $project->id,
                'success' => true,
                'mentions' => [
                    ['brand' => 'SEOIndicator', 'type' => 'comparison', 'sentiment' => 'positive'],
                    ['brand' => 'Ahrefs', 'type' => 'comparison', 'sentiment' => 'neutral'],
                ],
            ]);
        }

        // 1 result with only brand
        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertEquals(3, $result['co_mentions']);
    }

    /** @test CC-020: Calculates correct mention rates */
    public function test_get_head_to_head_calculates_mention_rates(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        // 10 total queries, 4 with brand mentions
        for ($i = 0; $i < 10; $i++) {
            $mentions = [];
            if ($i < 4) {
                $mentions[] = ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'];
            }
            AIQueryResult::factory()->create([
                'project_id' => $project->id,
                'success' => true,
                'mentions' => $mentions,
            ]);
        }

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertEquals(40.0, $result['brand']['rate']);
    }

    /** @test CC-024: Caches results for 1 hour */
    public function test_get_head_to_head_caches_results(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $cacheKey = "ai_visibility:comparison:{$project->id}:ahrefs";
        
        $result1 = $this->service->getHeadToHead($project->id, 'Ahrefs');
        $result2 = $this->service->getHeadToHead($project->id, 'Ahrefs');
        
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test CC-025: Uses correct cache key format */
    public function test_get_head_to_head_uses_correct_cache_key(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $expectedKey = "ai_visibility:comparison:{$project->id}:ahrefs";
        
        $this->service->getHeadToHead($project->id, 'Ahrefs');
        
        $this->assertTrue(Cache::has($expectedKey));
    }

    // =================================================================
    // AC-3: getCompetitorRanking() Method
    // =================================================================

    /** @test CC-026: Method exists and is public */
    public function test_get_competitor_ranking_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getCompetitorRanking'));
        
        $reflection = new \ReflectionMethod($this->service, 'getCompetitorRanking');
        $this->assertTrue($reflection->isPublic());
    }

    /** @test CC-027: Accepts projectId parameter */
    public function test_get_competitor_ranking_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getCompetitorRanking');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test CC-028: Returns array with rankings and brand_position */
    public function test_get_competitor_ranking_returns_structure(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [
                ['name' => 'Ahrefs', 'keywords' => ['ahrefs']],
                ['name' => 'SEMrush', 'keywords' => ['semrush']],
            ],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getCompetitorRanking($project->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('rankings', $result);
        $this->assertArrayHasKey('brand_position', $result);
    }

    /** @test CC-029: Rankings array contains position, name, rate, trend */
    public function test_get_competitor_ranking_rankings_structure(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getCompetitorRanking($project->id);

        $this->assertIsArray($result['rankings']);
        
        if (!empty($result['rankings'])) {
            $firstRanking = $result['rankings'][0];
            $this->assertArrayHasKey('position', $firstRanking);
            $this->assertArrayHasKey('name', $firstRanking);
            $this->assertArrayHasKey('rate', $firstRanking);
            $this->assertArrayHasKey('trend', $firstRanking);
        }
    }

    /** @test CC-030: Brand position is correctly identified */
    public function test_get_competitor_ranking_identifies_brand_position(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [
                ['name' => 'Ahrefs', 'keywords' => ['ahrefs']],
                ['name' => 'SEMrush', 'keywords' => ['semrush']],
            ],
        ]);

        // Ahrefs has most mentions, then SEMrush, then SEOIndicator
        for ($i = 0; $i < 5; $i++) {
            AIQueryResult::factory()->create([
                'project_id' => $project->id,
                'success' => true,
                'mentions' => [['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive']],
            ]);
        }
        
        for ($i = 0; $i < 3; $i++) {
            AIQueryResult::factory()->create([
                'project_id' => $project->id,
                'success' => true,
                'mentions' => [['brand' => 'SEMrush', 'type' => 'recommendation', 'sentiment' => 'positive']],
            ]);
        }
        
        for ($i = 0; $i < 2; $i++) {
            AIQueryResult::factory()->create([
                'project_id' => $project->id,
                'success' => true,
                'mentions' => [['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive']],
            ]);
        }

        $result = $this->service->getCompetitorRanking($project->id);

        $this->assertEquals(3, $result['brand_position']);
    }

    /** @test CC-031: Sorts by mention rate descending */
    public function test_get_competitor_ranking_sorts_by_rate_descending(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'BrandC',
            'competitors' => [
                ['name' => 'BrandA', 'keywords' => ['a']],
                ['name' => 'BrandB', 'keywords' => ['b']],
            ],
        ]);

        // BrandA: 50%, BrandB: 30%, BrandC: 20%
        for ($i = 0; $i < 10; $i++) {
            $mentions = [];
            if ($i < 5) $mentions[] = ['brand' => 'BrandA', 'type' => 'recommendation', 'sentiment' => 'positive'];
            if ($i < 3) $mentions[] = ['brand' => 'BrandB', 'type' => 'recommendation', 'sentiment' => 'positive'];
            if ($i < 2) $mentions[] = ['brand' => 'BrandC', 'type' => 'recommendation', 'sentiment' => 'positive'];
            
            AIQueryResult::factory()->create([
                'project_id' => $project->id,
                'success' => true,
                'mentions' => $mentions,
            ]);
        }

        $result = $this->service->getCompetitorRanking($project->id);

        $rates = array_column($result['rankings'], 'rate');
        $sortedRates = $rates;
        rsort($sortedRates);
        
        $this->assertEquals($sortedRates, $rates);
    }

    /** @test CC-033: Trend threshold stable */
    public function test_get_competitor_ranking_trend_stable(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIMentionAnalytics::factory()->create([
            'project_id' => $project->id,
            'brand_mentions' => 10,
            'mention_rate' => 25.0,
            'period_start' => now()->subDays(14),
            'period_end' => now()->subDays(7),
        ]);
        
        AIMentionAnalytics::factory()->create([
            'project_id' => $project->id,
            'brand_mentions' => 10,
            'mention_rate' => 26.0,
            'period_start' => now()->subDays(7),
            'period_end' => now(),
        ]);

        $result = $this->service->getCompetitorRanking($project->id);

        $brandRanking = collect($result['rankings'])->firstWhere('name', 'SEOIndicator');
        
        $this->assertEquals('stable', $brandRanking['trend']);
    }

    /** @test CC-036: Handles project with no competitors */
    public function test_get_competitor_ranking_handles_no_competitors(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getCompetitorRanking($project->id);

        $this->assertCount(1, $result['rankings']);
        $this->assertEquals('SEOIndicator', $result['rankings'][0]['name']);
        $this->assertEquals(1, $result['brand_position']);
    }

    /** @test CC-037: Handles project with no query results */
    public function test_get_competitor_ranking_handles_no_results(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        $result = $this->service->getCompetitorRanking($project->id);

        foreach ($result['rankings'] as $ranking) {
            $this->assertEquals(0.0, $ranking['rate']);
        }
    }

    /** @test CC-041: Caches results for 1 hour */
    public function test_get_competitor_ranking_caches_results(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $cacheKey = "ai_visibility:ranking:{$project->id}";
        
        $result1 = $this->service->getCompetitorRanking($project->id);
        $result2 = $this->service->getCompetitorRanking($project->id);
        
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has($cacheKey));
    }

    // =================================================================
    // AC-4: getCompetitorMentionDetails() Method
    // =================================================================

    /** @test CC-042: Method exists and is public */
    public function test_get_competitor_mention_details_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getCompetitorMentionDetails'));
        
        $reflection = new \ReflectionMethod($this->service, 'getCompetitorMentionDetails');
        $this->assertTrue($reflection->isPublic());
    }

    /** @test CC-043: Accepts projectId and competitorName parameters */
    public function test_get_competitor_mention_details_accepts_parameters(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getCompetitorMentionDetails');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('projectId', $parameters[0]->getName());
        $this->assertEquals('competitorName', $parameters[1]->getName());
    }

    /** @test CC-044: Returns array with required structure */
    public function test_get_competitor_mention_details_returns_structure(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'response_text' => 'Ahrefs is great for backlink analysis.',
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive', 'context' => 'Ahrefs is great for backlink analysis'],
            ],
        ]);

        $result = $this->service->getCompetitorMentionDetails($project->id, 'Ahrefs');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('query_categories', $result);
        $this->assertArrayHasKey('context_snippets', $result);
        $this->assertArrayHasKey('sentiment_breakdown', $result);
        $this->assertArrayHasKey('platforms', $result);
    }

    /** @test CC-047: Returns sentiment_breakdown object */
    public function test_get_competitor_mention_details_returns_sentiment(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
                ['brand' => 'Ahrefs', 'type' => 'comparison', 'sentiment' => 'neutral'],
                ['brand' => 'Ahrefs', 'type' => 'review', 'sentiment' => 'negative'],
            ],
        ]);

        $result = $this->service->getCompetitorMentionDetails($project->id, 'Ahrefs');

        $this->assertIsArray($result['sentiment_breakdown']);
        $this->assertArrayHasKey('positive', $result['sentiment_breakdown']);
        $this->assertArrayHasKey('neutral', $result['sentiment_breakdown']);
        $this->assertArrayHasKey('negative', $result['sentiment_breakdown']);
        
        $this->assertEquals(1, $result['sentiment_breakdown']['positive']);
        $this->assertEquals(1, $result['sentiment_breakdown']['neutral']);
        $this->assertEquals(1, $result['sentiment_breakdown']['negative']);
    }

    /** @test CC-048: Returns platforms array */
    public function test_get_competitor_mention_details_returns_platforms(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'platform' => 'chatgpt',
            'success' => true,
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'platform' => 'perplexity',
            'success' => true,
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'comparison', 'sentiment' => 'neutral'],
            ],
        ]);

        $result = $this->service->getCompetitorMentionDetails($project->id, 'Ahrefs');

        $this->assertIsArray($result['platforms']);
        $this->assertContains('chatgpt', $result['platforms']);
        $this->assertContains('perplexity', $result['platforms']);
    }

    /** @test CC-053: Handles competitor with no mentions */
    public function test_get_competitor_mention_details_handles_no_mentions(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEMrush', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getCompetitorMentionDetails($project->id, 'Ahrefs');

        $this->assertEmpty($result['query_categories']);
        $this->assertEmpty($result['context_snippets']);
        $this->assertEquals(0, $result['sentiment_breakdown']['positive']);
        $this->assertEmpty($result['platforms']);
    }

    /** @test CC-056: Handles invalid competitor name */
    public function test_get_competitor_mention_details_invalid_competitor(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        $this->expectException(InvalidArgumentException::class);
        
        $this->service->getCompetitorMentionDetails($project->id, 'NonExistentCompetitor');
    }

    /** @test CC-057: Handles project not found */
    public function test_get_competitor_mention_details_project_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->getCompetitorMentionDetails(99999, 'Ahrefs');
    }

    /** @test CC-058: Caches results for 1 hour */
    public function test_get_competitor_mention_details_caches_results(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $cacheKey = "ai_visibility:details:{$project->id}:ahrefs";
        
        $result1 = $this->service->getCompetitorMentionDetails($project->id, 'Ahrefs');
        $result2 = $this->service->getCompetitorMentionDetails($project->id, 'Ahrefs');
        
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has($cacheKey));
    }

    // =================================================================
    // AC-5: trackCompetitorTrend() Method
    // =================================================================

    /** @test CC-060: Method exists and is public */
    public function test_track_competitor_trend_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'trackCompetitorTrend'));
        
        $reflection = new \ReflectionMethod($this->service, 'trackCompetitorTrend');
        $this->assertTrue($reflection->isPublic());
    }

    /** @test CC-061: Accepts projectId, competitorName, period parameters */
    public function test_track_competitor_trend_accepts_parameters(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'trackCompetitorTrend');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(3, $parameters);
        $this->assertEquals('projectId', $parameters[0]->getName());
        $this->assertEquals('competitorName', $parameters[1]->getName());
        $this->assertEquals('period', $parameters[2]->getName());
    }

    /** @test CC-062: Returns array of data points */
    public function test_track_competitor_trend_returns_array(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'queried_at' => now()->subDay(),
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->trackCompetitorTrend($project->id, 'Ahrefs', 'week');

        $this->assertIsArray($result);
    }

    /** @test CC-063: Each data point has date, mentions, rate */
    public function test_track_competitor_trend_data_point_structure(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'queried_at' => now()->subDay(),
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->trackCompetitorTrend($project->id, 'Ahrefs', 'week');

        if (!empty($result)) {
            $firstPoint = $result[0];
            $this->assertArrayHasKey('date', $firstPoint);
            $this->assertArrayHasKey('mentions', $firstPoint);
            $this->assertArrayHasKey('rate', $firstPoint);
        }
    }

    /** @test CC-064: Supports 'week' period (7 data points) */
    public function test_track_competitor_trend_week_period(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        for ($i = 6; $i >= 0; $i--) {
            AIQueryResult::factory()->create([
                'project_id' => $project->id,
                'success' => true,
                'queried_at' => now()->subDays($i),
                'mentions' => [
                    ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
                ],
            ]);
        }

        $result = $this->service->trackCompetitorTrend($project->id, 'Ahrefs', 'week');

        $this->assertCount(7, $result);
    }

    /** @test CC-065: Supports 'month' period (30 data points) */
    public function test_track_competitor_trend_month_period(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        for ($i = 29; $i >= 0; $i--) {
            AIQueryResult::factory()->create([
                'project_id' => $project->id,
                'success' => true,
                'queried_at' => now()->subDays($i),
                'mentions' => [
                    ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
                ],
            ]);
        }

        $result = $this->service->trackCompetitorTrend($project->id, 'Ahrefs', 'month');

        $this->assertCount(30, $result);
    }

    /** @test CC-066: Handles empty analytics for period */
    public function test_track_competitor_trend_handles_empty_analytics(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        $result = $this->service->trackCompetitorTrend($project->id, 'Ahrefs', 'week');

        $this->assertCount(7, $result);
        
        foreach ($result as $point) {
            $this->assertEquals(0, $point['mentions']);
            $this->assertEquals(0.0, $point['rate']);
        }
    }

    /** @test CC-071: Handles invalid period parameter */
    public function test_track_competitor_trend_invalid_period(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        $this->expectException(InvalidArgumentException::class);
        
        $this->service->trackCompetitorTrend($project->id, 'Ahrefs', 'invalid_period');
    }

    /** @test CC-072: Handles project not found */
    public function test_track_competitor_trend_project_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->trackCompetitorTrend(99999, 'Ahrefs', 'week');
    }

    /** @test CC-073: Caches results for 1 hour */
    public function test_track_competitor_trend_caches_results(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'queried_at' => now()->subDay(),
            'mentions' => [
                ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $cacheKey = "ai_visibility:trend:{$project->id}:ahrefs:week";
        
        $result1 = $this->service->trackCompetitorTrend($project->id, 'Ahrefs', 'week');
        $result2 = $this->service->trackCompetitorTrend($project->id, 'Ahrefs', 'week');
        
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has($cacheKey));
    }

    // =================================================================
    // AC-6: Performance Optimization
    // =================================================================

    /** @test CC-074: Uses single database query for comparison data */
    public function test_uses_single_query_for_comparison(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        for ($i = 0; $i < 10; $i++) {
            AIQueryResult::factory()->create([
                'project_id' => $project->id,
                'success' => true,
                'mentions' => [
                    ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
                    ['brand' => 'Ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'],
                ],
            ]);
        }

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertLessThanOrEqual(3, count($queries), 'Should use minimal database queries');
    }

    /** @test CC-080: Returns cached results when available */
    public function test_returns_cached_results(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'SEOIndicator', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $cachedData = [
            'brand' => ['mentions' => 99, 'rate' => 99.9, 'sentiment' => 0.99],
            'competitor' => ['mentions' => 50, 'rate' => 50.0, 'sentiment' => 0.5],
            'co_mentions' => 25,
            'brand_advantage' => ['test'],
            'competitor_advantage' => [],
        ];

        Cache::put("ai_visibility:comparison:{$project->id}:ahrefs", $cachedData, 3600);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertEquals($cachedData, $result);
    }

    // =================================================================
    // AC-7: Error Handling
    // =================================================================

    /** @test CC-082: Throws ModelNotFoundException for non-existent project */
    public function test_throws_model_not_found_for_nonexistent_project(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->getHeadToHead(99999, 'Ahrefs');
    }

    /** @test CC-083: Throws InvalidArgumentException for invalid competitor */
    public function test_throws_invalid_argument_for_invalid_competitor(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        $this->expectException(InvalidArgumentException::class);
        
        $this->service->getHeadToHead($project->id, 'InvalidCompetitor');
    }

    /** @test CC-084: Throws InvalidArgumentException for invalid period */
    public function test_throws_invalid_argument_for_invalid_period(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        $this->expectException(InvalidArgumentException::class);
        
        $this->service->trackCompetitorTrend($project->id, 'Ahrefs', 'invalid');
    }

    /** @test CC-086: Returns empty arrays for no data (not exceptions) */
    public function test_returns_empty_arrays_for_no_data(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['brand']['mentions']);
        $this->assertEquals(0, $result['competitor']['mentions']);
    }

    // =================================================================
    // AC-8: Edge Cases
    // =================================================================

    /** @test CC-088: Handles competitor name with special characters */
    public function test_handles_special_characters_in_name(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs-Pro', 'keywords' => ['ahrefs-pro']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'Ahrefs-Pro', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getHeadToHead($project->id, 'Ahrefs-Pro');

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, $result['competitor']['mentions']);
    }

    /** @test CC-091: Handles unicode in competitor names */
    public function test_handles_unicode_in_competitor_names(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => '分析工具', 'keywords' => ['分析']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => '分析工具', 'type' => 'recommendation', 'sentiment' => 'positive'],
            ],
        ]);

        $result = $this->service->getHeadToHead($project->id, '分析工具');

        $this->assertIsArray($result);
    }

    /** @test CC-092: Handles case-insensitive competitor matching */
    public function test_handles_case_insensitive_matching(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'brand_name' => 'SEOIndicator',
            'competitors' => [['name' => 'Ahrefs', 'keywords' => ['ahrefs']]],
        ]);

        AIQueryResult::factory()->create([
            'project_id' => $project->id,
            'success' => true,
            'mentions' => [
                ['brand' => 'ahrefs', 'type' => 'recommendation', 'sentiment' => 'positive'], // lowercase
            ],
        ]);

        // Should work with different case
        $result = $this->service->getHeadToHead($project->id, 'Ahrefs');

        $this->assertGreaterThanOrEqual(1, $result['competitor']['mentions']);
    }
}
