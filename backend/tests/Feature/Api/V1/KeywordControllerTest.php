<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\Ranking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KeywordControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = Site::factory()->create();
    }

    // ==================== LIST TESTS ====================

    /**
     * Test list keywords returns all keywords.
     */
    public function test_list_keywords(): void
    {
        Keyword::factory()->count(5)->create(['site_id' => $this->site->id]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords");
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'keyword',
                        'targetUrl',
                        'searchEngine',
                        'location',
                        'device',
                        'status',
                    ],
                ],
                'meta' => ['total', 'page', 'perPage'],
            ]);
    }

    /**
     * Test list keywords with pagination.
     */
    public function test_list_keywords_with_pagination(): void
    {
        Keyword::factory()->count(25)->create(['site_id' => $this->site->id]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?page=2&perPage=10");
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.perPage', 10)
            ->assertJsonPath('meta.total', 25);
    }

    /**
     * Test list keywords filtered by status.
     */
    public function test_list_keywords_filter_by_status(): void
    {
        Keyword::factory()->count(3)->create(['site_id' => $this->site->id, 'status' => 'active']);
        Keyword::factory()->count(2)->create(['site_id' => $this->site->id, 'status' => 'paused']);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?status=active");
        
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test list keywords sorted by position.
     */
    public function test_list_keywords_sorted_by_position(): void
    {
        $keyword1 = Keyword::factory()->create(['site_id' => $this->site->id]);
        $keyword2 = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        Ranking::factory()->create(['keyword_id' => $keyword1->id, 'position' => 10]);
        Ranking::factory()->create(['keyword_id' => $keyword2->id, 'position' => 5]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?sortBy=position&sortOrder=asc");
        
        $response->assertStatus(200);
        
        $positions = collect($response->json('data'))->pluck('currentPosition')->toArray();
        $this->assertEquals([5, 10], $positions);
    }

    /**
     * Test list keywords for invalid site.
     */
    public function test_list_keywords_invalid_site(): void
    {
        $response = $this->getJson('/api/v1/sites/9999/keywords');
        
        $response->assertStatus(404);
    }

    /**
     * Test list keywords empty result.
     */
    public function test_list_keywords_empty(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords");
        
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // ==================== GET SINGLE TESTS ====================

    /**
     * Test get single keyword.
     */
    public function test_get_keyword(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        Ranking::factory()->create(['keyword_id' => $keyword->id, 'position' => 5]);
        
        $response = $this->getJson("/api/v1/keywords/{$keyword->id}");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $keyword->id)
            ->assertJsonPath('data.keyword', $keyword->keyword)
            ->assertJsonPath('data.currentPosition', 5);
    }

    /**
     * Test get keyword includes position change.
     */
    public function test_get_keyword_includes_position_change(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 10,
            'checked_at' => now()->subDay(),
        ]);
        
        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/keywords/{$keyword->id}");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.currentPosition', 5)
            ->assertJsonPath('data.previousPosition', 10)
            ->assertJsonPath('data.positionChange', -5);
    }

    /**
     * Test get keyword invalid ID.
     */
    public function test_get_keyword_invalid_id(): void
    {
        $response = $this->getJson('/api/v1/keywords/9999');
        
        $response->assertStatus(404);
    }

    // ==================== CREATE TESTS ====================

    /**
     * Test create keyword with valid data.
     */
    public function test_create_keyword(): void
    {
        $data = [
            'keyword' => 'test keyword',
            'targetUrl' => 'https://example.com/page',
            'searchEngine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
        ];
        
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/keywords", $data);
        
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.keyword', 'test keyword');
        
        $this->assertDatabaseHas('keywords', [
            'keyword' => 'test keyword',
            'site_id' => $this->site->id,
        ]);
    }

    /**
     * Test create keyword missing keyword field.
     */
    public function test_create_keyword_missing_keyword(): void
    {
        $data = [
            'targetUrl' => 'https://example.com',
        ];
        
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/keywords", $data);
        
        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    /**
     * Test create keyword invalid target URL.
     */
    public function test_create_keyword_invalid_url(): void
    {
        $data = [
            'keyword' => 'test',
            'targetUrl' => 'not-a-url',
        ];
        
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/keywords", $data);
        
        $response->assertStatus(400);
    }

    /**
     * Test create duplicate keyword.
     */
    public function test_create_duplicate_keyword(): void
    {
        Keyword::factory()->create([
            'site_id' => $this->site->id,
            'keyword' => 'duplicate',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
        ]);
        
        $data = [
            'keyword' => 'duplicate',
            'searchEngine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
        ];
        
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/keywords", $data);
        
        $response->assertStatus(409);
    }

    /**
     * Test create keyword for invalid site.
     */
    public function test_create_keyword_invalid_site(): void
    {
        $data = ['keyword' => 'test'];
        
        $response = $this->postJson('/api/v1/sites/9999/keywords', $data);
        
        $response->assertStatus(404);
    }

    // ==================== UPDATE TESTS ====================

    /**
     * Test update keyword target URL.
     */
    public function test_update_keyword_target_url(): void
    {
        $keyword = Keyword::factory()->create([
            'site_id' => $this->site->id,
            'target_url' => 'https://old-url.com',
        ]);
        
        $response = $this->putJson("/api/v1/keywords/{$keyword->id}", [
            'targetUrl' => 'https://new-url.com',
        ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.targetUrl', 'https://new-url.com');
    }

    /**
     * Test update keyword status.
     */
    public function test_update_keyword_status(): void
    {
        $keyword = Keyword::factory()->create([
            'site_id' => $this->site->id,
            'status' => 'active',
        ]);
        
        $response = $this->putJson("/api/v1/keywords/{$keyword->id}", [
            'status' => 'paused',
        ]);
        
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paused');
    }

    /**
     * Test update keyword invalid ID.
     */
    public function test_update_keyword_invalid_id(): void
    {
        $response = $this->putJson('/api/v1/keywords/9999', ['status' => 'paused']);
        
        $response->assertStatus(404);
    }

    /**
     * Test update keyword invalid URL.
     */
    public function test_update_keyword_invalid_url(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        $response = $this->putJson("/api/v1/keywords/{$keyword->id}", [
            'targetUrl' => 'not-a-url',
        ]);
        
        $response->assertStatus(400);
    }

    /**
     * Test update keyword empty payload.
     */
    public function test_update_keyword_empty_payload(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        $response = $this->putJson("/api/v1/keywords/{$keyword->id}", []);
        
        $response->assertStatus(400);
    }

    // ==================== DELETE TESTS ====================

    /**
     * Test delete keyword.
     */
    public function test_delete_keyword(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        $response = $this->deleteJson("/api/v1/keywords/{$keyword->id}");
        
        $response->assertStatus(204);
        $this->assertEquals('deleted', $keyword->fresh()->status);
    }

    /**
     * Test delete keyword keep history.
     */
    public function test_delete_keyword_keep_history(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        Ranking::factory()->count(3)->create(['keyword_id' => $keyword->id]);
        
        $response = $this->deleteJson("/api/v1/keywords/{$keyword->id}?keepHistory=true");
        
        $response->assertStatus(204);
        $this->assertEquals(3, $keyword->rankingHistory()->count());
    }

    /**
     * Test delete keyword remove history.
     */
    public function test_delete_keyword_remove_history(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        Ranking::factory()->count(3)->create(['keyword_id' => $keyword->id]);
        
        $response = $this->deleteJson("/api/v1/keywords/{$keyword->id}?keepHistory=false");
        
        $response->assertStatus(204);
        $this->assertDatabaseMissing('ranking_history', ['keyword_id' => $keyword->id]);
    }

    /**
     * Test delete keyword invalid ID.
     */
    public function test_delete_keyword_invalid_id(): void
    {
        $response = $this->deleteJson('/api/v1/keywords/9999');
        
        $response->assertStatus(404);
    }

    // ==================== SECURITY TESTS ====================

    /**
     * Test SQL injection protection.
     */
    public function test_sql_injection_protection(): void
    {
        $data = [
            'keyword' => "'; DROP TABLE keywords; --",
        ];
        
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/keywords", $data);
        
        // Should either succeed (sanitized) or fail validation
        // But should not cause database error
        $this->assertNotEquals(500, $response->status());
        
        // Table should still exist
        $this->assertDatabaseHas('keywords', ['site_id' => $this->site->id]);
    }

    /**
     * Test XSS protection in keyword field.
     */
    public function test_xss_protection(): void
    {
        $data = [
            'keyword' => '<script>alert("xss")</script>',
        ];
        
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/keywords", $data);
        
        if ($response->status() === 201) {
            $keyword = $response->json('data.keyword');
            $this->assertStringNotContainsString('<script>', $keyword);
        }
    }
}
