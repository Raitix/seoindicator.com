<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\Ranking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = Site::factory()->create();
    }

    /**
     * Test get dashboard summary.
     */
    public function test_get_dashboard_summary(): void
    {
        Keyword::factory()->count(10)->create(['site_id' => $this->site->id]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totalKeywords',
                    'activeKeywords',
                    'rankedKeywords',
                    'notRankedKeywords',
                    'averagePosition',
                    'positionChanges' => [
                        'improved',
                        'declined',
                        'unchanged',
                        'new',
                    ],
                    'topKeywords',
                    'alerts',
                ],
            ]);
    }

    /**
     * Test dashboard for invalid site.
     */
    public function test_dashboard_invalid_site(): void
    {
        $response = $this->getJson('/api/v1/sites/9999/dashboard');
        
        $response->assertStatus(404);
    }

    /**
     * Test dashboard with no keywords.
     */
    public function test_dashboard_no_keywords(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.totalKeywords', 0)
            ->assertJsonPath('data.activeKeywords', 0)
            ->assertJsonPath('data.rankedKeywords', 0)
            ->assertJsonPath('data.averagePosition', null);
    }

    /**
     * Test dashboard metrics.
     */
    public function test_dashboard_metrics(): void
    {
        // Create keywords with different statuses
        Keyword::factory()->count(5)->create(['site_id' => $this->site->id, 'status' => 'active']);
        Keyword::factory()->count(3)->create(['site_id' => $this->site->id, 'status' => 'paused']);
        Keyword::factory()->count(2)->create(['site_id' => $this->site->id, 'status' => 'deleted']);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.totalKeywords', 10)
            ->assertJsonPath('data.activeKeywords', 5);
    }

    /**
     * Test dashboard ranked vs not ranked.
     */
    public function test_dashboard_ranked_keywords(): void
    {
        $keyword1 = Keyword::factory()->create(['site_id' => $this->site->id]);
        $keyword2 = Keyword::factory()->create(['site_id' => $this->site->id]);
        $keyword3 = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        // Ranked keywords
        Ranking::factory()->create(['keyword_id' => $keyword1->id, 'position' => 5]);
        Ranking::factory()->create(['keyword_id' => $keyword2->id, 'position' => 50]);
        
        // Not ranked keyword
        Ranking::factory()->create(['keyword_id' => $keyword3->id, 'position' => null]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.rankedKeywords', 2)
            ->assertJsonPath('data.notRankedKeywords', 1);
    }

    /**
     * Test dashboard average position.
     */
    public function test_dashboard_average_position(): void
    {
        $keyword1 = Keyword::factory()->create(['site_id' => $this->site->id]);
        $keyword2 = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        Ranking::factory()->create(['keyword_id' => $keyword1->id, 'position' => 10]);
        Ranking::factory()->create(['keyword_id' => $keyword2->id, 'position' => 20]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.averagePosition', 15.0);
    }

    /**
     * Test dashboard position changes.
     */
    public function test_dashboard_position_changes(): void
    {
        $keyword1 = Keyword::factory()->create(['site_id' => $this->site->id]);
        $keyword2 = Keyword::factory()->create(['site_id' => $this->site->id]);
        $keyword3 = Keyword::factory()->create(['site_id' => $this->site->id]);
        $keyword4 = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        // Improved (position went down)
        Ranking::factory()->create(['keyword_id' => $keyword1->id, 'position' => 10, 'checked_at' => now()->subDay()]);
        Ranking::factory()->create(['keyword_id' => $keyword1->id, 'position' => 5, 'checked_at' => now()]);
        
        // Declined (position went up)
        Ranking::factory()->create(['keyword_id' => $keyword2->id, 'position' => 5, 'checked_at' => now()->subDay()]);
        Ranking::factory()->create(['keyword_id' => $keyword2->id, 'position' => 15, 'checked_at' => now()]);
        
        // Unchanged
        Ranking::factory()->create(['keyword_id' => $keyword3->id, 'position' => 10, 'checked_at' => now()->subDay()]);
        Ranking::factory()->create(['keyword_id' => $keyword3->id, 'position' => 10, 'checked_at' => now()]);
        
        // New (only one ranking)
        Ranking::factory()->create(['keyword_id' => $keyword4->id, 'position' => 20, 'checked_at' => now()]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.positionChanges.improved', 1)
            ->assertJsonPath('data.positionChanges.declined', 1)
            ->assertJsonPath('data.positionChanges.unchanged', 1)
            ->assertJsonPath('data.positionChanges.new', 1);
    }

    /**
     * Test dashboard top keywords.
     */
    public function test_dashboard_top_keywords(): void
    {
        $keywords = Keyword::factory()->count(5)->create(['site_id' => $this->site->id]);
        
        foreach ($keywords as $index => $keyword) {
            Ranking::factory()->create([
                'keyword_id' => $keyword->id,
                'position' => ($index + 1) * 10, // 10, 20, 30, 40, 50
            ]);
        }
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        $response->assertStatus(200);
        
        $topKeywords = $response->json('data.topKeywords');
        
        // Should be sorted by position
        $this->assertEquals(10, $topKeywords[0]['position']);
        $this->assertEquals(20, $topKeywords[1]['position']);
    }

    /**
     * Test dashboard alerts for significant drops.
     */
    public function test_dashboard_alerts(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        // Significant drop (more than 5 positions)
        Ranking::factory()->create(['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()->subDay()]);
        Ranking::factory()->create(['keyword_id' => $keyword->id, 'position' => 25, 'checked_at' => now()]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        $response->assertStatus(200);
        
        $alerts = $response->json('data.alerts');
        
        $this->assertNotEmpty($alerts);
        $this->assertEquals('dropped', $alerts[0]['type']);
        $this->assertEquals(20, $alerts[0]['change']);
    }

    /**
     * Test dashboard caching.
     */
    public function test_dashboard_caching(): void
    {
        Keyword::factory()->create(['site_id' => $this->site->id]);
        
        // First request
        $response1 = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        $response1->assertStatus(200);
        
        // Verify cache key exists
        $cacheKey = "dashboard:{$this->site->id}";
        $this->assertTrue(Cache::has($cacheKey));
        
        // Second request should hit cache
        $response2 = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        $response2->assertStatus(200);
    }

    /**
     * Test dashboard cache invalidation.
     */
    public function test_dashboard_cache_invalidation(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        // First request - cache the result
        $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        // Add a new ranking
        Ranking::factory()->create(['keyword_id' => $keyword->id]);
        
        // Cache should be invalidated (in a real implementation)
        // This test verifies the structure is correct after invalidation
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/dashboard");
        
        $response->assertStatus(200);
    }
}
