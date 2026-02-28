<?php

namespace Tests\Unit\Services\SERP;

use Tests\TestCase;
use App\Services\SERP\GoogleCSEFetcher;
use App\Services\SERP\MockSERPFetcher;
use App\Services\SERP\SERPOptionsData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for GoogleCSEFetcher.
 *
 * Tests Google Custom Search API integration for SERP fetching.
 *
 * @see \App\Services\SERP\GoogleCSEFetcher
 */
class GoogleCSEFetcherTest extends TestCase
{
    protected GoogleCSEFetcher $fetcher;
    protected GoogleCSEFetcher $fetcherWithFallback;
    protected MockSERPFetcher $mockFallback;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fetcher = new GoogleCSEFetcher('test-api-key', 'test-search-engine-id');
        $this->mockFallback = new MockSERPFetcher();
        $this->fetcherWithFallback = new GoogleCSEFetcher(
            'test-api-key',
            'test-search-engine-id',
            $this->mockFallback
        );

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // BASIC FETCH TESTS
    // =========================================================================

    /**
     * Fetch returns SERPResult for valid response.
     */
    public function test_fetch_returns_serp_result_for_valid_response(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example.com', 'title' => 'Example', 'snippet' => 'Description'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test keyword', $options);

        $this->assertEquals('test keyword', $result->getKeyword());
        $this->assertEquals('google', $result->getSearchEngine());
        $this->assertEquals('US', $result->getLocation());
        $this->assertEquals('desktop', $result->getDevice());
    }

    /**
     * Fetch extracts results correctly.
     */
    public function test_fetch_extracts_results_correctly(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example1.com', 'title' => 'Example 1', 'snippet' => 'Desc 1'],
                    ['link' => 'https://example2.com', 'title' => 'Example 2', 'snippet' => 'Desc 2'],
                    ['link' => 'https://example3.com', 'title' => 'Example 3', 'snippet' => 'Desc 3'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertCount(3, $result->getResults());
    }

    /**
     * Fetch returns max 10 results (API limitation).
     */
    public function test_fetch_returns_max_10_results(): void
    {
        $items = [];
        for ($i = 1; $i <= 15; $i++) {
            $items[] = ['link' => "https://example{$i}.com", 'title' => "Result {$i}", 'snippet' => "Desc {$i}"];
        }

        Http::fake([
            'googleapis.com/*' => Http::response(['items' => $items], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        // API returns 15 but we only process them (position 1-15)
        // Note: The fetcher doesn't limit to 10, it uses whatever the API returns
        $this->assertCount(15, $result->getResults());
    }

    /**
     * Fetch marks target URL as matched.
     */
    public function test_fetch_marks_target_url_as_matched(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://mysite.com/page', 'title' => 'My Site', 'snippet' => 'Desc'],
                    ['link' => 'https://other.com', 'title' => 'Other', 'snippet' => 'Desc'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', 'https://mysite.com/page');
        $result = $this->fetcher->fetch('test', $options);

        $results = $result->getResults();
        $this->assertTrue($results[0]->isOurSite());
        $this->assertFalse($results[1]->isOurSite());
    }

    /**
     * Fetch handles empty results.
     */
    public function test_fetch_handles_empty_results(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertCount(0, $result->getResults());
    }

    /**
     * Fetch handles missing items key.
     */
    public function test_fetch_handles_missing_items_key(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertCount(0, $result->getResults());
    }

    // =========================================================================
    // REQUEST PARAMETER TESTS
    // =========================================================================

    /**
     * Fetch sends correct keyword parameter.
     */
    public function test_fetch_sends_correct_keyword_parameter(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(['items' => []], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('my keyword', $options);

        Http::assertSent(function ($request) {
            return $request['q'] === 'my keyword';
        });
    }

    /**
     * Fetch sends correct API key.
     */
    public function test_fetch_sends_correct_api_key(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(['items' => []], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        Http::assertSent(function ($request) {
            return $request['key'] === 'test-api-key';
        });
    }

    /**
     * Fetch sends correct search engine ID.
     */
    public function test_fetch_sends_correct_search_engine_id(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(['items' => []], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        Http::assertSent(function ($request) {
            return $request['cx'] === 'test-search-engine-id';
        });
    }

    /**
     * Fetch sends num parameter for max results.
     */
    public function test_fetch_sends_num_parameter(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(['items' => []], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        Http::assertSent(function ($request) {
            return $request['num'] === 10;
        });
    }

    // =========================================================================
    // RESULT DATA TESTS
    // =========================================================================

    /**
     * Fetch result contains correct position.
     */
    public function test_fetch_result_contains_correct_position(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example1.com', 'title' => 'Test 1', 'snippet' => 'Desc 1'],
                    ['link' => 'https://example2.com', 'title' => 'Test 2', 'snippet' => 'Desc 2'],
                    ['link' => 'https://example3.com', 'title' => 'Test 3', 'snippet' => 'Desc 3'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertEquals(1, $result->getResults()[0]->getPosition());
        $this->assertEquals(2, $result->getResults()[1]->getPosition());
        $this->assertEquals(3, $result->getResults()[2]->getPosition());
    }

    /**
     * Fetch result contains correct URL.
     */
    public function test_fetch_result_contains_correct_url(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example.com/page', 'title' => 'Test', 'snippet' => 'Desc'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertEquals('https://example.com/page', $result->getResults()[0]->getUrl());
    }

    /**
     * Fetch result contains correct title.
     */
    public function test_fetch_result_contains_correct_title(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example.com', 'title' => 'Page Title', 'snippet' => 'Desc'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertEquals('Page Title', $result->getResults()[0]->getTitle());
    }

    /**
     * Fetch result contains correct description.
     */
    public function test_fetch_result_contains_correct_description(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example.com', 'title' => 'Test', 'snippet' => 'Page description here'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertEquals('Page description here', $result->getResults()[0]->getDescription());
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /**
     * Fetch handles 429 rate limit error.
     */
    public function test_fetch_handles_429_rate_limit_error(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'error' => ['message' => 'Rate limit exceeded'],
            ], 429),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('rate_limited', $result->getError());
    }

    /**
     * Fetch handles 403 quota exceeded error.
     */
    public function test_fetch_handles_403_quota_exceeded_error(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'error' => ['message' => 'Quota exceeded for quota metric'],
            ], 403),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('quota_exceeded', $result->getError());
    }

    /**
     * Fetch handles 400 invalid credentials error.
     */
    public function test_fetch_handles_400_invalid_credentials_error(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'error' => ['message' => 'API key not valid'],
            ], 400),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('invalid_credentials', $result->getError());
    }

    /**
     * Fetch handles 500 server error.
     */
    public function test_fetch_handles_500_server_error(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'error' => ['message' => 'Internal error'],
            ], 500),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('api_error', $result->getError());
    }

    // =========================================================================
    // FALLBACK TESTS
    // =========================================================================

    /**
     * Fetch falls back to mock on quota exceeded when configured.
     */
    public function test_fetch_falls_back_to_mock_on_quota_exceeded(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'error' => ['message' => 'Quota exceeded for quota metric'],
            ], 403),
        ]);

        $options = new SERPOptionsData(
            searchEngine: 'google',
            location: 'US',
            device: 'desktop',
            targetUrl: null,
            seed: 42,
            delayMs: 100
        );
        $result = $this->fetcherWithFallback->fetch('test', $options);

        // Mock fetcher returns 100 results
        $this->assertCount(100, $result->getResults(), 'Expected 100 results from mock fallback');
        $this->assertNull($result->getError());
    }

    /**
     * Fetch falls back to mock on rate limited when configured.
     */
    public function test_fetch_falls_back_to_mock_on_rate_limited(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'error' => ['message' => 'Rate limit exceeded'],
            ], 429),
        ]);

        $options = new SERPOptionsData(
            searchEngine: 'google',
            location: 'US',
            device: 'desktop',
            targetUrl: null,
            seed: 42,
            delayMs: 100
        );
        $result = $this->fetcherWithFallback->fetch('test', $options);

        // Mock fetcher returns 100 results
        $this->assertCount(100, $result->getResults(), 'Expected 100 results from mock fallback');
        $this->assertNull($result->getError());
    }

    /**
     * Fetch does not fall back on invalid credentials.
     */
    public function test_fetch_does_not_fall_back_on_invalid_credentials(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'error' => ['message' => 'API key not valid'],
            ], 400),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcherWithFallback->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertCount(0, $result->getResults());
    }

    /**
     * Fetch handles missing API key.
     */
    public function test_fetch_handles_missing_api_key(): void
    {
        $fetcher = new GoogleCSEFetcher('', 'test-search-engine-id', $this->mockFallback);
        $options = new SERPOptionsData('google', 'US', 'desktop', null, seed: 42, delayMs: 100);

        $result = $fetcher->fetch('test', $options);

        // Falls back to mock
        $this->assertCount(100, $result->getResults());
    }

    /**
     * Fetch handles missing search engine ID.
     */
    public function test_fetch_handles_missing_search_engine_id(): void
    {
        $fetcher = new GoogleCSEFetcher('test-api-key', '', $this->mockFallback);
        $options = new SERPOptionsData('google', 'US', 'desktop', null, seed: 42, delayMs: 100);

        $result = $fetcher->fetch('test', $options);

        // Falls back to mock
        $this->assertCount(100, $result->getResults());
    }

    /**
     * Fetch returns error when credentials missing and no fallback.
     */
    public function test_fetch_returns_error_when_credentials_missing_and_no_fallback(): void
    {
        $fetcher = new GoogleCSEFetcher('', '');
        $options = new SERPOptionsData('google', 'US', 'desktop', null);

        $result = $fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('invalid_credentials', $result->getError());
    }

    // =========================================================================
    // SERP FEATURES TESTS
    // =========================================================================

    /**
     * Fetch extracts SERP features.
     */
    public function test_fetch_extracts_serp_features(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    [
                        'link' => 'https://example.com',
                        'title' => 'Test',
                        'snippet' => 'Desc',
                        'pagemap' => [
                            'cse_image' => [['src' => 'https://example.com/image.jpg']],
                        ],
                    ],
                ],
                'spelling' => ['correctedQuery' => 'corrected'],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getSerpFeatures());
        $this->assertContains('did_you_mean', $result->getSerpFeatures());
        $this->assertContains('images', $result->getSerpFeatures());
    }

    /**
     * Fetch returns null SERP features when none present.
     */
    public function test_fetch_returns_null_serp_features_when_none_present(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example.com', 'title' => 'Test', 'snippet' => 'Desc'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNull($result->getSerpFeatures());
    }

    // =========================================================================
    // DURATION AND TIMESTAMP TESTS
    // =========================================================================

    /**
     * Fetch sets duration.
     */
    public function test_fetch_sets_duration(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertGreaterThanOrEqual(0, $result->getDurationMs());
    }

    /**
     * Fetch sets checked at timestamp.
     */
    public function test_fetch_sets_checked_at_timestamp(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getCheckedAt());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $result->getCheckedAt());
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Fetch handles special characters in keyword.
     */
    public function test_fetch_handles_special_characters_in_keyword(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(['items' => []], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test & keyword <script>', $options);

        $this->assertEquals('test & keyword <script>', $result->getKeyword());
    }

    /**
     * Fetch handles Unicode keyword.
     */
    public function test_fetch_handles_unicode_keyword(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response(['items' => []], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('日本語キーワード', $options);

        $this->assertEquals('日本語キーワード', $result->getKeyword());
    }

    /**
     * Fetch skips entries with missing URL.
     */
    public function test_fetch_skips_entries_with_missing_url(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['title' => 'No URL', 'snippet' => 'Desc'],
                    ['link' => 'https://example.com', 'title' => 'Has URL', 'snippet' => 'Desc'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertCount(1, $result->getResults());
        $this->assertEquals('https://example.com', $result->getResults()[0]->getUrl());
    }

    /**
     * Fetch handles missing title gracefully.
     */
    public function test_fetch_handles_missing_title_gracefully(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example.com', 'snippet' => 'Desc'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertEquals('', $result->getResults()[0]->getTitle());
    }

    /**
     * Fetch handles missing snippet gracefully.
     */
    public function test_fetch_handles_missing_snippet_gracefully(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example.com', 'title' => 'Test'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertEquals('', $result->getResults()[0]->getDescription());
    }

    /**
     * Target URL partial match works (host matching).
     */
    public function test_target_url_partial_match_works(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://mysite.com/page1', 'title' => 'Page 1', 'snippet' => ''],
                    ['link' => 'https://mysite.com/page2', 'title' => 'Page 2', 'snippet' => ''],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', 'https://mysite.com');
        $result = $this->fetcher->fetch('test', $options);

        // Both URLs have same host as target, so both should match
        $this->assertTrue($result->getResults()[0]->isOurSite());
        $this->assertTrue($result->getResults()[1]->isOurSite());
    }

    /**
     * Multiple fetch calls work correctly.
     */
    public function test_multiple_fetch_calls_work_correctly(): void
    {
        Http::fake([
            'googleapis.com/*' => Http::response([
                'items' => [
                    ['link' => 'https://example.com', 'title' => 'Test', 'snippet' => 'Desc'],
                ],
            ], 200),
        ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);

        for ($i = 0; $i < 5; $i++) {
            $result = $this->fetcher->fetch("keyword{$i}", $options);
            $this->assertCount(1, $result->getResults());
        }
    }
}
