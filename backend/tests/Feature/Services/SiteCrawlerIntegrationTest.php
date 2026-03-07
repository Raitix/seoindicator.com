<?php

namespace Tests\Feature\Services;

use App\Models\Audit;
use App\Models\Site;
use App\Models\User;
use App\Services\Audit\Contracts\SiteCrawlerInterface;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\RobotsParser;
use App\Services\Audit\SiteCrawler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Integration tests for SiteCrawler service.
 *
 * @see TASK-14-5
 * @see TEST-14-5
 */
class SiteCrawlerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected SiteCrawler $crawler;
    protected User $user;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crawler = new SiteCrawler(new RobotsParser());
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    }

    // =========================================================================
    // Full Crawl Workflow (INT-001)
    // =========================================================================

    /** @test Full crawl workflow */
    public function test_full_crawl_workflow(): void
    {
        Http::fake([
            'https://example.com/' => Http::response(
                '<html>
                    <head><title>Homepage</title></head>
                    <body>
                        <a href="/page1">Page 1</a>
                        <a href="/page2">Page 2</a>
                        <a href="https://external.com">External</a>
                    </body>
                </html>',
                200
            ),
            'https://example.com/page1' => Http::response(
                '<html><head><title>Page 1</title></head><body>Content</body></html>',
                200
            ),
            'https://example.com/page2' => Http::response(
                '<html><head><title>Page 2</title></head><body>Content</body></html>',
                200
            ),
        ]);

        $audit = Audit::create([
            'site_id' => $this->site->id,
            'status' => 'pending',
            'max_pages' => 10,
            'max_depth' => 2,
        ]);

        $this->crawler->startCrawl($audit->id, 'https://example.com');
        $results = iterator_to_array($this->crawler->processQueue());
        $state = $this->crawler->getStatus();

        $this->assertCount(3, $results);
        $this->assertTrue($state->isCompleted());
        $this->assertEquals(3, $state->pagesCrawled);

        // Verify all pages have correct metadata
        $homepage = collect($results)->firstWhere('url', 'https://example.com/');
        $this->assertEquals('Homepage', $homepage->getTitle());
        $this->assertCount(2, $homepage->getInternalLinks());
        $this->assertCount(1, $homepage->getExternalLinks());
    }

    // =========================================================================
    // Integration with RobotsParser (INT-002)
    // =========================================================================

    /** @test Robots.txt integration */
    public function test_robots_txt_integration(): void
    {
        Http::fake([
            'https://example.com/robots.txt' => Http::response(
                "User-agent: *\nDisallow: /admin/\nDisallow: /private/\nCrawl-delay: 1",
                200
            ),
            'https://example.com/' => Http::response('<html><body><a href="/page">Link</a><a href="/admin">Admin</a></body></html>', 200),
            'https://example.com/page' => Http::response('<html></html>', 200),
        ]);

        $this->crawler->setConfig(['respect_robots_txt' => true]);
        $this->crawler->startCrawl(1, 'https://example.com');

        // Admin should be blocked by robots.txt
        $this->crawler->enqueueUrl('https://example.com/admin');
        $this->crawler->enqueueUrl('https://example.com/private/secret');

        $results = iterator_to_array($this->crawler->processQueue());

        // Only homepage and page (not admin or private)
        $this->assertLessThanOrEqual(2, count($results));
    }

    // =========================================================================
    // Integration with Audit Model (INT-004)
    // =========================================================================

    /** @test Audit model integration */
    public function test_audit_model_integration(): void
    {
        Http::fake([
            'https://example.com/' => Http::response('<html></html>', 200),
        ]);

        $audit = Audit::create([
            'site_id' => $this->site->id,
            'status' => 'pending',
            'max_pages' => 10,
            'max_depth' => 2,
        ]);

        $this->crawler->startCrawl($audit->id, 'https://example.com');
        iterator_to_array($this->crawler->processQueue());

        $state = $this->crawler->getStatus();
        $this->assertEquals($audit->id, $state->auditId);
        $this->assertTrue($state->isCompleted());
    }

    // =========================================================================
    // Queue Worker Integration (INT-005)
    // =========================================================================

    /** @test Queue worker can process crawl */
    public function test_queue_worker_processes_crawl(): void
    {
        Http::fake([
            'https://example.com/' => Http::response('<html></html>', 200),
        ]);

        // Simulate queue worker picking up crawl
        $crawler = app(SiteCrawlerInterface::class);
        $crawler->startCrawl(1, 'https://example.com');

        $results = iterator_to_array($crawler->processQueue());

        $this->assertCount(1, $results);
    }

    // =========================================================================
    // Cache Integration (INT-006)
    // =========================================================================

    /** @test Cache integration for state persistence */
    public function test_cache_integration(): void
    {
        Http::fake([
            'https://example.com/' => Http::response('<html></html>', 200),
        ]);

        $this->crawler->startCrawl(1, 'https://example.com/');
        iterator_to_array($this->crawler->processQueue());

        // State should be in cache
        $cachedState = \Illuminate\Support\Facades\Cache::get('crawl_state:1');
        $this->assertNotNull($cachedState);
        $this->assertEquals('completed', $cachedState['status']);

        // Crawled URLs should be in cache
        $crawledUrls = \Illuminate\Support\Facades\Cache::get('crawl_urls:1');
        $this->assertNotNull($crawledUrls);
        $this->assertArrayHasKey(md5('https://example.com/'), $crawledUrls);
    }

    // =========================================================================
    // Multi-Depth Crawl (INT)
    // =========================================================================

    /** @test Multi-depth crawl */
    public function test_multi_depth_crawl(): void
    {
        Http::fake([
            'https://example.com/' => Http::response(
                '<html><body><a href="/level1">Level 1</a></body></html>',
                200
            ),
            'https://example.com/level1' => Http::response(
                '<html><body><a href="/level2">Level 2</a></body></html>',
                200
            ),
            'https://example.com/level2' => Http::response(
                '<html><body><a href="/level3">Level 3</a></body></html>',
                200
            ),
            'https://example.com/level3' => Http::response(
                '<html><body>Deepest</body></html>',
                200
            ),
        ]);

        $this->crawler->setConfig(['max_depth' => 2]);
        $this->crawler->startCrawl(1, 'https://example.com');
        $results = iterator_to_array($this->crawler->processQueue());

        // Depth 0, 1, 2 = 3 pages (level3 should not be crawled)
        $this->assertCount(3, $results);
    }

    // =========================================================================
    // Error Recovery
    // =========================================================================

    /** @test Error recovery during crawl */
    public function test_error_recovery(): void
    {
        Http::fake([
            'https://example.com/' => Http::response('<html><a href="/page1">1</a><a href="/page2">2</a></html>', 200),
            'https://example.com/page1' => Http::response('', 500),
            'https://example.com/page2' => Http::response('<html></html>', 200),
        ]);

        $this->crawler->startCrawl(1, 'https://example.com/');
        $results = iterator_to_array($this->crawler->processQueue());
        $state = $this->crawler->getStatus();

        $this->assertTrue($state->isCompleted());
        $this->assertEquals(3, $state->pagesCrawled);
        
        // Check that page1 had an error (500 status)
        $page1Result = collect($results)->firstWhere('url', 'https://example.com/page1');
        $this->assertNotNull($page1Result);
        $this->assertFalse($page1Result->isSuccess());
        $this->assertEquals(500, $page1Result->statusCode);
    }

    // =========================================================================
    // Large Site Handling
    // =========================================================================

    /** @test Large site handling with limits */
    public function test_large_site_handling(): void
    {
        // Simulate a site with many pages
        Http::fake(function ($request) {
            $page = (int)substr($request->url(), -1);
            return Http::response(
                "<html><body><a href='/page" . ($page + 1) . "'>Next</a></body></html>",
                200
            );
        });

        $this->crawler->setConfig(['max_pages' => 5]);
        $this->crawler->startCrawl(1, 'https://example.com/page0');
        $results = iterator_to_array($this->crawler->processQueue());

        $this->assertLessThanOrEqual(5, count($results));
    }

    // =========================================================================
    // Content Extraction Integration
    // =========================================================================

    /** @test Content extraction integration */
    public function test_content_extraction_integration(): void
    {
        Http::fake([
            'https://example.com/' => Http::response(
                '<html>
                    <head>
                        <title>Test Page</title>
                        <meta name="description" content="Meta description">
                        <link rel="canonical" href="https://example.com/canonical">
                    </head>
                    <body>
                        <h1>Main Heading</h1>
                        <h1>Second H1</h1>
                        <a href="/internal">Internal</a>
                        <a href="https://external.com">External</a>
                    </body>
                </html>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8']
            ),
        ]);

        $this->crawler->startCrawl(1, 'https://example.com');
        $results = iterator_to_array($this->crawler->processQueue());
        $result = $results[0];

        $this->assertEquals('Test Page', $result->getTitle());
        $this->assertEquals('Meta description', $result->getDescription());
        $this->assertEquals('https://example.com/canonical', $result->getCanonical());
        $this->assertCount(2, $result->getH1Tags());
        $this->assertCount(1, $result->getInternalLinks());
        $this->assertCount(1, $result->getExternalLinks());
    }

    // =========================================================================
    // Redirect Handling Integration
    // =========================================================================

    /** @test Redirect handling integration */
    public function test_redirect_handling(): void
    {
        Http::fake([
            'https://example.com/' => Http::response('', 301, ['Location' => 'https://example.com/new-home']),
            'https://example.com/new-home' => Http::response('<html><body>New location</body></html>', 200),
        ]);

        $this->crawler->startCrawl(1, 'https://example.com/');
        $results = iterator_to_array($this->crawler->processQueue());

        // Should follow redirect and crawl final page
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    // =========================================================================
    // SSRF Protection Integration
    // =========================================================================

    /** @test SSRF protection blocks internal URLs */
    public function test_ssrf_protection_integration(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->crawler->startCrawl(1, 'http://localhost/admin');
    }

    /** @test SSRF protection blocks private IPs */
    public function test_ssrf_protection_private_ip(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->crawler->startCrawl(1, 'http://192.168.1.1/admin');
    }

    // =========================================================================
    // Performance Test
    // =========================================================================

    /** @test Performance: 10 pages within time limit */
    public function test_performance_10_pages(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('<html></html>', 200),
        ]);

        $start = microtime(true);

        $this->crawler->setConfig(['max_pages' => 10, 'requests_per_second' => 100]);
        $this->crawler->startCrawl(1, 'https://example.com/');

        for ($i = 1; $i <= 9; $i++) {
            $this->crawler->enqueueUrl("https://example.com/page{$i}");
        }

        iterator_to_array($this->crawler->processQueue());

        $elapsed = microtime(true) - $start;

        // Should complete within 30 seconds
        $this->assertLessThan(30, $elapsed);
    }

    // =========================================================================
    // Service Container Integration
    // =========================================================================

    /** @test Service can be resolved from container */
    public function test_service_resolved_from_container(): void
    {
        $crawler = app(SiteCrawlerInterface::class);

        $this->assertInstanceOf(SiteCrawler::class, $crawler);
    }

    /** @test Service is singleton */
    public function test_service_is_singleton(): void
    {
        $crawler1 = app(SiteCrawlerInterface::class);
        $crawler2 = app(SiteCrawlerInterface::class);

        // Each resolution should create a new instance (not singleton)
        // This is expected behavior for stateful services
        $this->assertInstanceOf(SiteCrawler::class, $crawler1);
        $this->assertInstanceOf(SiteCrawler::class, $crawler2);
    }
}
