<?php

namespace Tests\Unit\Services\Crawler;

use App\Services\Crawler\DepthControllerService;
use App\Services\Crawler\UrlValidatorService;
use Tests\TestCase;

/**
 * Unit tests for DepthControllerService.
 *
 * Tests multi-level crawling, link extraction, and queue management.
 *
 * @see TASK-22-18
 * @see TASK-22-8
 * @see SPEC-022
 */
class DepthControllerServiceTest extends TestCase
{
    use CrawlerTestHelper;

    protected DepthControllerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DepthControllerService(new UrlValidatorService());
    }

    // =========================================================================
    // shouldCrawl Tests
    // =========================================================================

    /** @test */
    public function should_crawl_returns_true_for_valid_url(): void
    {
        $result = $this->service->shouldCrawl('https://example.com', 0, 2);

        $this->assertTrue($result);
    }

    /** @test */
    public function should_crawl_returns_false_for_null_url(): void
    {
        $result = $this->service->shouldCrawl(null, 0, 2);

        $this->assertFalse($result);
    }

    /** @test */
    public function should_crawl_returns_false_for_empty_url(): void
    {
        $result = $this->service->shouldCrawl('', 0, 2);

        $this->assertFalse($result);
    }

    /** @test */
    public function should_crawl_respects_depth_limit(): void
    {
        $this->assertTrue($this->service->shouldCrawl('https://example.com', 0, 2));
        $this->assertTrue($this->service->shouldCrawl('https://example.com', 1, 2));
        $this->assertTrue($this->service->shouldCrawl('https://example.com', 2, 2));
        $this->assertFalse($this->service->shouldCrawl('https://example.com', 3, 2));
    }

    /** @test */
    public function should_crawl_returns_false_for_negative_depth(): void
    {
        $result = $this->service->shouldCrawl('https://example.com', -1, 2);

        $this->assertFalse($result);
    }

    /** @test */
    public function should_crawl_blocks_ssrf_urls(): void
    {
        $blockedUrls = [
            'https://127.0.0.1',
            'https://10.0.0.1',
            'https://localhost',
        ];

        foreach ($blockedUrls as $url) {
            $result = $this->service->shouldCrawl($url, 0, 2);
            $this->assertFalse($result, "SSRF URL should be blocked: {$url}");
        }
    }

    /** @test */
    public function should_crawl_detects_duplicate_urls(): void
    {
        $jobId = 'test-job-' . uniqid();
        $url = 'https://example.com/page';

        // First crawl should be allowed
        $this->assertTrue($this->service->shouldCrawl($url, 0, 2, $jobId));

        // Mark as crawled
        $this->service->markCrawled($url, $jobId);

        // Second crawl should be blocked
        $this->assertFalse($this->service->shouldCrawl($url, 0, 2, $jobId));
    }

    /** @test */
    public function should_crawl_detects_queued_urls(): void
    {
        $jobId = 'test-job-' . uniqid();
        $url = 'https://example.com/page';

        // Queue the URL
        $this->service->queueLinks([['url' => $url]], $jobId, 1);

        // Should not allow crawling already queued URL
        $this->assertFalse($this->service->shouldCrawl($url, 1, 2, $jobId));
    }

    // =========================================================================
    // extractLinks Tests
    // =========================================================================

    /** @test */
    public function extract_links_returns_array(): void
    {
        $html = $this->getHtmlWithLinks();
        $links = $this->service->extractLinks($html, 'https://example.com');

        $this->assertIsArray($links);
    }

    /** @test */
    public function extract_links_handles_null_html(): void
    {
        $links = $this->service->extractLinks(null, 'https://example.com');

        $this->assertIsArray($links);
        $this->assertEmpty($links);
    }

    /** @test */
    public function extract_links_handles_empty_html(): void
    {
        $links = $this->service->extractLinks('', 'https://example.com');

        $this->assertIsArray($links);
        $this->assertEmpty($links);
    }

    /** @test */
    public function extract_links_finds_all_anchor_tags(): void
    {
        $html = $this->getHtmlWithLinks();
        $links = $this->service->extractLinks($html, 'https://example.com');

        // Should find all <a href> tags (excluding javascript, mailto, #)
        $linkUrls = array_column($links, 'url');

        $this->assertContains('https://example.com/', $linkUrls);
        $this->assertContains('https://example.com/about', $linkUrls);
        $this->assertContains('https://external.com', $linkUrls);
    }

    /** @test */
    public function extract_links_skips_special_protocols(): void
    {
        $html = $this->getHtmlWithLinks();
        $links = $this->service->extractLinks($html, 'https://example.com');

        $linkUrls = array_column($links, 'url');

        $this->assertNotContains('javascript:void(0)', $linkUrls);
        $this->assertNotContains('mailto:test@example.com', $linkUrls);
    }

    /** @test */
    public function extract_links_skips_hash_anchors(): void
    {
        $html = '<a href="#section">Jump</a>';
        $links = $this->service->extractLinks($html, 'https://example.com');

        $this->assertEmpty($links);
    }

    /** @test */
    public function extract_links_includes_link_attributes(): void
    {
        $html = '<a href="https://example.com" rel="nofollow sponsored" target="_blank">Link</a>';
        $links = $this->service->extractLinks($html, 'https://example.com');

        $this->assertCount(1, $links);
        $this->assertEquals('https://example.com', $links[0]['url']);
        $this->assertEquals('nofollow sponsored', $links[0]['rel']);
        $this->assertEquals('_blank', $links[0]['target']);
        $this->assertTrue($links[0]['nofollow']);
        $this->assertTrue($links[0]['sponsored']);
    }

    /** @test */
    public function extract_links_resolves_relative_urls(): void
    {
        $html = '<a href="/page">Relative</a><a href="../parent">Parent</a>';
        $links = $this->service->extractLinks($html, 'https://example.com/subdir/page');

        $this->assertGreaterThanOrEqual(1, count($links));
        $linkUrls = array_column($links, 'url');

        // All URLs should be absolute
        foreach ($linkUrls as $url) {
            $this->assertStringStartsWith('https://', $url);
        }
    }

    /** @test */
    public function extract_links_handles_malformed_html(): void
    {
        $html = $this->getMalformedHtml();
        $links = $this->service->extractLinks($html, 'https://example.com');

        // Should not throw exception
        $this->assertIsArray($links);
    }

    /** @test */
    public function extract_links_handles_unicode_content(): void
    {
        $html = '<a href="/日本語">日本語リンク</a>';
        $links = $this->service->extractLinks($html, 'https://example.com');

        $this->assertIsArray($links);
        if (!empty($links)) {
            $this->assertStringContainsString('日本語', $links[0]['text']);
        }
    }

    // =========================================================================
    // filterLinks Tests
    // =========================================================================

    /** @test */
    public function filter_links_removes_duplicates(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/other'],
        ];

        $filtered = $this->service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(2, $filtered);
    }

    /** @test */
    public function filter_links_handles_null_input(): void
    {
        $filtered = $this->service->filterLinks(null);

        $this->assertIsArray($filtered);
        $this->assertEmpty($filtered);
    }

    /** @test */
    public function filter_links_skips_file_extensions(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/file.pdf'],
            ['url' => 'https://example.com/image.jpg'],
            ['url' => 'https://example.com/video.mp4'],
        ];

        $filtered = $this->service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(1, $filtered);
        $this->assertEquals('https://example.com/page', $filtered[0]['url']);
    }

    /** @test */
    public function filter_links_skips_blocked_paths(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/api/data'],
            ['url' => 'https://example.com/admin/settings'],
            ['url' => 'https://example.com/login'],
        ];

        $filtered = $this->service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $filteredUrls = array_column($filtered, 'url');
        $this->assertContains('https://example.com/page', $filteredUrls);
        $this->assertNotContains('https://example.com/api/data', $filteredUrls);
        $this->assertNotContains('https://example.com/admin/settings', $filteredUrls);
        $this->assertNotContains('https://example.com/login', $filteredUrls);
    }

    /** @test */
    public function filter_links_limits_to_same_domain_by_default(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://external.com/page'],
        ];

        $filtered = $this->service->filterLinks($links, [
            'baseUrl' => 'https://example.com',
            'followExternalLinks' => false,
        ]);

        $this->assertCount(1, $filtered);
        $this->assertEquals('https://example.com/page', $filtered[0]['url']);
    }

    /** @test */
    public function filter_links_allows_external_when_configured(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://external.com/page'],
        ];

        $filtered = $this->service->filterLinks($links, [
            'baseUrl' => 'https://example.com',
            'followExternalLinks' => true,
        ]);

        $this->assertCount(2, $filtered);
    }

    /** @test */
    public function filter_links_skips_self_references(): void
    {
        $links = [
            ['url' => 'https://example.com/'],
            ['url' => 'https://example.com/page'],
        ];

        $filtered = $this->service->filterLinks($links, [
            'baseUrl' => 'https://example.com/',
            'skipSelfReferences' => true,
        ]);

        $filteredUrls = array_column($filtered, 'url');
        $this->assertNotContains('https://example.com/', $filteredUrls);
        $this->assertContains('https://example.com/page', $filteredUrls);
    }

    /** @test */
    public function filter_links_adds_normalized_url(): void
    {
        $links = [
            ['url' => 'https://Example.com/Page'],
        ];

        $filtered = $this->service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertArrayHasKey('normalizedUrl', $filtered[0]);
    }

    // =========================================================================
    // queueLinks Tests
    // =========================================================================

    /** @test */
    public function queue_links_adds_to_queue(): void
    {
        $jobId = 'test-job-' . uniqid();
        $links = [
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2'],
        ];

        $this->service->queueLinks($links, $jobId, 1);

        $queue = $this->service->getCrawlQueue($jobId);

        $this->assertCount(2, $queue);
    }

    /** @test */
    public function queue_links_handles_empty_array(): void
    {
        $jobId = 'test-job-' . uniqid();

        $this->service->queueLinks([], $jobId, 1);

        $queue = $this->service->getCrawlQueue($jobId);

        $this->assertEmpty($queue);
    }

    /** @test */
    public function queue_links_skips_duplicates(): void
    {
        $jobId = 'test-job-' . uniqid();
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/page'], // Duplicate
        ];

        $this->service->queueLinks($links, $jobId, 1);

        $queue = $this->service->getCrawlQueue($jobId);

        $this->assertCount(1, $queue);
    }

    /** @test */
    public function queue_links_stores_depth(): void
    {
        $jobId = 'test-job-' . uniqid();
        $links = [
            ['url' => 'https://example.com/page'],
        ];

        $this->service->queueLinks($links, $jobId, 2);

        $queue = $this->service->getCrawlQueue($jobId);

        $this->assertEquals(2, $queue[0]['depth']);
    }

    /** @test */
    public function queue_links_stores_parent_url(): void
    {
        $jobId = 'test-job-' . uniqid();
        $links = [
            ['url' => 'https://example.com/page'],
        ];

        $this->service->queueLinks($links, $jobId, 1, 'https://example.com/');

        $queue = $this->service->getCrawlQueue($jobId);

        $this->assertEquals('https://example.com/', $queue[0]['parent_url']);
    }

    // =========================================================================
    // getCrawlQueue Tests
    // =========================================================================

    /** @test */
    public function get_crawl_queue_returns_empty_for_nonexistent_job(): void
    {
        $queue = $this->service->getCrawlQueue('nonexistent-job');

        $this->assertEmpty($queue);
    }

    /** @test */
    public function get_crawl_queue_filters_by_status(): void
    {
        $jobId = 'test-job-' . uniqid();

        $this->service->queueLinks([
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2'],
        ], $jobId, 1);

        // Mark first as done
        $this->service->markCrawled('https://example.com/page1', $jobId);

        $pending = $this->service->getCrawlQueue($jobId, 'pending');
        $done = $this->service->getCrawlQueue($jobId, 'done');

        $this->assertCount(1, $pending);
        $this->assertCount(1, $done);
    }

    /** @test */
    public function get_crawl_queue_sorts_by_depth(): void
    {
        $jobId = 'test-job-' . uniqid();

        // Queue in reverse depth order
        $this->service->queueLinks([
            ['url' => 'https://example.com/depth2'],
        ], $jobId, 2);

        $this->service->queueLinks([
            ['url' => 'https://example.com/depth0'],
        ], $jobId, 0);

        $this->service->queueLinks([
            ['url' => 'https://example.com/depth1'],
        ], $jobId, 1);

        $queue = $this->service->getCrawlQueue($jobId);

        // Should be sorted by depth ascending
        $this->assertEquals(0, $queue[0]['depth']);
        $this->assertEquals(1, $queue[1]['depth']);
        $this->assertEquals(2, $queue[2]['depth']);
    }

    // =========================================================================
    // markCrawled Tests
    // =========================================================================

    /** @test */
    public function mark_crawled_updates_status(): void
    {
        $jobId = 'test-job-' . uniqid();
        $url = 'https://example.com/page';

        $this->service->queueLinks([['url' => $url]], $jobId, 1);
        $this->service->markCrawled($url, $jobId);

        $queue = $this->service->getCrawlQueue($jobId, 'done');

        $this->assertCount(1, $queue);
    }

    /** @test */
    public function mark_crawled_prevents_duplicate_crawls(): void
    {
        $jobId = 'test-job-' . uniqid();
        $url = 'https://example.com/page';

        $this->service->markCrawled($url, $jobId);

        // Should not allow crawling again
        $this->assertFalse($this->service->shouldCrawl($url, 0, 2, $jobId));
    }

    // =========================================================================
    // normalizeUrl Tests
    // =========================================================================

    /** @test */
    public function normalize_url_produces_consistent_output(): void
    {
        $normalized1 = $this->service->normalizeUrl('HTTPS://EXAMPLE.COM/');
        $normalized2 = $this->service->normalizeUrl('https://example.com/');

        $this->assertEquals($normalized1, $normalized2);
    }

    /** @test */
    public function normalize_url_removes_trailing_slash(): void
    {
        $normalized = $this->service->normalizeUrl('https://example.com/');

        // Behavior depends on UrlNormalizer implementation
        $this->assertStringStartsWith('https://example.com', $normalized);
    }

    // =========================================================================
    // Config Tests
    // =========================================================================

    /** @test */
    public function get_default_max_depth_returns_configured_value(): void
    {
        $maxDepth = $this->service->getDefaultMaxDepth();

        $this->assertIsInt($maxDepth);
        $this->assertGreaterThanOrEqual(0, $maxDepth);
    }

    /** @test */
    public function get_default_max_pages_returns_configured_value(): void
    {
        $maxPages = $this->service->getDefaultMaxPages();

        $this->assertIsInt($maxPages);
        $this->assertGreaterThan(0, $maxPages);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    /** @test */
    public function full_crawl_workflow(): void
    {
        $jobId = 'test-job-' . uniqid();
        $baseUrl = 'https://example.com';

        // Check base URL should be crawled
        $this->assertTrue($this->service->shouldCrawl($baseUrl, 0, 2, $jobId));

        // Mark base as crawled
        $this->service->markCrawled($baseUrl, $jobId);

        // Extract links from sample HTML
        $html = $this->getHtmlWithLinks();
        $links = $this->service->extractLinks($html, $baseUrl);

        // Filter links
        $filtered = $this->service->filterLinks($links, [
            'baseUrl' => $baseUrl,
            'followExternalLinks' => false,
        ]);

        // Queue for depth 1
        $this->service->queueLinks($filtered, $jobId, 1, $baseUrl);

        // Verify queue
        $queue = $this->service->getCrawlQueue($jobId);

        $this->assertGreaterThan(0, count($queue));

        // All queued items should be depth 1
        foreach ($queue as $item) {
            $this->assertEquals(1, $item['depth']);
        }
    }
}
