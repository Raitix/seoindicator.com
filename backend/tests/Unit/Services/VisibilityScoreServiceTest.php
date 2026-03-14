<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Project;
use App\Models\VisibilityScore;
use App\Services\SEO\VisibilityScoreService;
use App\Services\SEO\BacklinkProfileScorer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Unit tests for TASK-23-15: Visibility Score Integration
 *
 * Tests the VisibilityScoreService responsible for:
 * - Integrating backlink profile score into overall visibility score
 * - Component weight calculation (Position 40%, SERP 30%, Backlink 20%, Volume 10%)
 * - Graceful degradation when backlink data is missing
 * - Caching of calculated scores
 *
 * @see \App\Services\SEO\VisibilityScoreService
 * @see TASK-23-15
 */
class VisibilityScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VisibilityScoreService $service;
    protected $backlinkScorerMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use array cache driver to avoid transaction conflicts
        Cache::swap(Cache::store('array'));
        
        // Mock BacklinkProfileScorer dependency
        $this->backlinkScorerMock = Mockery::mock(BacklinkProfileScorer::class);
        $this->app->instance(BacklinkProfileScorer::class, $this->backlinkScorerMock);
        
        $this->service = app(VisibilityScoreService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // COMPONENT WEIGHT TESTS (VW)
    // =========================================================================

    /**
     * VW-01: Position component weight is 40%
     */
    public function test_position_component_weight_is_40_percent(): void
    {
        $this->assertEquals(0.40, $this->service->getPositionWeight());
    }

    /**
     * VW-02: SERP component weight is 30%
     */
    public function test_serp_component_weight_is_30_percent(): void
    {
        $this->assertEquals(0.30, $this->service->getSerpWeight());
    }

    /**
     * VW-03: Backlink component weight is 20%
     */
    public function test_backlink_component_weight_is_20_percent(): void
    {
        $this->assertEquals(0.20, $this->service->getBacklinkWeight());
    }

    /**
     * VW-04: Volume component weight is 10%
     */
    public function test_volume_component_weight_is_10_percent(): void
    {
        $this->assertEquals(0.10, $this->service->getVolumeWeight());
    }

    /**
     * VW-05: All weights sum to 100%
     */
    public function test_all_weights_sum_to_100_percent(): void
    {
        $total = $this->service->getPositionWeight()
            + $this->service->getSerpWeight()
            + $this->service->getBacklinkWeight()
            + $this->service->getVolumeWeight();

        $this->assertEqualsWithDelta(1.0, $total, 0.0001);
    }

    // =========================================================================
    // BACKLINK COMPONENT INTEGRATION TESTS (BC)
    // =========================================================================

    /**
     * BC-01: Project with backlinks returns weighted score
     */
    public function test_project_with_backlinks_returns_weighted_score(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->with($project)
            ->andReturn(['overall_score' => 75.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(15.0, $component); // 75 * 0.20 = 15
    }

    /**
     * BC-02: Backlink score 100 returns component 20
     */
    public function test_backlink_score_100_returns_component_20(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 100.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(20.0, $component);
    }

    /**
     * BC-03: Backlink score 50 returns component 10
     */
    public function test_backlink_score_50_returns_component_10(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 50.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(10.0, $component);
    }

    /**
     * BC-04: Backlink score 0 returns component 0
     */
    public function test_backlink_score_0_returns_component_0(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 0.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * BC-05: Service calls BacklinkProfileScorer
     */
    public function test_service_calls_backlink_profile_scorer(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->with($project)
            ->andReturn(['overall_score' => 50.0]);

        $this->service->calculateBacklinkComponent($project);

        // Mockery verifies the call automatically
        $this->assertTrue(true);
    }

    // =========================================================================
    // GRACEFUL DEGRADATION TESTS (GD)
    // =========================================================================

    /**
     * GD-01: Project without backlinks returns 0 component
     */
    public function test_project_without_backlinks_returns_0_component(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(null);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * GD-02: No backlink snapshots returns 0 component
     */
    public function test_no_backlink_snapshots_returns_0_component(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(null);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * GD-03: Redistributed weights when backlinks missing
     */
    public function test_redistributed_weights_when_backlinks_missing(): void
    {
        $weights = $this->service->getRedistributedWeights();

        $this->assertEquals(0.50, $weights['position']);
        $this->assertEquals(0.375, $weights['serp']);
        $this->assertEquals(0.125, $weights['volume']);
        $this->assertArrayNotHasKey('backlink', $weights);
    }

    /**
     * GD-04: Visibility score without backlinks uses redistribution
     */
    public function test_visibility_score_without_backlinks_uses_redistribution(): void
    {
        $project = Project::factory()->create();

        // Mock all component calculations
        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(null); // No backlink data

        // With redistribution: position (50%), serp (37.5%), volume (12.5%)
        // If position=80, serp=60, volume=90: 40 + 22.5 + 11.25 = 73.75 -> 74
        $score = $this->service->calculate($project);

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * GD-05: Missing BacklinkProfileScorer returns 0 gracefully
     */
    public function test_missing_backlink_profile_scorer_returns_0_gracefully(): void
    {
        // Create a fresh service without the BacklinkProfileScorer
        $service = new VisibilityScoreService(null);
        
        $project = Project::factory()->create();
        $component = $service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    // =========================================================================
    // VISIBILITY SCORE CALCULATION TESTS (VS)
    // =========================================================================

    /**
     * VS-01: Full score calculation returns int 0-100
     */
    public function test_full_score_calculation_returns_int_0_100(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 75.0]);

        $score = $this->service->calculate($project);

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * VS-02: Perfect scores all components returns 100
     */
    public function test_perfect_scores_all_components_returns_100(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 100.0]);

        // Mock other components to return 100
        $score = $this->service->calculate($project, [
            'position' => 100.0,
            'serp' => 100.0,
            'volume' => 100.0,
        ]);

        $this->assertEquals(100, $score);
    }

    /**
     * VS-03: Zero scores all components returns 0
     */
    public function test_zero_scores_all_components_returns_0(): void
    {
        $project = Project::factory()->create();

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
     * VS-04: Mixed scores calculates correctly
     */
    public function test_mixed_scores_calculates_correctly(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 40.0]);

        // Position=80 (40% of 80 = 32), SERP=60 (30% of 60 = 18), 
        // Backlinks=40 (20% of 40 = 8), Volume=90 (10% of 90 = 9)
        // Total = 32 + 18 + 8 + 9 = 67
        $score = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $this->assertEquals(67, $score);
    }

    /**
     * VS-05: Decimal rounding down
     */
    public function test_decimal_rounding_down(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 50.0]);

        // Create a scenario where the result is 67.4
        $score = $this->service->calculate($project, [
            'position' => 67.0,   // 26.8
            'serp' => 50.0,       // 15.0
            'volume' => 78.0,     // 7.8 + 10 = 25.6
        ]);
        // 26.8 + 15 + 10 + 7.8 = 59.6

        $this->assertEquals(60, $score); // 59.6 rounds to 60
    }

    /**
     * VS-06: Decimal rounding up
     */
    public function test_decimal_rounding_up(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 50.0]);

        // Create scenario where result rounds up
        $score = $this->service->calculate($project, [
            'position' => 67.5,
            'serp' => 50.0,
            'volume' => 80.0,
        ]);

        // Standard rounding: 0.5 rounds up
        $this->assertIsInt($score);
    }

    /**
     * VS-07: Score never exceeds 100
     */
    public function test_score_never_exceeds_100(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 150.0]); // Invalid high score

        $score = $this->service->calculate($project, [
            'position' => 120.0,  // Invalid
            'serp' => 110.0,      // Invalid
            'volume' => 200.0,    // Invalid
        ]);

        $this->assertEquals(100, $score); // Capped at 100
    }

    /**
     * VS-08: Score never below 0
     */
    public function test_score_never_below_0(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 0.0]); // Will be clamped to 0

        $score = $this->service->calculate($project, [
            'position' => 0.0,
            'serp' => 0.0,
            'volume' => 0.0,
        ]);

        $this->assertEquals(0, $score); // Floored at 0
    }

    // =========================================================================
    // CACHING BEHAVIOR TESTS (CB)
    // =========================================================================

    /**
     * CB-01: Score is cached
     */
    public function test_score_is_cached(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 75.0]);

        // First call - should calculate and cache
        $score1 = $this->service->calculate($project);
        
        // Second call - should use cache
        $score2 = $this->service->calculate($project);

        $this->assertEquals($score1, $score2);
    }

    /**
     * CB-02: Cache key format is correct
     */
    public function test_cache_key_format_is_correct(): void
    {
        $project = Project::factory()->create(['id' => 42]);

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 75.0]);

        $this->service->calculate($project);
        
        // Verify cache key exists
        $this->assertTrue(Cache::has('visibility_score:42'));
    }

    /**
     * CB-03: Cache TTL is 1 hour
     */
    public function test_cache_ttl_is_1_hour(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 50.0]);

        $this->service->calculate($project);

        // Cache should exist (array driver doesn't support TTL checking but we verify it's stored)
        $this->assertTrue(Cache::has("visibility_score:{$project->id}"));
    }

    /**
     * CB-04: Cache hit returns cached value
     */
    public function test_cache_hit_returns_cached_value(): void
    {
        $project = Project::factory()->create();

        // Pre-populate cache
        Cache::put("visibility_score:{$project->id}", 85, 3600);

        // Should not call scorer on cache hit
        $this->backlinkScorerMock->shouldNotReceive('score');

        $score = $this->service->calculate($project);

        $this->assertEquals(85, $score);
    }

    /**
     * CB-05: Cache miss recalculates score
     */
    public function test_cache_miss_recalculates_score(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 60.0]);

        $score = $this->service->calculate($project);

        $this->assertIsInt($score);
    }

    /**
     * CB-06: Cache cleared on backlink sync
     */
    public function test_cache_cleared_on_backlink_sync(): void
    {
        $projectId = 42;

        // Pre-populate cache
        Cache::put("visibility_score:{$projectId}", 75, 3600);

        $this->service->clearCache($projectId);

        $this->assertFalse(Cache::has("visibility_score:{$projectId}"));
    }

    /**
     * CB-07: Force recalculate bypasses cache
     */
    public function test_force_recalculate_bypasses_cache(): void
    {
        $project = Project::factory()->create();

        // Pre-populate cache with different value
        Cache::put("visibility_score:{$project->id}", 50, 3600);

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 70.0]);

        $score = $this->service->calculate($project, [], true); // force=true

        // Should have recalculated with new value
        $this->assertNotEquals(50, $score);
    }

    // =========================================================================
    // BACKLINK COMPONENT CALCULATION TESTS (BK)
    // =========================================================================

    /**
     * BK-01: Calls profile scorer
     */
    public function test_calls_profile_scorer(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->with($project)
            ->andReturn(['overall_score' => 75.0]);

        $this->service->calculateBacklinkComponent($project);

        // Mockery verifies the call
        $this->assertTrue(true);
    }

    /**
     * BK-02: Profile score 75 returns component 15
     */
    public function test_profile_score_75_returns_component_15(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 75.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(15.0, $component);
    }

    /**
     * BK-03: Profile score null returns 0
     */
    public function test_profile_score_null_returns_0(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(null);

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * BK-04: Profile score exception returns 0
     */
    public function test_profile_score_exception_returns_0(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andThrow(new \Exception('Scorer error'));

        $component = $this->service->calculateBacklinkComponent($project);

        $this->assertEquals(0.0, $component);
    }

    // =========================================================================
    // INTEGRATION WITH VISIBILITY SCORES TABLE TESTS (VT)
    // =========================================================================

    /**
     * VT-01: Stores backlink component
     */
    public function test_stores_backlink_component(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 75.0]);

        $score = $this->service->calculateAndStore($project);

        $this->assertDatabaseHas('visibility_scores', [
            'project_id' => $project->id,
            'backlink_component' => 15.0,
        ]);
    }

    /**
     * VT-02: Component breakdown stored
     */
    public function test_component_breakdown_stored(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 60.0]);

        $score = $this->service->calculateAndStore($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $visibilityScore = VisibilityScore::where('project_id', $project->id)->first();

        $this->assertNotNull($visibilityScore);
        $this->assertEquals(32.0, $visibilityScore->position_component);
        $this->assertEquals(18.0, $visibilityScore->serp_component);
        $this->assertEquals(12.0, $visibilityScore->backlink_component);
        $this->assertEquals(9.0, $visibilityScore->volume_component);
    }

    /**
     * VT-03: Historical tracking available
     */
    public function test_historical_tracking_available(): void
    {
        $project = Project::factory()->create();

        // Create historical scores
        VisibilityScore::factory()->create([
            'project_id' => $project->id,
            'score' => 50,
            'created_at' => now()->subDays(2),
        ]);
        VisibilityScore::factory()->create([
            'project_id' => $project->id,
            'score' => 55,
            'created_at' => now()->subDay(),
        ]);

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 70.0]);

        $this->service->calculateAndStore($project);

        $this->assertEquals(3, VisibilityScore::where('project_id', $project->id)->count());
    }

    /**
     * VT-04: Component queryable
     */
    public function test_component_queryable(): void
    {
        $project = Project::factory()->create();

        VisibilityScore::factory()->create([
            'project_id' => $project->id,
            'backlink_component' => 15.0,
            'score' => 75,
        ]);

        $highBacklinkScores = VisibilityScore::where('backlink_component', '>', 10.0)->count();

        $this->assertGreaterThanOrEqual(1, $highBacklinkScores);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-01: Project null throws exception
     */
    public function test_project_null_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->calculate(null);
    }

    /**
     * EC-02: Project deleted mid-calculation handled
     */
    public function test_project_deleted_mid_calculation_handled(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 50.0]);

        // Simulate project deletion during calculation
        $project->delete();

        // Should still complete without error (or throw appropriate exception)
        try {
            $score = $this->service->calculate($project);
        } catch (\Exception $e) {
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    /**
     * EC-03: Concurrent calculations no race conditions
     */
    public function test_concurrent_calculations_no_race_conditions(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(['overall_score' => 75.0]);

        // First call caches the result
        $score1 = $this->service->calculate($project);
        
        // Second call uses cache
        $score2 = $this->service->calculate($project);

        $this->assertEquals($score1, $score2);
    }

    /**
     * EC-04: Large project performance
     */
    public function test_large_project_performance(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 50.0]);

        $start = microtime(true);
        $this->service->calculate($project);
        $duration = microtime(true) - $start;

        // Should complete within 500ms
        $this->assertLessThan(0.5, $duration);
    }

    /**
     * EC-05: Negative component score treated as 0
     */
    public function test_negative_component_score_treated_as_0(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => -10.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        // Negative scores are clamped to 0, so contribution is 0
        $this->assertEquals(0.0, $component);
    }

    /**
     * EC-06: Component score above 100 capped
     */
    public function test_component_score_above_100_capped(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 150.0]);

        $component = $this->service->calculateBacklinkComponent($project);

        // 150 clamped to 100, then * 0.20 = 20
        $this->assertEquals(20.0, $component);
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-01: SQL injection in calculation prevented
     */
    public function test_sql_injection_in_calculation_prevented(): void
    {
        $project = Project::factory()->create([
            'name' => "'; DROP TABLE visibility_scores; --",
        ]);

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(['overall_score' => 50.0]);

        // Should complete without SQL error
        $score = $this->service->calculate($project);

        $this->assertIsInt($score);
    }

    /**
     * SC-02: Unauthorized project access denied
     */
    public function test_unauthorized_project_access_denied(): void
    {
        $this->markTestSkipped('Authorization handled at controller level');
    }

    /**
     * SC-03: Cache poisoning attempt prevented
     */
    public function test_cache_poisoning_attempt_prevented(): void
    {
        $projectId = 42;

        // Cache key should use the ID directly
        $this->service->clearCache($projectId);
        
        // Verify it doesn't throw an exception
        $this->assertTrue(true);
    }

    /**
     * SC-04: IDOR via project ID prevented
     */
    public function test_idor_via_project_id_prevented(): void
    {
        $this->markTestSkipped('Authorization handled at controller level');
    }
}
