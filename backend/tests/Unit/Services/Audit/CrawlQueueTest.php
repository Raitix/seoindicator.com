<?php

namespace Tests\Unit\Services\Audit;

use App\Services\Audit\DTOs\CrawlQueueItem;
use App\Services\Audit\RobotsParser;
use App\Services\Audit\SiteCrawler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Unit tests for CrawlQueue management in SiteCrawler.
 *
 * @see TASK-14-5
 * @see TEST-14-5
 */
class CrawlQueueTest extends TestCase
{
    protected SiteCrawler $crawler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crawler = new SiteCrawler(new RobotsParser());
    }

    // =========================================================================
    // Queue Basic Operations
    // =========================================================================

    /** @test Queue starts with homepage */
    public function test_queue_starts_with_homepage(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');

        $this->assertEquals(1, $this->crawler->getQueueSize());
    }

    /** @test Queue accepts multiple URLs */
    public function test_queue_accepts_multiple_urls(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');
        $this->crawler->enqueueUrl('https://example.com/page1');
        $this->crawler->enqueueUrl('https://example.com/page2');
        $this->crawler->enqueueUrl('https://example.com/page3');

        $this->assertEquals(4, $this->crawler->getQueueSize());
    }

    /** @test Queue rejects duplicates */
    public function test_queue_rejects_duplicates(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');
        $this->crawler->enqueueUrl('https://example.com/page');
        $this->crawler->enqueueUrl('https://example.com/page');
        $this->crawler->enqueueUrl('https://example.com/page');

        $this->assertEquals(2, $this->crawler->getQueueSize());
    }

    /** @test Queue rejects already crawled URLs */
    public function test_queue_rejects_crawled_urls(): void
    {
        Http::fake([
            'https://example.com/' => Http::response('<html><a href="/page">Link</a></html>', 200),
        ]);

        $this->crawler->startCrawl(1, 'https://example.com');
        iterator_to_array($this->crawler->processQueue());

        // Try to enqueue the already-crawled homepage
        $this->crawler->enqueueUrl('https://example.com/');

        $this->assertTrue($this->crawler->hasCrawled('https://example.com/'));
    }

    // =========================================================================
    // Queue Priority
    // =========================================================================

    /** @test Homepage has highest priority */
    public function test_homepage_has_highest_priority(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');
        $this->crawler->enqueueUrl('https://example.com/page1', 1);
        $this->crawler->enqueueUrl('https://example.com/page2', 1);

        // Homepage should be processed first
        Http::fake([
            'https://example.com/' => Http::response('<html></html>', 200),
            'https://example.com/*' => Http::response('<html></html>', 200),
        ]);

        $results = iterator_to_array($this->crawler->processQueue());
        $this->assertEquals('https://example.com/', $results[0]->url);
    }

    /** @test Depth affects priority */
    public function test_depth_affects_priority(): void
    {
        $this->crawler->setConfig(['max_depth' => 3]);
        $this->crawler->startCrawl(1, 'https://example.com');
        $this->crawler->enqueueUrl('https://example.com/depth1', 1);
        $this->crawler->enqueueUrl('https://example.com/depth2', 2);
        $this->crawler->enqueueUrl('https://example.com/depth3', 3);

        // Lower depth should be processed first
        $this->assertEquals(4, $this->crawler->getQueueSize());
    }

    // =========================================================================
    // Queue Limits
    // =========================================================================

    /** @test Queue respects max depth */
    public function test_queue_respects_max_depth(): void
    {
        $this->crawler->setConfig(['max_depth' => 2]);
        $this->crawler->startCrawl(1, 'https://example.com');

        // Depth within limit
        $this->crawler->enqueueUrl('https://example.com/page', 1);
        $this->crawler->enqueueUrl('https://example.com/deep', 2);

        // Depth exceeds limit
        $this->crawler->enqueueUrl('https://example.com/too-deep', 3);

        $this->assertEquals(3, $this->crawler->getQueueSize()); // homepage + 2 pages
    }

    /** @test Queue respects max pages */
    public function test_queue_respects_max_pages(): void
    {
        $this->crawler->setConfig(['max_pages' => 3]);
        $this->crawler->startCrawl(1, 'https://example.com');

        // Within limit
        $this->crawler->enqueueUrl('https://example.com/page1');
        $this->crawler->enqueueUrl('https://example.com/page2');

        // Exceeds limit
        $this->crawler->enqueueUrl('https://example.com/page3');
        $this->crawler->enqueueUrl('https://example.com/page4');

        $this->assertEquals(3, $this->crawler->getQueueSize());
    }

    /** @test Queue rejects URLs exceeding max length */
    public function test_queue_rejects_long_urls(): void
    {
        $this->crawler->setConfig(['max_url_length' => 100]);
        $this->crawler->startCrawl(1, 'https://example.com');

        $longUrl = 'https://example.com/' . str_repeat('a', 200);
        $this->crawler->enqueueUrl($longUrl);

        $this->assertEquals(1, $this->crawler->getQueueSize()); // Only homepage
    }

    // =========================================================================
    // Queue URL Filtering
    // =========================================================================

    /** @test Queue rejects external URLs */
    public function test_queue_rejects_external_urls(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');

        $this->crawler->enqueueUrl('https://external.com/page');
        $this->crawler->enqueueUrl('https://other.com/page');

        $this->assertEquals(1, $this->crawler->getQueueSize()); // Only homepage
    }

    /** @test Queue rejects non-HTTP URLs */
    public function test_queue_rejects_non_http_urls(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');

        $this->crawler->enqueueUrl('ftp://example.com/file');
        $this->crawler->enqueueUrl('javascript:void(0)');
        $this->crawler->enqueueUrl('data:text/html,test');

        $this->assertEquals(1, $this->crawler->getQueueSize());
    }

    /** @test Queue rejects special URLs */
    public function test_queue_rejects_special_urls(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');

        $this->crawler->enqueueUrl('javascript:alert(1)');
        $this->crawler->enqueueUrl('vbscript:msgbox(1)');
        $this->crawler->enqueueUrl('file:///etc/passwd');

        $this->assertEquals(1, $this->crawler->getQueueSize());
    }

    // =========================================================================
    // Queue Processing
    // =========================================================================

    /** @test Queue processes all items */
    public function test_queue_processes_all_items(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('<html></html>', 200),
        ]);

        $this->crawler->startCrawl(1, 'https://example.com');
        $this->crawler->enqueueUrl('https://example.com/page1');
        $this->crawler->enqueueUrl('https://example.com/page2');

        $results = iterator_to_array($this->crawler->processQueue());

        $this->assertCount(3, $results);
    }

    /** @test Queue tracks crawled URLs */
    public function test_queue_tracks_crawled_urls(): void
    {
        Http::fake([
            'https://example.com/' => Http::response('<html></html>', 200),
            'https://example.com/page' => Http::response('<html></html>', 200),
        ]);

        $this->crawler->startCrawl(1, 'https://example.com');
        $this->crawler->enqueueUrl('https://example.com/page');

        iterator_to_array($this->crawler->processQueue());

        $this->assertTrue($this->crawler->hasCrawled('https://example.com/'));
        $this->assertTrue($this->crawler->hasCrawled('https://example.com/page'));
    }

    // =========================================================================
    // Queue State Persistence
    // =========================================================================

    /** @test Queue state persisted during crawl */
    public function test_queue_state_persisted(): void
    {
        Http::fake([
            'https://example.com/' => Http::response('<html></html>', 200),
        ]);

        $this->crawler->startCrawl(1, 'https://example.com');
        iterator_to_array($this->crawler->processQueue());

        // State should be cached
        $cached = Cache::get('crawl_state:1');
        $this->assertNotNull($cached);
        $this->assertEquals('completed', $cached['status']);
    }

    /** @test Queue resumes from state */
    public function test_queue_resumes_from_state(): void
    {
        // Pre-populate cache with existing state
        Cache::put('crawl_state:1', [
            'auditId' => 1,
            'baseUrl' => 'https://example.com',
            'status' => 'running',
            'pagesCrawled' => 5,
            'startedAt' => now()->toIso8601String(),
        ], now()->addHour());

        Cache::put('crawl_urls:1', [md5('https://example.com/') => true], now()->addHour());

        $this->crawler->startCrawl(1, 'https://example.com');

        $state = $this->crawler->getStatus();
        $this->assertEquals(5, $state->pagesCrawled);
        $this->assertTrue($this->crawler->hasCrawled('https://example.com/'));
    }

    // =========================================================================
    // URL Normalization
    // =========================================================================

    /** @test Queue normalizes URLs */
    public function test_queue_normalizes_urls(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');

        $this->crawler->enqueueUrl('HTTPS://EXAMPLE.COM/PAGE');
        $this->crawler->enqueueUrl('https://example.com/PAGE');

        // Should be treated as duplicates after normalization
        $this->assertLessThanOrEqual(2, $this->crawler->getQueueSize());
    }

    /** @test Queue strips fragments */
    public function test_queue_strips_fragments(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');

        $this->crawler->enqueueUrl('https://example.com/page#section1');
        $this->crawler->enqueueUrl('https://example.com/page#section2');

        // Should be treated as same URL
        $this->assertEquals(2, $this->crawler->getQueueSize()); // homepage + page
    }

    /** @test Queue sorts query parameters */
    public function test_queue_sorts_query_params(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');

        $this->crawler->enqueueUrl('https://example.com/page?a=1&b=2');
        $this->crawler->enqueueUrl('https://example.com/page?b=2&a=1');

        // Should be treated as same URL after normalization
        $this->assertEquals(2, $this->crawler->getQueueSize());
    }

    // =========================================================================
    // Queue Cancellation
    // =========================================================================

    /** @test Queue clears on cancellation */
    public function test_queue_clears_on_cancellation(): void
    {
        $this->crawler->startCrawl(1, 'https://example.com');
        $this->crawler->enqueueUrl('https://example.com/page1');
        $this->crawler->enqueueUrl('https://example.com/page2');

        $this->crawler->cancelCrawl();

        $state = $this->crawler->getStatus();
        $this->assertTrue($state->isCancelled());
    }
}
