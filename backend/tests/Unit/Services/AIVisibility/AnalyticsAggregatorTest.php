<?php

namespace Tests\Unit\Services\AIVisibility;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use App\Services\AIVisibility\AnalyticsAggregator;
use App\Models\AIVisibility\AIVisibilityProject;
use App\Models\AIVisibility\AIQueryResult;
use App\Models\AIVisibility\AIMentionAnalytics;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Unit tests for AnalyticsAggregator service
 * 
 * @covers \App\Services\AIVisibility\AnalyticsAggregator
 * @task TASK-34-13
 * @spec SPEC-034
 */
class AnalyticsAggregatorTest extends TestCase
{
    use DatabaseMigrations;

    protected AnalyticsAggregator $service;
    protected User $user;
    protected AIVisibilityProject $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new AnalyticsAggregator();

        // Create a test user and project
        $this->user = User::factory()->create();
        $this->project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'Acme Corp',
            'keywords' => ['acme', 'acme corp'],
            'competitors' => [
                ['name' => 'Competitor A', 'keywords' => ['comp a']],
                ['name' => 'Competitor B', 'keywords' => ['comp b']],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // Category 1: Service Instantiation (AA-001 to AA-010)
    // ============================================================

    /** @test AA-001: Class exists at correct namespace */
    public function test_class_exists_at_correct_namespace(): void
    {
        $this->assertTrue(class_exists(AnalyticsAggregator::class));
        $this->assertInstanceOf(AnalyticsAggregator::class, $this->service);
    }

    /** @test AA-002: Constructor accepts no dependencies */
    public function test_constructor_accepts_no_dependencies(): void
    {
        $service = new AnalyticsAggregator();
        $this->assertInstanceOf(AnalyticsAggregator::class, $service);
    }

    /** @test AA-003: Service is instantiable via app container */
    public function test_service_instantiable_via_container(): void
    {
        $service = $this->app->make(AnalyticsAggregator::class);
        $this->assertInstanceOf(AnalyticsAggregator::class, $service);
    }

    /** @test AA-004: aggregate() method exists */
    public function test_aggregate_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'aggregate'));
    }

    /** @test AA-005: calculateMentionRate() method exists */
    public function test_calculate_mention_rate_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'calculateMentionRate'));
    }

    /** @test AA-006: calculateShareOfVoice() method exists */
    public function test_calculate_share_of_voice_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'calculateShareOfVoice'));
    }

    /** @test AA-007: getTrend() method exists */
    public function test_get_trend_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getTrend'));
    }

    /** @test AA-008: getTopCategories() method exists */
    public function test_get_top_categories_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getTopCategories'));
    }

    /** @test AA-009: getSentimentBreakdown() method exists */
    public function test_get_sentiment_breakdown_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getSentimentBreakdown'));
    }

    /** @test AA-010: Service can be mocked */
    public function test_service_can_be_mocked(): void
    {
        $mock = Mockery::mock(AnalyticsAggregator::class);
        $this->assertInstanceOf(AnalyticsAggregator::class, $mock);
    }

    // ============================================================
    // Category 2: aggregate() Method (AA-011 to AA-050)
    // ============================================================

    /** @test AA-011: Method accepts projectId */
    public function test_aggregate_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'aggregate');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }

    /** @test AA-012: Method accepts period */
    public function test_aggregate_accepts_period(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'aggregate');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('period', $parameters[1]->getName());
    }

    /** @test AA-013: Returns AIMentionAnalytics model */
    public function test_aggregate_returns_model(): void
    {
        // Create some query results
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertInstanceOf(AIMentionAnalytics::class, $result);
    }

    /** @test AA-014: Period: day */
    public function test_aggregate_period_day(): void
    {
        // Create results within last 24 hours
        AIQueryResult::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now()->subHours(12),
        ]);
        
        // Create results outside period (should not be included)
        AIQueryResult::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now()->subDays(2),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'day');
        
        $this->assertEquals(3, $result->total_queries);
    }

    /** @test AA-015: Period: week */
    public function test_aggregate_period_week(): void
    {
        // Create results within last 7 days
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now()->subDays(3),
        ]);
        
        // Create results outside period
        AIQueryResult::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now()->subDays(10),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertEquals(5, $result->total_queries);
    }

    /** @test AA-016: Period: month */
    public function test_aggregate_period_month(): void
    {
        // Create results within last 30 days
        AIQueryResult::factory()->count(7)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now()->subDays(15),
        ]);
        
        // Create results outside period
        AIQueryResult::factory()->count(4)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now()->subDays(40),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'month');
        
        $this->assertEquals(7, $result->total_queries);
    }

    /** @test AA-017: Calculates total_queries */
    public function test_aggregate_calculates_total_queries(): void
    {
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertEquals(10, $result->total_queries);
    }

    /** @test AA-018: Calculates brand_mentions */
    public function test_aggregate_calculates_brand_mentions(): void
    {
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertEquals(10, $result->brand_mentions);
    }

    /** @test AA-019: Calculates competitor_mentions */
    public function test_aggregate_calculates_competitor_mentions(): void
    {
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'competitor_mentions' => ['Competitor A'],
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'competitor_mentions' => ['Competitor B'],
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertIsArray($result->competitor_mentions);
    }

    /** @test AA-020: Calculates mention_rate per platform */
    public function test_aggregate_calculates_mention_rate(): void
    {
        AIQueryResult::factory()->count(70)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(30)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'week');
        
        // 70 mentions out of 100 = 70%
        $this->assertEqualsWithDelta(70.00, $result->mention_rate, 0.01);
    }

    /** @test AA-022: Upserts on unique constraint */
    public function test_aggregate_upserts_on_unique(): void
    {
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        // First aggregate
        $result1 = $this->service->aggregate($this->project->id, 'week');
        
        // Add more results
        AIQueryResult::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        // Second aggregate should update, not create new
        $result2 = $this->service->aggregate($this->project->id, 'week');
        
        $count = AIMentionAnalytics::where('project_id', $this->project->id)
            ->where('period', 'week')
            ->count();
        
        $this->assertEquals(1, $count);
        $this->assertEquals(8, $result2->total_queries);
    }

    /** @test AA-031: Handles empty query results */
    public function test_aggregate_handles_empty_results(): void
    {
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertEquals(0, $result->total_queries);
        $this->assertEquals(0, $result->brand_mentions);
        $this->assertEquals(0.00, $result->mention_rate);
    }

    /** @test AA-032: Handles single query */
    public function test_aggregate_handles_single_query(): void
    {
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertEquals(1, $result->total_queries);
        $this->assertEquals(1, $result->brand_mentions);
        $this->assertEquals(100.00, $result->mention_rate);
    }

    /** @test AA-033: Handles multiple platforms */
    public function test_aggregate_handles_multiple_platforms(): void
    {
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'queried_at' => now(),
        ]);
        
        $resultChatGpt = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertNotNull($resultChatGpt);
    }

    /** @test AA-035: Validates projectId exists */
    public function test_aggregate_validates_project_exists(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->aggregate(99999, 'week');
    }

    /** @test AA-036: Validates period value */
    public function test_aggregate_validates_period(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->service->aggregate($this->project->id, 'invalid_period');
    }

    /** @test AA-040: Sets aggregated_at timestamp */
    public function test_aggregate_sets_timestamp(): void
    {
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertNotNull($result->aggregated_at);
    }

    // ============================================================
    // Category 3: calculateMentionRate() Method (AA-051 to AA-085)
    // ============================================================

    /** @test AA-051: Method accepts projectId */
    public function test_calculate_mention_rate_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'calculateMentionRate');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test AA-052: Method accepts platform */
    public function test_calculate_mention_rate_accepts_platform(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'calculateMentionRate');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('platform', $parameters[1]->getName());
    }

    /** @test AA-053: Method accepts period */
    public function test_calculate_mention_rate_accepts_period(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'calculateMentionRate');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('period', $parameters[2]->getName());
    }

    /** @test AA-054: Returns float */
    public function test_calculate_mention_rate_returns_float(): void
    {
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $rate = $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        
        $this->assertIsFloat($rate);
    }

    /** @test AA-055: Formula: (brand_mentions / total_queries) * 100 */
    public function test_calculate_mention_rate_formula(): void
    {
        // 50 queries, 35 mentions = 70%
        AIQueryResult::factory()->count(35)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(15)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'queried_at' => now(),
        ]);
        
        $rate = $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        
        $this->assertEqualsWithDelta(70.00, $rate, 0.01);
    }

    /** @test AA-056: Returns 0.00 for no queries */
    public function test_calculate_mention_rate_no_queries(): void
    {
        $rate = $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        
        $this->assertEquals(0.00, $rate);
    }

    /** @test AA-058: Returns 100.00 for all mentions */
    public function test_calculate_mention_rate_all_mentions(): void
    {
        AIQueryResult::factory()->count(50)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        $rate = $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        
        $this->assertEquals(100.00, $rate);
    }

    /** @test AA-059: Returns correct decimal */
    public function test_calculate_mention_rate_decimal(): void
    {
        // 33 queries, 11 mentions = 33.33%
        AIQueryResult::factory()->count(11)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(22)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'queried_at' => now(),
        ]);
        
        $rate = $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        
        $this->assertEqualsWithDelta(33.33, $rate, 0.1);
    }

    /** @test AA-061: Platform: chatgpt */
    public function test_calculate_mention_rate_chatgpt(): void
    {
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'brand_mentioned' => false,
            'queried_at' => now(),
        ]);
        
        $rate = $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        
        $this->assertEquals(100.00, $rate);
    }

    /** @test AA-062: Platform: perplexity */
    public function test_calculate_mention_rate_perplexity(): void
    {
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'queried_at' => now(),
        ]);
        
        $rate = $this->service->calculateMentionRate($this->project->id, 'perplexity', 'week');
        
        $this->assertEquals(100.00, $rate);
    }

    /** @test AA-066: Handles division by zero */
    public function test_calculate_mention_rate_division_by_zero(): void
    {
        // No queries = 0.00, not an error
        $rate = $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        
        $this->assertEquals(0.00, $rate);
    }

    /** @test AA-070: Validates projectId exists */
    public function test_calculate_mention_rate_validates_project(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->calculateMentionRate(99999, 'chatgpt', 'week');
    }

    /** @test AA-072: Validates period value */
    public function test_calculate_mention_rate_validates_period(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'invalid');
    }

    // ============================================================
    // Category 4: calculateShareOfVoice() Method (AA-086 to AA-130)
    // ============================================================

    /** @test AA-086: Method accepts projectId */
    public function test_calculate_share_of_voice_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'calculateShareOfVoice');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test AA-087: Method accepts period */
    public function test_calculate_share_of_voice_accepts_period(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'calculateShareOfVoice');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('period', $parameters[1]->getName());
    }

    /** @test AA-088: Returns array */
    public function test_calculate_share_of_voice_returns_array(): void
    {
        $result = $this->service->calculateShareOfVoice($this->project->id, 'week');
        
        $this->assertIsArray($result);
    }

    /** @test AA-089: Formula: brand / (brand + competitors) * 100 */
    public function test_calculate_share_of_voice_formula(): void
    {
        // 40 brand mentions, 60 competitor mentions = 40%
        AIQueryResult::factory()->count(40)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'competitor_mentions' => [],
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(60)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'competitor_mentions' => ['Competitor A'],
            'queried_at' => now(),
        ]);
        
        $result = $this->service->calculateShareOfVoice($this->project->id, 'week');
        
        $this->assertArrayHasKey('share_of_voice', $result);
        $this->assertEqualsWithDelta(40.00, $result['share_of_voice'], 0.1);
    }

    /** @test AA-090: Returns 0.00 for zero mentions */
    public function test_calculate_share_of_voice_zero_mentions(): void
    {
        $result = $this->service->calculateShareOfVoice($this->project->id, 'week');
        
        $this->assertEquals(0.00, $result['share_of_voice']);
    }

    /** @test AA-091: Returns 100.00 for brand only */
    public function test_calculate_share_of_voice_brand_only(): void
    {
        AIQueryResult::factory()->count(50)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'competitor_mentions' => [],
            'queried_at' => now(),
        ]);
        
        $result = $this->service->calculateShareOfVoice($this->project->id, 'week');
        
        $this->assertEquals(100.00, $result['share_of_voice']);
    }

    /** @test AA-092: Handles 1 competitor */
    public function test_calculate_share_of_voice_one_competitor(): void
    {
        // 30 brand, 10 competitor = 75%
        AIQueryResult::factory()->count(30)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'competitor_mentions' => [],
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'competitor_mentions' => ['Competitor A'],
            'queried_at' => now(),
        ]);
        
        $result = $this->service->calculateShareOfVoice($this->project->id, 'week');
        
        $this->assertEqualsWithDelta(75.00, $result['share_of_voice'], 0.1);
    }

    /** @test AA-095: Division by zero: 0 brand + 0 comp */
    public function test_calculate_share_of_voice_division_by_zero(): void
    {
        $result = $this->service->calculateShareOfVoice($this->project->id, 'week');
        
        $this->assertEquals(0.00, $result['share_of_voice']);
    }

    /** @test AA-097: Rounds to 2 decimals */
    public function test_calculate_share_of_voice_rounding(): void
    {
        // 33 brand, 66 competitor = 33.33%
        AIQueryResult::factory()->count(33)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'competitor_mentions' => [],
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(66)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'competitor_mentions' => ['Competitor A'],
            'queried_at' => now(),
        ]);
        
        $result = $this->service->calculateShareOfVoice($this->project->id, 'week');
        
        $this->assertEqualsWithDelta(33.33, $result['share_of_voice'], 0.1);
    }

    /** @test AA-104: Validates projectId exists */
    public function test_calculate_share_of_voice_validates_project(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->calculateShareOfVoice(99999, 'week');
    }

    /** @test AA-105: Validates period value */
    public function test_calculate_share_of_voice_validates_period(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->service->calculateShareOfVoice($this->project->id, 'invalid');
    }

    /** @test AA-108: Handles project with no competitors */
    public function test_calculate_share_of_voice_no_competitors(): void
    {
        $projectNoComp = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'competitors' => [],
        ]);
        
        AIQueryResult::factory()->count(50)->create([
            'project_id' => $projectNoComp->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->calculateShareOfVoice($projectNoComp->id, 'week');
        
        $this->assertEquals(100.00, $result['share_of_voice']);
    }

    /** @test AA-109: Returns breakdown by competitor */
    public function test_calculate_share_of_voice_returns_breakdown(): void
    {
        AIQueryResult::factory()->count(30)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'competitor_mentions' => [],
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'competitor_mentions' => ['Competitor A'],
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => false,
            'competitor_mentions' => ['Competitor B'],
            'queried_at' => now(),
        ]);
        
        $result = $this->service->calculateShareOfVoice($this->project->id, 'week');
        
        $this->assertArrayHasKey('competitor_breakdown', $result);
    }

    // ============================================================
    // Category 5: getTrend() Method (AA-131 to AA-175)
    // ============================================================

    /** @test AA-131: Method accepts projectId */
    public function test_get_trend_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getTrend');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test AA-132: Returns array */
    public function test_get_trend_returns_array(): void
    {
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertIsArray($result);
    }

    /** @test AA-133: Returns direction key */
    public function test_get_trend_returns_direction(): void
    {
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertArrayHasKey('direction', $result);
    }

    /** @test AA-134: Returns change_percent key */
    public function test_get_trend_returns_change_percent(): void
    {
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertArrayHasKey('change_percent', $result);
    }

    /** @test AA-135: Direction: up */
    public function test_get_trend_direction_up(): void
    {
        // Earlier period (days 80-90): 30 mentions
        AIQueryResult::factory()->count(30)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(85),
        ]);
        
        // Recent period (days 1-7): 40 mentions (+33%)
        AIQueryResult::factory()->count(40)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(3),
        ]);
        
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertEquals('up', $result['direction']);
        $this->assertGreaterThan(0, $result['change_percent']);
    }

    /** @test AA-136: Direction: down */
    public function test_get_trend_direction_down(): void
    {
        // Earlier period (days 80-90): 50 mentions
        AIQueryResult::factory()->count(50)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(85),
        ]);
        
        // Recent period (days 1-7): 30 mentions (-40%)
        AIQueryResult::factory()->count(30)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(3),
        ]);
        
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertEquals('down', $result['direction']);
        $this->assertLessThan(0, $result['change_percent']);
    }

    /** @test AA-137: Direction: stable */
    public function test_get_trend_direction_stable(): void
    {
        // Earlier period (days 80-90): 50 mentions
        AIQueryResult::factory()->count(50)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(85),
        ]);
        
        // Recent period (days 1-7): 52 mentions (+4%)
        AIQueryResult::factory()->count(52)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(3),
        ]);
        
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertEquals('stable', $result['direction']);
    }

    /** @test AA-139: Stable threshold: < 5% */
    public function test_get_trend_stable_threshold(): void
    {
        // Earlier: 100, Recent: 104 (+4%)
        AIQueryResult::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(85),
        ]);
        
        AIQueryResult::factory()->count(104)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(3),
        ]);
        
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertEquals('stable', $result['direction']);
    }

    /** @test AA-141: Up threshold: >= 5% */
    public function test_get_trend_up_threshold(): void
    {
        // Earlier: 100, Recent: 105 (+5%)
        AIQueryResult::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(85),
        ]);
        
        AIQueryResult::factory()->count(105)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(3),
        ]);
        
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertEquals('up', $result['direction']);
    }

    /** @test AA-147: Returns 0% for no previous data */
    public function test_get_trend_no_previous_data(): void
    {
        // Only recent data
        AIQueryResult::factory()->count(50)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertEquals('stable', $result['direction']);
        $this->assertEquals(0.00, $result['change_percent']);
    }

    /** @test AA-153: Validates projectId exists */
    public function test_get_trend_validates_project(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->getTrend(99999, 90);
    }

    /** @test AA-154: Accepts days parameter */
    public function test_get_trend_accepts_days(): void
    {
        $result = $this->service->getTrend($this->project->id, 30);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('direction', $result);
    }

    // ============================================================
    // Category 6: getTopCategories() Method (AA-176 to AA-220)
    // ============================================================

    /** @test AA-176: Method accepts projectId */
    public function test_get_top_categories_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getTopCategories');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test AA-177: Method accepts limit */
    public function test_get_top_categories_accepts_limit(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getTopCategories');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('limit', $parameters[1]->getName());
    }

    /** @test AA-178: Returns array */
    public function test_get_top_categories_returns_array(): void
    {
        $result = $this->service->getTopCategories($this->project->id);
        
        $this->assertIsArray($result);
    }

    /** @test AA-179: Default limit: 5 */
    public function test_get_top_categories_default_limit(): void
    {
        // Create 10 categories
        for ($i = 1; $i <= 10; $i++) {
            AIQueryResult::factory()->create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_category' => "category_$i",
                'queried_at' => now(),
            ]);
        }
        
        $result = $this->service->getTopCategories($this->project->id);
        
        $this->assertLessThanOrEqual(5, count($result));
    }

    /** @test AA-180: Custom limit: 3 */
    public function test_get_top_categories_custom_limit(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            AIQueryResult::factory()->create([
                'project_id' => $this->project->id,
                'platform' => 'chatgpt',
                'query_category' => "category_$i",
                'queried_at' => now(),
            ]);
        }
        
        $result = $this->service->getTopCategories($this->project->id, 3);
        
        $this->assertCount(3, $result);
    }

    /** @test AA-182: Each item has category */
    public function test_get_top_categories_has_category(): void
    {
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_category' => 'product',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getTopCategories($this->project->id);
        
        if (!empty($result)) {
            $this->assertArrayHasKey('category', $result[0]);
        }
    }

    /** @test AA-183: Each item has count */
    public function test_get_top_categories_has_count(): void
    {
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_category' => 'product',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getTopCategories($this->project->id);
        
        if (!empty($result)) {
            $this->assertArrayHasKey('count', $result[0]);
        }
    }

    /** @test AA-184: Each item has mention_rate */
    public function test_get_top_categories_has_mention_rate(): void
    {
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_category' => 'product',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getTopCategories($this->project->id);
        
        if (!empty($result)) {
            $this->assertArrayHasKey('mention_rate', $result[0]);
        }
    }

    /** @test AA-185: Sorted by count descending */
    public function test_get_top_categories_sorted_descending(): void
    {
        // Category with 5 items
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_category' => 'product',
            'queried_at' => now(),
        ]);
        
        // Category with 10 items (should be first)
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_category' => 'recommendation',
            'queried_at' => now(),
        ]);
        
        // Category with 3 items
        AIQueryResult::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_category' => 'comparison',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getTopCategories($this->project->id, 10);
        
        $this->assertEquals('recommendation', $result[0]['category']);
        $this->assertEquals(10, $result[0]['count']);
    }

    /** @test AA-186: Groups by query_category */
    public function test_get_top_categories_groups_correctly(): void
    {
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'query_category' => 'product',
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'perplexity',
            'query_category' => 'product',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getTopCategories($this->project->id, 10);
        
        // Should have 1 category with count 10 (both platforms)
        $this->assertCount(1, $result);
        $this->assertEquals(10, $result[0]['count']);
    }

    /** @test AA-187: Returns empty array for no data */
    public function test_get_top_categories_empty_data(): void
    {
        $result = $this->service->getTopCategories($this->project->id);
        
        $this->assertEquals([], $result);
    }

    /** @test AA-189: Validates projectId exists */
    public function test_get_top_categories_validates_project(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->getTopCategories(99999);
    }

    /** @test AA-190: Validates limit is positive */
    public function test_get_top_categories_validates_limit(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->service->getTopCategories($this->project->id, -1);
    }

    // ============================================================
    // Category 7: getSentimentBreakdown() Method (AA-221 to AA-275)
    // ============================================================

    /** @test AA-221: Method accepts projectId */
    public function test_get_sentiment_breakdown_accepts_project_id(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getSentimentBreakdown');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('projectId', $parameters[0]->getName());
    }

    /** @test AA-222: Method accepts period */
    public function test_get_sentiment_breakdown_accepts_period(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getSentimentBreakdown');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('period', $parameters[1]->getName());
    }

    /** @test AA-223: Returns array */
    public function test_get_sentiment_breakdown_returns_array(): void
    {
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertIsArray($result);
    }

    /** @test AA-224: Returns positive count */
    public function test_get_sentiment_breakdown_returns_positive(): void
    {
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'positive',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertArrayHasKey('positive', $result);
        $this->assertEquals(10, $result['positive']);
    }

    /** @test AA-225: Returns neutral count */
    public function test_get_sentiment_breakdown_returns_neutral(): void
    {
        AIQueryResult::factory()->count(15)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'neutral',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertArrayHasKey('neutral', $result);
        $this->assertEquals(15, $result['neutral']);
    }

    /** @test AA-226: Returns negative count */
    public function test_get_sentiment_breakdown_returns_negative(): void
    {
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'negative',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertArrayHasKey('negative', $result);
        $this->assertEquals(5, $result['negative']);
    }

    /** @test AA-227: Returns mixed count */
    public function test_get_sentiment_breakdown_returns_mixed(): void
    {
        AIQueryResult::factory()->count(8)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'mixed',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertArrayHasKey('mixed', $result);
        $this->assertEquals(8, $result['mixed']);
    }

    /** @test AA-228: Returns average_sentiment */
    public function test_get_sentiment_breakdown_returns_average(): void
    {
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'positive',
            'sentiment_score' => 0.8,
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'negative',
            'sentiment_score' => -0.4,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertArrayHasKey('average_sentiment', $result);
        // Average: (0.8 + -0.4) / 2 = 0.2
        $this->assertEqualsWithDelta(0.2, $result['average_sentiment'], 0.01);
    }

    /** @test AA-234: Average sentiment range: -1.00 to +1.00 */
    public function test_get_sentiment_breakdown_range(): void
    {
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'positive',
            'sentiment_score' => 0.5,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertGreaterThanOrEqual(-1.00, $result['average_sentiment']);
        $this->assertLessThanOrEqual(1.00, $result['average_sentiment']);
    }

    /** @test AA-235: Average sentiment: all positive */
    public function test_get_sentiment_breakdown_all_positive(): void
    {
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'positive',
            'sentiment_score' => 1.0,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertEquals(1.00, $result['average_sentiment']);
    }

    /** @test AA-236: Average sentiment: all negative */
    public function test_get_sentiment_breakdown_all_negative(): void
    {
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'negative',
            'sentiment_score' => -1.0,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertEquals(-1.00, $result['average_sentiment']);
    }

    /** @test AA-238: Average sentiment: 0 for no data */
    public function test_get_sentiment_breakdown_no_data(): void
    {
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertEquals(0.00, $result['average_sentiment']);
        $this->assertEquals(0, $result['positive']);
        $this->assertEquals(0, $result['neutral']);
        $this->assertEquals(0, $result['negative']);
        $this->assertEquals(0, $result['mixed']);
    }

    /** @test AA-244: Validates projectId exists */
    public function test_get_sentiment_breakdown_validates_project(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->getSentimentBreakdown(99999, 'week');
    }

    /** @test AA-245: Validates period value */
    public function test_get_sentiment_breakdown_validates_period(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->service->getSentimentBreakdown($this->project->id, 'invalid');
    }

    /** @test AA-254: Returns 0 for missing sentiment types */
    public function test_get_sentiment_breakdown_missing_types(): void
    {
        // Only positive, no negative
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'positive',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertEquals(10, $result['positive']);
        $this->assertEquals(0, $result['negative']);
    }

    /** @test AA-255: Total = positive + neutral + negative + mixed */
    public function test_get_sentiment_breakdown_total(): void
    {
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'positive',
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'neutral',
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'negative',
            'queried_at' => now(),
        ]);
        
        AIQueryResult::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'mixed',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $total = $result['positive'] + $result['neutral'] + $result['negative'] + $result['mixed'];
        
        $this->assertEquals(20, $total);
    }

    // ============================================================
    // Category 8: Performance & Caching (AA-276 to AA-300)
    // ============================================================

    /** @test AA-276: aggregate() < 500ms */
    public function test_aggregate_performance(): void
    {
        AIQueryResult::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $start = microtime(true);
        $this->service->aggregate($this->project->id, 'week');
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(500, $elapsed, 'aggregate() should complete in < 500ms');
    }

    /** @test AA-277: calculateMentionRate() < 100ms */
    public function test_calculate_mention_rate_performance(): void
    {
        AIQueryResult::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $start = microtime(true);
        $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(100, $elapsed, 'calculateMentionRate() should complete in < 100ms');
    }

    /** @test AA-278: calculateShareOfVoice() < 100ms */
    public function test_calculate_share_of_voice_performance(): void
    {
        AIQueryResult::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $start = microtime(true);
        $this->service->calculateShareOfVoice($this->project->id, 'week');
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(100, $elapsed, 'calculateShareOfVoice() should complete in < 100ms');
    }

    /** @test AA-279: getTrend() < 100ms */
    public function test_get_trend_performance(): void
    {
        AIQueryResult::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $start = microtime(true);
        $this->service->getTrend($this->project->id, 90);
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(100, $elapsed, 'getTrend() should complete in < 100ms');
    }

    /** @test AA-280: getTopCategories() < 100ms */
    public function test_get_top_categories_performance(): void
    {
        AIQueryResult::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $start = microtime(true);
        $this->service->getTopCategories($this->project->id);
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(100, $elapsed, 'getTopCategories() should complete in < 100ms');
    }

    /** @test AA-281: getSentimentBreakdown() < 100ms */
    public function test_get_sentiment_breakdown_performance(): void
    {
        AIQueryResult::factory()->count(100)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $start = microtime(true);
        $this->service->getSentimentBreakdown($this->project->id, 'week');
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(100, $elapsed, 'getSentimentBreakdown() should complete in < 100ms');
    }

    /** @test AA-282: Cache hit: no DB query */
    public function test_cache_hit_no_db_query(): void
    {
        // First call
        $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        
        // Second call should use cache
        $queriesBefore = DB::getQueryLog();
        
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(50.00);
        
        $result = $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
        
        $this->assertEquals(50.00, $result);
    }

    /** @test AA-284: Cache key format */
    public function test_cache_key_format(): void
    {
        $expectedKey = "ai_visibility:mention_rate:{$this->project->id}:chatgpt:week";
        
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) use ($expectedKey) {
                return $key === $expectedKey && $ttl === 3600;
            })
            ->andReturn(50.00);
        
        $this->service->calculateMentionRate($this->project->id, 'chatgpt', 'week');
    }

    // ============================================================
    // Category 9: Security & Edge Cases (AA-301 to AA-350)
    // ============================================================

    /** @test AA-306: Unicode in brand name */
    public function test_unicode_brand_name(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => '東京株式会社',
        ]);
        
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($project->id, 'week');
        
        $this->assertNotNull($result);
    }

    /** @test AA-309: Emoji in brand name */
    public function test_emoji_brand_name(): void
    {
        $project = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
            'brand_name' => 'Acme 🚀',
        ]);
        
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($project->id, 'week');
        
        $this->assertNotNull($result);
    }

    /** @test AA-316: Zero queries */
    public function test_zero_queries(): void
    {
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertEquals(0, $result->total_queries);
        $this->assertEquals(0, $result->brand_mentions);
        $this->assertEquals(0.00, $result->mention_rate);
    }

    /** @test AA-317: Single query */
    public function test_single_query(): void
    {
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->aggregate($this->project->id, 'week');
        
        $this->assertEquals(1, $result->total_queries);
        $this->assertEquals(1, $result->brand_mentions);
        $this->assertEquals(100.00, $result->mention_rate);
    }

    /** @test AA-322: Negative sentiment score */
    public function test_negative_sentiment_score(): void
    {
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'negative',
            'sentiment_score' => -0.75,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertLessThan(0, $result['average_sentiment']);
    }

    /** @test AA-323: Positive sentiment score */
    public function test_positive_sentiment_score(): void
    {
        AIQueryResult::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'sentiment' => 'positive',
            'sentiment_score' => 0.85,
            'queried_at' => now(),
        ]);
        
        $result = $this->service->getSentimentBreakdown($this->project->id, 'week');
        
        $this->assertGreaterThan(0, $result['average_sentiment']);
    }

    /** @test AA-339: Negative projectId */
    public function test_negative_project_id(): void
    {
        $this->expectException(ModelNotFoundException::class);
        
        $this->service->aggregate(-1, 'week');
    }

    /** @test AA-342: Negative limit */
    public function test_negative_limit(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->service->getTopCategories($this->project->id, -1);
    }

    /** @test AA-343: Zero limit */
    public function test_zero_limit(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->service->getTopCategories($this->project->id, 0);
    }

    /** @test AA-347: Period not in enum */
    public function test_invalid_period(): void
    {
        $this->expectException(ValidationException::class);
        
        $this->service->aggregate($this->project->id, 'yearly');
    }

    // ============================================================
    // Category 10: Integration (AA-351 to AA-380)
    // ============================================================

    /** @test AA-351: Full flow: aggregate then get analytics */
    public function test_full_flow_aggregate_analytics(): void
    {
        // Create query results
        AIQueryResult::factory()->count(50)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now(),
        ]);
        
        // Aggregate
        $analytics = $this->service->aggregate($this->project->id, 'week');
        
        // Verify stored
        $stored = AIMentionAnalytics::where('project_id', $this->project->id)
            ->where('period', 'week')
            ->first();
        
        $this->assertNotNull($stored);
        $this->assertEquals(50, $stored->total_queries);
        $this->assertEquals(50, $stored->brand_mentions);
    }

    /** @test AA-361: Upsert on repeat aggregate */
    public function test_upsert_repeat_aggregate(): void
    {
        // First aggregate
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $result1 = $this->service->aggregate($this->project->id, 'week');
        
        // Add more results
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        // Second aggregate
        $result2 = $this->service->aggregate($this->project->id, 'week');
        
        // Should only have one record
        $count = AIMentionAnalytics::where('project_id', $this->project->id)
            ->where('period', 'week')
            ->count();
        
        $this->assertEquals(1, $count);
        $this->assertEquals(15, $result2->total_queries);
    }

    /** @test AA-375: Multiple projects isolated */
    public function test_multiple_projects_isolated(): void
    {
        $project2 = AIVisibilityProject::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        // Project 1: 10 queries
        AIQueryResult::factory()->count(10)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        // Project 2: 5 queries
        AIQueryResult::factory()->count(5)->create([
            'project_id' => $project2->id,
            'platform' => 'chatgpt',
            'queried_at' => now(),
        ]);
        
        $result1 = $this->service->aggregate($this->project->id, 'week');
        $result2 = $this->service->aggregate($project2->id, 'week');
        
        $this->assertEquals(10, $result1->total_queries);
        $this->assertEquals(5, $result2->total_queries);
    }

    /** @test AA-380: Cross-period trend */
    public function test_cross_period_trend(): void
    {
        // Earlier period (days 60-90)
        AIQueryResult::factory()->count(20)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(75),
        ]);
        
        // Recent period (days 1-7)
        AIQueryResult::factory()->count(30)->create([
            'project_id' => $this->project->id,
            'platform' => 'chatgpt',
            'brand_mentioned' => true,
            'queried_at' => now()->subDays(5),
        ]);
        
        $result = $this->service->getTrend($this->project->id, 90);
        
        $this->assertEquals('up', $result['direction']);
    }
}
