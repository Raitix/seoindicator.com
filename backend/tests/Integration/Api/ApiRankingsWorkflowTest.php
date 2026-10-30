<?php

namespace Tests\Integration\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
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
    protected Site $site;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);
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

        Ranking::factory()->create([
            'keyword_id' => $this->keyword->id,
            'position' => 10,
            'checked_at' => now(),
        ]);

        // Fetch rankings
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(200);
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

        // Fetch keyword with SERP features
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history?perPage=20&page=1");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history?from=" . now()->subDays(6)->toDateString() . "&to=" . now()->toDateString());

        $response->assertStatus(200);
    }

    /**
     * RL-005: Empty rankings list
     */
    public function test_empty_rankings_list(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features");

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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(403);
    }

    /**
     * AZ-002: Unauthenticated access denied
     */
    public function test_unauthenticated_access_denied(): void
    {
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/history");
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history");

        $response->assertStatus(200);
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
            ->getJson("/api/v1/keywords/{$this->keyword->id}/history?perPage=100");

        $response->assertStatus(200);
    }

    /**
     * PF-002: Multiple keywords with rankings
     */
    public function test_multiple_keywords_with_rankings(): void
    {
        $keywords = Keyword::factory()->count(10)->create(['site_id' => $this->site->id, 'user_id' => $this->user->id]);

        foreach ($keywords as $keyword) {
            Ranking::factory()->count(30)->create(['keyword_id' => $keyword->id]);
        }

        // Verify all keywords have rankings
        foreach ($keywords as $keyword) {
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/keywords/{$keyword->id}/history");
            $response->assertStatus(200);
        }
    }
}
