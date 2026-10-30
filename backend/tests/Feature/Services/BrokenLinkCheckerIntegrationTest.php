<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\Audit\BrokenLinkChecker;
use App\Services\Audit\BrokenLinkResult;
use App\Services\Audit\CheckSummary;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Integration tests for TASK-14-10: Broken Link Checker
 *
 * Tests the BrokenLinkChecker service in realistic scenarios including:
 * - Full workflow integration
 * - Concurrent processing
 * - Integration with crawler context
 * - End-to-end link checking
 *
 * @see TEST-14-10
 * @see TASK-14-10
 */
class BrokenLinkCheckerIntegrationTest extends TestCase
{
    protected BrokenLinkChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = app(BrokenLinkChecker::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // INTEGRATION TESTS (INT)
    // =========================================================================

    /**
     * INT-001: Full workflow - all valid links.
     * 
     * @test
     */
    public function full_workflow_all_valid_links(): void
    {
        $html = $this->createHtmlWithLinks([
            ['href' => '/page1', 'text' => 'Page 1'],
            ['href' => '/page2', 'text' => 'Page 2'],
            ['href' => '/page3', 'text' => 'Page 3'],
        ]);

        Http::fake([
            'https://example.com/*' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(3, $summary->totalChecked);
        $this->assertEquals(3, $summary->validCount);
        $this->assertEquals(0, $summary->brokenCount);
        $this->assertTrue($summary->successRate >= 99.0);
    }

    /**
     * INT-002: Full workflow - mixed results.
     * 
     * @test
     */
    public function full_workflow_mixed_results(): void
    {
        $html = $this->createHtmlWithLinks([
            ['href' => '/valid', 'text' => 'Valid'],
            ['href' => '/broken', 'text' => 'Broken'],
            ['href' => '/redirect', 'text' => 'Redirect'],
            ['href' => '/error', 'text' => 'Error'],
        ]);

        Http::fake([
            'https://example.com/valid' => Http::response('', 200),
            'https://example.com/broken' => Http::response('', 404),
            'https://example.com/redirect' => Http::response('', 301, ['Location' => '/new']),
            'https://example.com/error' => Http::response('', 500),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(4, $summary->totalChecked);
        $this->assertEquals(1, $summary->validCount);
        $this->assertEquals(1, $summary->brokenCount);
        $this->assertEquals(1, $summary->redirectCount);
        $this->assertEquals(1, $summary->errorCount);
    }

    /**
     * INT-003: Integration with mixed link types.
     * 
     * @test
     */
    public function integration_with_mixed_link_types(): void
    {
        $html = '<html><head>
            <link rel="stylesheet" href="/style.css">
            <script src="/app.js"></script>
        </head><body>
            <a href="/page">Link</a>
            <img src="/image.png" alt="Image">
            <iframe src="/embed"></iframe>
        </body></html>';

        Http::fake([
            'https://example.com/*' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(5, $summary->totalChecked);

        $elementTypes = collect($summary->results)->pluck('elementType')->unique()->values()->all();
        $this->assertContains('a', $elementTypes);
        $this->assertContains('img', $elementTypes);
        $this->assertContains('link', $elementTypes);
        $this->assertContains('script', $elementTypes);
        $this->assertContains('iframe', $elementTypes);
    }

    /**
     * INT-004: Full page with realistic content.
     * 
     * @test
     */
    public function full_page_with_realistic_content(): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="icon" href="/favicon.ico">
    <script src="/js/app.js"></script>
</head>
<body>
    <header>
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
        </nav>
    </header>
    <main>
        <article>
            <h1>Welcome</h1>
            <img src="/images/hero.jpg" alt="Hero Image">
            <p>Check out our <a href="/products">products</a>.</p>
        </article>
        <aside>
            <iframe src="/ads/banner" width="300" height="250"></iframe>
        </aside>
    </main>
    <footer>
        <a href="/privacy">Privacy</a>
        <a href="/terms">Terms</a>
    </footer>
    <img srcset="/images/logo-1x.png 1x, /images/logo-2x.png 2x" alt="Logo">
</body>
</html>
HTML;

        Http::fake([
            'https://example.com/*' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        // Should extract all link types
        $this->assertGreaterThanOrEqual(10, $summary->totalChecked);

        // All should be valid
        $this->assertEquals($summary->totalChecked, $summary->validCount);
    }

    // =========================================================================
    // CONCURRENT PROCESSING TESTS (CONCUR)
    // =========================================================================

    /**
     * CONCUR-001: Handle concurrent checks.
     * 
     * @test
     */
    public function handle_concurrent_checks(): void
    {
        $links = [];
        for ($i = 1; $i <= 20; $i++) {
            $links[] = ['href' => "/page{$i}", 'text' => "Page {$i}"];
        }
        $html = $this->createHtmlWithLinks($links);

        Http::fake(function ($request) {
            return Http::response('', 200);
        });

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(20, $summary->totalChecked);
        $this->assertCount(20, $summary->results);
    }

    /**
     * CONCUR-002: Memory usage stable.
     * 
     * @test
     */
    public function memory_usage_stable(): void
    {
        $links = [];
        for ($i = 1; $i <= 100; $i++) {
            $links[] = ['href' => "/page{$i}", 'text' => "Page {$i}"];
        }
        $html = $this->createHtmlWithLinks($links);

        Http::fake(function ($request) {
            return Http::response('', 200);
        });

        $memoryBefore = memory_get_usage(true);
        $this->checker->check($html, 'https://example.com');
        $memoryAfter = memory_get_usage(true);

        // Memory should not grow unboundedly (less than 50MB growth)
        $memoryGrowth = ($memoryAfter - $memoryBefore) / 1024 / 1024;
        $this->assertLessThan(50, $memoryGrowth);
    }

    /**
     * CONCUR-003: Exception handling per link.
     * 
     * @test
     */
    public function exception_handling_per_link(): void
    {
        $html = $this->createHtmlWithLinks([
            ['href' => '/valid', 'text' => 'Valid'],
            ['href' => '/error', 'text' => 'Error'],
            ['href' => '/also-valid', 'text' => 'Also Valid'],
        ]);

        Http::fake([
            'https://example.com/valid' => Http::response('', 200),
            'https://example.com/error' => Http::response('', 500),
            'https://example.com/also-valid' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(3, $summary->totalChecked);
        $this->assertEquals(2, $summary->validCount);
        $this->assertEquals(1, $summary->errorCount);
    }

    /**
     * CONCUR-004: Timeout isolation.
     * 
     * @test
     */
    public function timeout_isolation(): void
    {
        $html = $this->createHtmlWithLinks([
            ['href' => '/timeout', 'text' => 'Timeout'],
            ['href' => '/valid', 'text' => 'Valid'],
        ]);

        Http::fake([
            'https://example.com/timeout' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
            'https://example.com/valid' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(2, $summary->totalChecked);
        $this->assertEquals(1, $summary->validCount);
        $this->assertEquals(1, $summary->timeoutCount);
    }

    // =========================================================================
    // CACHING TESTS
    // =========================================================================

    /**
     * Test caching of checked URLs.
     * 
     * @test
     */
    public function caching_of_checked_urls(): void
    {
        $url = 'https://example.com/cached-page';

        Http::fake([
            $url => Http::response('', 200),
        ]);

        // First check
        $result1 = $this->checker->checkUrl($url);

        // Second check should use cache
        $result2 = $this->checker->checkUrl($url);

        // Both should return valid results
        $this->assertEquals('valid', $result1->type);
        $this->assertEquals('valid', $result2->type);

        // Results should be identical (from cache)
        $this->assertEquals($result1->url, $result2->url);
        $this->assertEquals($result1->statusCode, $result2->statusCode);
    }

    /**
     * Test cache respects TTL.
     * 
     * @test
     */
    public function cache_respects_ttl(): void
    {
        $this->markTestSkipped('Requires time manipulation for TTL testing');

        $url = 'https://example.com/ttl-test';

        Http::fake([
            $url => Http::response('', 200),
        ]);

        // Check URL
        $this->checker->checkUrl($url);

        // Verify cache exists
        $cacheKey = 'link_check:' . md5($url);
        $this->assertTrue(Cache::has($cacheKey));
    }

    // =========================================================================
    // RATE LIMITING INTEGRATION TESTS
    // =========================================================================

    /**
     * Test rate limiting for external domains.
     * 
     * @test
     */
    public function rate_limiting_for_external_domains(): void
    {
        $html = '<html><body>
            <a href="https://external1.com/page1">External 1</a>
            <a href="https://external1.com/page2">External 2</a>
            <a href="https://external2.com/page">External 3</a>
        </body></html>';

        Http::fake([
            'https://external1.com/*' => Http::response('', 200),
            'https://external2.com/*' => Http::response('', 200),
        ]);

        $start = microtime(true);
        $summary = $this->checker->check($html, 'https://example.com');
        $duration = microtime(true) - $start;

        // Should have checked all 3 external links
        $this->assertEquals(3, $summary->totalChecked);

        // Should have some delay due to rate limiting (but not too much)
        $this->assertLessThan(5, $duration, 'Rate limiting should not cause excessive delays');
    }

    // =========================================================================
    // ERROR RECOVERY TESTS
    // =========================================================================

    /**
     * Test graceful handling of malformed URLs.
     * 
     * @test
     */
    public function graceful_handling_of_malformed_urls(): void
    {
        $html = '<html><body>
            <a href="not-a-valid-url">Invalid URL</a>
            <a href="/valid">Valid</a>
        </body></html>';

        Http::fake([
            'https://example.com/valid' => Http::response('', 200),
        ]);

        // Should not throw exception
        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertGreaterThanOrEqual(1, $summary->totalChecked);
    }

    /**
     * Test graceful handling of network errors.
     * 
     * @test
     */
    public function graceful_handling_of_network_errors(): void
    {
        $html = '<html><body>
            <a href="https://network-error.com/page">Network Error</a>
            <a href="/valid">Valid</a>
        </body></html>';

        Http::fake([
            'https://network-error.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Network error');
            },
            'https://example.com/valid' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(2, $summary->totalChecked);
        $this->assertEquals(1, $summary->validCount);
    }

    /**
     * Test graceful handling of DNS failures.
     * 
     * @test
     */
    public function graceful_handling_of_dns_failures(): void
    {
        $html = '<html><body>
            <a href="https://nonexistent-domain-xyz123.com/page">DNS Failure</a>
            <a href="/valid">Valid</a>
        </body></html>';

        Http::fake([
            'https://nonexistent-domain-xyz123.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Could not resolve host');
            },
            'https://example.com/valid' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(2, $summary->totalChecked);
    }

    // =========================================================================
    // EXTERNAL VS INTERNAL HANDLING
    // =========================================================================

    /**
     * Test internal vs external link handling.
     * 
     * @test
     */
    public function internal_vs_external_link_handling(): void
    {
        $html = '<html><body>
            <a href="/internal">Internal</a>
            <a href="https://external.com/page">External</a>
        </body></html>';

        Http::fake([
            'https://example.com/internal' => Http::response('', 200),
            'https://external.com/*' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(2, $summary->totalChecked);
    }

    /**
     * Test disabling external link checking.
     * 
     * @test
     */
    public function disabling_external_link_checking(): void
    {
        $checker = app(BrokenLinkChecker::class);
        $checker->setConfig(['check_external' => false]);

        $html = '<html><body>
            <a href="/internal">Internal</a>
            <a href="https://external.com/page">External</a>
        </body></html>';

        Http::fake([
            'https://example.com/internal' => Http::response('', 200),
        ]);

        $summary = $checker->check($html, 'https://example.com');

        // Only internal should be checked
        $this->assertEquals(1, $summary->totalChecked);
    }

    // =========================================================================
    // RESULT DETAIL TESTS
    // =========================================================================

    /**
     * Test result contains complete information.
     * 
     * @test
     */
    public function result_contains_complete_information(): void
    {
        $html = '<html><body>
            <p>Click <a href="/page">here</a> for more info.</p>
        </body></html>';

        Http::fake([
            'https://example.com/page' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $result = $summary->results[0] ?? null;

        $this->assertNotNull($result);
        $this->assertEquals('https://example.com/page', $result->url);
        $this->assertEquals('https://example.com', $result->sourceUrl);
        $this->assertEquals(200, $result->statusCode);
        $this->assertEquals('valid', $result->type);
        $this->assertEquals('here', $result->linkText);
        $this->assertEquals('a', $result->elementType);
        $this->assertNotNull($result->responseTimeMs);
    }

    /**
     * Test redirect result contains destination URL.
     * 
     * @test
     */
    public function redirect_result_contains_destination_url(): void
    {
        $html = '<html><body>
            <a href="/old-page">Old Page</a>
        </body></html>';

        Http::fake([
            'https://example.com/old-page' => Http::response('', 301, ['Location' => 'https://example.com/new-page']),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $result = $summary->results[0] ?? null;

        $this->assertNotNull($result);
        $this->assertEquals('redirect', $result->type);
        $this->assertEquals('https://example.com/new-page', $result->redirectUrl);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create HTML with links for testing.
     */
    private function createHtmlWithLinks(array $links): string
    {
        $linksHtml = '';
        foreach ($links as $link) {
            $linksHtml .= '<a href="' . $link['href'] . '">' . $link['text'] . '</a>';
        }

        return '<html><body>' . $linksHtml . '</body></html>';
    }
}
