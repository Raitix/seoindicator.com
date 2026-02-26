<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\SerpFeatureRepository;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\SerpFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class SerpFeatureRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected SerpFeatureRepository $repository;
    protected Site $site;
    protected Keyword $keyword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new SerpFeatureRepository();

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
    // getCurrentFeatures()
    // ============================================

    /** @test */
    public function it_returns_current_features_for_keyword(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com/seo-tools',
            'snippet' => 'Best SEO tools...',
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->getCurrentFeatures($this->keyword->id);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('featured_snippet', $result[0]['feature_type']);
        $this->assertTrue($result[0]['is_present']);
    }

    /** @test */
    public function it_returns_empty_array_for_nonexistent_keyword(): void
    {
        // Act
        $result = $this->repository->getCurrentFeatures(99999);

        // Assert
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_returns_only_latest_features_per_type(): void
    {
        // Arrange - Create multiple checks for same feature type
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()->subDays(2)
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com',
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->getCurrentFeatures($this->keyword->id);

        // Assert - Should return only the latest
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]['is_present']);
    }

    // ============================================
    // getFeaturesByDateRange()
    // ============================================

    /** @test */
    public function it_returns_features_within_date_range(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => '2026-02-20 10:00:00'
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => '2026-02-10 10:00:00'
        ]);

        // Act
        $result = $this->repository->getFeaturesByDateRange(
            $this->keyword->id,
            ['from' => '2026-02-15', 'to' => '2026-02-25']
        );

        // Assert
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_returns_all_features_for_empty_date_range(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->getFeaturesByDateRange($this->keyword->id, []);

        // Assert
        $this->assertCount(1, $result);
    }

    // ============================================
    // insert()
    // ============================================

    /** @test */
    public function it_inserts_new_feature_record(): void
    {
        // Arrange
        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com',
            'title' => 'Test Title',
            'snippet' => 'Test snippet',
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->insert($data);

        // Assert
        $this->assertIsInt($result);
        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet'
        ]);
    }

    /** @test */
    public function it_handles_null_url_and_snippet(): void
    {
        // Arrange
        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->insert($data);

        // Assert
        $this->assertIsInt($result);
        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'sitelinks',
            'url' => null,
            'snippet' => null
        ]);
    }

    // ============================================
    // update()
    // ============================================

    /** @test */
    public function it_updates_existing_feature(): void
    {
        // Arrange
        $feature = SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->update($feature->id, [
            'is_present' => true,
            'url' => 'https://test-example.com'
        ]);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('serp_features', [
            'id' => $feature->id,
            'is_present' => true,
            'url' => 'https://test-example.com'
        ]);
    }

    /** @test */
    public function it_returns_false_for_nonexistent_feature_update(): void
    {
        // Act
        $result = $this->repository->update(99999, ['is_present' => true]);

        // Assert
        $this->assertFalse($result);
    }

    // ============================================
    // deleteByKeyword()
    // ============================================

    /** @test */
    public function it_deletes_features_by_keyword(): void
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
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $count = $this->repository->deleteByKeyword($this->keyword->id);

        // Assert
        $this->assertEquals(2, $count);
        $this->assertDatabaseMissing('serp_features', [
            'keyword_id' => $this->keyword->id
        ]);
    }

    /** @test */
    public function it_returns_zero_for_nonexistent_keyword(): void
    {
        // Act
        $count = $this->repository->deleteByKeyword(99999);

        // Assert
        $this->assertEquals(0, $count);
    }

    // ============================================
    // getSiteFeatures()
    // ============================================

    /** @test */
    public function it_returns_paginated_features_for_site(): void
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
            'keyword_id' => $keyword2->id,
            'feature_type' => 'local_pack',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->getSiteFeatures($this->site->id, ['page' => 1, 'limit' => 20]);

        // Assert
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['data']);
    }

    /** @test */
    public function it_applies_default_pagination(): void
    {
        // Act
        $result = $this->repository->getSiteFeatures($this->site->id);

        // Assert
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(50, $result['limit']);
    }

    /** @test */
    public function it_handles_pagination_correctly(): void
    {
        // Arrange - Create 25 features
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
        $result = $this->repository->getSiteFeatures($this->site->id, ['page' => 2, 'limit' => 10]);

        // Assert
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(10, $result['limit']);
        $this->assertCount(10, $result['data']);
        $this->assertEquals(25, $result['total']);
    }

    // ============================================
    // getTotalKeywords()
    // ============================================

    /** @test */
    public function it_returns_total_keywords_for_site(): void
    {
        // Arrange
        Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword 2',
            'status' => 'active'
        ]);

        Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword 3',
            'status' => 'paused'
        ]);

        // Act
        $count = $this->repository->getTotalKeywords($this->site->id);

        // Assert
        $this->assertEquals(3, $count); // Including the one from setUp
    }

    /** @test */
    public function it_returns_zero_for_site_with_no_keywords(): void
    {
        // Arrange
        $emptySite = Site::create([
            'url' => 'https://empty-site.com',
            'name' => 'Empty Site'
        ]);

        // Act
        $count = $this->repository->getTotalKeywords($emptySite->id);

        // Assert
        $this->assertEquals(0, $count);
    }

    // ============================================
    // getKeywordsWithFeatures()
    // ============================================

    /** @test */
    public function it_returns_keywords_with_features_count(): void
    {
        // Arrange
        $keyword2 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword 2',
            'status' => 'active'
        ]);

        // Keyword 1 has features
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Keyword 2 has no features

        // Act
        $count = $this->repository->getKeywordsWithFeatures($this->site->id);

        // Assert
        $this->assertEquals(1, $count);
    }

    // ============================================
    // getFeatureCountsByType()
    // ============================================

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
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()->subDay()
        ]);

        // Act
        $counts = $this->repository->getFeatureCountsByType($this->site->id);

        // Assert
        $this->assertArrayHasKey('featured_snippet', $counts);
        $this->assertArrayHasKey('sitelinks', $counts);
        // Count should be distinct keywords per feature type
        $this->assertEquals(1, $counts['featured_snippet']);
        $this->assertEquals(1, $counts['sitelinks']);
    }

    /** @test */
    public function it_returns_empty_array_for_site_with_no_features(): void
    {
        // Act
        $counts = $this->repository->getFeatureCountsByType($this->site->id);

        // Assert
        $this->assertEmpty($counts);
    }

    // ============================================
    // getHistory()
    // ============================================

    /** @test */
    public function it_returns_history_for_keyword(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => '2026-02-20 10:00:00'
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => '2026-02-19 10:00:00'
        ]);

        // Act
        $history = $this->repository->getHistory($this->keyword->id);

        // Assert
        $this->assertCount(2, $history);
    }

    /** @test */
    public function it_returns_history_with_date_range(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => '2026-02-20 10:00:00'
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => '2026-02-10 10:00:00'
        ]);

        // Act
        $history = $this->repository->getHistory(
            $this->keyword->id,
            ['from' => '2026-02-15', 'to' => '2026-02-25']
        );

        // Assert
        $this->assertCount(1, $history);
    }

    // ============================================
    // Edge Cases
    // ============================================

    /** @test */
    public function it_handles_invalid_pagination_params(): void
    {
        // Act - Negative page should default to 1
        $result = $this->repository->getSiteFeatures($this->site->id, ['page' => -1, 'limit' => 10]);

        // Assert
        $this->assertEquals(1, $result['page']);
    }

    /** @test */
    public function it_limits_maximum_pagination_size(): void
    {
        // Act
        $result = $this->repository->getSiteFeatures($this->site->id, ['page' => 1, 'limit' => 1000]);

        // Assert - Should cap at reasonable maximum
        $this->assertLessThanOrEqual(100, $result['limit']);
    }

    /** @test */
    public function it_handles_special_characters_in_feature_type(): void
    {
        // Arrange
        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => "'; DROP TABLE serp_features;--",
            'is_present' => true,
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->insert($data);

        // Assert - Should store as literal string, not execute SQL
        $this->assertIsInt($result);
        $this->assertDatabaseHas('serp_features', [
            'feature_type' => "'; DROP TABLE serp_features;--"
        ]);
    }
}
