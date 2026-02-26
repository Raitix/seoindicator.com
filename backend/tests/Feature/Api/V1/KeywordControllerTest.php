<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\Ranking;
use App\Models\SerpFeature;
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

    // ==================== SERP FEATURE FILTER TESTS ====================

    /**
     * Test filter by single SERP feature.
     */
    public function test_filter_by_single_serp_feature(): void
    {
        $keyword1 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'seo tools']);
        $keyword2 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'keyword tracker']);
        
        // Add SERP features
        SerpFeature::create([
            'keyword_id' => $keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        SerpFeature::create([
            'keyword_id' => $keyword2->id,
            'feature_type' => 'local_pack',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.keyword', 'seo tools');
    }

    /**
     * Test filter by SERP feature with present=false.
     */
    public function test_filter_by_serp_feature_present_false(): void
    {
        $keyword1 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'seo tools']);
        $keyword2 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'keyword tracker']);
        
        // Keyword1 HAS featured_snippet
        SerpFeature::create([
            'keyword_id' => $keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        // Keyword2 DOES NOT have featured_snippet
        SerpFeature::create([
            'keyword_id' => $keyword2->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet&present=false");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.keyword', 'keyword tracker');
    }

    /**
     * Test filter by multiple SERP features.
     */
    public function test_filter_by_multiple_serp_features(): void
    {
        $keyword1 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'seo tools']);
        $keyword2 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'keyword tracker']);
        $keyword3 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'backlink checker']);
        
        // Keyword1 has featured_snippet
        SerpFeature::create([
            'keyword_id' => $keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        // Keyword2 has local_pack
        SerpFeature::create([
            'keyword_id' => $keyword2->id,
            'feature_type' => 'local_pack',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        // Keyword3 has neither
        SerpFeature::create([
            'keyword_id' => $keyword3->id,
            'feature_type' => 'reviews',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet,local_pack");
        
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
        
        $keywords = collect($response->json('data'))->pluck('keyword')->toArray();
        $this->assertContains('seo tools', $keywords);
        $this->assertContains('keyword tracker', $keywords);
        $this->assertNotContains('backlink checker', $keywords);
    }

    /**
     * Test filter by SERP feature with no matches.
     */
    public function test_filter_by_serp_feature_no_matches(): void
    {
        $keyword1 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'seo tools']);
        
        // Keyword has featured_snippet, not knowledge_panel
        SerpFeature::create([
            'keyword_id' => $keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=knowledge_panel");
        
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test filter by SERP feature with status filter combined.
     */
    public function test_filter_by_serp_feature_with_status(): void
    {
        $keyword1 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'seo tools', 'status' => 'active']);
        $keyword2 = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => 'keyword tracker', 'status' => 'paused']);
        
        // Both have featured_snippet
        SerpFeature::create([
            'keyword_id' => $keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        SerpFeature::create([
            'keyword_id' => $keyword2->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet&status=active");
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.keyword', 'seo tools');
    }

    /**
     * Test filter by SERP feature with pagination.
     */
    public function test_filter_by_serp_feature_with_pagination(): void
    {
        // Create 25 keywords with featured_snippet
        for ($i = 0; $i < 25; $i++) {
            $keyword = Keyword::factory()->create(['site_id' => $this->site->id, 'keyword' => "keyword {$i}"]);
            SerpFeature::create([
                'keyword_id' => $keyword->id,
                'feature_type' => 'featured_snippet',
                'is_present' => true,
                'checked_at' => now(),
            ]);
        }
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet&page=2&perPage=10");
        
        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.perPage', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonCount(10, 'data');
    }

    /**
     * Test response includes serpFeatures object.
     */
    public function test_response_includes_serp_features(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        SerpFeature::create([
            'keyword_id' => $keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        SerpFeature::create([
            'keyword_id' => $keyword->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'serpFeatures' => [
                            'featured_snippet',
                            'local_pack',
                            'sitelinks',
                        ],
                    ],
                ],
            ]);
        
        $serpFeatures = $response->json('data.0.serpFeatures');
        $this->assertTrue($serpFeatures['featured_snippet']);
        $this->assertTrue($serpFeatures['sitelinks']);
        $this->assertFalse($serpFeatures['local_pack']); // Not present
    }

    /**
     * Test invalid SERP feature type returns 400.
     */
    public function test_invalid_serp_feature_type(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=invalid_type");
        
        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    /**
     * Test empty serpFeature parameter is ignored.
     */
    public function test_empty_serp_feature_parameter(): void
    {
        Keyword::factory()->count(3)->create(['site_id' => $this->site->id]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=");
        
        // Empty parameter should be ignored, return all keywords
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test present parameter variations.
     */
    public function test_present_parameter_variations(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        SerpFeature::create([
            'keyword_id' => $keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now(),
        ]);
        
        // Test present=true
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet&present=true");
        $response->assertStatus(200)->assertJsonCount(1, 'data');
        
        // Test present=1
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet&present=1");
        $response->assertStatus(200)->assertJsonCount(1, 'data');
        
        // Test present=0 (should return keywords WITHOUT the feature)
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet&present=0");
        $response->assertStatus(200)->assertJsonCount(0, 'data');
        
        // Test present=false
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet&present=false");
        $response->assertStatus(200)->assertJsonCount(0, 'data');
    }

    /**
     * Test SQL injection protection in serpFeature parameter.
     */
    public function test_sql_injection_in_serp_feature(): void
    {
        // Create a keyword so the table isn't empty
        Keyword::factory()->create(['site_id' => $this->site->id]);
        
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature='; DROP TABLE serp_features; --");
        
        // Should return 400 validation error, not 500 server error
        $this->assertNotEquals(500, $response->status());
        
        // Table should still exist
        $this->assertDatabaseHas('keywords', ['site_id' => $this->site->id]);
    }
}
