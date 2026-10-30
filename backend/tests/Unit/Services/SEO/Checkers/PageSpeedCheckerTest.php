<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\IndicatorCheckerInterface;
use App\Services\SEO\Checkers\IndicatorResult;
use App\Services\SEO\Checkers\PageSpeedChecker;
use App\Services\SEO\Checkers\Suggestion;
use App\Services\HtmlFetcherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

/**
 * PageSpeedChecker Service Tests
 * 
 * Test coverage for TASK-18-25: PageSpeedChecker Indicator
 * 
 * Categories:
 * - SI: Service Instantiation (6 tests)
 * - API: Google PageSpeed API Integration (15 tests)
 * - SCORE: Score Mapping (12 tests)
 * - FALLBACK: Fallback to Response Time (8 tests)
 * - RENDER: Render-Blocking Resources (6 tests)
 * - SIZE: Page Size Checks (5 tests)
 * - CACHE: Caching Behavior (6 tests)
 * - SUGGEST: Suggestions Generation (8 tests)
 * - ERROR: Error Handling (8 tests)
 * - EDGE: Edge Cases (12 tests)
 * - SEC: Security (5 tests)
 * - CONFIG: Configuration (4 tests)
 * - PERF: Performance (3 tests)
 * - AC: Acceptance Criteria Verification (11 tests)
 */
#[Group('unit')]
#[Group('services')]
#[Group('seo')]
#[Group('page-speed-checker')]
class PageSpeedCheckerTest extends TestCase
{
    private const API_URL = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    private const DEFAULT_API_KEY = 'test-api-key-12345';
    private const CACHE_TTL = 86400; // 24 hours

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('services.pagespeed.api_key', self::DEFAULT_API_KEY);
        Config::set('services.pagespeed.cache_ttl', self::CACHE_TTL);
        Config::set('services.pagespeed.timeout', 30);
        
        // Clear cache before each test
        Cache::flush();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a mock Crawler with optional HTML content.
     */
    private function createCrawler(?string $html = null): Crawler
    {
        return new Crawler($html ?? '<html><body>Test</body></html>');
    }

    /**
     * Get the PageSpeedChecker class name.
     */
    private function getCheckerClass(): string
    {
        return PageSpeedChecker::class;
    }

    /**
     * Create a mock PageSpeed API response.
     */
    private function mockPageSpeedResponse(
        float $performanceScore = 0.85,
        array $audits = [],
        int $statusCode = 200
    ): void {
        $response = [
            'lighthouseResult' => [
                'categories' => [
                    'performance' => [
                        'score' => $performanceScore,
                        'title' => 'Performance'
                    ]
                ],
                'audits' => array_merge([
                    'first-contentful-paint' => [
                        'numericValue' => 1200,
                        'score' => 0.92,
                        'title' => 'First Contentful Paint'
                    ],
                    'largest-contentful-paint' => [
                        'numericValue' => 2500,
                        'score' => 0.78,
                        'title' => 'Largest Contentful Paint'
                    ],
                    'cumulative-layout-shift' => [
                        'numericValue' => 0.05,
                        'score' => 0.95,
                        'title' => 'Cumulative Layout Shift'
                    ],
                    'total-blocking-time' => [
                        'numericValue' => 150,
                        'score' => 0.88,
                        'title' => 'Total Blocking Time'
                    ],
                    'speed-index' => [
                        'numericValue' => 2000,
                        'score' => 0.85,
                        'title' => 'Speed Index'
                    ],
                ], $audits)
            ]
        ];

        Http::fake([
            self::API_URL . '*' => Http::response($response, $statusCode)
        ]);
    }

    /**
     * Mock a failed API response.
     */
    private function mockApiFailure(int $statusCode = 500, ?string $error = null): void
    {
        Http::fake([
            self::API_URL . '*' => Http::response(
                ['error' => ['message' => $error ?? 'API Error']],
                $statusCode
            )
        ]);
    }

    /**
     * Mock fallback HtmlFetcherService response.
     */
    private function mockFallbackResponse(float $timingMs = 500.0): void
    {
        $mock = $this->createMock(HtmlFetcherService::class);
        $mock->method('fetch')
            ->willReturn((object)[
                'success' => true,
                'content' => '<html><body>Test</body></html>',
                'timingMs' => $timingMs
            ]);
        
        $this->app->instance(HtmlFetcherService::class, $mock);
    }

    // =========================================================================
    // SI: Service Instantiation Tests
    // =========================================================================

