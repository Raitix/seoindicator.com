<?php

namespace Tests\Unit\Services;

use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use App\Models\SerpFeature;
use App\Repositories\SerpFeatureHistoryRepository;
use App\Repositories\SerpFeatureRepository;
use App\Services\SERPFeatureDetector;
use App\Services\SerpFeatureChangeTracker;
use App\Services\RankCheckerService;
use App\Services\SERP\SERPResult;
use App\Services\SERP\SERPEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for TASK-7-13: Integrate with Ranking Check Workflow.
 * 
 * Tests the integration of SERP feature detection into the ranking check workflow.
 */
class RankingServiceFeatureIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run all migrations including the new column
        $this->artisan('migrate');
    }

    /**
     * T1: Ranking check triggers feature detection when enabled.
     */
    public function test_ranking_check_triggers_feature_detection(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
        ]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn(
            $this->createMockSerpResult('test keyword', [])
        );

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->expects($this->once())
            ->method('detectFeatures')
            ->with('test keyword', $this->anything(), $this->anything())
            ->willReturn([
                ['type' => 'featured_snippet', 'is_present' => false, 'checked_at' => now()],
            ]);

        $mockRepo = $this->createMock(SerpFeatureRepository::class);
        $mockRepo->expects($this->once())
            ->method('insertBatch');

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: $mockRepo
        );

        $service->checkAndStore($keyword->id);
    }

    /**
     * T2: Features stored in serp_features table.
     */
    public function test_features_stored_in_serp_features_table(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
        ]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn(
            $this->createMockSerpResult('test keyword', [])
        );

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->method('detectFeatures')->willReturn([
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com', 'checked_at' => now()],
            ['type' => 'local_pack', 'is_present' => false, 'checked_at' => now()],
        ]);

        $mockRepo = app(SerpFeatureRepository::class);
        $mockTracker = $this->createMock(SerpFeatureChangeTracker::class);
        $mockTracker->method('trackChanges')->willReturn([]);

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: $mockRepo,
            changeTracker: $mockTracker
        );

        $service->checkAndStore($keyword->id);

        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
        ]);

        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $keyword->id,
            'feature_type' => 'local_pack',
            'is_present' => false,
        ]);
    }

    /**
     * T3: Rankings.serp_features JSON updated.
     */
    public function test_rankings_serp_features_json_updated(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
        ]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockResult = $this->createMock(SERPResult::class);
        $mockResult->method('getKeyword')->willReturn('test keyword');
        $mockResult->method('getResults')->willReturn([]);
        $mockResult->method('getDurationMs')->willReturn(100);
        $mockResult->method('getSerpFeatures')->willReturn(['featured_snippet' => true]);
        $mockFetcher->method('fetch')->willReturn($mockResult);

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->method('detectFeatures')->willReturn([]);

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class)
        );

        $service->checkAndStore($keyword->id);

        $ranking = Ranking::where('keyword_id', $keyword->id)->first();
        $this->assertNotNull($ranking);
        $this->assertNotNull($ranking->serp_features);
    }

    /**
     * T4: Change tracker triggered on changes.
     */
    public function test_change_tracker_triggered_on_changes(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
        ]);

        // Create previous feature to simulate change
        SerpFeature::create([
            'keyword_id' => $keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()->subDay(),
        ]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn(
            $this->createMockSerpResult('test keyword', [])
        );

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->method('detectFeatures')->willReturn([
            ['type' => 'featured_snippet', 'is_present' => true, 'checked_at' => now()],
        ]);

        $mockTracker = $this->createMock(SerpFeatureChangeTracker::class);
        $mockTracker->expects($this->once())
            ->method('trackChanges')
            ->willReturn([]);

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class),
            changeTracker: $mockTracker
        );

        $service->checkAndStore($keyword->id);
    }

    /**
     * T5: Feature detection failure doesn't break ranking.
     */
    public function test_feature_detection_failure_doesnt_break_ranking(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
        ]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn(
            $this->createMockSerpResult('test keyword', [])
        );

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->method('detectFeatures')
            ->willThrowException(new \RuntimeException('Detection failed'));

        Log::shouldReceive('error')->once();

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class)
        );

        // Should not throw
        $result = $service->checkAndStore($keyword->id);
        $this->assertTrue($result);

        // Ranking should still be created
        $this->assertDatabaseHas('ranking_history', [
            'keyword_id' => $keyword->id,
        ]);
    }

    /**
     * T6: Site with enableSerpFeatures=true runs feature detection.
     */
    public function test_site_with_feature_flag_enabled_runs_detection(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn($this->createMockSerpResult('test', []));

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->expects($this->once())->method('detectFeatures');

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class)
        );

        $service->checkAndStore($keyword->id);
    }

    /**
     * T7: Site with enableSerpFeatures=false skips feature detection.
     */
    public function test_site_with_feature_flag_disabled_skips_detection(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => false]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn($this->createMockSerpResult('test', []));

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->expects($this->never())->method('detectFeatures');

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class)
        );

        $service->checkAndStore($keyword->id);
    }

    /**
     * T8: Site without config flag (default) skips feature detection.
     */
    public function test_site_without_config_flag_skips_detection(): void
    {
        $site = Site::factory()->create(); // No enable_serp_features set
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn($this->createMockSerpResult('test', []));

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->expects($this->never())->method('detectFeatures');

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class)
        );

        $service->checkAndStore($keyword->id);
    }

    /**
     * T9-T11: Error handling - detector/repository/tracker exceptions.
     */
    public function test_repository_failure_doesnt_break_ranking(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn($this->createMockSerpResult('test', []));

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->method('detectFeatures')->willReturn([
            ['type' => 'featured_snippet', 'is_present' => true, 'checked_at' => now()],
        ]);

        $mockRepo = $this->createMock(SerpFeatureRepository::class);
        $mockRepo->method('insertBatch')->willThrowException(new \RuntimeException('DB error'));

        Log::shouldReceive('error')->once();

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: $mockRepo
        );

        // Should not throw
        $result = $service->checkAndStore($keyword->id);
        $this->assertTrue($result);
    }

    /**
     * T15: First check (no previous features) - no 'gained' events recorded.
     */
    public function test_first_check_no_gained_events(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn($this->createMockSerpResult('test', []));

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->method('detectFeatures')->willReturn([
            ['type' => 'featured_snippet', 'is_present' => true, 'checked_at' => now()],
        ]);

        $historyRepo = app(SerpFeatureHistoryRepository::class);
        $tracker = new SerpFeatureChangeTracker($historyRepo);

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class),
            changeTracker: $tracker
        );

        $service->checkAndStore($keyword->id);

        // No history should be created for first check
        $this->assertDatabaseCount('serp_feature_history', 0);
    }

    /**
     * T16: Empty SERP result - no features detected, no crash.
     */
    public function test_empty_serp_result_no_crash(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn($this->createMockSerpResult('test', []));

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->method('detectFeatures')->willReturn([
            ['type' => 'featured_snippet', 'is_present' => false, 'checked_at' => now()],
        ]);

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class)
        );

        $result = $service->checkAndStore($keyword->id);
        $this->assertTrue($result);
    }

    /**
     * T17: Null keyword.site - feature detection skipped.
     */
    public function test_null_keyword_site_skips_detection(): void
    {
        $user = \App\Models\User::factory()->create();
        $keyword = Keyword::factory()->create(['site_id' => null, 'user_id' => $user->id]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn($this->createMockSerpResult('test', []));

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->expects($this->never())->method('detectFeatures');

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class)
        );

        $service->checkAndStore($keyword->id);
    }

    /**
     * T18: Multiple features detected - all stored correctly.
     */
    public function test_multiple_features_stored_correctly(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn($this->createMockSerpResult('test', []));

        $mockDetector = $this->createMock(SERPFeatureDetector::class);
        $mockDetector->method('detectFeatures')->willReturn([
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com', 'checked_at' => now()],
            ['type' => 'local_pack', 'is_present' => true, 'position' => 2, 'checked_at' => now()],
            ['type' => 'image_pack', 'is_present' => false, 'checked_at' => now()],
            ['type' => 'sitelinks', 'is_present' => true, 'checked_at' => now()],
        ]);

        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $mockDetector,
            featureRepository: app(SerpFeatureRepository::class)
        );

        $service->checkAndStore($keyword->id);

        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
        ]);

        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $keyword->id,
            'feature_type' => 'local_pack',
            'is_present' => true,
            'position' => 2,
        ]);

        $this->assertDatabaseHas('serp_features', [
            'keyword_id' => $keyword->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
        ]);
    }

    /**
     * T13: Performance test - feature detection adds <1 second.
     */
    public function test_feature_detection_performance(): void
    {
        $site = Site::factory()->create(['enable_serp_features' => true]);
        $keyword = Keyword::factory()->create(['site_id' => $site->id]);

        $mockFetcher = $this->createMock(\App\Services\SERPFetcherInterface::class);
        $mockFetcher->method('fetch')->willReturn($this->createMockSerpResult('test', []));

        // Create a detector that simulates processing time
        $detector = new SERPFeatureDetector();
        
        $start = microtime(true);
        
        $service = new RankCheckerService(
            fetcher: $mockFetcher,
            featureDetector: $detector,
            featureRepository: app(SerpFeatureRepository::class)
        );

        $service->checkAndStore($keyword->id);
        
        $duration = microtime(true) - $start;
        
        // Feature detection should add less than 1 second
        $this->assertLessThan(1.0, $duration, 'Feature detection should add <1 second');
    }

    /**
     * Helper: Create mock SERP result.
     */
    protected function createMockSerpResult(string $keyword, array $results): SERPResult
    {
        $mock = $this->createMock(SERPResult::class);
        $mock->method('getKeyword')->willReturn($keyword);
        $mock->method('getResults')->willReturn([]);
        $mock->method('getDurationMs')->willReturn(100);
        return $mock;
    }
}
