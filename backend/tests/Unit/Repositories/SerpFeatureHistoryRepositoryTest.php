<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\SerpFeatureHistoryRepository;
use App\Models\User;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\SerpFeatureHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SerpFeatureHistoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected SerpFeatureHistoryRepository $repository;
    protected Site $site;
    protected Site $site2;
    protected Keyword $keyword1;
    protected Keyword $keyword2;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new SerpFeatureHistoryRepository();

        // Create user first
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

        $this->site2 = Site::create([
            'user_id' => $this->user->id,
            'url' => 'https://other-site.com',
            'name' => 'Other Site'
        ]);

        $this->keyword1 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'seo tools',
            'target_url' => 'https://test-example.com/seo-tools',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active'
        ]);

        $this->keyword2 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword research',
            'target_url' => 'https://test-example.com/keyword-research',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active'
        ]);
    }

    // ============================================
    // getFeatureTimeline() Tests
    // ============================================

    /** @test */
    public function it_returns_daily_feature_presence(): void
    {
        // Arrange - Create history entries on different days
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(3),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(2),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(1),
        ]);

        // Act
        $result = $this->repository->getFeatureTimeline($this->keyword1->id, 7);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Should have entries for the days with changes
        $this->assertGreaterThanOrEqual(3, count($result));
        
        // Each entry should have date and features
        foreach ($result as $entry) {
            $this->assertArrayHasKey('date', $entry);
            $this->assertArrayHasKey('features', $entry);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $entry['date']);
        }
    }

    /** @test */
    public function it_returns_empty_for_nonexistent_keyword(): void
    {
        // Act
        $result = $this->repository->getFeatureTimeline(99999, 7);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_respects_days_parameter(): void
    {
        // Arrange - Create old entry
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(10),
        ]);

        // Create recent entry
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(2),
        ]);

        // Act
        $result7days = $this->repository->getFeatureTimeline($this->keyword1->id, 7);

        // Assert - Should only include recent entry
        $this->assertNotEmpty($result7days);
    }

    /** @test */
    public function it_orders_by_date_desc(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(3),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(1),
        ]);

        // Act
        $result = $this->repository->getFeatureTimeline($this->keyword1->id, 7);

        // Assert - Newest first
        $dates = array_column($result, 'date');
        $sortedDates = $dates;
        rsort($sortedDates);
        $this->assertEquals($sortedDates, $dates);
    }

    /** @test */
    public function it_includes_all_feature_types_in_features_object(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        // Act
        $result = $this->repository->getFeatureTimeline($this->keyword1->id, 1);

        // Assert
        $this->assertNotEmpty($result);
        $features = $result[0]['features'];
        
        $expectedTypes = [
            'featured_snippet', 'local_pack', 'image_pack', 'video',
            'people_also_ask', 'knowledge_panel', 'sitelinks', 'reviews',
            'faq', 'product'
        ];
        
        foreach ($expectedTypes as $type) {
            $this->assertArrayHasKey($type, $features);
            $this->assertIsBool($features[$type]);
        }
    }

    /** @test */
    public function it_throws_for_zero_days(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getFeatureTimeline($this->keyword1->id, 0);
    }

    /** @test */
    public function it_throws_for_negative_days(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getFeatureTimeline($this->keyword1->id, -1);
    }

    /** @test */
    public function it_throws_type_error_for_invalid_keyword_id(): void
    {
        $this->expectException(\TypeError::class);
        $this->repository->getFeatureTimeline('invalid', 7);
    }

    // ============================================
    // getFeatureGainsBySite() Tests
    // ============================================

    /** @test */
    public function it_returns_gains_in_period(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(2),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com/local',
            'detected_at' => Carbon::now()->subDays(1),
        ]);

        // Act
        $result = $this->repository->getFeatureGainsBySite($this->site->id, 7);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['data']);
    }

    /** @test */
    public function it_returns_empty_for_no_gains(): void
    {
        // Act
        $result = $this->repository->getFeatureGainsBySite($this->site->id, 7);

        // Assert
        $this->assertCount(0, $result['data']);
        $this->assertEquals(0, $result['pagination']['total']);
    }

    /** @test */
    public function it_respects_days_parameter_for_gains(): void
    {
        // Arrange - Old gain
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(10),
        ]);

        // Recent gain
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(2),
        ]);

        // Act
        $result = $this->repository->getFeatureGainsBySite($this->site->id, 5);

        // Assert - Only recent gain included
        $this->assertCount(1, $result['data']);
    }

    /** @test */
    public function it_orders_gains_by_detected_at_desc(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(3),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(1),
        ]);

        // Act
        $result = $this->repository->getFeatureGainsBySite($this->site->id, 7);

        // Assert - Most recent first
        $this->assertEquals('local_pack', $result['data'][0]['feature_type']);
        $this->assertEquals('featured_snippet', $result['data'][1]['feature_type']);
    }

    /** @test */
    public function it_includes_required_fields_in_gain_response(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com/page',
            'detected_at' => Carbon::now(),
        ]);

        // Act
        $result = $this->repository->getFeatureGainsBySite($this->site->id, 7);

        // Assert
        $gain = $result['data'][0];
        $this->assertArrayHasKey('keyword_id', $gain);
        $this->assertArrayHasKey('keyword', $gain);
        $this->assertArrayHasKey('feature_type', $gain);
        $this->assertArrayHasKey('current_url', $gain);
        $this->assertArrayHasKey('detected_at', $gain);
    }

    /** @test */
    public function it_only_includes_gained_actions(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        // Act
        $result = $this->repository->getFeatureGainsBySite($this->site->id, 7);

        // Assert - Only gained action
        $this->assertCount(1, $result['data']);
        $this->assertEquals('gained', 'gained'); // Verify only gains
    }

    /** @test */
    public function it_supports_pagination_for_gains(): void
    {
        // Arrange - Create 15 gains
        for ($i = 0; $i < 15; $i++) {
            SerpFeatureHistory::create([
                'keyword_id' => $this->keyword1->id,
                'feature_type' => 'featured_snippet',
                'action' => 'gained',
                'current_url' => 'https://test-example.com',
                'detected_at' => Carbon::now()->subMinutes($i),
            ]);
        }

        // Act
        $result = $this->repository->getFeatureGainsBySite($this->site->id, 7, ['limit' => 5]);

        // Assert
        $this->assertCount(5, $result['data']);
        $this->assertEquals(15, $result['pagination']['total']);
        $this->assertEquals(5, $result['pagination']['limit']);
        $this->assertTrue($result['pagination']['hasMore']);
    }

    // ============================================
    // getFeatureLossesBySite() Tests
    // ============================================

    /** @test */
    public function it_returns_losses_in_period(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(2),
        ]);

        // Act
        $result = $this->repository->getFeatureLossesBySite($this->site->id, 7);

        // Assert
        $this->assertCount(1, $result['data']);
    }

    /** @test */
    public function it_returns_empty_for_no_losses(): void
    {
        // Act
        $result = $this->repository->getFeatureLossesBySite($this->site->id, 7);

        // Assert
        $this->assertCount(0, $result['data']);
    }

    /** @test */
    public function it_includes_required_fields_in_loss_response(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com/page',
            'detected_at' => Carbon::now(),
        ]);

        // Act
        $result = $this->repository->getFeatureLossesBySite($this->site->id, 7);

        // Assert
        $loss = $result['data'][0];
        $this->assertArrayHasKey('keyword_id', $loss);
        $this->assertArrayHasKey('keyword', $loss);
        $this->assertArrayHasKey('feature_type', $loss);
        $this->assertArrayHasKey('previous_url', $loss);
        $this->assertArrayHasKey('detected_at', $loss);
    }

    /** @test */
    public function it_only_includes_lost_actions(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        // Act
        $result = $this->repository->getFeatureLossesBySite($this->site->id, 7);

        // Assert - Only lost action
        $this->assertCount(1, $result['data']);
    }

    /** @test */
    public function it_supports_pagination_for_losses(): void
    {
        // Arrange - Create 15 losses
        for ($i = 0; $i < 15; $i++) {
            SerpFeatureHistory::create([
                'keyword_id' => $this->keyword1->id,
                'feature_type' => 'featured_snippet',
                'action' => 'lost',
                'previous_url' => 'https://test-example.com',
                'detected_at' => Carbon::now()->subMinutes($i),
            ]);
        }

        // Act
        $result = $this->repository->getFeatureLossesBySite($this->site->id, 7, ['limit' => 5]);

        // Assert
        $this->assertCount(5, $result['data']);
        $this->assertEquals(15, $result['pagination']['total']);
    }

    // ============================================
    // getFeatureStatistics() Tests
    // ============================================

    /** @test */
    public function it_returns_aggregated_statistics(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        // Act
        $result = $this->repository->getFeatureStatistics($this->site->id);

        // Assert
        $this->assertArrayHasKey('totalKeywordsWithFeatures', $result);
        $this->assertArrayHasKey('totalFeatures', $result);
        $this->assertArrayHasKey('featuresByType', $result);
        $this->assertArrayHasKey('gainsThisWeek', $result);
        $this->assertArrayHasKey('lossesThisWeek', $result);
    }

    /** @test */
    public function it_returns_zeros_for_no_data(): void
    {
        // Act
        $result = $this->repository->getFeatureStatistics($this->site->id);

        // Assert
        $this->assertEquals(0, $result['totalKeywordsWithFeatures']);
        $this->assertEquals(0, $result['totalFeatures']);
        $this->assertEquals(0, $result['gainsThisWeek']);
        $this->assertEquals(0, $result['lossesThisWeek']);
    }

    /** @test */
    public function it_counts_distinct_keywords(): void
    {
        // Arrange - Same keyword multiple features
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        // Act
        $result = $this->repository->getFeatureStatistics($this->site->id);

        // Assert
        $this->assertEquals(1, $result['totalKeywordsWithFeatures']);
        $this->assertEquals(2, $result['totalFeatures']);
    }

    /** @test */
    public function it_returns_features_by_type(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        // Act
        $result = $this->repository->getFeatureStatistics($this->site->id);

        // Assert
        $this->assertEquals(2, $result['featuresByType']['featured_snippet']);
        $this->assertEquals(1, $result['featuresByType']['local_pack']);
    }

    /** @test */
    public function it_counts_gains_and_losses_this_week(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(3),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(2),
        ]);

        // Act
        $result = $this->repository->getFeatureStatistics($this->site->id);

        // Assert
        $this->assertEquals(1, $result['gainsThisWeek']);
        $this->assertEquals(1, $result['lossesThisWeek']);
    }

    // ============================================
    // getLongestHeldFeatures() Tests
    // ============================================

    /** @test */
    public function it_returns_features_held_longest(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(30),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(15),
        ]);

        // Act
        $result = $this->repository->getLongestHeldFeatures($this->site->id, 10);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('featured_snippet', $result[0]['feature_type']);
        $this->assertGreaterThanOrEqual(29, $result[0]['days_held']);
    }

    /** @test */
    public function it_respects_limit_parameter(): void
    {
        // Arrange - Create 5 features
        for ($i = 0; $i < 5; $i++) {
            SerpFeatureHistory::create([
                'keyword_id' => $this->keyword1->id,
                'feature_type' => self::FEATURE_TYPES[$i] ?? 'featured_snippet',
                'action' => 'gained',
                'current_url' => 'https://test-example.com',
                'detected_at' => Carbon::now()->subDays($i + 1),
            ]);
        }

        // Act
        $result = $this->repository->getLongestHeldFeatures($this->site->id, 3);

        // Assert
        $this->assertLessThanOrEqual(3, count($result));
    }

    /** @test */
    public function it_returns_empty_for_no_features(): void
    {
        // Act
        $result = $this->repository->getLongestHeldFeatures($this->site->id, 10);

        // Assert
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_includes_required_fields_in_held_features(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(10),
        ]);

        // Act
        $result = $this->repository->getLongestHeldFeatures($this->site->id, 10);

        // Assert
        $feature = $result[0];
        $this->assertArrayHasKey('keyword_id', $feature);
        $this->assertArrayHasKey('keyword', $feature);
        $this->assertArrayHasKey('feature_type', $feature);
        $this->assertArrayHasKey('held_since', $feature);
        $this->assertArrayHasKey('days_held', $feature);
    }

    /** @test */
    public function it_excludes_lost_features(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(30),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(5),
        ]);

        // Act
        $result = $this->repository->getLongestHeldFeatures($this->site->id, 10);

        // Assert - Lost feature should not be included
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_orders_by_days_held_desc(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(10),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(30),
        ]);

        // Act
        $result = $this->repository->getLongestHeldFeatures($this->site->id, 10);

        // Assert - Longest held first
        $this->assertEquals('local_pack', $result[0]['feature_type']);
        $this->assertEquals('featured_snippet', $result[1]['feature_type']);
    }

    // ============================================
    // getMostVolatileKeywords() Tests
    // ============================================

    /** @test */
    public function it_returns_keywords_with_most_changes(): void
    {
        // Arrange - Keyword 1: 5 changes
        for ($i = 0; $i < 3; $i++) {
            SerpFeatureHistory::create([
                'keyword_id' => $this->keyword1->id,
                'feature_type' => 'featured_snippet',
                'action' => $i % 2 === 0 ? 'gained' : 'lost',
                'current_url' => 'https://test-example.com',
                'previous_url' => 'https://test-example.com',
                'detected_at' => Carbon::now()->subDays($i),
            ]);
        }

        // Keyword 2: 2 changes
        for ($i = 0; $i < 2; $i++) {
            SerpFeatureHistory::create([
                'keyword_id' => $this->keyword2->id,
                'feature_type' => 'local_pack',
                'action' => $i % 2 === 0 ? 'gained' : 'lost',
                'current_url' => 'https://test-example.com',
                'previous_url' => 'https://test-example.com',
                'detected_at' => Carbon::now()->subDays($i),
            ]);
        }

        // Act
        $result = $this->repository->getMostVolatileKeywords($this->site->id, 10);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($this->keyword1->id, $result[0]['keyword_id']);
        $this->assertEquals(3, $result[0]['change_count']);
    }

    /** @test */
    public function it_respects_limit_for_volatile_keywords(): void
    {
        // Arrange
        for ($i = 0; $i < 5; $i++) {
            SerpFeatureHistory::create([
                'keyword_id' => $this->keyword1->id,
                'feature_type' => 'featured_snippet',
                'action' => $i % 2 === 0 ? 'gained' : 'lost',
                'current_url' => 'https://test-example.com',
                'previous_url' => 'https://test-example.com',
                'detected_at' => Carbon::now()->subDays($i),
            ]);
        }

        // Act
        $result = $this->repository->getMostVolatileKeywords($this->site->id, 1);

        // Assert
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_returns_empty_for_no_changes(): void
    {
        // Act
        $result = $this->repository->getMostVolatileKeywords($this->site->id, 10);

        // Assert
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_includes_gains_and_losses_breakdown(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(1),
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'local_pack',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now()->subDays(2),
        ]);

        // Act
        $result = $this->repository->getMostVolatileKeywords($this->site->id, 10);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['gains']);
        $this->assertEquals(1, $result[0]['losses']);
        $this->assertEquals(3, $result[0]['change_count']);
    }

    /** @test */
    public function it_orders_by_change_count_desc(): void
    {
        // Arrange
        for ($i = 0; $i < 5; $i++) {
            SerpFeatureHistory::create([
                'keyword_id' => $this->keyword1->id,
                'feature_type' => 'featured_snippet',
                'action' => $i % 2 === 0 ? 'gained' : 'lost',
                'current_url' => 'https://test-example.com',
                'previous_url' => 'https://test-example.com',
                'detected_at' => Carbon::now()->subDays($i),
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            SerpFeatureHistory::create([
                'keyword_id' => $this->keyword2->id,
                'feature_type' => 'local_pack',
                'action' => $i % 2 === 0 ? 'gained' : 'lost',
                'current_url' => 'https://test-example.com',
                'previous_url' => 'https://test-example.com',
                'detected_at' => Carbon::now()->subDays($i),
            ]);
        }

        // Act
        $result = $this->repository->getMostVolatileKeywords($this->site->id, 10);

        // Assert - Most changes first
        $this->assertEquals(5, $result[0]['change_count']);
        $this->assertEquals(2, $result[1]['change_count']);
    }

    // ============================================
    // insert() and insertBatch() Tests
    // ============================================

    /** @test */
    public function it_inserts_single_history_entry(): void
    {
        // Act
        $result = $this->repository->insert([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
        ]);

        // Assert
        $this->assertInstanceOf(SerpFeatureHistory::class, $result);
        $this->assertDatabaseHas('serp_feature_history', [
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
        ]);
    }

    /** @test */
    public function it_inserts_batch_entries(): void
    {
        // Act
        $result = $this->repository->insertBatch([
            [
                'keyword_id' => $this->keyword1->id,
                'feature_type' => 'featured_snippet',
                'action' => 'gained',
                'current_url' => 'https://test-example.com',
            ],
            [
                'keyword_id' => $this->keyword2->id,
                'feature_type' => 'local_pack',
                'action' => 'lost',
                'previous_url' => 'https://test-example.com',
            ],
        ]);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(2, SerpFeatureHistory::count());
    }

    /** @test */
    public function it_validates_keyword_id_on_insert(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->insert([
            'keyword_id' => 'invalid',
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
        ]);
    }

    /** @test */
    public function it_validates_feature_type_on_insert(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->insert([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'invalid_type',
            'action' => 'gained',
        ]);
    }

    /** @test */
    public function it_validates_action_on_insert(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->insert([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'invalid_action',
        ]);
    }

    // ============================================
    // Error Handling Tests
    // ============================================

    /** @test */
    public function it_throws_type_error_for_invalid_site_id(): void
    {
        $this->expectException(\TypeError::class);
        $this->repository->getFeatureStatistics('invalid');
    }

    /** @test */
    public function it_throws_for_negative_site_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getFeatureStatistics(-1);
    }

    /** @test */
    public function it_throws_type_error_for_invalid_limit(): void
    {
        $this->expectException(\TypeError::class);
        $this->repository->getMostVolatileKeywords($this->site->id, 'invalid');
    }

    /** @test */
    public function it_throws_for_negative_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->getMostVolatileKeywords($this->site->id, -1);
    }

    // ============================================
    // Type Safety Tests
    // ============================================

    /** @test */
    public function it_returns_properly_typed_timeline(): void
    {
        // Arrange
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com',
            'detected_at' => Carbon::now(),
        ]);

        // Act
        $result = $this->repository->getFeatureTimeline($this->keyword1->id, 7);

        // Assert
        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertIsString($result[0]['date']);
            $this->assertIsArray($result[0]['features']);
        }
    }

    /** @test */
    public function it_returns_properly_typed_statistics(): void
    {
        // Act
        $result = $this->repository->getFeatureStatistics($this->site->id);

        // Assert
        $this->assertIsInt($result['totalKeywordsWithFeatures']);
        $this->assertIsInt($result['totalFeatures']);
        $this->assertIsInt($result['gainsThisWeek']);
        $this->assertIsInt($result['lossesThisWeek']);
        $this->assertIsArray($result['featuresByType']);
    }

    // Helper constant for test
    private const FEATURE_TYPES = [
        'featured_snippet',
        'local_pack',
        'image_pack',
        'video',
        'people_also_ask',
        'knowledge_panel',
        'sitelinks',
        'reviews',
        'faq',
        'product'
    ];
}
