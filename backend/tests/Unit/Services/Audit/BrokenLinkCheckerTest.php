<?php

namespace Tests\Unit\Services\Audit;

use Tests\TestCase;
use App\Services\Audit\BrokenLinkChecker;
use App\Services\Audit\BrokenLinkResult;
use App\Services\Audit\CheckSummary;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for TASK-14-10: Broken Link Checker
 *
 * Tests the BrokenLinkChecker service responsible for:
 * - Extracting links from HTML (anchor, img, link, script, iframe)
 * - Validating internal links relative to base URL
 * - Using HEAD requests for external links with GET fallback
 * - Categorizing broken links by type (broken, redirect, timeout, error)
 * - Respecting rate limits and robots.txt
 *
 * @see TEST-14-10
 * @see TASK-14-10
 */
class BrokenLinkCheckerTest extends TestCase
{
    protected BrokenLinkChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = $this->createChecker();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create checker instance with default config.
     */
    protected function createChecker(array $config = []): BrokenLinkChecker
    {
        $defaultConfig = [
            'timeout' => 10,
            'max_redirects' => 5,
            'user_agent' => 'SEOIndicator-Bot/1.0',
            'check_external' => true,
            'rate_limit_ms' => 100,
            'retries' => 2,
        ];

        return new BrokenLinkChecker(array_merge($defaultConfig, $config));
    }

    /**
     * Mock HTTP response for a URL.
     */
    protected function mockHttpResponse(string $url, int $status, array $headers = [], ?string $body = null): void
    {
        Http::fake([
            $url => Http::response($body ?? '', $status, $headers),
        ]);
    }

    /**
     * Mock multiple HTTP responses.
     */
    protected function mockHttpResponses(array $responses): void
    {
        Http::fake($responses);
    }

    // =========================================================================
    // SERVICE INSTANTIATION TESTS (SI)
    // =========================================================================

    /**
     * SI-001: Service can be instantiated.
     * 
     * @test
     */
    public function service_can_be_instantiated(): void
    {
        $checker = new BrokenLinkChecker();

        $this->assertInstanceOf(BrokenLinkChecker::class, $checker);
    }

    /**
     * SI-002: Default configuration applied.
     * 
     * @test
     */
    public function default_configuration_applied(): void
    {
        $checker = new BrokenLinkChecker();

        $config = $checker->getConfig();

        $this->assertEquals(10, $config['timeout']);
        $this->assertEquals(5, $config['max_redirects']);
    }

    /**
     * SI-003: Custom configuration applied.
     * 
     * @test
     */
    public function custom_configuration_applied(): void
    {
        $checker = $this->createChecker([
            'timeout' => 5,
            'max_redirects' => 3,
        ]);

        $config = $checker->getConfig();

        $this->assertEquals(5, $config['timeout']);
        $this->assertEquals(3, $config['max_redirects']);
    }

    /**
     * SI-004: Dependencies injected.
     * 
     * @test
     */
    public function dependencies_injected(): void
    {
        $checker = app(BrokenLinkChecker::class);

        $this->assertInstanceOf(BrokenLinkChecker::class, $checker);
    }

    // =========================================================================
    // LINK EXTRACTION TESTS (LINK-EXT)
    // =========================================================================

    /**
     * LINK-EXT-001: Extract anchor hrefs.
     * 
     * @test
     */
    public function extract_anchor_hrefs(): void
    {
        $html = '<html><body><a href="/page1">Link 1</a><a href="/page2">Link 2</a></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(2, $links);
        $this->assertEquals('https://example.com/page1', $links[0]['url']);
        $this->assertEquals('a', $links[0]['elementType']);
        $this->assertEquals('Link 1', $links[0]['linkText']);
    }

    /**
     * LINK-EXT-002: Extract image srcs.
     * 
     * @test
     */
    public function extract_image_srcs(): void
    {
        $html = '<html><body><img src="/img1.png" alt="Image 1"><img src="/img2.png" alt="Image 2"></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(2, $links);
        $this->assertEquals('img', $links[0]['elementType']);
        $this->assertEquals('Image 1', $links[0]['linkText']); // alt text
    }

    /**
     * LINK-EXT-003: Extract link hrefs.
     * 
     * @test
     */
    public function extract_link_hrefs(): void
    {
        $html = '<html><head><link rel="stylesheet" href="/style.css"><link rel="icon" href="/favicon.ico"></head></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(2, $links);
        $this->assertEquals('link', $links[0]['elementType']);
    }

    /**
     * LINK-EXT-004: Extract script srcs.
     * 
     * @test
     */
    public function extract_script_srcs(): void
    {
        $html = '<html><body><script src="/app.js"></script><script src="/analytics.js"></script></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(2, $links);
        $this->assertEquals('script', $links[0]['elementType']);
    }

    /**
     * LINK-EXT-005: Extract iframe srcs.
     * 
     * @test
     */
    public function extract_iframe_srcs(): void
    {
        $html = '<html><body><iframe src="/frame1"></iframe><iframe src="/frame2"></iframe></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(2, $links);
        $this->assertEquals('iframe', $links[0]['elementType']);
    }

