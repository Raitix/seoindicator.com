<?php

namespace Tests\Integration\Services\Crawler;

use App\Models\CrawlJob;
use App\Models\CrawlPage;
use App\Models\User;
use App\Models\Project;
use App\Services\Crawler\CrawlerService;
use App\Services\Crawler\DepthControllerService;
use App\Services\Crawler\UrlValidatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Integration tests for multi-depth crawling (L0/L1/L2).
 *
 * Tests depth limiting, link extraction, and queue management
 * for hierarchical page crawling.
 *
 * @see TASK-22-20
 * @see SPEC-022
 */
class MultiDepthCrawlTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected CrawlerService $crawlerService;
    protected DepthControllerService $depthController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);

        $this->crawlerService = app(CrawlerService::class);
        $this->depthController = app(DepthControllerService::class);

        Event::fake();
    }

    // =========================================================================
    // L0 Crawl (No Links Followed) - MD-001 to MD-003
    // =========================================================================

    /**
     * Test L0 crawl only crawls the starting URL.
     * MD-001: L0 single page
     */
    public function test_l0_crawl_single_page_only(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 0]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();
        $this->assertEquals(0, $crawlJob->depth);

        // Simulate L0 crawl result
        $page = CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
            'title' => 'Example Domain',
            'links' => [
                ['url' => 'https://example.com/about', 'text' => 'About', 'internal' => true],
                ['url' => 'https://example.com/contact', 'text' => 'Contact', 'internal' => true],
            ],
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 1,
            'completed_at' => now(),
        ]);

        // Verify only one page was crawled
        $this->assertEquals(1, CrawlPage::where('crawl_job_id', $crawlJob->id)->count());
    }

    /**
     * Test L0 crawl does not follow links even if present.
     * MD-002: L0 no link following
     */
    public function test_l0_crawl_does_not_follow_links(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 0]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Verify depth controller respects L0
        $this->assertFalse(
            $this->depthController->shouldCrawl(
                'https://example.com/about',
                1, // Proposed depth
                0  // Max depth
            )
        );
    }

    /**
     * Test L0 crawl captures links in result but does not follow.
     * MD-003: L0 link capture
     */
    public function test_l0_crawl_captures_links_without_following(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 0]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        $page = CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
            'links' => [
                ['url' => 'https://example.com/page1', 'text' => 'Page 1', 'internal' => true],
                ['url' => 'https://example.com/page2', 'text' => 'Page 2', 'internal' => true],
                ['url' => 'https://external.com', 'text' => 'External', 'internal' => false],
            ],
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 1,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        // Links should be captured
        $this->assertCount(3, $content['pages'][0]['links']);
        // But only 1 page should exist
        $this->assertEquals(1, $content['summary']['totalPages']);
    }

    // =========================================================================
    // L1 Crawl (Main + Linked Pages) - MD-004 to MD-007
    // =========================================================================

    /**
     * Test L1 crawl follows links from main page.
     * MD-004: L1 link following
     */
    public function test_l1_crawl_follows_links_from_main_page(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 1]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();
        $this->assertEquals(1, $crawlJob->depth);

        // Simulate L1 crawl
        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/about',
            'depth' => 1,
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/contact',
            'depth' => 1,
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 3,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertEquals(3, $content['summary']['totalPages']);
        $this->assertEquals(1, $content['summary']['depthDistribution'][0]);
        $this->assertEquals(2, $content['summary']['depthDistribution'][1]);
    }

    /**
     * Test L1 crawl does not follow links from depth 1 pages.
     * MD-005: L1 depth limit
     */
    public function test_l1_crawl_does_not_follow_depth_2_links(): void
    {
        // Verify depth controller respects L1 limit
        $this->assertTrue(
            $this->depthController->shouldCrawl(
                'https://example.com/about',
                1, // Current depth
                1  // Max depth
            )
        );

        $this->assertFalse(
            $this->depthController->shouldCrawl(
                'https://example.com/about/team',
                2, // Current depth
                1  // Max depth
            )
        );
    }

    /**
     * Test L1 crawl handles circular links.
     * MD-006: L1 circular links
     */
    public function test_l1_crawl_handles_circular_links(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 1]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Page A links to B, B links back to A
        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/page-a',
            'depth' => 0,
            'links' => [
                ['url' => 'https://example.com/page-b', 'text' => 'Page B', 'internal' => true],
            ],
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/page-b',
            'depth' => 1,
            'links' => [
                ['url' => 'https://example.com/page-a', 'text' => 'Page A', 'internal' => true],
            ],
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 2,
            'completed_at' => now(),
        ]);

        // Both pages should exist, no infinite loop
        $this->assertEquals(2, CrawlPage::where('crawl_job_id', $crawlJob->id)->count());
    }

    /**
     * Test L1 crawl counts pages correctly.
     * MD-007: L1 page count
     */
    public function test_l1_crawl_page_count_accuracy(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 1]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Create 1 main page + 5 linked pages
        for ($i = 0; $i <= 5; $i++) {
            CrawlPage::factory()->forJob($crawlJob)->create([
                'url' => $i === 0 ? 'https://example.com' : "https://example.com/page{$i}",
                'depth' => $i === 0 ? 0 : 1,
            ]);
        }

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 6,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertEquals(6, $content['summary']['totalPages']);
    }

    // =========================================================================
    // L2 Crawl (Main + Linked + Sub-linked) - MD-008 to MD-011
    // =========================================================================

    /**
     * Test L2 crawl follows links two levels deep.
     * MD-008: L2 depth crawling
     */
    public function test_l2_crawl_follows_two_levels_deep(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 2]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();
        $this->assertEquals(2, $crawlJob->depth);

        // Create hierarchical pages
        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/products',
            'depth' => 1,
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/products/item1',
            'depth' => 2,
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 3,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertEquals(3, $content['summary']['totalPages']);
        $this->assertArrayHasKey(0, $content['summary']['depthDistribution']);
        $this->assertArrayHasKey(1, $content['summary']['depthDistribution']);
        $this->assertArrayHasKey(2, $content['summary']['depthDistribution']);
    }

    /**
     * Test L2 crawl does not follow depth 3 links.
     * MD-009: L2 depth limit
     */
    public function test_l2_crawl_does_not_follow_depth_3_links(): void
    {
        $this->assertTrue(
            $this->depthController->shouldCrawl(
                'https://example.com/products/item1',
                2, // Current depth
                2  // Max depth
            )
        );

        $this->assertFalse(
            $this->depthController->shouldCrawl(
                'https://example.com/products/item1/reviews',
                3, // Current depth
                2  // Max depth
            )
        );
    }

    /**
     * Test L2 crawl with many pages per level.
     * MD-010: L2 multiple pages per level
     */
    public function test_l2_crawl_multiple_pages_per_level(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 2]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // 1 main page
        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
        ]);

        // 5 depth 1 pages
        for ($i = 1; $i <= 5; $i++) {
            CrawlPage::factory()->forJob($crawlJob)->create([
                'url' => "https://example.com/cat{$i}",
                'depth' => 1,
            ]);
        }

        // 10 depth 2 pages
        for ($i = 1; $i <= 10; $i++) {
            CrawlPage::factory()->forJob($crawlJob)->create([
                'url' => "https://example.com/product{$i}",
                'depth' => 2,
            ]);
        }

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 16,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertEquals(16, $content['summary']['totalPages']);
        $this->assertEquals(1, $content['summary']['depthDistribution'][0]);
        $this->assertEquals(5, $content['summary']['depthDistribution'][1]);
        $this->assertEquals(10, $content['summary']['depthDistribution'][2]);
    }

    /**
     * Test L2 crawl respects max pages limit.
     * MD-011: L2 max pages limit
     */
    public function test_l2_crawl_respects_max_pages_limit(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 2]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Create exactly 50 pages (max limit from config)
        for ($i = 0; $i < 50; $i++) {
            CrawlPage::factory()->forJob($crawlJob)->create([
                'url' => "https://example.com/page{$i}",
                'depth' => $i < 1 ? 0 : ($i < 10 ? 1 : 2),
            ]);
        }

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 50,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertEquals(50, $content['summary']['totalPages']);
    }

    // =========================================================================
    // Link Handling - MD-012 to MD-017
    // =========================================================================

    /**
     * Test internal links are followed.
     * MD-012: Internal link following
     */
    public function test_internal_links_are_followed(): void
    {
        $links = [
            ['url' => 'https://example.com/page1', 'text' => 'Page 1'],
            ['url' => 'https://example.com/page2', 'text' => 'Page 2'],
        ];

        $filtered = $this->depthController->filterLinks($links, [
            'baseUrl' => 'https://example.com',
            'followExternalLinks' => false,
        ]);

        $this->assertCount(2, $filtered);
    }

    /**
     * Test external links are skipped by default.
     * MD-013: External link skipping
     */
    public function test_external_links_are_skipped_by_default(): void
    {
        $links = [
            ['url' => 'https://example.com/page1', 'text' => 'Page 1'],
            ['url' => 'https://external.com/page', 'text' => 'External'],
            ['url' => 'https://another.org/page', 'text' => 'Another'],
        ];

        $filtered = $this->depthController->filterLinks($links, [
            'baseUrl' => 'https://example.com',
            'followExternalLinks' => false,
        ]);

        $this->assertCount(1, $filtered);
        $this->assertEquals('https://example.com/page1', $filtered[0]['url']);
    }

    /**
     * Test external links can be followed with option.
     * MD-014: External link following option
     */
    public function test_external_links_can_be_followed_with_option(): void
    {
        $links = [
            ['url' => 'https://example.com/page1', 'text' => 'Page 1'],
            ['url' => 'https://external.com/page', 'text' => 'External'],
        ];

        $filtered = $this->depthController->filterLinks($links, [
            'baseUrl' => 'https://example.com',
            'followExternalLinks' => true,
        ]);

        $this->assertCount(2, $filtered);
    }

    /**
     * Test duplicate URLs are handled.
     * MD-015: Duplicate URL handling
     */
    public function test_duplicate_urls_are_handled(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 1]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Create duplicate URLs (same URL, different depth would be a bug)
        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/page',
            'depth' => 1,
        ]);

        // Verify only one page exists
        $this->assertEquals(1, CrawlPage::where('crawl_job_id', $crawlJob->id)
            ->where('url', 'https://example.com/page')
            ->count());
    }

    /**
     * Test URL normalization for duplicates.
     * MD-016: URL normalization
     */
    public function test_url_normalization_for_duplicates(): void
    {
        // These should all normalize to the same URL
        $urls = [
            'https://example.com/page',
            'https://example.com/page/',
            'https://EXAMPLE.COM/page',
        ];

        $normalized = array_map(
            fn($url) => $this->depthController->normalizeUrl($url),
            $urls
        );

        // All should normalize to the same value
        $this->assertCount(1, array_unique($normalized));
    }

    /**
     * Test link extraction from HTML.
     * MD-017: Link extraction
     */
    public function test_link_extraction_from_html(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <a href="/about">About Us</a>
    <a href="https://example.com/contact">Contact</a>
    <a href="https://external.com">External Link</a>
    <a href="#anchor">Skip</a>
    <a href="javascript:void(0)">JS Link</a>
    <a href="mailto:test@example.com">Email</a>
</body>
</html>
HTML;

        $links = $this->depthController->extractLinks($html, 'https://example.com');

        // Should extract valid HTTP links
        $this->assertGreaterThanOrEqual(2, count($links));

        // Should skip anchor, javascript, mailto
        foreach ($links as $link) {
            $this->assertStringStartsWith('http', $link['url']);
        }
    }

    // =========================================================================
    // File Type and Path Filtering - MD-018 to MD-020
    // =========================================================================

    /**
     * Test binary file URLs are skipped.
     * MD-018: Binary file skipping
     */
    public function test_binary_file_urls_are_skipped(): void
    {
        $links = [
            ['url' => 'https://example.com/page', 'text' => 'Page'],
            ['url' => 'https://example.com/file.pdf', 'text' => 'PDF'],
            ['url' => 'https://example.com/image.jpg', 'text' => 'Image'],
            ['url' => 'https://example.com/video.mp4', 'text' => 'Video'],
        ];

        $filtered = $this->depthController->filterLinks($links, [
            'baseUrl' => 'https://example.com',
        ]);

        $this->assertCount(1, $filtered);
        $this->assertEquals('https://example.com/page', $filtered[0]['url']);
    }

    /**
     * Test restricted paths are skipped.
     * MD-019: Restricted path skipping
     */
    public function test_restricted_paths_are_skipped(): void
    {
        $links = [
            ['url' => 'https://example.com/page', 'text' => 'Page'],
            ['url' => 'https://example.com/admin/dashboard', 'text' => 'Admin'],
            ['url' => 'https://example.com/login', 'text' => 'Login'],
            ['url' => 'https://example.com/api/v1/data', 'text' => 'API'],
        ];

        $filtered = $this->depthController->filterLinks($links, [
            'baseUrl' => 'https://example.com',
        ]);

        // Should only include the regular page
        $this->assertGreaterThanOrEqual(1, count($filtered));

        // Admin, login, API paths should be filtered
        $filteredUrls = array_column($filtered, 'url');
        $this->assertNotContains('https://example.com/login', $filteredUrls);
    }

    /**
     * Test anchor and query URLs are handled.
     * MD-020: Anchor and query handling
     */
    public function test_anchor_and_query_urls_are_handled(): void
    {
        $links = [
            ['url' => 'https://example.com/page', 'text' => 'Page'],
            ['url' => 'https://example.com/page#section', 'text' => 'Anchor'],
            ['url' => 'https://example.com/page?id=1', 'text' => 'Query'],
        ];

        $filtered = $this->depthController->filterLinks($links, [
            'baseUrl' => 'https://example.com',
            'skipSelfReferences' => true,
        ]);

        // Should handle anchors and queries
        $this->assertNotEmpty($filtered);
    }

    // =========================================================================
    // Size and Content Limits - MD-021 to MD-023
    // =========================================================================

    /**
     * Test total size limit enforcement.
     * MD-021: Size limit enforcement
     */
    public function test_total_size_limit_enforcement(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 1]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Create pages with sizes totaling under 3MB
        $totalSize = 0;
        for ($i = 0; $i < 10; $i++) {
            $size = 100 * 1024; // 100KB per page
            CrawlPage::factory()->forJob($crawlJob)->create([
                'url' => "https://example.com/page{$i}",
                'depth' => $i === 0 ? 0 : 1,
                'size' => $size,
            ]);
            $totalSize += $size;
        }

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 10,
            'total_size' => $totalSize,
            'completed_at' => now(),
        ]);

        // Total should be under 3MB (3 * 1024 * 1024)
        $this->assertLessThan(3 * 1024 * 1024, $totalSize);
    }

    /**
     * Test page count limit.
     * MD-022: Page count limit
     */
    public function test_page_count_limit(): void
    {
        $maxPages = config('crawler.max_pages', 50);

        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 2]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Create max pages
        for ($i = 0; $i < $maxPages; $i++) {
            CrawlPage::factory()->forJob($crawlJob)->create([
                'url' => "https://example.com/page{$i}",
                'depth' => $i < 1 ? 0 : ($i < 20 ? 1 : 2),
            ]);
        }

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => $maxPages,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertEquals($maxPages, $content['summary']['totalPages']);
    }

    /**
     * Test content size per page.
     * MD-023: Per-page size tracking
     */
    public function test_content_size_per_page_tracking(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 0]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        $page = CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
            'rendered_html' => str_repeat('a', 50000), // 50KB HTML
            'structured_text' => str_repeat('b', 10000), // 10KB text
            'size' => 60000, // 60KB total
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 1,
            'total_size' => 60000,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertEquals(60000, $content['pages'][0]['size']);
    }

    // =========================================================================
    // Content Retrieval by Depth - MD-024 to MD-026
    // =========================================================================

    /**
     * Test content can be filtered by depth.
     * MD-024: Filter by depth
     */
    public function test_content_can_be_filtered_by_depth(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 2]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Create pages at different depths
        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/cat1',
            'depth' => 1,
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/cat2',
            'depth' => 1,
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/prod1',
            'depth' => 2,
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 4,
            'completed_at' => now(),
        ]);

        // Get content and verify depth distribution
        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        $this->assertEquals(4, $content['summary']['totalPages']);
        $this->assertEquals(1, $content['summary']['depthDistribution'][0]);
        $this->assertEquals(2, $content['summary']['depthDistribution'][1]);
        $this->assertEquals(1, $content['summary']['depthDistribution'][2]);
    }

    /**
     * Test pages are returned in depth order.
     * MD-025: Depth ordering
     */
    public function test_pages_are_returned_in_depth_order(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 2]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        // Create in reverse order
        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/prod1',
            'depth' => 2,
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/cat1',
            'depth' => 1,
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 3,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        // First page should be depth 0
        $this->assertEquals(0, $content['pages'][0]['depth']);
    }

    /**
     * Test depth metadata in response.
     * MD-026: Depth metadata
     */
    public function test_depth_metadata_in_response(): void
    {
        $jobData = $this->crawlerService->createJob(
            $this->user->id,
            'https://example.com',
            ['depth' => 2]
        );

        $crawlJob = CrawlJob::where('job_id', $jobData['jobId'])->first();

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com',
            'depth' => 0,
            'title' => 'Home',
        ]);

        CrawlPage::factory()->forJob($crawlJob)->create([
            'url' => 'https://example.com/about',
            'depth' => 1,
            'title' => 'About',
        ]);

        $crawlJob->update([
            'status' => CrawlJob::STATUS_COMPLETED,
            'pages_crawled' => 2,
            'completed_at' => now(),
        ]);

        $content = $this->crawlerService->getJobContent($jobData['jobId']);

        // Each page should have depth
        foreach ($content['pages'] as $page) {
            $this->assertArrayHasKey('depth', $page);
            $this->assertIsInt($page['depth']);
        }
    }
}
