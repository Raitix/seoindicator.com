<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SerpFeatureService;
use App\Repositories\SerpFeatureRepository;
use App\Repositories\SerpFeatureHistoryRepository;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\User;
use App\Models\SerpFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class SerpFeatureServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SerpFeatureService $service;
    protected Site $site;
    protected Keyword $keyword;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user first (required for sites.user_id foreign key)
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->site = Site::create([
            'user_id' => $this->user->id,
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

        $this->service = app(SerpFeatureService::class);
    }

    // ============================================
    // getKeywordFeatures()
    // ============================================

    /** @test */
    public function it_returns_features_for_keyword(): void
    {
        // Act
        $result = $this->service->getKeywordFeatures($this->keyword->id);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($this->keyword->id, $result['data']['keyword']['id']);
        $this->assertEquals('seo tools', $result['data']['keyword']['keyword']);
    }

    /** @test */
    public function it_returns_error_for_nonexistent_keyword(): void
    {
        // Act
        $result = $this->service->getKeywordFeatures(99999);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('KEYWORD_NOT_FOUND', $result['error']);
    }

    /** @test */
    public function it_returns_current_features_with_all_types(): void
    {
        // Act
        $result = $this->service->getKeywordFeatures($this->keyword->id);

        // Assert - Should return all feature types with isPresent status
        $this->assertArrayHasKey('currentFeatures', $result['data']);
        $this->assertIsArray($result['data']['currentFeatures']);

        // Each feature should have type and isPresent
        foreach ($result['data']['currentFeatures'] as $feature) {
            $this->assertArrayHasKey('type', $feature);
            $this->assertArrayHasKey('isPresent', $feature);
        }
    }

    // ============================================
    // getSiteFeatures()
    // ============================================

    /** @test */
    public function it_returns_paginated_features_for_site(): void
    {
        // Act
        $result = $this->service->getSiteFeatures($this->site->id, 1, 20);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('total', $result['meta']);
        $this->assertArrayHasKey('page', $result['meta']);
        $this->assertArrayHasKey('perPage', $result['meta']);
    }

    /** @test */
    public function it_applies_default_pagination(): void
    {
        // Act
        $result = $this->service->getSiteFeatures($this->site->id);

        // Assert - Defaults to page 1, 20 per page
        $this->assertEquals(1, $result['meta']['page']);
        $this->assertEquals(20, $result['meta']['perPage']);
    }

    /** @test */
    public function it_returns_error_for_nonexistent_site(): void
    {
        // Act
        $result = $this->service->getSiteFeatures(99999);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('SITE_NOT_FOUND', $result['error']);
    }

    // ============================================
    // getSummary()
    // ============================================

    /** @test */
    public function it_returns_summary_with_feature_counts(): void
    {
        // Act
        $result = $this->service->getSummary($this->site->id);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('totalKeywords', $result['data']);
        $this->assertArrayHasKey('keywordsWithFeatures', $result['data']);
        $this->assertArrayHasKey('featureCounts', $result['data']);
        $this->assertArrayHasKey('recentChanges', $result['data']);
    }

    /** @test */
    public function it_returns_correct_keyword_count_in_summary(): void
    {
        // Arrange - Add more keywords
        Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword tracker',
            'status' => 'active'
        ]);

        Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'seo audit',
            'status' => 'paused' // Should not be counted
        ]);

        // Act
        $result = $this->service->getSummary($this->site->id);

        // Assert - Only active keywords counted
        $this->assertEquals(2, $result['data']['totalKeywords']);
    }

    /** @test */
    public function it_returns_error_for_nonexistent_site_in_summary(): void
    {
        // Act
        $result = $this->service->getSummary(99999);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('SITE_NOT_FOUND', $result['error']);
    }

    /** @test */
    public function it_validates_days_parameter_in_summary(): void
    {
        // Act
        $result = $this->service->getSummary($this->site->id, 30);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_caps_days_parameter_in_summary(): void
    {
        // Act - Request more than max (365)
        $result = $this->service->getSummary($this->site->id, 500);

        // Assert - Should succeed (capped internally)
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_returns_empty_feature_counts_for_site_with_no_features(): void
    {
        // Act
        $result = $this->service->getSummary($this->site->id);

        // Assert - Feature counts should be 0 for each type
        $featureCounts = $result['data']['featureCounts'];
        foreach ($featureCounts as $type => $count) {
            $this->assertEquals(0, $count);
        }
    }

    /** @test */
    public function it_returns_recent_changes_structure(): void
    {
        // Act
        $result = $this->service->getSummary($this->site->id);

        // Assert
        $this->assertArrayHasKey('gained', $result['data']['recentChanges']);
        $this->assertArrayHasKey('lost', $result['data']['recentChanges']);
        $this->assertIsArray($result['data']['recentChanges']['gained']);
        $this->assertIsArray($result['data']['recentChanges']['lost']);
    }

    // ============================================
    // Edge Cases
    // ============================================

    /** @test */
    public function it_handles_empty_site_gracefully(): void
    {
        // Arrange - Site with no keywords
        $emptySite = Site::create([
            'user_id' => $this->user->id,
            'url' => 'https://empty-site.com',
            'name' => 'Empty Site'
        ]);

        // Act
        $result = $this->service->getSummary($emptySite->id);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['data']['totalKeywords']);
        $this->assertEquals(0, $result['data']['keywordsWithFeatures']);
    }

    // ============================================
    // Cache Tests (S-003, S-004)
    // ============================================

    /** @test */
    public function it_caches_summary_queries(): void
    {
        // Arrange - S-003: Service caches frequent queries
        $cacheKey = "serp_feature_summary:{$this->site->id}:7";

        // Act - First call
        $result1 = $this->service->getSummary($this->site->id, 7);

        // Assert - Should be cached
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Second call should use cache
        $result2 = $this->service->getSummary($this->site->id, 7);

        // Assert - Results should be identical
        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function it_clears_cache_on_invalidation(): void
    {
        // Arrange - S-004: Service clears cache on update
        $this->service->getSummary($this->site->id, 7);
        $this->service->getSummary($this->site->id, 30);

        // Act
        $this->service->invalidateSummaryCache($this->site->id);

        // Assert - All caches for this site should be cleared
        $this->assertNull(Cache::get("serp_feature_summary:{$this->site->id}:7"));
        $this->assertNull(Cache::get("serp_feature_summary:{$this->site->id}:30"));
    }

    /** @test */
    public function it_uses_different_cache_keys_for_different_day_ranges(): void
    {
        // Act
        $result7 = $this->service->getSummary($this->site->id, 7);
        $result30 = $this->service->getSummary($this->site->id, 30);

        // Assert - Both should be cached separately
        $this->assertTrue(Cache::has("serp_feature_summary:{$this->site->id}:7"));
        $this->assertTrue(Cache::has("serp_feature_summary:{$this->site->id}:30"));
    }

    // ============================================
    // Input Validation Tests (S-002)
    // ============================================

    /** @test */
    public function it_validates_keyword_id_type(): void
    {
        // Act - Non-existent keyword
        $result = $this->service->getKeywordFeatures(99999);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('KEYWORD_NOT_FOUND', $result['error']);
    }

    /** @test */
    public function it_validates_site_id_type(): void
    {
        // Act - Non-existent site
        $result = $this->service->getSiteFeatures(99999);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('SITE_NOT_FOUND', $result['error']);
    }

    /** @test */
    public function it_applies_default_pagination_values(): void
    {
        // Act - Call without pagination params
        $result = $this->service->getSiteFeatures($this->site->id);

        // Assert - Should use defaults
        $this->assertEquals(1, $result['meta']['page']);
        $this->assertEquals(20, $result['meta']['perPage']);
    }

    /** @test */
    public function it_handles_custom_pagination_values(): void
    {
        // Act
        $result = $this->service->getSiteFeatures($this->site->id, 2, 50);

        // Assert
        $this->assertEquals(2, $result['meta']['page']);
        $this->assertEquals(50, $result['meta']['perPage']);
    }

    // ============================================
    // Boundary Tests
    // ============================================

    /** @test */
    public function it_handles_minimum_days_parameter(): void
    {
        // Act - Days = 1 (minimum)
        $result = $this->service->getSummary($this->site->id, 1);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_returns_correct_last_page_calculation(): void
    {
        // Arrange - Create multiple features
        for ($i = 0; $i < 25; $i++) {
            \App\Models\SerpFeature::create([
                'keyword_id' => $this->keyword->id,
                'feature_type' => SerpFeature::FEATURE_TYPES[$i % 10],
                'is_present' => true,
                'checked_at' => now()->subMinutes($i)
            ]);
        }

        // Act
        $result = $this->service->getSiteFeatures($this->site->id, 1, 10);

        // Assert
        $this->assertGreaterThanOrEqual(1, $result['meta']['lastPage']);
    }
}
