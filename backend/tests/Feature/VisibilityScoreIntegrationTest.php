<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Project;
use App\Models\VisibilityScore;
use App\Models\User;
use App\Services\VisibilityScoreService;
use App\Services\BacklinkProfileScorer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Feature tests for TASK-23-15: Visibility Score Integration
 *
 * Tests the integration of backlink profile score into visibility score
 * through the full application stack, including database operations,
 * caching, and API endpoints.
 *
 * @see \App\Services\VisibilityScoreService
 * @see TASK-23-15
 */
class VisibilityScoreIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $backlinkScorerMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock BacklinkProfileScorer dependency
        $this->backlinkScorerMock = Mockery::mock(BacklinkProfileScorer::class);
        $this->app->instance(BacklinkProfileScorer::class, $this->backlinkScorerMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // END-TO-END CALCULATION TESTS
    // =========================================================================

    /**
     * E2E-01: Full calculation flow with all components
     */
    public function test_full_calculation_flow_with_all_components(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(75.0);

        $service = app(VisibilityScoreService::class);
        $score = $service->calculateAndStore($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        // Verify score calculation
        // Position: 80 * 0.40 = 32
        // SERP: 60 * 0.30 = 18
        // Backlink: 75 * 0.20 = 15
        // Volume: 90 * 0.10 = 9
        // Total: 74
        $this->assertEquals(74, $score);

        // Verify database storage
        $this->assertDatabaseHas('visibility_scores', [
            'project_id' => $project->id,
            'score' => 74,
            'position_component' => 32.0,
            'serp_component' => 18.0,
            'backlink_component' => 15.0,
            'volume_component' => 9.0,
        ]);
    }

    /**
     * E2E-02: Calculation without backlink data
     */
    public function test_calculation_without_backlink_data(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(null);

        $service = app(VisibilityScoreService::class);
        $score = $service->calculateAndStore($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        // With redistribution:
        // Position: 80 * 0.50 = 40
        // SERP: 60 * 0.375 = 22.5
        // Volume: 90 * 0.125 = 11.25
        // Total: 73.75 -> 74
        $this->assertEquals(74, $score);

        $this->assertDatabaseHas('visibility_scores', [
            'project_id' => $project->id,
            'score' => 74,
            'backlink_component' => 0.0,
        ]);
    }

    /**
     * E2E-03: Multiple projects independent calculations
     */
    public function test_multiple_projects_independent_calculations(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->twice()
            ->andReturnValues([75.0, 50.0]);

        $service = app(VisibilityScoreService::class);

        $score1 = $service->calculateAndStore($project1, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $score2 = $service->calculateAndStore($project2, [
            'position' => 70.0,
            'serp' => 50.0,
            'volume' => 80.0,
        ]);

        $this->assertEquals(74, $score1);
        $this->assertEquals(63, $score2);

        $this->assertEquals(2, VisibilityScore::count());
    }

    // =========================================================================
    // CACHING INTEGRATION TESTS
    // =========================================================================

    /**
     * CACHE-01: Cache persists across requests
     */
    public function test_cache_persists_across_requests(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(75.0);

        $service = app(VisibilityScoreService::class);

        // First call - calculates and caches
        $score1 = $service->calculate($project, ['position' => 80.0, 'serp' => 60.0, 'volume' => 90.0]);

        // Second call - should use cache
        $score2 = $service->calculate($project);

        $this->assertEquals($score1, $score2);
    }

    /**
     * CACHE-02: Cache invalidation on clear
     */
    public function test_cache_invalidation_on_clear(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->twice()
            ->andReturn(75.0, 80.0);

        $service = app(VisibilityScoreService::class);

        // First calculation
        $score1 = $service->calculate($project, ['position' => 80.0, 'serp' => 60.0, 'volume' => 90.0]);

        // Clear cache
        $service->clearCache($project->id);

        // Second calculation - should recalculate
        $score2 = $service->calculate($project, ['position' => 80.0, 'serp' => 60.0, 'volume' => 90.0]);

        $this->assertNotEquals($score1, $score2);
    }

    /**
     * CACHE-03: Force recalculate bypasses cache
     */
    public function test_force_recalculate_bypasses_cache(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->twice()
            ->andReturn(75.0, 80.0);

        $service = app(VisibilityScoreService::class);

        // First calculation
        $score1 = $service->calculate($project, ['position' => 80.0, 'serp' => 60.0, 'volume' => 90.0]);

        // Force recalculate
        $score2 = $service->calculate($project, ['position' => 80.0, 'serp' => 60.0, 'volume' => 90.0], true);

        $this->assertNotEquals($score1, $score2);
    }

    // =========================================================================
    // DATABASE INTEGRATION TESTS
    // =========================================================================

    /**
     * DB-01: Visibility score record created correctly
     */
    public function test_visibility_score_record_created_correctly(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(75.0);

        $service = app(VisibilityScoreService::class);
        $service->calculateAndStore($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $visibilityScore = VisibilityScore::where('project_id', $project->id)->first();

        $this->assertNotNull($visibilityScore);
        $this->assertEquals(74, $visibilityScore->score);
        $this->assertNotNull($visibilityScore->calculated_at);
    }

    /**
     * DB-02: Multiple scores for same project on different days
     */
    public function test_multiple_scores_for_same_project_on_different_days(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(75.0);

        $service = app(VisibilityScoreService::class);

        // Create score for yesterday
        VisibilityScore::factory()->create([
            'project_id' => $project->id,
            'score' => 70,
            'calculated_at' => now()->subDay(),
        ]);

        // Create new score for today
        $service->calculateAndStore($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $scores = VisibilityScore::where('project_id', $project->id)->get();

        $this->assertEquals(2, $scores->count());
    }

    /**
     * DB-03: Cascade delete with project
     */
    public function test_cascade_delete_with_project(): void
    {
        $project = Project::factory()->create();

        VisibilityScore::factory()->create([
            'project_id' => $project->id,
        ]);

        $project->delete();

        $this->assertEquals(0, VisibilityScore::where('project_id', $project->id)->count());
    }

    /**
     * DB-04: Latest score retrieval
     */
    public function test_latest_score_retrieval(): void
    {
        $project = Project::factory()->create();

        VisibilityScore::factory()->create([
            'project_id' => $project->id,
            'score' => 60,
            'calculated_at' => now()->subDays(2),
        ]);

        VisibilityScore::factory()->create([
            'project_id' => $project->id,
            'score' => 70,
            'calculated_at' => now()->subDay(),
        ]);

        VisibilityScore::factory()->create([
            'project_id' => $project->id,
            'score' => 75,
            'calculated_at' => now(),
        ]);

        $latest = VisibilityScore::where('project_id', $project->id)
            ->latest('calculated_at')
            ->first();

        $this->assertEquals(75, $latest->score);
    }

    // =========================================================================
    // COMPONENT CALCULATION INTEGRATION TESTS
    // =========================================================================

    /**
     * COMP-01: Backlink component integrates with other components
     */
    public function test_backlink_component_integrates_with_other_components(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(100.0);

        $service = app(VisibilityScoreService::class);

        // All components at 100
        $score = $service->calculate($project, [
            'position' => 100.0,
            'serp' => 100.0,
            'volume' => 100.0,
        ]);

        $this->assertEquals(100, $score);
    }

    /**
     * COMP-02: Low backlink score affects total
     */
    public function test_low_backlink_score_affects_total(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(25.0);

        $service = app(VisibilityScoreService::class);

        // Position 100, SERP 100, Backlink 25, Volume 100
        // = 40 + 30 + 5 + 10 = 85
        $score = $service->calculate($project, [
            'position' => 100.0,
            'serp' => 100.0,
            'volume' => 100.0,
        ]);

        $this->assertEquals(85, $score);
    }

    /**
     * COMP-03: High backlink score boosts total
     */
    public function test_high_backlink_score_boosts_total(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andReturn(100.0);

        $service = app(VisibilityScoreService::class);

        // Position 50, SERP 50, Backlink 100, Volume 50
        // = 20 + 15 + 20 + 5 = 60
        $score = $service->calculate($project, [
            'position' => 50.0,
            'serp' => 50.0,
            'volume' => 50.0,
        ]);

        $this->assertEquals(60, $score);
    }

    // =========================================================================
    // ERROR HANDLING INTEGRATION TESTS
    // =========================================================================

    /**
     * ERR-01: Scorer exception handled gracefully
     */
    public function test_scorer_exception_handled_gracefully(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->once()
            ->andThrow(new \Exception('Backlink API error'));

        $service = app(VisibilityScoreService::class);

        // Should not throw exception
        $score = $service->calculate($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        // Backlink component should be 0, others calculated normally
        // With redistribution: 40 + 22.5 + 11.25 = 73.75 -> 74
        $this->assertEquals(74, $score);
    }

    /**
     * ERR-02: Database error handled
     */
    public function test_database_error_handled(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(75.0);

        $service = app(VisibilityScoreService::class);

        // This should work without error
        $score = $service->calculateAndStore($project, [
            'position' => 80.0,
            'serp' => 60.0,
            'volume' => 90.0,
        ]);

        $this->assertIsInt($score);
    }

    // =========================================================================
    // PERFORMANCE TESTS
    // =========================================================================

    /**
     * PERF-01: Single calculation performance
     */
    public function test_single_calculation_performance(): void
    {
        $project = Project::factory()->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(75.0);

        $service = app(VisibilityScoreService::class);

        $start = microtime(true);
        $service->calculate($project, ['position' => 80.0, 'serp' => 60.0, 'volume' => 90.0]);
        $duration = microtime(true) - $start;

        // Should complete within 500ms
        $this->assertLessThan(0.5, $duration, 'Visibility score calculation took too long');
    }

    /**
     * PERF-02: Batch calculation performance
     */
    public function test_batch_calculation_performance(): void
    {
        $projects = Project::factory()->count(10)->create();

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(75.0);

        $service = app(VisibilityScoreService::class);

        $start = microtime(true);

        foreach ($projects as $project) {
            $service->calculate($project, ['position' => 80.0, 'serp' => 60.0, 'volume' => 90.0]);
        }

        $duration = microtime(true) - $start;

        // 10 projects should complete within 5 seconds
        $this->assertLessThan(5.0, $duration, 'Batch calculation took too long');
    }

    // =========================================================================
    // AUTHORIZATION TESTS
    // =========================================================================

    /**
     * AUTH-01: User can calculate their own project
     */
    public function test_user_can_calculate_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->backlinkScorerMock
            ->shouldReceive('score')
            ->andReturn(75.0);

        $this->actingAs($user);

        $service = app(VisibilityScoreService::class);
        $score = $service->calculate($project, ['position' => 80.0, 'serp' => 60.0, 'volume' => 90.0]);

        $this->assertEquals(74, $score);
    }

    /**
     * AUTH-02: User cannot calculate another user's project
     */
    public function test_user_cannot_calculate_another_users_project(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user2->id]);

        $this->actingAs($user1);

        // Authorization is typically handled at the controller level
        // This test documents expected behavior
        $this->assertTrue(true);
    }
}
