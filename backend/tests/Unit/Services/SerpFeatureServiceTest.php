<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SerpFeatureService;
use App\Repositories\SerpFeatureRepository;
use App\Repositories\SerpFeatureHistoryRepository;
use App\Models\Keyword;
use Mockery;

class SerpFeatureServiceTest extends TestCase
{
    protected SerpFeatureService $service;
    protected $featureRepo;
    protected $historyRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->featureRepo = Mockery::mock(SerpFeatureRepository::class);
        $this->historyRepo = Mockery::mock(SerpFeatureHistoryRepository::class);

        $this->service = new SerpFeatureService(
            $this->featureRepo,
            $this->historyRepo
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // getKeywordFeatures()
    // ============================================

    /** @test */
    public function it_returns_features_for_keyword(): void
    {
        // Arrange
        $keywordId = 1;
        $expectedFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com']
        ];

        $this->featureRepo
            ->shouldReceive('getCurrentFeatures')
            ->with($keywordId)
            ->once()
            ->andReturn($expectedFeatures);

        // Act
        $result = $this->service->getKeywordFeatures($keywordId);

        // Assert
        $this->assertEquals($expectedFeatures, $result);
    }

    /** @test */
    public function it_returns_features_with_date_range(): void
    {
        // Arrange
        $keywordId = 1;
        $dateRange = ['from' => '2026-02-01', 'to' => '2026-02-20'];

        $this->featureRepo
            ->shouldReceive('getFeaturesByDateRange')
            ->with($keywordId, $dateRange)
            ->once()
            ->andReturn([]);

        // Act
        $result = $this->service->getKeywordFeatures($keywordId, $dateRange);

        // Assert
        $this->assertIsArray($result);
    }

    /** @test */
    public function it_returns_empty_array_for_nonexistent_keyword(): void
    {
        // Arrange
        $keywordId = 99999;

        $this->featureRepo
            ->shouldReceive('getCurrentFeatures')
            ->with($keywordId)
            ->once()
            ->andReturn([]);

        // Act
        $result = $this->service->getKeywordFeatures($keywordId);

        // Assert
        $this->assertEmpty($result);
    }

    // ============================================
    // getSiteFeatures()
    // ============================================

    /** @test */
    public function it_returns_paginated_features_for_site(): void
    {
        // Arrange
        $siteId = 1;
        $options = ['page' => 1, 'limit' => 20];

        $this->featureRepo
            ->shouldReceive('getSiteFeatures')
            ->with($siteId, $options)
            ->once()
            ->andReturn([
                'data' => [],
                'total' => 0,
                'page' => 1,
                'limit' => 20
            ]);

        // Act
        $result = $this->service->getSiteFeatures($siteId, $options);

        // Assert
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /** @test */
    public function it_applies_default_pagination(): void
    {
        // Arrange
        $siteId = 1;

        $this->featureRepo
            ->shouldReceive('getSiteFeatures')
            ->with($siteId, ['page' => 1, 'limit' => 50])
            ->once()
            ->andReturn(['data' => [], 'total' => 0, 'page' => 1, 'limit' => 50]);

        // Act
        $result = $this->service->getSiteFeatures($siteId);

        // Assert
        $this->assertEquals(50, $result['limit']);
    }

    // ============================================
    // getSummary()
    // ============================================

    /** @test */
    public function it_returns_summary_with_feature_counts(): void
    {
        // Arrange
        $siteId = 1;

        $this->featureRepo
            ->shouldReceive('getTotalKeywords')
            ->with($siteId)
            ->once()
            ->andReturn(10);

        $this->featureRepo
            ->shouldReceive('getKeywordsWithFeatures')
            ->with($siteId)
            ->once()
            ->andReturn(5);

        $this->featureRepo
            ->shouldReceive('getFeatureCountsByType')
            ->with($siteId)
            ->once()
            ->andReturn(['featured_snippet' => 3, 'local_pack' => 2]);

        $this->historyRepo
            ->shouldReceive('getRecentChanges')
            ->with($siteId, 7)
            ->once()
            ->andReturn(['gained' => [], 'lost' => []]);

        // Act
        $result = $this->service->getSummary($siteId);

        // Assert
        $this->assertEquals(10, $result['totalKeywords']);
        $this->assertEquals(5, $result['keywordsWithFeatures']);
        $this->assertArrayHasKey('featureCounts', $result);
    }

    /** @test */
    public function it_returns_recent_changes_in_summary(): void
    {
        // Arrange
        $siteId = 1;
        $expectedChanges = [
            'gained' => [
                ['keyword' => 'seo tools', 'feature' => 'featured_snippet', 'detectedAt' => '2026-02-20']
            ],
            'lost' => []
        ];

        $this->featureRepo->shouldReceive('getTotalKeywords')->andReturn(1);
        $this->featureRepo->shouldReceive('getKeywordsWithFeatures')->andReturn(1);
        $this->featureRepo->shouldReceive('getFeatureCountsByType')->andReturn([]);

        $this->historyRepo
            ->shouldReceive('getRecentChanges')
            ->with($siteId, 7)
            ->once()
            ->andReturn($expectedChanges);

        // Act
        $result = $this->service->getSummary($siteId);

        // Assert
        $this->assertEquals($expectedChanges, $result['recentChanges']);
    }

    // ============================================
    // getHistory()
    // ============================================

    /** @test */
    public function it_returns_history_for_keyword(): void
    {
        // Arrange
        $keywordId = 1;
        $expectedHistory = [
            ['date' => '2026-02-20', 'features' => ['featured_snippet' => true]],
            ['date' => '2026-02-19', 'features' => ['featured_snippet' => false]]
        ];

        $this->featureRepo
            ->shouldReceive('getHistory')
            ->with($keywordId, null)
            ->once()
            ->andReturn($expectedHistory);

        // Act
        $result = $this->service->getHistory($keywordId);

        // Assert
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_returns_history_with_date_range(): void
    {
        // Arrange
        $keywordId = 1;
        $dateRange = ['from' => '2026-02-15', 'to' => '2026-02-20'];

        $this->featureRepo
            ->shouldReceive('getHistory')
            ->with($keywordId, $dateRange)
            ->once()
            ->andReturn([]);

        // Act
        $result = $this->service->getHistory($keywordId, $dateRange);

        // Assert
        $this->assertIsArray($result);
    }

    // ============================================
    // getChanges()
    // ============================================

    /** @test */
    public function it_returns_changes_for_keyword(): void
    {
        // Arrange
        $keywordId = 1;
        $expectedChanges = [
            ['type' => 'featured_snippet', 'action' => 'gained', 'detectedAt' => '2026-02-20T10:00:00Z']
        ];

        $this->historyRepo
            ->shouldReceive('getChangesForKeyword')
            ->with($keywordId)
            ->once()
            ->andReturn($expectedChanges);

        // Act
        $result = $this->service->getChanges($keywordId);

        // Assert
        $this->assertEquals($expectedChanges, $result);
    }

    // ============================================
    // Edge Cases
    // ============================================

    /** @test */
    public function it_handles_empty_gracefully(): void
    {
        // Arrange
        $this->featureRepo->shouldReceive('getCurrentFeatures')->andReturn([]);
        $this->featureRepo->shouldReceive('getHistory')->andReturn([]);
        $this->historyRepo->shouldReceive('getChangesForKeyword')->andReturn([]);

        // Act
        $features = $this->service->getKeywordFeatures(1);
        $history = $this->service->getHistory(1);
        $changes = $this->service->getChanges(1);

        // Assert
        $this->assertEmpty($features);
        $this->assertEmpty($history);
        $this->assertEmpty($changes);
    }
}
