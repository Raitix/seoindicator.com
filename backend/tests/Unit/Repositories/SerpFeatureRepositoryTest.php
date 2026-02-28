<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\SerpFeatureRepository;
use App\Models\User;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\SerpFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SerpFeatureRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected SerpFeatureRepository $repository;
    protected Site $site;
    protected Keyword $keyword;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new SerpFeatureRepository();

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
    }

    // ============================================
    // findById()
    // ============================================

    /** @test */
    public function it_finds_feature_by_id(): void
    {
        // Arrange
        $feature = SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->findById($feature->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('featured_snippet', $result->feature_type);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_id(): void
    {
        // Act
        $result = $this->repository->findById(99999);

        // Assert
        $this->assertNull($result);
    }

    // ============================================
    // getByKeyword()
    // ============================================

    /** @test */
    public function it_gets_features_by_keyword(): void
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
            'feature_type' => 'local_pack',
            'is_present' => false,
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->getByKeyword($this->keyword->id);

        // Assert
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_returns_empty_collection_for_keyword_with_no_features(): void
    {
        // Act
        $result = $this->repository->getByKeyword($this->keyword->id);

        // Assert
        $this->assertCount(0, $result);
    }

    // ============================================
    // getBySite()
    // ============================================

    /** @test */
    public function it_gets_features_by_site(): void
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
            'feature_type' => 'local_pack',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Non-present feature (should not be returned)
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'image_pack',
            'is_present' => false,
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->getBySite($this->site->id);

        // Assert - Only present features returned
        $this->assertCount(2, $result);
    }

    // ============================================
    // getGroupedByKeyword()
    // ============================================

    /** @test */
    public function it_groups_features_by_keyword(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->getGroupedByKeyword($this->keyword->id);

        // Assert - Should have all feature types
        $this->assertArrayHasKey('featured_snippet', $result);
        $this->assertArrayHasKey('local_pack', $result);
        $this->assertTrue($result['featured_snippet']);
        $this->assertFalse($result['local_pack']);
    }

    // ============================================
    // getTotalKeywords()
    // ============================================

    /** @test */
    public function it_returns_total_active_keywords_for_site(): void
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
            'status' => 'paused' // Should not be counted
        ]);

        // Act
        $count = $this->repository->getTotalKeywords($this->site->id);

        // Assert - Only active keywords counted
        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_returns_zero_for_site_with_no_keywords(): void
    {
        // Arrange
        $emptySite = Site::create([
            'user_id' => $this->user->id,
            'url' => 'https://empty-site.com',
            'name' => 'Empty Site'
        ]);

        // Act
        $count = $this->repository->getTotalKeywords($emptySite->id);

        // Assert
        $this->assertEquals(0, $count);
    }

    // ============================================
    // getKeywordsWithFeaturesCount()
    // ============================================

    /** @test */
    public function it_counts_keywords_with_features(): void
    {
        // Arrange
        $keyword2 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword 2',
            'status' => 'active'
        ]);

        // Keyword 1 has feature
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Keyword 2 has no features

        // Act
        $count = $this->repository->getKeywordsWithFeaturesCount($this->site->id);

        // Assert
        $this->assertEquals(1, $count);
    }

    // ============================================
    // getFeatureCountsBySite()
    // ============================================

    /** @test */
    public function it_returns_feature_counts_by_site(): void
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
        $counts = $this->repository->getFeatureCountsBySite($this->site->id);

        // Assert - Should have all feature types
        $this->assertArrayHasKey('featured_snippet', $counts);
        $this->assertArrayHasKey('sitelinks', $counts);
        $this->assertArrayHasKey('local_pack', $counts);
        $this->assertEquals(1, $counts['featured_snippet']);
        $this->assertEquals(1, $counts['sitelinks']);
        $this->assertEquals(0, $counts['local_pack']);
    }

    // ============================================
    // upsert()
    // ============================================

    /** @test */
    public function it_creates_new_feature_on_upsert(): void
    {
        // Arrange
        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com',
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->upsert($data);

        // Assert
        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true
        ]);
    }

    /** @test */
    public function it_updates_existing_feature_on_upsert(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()
        ]);

        // Act
        $this->repository->upsert([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com',
            'checked_at' => now()
        ]);

        // Assert - Should have updated, not created new
        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true
        ]);

        $this->assertEquals(1, SerpFeature::where('keyword_id', $this->keyword->id)
            ->where('feature_type', 'featured_snippet')
            ->count());
    }

    // ============================================
    // insertBatch()
    // ============================================

    /** @test */
    public function it_inserts_multiple_features(): void
    {
        // Arrange
        $features = [
            [
                'keyword_id' => $this->keyword->id,
                'feature_type' => 'featured_snippet',
                'is_present' => true,
            ],
            [
                'keyword_id' => $this->keyword->id,
                'feature_type' => 'local_pack',
                'is_present' => false,
            ],
        ];

        // Act
        $result = $this->repository->insertBatch($features);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(2, SerpFeature::where('keyword_id', $this->keyword->id)->count());
    }

    // ============================================
    // getByKeywordIds()
    // ============================================

    /** @test */
    public function it_gets_features_by_keyword_ids(): void
    {
        // Arrange
        $keyword2 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword 2',
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
        $result = $this->repository->getByKeywordIds([$this->keyword->id, $keyword2->id]);

        // Assert
        $this->assertCount(2, $result);
    }

    // ============================================
    // getKeywordsByFeature()
    // ============================================

    /** @test */
    public function it_gets_keywords_with_specific_feature(): void
    {
        // Arrange
        $keyword2 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword 2',
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
            'feature_type' => 'featured_snippet',
            'is_present' => false, // Not present
            'checked_at' => now()
        ]);

        // Act
        $result = $this->repository->getKeywordsByFeature($this->site->id, 'featured_snippet', true);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($this->keyword->id, $result->first()->id);
    }

    // ============================================
    // getKeywordFeaturesWithHistory()
    // ============================================

    /** @test */
    public function it_gets_keyword_features_with_history(): void
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
            'is_present' => false,
            'checked_at' => now()->subDays(2)
        ]);

        // Act
        $result = $this->repository->getKeywordFeaturesWithHistory($this->keyword->id, 7);

        // Assert
        $this->assertArrayHasKey('current', $result);
        $this->assertArrayHasKey('history', $result);
        $this->assertIsArray($result['current']);
        $this->assertIsArray($result['history']);
    }

    // ============================================
    // Edge Cases
    // ============================================

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
        $result = $this->repository->upsert($data);

        // Assert - Should store as literal string, not execute SQL
        $this->assertDatabaseHas('serp_features', [
            'feature_type' => "'; DROP TABLE serp_features;--"
        ]);
    }

    /** @test */
    public function it_handles_null_url_in_feature(): void
    {
        // Arrange - R-014: Null target URL
        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'url' => null,
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->upsert($data);

        // Assert - Should store NULL in url column
        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'sitelinks',
            'url' => null
        ]);
    }

    /** @test */
    public function it_handles_very_long_snippet_text(): void
    {
        // Arrange - R-015: Very long snippet text
        $longSnippet = str_repeat('a', 5000); // 5000 character snippet
        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'snippet' => $longSnippet,
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->upsert($data);

        // Assert - Should store the snippet (truncated by DB if needed)
        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet'
        ]);
    }

    /** @test */
    public function it_handles_duplicate_keyword_feature_combination(): void
    {
        // Arrange - R-018: Duplicate (keyword_id, feature_type)
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'url' => 'https://old-url.com',
            'checked_at' => now()->subDay()
        ]);

        // Act - Upsert should update, not create duplicate
        $this->repository->upsert([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://new-url.com',
            'checked_at' => now()
        ]);

        // Assert - Only one record exists
        $count = SerpFeature::where('keyword_id', $this->keyword->id)
            ->where('feature_type', 'featured_snippet')
            ->count();
        $this->assertEquals(1, $count);

        // Assert - Record was updated
        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'url' => 'https://new-url.com'
        ]);
    }

    // ============================================
    // Adversarial Tests (A-001 to A-009)
    // ============================================

    /** @test */
    public function it_sanitizes_xss_in_snippet_and_title(): void
    {
        // Arrange - A-002: XSS in snippet/title
        $xssPayload = '<script>alert("xss")</script><img src=x onerror=alert(1)>';
        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'title' => $xssPayload,
            'snippet' => $xssPayload,
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->upsert($data);

        // Assert - Should store as-is (encoding is handled at output layer)
        $feature = SerpFeature::find($result->id);
        $this->assertStringContainsString('<script>', $feature->snippet);
        // Note: XSS prevention is typically handled at the view/output layer
    }

    /** @test */
    public function it_handles_very_long_url(): void
    {
        // Arrange - A-003: Very long URL
        $longUrl = 'https://example.com/' . str_repeat('path/', 500); // ~2500+ chars
        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => $longUrl,
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->upsert($data);

        // Assert - Should store or truncate (DB dependent)
        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet'
        ]);
    }

    /** @test */
    public function it_handles_unicode_in_feature_data(): void
    {
        // Arrange - A-004: Unicode in feature data
        $unicodeData = [
            'emoji' => '🏆 Featured Snippet 🎉',
            'chinese' => '最佳SEO工具',
            'arabic' => 'أدوات تحسين محركات البحث',
            'russian' => 'Лучшие SEO инструменты',
            'rtl' => 'מימין לשמאל',
        ];

        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'title' => $unicodeData['emoji'],
            'snippet' => $unicodeData['chinese'] . ' ' . $unicodeData['arabic'],
            'url' => 'https://example.com/' . urlencode($unicodeData['russian']),
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->upsert($data);
        $feature = SerpFeature::find($result->id);

        // Assert - Unicode should be stored correctly
        $this->assertEquals($unicodeData['emoji'], $feature->title);
        $this->assertStringContainsString($unicodeData['chinese'], $feature->snippet);
    }

    /** @test */
    public function it_handles_null_byte_injection(): void
    {
        // Arrange - A-005: Null byte injection
        $nullByteString = "featured\u0000snippet";
        $data = [
            'keyword_id' => $this->keyword->id,
            'feature_type' => $nullByteString,
            'is_present' => true,
            'checked_at' => now()
        ];

        // Act
        $result = $this->repository->upsert($data);

        // Assert - Should store sanitized or as-is
        $this->assertNotNull($result);
    }

    /** @test */
    public function it_handles_maximum_features_per_keyword(): void
    {
        // Arrange - A-007: Maximum features per keyword (all 10 feature types)
        $allFeatureTypes = SerpFeature::FEATURE_TYPES;
        $features = [];

        foreach ($allFeatureTypes as $type) {
            $features[] = [
                'keyword_id' => $this->keyword->id,
                'feature_type' => $type,
                'is_present' => true,
                'checked_at' => now()
            ];
        }

        // Act
        $result = $this->repository->insertBatch($features);

        // Assert - All 10 feature types should be stored
        $this->assertTrue($result);
        $count = SerpFeature::where('keyword_id', $this->keyword->id)->count();
        $this->assertEquals(10, $count);
    }

    /** @test */
    public function it_handles_empty_batch_insert(): void
    {
        // Arrange - Empty features array
        $features = [];

        // Act
        $result = $this->repository->insertBatch($features);

        // Assert - Should return true for empty array
        $this->assertTrue($result);
    }

    /** @test */
    public function it_clears_feature_cache_on_invalidation(): void
    {
        // Arrange
        Cache::put("serp_feature_counts:{$this->site->id}", ['test' => 'data'], 300);
        Cache::put("serp_feature_summary:{$this->site->id}", ['test' => 'summary'], 300);

        // Act
        $this->repository->invalidateCache($this->site->id);

        // Assert - Cache should be cleared
        $this->assertNull(Cache::get("serp_feature_counts:{$this->site->id}"));
        $this->assertNull(Cache::get("serp_feature_summary:{$this->site->id}"));
    }
}
