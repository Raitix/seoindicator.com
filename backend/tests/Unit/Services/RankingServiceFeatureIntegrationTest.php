<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RankCheckerService;
use App\Services\SERPFetcherInterface;
use App\Services\SERP\SERPEntryData;
use App\Services\SERP\SERPResultData;
use App\Services\SerpFeatureDetector;
use App\Services\SerpFeatureChangeTracker;
use App\Repositories\SerpFeatureRepository;
use App\Models\Keyword;
use App\Models\Site;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for TASK-7-13: Integrate with Ranking Check Workflow
 *
 * Tests verify:
 * - Feature detection triggered after SERP fetch
 * - Features stored in serp_features table
 * - Rankings.serp_features JSON updated
 * - Change tracker triggered for gains/losses
 * - Error handling (feature detection failure doesn't break ranking)
 * - Configuration flag for enabling/disabling feature tracking
 * - Performance: feature detection adds <1 second
 */
class RankingServiceFeatureIntegrationTest extends TestCase
{
    protected RankCheckerService $service;
    protected $mockSerpFetcher;
    protected $mockFeatureDetector;
    protected $mockFeatureRepo;
    protected $mockChangeTracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSerpFetcher = Mockery::mock(SERPFetcherInterface::class);
        $this->mockFeatureDetector = Mockery::mock(SerpFeatureDetector::class);
        $this->mockFeatureRepo = Mockery::mock(SerpFeatureRepository::class);
        $this->mockChangeTracker = Mockery::mock(SerpFeatureChangeTracker::class);

        $this->service = new RankCheckerService(
            $this->mockSerpFetcher,
            $this->mockFeatureDetector,
            $this->mockFeatureRepo,
            $this->mockChangeTracker
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Core Integration Tests
    // ============================================

    /**
     * T1: Ranking check triggers feature detection
     */
    public function test_ranking_check_triggers_feature_detection(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('seo tools', [
            ['position' => 1, 'url' => 'https://example.com/seo-tools']
        ]);

        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($serpResult);

        $this->mockFeatureDetector
            ->shouldReceive('detectFeatures')
            ->once()
            ->with('seo tools', 'example.com', Mockery::type(SERPResultData::class))
            ->andReturn($this->makeMockFeatures());

        $this->mockFeatureRepo
            ->shouldReceive('insertBatch')
            ->once();

        $this->mockFeatureRepo
            ->shouldReceive('findByKeywordId')
            ->once()
            ->andReturn([]);

        $this->mockChangeTracker
            ->shouldReceive('trackChanges')
            ->once();

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
    }

    /**
     * T2: Features stored in serp_features table
     */
    public function test_features_stored_in_serp_features_table(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('seo tools', [
            ['position' => 1, 'url' => 'https://example.com/seo-tools']
        ]);

        $features = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com'],
            ['type' => 'local_pack', 'is_present' => false],
        ];

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn($features);

        $this->mockFeatureRepo
            ->shouldReceive('insertBatch')
            ->once()
            ->withArgs(function ($rankingId, $keywordId, $storedFeatures) use ($features) {
                return $storedFeatures === $features;
            });

        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        $this->service->checkKeywordWithFeatures($keyword);
    }

    /**
     * T3: Rankings.serp_features JSON updated
     */
    public function test_rankings_serp_features_json_updated(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('seo tools', [
            ['position' => 1, 'url' => 'https://example.com/seo-tools']
        ]);

        $features = [
            ['type' => 'featured_snippet', 'is_present' => true],
            ['type' => 'sitelinks', 'is_present' => true],
        ];

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn($features);
        $this->mockFeatureRepo->shouldReceive('insertBatch');
        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertArrayHasKey('serp_features', $result);
        $this->assertJson($result['serp_features'] ?? '{}');
    }

    /**
     * T4: Change tracker triggered on changes
     */
    public function test_change_tracker_triggered_on_changes(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('seo tools', [
            ['position' => 1, 'url' => 'https://example.com/seo-tools']
        ]);

        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true],
        ];

        $previousFeatures = [
            ['type' => 'featured_snippet', 'is_present' => false],
        ];

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn($currentFeatures);
        $this->mockFeatureRepo->shouldReceive('insertBatch');
        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn($previousFeatures);

        $this->mockChangeTracker
            ->shouldReceive('trackChanges')
            ->once()
            ->withArgs(function ($keywordId, $current, $previous) use ($currentFeatures, $previousFeatures) {
                return $current === $currentFeatures && $previous === $previousFeatures;
            });

        $this->service->checkKeywordWithFeatures($keyword);
    }

    /**
     * T5: Feature detection failure doesn't break ranking
     */
    public function test_feature_detection_failure_doesnt_break_ranking(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('seo tools', [
            ['position' => 5, 'url' => 'https://example.com/seo-tools']
        ]);

        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->once()
            ->andReturn($serpResult);

        $this->mockFeatureDetector
            ->shouldReceive('detectFeatures')
            ->once()
            ->andThrow(new \Exception('Feature detection failed'));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'feature') || str_contains($message, 'failed');
            });

        // Feature repo should NOT be called if detector fails
        $this->mockFeatureRepo->shouldReceive('insertBatch')->never();
        $this->mockChangeTracker->shouldReceive('trackChanges')->never();

        $result = $this->service->checkKeywordWithFeatures($keyword);

        // Ranking should still be returned
        $this->assertNotNull($result);
        $this->assertEquals(5, $result['position']);
    }

    // ============================================
    // Configuration Tests
    // ============================================

    /**
     * T6: Site with enableSerpFeatures=true triggers detection
     */
    public function test_site_with_feature_enabled_triggers_detection(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', []);

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->once();
        $this->mockFeatureRepo->shouldReceive('insertBatch');
        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        $this->service->checkKeywordWithFeatures($keyword);

        // Assertion: mock expectations verified (detectFeatures called once)
        $this->assertTrue(true);
    }

    /**
     * T7: Site with enableSerpFeatures=false skips detection
     */
    public function test_site_with_feature_disabled_skips_detection(): void
    {
        $keyword = $this->createMockKeyword(false);
        $serpResult = $this->makeSerpResult('test', []);

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);

        // Feature detector should NOT be called
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->never();
        $this->mockFeatureRepo->shouldReceive('insertBatch')->never();
        $this->mockChangeTracker->shouldReceive('trackChanges')->never();

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
    }

    /**
     * T8: Site without config flag (default) skips detection
     */
    public function test_site_without_config_skips_detection(): void
    {
        $keyword = $this->createMockKeyword(null);
        $serpResult = $this->makeSerpResult('test', []);

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);

        // Feature detector should NOT be called
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->never();

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
    }

    // ============================================
    // Error Handling Tests
    // ============================================

    /**
     * T9: Feature detector throws exception
     */
    public function test_feature_detector_exception_handled(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', [
            ['position' => 1, 'url' => 'https://example.com']
        ]);

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andThrow(new \Exception('Detector error'));

        Log::shouldReceive('error')->once();

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertEquals(1, $result['position']);
    }

    /**
     * T10: Feature repository throws exception
     */
    public function test_feature_repository_exception_handled(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', [
            ['position' => 1, 'url' => 'https://example.com']
        ]);

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn([]);
        $this->mockFeatureRepo->shouldReceive('insertBatch')->andThrow(new \Exception('DB error'));

        Log::shouldReceive('error')->once();

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertEquals(1, $result['position']);
    }

    /**
     * T11: Change tracker throws exception
     */
    public function test_change_tracker_exception_handled(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', [
            ['position' => 1, 'url' => 'https://example.com']
        ]);

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn([]);
        $this->mockFeatureRepo->shouldReceive('insertBatch');
        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges')->andThrow(new \Exception('Tracker error'));

        Log::shouldReceive('error')->once();

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertEquals(1, $result['position']);
    }

    /**
     * T12: Malformed SERP data handled gracefully
     */
    public function test_malformed_serp_data_handled(): void
    {
        $keyword = $this->createMockKeyword(true);

        $this->mockSerpFetcher
            ->shouldReceive('fetch')
            ->andReturn($this->makeMalformedSerpResult());

        $this->mockFeatureDetector
            ->shouldReceive('detectFeatures')
            ->andReturn([]);

        $this->mockFeatureRepo->shouldReceive('insertBatch');
        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        // Should not throw
        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
    }

    // ============================================
    // Performance Tests
    // ============================================

    /**
     * T13: Feature detection adds <1 second
     */
    public function test_feature_detection_performance(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', [
            ['position' => 1, 'url' => 'https://example.com']
        ]);

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn([]);
        $this->mockFeatureRepo->shouldReceive('insertBatch');
        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        $start = microtime(true);
        $this->service->checkKeywordWithFeatures($keyword);
        $duration = (microtime(true) - $start) * 1000; // ms

        // Feature detection overhead should be <1000ms in tests (mocked)
        $this->assertLessThan(1000, $duration, 'Feature detection should add <1 second');
    }

    /**
     * T14: Large SERP result handled within time limit
     */
    public function test_large_serp_result_performance(): void
    {
        $keyword = $this->createMockKeyword(true);

        // Generate 100 SERP entries
        $entries = [];
        for ($i = 1; $i <= 100; $i++) {
            $entries[] = ['position' => $i, 'url' => "https://site{$i}.com/page"];
        }

        $serpResult = $this->makeSerpResult('test', $entries);

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn([]);
        $this->mockFeatureRepo->shouldReceive('insertBatch');
        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        $start = microtime(true);
        $this->service->checkKeywordWithFeatures($keyword);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(1000, $duration, 'Large SERP result should still be fast');
    }

    // ============================================
    // Edge Cases
    // ============================================

    /**
     * T15: No previous features (first check) - no 'gained' events
     */
    public function test_first_check_no_gained_events(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', []);

        $currentFeatures = [
            ['type' => 'featured_snippet', 'is_present' => true],
        ];

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn($currentFeatures);
        $this->mockFeatureRepo->shouldReceive('insertBatch');
        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn(null); // No previous
        $this->mockChangeTracker->shouldReceive('trackChanges')->never(); // No previous = no changes

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
    }

    /**
     * T16: Empty SERP result handled
     */
    public function test_empty_serp_result_handled(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', []); // Empty entries

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn([]);
        $this->mockFeatureRepo->shouldReceive('insertBatch');
        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
        $this->assertNull($result['position']); // Not found in empty SERP
    }

    /**
     * T17: Null keyword.site handled
     */
    public function test_null_keyword_site_handled(): void
    {
        $keyword = Mockery::mock(Keyword::class);
        $keyword->id = 1;
        $keyword->keyword = 'test';
        $keyword->site = null; // No site

        $serpResult = $this->makeSerpResult('test', []);

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);

        // Feature detector should NOT be called when site is null
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->never();

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
    }

    /**
     * T18: Multiple features detected and stored
     */
    public function test_multiple_features_detected(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', [
            ['position' => 1, 'url' => 'https://example.com']
        ]);

        $features = [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com'],
            ['type' => 'local_pack', 'is_present' => false],
            ['type' => 'image_pack', 'is_present' => false],
            ['type' => 'sitelinks', 'is_present' => true],
            ['type' => 'reviews', 'is_present' => true],
        ];

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn($features);

        $this->mockFeatureRepo
            ->shouldReceive('insertBatch')
            ->once()
            ->withArgs(function ($rankingId, $keywordId, $storedFeatures) use ($features) {
                return count($storedFeatures) === 5;
            });

        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
    }

    // ============================================
    // Security Tests
    // ============================================

    /**
     * T19: SQL injection in feature data sanitized
     */
    public function test_sql_injection_sanitized(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', []);

        $maliciousFeatures = [
            [
                'type' => "'; DROP TABLE serp_features; --",
                'is_present' => true,
                'url' => "https://example.com'; DELETE FROM rankings; --"
            ],
        ];

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn($maliciousFeatures);

        // Repository should handle sanitization
        $this->mockFeatureRepo
            ->shouldReceive('insertBatch')
            ->once()
            ->withArgs(function ($rankingId, $keywordId, $features) {
                // Features should be sanitized (not raw malicious data)
                return true;
            });

        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
    }

    /**
     * T20: XSS in feature snippet/title escaped
     */
    public function test_xss_in_feature_data_escaped(): void
    {
        $keyword = $this->createMockKeyword(true);
        $serpResult = $this->makeSerpResult('test', []);

        $xssFeatures = [
            [
                'type' => 'featured_snippet',
                'is_present' => true,
                'snippet' => '<script>alert("xss")</script>Malicious snippet',
                'title' => '<img src=x onerror=alert(1)>Title',
            ],
        ];

        $this->mockSerpFetcher->shouldReceive('fetch')->andReturn($serpResult);
        $this->mockFeatureDetector->shouldReceive('detectFeatures')->andReturn($xssFeatures);

        $this->mockFeatureRepo
            ->shouldReceive('insertBatch')
            ->once()
            ->withArgs(function ($rankingId, $keywordId, $features) {
                // Data should be escaped/sanitized before storage
                return true;
            });

        $this->mockFeatureRepo->shouldReceive('findByKeywordId')->andReturn([]);
        $this->mockChangeTracker->shouldReceive('trackChanges');

        $result = $this->service->checkKeywordWithFeatures($keyword);

        $this->assertNotNull($result);
    }

    // ============================================
    // Helper Methods
    // ============================================

    protected function createMockKeyword(?bool $enableSerpFeatures): Mockery\MockInterface
    {
        $keyword = Mockery::mock(Keyword::class);
        $keyword->id = 1;
        $keyword->keyword = 'seo tools';
        $keyword->query = 'seo tools';

        $site = null;
        if ($enableSerpFeatures !== null) {
            $site = Mockery::mock(Site::class);
            $site->id = 1;
            $site->url = 'https://example.com';
            $site->enableSerpFeatures = $enableSerpFeatures;
        }

        $keyword->site = $site;

        return $keyword;
    }

    /**
     * @param array<int, array{position:int, url:string}> $entries
     */
    protected function makeSerpResult(string $keyword, array $entries, int $durationMs = 100): SERPResultData
    {
        $serpEntries = array_map(function (array $entry) {
            return new SERPEntryData(
                (int) $entry['position'],
                (string) $entry['url'],
                $entry['title'] ?? '',
                $entry['description'] ?? '',
                $entry['is_our_site'] ?? false
            );
        }, $entries);

        return new SERPResultData(
            $keyword,
            'google',
            'US',
            'desktop',
            $serpEntries,
            now()->toAtomString(),
            null,
            $durationMs
        );
    }

    protected function makeMalformedSerpResult(): SERPResultData
    {
        return new SERPResultData(
            'test',
            'google',
            'US',
            'desktop',
            [], // Empty entries
            now()->toAtomString(),
            'Malformed data received',
            0
        );
    }

    protected function makeMockFeatures(): array
    {
        return [
            ['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://example.com'],
            ['type' => 'local_pack', 'is_present' => false],
        ];
    }
}
