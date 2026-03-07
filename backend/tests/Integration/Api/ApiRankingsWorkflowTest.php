<?php

namespace Tests\Integration\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\SerpFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for TASK-6-20: Rankings API Workflow
 *
 * Tests the rankings API including:
 * - Fetch rankings for keyword
 * - Rankings history
 * - Position changes
 * - SERP features
 */
class ApiRankingsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create(['project_id' => $this->project->id]);
    }

    // =========================================================================
    // WORKFLOW TESTS (WF)
    // =========================================================================

    /**
     * WF-001: Full rankings workflow
     * Create rankings -> Fetch -> Verify history -> Track changes
     */
    public function test_full_rankings_workflow(): void
    {
        // Create ranking history
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 50,
            'checked_at' => now()->subDays(7),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 30,
            'checked_at' => now()->subDays(3),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 15,
            'checked_at' => now()->subDay(),
        ]);

        $latestRanking = Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 10,
            'checked_at' => now(),
        ]);

        // Fetch rankings
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');

        // Verify latest position
        $keywordResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}");

        $keywordResponse->assertStatus(200)
            ->assertJsonPath('data.currentPosition', 10)
            ->assertJsonPath('data.previousPosition', 15)
            ->assertJsonPath('data.positionChange', -5); // Improved by 5 positions
    }

    /**
     * WF-002: Rankings with SERP features
     */
    public function test_rankings_with_serp_features(): void
    {
        // Create ranking
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now(),
        ]);

        // Add SERP features
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'people_also_ask',
            'is_present' => true,
            'checked_at' => now(),
        ]);

        // Fetch rankings with SERP features
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}?include=serpFeatures");

        $response->assertStatus(200);

        $serpFeatures = $response->json('data.serpFeatures');
        $this->assertTrue($serpFeatures['featured_snippet']);
        $this->assertTrue($serpFeatures['people_also_ask']);
    }

    /**
     * WF-003: Position improvement tracking
     */
    public function test_position_improvement_tracking(): void
    {
        // Simulate steady improvement over time
        $positions = [100, 80, 60, 40, 20, 10, 5, 3];

        foreach ($positions as $index => $position) {
            Ranking::factory()->create([
                'keyword_id' => $this->keyword->id,
                'position' => $position,
                'checked_at' => now()->subDays(count($positions) - $index - 1),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.currentPosition', 3);

        // Calculate total improvement (100 -> 3 = 97 positions)
        $totalImprovement = 100 - 3;

        // The position change should be from 5 to 3 (last two positions)
        $change = $response->json('data.positionChange');
        $this->assertEquals(-2, $change); // Improved by 2 positions (5 -> 3)
    }

    /**
     * WF-004: Position decline tracking
     */
    public function test_position_decline_tracking(): void
    {
        // Simulate declining positions
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now()->subDays(2),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 15,
            'checked_at' => now()->subDay(),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 25,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.currentPosition', 25);

        // Position worsened
        $change = $response->json('data.positionChange');
        $this->assertEquals(10, $change); // Dropped by 10 positions (15 -> 25)
    }

    // =========================================================================
    // RANKINGS LIST TESTS (RL)
    // =========================================================================

    /**
     * RL-001: List all rankings for keyword
     */
    public function test_list_all_rankings_for_keyword(): void
    {
        Ranking::factory()->count(10)->create([
            'keyword_id' => $this->keyword->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }

    /**
     * RL-002: Rankings ordered by date desc
     */
    public function test_rankings_ordered_by_date_desc(): void
    {
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 10,
            'checked_at' => now()->subDays(2),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now()->subDay(),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 3,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200);

        $positions = collect($response->json('data'))->pluck('position')->toArray();
        $this->assertEquals([3, 5, 10], $positions); // Most recent first
    }

    /**
     * RL-003: Rankings pagination
     */
    public function test_rankings_pagination(): void
    {
        Ranking::factory()->count(50)->create([
            'keyword_id' => $this->keyword->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?perPage=20&page=1");

        $response->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 50);
    }

    /**
     * RL-004: Rankings for date range
     */
    public function test_rankings_for_date_range(): void
    {
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'checked_at' => now()->subDays(10),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'checked_at' => now()->subDays(5),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'checked_at' => now()->subDays(2),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?from=" . now()->subDays(6)->toDateString() . "&to=" . now()->toDateString());

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data'); // Only last 3 within range
    }

    /**
     * RL-005: Empty rankings list
     */
    public function test_empty_rankings_list(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // =========================================================================
    // PROJECT RANKINGS TESTS (PR)
    // =========================================================================

    /**
     * PR-001: All project rankings
     */
    public function test_all_project_rankings(): void
    {
        $keyword2 = Keyword::factory()->create(['project_id' => $this->project->id]);

        Ranking::factory()->count(5)->create(['keyword_id' => $this->keyword->id]);
        Ranking::factory()->count(3)->create(['keyword_id' => $keyword2->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/rankings");

        $response->assertStatus(200);
    }

    /**
     * PR-002: Project rankings summary
     */
    public function test_project_rankings_summary(): void
    {
        $keyword2 = Keyword::factory()->create(['project_id' => $this->project->id]);

        // Create rankings with various positions
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now(),
        ]);
        Ranking::factory()->create([
            'keyword_id' => $keyword2->id,
            'position' => 25,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/rankings/summary");

        $response->assertStatus(200);
    }

    // =========================================================================
    // SERP FEATURE TESTS (SF)
    // =========================================================================

    /**
     * SF-001: Rankings include SERP features
     */
    public function test_rankings_include_serp_features(): void
    {
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'checked_at' => now(),
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?include=serpFeatures");

        $response->assertStatus(200);
    }

    /**
     * SF-002: SERP feature history
     */
    public function test_serp_feature_history(): void
    {
        // Feature appeared on day 1
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()->subDays(5),
        ]);

        // Feature appeared on day 3
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()->subDays(3),
        ]);

        // Feature still present on day 5
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features");

        $response->assertStatus(200);
    }

    // =========================================================================
    // AUTHORIZATION TESTS (AZ)
    // =========================================================================

    /**
     * AZ-001: Cannot view another user's rankings
     */
    public function test_cannot_view_another_users_rankings(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(403);
    }

    /**
     * AZ-002: Unauthenticated access denied
     */
    public function test_unauthenticated_access_denied(): void
    {
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");
        $response->assertStatus(401);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Position 1 (best position)
     */
    public function test_position_one(): void
    {
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 1,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.currentPosition', 1);
    }

    /**
     * EC-002: Position 100+ (poor position)
     */
    public function test_position_over_100(): void
    {
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 150,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.currentPosition', 150);
    }

    /**
     * EC-003: No change in position
     */
    public function test_no_change_in_position(): void
    {
        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 10,
            'checked_at' => now()->subDay(),
        ]);

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 10,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.positionChange', 0);
    }

    /**
     * EC-004: Same position multiple days
     */
    public function test_same_position_multiple_days(): void
    {
        for ($i = 0; $i < 7; $i++) {
            Ranking::factory()->create([
                'keyword_id' => $this->keyword->id,
                'position' => 15,
                'checked_at' => now()->subDays(6 - $i),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonCount(7, 'data');

        // All positions should be 15
        $positions = collect($response->json('data'))->pluck('position')->unique();
        $this->assertEquals([15], $positions->toArray());
    }

    // =========================================================================
    // PERFORMANCE TESTS (PF)
    // =========================================================================

    /**
     * PF-001: Large rankings dataset
     */
    public function test_large_rankings_dataset(): void
    {
        // Create 365 days of rankings
        for ($i = 0; $i < 365; $i++) {
            Ranking::factory()->create([
                'keyword_id' => $this->keyword->id,
                'position' => rand(1, 100),
                'checked_at' => now()->subDays(364 - $i),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/rankings?perPage=100");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 365);
    }

    /**
     * PF-002: Multiple keywords with rankings
     */
    public function test_multiple_keywords_with_rankings(): void
    {
        $keywords = Keyword::factory()->count(10)->create(['project_id' => $this->project->id]);

        foreach ($keywords as $keyword) {
            Ranking::factory()->count(30)->create(['keyword_id' => $keyword->id]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/rankings");

        $response->assertStatus(200);
    }
}
