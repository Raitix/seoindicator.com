<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\VisibilityScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityScoreTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Creation Tests (MC)
    // ==========================================

    /** @test MC-001: Can create visibility score with all fields */
    public function test_mc_001_can_create_visibility_score_with_all_fields(): void
    {
        $project = Project::factory()->create();

        $score = VisibilityScore::factory()->create([
            'project_id' => $project->id,
            'score' => 75,
            'position_component' => 30.50,
            'serp_component' => 22.25,
            'volume_component' => 22.25,
            'keywords_count' => 150,
        ]);

        $this->assertDatabaseHas('visibility_scores', [
            'id' => $score->id,
            'project_id' => $project->id,
            'score' => 75,
            'position_component' => 30.50,
            'serp_component' => 22.25,
            'volume_component' => 22.25,
            'keywords_count' => 150,
        ]);
    }

    /** @test MC-002: Score must be between 0 and 100 */
    public function test_mc_002_score_must_be_between_0_and_100(): void
    {
        $score1 = VisibilityScore::factory()->withScore(0)->create();
        $this->assertEquals(0, $score1->score);

        $score2 = VisibilityScore::factory()->withScore(100)->create();
        $this->assertEquals(100, $score2->score);

        $score3 = VisibilityScore::factory()->withScore(50)->create();
        $this->assertEquals(50, $score3->score);
    }

    /** @test MC-003: Calculated at is automatically set */
    public function test_mc_003_calculated_at_is_automatically_set(): void
    {
        $score = VisibilityScore::factory()->create();

        $this->assertNotNull($score->calculated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $score->calculated_at);
    }

    // ==========================================
    // Relationship Tests (RL)
    // ==========================================

    /** @test RL-001: Visibility score belongs to project */
    public function test_rl_001_visibility_score_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $score = VisibilityScore::factory()->forProject($project)->create();

        $this->assertEquals($project->id, $score->project->id);
        $this->assertInstanceOf(Project::class, $score->project);
    }

    /** @test RL-002: Project has many visibility scores */
    public function test_rl_002_project_has_many_visibility_scores(): void
    {
        $project = Project::factory()->create();
        $score1 = VisibilityScore::factory()->forProject($project)->create();
        $score2 = VisibilityScore::factory()->forProject($project)->create();

        $this->assertCount(2, $project->visibilityScores);
        $this->assertTrue($project->visibilityScores->contains($score1));
        $this->assertTrue($project->visibilityScores->contains($score2));
    }

    /** @test RL-003: Deleting project cascades to visibility scores */
    public function test_rl_003_deleting_project_cascades_to_visibility_scores(): void
    {
        $project = Project::factory()->create();
        $score = VisibilityScore::factory()->forProject($project)->create();

        $project->delete();

        $this->assertDatabaseMissing('visibility_scores', [
            'id' => $score->id,
        ]);
    }

    // ==========================================
    // Scope Tests (SC)
    // ==========================================

    /** @test SC-001: Scope forProject filters by project */
    public function test_sc_001_scope_for_project_filters_by_project(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        VisibilityScore::factory()->forProject($project1)->count(3)->create();
        VisibilityScore::factory()->forProject($project2)->count(2)->create();

        $results = VisibilityScore::forProject($project1->id)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($score) => $this->assertEquals($project1->id, $score->project_id));
    }

    /** @test SC-002: Scope dateRange filters by date range */
    public function test_sc_002_scope_date_range_filters_by_date_range(): void
    {
        $project = Project::factory()->create();

        $oldScore = VisibilityScore::factory()->forProject($project)->calculatedAt('2026-01-01')->create();
        $newScore = VisibilityScore::factory()->forProject($project)->calculatedAt('2026-02-15')->create();

        $results = VisibilityScore::dateRange('2026-02-01', '2026-02-28')->get();

        $this->assertTrue($results->contains($newScore));
        $this->assertFalse($results->contains($oldScore));
    }

    /** @test SC-003: Scope latestPerProject returns one score per project */
    public function test_sc_003_scope_latest_per_project_returns_one_score_per_project(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        VisibilityScore::factory()->forProject($project1)->calculatedAt('2026-02-01')->create();
        $latest1 = VisibilityScore::factory()->forProject($project1)->calculatedAt('2026-02-15')->create();

        VisibilityScore::factory()->forProject($project2)->calculatedAt('2026-02-01')->create();
        $latest2 = VisibilityScore::factory()->forProject($project2)->calculatedAt('2026-02-20')->create();

        $results = VisibilityScore::latestPerProject()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains($latest1));
        $this->assertTrue($results->contains($latest2));
    }

    // ==========================================
    // Static Method Tests (SM)
    // ==========================================

    /** @test SM-001: Get latest for project returns latest score */
    public function test_sm_001_get_latest_for_project_returns_latest_score(): void
    {
        $project = Project::factory()->create();

        VisibilityScore::factory()->forProject($project)->calculatedAt('2026-02-01')->create();
        VisibilityScore::factory()->forProject($project)->calculatedAt('2026-02-10')->create();
        $latest = VisibilityScore::factory()->forProject($project)->calculatedAt('2026-02-20')->create();

        $result = VisibilityScore::getLatestForProject($project->id);

        $this->assertNotNull($result);
        $this->assertEquals($latest->id, $result->id);
    }

    /** @test SM-002: Get latest for project returns null when no scores */
    public function test_sm_002_get_latest_for_project_returns_null_when_no_scores(): void
    {
        $project = Project::factory()->create();

        $result = VisibilityScore::getLatestForProject($project->id);

        $this->assertNull($result);
    }

    /** @test SM-003: Get trend for project returns scores within days */
    public function test_sm_003_get_trend_for_project_returns_scores_within_days(): void
    {
        $project = Project::factory()->create();

        $oldScore = VisibilityScore::factory()->forProject($project)->calculatedAt(now()->subDays(60))->create();
        $newScore = VisibilityScore::factory()->forProject($project)->calculatedAt(now()->subDays(10))->create();

        $results = VisibilityScore::getTrendForProject($project->id, 30);

        $this->assertCount(1, $results);
        $this->assertEquals($newScore->id, $results[0]['id']);
    }

    // ==========================================
    // Factory State Tests (FS)
    // ==========================================

    /** @test FS-001: Factory high state creates high score */
    public function test_fs_001_factory_high_state_creates_high_score(): void
    {
        $score = VisibilityScore::factory()->high()->create();

        $this->assertGreaterThanOrEqual(70, $score->score);
        $this->assertLessThanOrEqual(100, $score->score);
    }

    /** @test FS-002: Factory medium state creates medium score */
    public function test_fs_002_factory_medium_state_creates_medium_score(): void
    {
        $score = VisibilityScore::factory()->medium()->create();

        $this->assertGreaterThanOrEqual(40, $score->score);
        $this->assertLessThanOrEqual(69, $score->score);
    }

    /** @test FS-003: Factory low state creates low score */
    public function test_fs_003_factory_low_state_creates_low_score(): void
    {
        $score = VisibilityScore::factory()->low()->create();

        $this->assertGreaterThanOrEqual(0, $score->score);
        $this->assertLessThanOrEqual(39, $score->score);
    }

    // ==========================================
    // Validation Tests (VL)
    // ==========================================

    /** @test VL-001: Component scores are properly cast as decimal */
    public function test_vl_001_component_scores_are_properly_cast_as_decimal(): void
    {
        $score = VisibilityScore::factory()->create([
            'position_component' => 30.55,
            'serp_component' => 25.75,
            'volume_component' => 20.25,
        ]);

        $this->assertEquals('30.55', $score->position_component);
        $this->assertEquals('25.75', $score->serp_component);
        $this->assertEquals('20.25', $score->volume_component);
    }

    /** @test VL-002: Calculated at is cast as datetime */
    public function test_vl_002_calculated_at_is_cast_as_datetime(): void
    {
        $score = VisibilityScore::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $score->calculated_at);
    }

    // ==========================================
    // Edge Cases (EC)
    // ==========================================

    /** @test EC-001: Can handle zero keywords count */
    public function test_ec_001_can_handle_zero_keywords_count(): void
    {
        $score = VisibilityScore::factory()->create(['keywords_count' => 0]);

        $this->assertEquals(0, $score->keywords_count);
        $this->assertDatabaseHas('visibility_scores', [
            'id' => $score->id,
            'keywords_count' => 0,
        ]);
    }

    /** @test EC-002: Multiple scores same day different projects allowed */
    public function test_ec_002_multiple_scores_same_day_different_projects_allowed(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $date = '2026-02-15 12:00:00';

        $score1 = VisibilityScore::factory()->forProject($project1)->calculatedAt($date)->create();
        $score2 = VisibilityScore::factory()->forProject($project2)->calculatedAt($date)->create();

        $this->assertNotEquals($score1->id, $score2->id);
        $this->assertEquals($project1->id, $score1->project_id);
        $this->assertEquals($project2->id, $score2->project_id);
    }
}