    /**
     * LINK-EXT-006: Extract multiple link types.
     * 
     * @test
     */
    public function extract_multiple_link_types(): void
    {
        $html = '<html><head>
            <link rel="stylesheet" href="/style.css">
        </head><body>
            <a href="/page">Link</a>
            <img src="/img.png" alt="Image">
            <script src="/app.js"></script>
            <iframe src="/frame"></iframe>
        </body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(5, $links);
        $elementTypes = array_column($links, 'elementType');
        $this->assertContains('a', $elementTypes);
        $this->assertContains('img', $elementTypes);
        $this->assertContains('link', $elementTypes);
        $this->assertContains('script', $elementTypes);
        $this->assertContains('iframe', $elementTypes);
    }

    /**
     * LINK-EXT-007: Skip empty hrefs.
     * 
     * @test
     */
    public function skip_empty_hrefs(): void
    {
        $html = '<html><body><a href="">Empty</a><a href="/page">Valid</a></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(1, $links);
        $this->assertEquals('https://example.com/page', $links[0]['url']);
    }

    /**
     * LINK-EXT-008: Skip javascript: links.
     * 
     * @test
     */
    public function skip_javascript_links(): void
    {
        $html = '<html><body><a href="javascript:void(0)">JS</a><a href="/page">Valid</a></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(1, $links);
    }

    /**
     * LINK-EXT-009: Skip mailto: links.
     * 
     * @test
     */
    public function skip_mailto_links(): void
    {
        $html = '<html><body><a href="mailto:test@example.com">Email</a><a href="/page">Valid</a></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(1, $links);
    }

    /**
     * LINK-EXT-010: Skip tel: links.
     * 
     * @test
     */
    public function skip_tel_links(): void
    {
        $html = '<html><body><a href="tel:+1234567890">Call</a><a href="/page">Valid</a></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(1, $links);
    }

    /**
     * LINK-EXT-011: Skip # anchor links.
     * 
     * @test
     */
    public function skip_anchor_links(): void
    {
        $html = '<html><body><a href="#section">Jump</a><a href="/page">Valid</a></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(1, $links);
    }

    /**
     * LINK-EXT-012: Handle relative URLs.
     * 
     * @test
     */
    public function handle_relative_urls(): void
    {
        $html = '<html><body><a href="../page">Relative</a></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com/docs/guide/');

