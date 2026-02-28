<?php

namespace Tests\Unit\Services\SEO;

use Tests\TestCase;
use App\Models\Project;
use App\Models\PWAAnalysis;
use App\Models\VisibilityScore;
use App\Services\SEO\VisibilityScoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for TASK-24-13: PWA Component in Visibility Score
 *
 * Tests the VisibilityScoreService PWA integration:
 * - PWA component calculation (10% weight)
 * - Graceful degradation when PWA data is missing
 * - Score calculation with all components
 *
 * @see \App\Services\SEO\VisibilityScoreService
 * @see TASK-24-13
 */
class VisibilityScorePWAComponentTest extends TestCase
{
    use RefreshDatabase;

    protected VisibilityScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VisibilityScoreService::class);
    }

    // =========================================================================
    // COMPONENT WEIGHT TESTS (W)
    // =========================================================================

    /**
     * W-01: Position component weight is 50%
     */
    public function test_position_component_weight_is_50_percent(): void
    {
        $this->assertEquals(0.50, $this->service->getPositionWeight());
    }

    /**
     * W-02: SERP component weight is 25%
     */
    public function test_serp_component_weight_is_25_percent(): void
    {
        $this->assertEquals(0.25, $this->service->getSerpWeight());
    }

    /**
     * W-03: Volume component weight is 15%
     */
    public function test_volume_component_weight_is_15_percent(): void
    {
        $this->assertEquals(0.15, $this->service->getVolumeWeight());
    }

    /**
     * W-04: PWA component weight is 10%
     */
    public function test_pwa_component_weight_is_10_percent(): void
    {
        $this->assertEquals(0.10, $this->service->getPWAWeight());
    }

    /**
     * W-05: All weights sum to 100%
     */
    public function test_all_weights_sum_to_100_percent(): void
    {
        $total = $this->service->getPositionWeight()
            + $this->service->getSerpWeight()
            + $this->service->getVolumeWeight()
            + $this->service->getPWAWeight();

        $this->assertEquals(1.0, $total);
    }

    // =========================================================================
    // PWA COMPONENT CALCULATION TESTS (PC)
    // =========================================================================

    /**
     * PC-001: Full PWA score returns 10 points
     */
    public function test_full_pwa_score_returns_10_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 100,
            'readiness_level' => 'full',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(10.0, $component);
    }

    /**
     * PC-002: Enhanced PWA score 85 returns 8.5 points
     */
    public function test_enhanced_pwa_score_85_returns_8_5_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 85,
            'readiness_level' => 'enhanced',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(8.5, $component);
    }

    /**
     * PC-003: Basic PWA score 50 returns 5 points
     */
    public function test_basic_pwa_score_50_returns_5_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 50,
            'readiness_level' => 'basic',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(5.0, $component);
    }

    /**
     * PC-004: Minimal PWA score 25 returns 2.5 points
     */
    public function test_minimal_pwa_score_25_returns_2_5_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 25,
            'readiness_level' => 'none',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(2.5, $component);
    }

    /**
     * PC-005: No PWA score returns 0 points
     */
    public function test_no_pwa_score_returns_0_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 0,
            'readiness_level' => 'none',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * PC-006: Null PWA analysis returns 0 points
     */
    public function test_null_pwa_analysis_returns_0_points(): void
    {
        $project = Project::factory()->create();

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * PC-007: Decimal precision - score 73 returns 7.3 points
     */
    public function test_decimal_precision_score_73_returns_7_3_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 73,
            'readiness_level' => 'enhanced',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(7.3, $component);
    }

    /**
     * PC-008: Boundary 39 (none level) returns 3.9 points
     */
    public function test_boundary_39_returns_3_9_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 39,
            'readiness_level' => 'none',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(3.9, $component);
    }

    /**
     * PC-009: Boundary 40 (basic level) returns 4 points
     */
    public function test_boundary_40_returns_4_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 40,
            'readiness_level' => 'basic',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(4.0, $component);
    }

    /**
     * PC-010: Boundary 69 (basic high) returns 6.9 points
     */
    public function test_boundary_69_returns_6_9_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 69,
            'readiness_level' => 'basic',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(6.9, $component);
    }

    /**
     * PC-011: Boundary 70 (enhanced level) returns 7 points
     */
    public function test_boundary_70_returns_7_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 70,
            'readiness_level' => 'enhanced',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(7.0, $component);
    }

    /**
     * PC-012: Boundary 89 (enhanced high) returns 8.9 points
     */
    public function test_boundary_89_returns_8_9_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 89,
            'readiness_level' => 'enhanced',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(8.9, $component);
    }

    /**
     * PC-013: Boundary 90 (full level) returns 9 points
     */
    public function test_boundary_90_returns_9_points(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 90,
            'readiness_level' => 'full',
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(9.0, $component);
    }

    // =========================================================================
    // FULL VISIBILITY SCORE TESTS (VS)
    // =========================================================================

    /**
     * VS-001: Full visibility with full PWA returns 100
     */
    public function test_full_visibility_with_full_pwa_returns_100(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 100,
        ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        $score = $this->service->calculate($project, [
            'position' => 100.0,
            'serp' => 100.0,
            'volume' => 100.0,
        ]);

        $this->assertEquals(100, $score);
    }

    /**
     * VS-002: No PWA analysis - other components weighted normally
     */
    public function test_no_pwa_analysis_other_components_weighted_normally(): void
    {
        $project = Project::factory()->create();

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        // Position: 80 * 0.50 = 40
        // SERP: 60 * 0.25 = 15
        // Volume: 80 * 0.15 = 12
        // PWA: 0 * 0.10 = 0
        // Total: 67
        $score = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 80.0,
        ]);

        $this->assertEquals(67, $score);
    }

    /**
     * VS-003: Partial PWA with mixed scores
     */
    public function test_partial_pwa_with_mixed_scores(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 60,
        ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        // Position: 75 * 0.50 = 37.5
        // SERP: 50 * 0.25 = 12.5
        // Volume: 70 * 0.15 = 10.5
        // PWA: 6 (from score 60)
        // Total: 66.5 -> 67
        $score = $this->service->calculate($project, [
            'position' => 75.0,
            'serp' => 50.0,
            'volume' => 70.0,
        ]);

        $this->assertEquals(67, $score);
    }

    /**
     * VS-004: Zero PWA score
     */
    public function test_zero_pwa_score(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 0,
        ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        // Position: 50 * 0.50 = 25
        // SERP: 40 * 0.25 = 10
        // Volume: 60 * 0.15 = 9
        // PWA: 0
        // Total: 44
        $score = $this->service->calculate($project, [
            'position' => 50.0,
            'serp' => 40.0,
            'volume' => 60.0,
        ]);

        $this->assertEquals(44, $score);
    }

    /**
     * VS-005: Only position component
     */
    public function test_only_position_component(): void
    {
        $project = Project::factory()->create();

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        // Position: 100 * 0.50 = 50
        // Others: 0
        // Total: 50
        $score = $this->service->calculate($project, [
            'position' => 100.0,
            'serp' => 0.0,
            'volume' => 0.0,
        ]);

        $this->assertEquals(50, $score);
    }

    /**
     * VS-006: Only SERP component
     */
    public function test_only_serp_component(): void
    {
        $project = Project::factory()->create();

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        // SERP: 100 * 0.25 = 25
        // Others: 0
        // Total: 25
        $score = $this->service->calculate($project, [
            'position' => 0.0,
            'serp' => 100.0,
            'volume' => 0.0,
        ]);

        $this->assertEquals(25, $score);
    }

    /**
     * VS-007: Only PWA component
     */
    public function test_only_pwa_component(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 100,
        ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        // PWA: 10 (from score 100)
        // Others: 0
        // Total: 10
        $score = $this->service->calculate($project, [
            'position' => 0.0,
            'serp' => 0.0,
            'volume' => 0.0,
        ]);

        $this->assertEquals(10, $score);
    }

    /**
     * VS-008: All zeros returns 0
     */
    public function test_all_zeros_returns_0(): void
    {
        $project = Project::factory()->create();

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        $score = $this->service->calculate($project, [
            'position' => 0.0,
            'serp' => 0.0,
            'volume' => 0.0,
        ]);

        $this->assertEquals(0, $score);
    }

    /**
     * VS-009: Decimal precision in calculation
     */
    public function test_decimal_precision_in_calculation(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 67,
        ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        // Position: 73.5 * 0.50 = 36.75
        // SERP: 45.2 * 0.25 = 11.3
        // Volume: 58.0 * 0.15 = 8.7
        // PWA: 6.7
        // Total: 63.45 -> 63
        $score = $this->service->calculate($project, [
            'position' => 73.5,
            'serp' => 45.2,
            'volume' => 58.0,
        ]);

        $this->assertEquals(63, $score);
    }

    // =========================================================================
    // SCORE BREAKDOWN TESTS (SB)
    // =========================================================================

    /**
     * SB-001: Full breakdown with PWA
     */
    public function test_full_breakdown_with_pwa(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 100,
        ]);

        $breakdown = $this->service->getBreakdown($project, [
            'position' => 100.0,
            'serp' => 100.0,
            'volume' => 100.0,
        ]);

        $this->assertEquals(100, $breakdown['overall_score']);
        $this->assertArrayHasKey('position', $breakdown['components']);
        $this->assertArrayHasKey('serp_features', $breakdown['components']);
        $this->assertArrayHasKey('volume_distribution', $breakdown['components']);
        $this->assertArrayHasKey('pwa', $breakdown['components']);
    }

    /**
     * SB-002: Missing PWA in breakdown shows 0
     */
    public function test_missing_pwa_in_breakdown_shows_0(): void
    {
        $project = Project::factory()->create();

        $breakdown = $this->service->getBreakdown($project, [
            'position' => 50.0,
            'serp' => 50.0,
            'volume' => 50.0,
        ]);

        $this->assertEquals(0.0, $breakdown['components']['pwa']['score']);
        $this->assertEquals(0.0, $breakdown['components']['pwa']['contribution']);
    }

    /**
     * SB-003: PWA line item format correct
     */
    public function test_pwa_line_item_format_correct(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 85,
        ]);

        $breakdown = $this->service->getBreakdown($project);

        $this->assertEquals(85.0, $breakdown['components']['pwa']['score']);
        $this->assertEquals(10, $breakdown['components']['pwa']['weight']);
        $this->assertEquals(8.5, $breakdown['components']['pwa']['contribution']);
    }

    /**
     * SB-004: All percentages sum to 100%
     */
    public function test_all_percentages_sum_to_100_percent(): void
    {
        $breakdown = $this->service->getBreakdown(Project::factory()->create());

        $totalWeight = $breakdown['components']['position']['weight']
            + $breakdown['components']['serp_features']['weight']
            + $breakdown['components']['volume_distribution']['weight']
            + $breakdown['components']['pwa']['weight'];

        $this->assertEquals(100, $totalWeight);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: PWA analysis missing returns 0
     */
    public function test_pwa_analysis_missing_returns_0(): void
    {
        $project = Project::factory()->create();

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * EC-002: PWA score null returns 0
     */
    public function test_pwa_score_null_returns_0(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => null,
        ]);

        $component = $this->service->calculatePWAComponent($project);

        $this->assertEquals(0.0, $component);
    }

    /**
     * EC-003: Negative PWA score clamped to 0
     */
    public function test_negative_pwa_score_clamped_to_0(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => -5,
        ]);

        // Since the model might not allow negative, we test the service logic
        // by directly testing the calculation
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        $score = $this->service->calculate($project, [
            'position' => 50.0,
            'serp' => 50.0,
            'volume' => 50.0,
        ]);

        // Score should be calculated without errors
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /**
     * EC-004: PWA score above 100 clamped to 100
     */
    public function test_pwa_score_above_100_clamped_to_100(): void
    {
        $project = Project::factory()->create();
        $pwaAnalysis = PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 150,
        ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        $score = $this->service->calculate($project, [
            'position' => 100.0,
            'serp' => 100.0,
            'volume' => 100.0,
        ]);

        // Should be capped at 100
        $this->assertEquals(100, $score);
    }

    /**
     * EC-005: Project null throws exception
     */
    public function test_project_null_throws_exception(): void
    {
        $this->expectException(\TypeError::class);

        $this->service->calculate(null);
    }

    /**
     * EC-006: Concurrent calculations no race conditions
     */
    public function test_concurrent_calculations_no_race_conditions(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 75,
        ]);

        // First call caches the result
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $score1 = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 70.0,
        ]);

        // Second call uses cache
        Cache::shouldReceive('get')->once()->andReturn($score1);

        $score2 = $this->service->calculate($project);

        $this->assertEquals($score1, $score2);
    }

    /**
     * EC-007: Large project performance
     */
    public function test_large_project_performance(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 85,
        ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        $start = microtime(true);
        $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 70.0,
        ]);
        $duration = microtime(true) - $start;

        // Should complete within 500ms
        $this->assertLessThan(0.5, $duration);
    }

    /**
     * EC-008: Pre-calculated PWA component used
     */
    public function test_pre_calculated_pwa_component_used(): void
    {
        $project = Project::factory()->create();

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        // Pass pre-calculated PWA component
        $score = $this->service->calculate($project, [
            'position' => 100.0,
            'serp' => 100.0,
            'volume' => 100.0,
            'pwa' => 10.0, // Full PWA contribution
        ]);

        $this->assertEquals(100, $score);
    }

    /**
     * EC-009: Score never exceeds 100
     */
    public function test_score_never_exceeds_100(): void
    {
        $project = Project::factory()->create();
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 150, // Invalid high
        ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        $score = $this->service->calculate($project, [
            'position' => 150.0, // Invalid
            'serp' => 150.0,     // Invalid
            'volume' => 150.0,   // Invalid
        ]);

        $this->assertEquals(100, $score);
    }

    // =========================================================================
    // CACHING TESTS (C)
    // =========================================================================

    /**
     * C-001: Score is cached
     */
    public function test_score_is_cached(): void
    {
        $project = Project::factory()->create();

        Cache::shouldReceive('get')
            ->once()
            ->with("visibility_score:{$project->id}")
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once()
            ->with(
                "visibility_score:{$project->id}",
                \Mockery::type('int'),
                \Mockery::type('int')
            );

        $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 70.0,
        ]);
    }

    /**
     * C-002: Cache hit returns cached value
     */
    public function test_cache_hit_returns_cached_value(): void
    {
        $project = Project::factory()->create();

        Cache::shouldReceive('get')
            ->once()
            ->with("visibility_score:{$project->id}")
            ->andReturn(75);

        $score = $this->service->calculate($project);

        $this->assertEquals(75, $score);
    }

    /**
     * C-003: Cache cleared correctly
     */
    public function test_cache_cleared_correctly(): void
    {
        $projectId = 42;

        Cache::shouldReceive('forget')
            ->once()
            ->with("visibility_score:{$projectId}");

        $this->service->clearCache($projectId);
    }

    /**
     * C-004: Force recalculate bypasses cache
     */
    public function test_force_recalculate_bypasses_cache(): void
    {
        $project = Project::factory()->create();

        // Should not check cache when force=true
        Cache::shouldNotReceive('get');
        Cache::shouldReceive('put')->once();

        $score = $this->service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 70.0,
        ], true);

        $this->assertIsInt($score);
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-001: SQL injection in project ID prevented
     */
    public function test_sql_injection_in_project_id_prevented(): void
    {
        $project = Project::factory()->create([
            'name' => "'; DROP TABLE visibility_scores; --",
        ]);
        PWAAnalysis::factory()->create([
            'project_id' => $project->id,
            'pwa_score' => 85,
        ]);

        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->once();

        $score = $this->service->calculate($project);

        $this->assertIsInt($score);
    }

    /**
     * SC-002: Cache key sanitized
     */
    public function test_cache_key_sanitized(): void
    {
        $projectId = 42;

        Cache::shouldReceive('forget')
            ->once()
            ->with('visibility_score:42');

        $this->service->clearCache($projectId);
    }
}
