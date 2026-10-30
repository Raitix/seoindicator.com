<?php

namespace Tests\Unit\Services\SEO;

use Tests\TestCase;
use App\Models\Project;
use App\Services\SEO\VisibilityScoreService;
use App\Services\SEO\BacklinkProfileScorer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Unit tests for TASK-23-15: Visibility Score Integration
 *
 * Tests the VisibilityScoreService's integration with the BacklinkProfileScorer
 * and component weight calculations.
 *
 * @see \App\Services\SEO\VisibilityScoreService
 * @see TASK-23-15
 */
class VisibilityScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $backlinkScorerMock;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache for each test (array driver is already configured in phpunit.xml)
        Cache::flush();

        // Create mock for BacklinkProfileScorer
        $this->backlinkScorerMock = Mockery::mock(BacklinkProfileScorer::class);

        // Create service instance with mock
        $this->service = new VisibilityScoreService($this->backlinkScorerMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock Project without database interaction
     */
    protected function createMockProject(int $id = 1): Project
    {
        $project = new Project();
        $project->id = $id;
        $project->name = 'Test Project';
        $project->user_id = 1;
        return $project;
    }

    // =========================================================================
    // COMPONENT WEIGHT TESTS (VW-01 to VW-05)
    // =========================================================================

    /**
     * VW-01: Position component weight is 40%
     */
    public function test_position_component_weight_is_40_percent(): void
    {
        $weights = $this->service->getWeights();

        $this->assertEquals(0.40, $weights['position']);
        $this->assertEquals(0.40, $this->service->getPositionWeight());
    }

    /**
     * VW-02: SERP component weight is 30%
     */
    public function test_serp_component_weight_is_30_percent(): void
    {
        $weights = $this->service->getWeights();

        $this->assertEquals(0.30, $weights['serp']);
        $this->assertEquals(0.30, $this->service->getSerpWeight());
    }

    /**
     * VW-03: Backlink component weight is 20%
     */
    public function test_backlink_component_weight_is_20_percent(): void
    {
        $weights = $this->service->getWeights();

        $this->assertEquals(0.20, $weights['backlink']);
        $this->assertEquals(0.20, $this->service->getBacklinkWeight());
    }

    /**
     * VW-04: Volume component weight is 10%
     */
    public function test_volume_component_weight_is_10_percent(): void
    {
        $weights = $this->service->getWeights();

        $this->assertEquals(0.10, $weights['volume']);
        $this->assertEquals(0.10, $this->service->getVolumeWeight());
    }

    /**
     * VW-05: All weights sum to 100%
     */
    public function test_all_weights_sum_to_100_percent(): void
    {
        $weights = $this->service->getWeights();

        $sum = array_sum($weights);

        $this->assertEqualsWithDelta(1.0, $sum, 0.0001);
    }

    // =========================================================================
    // BACKLINK COMPONENT INTEGRATION TESTS (BC-01 to BC-05)
    // =========================================================================

    /**
     * BC-01: Project with backlinks returns raw profile score
     */
    public function test_project_with_backlinks_returns_weighted_score(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 75.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        // calculateBacklinkComponent returns raw profile score (0-100)
        // Weight is applied by calculate() method
        $this->assertEquals(75.0, $component);
    }

    /**
     * BC-02: Backlink score 100 returns raw score 100
     */
    public function test_backlink_score_100_returns_component_20(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 100.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        // Returns raw profile score (0-100)
        $this->assertEquals(100.0, $component);
    }

    /**
     * BC-03: Backlink score 50 returns raw score 50
     */
    public function test_backlink_score_50_returns_component_10(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 50.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        // Returns raw profile score (0-100)
        $this->assertEquals(50.0, $component);
    }

    /**
     * BC-04: Backlink score 0 returns component 0.0
     */
    public function test_backlink_score_0_returns_component_0(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 0.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        // 0 * 0.20 = 0.0
        $this->assertEquals(0.0, $component);
    }

    /**
     * BC-05: Service calls BacklinkProfileScorer
     */
    public function test_service_calls_backlink_profile_scorer(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->with($project)
            ->andReturn(['overall_score' => 75.0]);

        $this->service->calculateBacklinkComponent($project);
    }

    // =========================================================================
    // GRACEFUL DEGRADATION TESTS (GD-01 to GD-05)
    // =========================================================================

    /**
     * GD-01: Project without backlinks returns 0 component
     */
    public function test_project_without_backlinks_returns_0_component(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 0.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * GD-02: No backlink snapshots returns 0 for component
     */
    public function test_no_backlink_snapshots_returns_0_component(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 0.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * GD-03: Redistributed weights when no backlink data
     */
    public function test_redistributed_weights_when_no_backlink_data(): void
    {
        $weights = $this->service->getRedistributedWeights();

        // Position: 50%, SERP: 37.5%, Volume: 12.5%
        $this->assertEquals(0.50, $weights['position']);
        $this->assertEquals(0.375, $weights['serp']);
        $this->assertEquals(0.125, $weights['volume']);

        // Verify sum is 1.0
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    /**
     * GD-04: Visibility score without backlinks uses redistribution
     */
    public function test_visibility_score_without_backlinks_uses_redistribution(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 0.0]);

        // Position 80, SERP 60, Volume 90
        // With redistribution: 80 * 0.50 + 60 * 0.375 + 90 * 0.125
        // = 40 + 22.5 + 11.25 = 73.75 -> 74
        $score = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $this->assertEquals(74, $score);
    }

    /**
     * GD-05: Missing BacklinkProfileScorer returns 0
     */
    public function test_missing_backlink_profile_scorer_returns_0(): void
    {
        $project = $this->createMockProject();

        // Service without backlink scorer
        $service = new VisibilityScoreService(null);

        $component = $service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    // =========================================================================
    // VISIBILITY SCORE CALCULATION TESTS (VS-01 to VS-08)
    // =========================================================================

    /**
     * VS-01: Full score calculation returns int 0-100
     */
    public function test_full_score_calculation_returns_int_0_to_100(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 75.0]);

        $score = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * VS-02: Perfect scores all components returns 100
     */
    public function test_perfect_scores_all_components_returns_100(): void
    {
        $project = $this->createMockProject();

        // Pre-calculated backlink component: 100 * 0.20 = 20
        $score = $this->service->calculate($project, [
            'position' => 100.0,
            'serp' => 100.0,
            'volume' => 100.0,
            'backlink' => 100.0,  // Pre-calculated to avoid scorer call
        ]);

        // 100 * 0.40 + 100 * 0.30 + 100 * 0.20 + 100 * 0.10 = 100
        $this->assertEquals(100, $score);
    }

    /**
     * VS-03: Zero scores all components returns 0
     */
    public function test_zero_scores_all_components_returns_0(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 0.0]);

        $score = $this->service->calculate($project, [
            'position' => 0.0,
            'serp' => 0.0,
            'volume' => 0.0,
        ]);

        $this->assertEquals(0, $score);
    }

    /**
     * VS-04: Mixed scores calculated correctly
     */
    public function test_mixed_scores_calculated_correctly(): void
    {
        $project = $this->createMockProject();

        // Position=80, SERP=60, Backlinks=40, Volume=90
        // = 80 * 0.40 + 60 * 0.30 + 40 * 0.20 + 90 * 0.10
        // = 32 + 18 + 8 + 9 = 67
        $score = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
            'backlink' => 40.0,  // Pre-calculated
        ]);

        $this->assertEquals(67, $score);
    }

    /**
     * VS-05: Decimal rounding (67.4 -> 67, 67.5 -> 68)
     */
    public function test_decimal_rounding(): void
    {
        $project = $this->createMockProject();

        // Score that results in 67.4
        // 67.4 = pos * 0.4 + serp * 0.3 + back * 0.2 + vol * 0.1
        // Let's use: 67.4 = 67.4 * 0.4 + 67.4 * 0.3 + 67.4 * 0.2 + 67.4 * 0.1
        // = 26.96 + 20.22 + 13.48 + 6.74 = 67.4
        $score = $this->service->calculate($project, [
            'position' => 67.4,
            'serp' => 67.4,
            'volume' => 67.4,
            'backlink' => 67.4,
        ]);

        $this->assertEquals(67, $score);

        // Score that results in 67.5 -> rounds to 68
        // Clear cache for fresh calculation
        $this->service->clearCache($project->id);
        $project->id = 2; // Different project to avoid cache
        
        $score = $this->service->calculate($project, [
            'position' => 67.5,
            'serp' => 67.5,
            'volume' => 67.5,
            'backlink' => 67.5,
        ]);

        $this->assertEquals(68, $score);
    }

    /**
     * VS-07: Score never exceeds 100
     */
    public function test_score_never_exceeds_100(): void
    {
        $project = $this->createMockProject();

        // Components exceed 100, but should be capped
        $score = $this->service->calculate($project, [
            'position' => 150.0,
            'serp' => 150.0,
            'volume' => 150.0,
            'backlink' => 150.0,  // Invalid high score
        ]);

        // All components capped at 100, so max score is 100
        // 100 * 0.40 + 100 * 0.30 + 100 * 0.20 + 100 * 0.10 = 100
        $this->assertEquals(100, $score);
    }

    /**
     * VS-08: Score never below 0
     */
    public function test_score_never_below_0(): void
    {
        $project = $this->createMockProject();

        $score = $this->service->calculate($project, [
            'position' => -50.0,
            'serp' => -50.0,
            'volume' => -50.0,
            'backlink' => -50.0,  // Invalid negative score
        ]);

        // Should be floored at 0
        $this->assertEquals(0, $score);
    }

    // =========================================================================
    // BACKLINK COMPONENT CALCULATION TESTS (BK-01 to BK-04)
    // =========================================================================

    /**
     * BK-01: Calls profile scorer
     */
    public function test_calls_profile_scorer(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->with(Mockery::type(Project::class))
            ->andReturn(['overall_score' => 75.0]);

        $this->service->calculateBacklinkComponent($project);
    }

    /**
     * BK-02: Profile score 75 returns raw score 75
     */
    public function test_profile_score_75_returns_component_15(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 75.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        // Returns raw profile score (0-100)
        $this->assertEquals(75.0, $component);
    }

    /**
     * BK-03: Profile score zero (no data) returns component 0
     */
    public function test_profile_score_zero_returns_component_0(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 0.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * BK-04: Profile score exception returns component 0
     */
    public function test_profile_score_exception_returns_component_0(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andThrow(new \Exception('Scorer failed'));

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    // =========================================================================
    // CACHING BEHAVIOR TESTS (CB-01 to CB-07)
    // =========================================================================

    /**
     * CB-01: Score is cached
     */
    public function test_score_is_cached(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 75.0]);

        // First call
        $score1 = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        // Second call should use cache (no additional mock call)
        $score2 = $this->service->calculate($project);

        $this->assertEquals($score1, $score2);
    }

    /**
     * CB-02: Cache key format is visibility_score:{project_id}
     */
    public function test_cache_key_format(): void
    {
        $projectId = 123;

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);

        $cacheKey = $method->invoke($this->service, $projectId);

        $this->assertEquals('visibility_score:123', $cacheKey);
    }

    /**
     * CB-03: Cache TTL is 1 hour
     */
    public function test_cache_ttl_is_1_hour(): void
    {
        // TTL is defined as constant CACHE_TTL = 3600
        $reflection = new \ReflectionClass($this->service);
        $constant = $reflection->getConstant('CACHE_TTL');

        $this->assertEquals(3600, $constant);
    }

    /**
     * CB-04: Cache hit returns cached value
     */
    public function test_cache_hit_returns_cached_value(): void
    {
        // Use unique project ID to avoid cache collisions with other tests
        $project = $this->createMockProject(9001);

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 75.0]);

        // Calculate once
        $score1 = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        // Second call should return cached value (no additional scorer call)
        $score2 = $this->service->calculate($project);

        $this->assertEquals(74, $score1);
        $this->assertEquals($score1, $score2);
    }

    /**
     * CB-05: Cache miss recalculates
     */
    public function test_cache_miss_recalculates(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->twice()
            ->andReturn(['overall_score' => 75.0]);

        // First call
        $score1 = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        // Clear cache
        $this->service->clearCache($project->id);

        // Second call should recalculate
        $score2 = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $this->assertEquals($score1, $score2);
    }

    /**
     * CB-06: Cache invalidated on clear
     */
    public function test_cache_invalidated_on_clear(): void
    {
        // Use unique project ID to avoid cache collisions with other tests
        $project = $this->createMockProject(9002);

        // Set up mock to be called twice with different values
        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->twice()
            ->andReturnValues([
                ['overall_score' => 75.0],
                ['overall_score' => 80.0],
            ]);

        // Calculate and cache
        $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        // Clear cache
        $this->service->clearCache($project->id);

        // Second call should use new backlink score (80 * 0.20 = 16)
        $score = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        // Score should reflect new backlink score
        // Position: 80 * 0.40 = 32
        // SERP: 60 * 0.30 = 18
        // Backlink: 80 * 0.20 = 16
        // Volume: 90 * 0.10 = 9
        // Total: 32 + 18 + 16 + 9 = 75
        $this->assertEquals(75, $score);
    }

    /**
     * CB-07: Force recalculate bypasses cache
     */
    public function test_force_recalculate_bypasses_cache(): void
    {
        // Use unique project ID to avoid cache collisions with other tests
        $project = $this->createMockProject(9003);

        // Set up mock to return different values on each call
        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->twice()
            ->andReturnValues([
                ['overall_score' => 75.0],
                ['overall_score' => 90.0],  // Different value for second call
            ]);

        // First call - cache backlink score 75
        $score1 = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);
        // Score: 32 + 18 + 15 + 9 = 74

        // Force recalculate - should use new backlink score 90
        $score2 = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ], true);
        // Score: 32 + 18 + 18 + 9 = 77

        // Scores should be different
        $this->assertEquals(74, $score1);
        $this->assertEquals(77, $score2);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC-01 to EC-06)
    // =========================================================================

    /**
     * EC-01: Project null throws TypeError (type-hinted parameter)
     */
    public function test_project_null_throws_type_error(): void
    {
        $this->expectException(\TypeError::class);

        $this->service->calculate(null);
    }

    /**
     * EC-02: Negative component score treated as 0
     */
    public function test_negative_component_score_treated_as_0(): void
    {
        $project = $this->createMockProject();

        $score = $this->service->calculate($project, [
            'position' => -50.0,
            'serp' => -50.0,
            'volume' => -50.0,
            'backlink' => -50.0,
        ]);

        // Negative values should be clamped to 0
        $this->assertEquals(0, $score);
    }

    /**
     * EC-03: Component score > 100 capped at 100
     */
    public function test_component_score_above_100_capped(): void
    {
        $project = $this->createMockProject();

        $score = $this->service->calculate($project, [
            'position' => 200.0,  // Should be capped to 100
            'serp' => 200.0,
            'volume' => 200.0,
            'backlink' => 200.0,
        ]);

        // All components capped at 100, so max score is 100
        $this->assertEquals(100, $score);
    }

    /**
     * EC-04: Large project performance
     */
    public function test_large_project_performance(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 75.0]);

        $start = microtime(true);

        $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $duration = microtime(true) - $start;

        // Should complete within 500ms
        $this->assertLessThan(0.5, $duration);
    }

    /**
     * EC-05: Concurrent calculations (caching prevents race)
     */
    public function test_concurrent_calculations_use_cache(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 75.0]);

        // Multiple "concurrent" calls
        $scores = [];
        for ($i = 0; $i < 5; $i++) {
            $scores[] = $this->service->calculate($project, [
                'position' => 80.0,
                'serp' => 60.0,
                'volume' => 90.0,
            ]);
        }

        // All scores should be identical
        $this->assertCount(1, array_unique($scores));
    }

    /**
     * EC-06: Decimal precision in calculations
     */
    public function test_decimal_precision_in_calculations(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 33.333]);

        $score = $this->service->calculate($project, [
            'position' => 33.333,
            'serp' => 33.333,
            'volume' => 33.333,
        ]);

        // Should be a valid integer
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    // =========================================================================
    // GET BREAKDOWN TESTS
    // =========================================================================

    /**
     * Get breakdown returns all components
     */
    public function test_get_breakdown_returns_all_components(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 75.0]);

        $breakdown = $this->service->getBreakdown($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $this->assertArrayHasKey('overall_score', $breakdown);
        $this->assertArrayHasKey('components', $breakdown);
        $this->assertArrayHasKey('has_backlink_data', $breakdown);

        $components = $breakdown['components'];
        $this->assertArrayHasKey('position', $components);
        $this->assertArrayHasKey('serp_features', $components);
        $this->assertArrayHasKey('backlinks', $components);
        $this->assertArrayHasKey('volume_distribution', $components);
    }

    /**
     * Get breakdown calculates contributions correctly
     */
    public function test_get_breakdown_calculates_contributions_correctly(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 75.0]);

        $breakdown = $this->service->getBreakdown($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        // Position contribution: 80 * 0.40 = 32
        $this->assertEquals(32.0, $breakdown['components']['position']['contribution']);

        // SERP contribution: 60 * 0.30 = 18
        $this->assertEquals(18.0, $breakdown['components']['serp_features']['contribution']);

        // Backlink contribution: 75 * 0.20 = 15 (calculateBacklinkComponent returns raw score 75)
        $this->assertEquals(15.0, $breakdown['components']['backlinks']['contribution']);

        // Volume contribution: 90 * 0.10 = 9
        $this->assertEquals(9.0, $breakdown['components']['volume_distribution']['contribution']);

        // Overall: 32 + 18 + 15 + 9 = 74
        $this->assertEquals(74, $breakdown['overall_score']);
    }

    /**
     * Get breakdown with no backlinks shows redistribution
     */
    public function test_get_breakdown_with_no_backlinks_shows_redistribution(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 0.0]);

        $breakdown = $this->service->getBreakdown($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $this->assertFalse($breakdown['has_backlink_data']);

        // Position weight should be 50% (redistributed)
        $this->assertEquals(50, $breakdown['components']['position']['weight']);

        // SERP weight should be 37.5% (redistributed)
        $this->assertEquals(37, $breakdown['components']['serp_features']['weight']);

        // Volume weight should be 12.5% (redistributed)
        $this->assertEquals(12, $breakdown['components']['volume_distribution']['weight']);
    }

    // =========================================================================
    // SCORER RETURN VALUE TESTS
    // =========================================================================

    /**
     * Scorer returns array with overall_score
     */
    public function test_scorer_returns_array_with_overall_score(): void
    {
        $project = $this->createMockProject();

        // BacklinkProfileScorer returns array with overall_score
        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 75.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        // Returns raw profile score (0-100)
        $this->assertEquals(75.0, $component);
    }

    /**
     * Scorer returns array with zero overall_score
     */
    public function test_scorer_returns_array_with_zero_overall_score(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 0.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * Scorer returns array with missing overall_score (defaults to 0)
     */
    public function test_scorer_returns_array_with_missing_overall_score(): void
    {
        $project = $this->createMockProject();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['diversity_score' => 25.0]);  // No overall_score key

        $component = $this->service->calculateBacklinkComponent($project);

        // Missing overall_score defaults to 0.0
        $this->assertEquals(0.0, $component);
    }
}