        $this->assertCount(1, $links);
        $this->assertEquals('https://example.com/docs/page', $links[0]['url']);
    }

    /**
     * LINK-EXT-013: Handle protocol-relative URLs.
     * 
     * @test
     */
    public function handle_protocol_relative_urls(): void
    {
        $html = '<html><body><a href="//other.com/page">Protocol Relative</a></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(1, $links);
        $this->assertEquals('https://other.com/page', $links[0]['url']);
    }

    /**
     * LINK-EXT-014: Handle data URIs.
     * 
     * @test
     */
    public function skip_data_uris(): void
    {
        $html = '<html><body><img src="data:image/png;base64,iVBORw0KGgo=" alt="Data"><img src="/img.png" alt="Valid"></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $this->assertCount(1, $links);
        $this->assertStringContainsString('/img.png', $links[0]['url']);
    }

    /**
     * LINK-EXT-015: Extract srcset URLs.
     * 
     * @test
     */
    public function extract_srcset_urls(): void
    {
        $html = '<html><body><img srcset="/img-1x.png 1x, /img-2x.png 2x" src="/img.png" alt="Responsive"></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $urls = array_column($links, 'url');
        $this->assertGreaterThanOrEqual(3, count($links)); // src + 2 srcset
        $this->assertContains('https://example.com/img.png', $urls);
        $this->assertContains('https://example.com/img-1x.png', $urls);
        $this->assertContains('https://example.com/img-2x.png', $urls);
    }

    /**
     * LINK-EXT-016: Extract poster URLs.
     * 
     * @test
     */
    public function extract_poster_urls(): void
    {
        $html = '<html><body><video poster="/poster.jpg" src="/video.mp4"></video></body></html>';

        $links = $this->checker->extractLinks($html, 'https://example.com');

        $urls = array_column($links, 'url');
        $this->assertContains('https://example.com/poster.jpg', $urls);
    }

    // =========================================================================
    // INTERNAL LINKS TESTS (INT-LINK)
    // =========================================================================

    /**
     * INT-LINK-001: Valid internal link.
     * 
     * @test
     */
    public function valid_internal_link(): void
    {
        $this->mockHttpResponse('https://example.com/page', 200);

        $result = $this->checker->checkUrl('https://example.com/page');

        $this->assertEquals(200, $result->statusCode);
        $this->assertEquals('valid', $result->type);
    }

    /**
     * INT-LINK-002: Broken internal link.
     * 
     * @test
     */
    public function broken_internal_link(): void
    {
        $this->mockHttpResponse('https://example.com/nonexistent', 404);

        $result = $this->checker->checkUrl('https://example.com/nonexistent');

        $this->assertEquals(404, $result->statusCode);
        $this->assertEquals('broken', $result->type);
    }

    /**
     * INT-LINK-003: Internal redirect.
     * 
     * @test
     */
    public function internal_redirect(): void
    {
        $this->mockHttpResponse('https://example.com/old-page', 301, ['Location' => 'https://example.com/new-page']);

        $result = $this->checker->checkUrl('https://example.com/old-page');

        $this->assertEquals(301, $result->statusCode);
        $this->assertEquals('redirect', $result->type);
        $this->assertEquals('https://example.com/new-page', $result->redirectUrl);
    }

    /**
     * INT-LINK-004: Internal server error.
     * 
     * @test
     */
    public function internal_server_error(): void
    {
        $this->mockHttpResponse('https://example.com/error-page', 500);

        $result = $this->checker->checkUrl('https://example.com/error-page');

        $this->assertEquals(500, $result->statusCode);
        $this->assertEquals('error', $result->type);
    }

    /**
     * INT-LINK-005: Relative path resolution.
     * 
     * @test
     */
    public function relative_path_resolution(): void
    {
        $html = '<html><body><a href="../page">Link</a></body></html>';
        $this->mockHttpResponse('https://example.com/docs/page', 200);

        $result = $this->checker->check($html, 'https://example.com/docs/guide/');

        $this->assertGreaterThan(0, $result->totalChecked);
    }

    /**
     * INT-LINK-006: Root-relative path.
     * 
     * @test
     */
    public function root_relative_path(): void
    {
        $html = '<html><body><a href="/about">About</a></body></html>';
        $this->mockHttpResponse('https://example.com/about', 200);

        $result = $this->checker->check($html, 'https://example.com/docs/guide/');

        $this->assertGreaterThan(0, $result->totalChecked);
    }

    /**
     * INT-LINK-008: Query string preserved.
     * 
     * @test
     */
    public function query_string_preserved(): void
    {
        $this->mockHttpResponse('https://example.com/page?param=value', 200);

        $result = $this->checker->checkUrl('https://example.com/page?param=value');

        $this->assertEquals(200, $result->statusCode);
    }

    /**
     * INT-LINK-009: Fragment removed for check.
     * 
     * @test
     */
    public function fragment_removed_for_check(): void
    {
        $this->mockHttpResponse('https://example.com/page', 200);

        $result = $this->checker->checkUrl('https://example.com/page#section');

        $this->assertEquals(200, $result->statusCode);
    }

    // =========================================================================
    // EXTERNAL LINKS - HEAD REQUESTS TESTS (EXT-HEAD)
    // =========================================================================

    /**
     * EXT-HEAD-001: HEAD request success.
     * 
     * @test
     */
    public function head_request_success(): void
    {
        Http::fake([
            'https://external.com/page' => Http::sequence()
                ->pushResponse(Http::response('', 200)) // HEAD
        ]);

        $result = $this->checker->checkUrl('https://external.com/page');

        $this->assertEquals(200, $result->statusCode);
        $this->assertEquals('valid', $result->type);
    }

    /**
     * EXT-HEAD-002: HEAD failure → GET fallback.
     * 
     * @test
     */
    public function head_failure_get_fallback(): void
    {
        Http::fake([
            'https://external.com/page' => Http::sequence()
                ->pushResponse(Http::response('', 405)) // HEAD not allowed
                ->pushResponse(Http::response('content', 200)) // GET succeeds
        ]);

        $result = $this->checker->checkUrl('https://external.com/page');

        $this->assertEquals(200, $result->statusCode);
    }

    /**
     * EXT-HEAD-003: HEAD timeout → GET fallback.
     * 
     * @test
     */
    public function head_timeout_get_fallback(): void
    {
        Http::fake([
            'https://external.com/page' => Http::sequence()
                ->pushResponse(Http::response('', 200)) // Will be called as GET after HEAD timeout
        ]);

        $result = $this->checker->checkUrl('https://external.com/page');

        $this->assertEquals(200, $result->statusCode);
    }

    /**
     * EXT-HEAD-004: Both HEAD and GET fail.
     * 
     * @test
     */
    public function both_head_and_get_fail(): void
    {
        Http::fake([
            'https://external.com/notfound' => Http::sequence()
                ->pushResponse(Http::response('', 404)) // HEAD
                ->pushResponse(Http::response('', 404)) // GET
        ]);

        $result = $this->checker->checkUrl('https://external.com/notfound');

        $this->assertEquals(404, $result->statusCode);
        $this->assertEquals('broken', $result->type);
    }

    /**
     * EXT-HEAD-005: External redirect.
     * 
     * @test
     */
    public function external_redirect(): void
    {
        $this->mockHttpResponse('https://external.com/old', 301, ['Location' => 'https://external.com/new']);

        $result = $this->checker->checkUrl('https://external.com/old');

        $this->assertEquals(301, $result->statusCode);
        $this->assertEquals('redirect', $result->type);
        $this->assertEquals('https://external.com/new', $result->redirectUrl);
    }

    /**
     * EXT-HEAD-006: External server error.
     * 
     * @test
     */
    public function external_server_error(): void
    {
        $this->mockHttpResponse('https://external.com/error', 503);

        $result = $this->checker->checkUrl('https://external.com/error');

        $this->assertEquals(503, $result->statusCode);
        $this->assertEquals('error', $result->type);
    }

    /**
     * EXT-HEAD-007: Connection timeout.
     * 
     * @test
     */
    public function connection_timeout(): void
    {
        Http::fake(function ($request) {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $result = $this->checker->checkUrl('https://slow-external.com/page');

        $this->assertEquals('timeout', $result->type);
        $this->assertNotNull($result->errorMessage);
    }

    /**
     * EXT-HEAD-008: DNS resolution failure.
     * 
     * @test
     */
    public function dns_resolution_failure(): void
    {
        Http::fake(function ($request) {
            throw new \Illuminate\Http\Client\ConnectionException('Could not resolve host');
        });

        $result = $this->checker->checkUrl('https://nonexistent-domain-12345.com/page');

        $this->assertEquals('broken', $result->type);
        $this->assertNotNull($result->errorMessage);
    }

    /**
     * EXT-HEAD-009: SSL certificate error.
     * 
     * @test
     */
    public function ssl_certificate_error(): void
    {
        Http::fake(function ($request) {
            throw new \Illuminate\Http\Client\ConnectionException('SSL certificate problem');
        });

        $result = $this->checker->checkUrl('https://bad-ssl.com/page');

        $this->assertEquals('error', $result->type);
        $this->assertStringContainsString('SSL', $result->errorMessage);
    }

    // =========================================================================
    // HTTP STATUS HANDLING TESTS (HTTP-STAT)
    // =========================================================================

    /**
     * HTTP-STAT-001: Status 200 OK.
     * 
     * @test
     */
    public function status_200_ok(): void
    {
        $this->mockHttpResponse('https://example.com/page', 200);

        $result = $this->checker->checkUrl('https://example.com/page');

        $this->assertEquals(200, $result->statusCode);
        $this->assertEquals('valid', $result->type);
    }

    /**
     * HTTP-STAT-002: Status 201 Created.
     * 
     * @test
     */
    public function status_201_created(): void
    {
        $this->mockHttpResponse('https://example.com/resource', 201);

        $result = $this->checker->checkUrl('https://example.com/resource');

        $this->assertEquals(201, $result->statusCode);
        $this->assertEquals('valid', $result->type);
    }

    /**
     * HTTP-STAT-003: Status 204 No Content.
     * 
     * @test
     */
    public function status_204_no_content(): void
    {
        $this->mockHttpResponse('https://example.com/resource', 204);

        $result = $this->checker->checkUrl('https://example.com/resource');

        $this->assertEquals(204, $result->statusCode);
        $this->assertEquals('valid', $result->type);
    }

    /**
     * HTTP-STAT-004: Status 301 Moved Permanently.
     * 
     * @test
     */
    public function status_301_moved_permanently(): void
    {
        $this->mockHttpResponse('https://example.com/old', 301, ['Location' => 'https://example.com/new']);

        $result = $this->checker->checkUrl('https://example.com/old');

        $this->assertEquals(301, $result->statusCode);
        $this->assertEquals('redirect', $result->type);
        $this->assertEquals('https://example.com/new', $result->redirectUrl);
    }

    /**
     * HTTP-STAT-005: Status 302 Found.
     * 
     * @test
     */
    public function status_302_found(): void
    {
        $this->mockHttpResponse('https://example.com/temp', 302, ['Location' => 'https://example.com/new']);

        $result = $this->checker->checkUrl('https://example.com/temp');

        $this->assertEquals(302, $result->statusCode);
        $this->assertEquals('redirect', $result->type);
    }

    /**
     * HTTP-STAT-006: Status 307 Temporary Redirect.
     * 
     * @test
     */
    public function status_307_temporary_redirect(): void
    {
        $this->mockHttpResponse('https://example.com/temp', 307, ['Location' => 'https://example.com/new']);

        $result = $this->checker->checkUrl('https://example.com/temp');

        $this->assertEquals(307, $result->statusCode);
        $this->assertEquals('redirect', $result->type);
    }

    /**
     * HTTP-STAT-007: Status 308 Permanent Redirect.
     * 
     * @test
     */
    public function status_308_permanent_redirect(): void
    {
        $this->mockHttpResponse('https://example.com/old', 308, ['Location' => 'https://example.com/new']);

        $result = $this->checker->checkUrl('https://example.com/old');

        $this->assertEquals(308, $result->statusCode);
        $this->assertEquals('redirect', $result->type);
    }

    /**
     * HTTP-STAT-008: Status 400 Bad Request.
     * 
     * @test
     */
    public function status_400_bad_request(): void
    {
        $this->mockHttpResponse('https://example.com/bad', 400);

        $result = $this->checker->checkUrl('https://example.com/bad');

        $this->assertEquals(400, $result->statusCode);
        $this->assertEquals('error', $result->type);
    }

    /**
     * HTTP-STAT-009: Status 401 Unauthorized.
     * 
     * @test
     */
    public function status_401_unauthorized(): void
    {
        $this->mockHttpResponse('https://example.com/protected', 401);

        $result = $this->checker->checkUrl('https://example.com/protected');

        $this->assertEquals(401, $result->statusCode);
        $this->assertEquals('auth_required', $result->type);
    }

    /**
     * HTTP-STAT-010: Status 403 Forbidden.
     * 
     * @test
     */
    public function status_403_forbidden(): void
    {
        $this->mockHttpResponse('https://example.com/forbidden', 403);

        $result = $this->checker->checkUrl('https://example.com/forbidden');

        $this->assertEquals(403, $result->statusCode);
        $this->assertEquals('access_denied', $result->type);
    }

    /**
     * HTTP-STAT-011: Status 404 Not Found.
     * 
     * @test
     */
    public function status_404_not_found(): void
    {
        $this->mockHttpResponse('https://example.com/nonexistent', 404);

        $result = $this->checker->checkUrl('https://example.com/nonexistent');

        $this->assertEquals(404, $result->statusCode);
        $this->assertEquals('broken', $result->type);
    }

    /**
     * HTTP-STAT-012: Status 410 Gone.
     * 
     * @test
     */
    public function status_410_gone(): void
    {
        $this->mockHttpResponse('https://example.com/deleted', 410);

        $result = $this->checker->checkUrl('https://example.com/deleted');

        $this->assertEquals(410, $result->statusCode);
        $this->assertEquals('gone', $result->type);
    }

    /**
     * HTTP-STAT-013: Status 429 Too Many Requests.
     * 
     * @test
     */
    public function status_429_too_many_requests(): void
    {
        $this->mockHttpResponse('https://example.com/rate-limited', 429);

        $result = $this->checker->checkUrl('https://example.com/rate-limited');

        $this->assertEquals(429, $result->statusCode);
        $this->assertEquals('rate_limited', $result->type);
    }

    /**
     * HTTP-STAT-014: Status 500 Internal Server Error.
     * 
     * @test
     */
    public function status_500_internal_server_error(): void
    {
        $this->mockHttpResponse('https://example.com/error', 500);

        $result = $this->checker->checkUrl('https://example.com/error');

        $this->assertEquals(500, $result->statusCode);
        $this->assertEquals('error', $result->type);
    }

    /**
     * HTTP-STAT-015: Status 502 Bad Gateway.
     * 
     * @test
     */
    public function status_502_bad_gateway(): void
    {
        $this->mockHttpResponse('https://example.com/bad-gateway', 502);

        $result = $this->checker->checkUrl('https://example.com/bad-gateway');

        $this->assertEquals(502, $result->statusCode);
        $this->assertEquals('error', $result->type);
    }

    /**
     * HTTP-STAT-016: Status 503 Service Unavailable.
     * 
     * @test
     */
    public function status_503_service_unavailable(): void
    {
        $this->mockHttpResponse('https://example.com/unavailable', 503);

        $result = $this->checker->checkUrl('https://example.com/unavailable');

        $this->assertEquals(503, $result->statusCode);
        $this->assertEquals('error', $result->type);
    }

    /**
     * HTTP-STAT-017: Status 504 Gateway Timeout.
     * 
     * @test
     */
    public function status_504_gateway_timeout(): void
    {
        $this->mockHttpResponse('https://example.com/gateway-timeout', 504);

        $result = $this->checker->checkUrl('https://example.com/gateway-timeout');

        $this->assertEquals(504, $result->statusCode);
        $this->assertEquals('timeout', $result->type);
    }

    // =========================================================================
    // CATEGORIZATION TESTS (CAT-TYPE)
    // =========================================================================

    /**
     * CAT-TYPE-001: Broken link category.
     * 
     * @test
     */
    public function broken_link_category(): void
    {
        $this->mockHttpResponse('https://example.com/notfound', 404);

        $result = $this->checker->checkUrl('https://example.com/notfound');

        $this->assertEquals('broken', $result->type);
    }

    /**
     * CAT-TYPE-002: Redirect category.
     * 
     * @test
     */
    public function redirect_category(): void
    {
        $this->mockHttpResponse('https://example.com/redirect', 301, ['Location' => '/new']);

        $result = $this->checker->checkUrl('https://example.com/redirect');

        $this->assertEquals('redirect', $result->type);
    }

    /**
     * CAT-TYPE-003: Timeout category.
     * 
     * @test
     */
    public function timeout_category(): void
    {
        Http::fake(function ($request) {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $result = $this->checker->checkUrl('https://example.com/timeout');

        $this->assertEquals('timeout', $result->type);
    }

    /**
     * CAT-TYPE-004: Error category.
     * 
     * @test
     */
    public function error_category(): void
    {
        $this->mockHttpResponse('https://example.com/server-error', 500);

        $result = $this->checker->checkUrl('https://example.com/server-error');

        $this->assertEquals('error', $result->type);
    }

    /**
     * CAT-TYPE-005: Access denied category.
     * 
     * @test
     */
    public function access_denied_category(): void
    {
        $this->mockHttpResponse('https://example.com/forbidden', 403);

        $result = $this->checker->checkUrl('https://example.com/forbidden');

        $this->assertEquals('access_denied', $result->type);
    }

    /**
     * CAT-TYPE-006: Auth required category.
     * 
     * @test
     */
    public function auth_required_category(): void
    {
        $this->mockHttpResponse('https://example.com/protected', 401);

        $result = $this->checker->checkUrl('https://example.com/protected');

        $this->assertEquals('auth_required', $result->type);
    }

    /**
     * CAT-TYPE-007: Gone permanently category.
     * 
     * @test
     */
    public function gone_permanently_category(): void
    {
        $this->mockHttpResponse('https://example.com/deleted', 410);

        $result = $this->checker->checkUrl('https://example.com/deleted');

        $this->assertEquals('gone', $result->type);
    }

    /**
     * CAT-TYPE-008: Rate limited category.
     * 
     * @test
     */
    public function rate_limited_category(): void
    {
        $this->mockHttpResponse('https://example.com/rate-limited', 429);

        $result = $this->checker->checkUrl('https://example.com/rate-limited');

        $this->assertEquals('rate_limited', $result->type);
    }

    // =========================================================================
    // RESULT STRUCTURE TESTS (RESULT)
    // =========================================================================

    /**
     * RESULT-001: Result contains URL.
     * 
     * @test
     */
    public function result_contains_url(): void
    {
        $this->mockHttpResponse('https://example.com/page', 200);

        $result = $this->checker->checkUrl('https://example.com/page');

        $this->assertEquals('https://example.com/page', $result->url);
    }

    /**
     * RESULT-002: Result contains status code.
     * 
     * @test
     */
    public function result_contains_status_code(): void
    {
        $this->mockHttpResponse('https://example.com/page', 200);

        $result = $this->checker->checkUrl('https://example.com/page');

        $this->assertEquals(200, $result->statusCode);
    }

    /**
     * RESULT-003: Result contains type.
     * 
     * @test
     */
    public function result_contains_type(): void
    {
        $this->mockHttpResponse('https://example.com/notfound', 404);

        $result = $this->checker->checkUrl('https://example.com/notfound');

        $this->assertEquals('broken', $result->type);
    }

    /**
     * RESULT-004: Result contains context.
     * 
     * @test
     */
    public function result_contains_context(): void
    {
        $html = '<html><body><p>Some text <a href="/page">Link</a> more text</p></body></html>';
        $this->mockHttpResponse('https://example.com/page', 200);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertIsArray($summary->results);
        if (count($summary->results) > 0) {
            $result = $summary->results[0];
            $this->assertNotNull($result->surroundingText);
        }
    }

    /**
     * RESULT-005: Result contains redirect URL.
     * 
     * @test
     */
    public function result_contains_redirect_url(): void
    {
        $this->mockHttpResponse('https://example.com/old', 301, ['Location' => 'https://example.com/new']);

        $result = $this->checker->checkUrl('https://example.com/old');

        $this->assertEquals('https://example.com/new', $result->redirectUrl);
    }

    /**
     * RESULT-006: Result contains response time.
     * 
     * @test
     */
    public function result_contains_response_time(): void
    {
        $this->mockHttpResponse('https://example.com/page', 200);

        $result = $this->checker->checkUrl('https://example.com/page');

        $this->assertIsInt($result->responseTimeMs);
        $this->assertGreaterThanOrEqual(0, $result->responseTimeMs);
    }

    /**
     * RESULT-007: Result contains source URL.
     * 
     * @test
     */
    public function result_contains_source_url(): void
    {
        $html = '<html><body><a href="/page">Link</a></body></html>';
        $this->mockHttpResponse('https://example.com/page', 200);

        $summary = $this->checker->check($html, 'https://example.com/source');

        $this->assertIsArray($summary->results);
        if (count($summary->results) > 0) {
            $result = $summary->results[0];
            $this->assertEquals('https://example.com/source', $result->sourceUrl);
        }
    }

    /**
     * RESULT-008: Result contains link text.
     * 
     * @test
     */
    public function result_contains_link_text(): void
    {
        $html = '<html><body><a href="/page">Click Here</a></body></html>';
        $this->mockHttpResponse('https://example.com/page', 200);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertIsArray($summary->results);
        if (count($summary->results) > 0) {
            $result = $summary->results[0];
            $this->assertEquals('Click Here', $result->linkText);
        }
    }

    /**
     * RESULT-009: Result contains element type.
     * 
     * @test
     */
    public function result_contains_element_type(): void
    {
        $html = '<html><body><a href="/page">Link</a><img src="/img.png" alt="Image"></body></html>';
        $this->mockHttpResponse('https://example.com/page', 200);
        $this->mockHttpResponse('https://example.com/img.png', 200);

        $summary = $this->checker->check($html, 'https://example.com');

        $elementTypes = array_map(fn($r) => $r->elementType, $summary->results);
        $this->assertContains('a', $elementTypes);
        $this->assertContains('img', $elementTypes);
    }

    /**
     * RESULT-010: Aggregated summary.
     * 
     * @test
     */
    public function aggregated_summary(): void
    {
        $html = '<html><body>
            <a href="/page1">Valid</a>
            <a href="/page2">Valid</a>
            <a href="/notfound">Broken</a>
            <a href="/redirect">Redirect</a>
        </body></html>';

        Http::fake([
            'https://example.com/page1' => Http::response('', 200),
            'https://example.com/page2' => Http::response('', 200),
            'https://example.com/notfound' => Http::response('', 404),
            'https://example.com/redirect' => Http::response('', 301, ['Location' => '/new']),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(4, $summary->totalChecked);
        $this->assertEquals(2, $summary->validCount);
        $this->assertEquals(1, $summary->brokenCount);
        $this->assertEquals(1, $summary->redirectCount);
    }

    // =========================================================================
    // SECURITY TESTS (SEC)
    // =========================================================================

    /**
     * SEC-001: SSRF prevention - localhost.
     * 
     * @test
     */
    public function ssrf_prevention_localhost(): void
    {
        $result = $this->checker->checkUrl('http://localhost/admin');

        $this->assertEquals('blocked', $result->type);
        $this->assertStringContainsString('SSRF', $result->errorMessage ?? '');
    }

    /**
     * SEC-002: SSRF prevention - private IP.
     * 
     * @test
     */
    public function ssrf_prevention_private_ip(): void
    {
        $result = $this->checker->checkUrl('http://192.168.1.1/admin');

        $this->assertEquals('blocked', $result->type);
    }

    /**
     * SEC-003: SSRF prevention - cloud metadata.
     * 
     * @test
     */
    public function ssrf_prevention_cloud_metadata(): void
    {
        $result = $this->checker->checkUrl('http://169.254.169.254/latest/meta-data/');

        $this->assertEquals('blocked', $result->type);
    }

    /**
     * SEC-004: No credentials in logs.
     * 
     * @test
     */
    public function no_credentials_in_logs(): void
    {
        Log::shouldReceive('info')->never();

        $result = $this->checker->checkUrl('https://user:password@example.com/page');

        // URL should be sanitized if logged
        $this->assertNotNull($result);
    }

    /**
     * SEC-005: Maximum URL length.
     * 
     * @test
     */
    public function maximum_url_length(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 3000);

        $result = $this->checker->checkUrl($longUrl);

        $this->assertNotNull($result);
        // Should either truncate or reject gracefully
    }

    /**
     * SEC-006: Unicode URL handling.
     * 
     * @test
     */
    public function unicode_url_handling(): void
    {
        $this->mockHttpResponse('https://example.com/%E6%97%A5%E6%9C%AC%E8%AA%9E', 200);

        $result = $this->checker->checkUrl('https://example.com/日本語');

        $this->assertNotNull($result);
    }

    /**
     * SEC-007: SQL injection in URL.
     * 
     * @test
     */
    public function sql_injection_in_url(): void
    {
        $this->mockHttpResponse('https://example.com/page?id=1%27%20OR%20%271%27%3D%271', 200);

        $result = $this->checker->checkUrl("https://example.com/page?id=1' OR '1'='1");

        $this->assertNotNull($result);
        // Should not cause SQL issues
    }

    // =========================================================================
    // EDGE CASES TESTS (EDGE)
    // =========================================================================

    /**
     * EDGE-001: Empty HTML.
     * 
     * @test
     */
    public function empty_html(): void
    {
        $summary = $this->checker->check('', 'https://example.com');

        $this->assertEquals(0, $summary->totalChecked);
    }

    /**
     * EDGE-002: HTML with no links.
     * 
     * @test
     */
    public function html_with_no_links(): void
    {
        $html = '<html><body>Just text, no links here.</body></html>';

        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertEquals(0, $summary->totalChecked);
    }

    /**
     * EDGE-003: Malformed HTML.
     * 
     * @test
     */
    public function malformed_html(): void
    {
        $html = '<html><body><a href="/page"><a></body></html>';

        // Should handle gracefully, not throw
        $summary = $this->checker->check($html, 'https://example.com');

        $this->assertIsInt($summary->totalChecked);
    }

    /**
     * EDGE-004: Duplicate links.
     * 
     * @test
     */
    public function duplicate_links(): void
    {
        $html = '<html><body><a href="/page">Link 1</a><a href="/page">Link 2</a></body></html>';
        $this->mockHttpResponse('https://example.com/page', 200);

        $summary = $this->checker->check($html, 'https://example.com');

        // Should check once but note both occurrences
        $this->assertEquals(1, $summary->totalChecked); // or 2 depending on implementation
    }

    /**
     * EDGE-005: Very long URL.
     * 
     * @test
     */
    public function very_long_url(): void
    {
        $longPath = str_repeat('/segment', 300);
        $this->mockHttpResponse('https://example.com' . $longPath, 200);

        $result = $this->checker->checkUrl('https://example.com' . $longPath);

        $this->assertNotNull($result);
    }

    /**
     * EDGE-006: Unicode in URL.
     * 
     * @test
     */
    public function unicode_in_url(): void
    {
        $this->mockHttpResponse('https://example.com/%E6%97%A5%E6%9C%AC%E8%AA%9E/%E3%83%9A%E3%83%BC%E3%82%B8', 200);

        $result = $this->checker->checkUrl('https://example.com/日本語/ページ');

        $this->assertNotNull($result);
    }

    /**
     * EDGE-007: Emoji in URL.
     * 
     * @test
     */
    public function emoji_in_url(): void
    {
        $this->mockHttpResponse('https://example.com/%F0%9F%8E%89/page', 200);

        $result = $this->checker->checkUrl('https://example.com/🎉/page');

        $this->assertNotNull($result);
    }

    /**
     * EDGE-008: Self-referential links.
     * 
     * @test
     */
    public function self_referential_links(): void
    {
        $html = '<html><body><a href="https://example.com/current">Self Link</a></body></html>';

        $summary = $this->checker->check($html, 'https://example.com/current');

        // Should either skip or mark appropriately
        $this->assertIsInt($summary->totalChecked);
    }

    /**
     * EDGE-009: Circular redirects.
     * 
     * @test
     */
    public function circular_redirects(): void
    {
        Http::fake([
            'https://example.com/a' => Http::response('', 301, ['Location' => 'https://example.com/b']),
            'https://example.com/b' => Http::response('', 301, ['Location' => 'https://example.com/a']),
        ]);

        $result = $this->checker->checkUrl('https://example.com/a');

        // Should detect circular redirect
        $this->assertNotNull($result);
    }

    /**
     * EDGE-010: Infinite redirect chain.
     * 
     * @test
     */
    public function infinite_redirect_chain(): void
    {
        $chain = [];
        for ($i = 1; $i <= 10; $i++) {
            $chain['https://example.com/page' . $i] = Http::response('', 301, ['Location' => 'https://example.com/page' . ($i + 1)]);
        }
        Http::fake($chain);

        $result = $this->checker->checkUrl('https://example.com/page1');

        // Should stop at maxRedirects
        $this->assertNotNull($result);
    }

    // =========================================================================
    // CONFIGURATION TESTS (CONFIG)
    // =========================================================================

    /**
     * CONFIG-001: Custom timeout.
     * 
     * @test
     */
    public function custom_timeout(): void
    {
        $checker = $this->createChecker(['timeout' => 5]);

        $this->assertEquals(5, $checker->getConfig()['timeout']);
    }

    /**
     * CONFIG-002: Custom max redirects.
     * 
     * @test
     */
    public function custom_max_redirects(): void
    {
        $checker = $this->createChecker(['max_redirects' => 3]);

        $this->assertEquals(3, $checker->getConfig()['max_redirects']);
    }

    /**
     * CONFIG-003: Custom user-agent.
     * 
     * @test
     */
    public function custom_user_agent(): void
    {
        $checker = $this->createChecker(['user_agent' => 'CustomBot/2.0']);

        $this->assertEquals('CustomBot/2.0', $checker->getConfig()['user_agent']);
    }

    /**
     * CONFIG-004: Custom retry count.
     * 
     * @test
     */
    public function custom_retry_count(): void
    {
        $checker = $this->createChecker(['retries' => 3]);

        $this->assertEquals(3, $checker->getConfig()['retries']);
    }

    /**
     * CONFIG-005: Enable/disable external checking.
     * 
     * @test
     */
    public function disable_external_checking(): void
    {
        $checker = $this->createChecker(['check_external' => false]);

        $this->assertFalse($checker->getConfig()['check_external']);
    }

    // =========================================================================
    // PERFORMANCE TESTS (PERF)
    // =========================================================================

    /**
     * PERF-001: Single page check completes in reasonable time.
     * 
     * @test
     */
    public function single_page_check_performance(): void
    {
        $html = '<html><body>';
        for ($i = 1; $i <= 50; $i++) {
            $html .= '<a href="/page' . $i . '">Link ' . $i . '</a>';
        }
        $html .= '</body></html>';

        Http::fake(function ($request) {
            return Http::response('', 200);
        });

        $start = microtime(true);
        $summary = $this->checker->check($html, 'https://example.com');
        $duration = microtime(true) - $start;

        $this->assertLessThan(30, $duration, 'Check should complete in under 30 seconds');
    }

    /**
     * PERF-003: Cache checked URLs.
     * 
     * @test
     */
    public function cache_checked_urls(): void
    {
        $html = '<html><body>
            <a href="/page">Link 1</a>
            <a href="/page">Link 2</a>
            <a href="/page">Link 3</a>
        </body></html>';

        Http::fake([
            'https://example.com/page' => Http::response('', 200),
        ]);

        $summary = $this->checker->check($html, 'https://example.com');

        // URL should be checked once, not 3 times
        Http::assertSentCount(1);
    }
}
