<?php

namespace Tests\Unit\Services\SERP;

use Tests\TestCase;
use App\Services\SERP\ScraperSERPFetcher;
use App\Services\SERP\SERPOptionsData;
use App\Services\Playwright\BrowserLauncher;
use App\Services\Playwright\Contracts\BrowserInterface;
use App\Services\Playwright\Contracts\BrowserContextInterface;
use App\Services\Playwright\Contracts\PageInterface;
use Mockery;
use Illuminate\Support\Facades\Log;

/**
 * Unit tests for ScraperSERPFetcher.
 *
 * Tests self-hosted SERP scraping functionality with mocked browser.
 *
 * @see \App\Services\SERP\ScraperSERPFetcher
 * @see TASK-1-9
 */
class ScraperSERPFetcherTest extends TestCase
{
    protected BrowserLauncher $browserLauncher;
    protected BrowserInterface $browser;
    protected BrowserContextInterface $context;
    protected PageInterface $page;
    protected ScraperSERPFetcher $fetcher;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset rate limiting state before each test
        ScraperSERPFetcher::resetRateLimiting();

        // Create mocks
        $this->page = Mockery::mock(PageInterface::class);
        $this->context = Mockery::mock(BrowserContextInterface::class);
        $this->browser = Mockery::mock(BrowserInterface::class);
        $this->browserLauncher = Mockery::mock(BrowserLauncher::class);

        // Default mock setup
        $this->browserLauncher->shouldReceive('getSharedInstance')
            ->andReturn($this->browser);

        $this->browser->shouldReceive('newContext')
            ->andReturn($this->context);

        $this->context->shouldReceive('newPage')
            ->andReturn($this->page);

        $this->page->shouldReceive('setDefaultNavigationTimeout')
            ->andReturnSelf();

        $this->page->shouldReceive('close')
            ->andReturnSelf();

        $this->context->shouldReceive('close')
            ->andReturnSelf();

        $this->fetcher = new ScraperSERPFetcher($this->browserLauncher);
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
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['url' => 'https://example.com', 'title' => 'Example', 'description' => 'Desc'],
            ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test keyword', $options);