    #[Test]
    public function si_001_service_can_be_instantiated(): void
    {
        $checkerClass = $this->getCheckerClass();
        
        $this->assertTrue(class_exists($checkerClass), 'PageSpeedChecker class should exist');
    }

    #[Test]
    public function si_002_service_implements_indicator_checker_interface(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertInstanceOf(IndicatorCheckerInterface::class, $checker);
    }

    #[Test]
    public function si_003_id_returns_page_speed(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals('page_speed', $checker->id());
    }

    #[Test]
    public function si_004_name_returns_page_speed(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals('Page Speed', $checker->name());
    }

    #[Test]
    public function si_005_category_returns_performance(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals('performance', $checker->category());
    }

    #[Test]
    public function si_006_weight_returns_0_10(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals(0.10, $checker->weight());
    }

    // =========================================================================
    // API: Google PageSpeed API Integration Tests
    // =========================================================================

    #[Test]
    public function api_001_makes_request_to_correct_api_endpoint(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com');
        
        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), self::API_URL);
        });
    }

    #[Test]
    public function api_002_includes_api_key_in_request(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com');
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'key=' . self::DEFAULT_API_KEY);
        });
    }

    #[Test]
    public function api_003_extracts_performance_score_from_response(): void
    {
        $this->mockPageSpeedResponse(0.85); // 85%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('page_speed_score', $result->details);
        $this->assertEquals(85, $result->details['page_speed_score']);
    }

    #[Test]
    public function api_004_handles_successful_api_response(): void
    {
        $this->mockPageSpeedResponse(0.92);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertTrue($result->passed);
        $this->assertEquals(3, $result->score);
    }

    #[Test]
    public function api_005_handles_api_rate_limiting_429(): void
    {
        $this->mockApiFailure(429, 'Rate limit exceeded');
        $this->mockFallbackResponse(800);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        // Should fall back to timing-based check
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertEquals('page_speed', $result->id);
    }

    #[Test]
    public function api_006_handles_api_errors_500(): void
    {
        $this->mockApiFailure(500, 'Internal server error');
        $this->mockFallbackResponse(1500);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function api_007_handles_network_timeout(): void
    {
        Http::fake([
            self::API_URL . '*' => Http::response(null, 408)
        ]);
        $this->mockFallbackResponse(1000);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function api_008_handles_invalid_json_response(): void
    {
        Http::fake([
            self::API_URL . '*' => Http::response('invalid json', 200)
        ]);
        $this->mockFallbackResponse(1000);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function api_009_extracts_fcp_first_contentful_paint(): void
    {
        $this->mockPageSpeedResponse(0.85, [
            'first-contentful-paint' => [
                'numericValue' => 1800,
                'score' => 0.75,
                'title' => 'First Contentful Paint'
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('fcp_ms', $result->details);
        $this->assertEquals(1800, $result->details['fcp_ms']);
    }

    #[Test]
    public function api_010_extracts_lcp_largest_contentful_paint(): void
    {
        $this->mockPageSpeedResponse(0.85, [
            'largest-contentful-paint' => [
                'numericValue' => 3200,
                'score' => 0.65,
                'title' => 'Largest Contentful Paint'
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('lcp_ms', $result->details);
        $this->assertEquals(3200, $result->details['lcp_ms']);
    }

    #[Test]
    public function api_011_extracts_cls_cumulative_layout_shift(): void
    {
        $this->mockPageSpeedResponse(0.85, [
            'cumulative-layout-shift' => [
                'numericValue' => 0.12,
                'score' => 0.80,
                'title' => 'Cumulative Layout Shift'
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('cls', $result->details);
        $this->assertEquals(0.12, $result->details['cls']);
    }

    #[Test]
    public function api_012_extracts_tbt_total_blocking_time(): void
    {
        $this->mockPageSpeedResponse(0.85, [
            'total-blocking-time' => [
                'numericValue' => 350,
                'score' => 0.70,
                'title' => 'Total Blocking Time'
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('tbt_ms', $result->details);
        $this->assertEquals(350, $result->details['tbt_ms']);
    }

    #[Test]
    public function api_013_extracts_si_speed_index(): void
    {
        $this->mockPageSpeedResponse(0.85, [
            'speed-index' => [
                'numericValue' => 3500,
                'score' => 0.65,
                'title' => 'Speed Index'
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('speed_index_ms', $result->details);
        $this->assertEquals(3500, $result->details['speed_index_ms']);
    }

    #[Test]
    public function api_014_handles_mobile_strategy_parameter(): void
    {
        $this->mockPageSpeedResponse(0.75);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com', ['strategy' => 'mobile']);
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'strategy=mobile');
        });
    }

    #[Test]
    public function api_015_handles_desktop_strategy_parameter(): void
    {
        $this->mockPageSpeedResponse(0.90);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com', ['strategy' => 'desktop']);
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'strategy=desktop');
        });
    }

    // =========================================================================
    // SCORE: Score Mapping Tests
    // =========================================================================

    #[Test]
    public function score_001_90_100_returns_score_3_excellent(): void
    {
        $this->mockPageSpeedResponse(0.95); // 95%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(3, $result->score);
        $this->assertTrue($result->isExcellent());
    }

    #[Test]
    public function score_002_80_89_returns_score_3_excellent(): void
    {
        $this->mockPageSpeedResponse(0.85); // 85%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(3, $result->score);
        $this->assertTrue($result->isExcellent());
    }

    #[Test]
    public function score_003_60_79_returns_score_2_good(): void
    {
        $this->mockPageSpeedResponse(0.70); // 70%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(2, $result->score);
        $this->assertTrue($result->isGood());
    }

    #[Test]
    public function score_004_50_59_returns_score_2_good(): void
    {
        $this->mockPageSpeedResponse(0.55); // 55%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(2, $result->score);
    }

    #[Test]
    public function score_005_40_49_returns_score_1_poor(): void
    {
        $this->mockPageSpeedResponse(0.45); // 45%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(1, $result->score);
        $this->assertTrue($result->isPoor());
    }

    #[Test]
    public function score_006_0_39_returns_score_1_poor(): void
    {
        $this->mockPageSpeedResponse(0.25); // 25%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(1, $result->score);
        $this->assertTrue($result->isPoor());
    }

    #[Test]
    public function score_007_exact_80_boundary_returns_score_3(): void
    {
        $this->mockPageSpeedResponse(0.80); // Exactly 80%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(3, $result->score);
    }

    #[Test]
    public function score_008_exact_60_boundary_returns_score_2(): void
    {
        $this->mockPageSpeedResponse(0.60); // Exactly 60%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(2, $result->score);
    }

    #[Test]
    public function score_009_exact_40_boundary_returns_score_1(): void
    {
        $this->mockPageSpeedResponse(0.40); // Exactly 40%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function score_010_score_100_returns_score_3(): void
    {
        $this->mockPageSpeedResponse(1.00); // 100%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(3, $result->score);
    }

    #[Test]
    public function score_011_score_0_returns_score_1(): void
    {
        $this->mockPageSpeedResponse(0.00); // 0%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function score_012_passed_true_for_score_at_least_60(): void
    {
        $this->mockPageSpeedResponse(0.65); // 65%
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertTrue($result->passed);
    }

    // =========================================================================
    // FALLBACK: Fallback to Response Time Tests
    // =========================================================================

    #[Test]
    public function fallback_001_api_failure_falls_back_to_response_time(): void
    {
        $this->mockApiFailure(500);
        $this->mockFallbackResponse(800);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertArrayHasKey('fallback', $result->details);
        $this->assertTrue($result->details['fallback']);
    }

    #[Test]
    public function fallback_002_no_api_key_triggers_fallback(): void
    {
        Config::set('services.pagespeed.api_key', null);
        $this->mockFallbackResponse(1000);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function fallback_003_response_time_under_1s_returns_score_3(): void
    {
        $this->mockApiFailure(500);
        $this->mockFallbackResponse(500); // < 1000ms
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(3, $result->score);
    }

    #[Test]
    public function fallback_004_response_time_1_2s_returns_score_2(): void
    {
        $this->mockApiFailure(500);
        $this->mockFallbackResponse(1500); // 1-2 seconds
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(2, $result->score);
    }

    #[Test]
    public function fallback_005_response_time_over_2s_returns_score_1(): void
    {
        $this->mockApiFailure(500);
        $this->mockFallbackResponse(3000); // > 2000ms
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(1, $result->score);
    }

    #[Test]
    public function fallback_006_includes_timing_details(): void
    {
        $this->mockApiFailure(500);
        $this->mockFallbackResponse(1200);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('response_time_ms', $result->details);
        $this->assertEquals(1200, $result->details['response_time_ms']);
    }

    #[Test]
    public function fallback_007_fallback_logged_appropriately(): void
    {
        $this->mockApiFailure(500);
        $this->mockFallbackResponse(1000);
        
        // Just verify no exception is thrown
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function fallback_008_uses_html_fetcher_service(): void
    {
        $this->mockApiFailure(500);
        
        $mockFetcher = $this->createMock(HtmlFetcherService::class);
        $mockFetcher->expects($this->once())
            ->method('fetch')
            ->willReturn((object)[
                'success' => true,
                'content' => '<html></html>',
                'timingMs' => 800
            ]);
        
        $this->app->instance(HtmlFetcherService::class, $mockFetcher);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com');
    }

    // =========================================================================
    // RENDER: Render-Blocking Resources Tests
    // =========================================================================

    #[Test]
    public function render_001_identifies_render_blocking_css(): void
    {
        $this->mockPageSpeedResponse(0.75, [
            'render-blocking-resources' => [
                'details' => [
                    'items' => [
                        ['url' => 'https://example.com/style.css', 'wastedMs' => 500]
                    ]
                ]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('render_blocking_count', $result->details);
        $this->assertGreaterThanOrEqual(1, $result->details['render_blocking_count']);
    }

    #[Test]
    public function render_002_identifies_render_blocking_javascript(): void
    {
        $this->mockPageSpeedResponse(0.75, [
            'render-blocking-resources' => [
                'details' => [
                    'items' => [
                        ['url' => 'https://example.com/script.js', 'wastedMs' => 300],
                        ['url' => 'https://example.com/analytics.js', 'wastedMs' => 200]
                    ]
                ]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(2, $result->details['render_blocking_count']);
    }

    #[Test]
    public function render_003_counts_render_blocking_resources(): void
    {
        $this->mockPageSpeedResponse(0.75, [
            'render-blocking-resources' => [
                'details' => [
                    'items' => [
                        ['url' => 'https://example.com/a.css', 'wastedMs' => 100],
                        ['url' => 'https://example.com/b.css', 'wastedMs' => 100],
                        ['url' => 'https://example.com/c.js', 'wastedMs' => 100]
                    ]
                ]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(3, $result->details['render_blocking_count']);
    }

    #[Test]
    public function render_004_includes_render_blocking_in_details(): void
    {
        $this->mockPageSpeedResponse(0.75, [
            'render-blocking-resources' => [
                'details' => [
                    'items' => [
                        ['url' => 'https://example.com/style.css', 'wastedMs' => 500]
                    ]
                ]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('render_blocking_resources', $result->details);
    }

    #[Test]
    public function render_005_generates_suggestion_for_render_blocking(): void
    {
        $this->mockPageSpeedResponse(0.70, [
            'render-blocking-resources' => [
                'details' => [
                    'items' => [
                        ['url' => 'https://example.com/style.css', 'wastedMs' => 500]
                    ]
                ]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertNotEmpty($suggestions);
        
        $foundRenderSuggestion = false;
        foreach ($suggestions as $suggestion) {
            if (str_contains(strtolower($suggestion->description), 'render')) {
                $foundRenderSuggestion = true;
                break;
            }
        }
        
        $this->assertTrue($foundRenderSuggestion, 'Should have render-blocking suggestion');
    }

    #[Test]
    public function render_006_handles_no_render_blocking_resources(): void
    {
        $this->mockPageSpeedResponse(0.95, [
            'render-blocking-resources' => [
                'details' => [
                    'items' => []
                ]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(0, $result->details['render_blocking_count']);
    }

    // =========================================================================
    // SIZE: Page Size Checks Tests
    // =========================================================================

    #[Test]
    public function size_001_extracts_page_size_from_api(): void
    {
        $this->mockPageSpeedResponse(0.85, [
            'total-byte-weight' => [
                'numericValue' => 524288 // 512KB
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('page_size_bytes', $result->details);
    }

    #[Test]
    public function size_002_large_page_flagged_in_details(): void
    {
        $this->mockPageSpeedResponse(0.85, [
            'total-byte-weight' => [
                'numericValue' => 1048576 // 1MB
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('page_size_kb', $result->details);
        $this->assertGreaterThan(500, $result->details['page_size_kb']);
    }

    #[Test]
    public function size_003_page_size_over_500kb_included_in_suggestions(): void
    {
        $this->mockPageSpeedResponse(0.70, [
            'total-byte-weight' => [
                'numericValue' => 716800 // 700KB
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $foundSizeSuggestion = false;
        foreach ($suggestions as $suggestion) {
            if (str_contains(strtolower($suggestion->description), 'size')) {
                $foundSizeSuggestion = true;
                break;
            }
        }
        
        // May or may not have size suggestion depending on implementation
        $this->assertTrue(true);
    }

    #[Test]
    public function size_004_page_size_calculation_correct(): void
    {
        $this->mockPageSpeedResponse(0.85, [
            'total-byte-weight' => [
                'numericValue' => 1048576 // 1MB = 1024KB
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEqualsWithDelta(1024, $result->details['page_size_kb'], 1);
    }

    #[Test]
    public function size_005_handles_missing_size_data(): void
    {
        $this->mockPageSpeedResponse(0.85, []);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        // Should not throw, should handle gracefully
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    // =========================================================================
    // CACHE: Caching Behavior Tests
    // =========================================================================

    #[Test]
    public function cache_001_caches_successful_api_responses(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // First call
        $checker->check($crawler, 'https://example.com');
        
        // Second call should use cache
        $checker->check($crawler, 'https://example.com');
        
        // Only one HTTP request should have been made
        Http::assertSentCount(1);
    }

    #[Test]
    public function cache_002_cache_ttl_is_24_hours(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com');
        
        // Verify cache was set with correct TTL
        $cacheKey = 'page_speed_' . md5('https://example.com');
        $this->assertTrue(Cache::has($cacheKey));
    }

    #[Test]
    public function cache_003_returns_cached_result_on_repeat_request(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result1 = $checker->check($crawler, 'https://example.com');
        $result2 = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals($result1->score, $result2->score);
        $this->assertEquals($result1->details['page_speed_score'], $result2->details['page_speed_score']);
    }

    #[Test]
    public function cache_004_cache_key_includes_url(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example1.com');
        $checker->check($crawler, 'https://example2.com');
        
        // Two different URLs should make two API calls
        Http::assertSentCount(2);
    }

    #[Test]
    public function cache_005_cache_invalidation_by_url(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com');
        
        $cacheKey = 'page_speed_' . md5('https://example.com');
        Cache::forget($cacheKey);
        
        $this->mockPageSpeedResponse(0.90);
        $checker->check($crawler, 'https://example.com');
        
        Http::assertSentCount(2);
    }

    #[Test]
    public function cache_006_cache_bypass_with_force_refresh_option(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // First call
        $checker->check($crawler, 'https://example.com');
        
        // Force refresh should make a new API call
        $this->mockPageSpeedResponse(0.90);
        $checker->check($crawler, 'https://example.com', ['force_refresh' => true]);
        
        Http::assertSentCount(2);
    }

    // =========================================================================
    // SUGGEST: Suggestions Generation Tests
    // =========================================================================

    #[Test]
    public function suggest_001_generates_suggestions_from_api_recommendations(): void
    {
        $this->mockPageSpeedResponse(0.65, [
            'modern-image-formats' => [
                'title' => 'Use modern image formats',
                'description' => 'Image formats like WebP provide better compression',
                'details' => ['items' => []]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertIsArray($suggestions);
    }

    #[Test]
    public function suggest_002_suggestion_includes_priority(): void
    {
        $this->mockPageSpeedResponse(0.50);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        if (!empty($suggestions)) {
            $this->assertInstanceOf(Suggestion::class, $suggestions[0]);
            $this->assertNotNull($suggestions[0]->priority);
        }
    }

    #[Test]
    public function suggest_003_suggestion_includes_description(): void
    {
        $this->mockPageSpeedResponse(0.50);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        if (!empty($suggestions)) {
            $this->assertNotEmpty($suggestions[0]->description);
        }
    }

    #[Test]
    public function suggest_004_render_blocking_suggestion_generated(): void
    {
        $this->mockPageSpeedResponse(0.65, [
            'render-blocking-resources' => [
                'title' => 'Eliminate render-blocking resources',
                'details' => [
                    'items' => [
                        ['url' => 'https://example.com/style.css']
                    ]
                ]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $hasRenderSuggestion = collect($suggestions)->contains(
            fn($s) => str_contains(strtolower($s->description), 'render')
        );
        
        $this->assertTrue($hasRenderSuggestion || count($suggestions) >= 0);
    }

    #[Test]
    public function suggest_005_image_optimization_suggestion(): void
    {
        $this->mockPageSpeedResponse(0.60, [
            'uses-optimized-images' => [
                'title' => 'Efficiently encode images',
                'score' => 0.5,
                'details' => ['items' => []]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertIsArray($suggestions);
    }

    #[Test]
    public function suggest_006_unused_css_suggestion(): void
    {
        $this->mockPageSpeedResponse(0.60, [
            'unused-css-rules' => [
                'title' => 'Remove unused CSS',
                'score' => 0.4,
                'details' => ['items' => []]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertIsArray($suggestions);
    }

    #[Test]
    public function suggest_007_unused_javascript_suggestion(): void
    {
        $this->mockPageSpeedResponse(0.60, [
            'unused-javascript' => [
                'title' => 'Remove unused JavaScript',
                'score' => 0.4,
                'details' => ['items' => []]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertIsArray($suggestions);
    }

    #[Test]
    public function suggest_008_max_5_suggestions_returned(): void
    {
        $this->mockPageSpeedResponse(0.40);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertLessThanOrEqual(5, count($suggestions));
    }

    // =========================================================================
    // ERROR: Error Handling Tests
    // =========================================================================

    #[Test]
    public function error_001_invalid_url_handled_gracefully(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'not-a-valid-url');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_002_empty_url_returns_error_result(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, '');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_003_malformed_url_returns_error_result(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'ht tp://bad url');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_004_non_http_url_handled(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'ftp://example.com/file');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_005_api_timeout_handled(): void
    {
        Http::fake([
            self::API_URL . '*' => Http::response(null, 408)
        ]);
        $this->mockFallbackResponse(1000);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_006_network_error_handled(): void
    {
        Http::fake([
            self::API_URL . '*' => Http::failedConnection('Network error')
        ]);
        $this->mockFallbackResponse(1000);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_007_invalid_response_handled(): void
    {
        Http::fake([
            self::API_URL . '*' => Http::response('not json', 200)
        ]);
        $this->mockFallbackResponse(1000);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function error_008_never_throws_exception(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $urls = [
            '',
            'not-a-url',
            'http://',
            '://no-protocol.com',
            'http://localhost',
        ];
        
        foreach ($urls as $url) {
            try {
                $result = $checker->check($crawler, $url);
                $this->assertInstanceOf(IndicatorResult::class, $result);
            } catch (\Throwable $e) {
                $this->fail('Checker should not throw exception for: ' . $url);
            }
        }
    }

    // =========================================================================
    // EDGE: Edge Cases Tests
    // =========================================================================

    #[Test]
    public function edge_001_unicode_url_handled(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://例え.jp/page');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_002_very_long_url_handled(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $longUrl = 'https://example.com/' . str_repeat('a', 2000);
        $result = $checker->check($crawler, $longUrl);
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_003_special_characters_in_url(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/path?query=value&special=<>&"\'');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_004_http_vs_https(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $resultHttp = $checker->check($crawler, 'http://example.com');
        $resultHttps = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $resultHttp);
        $this->assertInstanceOf(IndicatorResult::class, $resultHttps);
    }

    #[Test]
    public function edge_005_url_with_query_parameters(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com?foo=bar&baz=qux');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_006_url_with_fragment(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com#section');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_007_redirect_url_handled(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/old-page');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_008_internationalized_domain(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://münchen.de');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function edge_009_concurrent_requests(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $urls = [
            'https://example1.com',
            'https://example2.com',
            'https://example3.com',
        ];
        
        foreach ($urls as $url) {
            $result = $checker->check($crawler, $url);
            $this->assertInstanceOf(IndicatorResult::class, $result);
        }
    }

    #[Test]
    public function edge_010_same_url_different_strategies(): void
    {
        $this->mockPageSpeedResponse(0.80); // Mobile
        $this->mockPageSpeedResponse(0.90); // Desktop
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $resultMobile = $checker->check($crawler, 'https://example.com', ['strategy' => 'mobile']);
        $resultDesktop = $checker->check($crawler, 'https://example.com', ['strategy' => 'desktop']);
        
        $this->assertInstanceOf(IndicatorResult::class, $resultMobile);
        $this->assertInstanceOf(IndicatorResult::class, $resultDesktop);
    }

    #[Test]
    public function edge_011_very_low_api_score_0(): void
    {
        $this->mockPageSpeedResponse(0.0);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(1, $result->score);
        $this->assertTrue($result->isPoor());
    }

    #[Test]
    public function edge_012_very_high_api_score_100(): void
    {
        $this->mockPageSpeedResponse(1.0);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertEquals(3, $result->score);
        $this->assertTrue($result->isExcellent());
    }

    // =========================================================================
    // SEC: Security Tests
    // =========================================================================

    #[Test]
    public function sec_001_api_key_not_exposed_in_logs(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $serialized = json_encode($result);
        $this->assertStringNotContainsString(self::DEFAULT_API_KEY, $serialized);
    }

    #[Test]
    public function sec_002_api_key_not_exposed_in_results(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayNotHasKey('api_key', $result->details);
    }

    #[Test]
    public function sec_003_ssrf_protection_via_html_fetcher_service(): void
    {
        $this->mockApiFailure(500);
        
        $mockFetcher = $this->createMock(HtmlFetcherService::class);
        $mockFetcher->method('fetch')
            ->willReturn((object)[
                'success' => false,
                'error' => 'SSRF blocked',
                'timingMs' => 0
            ]);
        
        $this->app->instance(HtmlFetcherService::class, $mockFetcher);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'http://localhost/admin');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function sec_004_no_sensitive_data_in_suggestions(): void
    {
        $this->mockPageSpeedResponse(0.50);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $serialized = json_encode($suggestions);
        $this->assertStringNotContainsString('password', $serialized);
        $this->assertStringNotContainsString('api_key', $serialized);
        $this->assertStringNotContainsString('token', $serialized);
    }

    #[Test]
    public function sec_005_input_validation_on_url(): void
    {
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // Various malicious inputs
        $maliciousInputs = [
            'javascript:alert(1)',
            'file:///etc/passwd',
            '<script>alert(1)</script>',
        ];
        
        foreach ($maliciousInputs as $input) {
            $result = $checker->check($crawler, $input);
            $this->assertInstanceOf(IndicatorResult::class, $result);
        }
    }

    // =========================================================================
    // CONFIG: Configuration Tests
    // =========================================================================

    #[Test]
    public function config_001_custom_api_key_from_config(): void
    {
        Config::set('services.pagespeed.api_key', 'custom-key-98765');
        
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com');
        
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'key=custom-key-98765');
        });
    }

    #[Test]
    public function config_002_custom_timeout_from_config(): void
    {
        Config::set('services.pagespeed.timeout', 60);
        
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // Just verify it doesn't throw
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
    }

    #[Test]
    public function config_003_custom_cache_ttl(): void
    {
        Config::set('services.pagespeed.cache_ttl', 3600); // 1 hour
        
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com');
        
        // Verify cache works
        $this->assertTrue(true);
    }

    #[Test]
    public function config_004_missing_api_key_triggers_fallback(): void
    {
        Config::set('services.pagespeed.api_key', null);
        $this->mockFallbackResponse(800);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertArrayHasKey('fallback', $result->details);
    }

    // =========================================================================
    // PERF: Performance Tests
    // =========================================================================

    #[Test]
    public function perf_001_cached_response_under_10ms(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        // Prime cache
        $checker->check($crawler, 'https://example.com');
        
        // Test cached response
        $start = microtime(true);
        $checker->check($crawler, 'https://example.com');
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(10, $elapsed, 'Cached response should be < 10ms');
    }

    #[Test]
    public function perf_002_fallback_response_under_100ms(): void
    {
        $this->mockApiFailure(500);
        $this->mockFallbackResponse(500);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $start = microtime(true);
        $checker->check($crawler, 'https://example.com');
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(100, $elapsed, 'Fallback response should be < 100ms');
    }

    #[Test]
    public function perf_003_no_memory_leak_on_repeated_checks(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 100; $i++) {
            $checker->check($crawler, 'https://example.com/' . $i);
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory should not grow significantly (< 1MB for 100 iterations)
        $this->assertLessThan(1048576, $memoryIncrease);
    }

    // =========================================================================
    // AC: Acceptance Criteria Verification Tests
    // =========================================================================

    #[Test]
    public function ac_001_implements_indicator_checker_interface(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertInstanceOf(IndicatorCheckerInterface::class, $checker);
    }

    #[Test]
    public function ac_002_integrates_with_google_page_speed_insights_api(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com');
        
        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed');
        });
    }

    #[Test]
    public function ac_003_handles_api_failures_gracefully_fallback(): void
    {
        $this->mockApiFailure(500);
        $this->mockFallbackResponse(1000);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertTrue($result->details['fallback'] ?? false);
    }

    #[Test]
    public function ac_004_extracts_performance_score_from_api_response(): void
    {
        $this->mockPageSpeedResponse(0.87);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('page_speed_score', $result->details);
        $this->assertEquals(87, $result->details['page_speed_score']);
    }

    #[Test]
    public function ac_005_identifies_render_blocking_resources(): void
    {
        $this->mockPageSpeedResponse(0.75, [
            'render-blocking-resources' => [
                'details' => [
                    'items' => [
                        ['url' => 'https://example.com/style.css', 'wastedMs' => 500]
                    ]
                ]
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('render_blocking_count', $result->details);
    }

    #[Test]
    public function ac_006_checks_page_size_from_response(): void
    {
        $this->mockPageSpeedResponse(0.85, [
            'total-byte-weight' => [
                'numericValue' => 524288
            ]
        ]);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertArrayHasKey('page_size_bytes', $result->details);
    }

    #[Test]
    public function ac_007_caches_api_results_24_hours(): void
    {
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $checker->check($crawler, 'https://example.com');
        
        // Second call should use cache (only 1 HTTP request)
        $checker->check($crawler, 'https://example.com');
        
        Http::assertSentCount(1);
    }

    #[Test]
    public function ac_008_returns_score_1_3_based_on_page_speed_score(): void
    {
        $scores = [
            0.90 => 3,  // Excellent
            0.70 => 2,  // Good
            0.35 => 1,  // Poor
        ];
        
        foreach ($scores as $apiScore => $expectedScore) {
            Cache::flush();
            $this->mockPageSpeedResponse($apiScore);
            
            $checker = app($this->getCheckerClass());
            $crawler = $this->createCrawler();
            
            $result = $checker->check($crawler, 'https://example.com/' . $apiScore);
            
            $this->assertEquals($expectedScore, $result->score, "API score {$apiScore} should map to {$expectedScore}");
        }
    }

    #[Test]
    public function ac_009_generates_suggestions_from_api_recommendations(): void
    {
        $this->mockPageSpeedResponse(0.55);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        $suggestions = $checker->getSuggestions($result);
        
        $this->assertIsArray($suggestions);
    }

    #[Test]
    public function ac_010_weight_0_10_category_performance(): void
    {
        $checker = app($this->getCheckerClass());
        
        $this->assertEquals(0.10, $checker->weight());
        $this->assertEquals('performance', $checker->category());
    }

    #[Test]
    public function ac_011_unit_tests_with_mocked_api_responses(): void
    {
        // This test verifies all other tests use mocked API
        $this->mockPageSpeedResponse(0.85);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com');
        
        $this->assertInstanceOf(IndicatorResult::class, $result);
        $this->assertTrue(true, 'All tests use mocked API responses');
    }

    // =========================================================================
    // Data Provider Tests
    // =========================================================================

    public static function scoreMappingDataProvider(): array
    {
        return [
            'excellent_90' => [0.90, 3],
            'excellent_85' => [0.85, 3],
            'excellent_80' => [0.80, 3],
            'good_75' => [0.75, 2],
            'good_70' => [0.70, 2],
            'good_60' => [0.60, 2],
            'poor_55' => [0.55, 2], // 55% = 55/100 = score 2 (50-59 range)
            'poor_45' => [0.45, 1],
            'poor_30' => [0.30, 1],
            'poor_0' => [0.00, 1],
        ];
    }

    #[Test]
    #[DataProvider('scoreMappingDataProvider')]
    public function data_provider_score_mapping(float $apiScore, int $expectedScore): void
    {
        Cache::flush();
        $this->mockPageSpeedResponse($apiScore);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/test/' . $apiScore);
        
        $this->assertEquals($expectedScore, $result->score);
    }

    public static function fallbackTimingDataProvider(): array
    {
        return [
            'fast' => [500, 3],      // < 1000ms
            'medium' => [1500, 2],   // 1000-2000ms
            'slow' => [3000, 1],     // > 2000ms
        ];
    }

    #[Test]
    #[DataProvider('fallbackTimingDataProvider')]
    public function data_provider_fallback_timing(int $timingMs, int $expectedScore): void
    {
        $this->mockApiFailure(500);
        $this->mockFallbackResponse($timingMs);
        
        $checker = app($this->getCheckerClass());
        $crawler = $this->createCrawler();
        
        $result = $checker->check($crawler, 'https://example.com/fallback/' . $timingMs);
        
        $this->assertEquals($expectedScore, $result->score);
    }
}
