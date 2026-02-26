<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\SerpFeature;
use App\Models\SerpFeatureHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SerpFeatureControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Site $site;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();

        $this->site = Site::create([
            'url' => 'https://test-example.com',
            'name' => 'Test Site'
        ]);

        $this->keyword = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'seo tools',
            'target_url' => 'https://test-example.com/seo-tools',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active'
        ]);
    }

    // ============================================
    // GET /api/v1/keywords/:id/serp-features
    // ============================================

    /** @test */
    public function it_returns_current_features_for_keyword(): void
    {
        // Arrange: Create SERP features for keyword
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com/seo-tools',
            'snippet' => 'Best SEO tools for 2026...',
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'keyword' => ['id', 'keyword'],
                    'currentFeatures',
                    'history',
                    'changes'
                ]
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.keyword.id', $this->keyword->id)
            ->assertJsonPath('data.keyword.keyword', 'seo tools');
    }

    /** @test */
    public function it_returns_empty_features_for_keyword_with_no_data(): void
    {
        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.currentFeatures', [])
            ->assertJsonPath('data.history', []);
    }

    /** @test */
    public function it_filters_history_by_date_range(): void
    {
        // Arrange: Create feature history
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => '2026-02-15 10:00:00'
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => '2026-02-10 10:00:00'
        ]);

        // Act: Request with date range
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features?from=2026-02-14&to=2026-02-16");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // History should only contain entries within the date range
        $history = $response->json('data.history');
        $this->assertGreaterThanOrEqual(1, count($history));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_keyword(): void
    {
        // Act
        $response = $this->getJson('/api/v1/keywords/99999/serp-features');

        // Assert
        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function it_returns_400_for_invalid_keyword_id(): void
    {
        // Act
        $response = $this->getJson('/api/v1/keywords/abc/serp-features');

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function it_returns_400_for_invalid_date_format(): void
    {
        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features?from=invalid-date");

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function it_returns_400_when_from_date_after_to_date(): void
    {
        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features?from=2026-02-20&to=2026-02-01");

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function it_returns_changes_with_gained_and_lost(): void
    {
        // Arrange: Create feature history
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com/seo-tools',
            'detected_at' => now()
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'local_pack',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com/local',
            'detected_at' => now()->subDay()
        ]);

        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features");

        // Assert
        $response->assertStatus(200);

        $changes = $response->json('data.changes');
        $this->assertGreaterThanOrEqual(2, count($changes));
    }

    // ============================================
    // GET /api/v1/sites/:siteId/serp-features
    // ============================================

    /** @test */
    public function it_returns_all_features_for_site(): void
    {
        // Arrange: Create multiple keywords with features
        $keyword2 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword tracker',
            'status' => 'active'
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $keyword2->id,
            'feature_type' => 'local_pack',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['total', 'page', 'limit']
            ]);
    }

    /** @test */
    public function it_supports_pagination(): void
    {
        // Arrange: Create multiple keywords
        for ($i = 0; $i < 25; $i++) {
            $keyword = Keyword::create([
                'site_id' => $this->site->id,
                'keyword' => "keyword {$i}",
                'status' => 'active'
            ]);

            SerpFeature::create([
                'keyword_id' => $keyword->id,
                'feature_type' => 'featured_snippet',
                'is_present' => true,
                'checked_at' => now()
            ]);
        }

        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features?page=2&limit=10");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.limit', 10);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_site(): void
    {
        // Act
        $response = $this->getJson('/api/v1/sites/99999/serp-features');

        // Assert
        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function it_returns_400_for_invalid_pagination_params(): void
    {
        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features?page=-1");

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function it_returns_400_when_limit_exceeds_maximum(): void
    {
        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features?limit=1000");

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    // ============================================
    // GET /api/v1/sites/:siteId/serp-features/summary
    // ============================================

    /** @test */
    public function it_returns_feature_summary(): void
    {
        // Arrange
        $keyword2 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword tracker',
            'status' => 'active'
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $keyword2->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features/summary");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totalKeywords',
                    'keywordsWithFeatures',
                    'featureCounts',
                    'recentChanges'
                ]
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.totalKeywords', 2)
            ->assertJsonPath('data.keywordsWithFeatures', 2);
    }

    /** @test */
    public function it_returns_feature_counts_by_type(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()->subDay()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features/summary");

        // Assert
        $response->assertStatus(200);

        $featureCounts = $response->json('data.featureCounts');
        $this->assertArrayHasKey('featured_snippet', $featureCounts);
        $this->assertArrayHasKey('sitelinks', $featureCounts);
    }

    /** @test */
    public function it_returns_recent_changes(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => now()
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'local_pack',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com',
            'detected_at' => now()->subDay()
        ]);

        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features/summary");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'recentChanges' => [
                        'gained',
                        'lost'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_returns_empty_summary_for_site_with_no_keywords(): void
    {
        // Arrange: Create site with no keywords
        $emptySite = Site::create([
            'url' => 'https://empty-site.com',
            'name' => 'Empty Site'
        ]);

        // Act
        $response = $this->getJson("/api/v1/sites/{$emptySite->id}/serp-features/summary");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.totalKeywords', 0)
            ->assertJsonPath('data.keywordsWithFeatures', 0);
    }

    // ============================================
    // Security Tests
    // ============================================

    /** @test */
    public function it_sanitizes_sql_injection_in_keyword_id(): void
    {
        // Act
        $response = $this->getJson("/api/v1/keywords/1; DROP TABLE keywords;--/serp-features");

        // Assert: Should return 400 (validation error) not 500
        $response->assertStatus(400);
    }

    /** @test */
    public function it_sanitizes_sql_injection_in_date_params(): void
    {
        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword->id}/serp-features?from=2026-01-01'; DROP TABLE keywords;--");

        // Assert: Should return 400 (validation error)
        $response->assertStatus(400);
    }
}