        $this->assertEquals('test keyword', $result->getKeyword());
        $this->assertEquals('google', $result->getSearchEngine());
        $this->assertEquals('US', $result->getLocation());
        $this->assertEquals('desktop', $result->getDevice());
        $this->assertNull($result->getError());
    }

    /**
     * Fetch extracts 100 results.
     */
    public function test_fetch_extracts_up_to_100_results(): void
    {
        $mockResults = [];
        for ($i = 1; $i <= 100; $i++) {
            $mockResults[] = [
                'url' => "https://example{$i}.com",
                'title' => "Result {$i}",
                'description' => "Description {$i}",
            ];
        }

        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn($mockResults);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertCount(100, $result->getResults());
    }

    /**
     * Fetch marks target URL as matched.
     */
    public function test_fetch_marks_target_url_as_matched(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['url' => 'https://mysite.com/page', 'title' => 'My Site', 'description' => 'Desc'],
                ['url' => 'https://other.com', 'title' => 'Other', 'description' => 'Desc'],
            ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', 'mysite.com');
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
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertCount(0, $result->getResults());
    }

    /**
     * Fetch handles null evaluate result.
     */
    public function test_fetch_handles_null_evaluate_result(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn(null);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertCount(0, $result->getResults());
    }

    // =========================================================================
    // URL BUILDING TESTS
    // =========================================================================

    /**
     * Fetch builds correct Google URL.
     */
    public function test_fetch_builds_correct_google_url(): void
    {
        $capturedUrl = null;

        $this->page->shouldReceive('goto')
            ->once()
            ->withArgs(function ($url) use (&$capturedUrl) {
                $capturedUrl = $url;
                return true;
            })
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('test keyword', $options);

        $this->assertStringContainsString('google.com/search', $capturedUrl);
        $this->assertStringContainsString('q=test+keyword', $capturedUrl);
        $this->assertStringContainsString('num=100', $capturedUrl);
    }

    /**
     * Fetch builds correct Bing URL.
     */
    public function test_fetch_builds_correct_bing_url(): void
    {
        $capturedUrl = null;

        $this->page->shouldReceive('goto')
            ->once()
            ->withArgs(function ($url) use (&$capturedUrl) {
                $capturedUrl = $url;
                return true;
            })
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('bing', 'US', 'desktop', null);
        $this->fetcher->fetch('test keyword', $options);

        $this->assertStringContainsString('bing.com/search', $capturedUrl);
        $this->assertStringContainsString('q=test+keyword', $capturedUrl);
    }

    /**
     * Fetch adds location parameter for non-US.
     */
    public function test_fetch_adds_location_parameter_for_non_us(): void
    {
        $capturedUrl = null;

        $this->page->shouldReceive('goto')
            ->once()
            ->withArgs(function ($url) use (&$capturedUrl) {
                $capturedUrl = $url;
                return true;
            })
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'UK', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        $this->assertStringContainsString('gl=uk', $capturedUrl);
    }

    // =========================================================================
    // CAPTCHA DETECTION TESTS
    // =========================================================================

    /**
     * Fetch detects CAPTCHA via content pattern.
     */
    public function test_fetch_detects_captcha_via_content_pattern(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>We have detected unusual traffic. Please verify you are human.</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Verification Required');

        $this->page->shouldReceive('evaluate')
            ->andReturn(false);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('CAPTCHA', $result->getError());
        $this->assertCount(0, $result->getResults());
    }

    /**
     * Fetch detects CAPTCHA via selector.
     */
    public function test_fetch_detects_captcha_via_selector(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal content</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturnUsing(function ($script) {
                if (str_contains($script, 'captcha')) {
                    return true;
                }
                return [];
            });

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('CAPTCHA', $result->getError());
    }

    /**
     * Fetch detects blocked page via title.
     */
    public function test_fetch_detects_blocked_page_via_title(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Access Denied</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Access Denied');

        $this->page->shouldReceive('evaluate')
            ->andReturn(false);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('CAPTCHA', $result->getError());
    }

    // =========================================================================
    // USER AGENT TESTS
    // =========================================================================

    /**
     * Fetch uses desktop user agent for desktop device.
     */
    public function test_fetch_uses_desktop_user_agent_for_desktop_device(): void
    {
        $capturedOptions = null;

        $this->browser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->context);

        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        $this->assertStringContainsString('Windows', $capturedOptions['userAgent']);
    }

    /**
     * Fetch uses mobile user agent for mobile device.
     */
    public function test_fetch_uses_mobile_user_agent_for_mobile_device(): void
    {
        $capturedOptions = null;

        $this->browser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->context);

        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'mobile', null);
        $this->fetcher->fetch('test', $options);

        $this->assertStringContainsString('iPhone', $capturedOptions['userAgent']);
    }

    /**
     * Fetch uses correct viewport for device type.
     */
    public function test_fetch_uses_correct_viewport_for_device_type(): void
    {
        $capturedOptions = null;

        $this->browser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->context);

        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'mobile', null);
        $this->fetcher->fetch('test', $options);

        $this->assertEquals(375, $capturedOptions['viewport']['width']);
        $this->assertEquals(667, $capturedOptions['viewport']['height']);
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /**
     * Fetch handles navigation exception gracefully.
     */
    public function test_fetch_handles_navigation_exception_gracefully(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andThrow(new \Exception('Navigation timeout'));

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('Scraper error', $result->getError());
        $this->assertCount(0, $result->getResults());
    }

    /**
     * Fetch handles browser launch failure gracefully.
     */
    public function test_fetch_handles_browser_launch_failure_gracefully(): void
    {
        $this->browserLauncher = Mockery::mock(BrowserLauncher::class);
        $this->browserLauncher->shouldReceive('getSharedInstance')
            ->andThrow(new \RuntimeException('Failed to launch browser'));

        $this->fetcher = new ScraperSERPFetcher($this->browserLauncher);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertNotNull($result->getError());
        $this->assertStringContainsString('Scraper error', $result->getError());
    }

    // =========================================================================
    // RATE LIMITING TESTS
    // =========================================================================

    /**
     * Fetch applies rate limiting between requests.
     */
    public function test_fetch_applies_rate_limiting_between_requests(): void
    {
        $this->page->shouldReceive('goto')
            ->twice()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);

        $start = microtime(true);
        $this->fetcher->fetch('test1', $options);
        $this->fetcher->fetch('test2', $options);
        $elapsed = microtime(true) - $start;

        // Should have some delay between requests
        $this->assertGreaterThanOrEqual(0.5, $elapsed);
    }

    /**
     * Request count increments correctly.
     */
    public function test_request_count_increments_correctly(): void
    {
        $this->page->shouldReceive('goto')
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);

        $this->assertEquals(0, $this->fetcher->getRequestCount());
        
        $this->fetcher->fetch('test1', $options);
        $this->assertEquals(1, $this->fetcher->getRequestCount());
        
        $this->fetcher->fetch('test2', $options);
        $this->assertEquals(2, $this->fetcher->getRequestCount());
    }

    /**
     * Rate limiting resets correctly.
     */
    public function test_rate_limiting_resets_correctly(): void
    {
        $this->page->shouldReceive('goto')
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        $this->assertEquals(1, $this->fetcher->getRequestCount());

        ScraperSERPFetcher::resetRateLimiting();

        // Create a new instance to check static state
        $newFetcher = new ScraperSERPFetcher($this->browserLauncher);
        $this->assertEquals(0, $newFetcher->getRequestCount());
    }

    // =========================================================================
    // RESULT DATA TESTS
    // =========================================================================

    /**
     * Fetch result contains correct position.
     */
    public function test_fetch_result_contains_correct_position(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['url' => 'https://example1.com', 'title' => 'First', 'description' => 'Desc 1'],
                ['url' => 'https://example2.com', 'title' => 'Second', 'description' => 'Desc 2'],
                ['url' => 'https://example3.com', 'title' => 'Third', 'description' => 'Desc 3'],
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
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['url' => 'https://example.com/page', 'title' => 'Test', 'description' => 'Desc'],
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
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['url' => 'https://example.com', 'title' => 'Page Title', 'description' => 'Desc'],
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
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['url' => 'https://example.com', 'title' => 'Test', 'description' => 'Page description here'],
            ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertEquals('Page description here', $result->getResults()[0]->getDescription());
    }

    /**
     * Fetch skips entries with missing URL.
     */
    public function test_fetch_skips_entries_with_missing_url(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['title' => 'No URL', 'description' => 'Desc'],
                ['url' => 'https://example.com', 'title' => 'Has URL', 'description' => 'Desc'],
            ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertCount(1, $result->getResults());
        $this->assertEquals('https://example.com', $result->getResults()[0]->getUrl());
    }

    /**
     * Fetch skips entries with missing title.
     */
    public function test_fetch_skips_entries_with_missing_title(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['url' => 'https://no-title.com', 'description' => 'Desc'],
                ['url' => 'https://example.com', 'title' => 'Has Title', 'description' => 'Desc'],
            ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertCount(1, $result->getResults());
    }

    // =========================================================================
    // DURATION TESTS
    // =========================================================================

    /**
     * Fetch sets duration.
     */
    public function test_fetch_sets_duration(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertGreaterThanOrEqual(0, $result->getDurationMs());
    }

    /**
     * Fetch sets checked at timestamp.
     */
    public function test_fetch_sets_checked_at_timestamp(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

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
        $capturedUrl = null;

        $this->page->shouldReceive('goto')
            ->once()
            ->withArgs(function ($url) use (&$capturedUrl) {
                $capturedUrl = $url;
                return true;
            })
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test & keyword <script>', $options);

        $this->assertEquals('test & keyword <script>', $result->getKeyword());
        $this->assertStringContainsString('test+%26+keyword', $capturedUrl);
    }

    /**
     * Fetch handles Unicode keyword.
     */
    public function test_fetch_handles_unicode_keyword(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('日本語キーワード', $options);

        $this->assertEquals('日本語キーワード', $result->getKeyword());
    }

    /**
     * Target URL partial match works.
     */
    public function test_target_url_partial_match_works(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['url' => 'https://mysite.com/page1', 'title' => 'Page 1', 'description' => ''],
                ['url' => 'https://mysite.com/page2', 'title' => 'Page 2', 'description' => ''],
            ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', 'mysite.com');
        $result = $this->fetcher->fetch('test', $options);

        // Both URLs contain 'mysite.com' host so both should match
        $this->assertTrue($result->getResults()[0]->isOurSite());
        $this->assertTrue($result->getResults()[1]->isOurSite());
    }

    /**
     * Fetch handles missing description gracefully.
     */
    public function test_fetch_handles_missing_description_gracefully(): void
    {
        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([
                ['url' => 'https://example.com', 'title' => 'Test'],
            ]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $result = $this->fetcher->fetch('test', $options);

        $this->assertEquals('', $result->getResults()[0]->getDescription());
    }

    // =========================================================================
    // LOCALE TESTS
    // =========================================================================

    /**
     * Fetch uses correct locale for US.
     */
    public function test_fetch_uses_correct_locale_for_us(): void
    {
        $capturedOptions = null;

        $this->browser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->context);

        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        $this->assertEquals('en-US', $capturedOptions['locale']);
    }

    /**
     * Fetch uses correct locale for UK.
     */
    public function test_fetch_uses_correct_locale_for_uk(): void
    {
        $capturedOptions = null;

        $this->browser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->context);

        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'UK', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        $this->assertEquals('en-GB', $capturedOptions['locale']);
    }

    /**
     * Fetch uses correct locale for Germany.
     */
    public function test_fetch_uses_correct_locale_for_germany(): void
    {
        $capturedOptions = null;

        $this->browser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->context);

        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'DE', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        $this->assertEquals('de-DE', $capturedOptions['locale']);
    }

    // =========================================================================
    // PROXY CONFIGURATION TESTS
    // =========================================================================

    /**
     * Fetch uses proxy when configured.
     */
    public function test_fetch_uses_proxy_when_configured(): void
    {
        config(['serp-scraper.proxy.server' => 'http://proxy.example.com:8080']);
        config(['serp-scraper.proxy.username' => 'user']);
        config(['serp-scraper.proxy.password' => 'pass']);

        $capturedOptions = null;

        $this->browser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->context);

        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        $this->assertEquals('http://proxy.example.com:8080', $capturedOptions['proxy']['server']);
        $this->assertEquals('user', $capturedOptions['proxy']['username']);
        $this->assertEquals('pass', $capturedOptions['proxy']['password']);
    }

    /**
     * Fetch works without proxy credentials.
     */
    public function test_fetch_works_without_proxy_credentials(): void
    {
        config(['serp-scraper.proxy.server' => 'http://proxy.example.com:8080']);
        config(['serp-scraper.proxy.username' => null]);
        config(['serp-scraper.proxy.password' => null]);

        $capturedOptions = null;

        $this->browser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->context);

        $this->page->shouldReceive('goto')
            ->once()
            ->andReturnSelf();

        $this->page->shouldReceive('content')
            ->andReturn('<html><body>Normal page</body></html>');

        $this->page->shouldReceive('title')
            ->andReturn('Search Results');

        $this->page->shouldReceive('evaluate')
            ->andReturn([]);

        $options = new SERPOptionsData('google', 'US', 'desktop', null);
        $this->fetcher->fetch('test', $options);

        $this->assertEquals('http://proxy.example.com:8080', $capturedOptions['proxy']['server']);
        $this->assertArrayNotHasKey('username', $capturedOptions['proxy']);
    }
}
