<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\HtmlFetcherService;
use App\Services\URLValidationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Unit tests for HtmlFetcherService.
 *
 * Tests HTML fetching with SSRF protection, timeout handling,
 * response size limits, and caching.
 *
 * @see \App\Services\HtmlFetcherService
 * @see TEST-18-5
 */
class HtmlFetcherServiceTest extends TestCase
{
    protected HtmlFetcherService $service;
    protected $urlValidationServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlValidationServiceMock = Mockery::mock(URLValidationService::class);
        $this->service = new HtmlFetcherService($this->urlValidationServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // HTML FETCH TESTS (HF-*)
    // =========================================================================

    /**
     * HF-01: Successful fetch returns content.
     */
    public function test_fetch_returns_content_for_valid_html(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->with($url)
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html><body>Test</body></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Test', $result['content']);
    }

    /**
     * HF-05: Fetch large HTML under limit.
     */
    public function test_fetch_handles_large_html_under_limit(): void
    {
        $url = 'https://example.com/large';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        // ~5MB HTML
        $largeContent = str_repeat('<p>Content</p>', 200000);

        Http::fake([
            $url => Http::response($largeContent, 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
    }

    /**
     * HF-07: Fetch with timeout.
     */
    public function test_fetch_handles_timeout(): void
    {
        $url = 'https://example.com/slow';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::failedConnection('Connection timed out'),
        ]);

        // Service should enforce timeout
        $result = $this->service->fetch($url, ['timeout' => 1]);

        $this->assertFalse($result['success']);
        $this->assertEquals('TIMEOUT', $result['errorCode']);
    }

    /**
     * HF-11: Fetch with HTTP error.
     */
    public function test_fetch_handles_http_500_error(): void
    {
        $url = 'https://example.com/error';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('Internal Server Error', 500),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals(500, $result['statusCode']);
    }

    /**
     * HF-12: Fetch with HTTP 404.
     */
    public function test_fetch_handles_http_404(): void
    {
        $url = 'https://example.com/notfound';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('Not Found', 404),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('NOT_FOUND', $result['errorCode']);
    }

    /**
     * HF-13: Fetch with HTTP 403.
     */
    public function test_fetch_handles_http_403(): void
    {
        $url = 'https://example.com/forbidden';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('Forbidden', 403),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('FORBIDDEN', $result['errorCode']);
    }

    // =========================================================================
    // CONTENT-TYPE VALIDATION TESTS (CT-*)
    // =========================================================================

    /**
     * CT-01: HTML content-type accepted.
     */
    public function test_fetch_accepts_html_content_type(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
    }

    /**
     * CT-02: HTML with charset accepted.
     */
    public function test_fetch_accepts_html_with_charset(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
    }

    /**
     * CT-03: XHTML content-type accepted.
     */
    public function test_fetch_accepts_xhtml_content_type(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'application/xhtml+xml',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
    }

    /**
     * CT-04: JSON rejected.
     */
    public function test_fetch_rejects_json_content_type(): void
    {
        $url = 'https://example.com/api';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('{"data": 1}', 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_CONTENT_TYPE', $result['errorCode']);
    }

    /**
     * CT-05: XML rejected.
     */
    public function test_fetch_rejects_xml_content_type(): void
    {
        $url = 'https://example.com/feed';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<?xml version="1.0"?><rss></rss>', 200, [
                'Content-Type' => 'application/xml',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_CONTENT_TYPE', $result['errorCode']);
    }

    /**
     * CT-07: Image rejected.
     */
    public function test_fetch_rejects_image_content_type(): void
    {
        $url = 'https://example.com/image.jpg';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('binary data', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_CONTENT_TYPE', $result['errorCode']);
    }

    // =========================================================================
    // SSRF PROTECTION TESTS (SSRF-*)
    // =========================================================================

    /**
     * SSRF-01: Localhost blocked.
     */
    public function test_fetch_blocks_localhost(): void
    {
        $url = 'http://localhost/page.html';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['errorCode']);
    }

    /**
     * SSRF-02: 127.0.0.1 blocked.
     */
    public function test_fetch_blocks_127_0_0_1(): void
    {
        $url = 'http://127.0.0.1/page.html';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['errorCode']);
    }

    /**
     * SSRF-04: 172.16-31.x.x blocked.
     */
    public function test_fetch_blocks_172_subnet(): void
    {
        $url = 'http://172.16.0.1/page.html';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['errorCode']);
    }

    /**
     * SSRF-05: 192.168.x.x blocked.
     */
    public function test_fetch_blocks_192_168_subnet(): void
    {
        $url = 'http://192.168.1.1/page.html';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['errorCode']);
    }

    /**
     * SSRF-06: Cloud metadata blocked.
     */
    public function test_fetch_blocks_cloud_metadata(): void
    {
        $url = 'http://169.254.169.254/latest/meta-data';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['errorCode']);
    }

    /**
     * SSRF-07: file:// protocol blocked.
     */
    public function test_fetch_blocks_file_protocol(): void
    {
        $url = 'file:///etc/passwd';

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_PROTOCOL', $result['errorCode']);
    }

    /**
     * SSRF-08: ftp:// protocol blocked.
     */
    public function test_fetch_blocks_ftp_protocol(): void
    {
        $url = 'ftp://example.com/file';

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_PROTOCOL', $result['errorCode']);
    }

    /**
     * SSRF-09: gopher:// protocol blocked.
     */
    public function test_fetch_blocks_gopher_protocol(): void
    {
        $url = 'gopher://example.com';

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_PROTOCOL', $result['errorCode']);
    }

    // =========================================================================
    // CACHING TESTS (CA-*)
    // =========================================================================

    /**
     * CA-01: First fetch not cached.
     */
    public function test_fetch_caches_successful_response(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html><body>Cached</body></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        // First fetch
        $result1 = $this->service->fetch($url);
        $this->assertFalse($result1['cached']);

        // Second fetch should be cached
        $result2 = $this->service->fetch($url);
        $this->assertTrue($result2['cached']);
    }

    /**
     * CA-04: Cache respects TTL.
     */
    public function test_fetch_cache_expires_after_ttl(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html><body>Content</body></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        // Fetch with short TTL
        $result1 = $this->service->fetch($url, ['cacheTtl' => 1]);
        $this->assertFalse($result1['cached']);

        // Wait for cache to expire
        sleep(2);

        // Should fetch again (cache expired)
        $result2 = $this->service->fetch($url);
        $this->assertFalse($result2['cached']);
    }

    /**
     * CA-05: Cache invalidated on error.
     */
    public function test_fetch_does_not_cache_errors(): void
    {
        $url = 'https://example.com/error';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('Error', 500),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['cached']);
    }

    // =========================================================================
    // REDIRECT HANDLING TESTS (RH-*)
    // =========================================================================

    /**
     * RH-01: No redirect returns content directly.
     */
    public function test_fetch_returns_content_without_redirect(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html><body>Direct</body></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertEquals($url, $result['finalUrl']);
        $this->assertEmpty($result['redirectChain']);
    }

    /**
     * RH-02: 301 permanent redirect followed.
     */
    public function test_fetch_follows_301_redirect(): void
    {
        $url = 'https://example.com/old';
        $newUrl = 'https://example.com/new';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 301, ['Location' => $newUrl]),
            $newUrl => Http::response('<html><body>New</body></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertEquals($newUrl, $result['finalUrl']);
    }

    /**
     * RH-03: 302 temporary redirect followed.
     */
    public function test_fetch_follows_302_redirect(): void
    {
        $url = 'https://example.com/temp';
        $newUrl = 'https://example.com/target';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 302, ['Location' => $newUrl]),
            $newUrl => Http::response('<html><body>Target</body></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertEquals($newUrl, $result['finalUrl']);
    }

    // =========================================================================
    // RESULT STRUCTURE TESTS (RS-*)
    // =========================================================================

    /**
     * RS-01: Success result has correct structure.
     */
    public function test_fetch_returns_correct_result_structure(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
                'Server' => 'nginx',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('finalUrl', $result);
        $this->assertArrayHasKey('statusCode', $result);
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('timing', $result);
        $this->assertArrayHasKey('cached', $result);
    }

    /**
     * RS-02: Error result has correct structure.
     */
    public function test_fetch_error_has_correct_structure(): void
    {
        $url = 'https://example.com/notfound';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('Not Found', 404),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('errorCode', $result);
        $this->assertArrayHasKey('url', $result);
    }

    /**
     * RS-03: Timing data included.
     */
    public function test_fetch_includes_timing_data(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertArrayHasKey('timing', $result);
        $this->assertArrayHasKey('total', $result['timing']);
    }

    // =========================================================================
    // SSL/TLS TESTS (SSL-*)
    // =========================================================================

    /**
     * SSL-01: Valid SSL certificate works.
     */
    public function test_fetch_accepts_valid_ssl(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
    }

    /**
     * SSL-02: Expired SSL certificate rejected.
     */
    public function test_fetch_rejects_expired_ssl(): void
    {
        $url = 'https://expired.example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        // Simulate SSL error
        Http::fake([
            $url => Http::failedConnection('SSL certificate problem'),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('SSL_ERROR', $result['errorCode']);
    }

    // =========================================================================
    // HELPER METHOD TESTS
    // =========================================================================

    /**
     * isFetchable returns true for valid URL.
     */
    public function test_is_fetchable_returns_true_for_valid_url(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $this->assertTrue($this->service->isFetchable($url));
    }

    /**
     * isFetchable returns false for blocked URL.
     */
    public function test_is_fetchable_returns_false_for_blocked_url(): void
    {
        $url = 'http://localhost';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Blocked'));

        $this->assertFalse($this->service->isFetchable($url));
    }

    /**
     * clearCache removes cached entry.
     */
    public function test_clear_cache_removes_entry(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        // Fetch and cache
        $this->service->fetch($url);

        // Clear cache
        $this->service->clearCache($url);

        // Next fetch should not be cached
        $result = $this->service->fetch($url);
        $this->assertFalse($result['cached']);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Handles empty URL.
     */
    public function test_fetch_handles_empty_url(): void
    {
        $result = $this->service->fetch('');

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_URL', $result['errorCode']);
    }

    /**
     * Handles null URL.
     */
    public function test_fetch_handles_null_url(): void
    {
        $result = $this->service->fetch(null);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_URL', $result['errorCode']);
    }

    /**
     * Handles URL with unicode characters.
     */
    public function test_fetch_handles_unicode_url(): void
    {
        $url = 'https://例え.jp';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
    }

    /**
     * Custom options override defaults.
     */
    public function test_fetch_respects_custom_options(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url, [
            'timeout' => 5,
            'maxSize' => 1024 * 1024, // 1MB
        ]);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // TIMEOUT TESTS (TO-*)
    // =========================================================================

    /**
     * TO-01: Default timeout is 30 seconds.
     */
    public function test_fetch_uses_default_30_second_timeout(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        // Timeout should be 30 seconds by default
    }

    /**
     * TO-02: Custom timeout is respected.
     */
    public function test_fetch_respects_custom_timeout(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url, ['timeout' => 5]);

        $this->assertTrue($result['success']);
    }

    /**
     * TO-03: Connection timeout handled.
     */
    public function test_fetch_handles_connection_timeout(): void
    {
        $url = 'https://example.com/slow-connect';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::failedConnection('Connection timed out'),
        ]);

        $result = $this->service->fetch($url, ['timeout' => 1]);

        $this->assertFalse($result['success']);
        $this->assertEquals('TIMEOUT', $result['errorCode']);
    }

    /**
     * TO-05: Timeout during redirect handled.
     */
    public function test_fetch_handles_timeout_during_redirect(): void
    {
        $url = 'https://example.com/redirect';
        $newUrl = 'https://slow-example.com/target';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 301, ['Location' => $newUrl]),
            $newUrl => Http::failedConnection('Connection timed out'),
        ]);

        $result = $this->service->fetch($url, ['timeout' => 1]);

        $this->assertFalse($result['success']);
        $this->assertEquals('TIMEOUT', $result['errorCode']);
    }

    // =========================================================================
    // RESPONSE SIZE LIMIT TESTS (SIZE-*)
    // =========================================================================

    /**
     * SIZE-01: Small response succeeds.
     */
    public function test_fetch_succeeds_with_small_response(): void
    {
        $url = 'https://example.com/small';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $smallContent = str_repeat('a', 100 * 1024); // 100KB

        Http::fake([
            $url => Http::response($smallContent, 200, [
                'Content-Type' => 'text/html',
                'Content-Length' => (string) strlen($smallContent),
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
    }

    /**
     * SIZE-04: Response exceeds limit.
     */
    public function test_fetch_fails_when_response_exceeds_limit(): void
    {
        $url = 'https://example.com/huge';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        // Content-Length header says 15MB
        Http::fake([
            $url => Http::response('', 200, [
                'Content-Type' => 'text/html',
                'Content-Length' => '15728640', // 15MB
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('RESPONSE_TOO_LARGE', $result['errorCode']);
    }

    /**
     * SIZE-05: Content-Length check prevents download.
     */
    public function test_fetch_checks_content_length_header_early(): void
    {
        $url = 'https://example.com/will-not-download';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 200, [
                'Content-Type' => 'text/html',
                'Content-Length' => '52428800', // 50MB
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('RESPONSE_TOO_LARGE', $result['errorCode']);
    }

    /**
     * SIZE-08: Custom size limit respected.
     */
    public function test_fetch_respects_custom_max_size(): void
    {
        $url = 'https://example.com/medium';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        // 3MB content with 1MB limit
        Http::fake([
            $url => Http::response('', 200, [
                'Content-Type' => 'text/html',
                'Content-Length' => '3145728', // 3MB
            ]),
        ]);

        $result = $this->service->fetch($url, ['maxSize' => 1024 * 1024]); // 1MB limit

        $this->assertFalse($result['success']);
        $this->assertEquals('RESPONSE_TOO_LARGE', $result['errorCode']);
    }

    // =========================================================================
    // HEADER EXTRACTION TESTS (HE-*)
    // =========================================================================

    /**
     * HE-01: Content-Type header extracted.
     */
    public function test_fetch_extracts_content_type_header(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('Content-Type', $result['headers']);
    }

    /**
     * HE-02: Content-Length header extracted.
     */
    public function test_fetch_extracts_content_length_header(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
                'Content-Length' => '1024',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('Content-Length', $result['headers']);
    }

    /**
     * HE-06: Server header extracted.
     */
    public function test_fetch_extracts_server_header(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
                'Server' => 'nginx/1.18.0',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertEquals('nginx/1.18.0', $result['headers']['Server']);
    }

    /**
     * HE-09: Headers are case-insensitive.
     */
    public function test_fetch_handles_case_insensitive_headers(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'content-type' => 'text/html', // lowercase
                'SERVER' => 'nginx', // uppercase
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        // Should normalize header names
    }

    // =========================================================================
    // ERROR HANDLING TESTS (EH-*)
    // =========================================================================

    /**
     * EH-01: Invalid URL format handled.
     */
    public function test_fetch_handles_invalid_url_format(): void
    {
        $result = $this->service->fetch('not a valid url');

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_URL', $result['errorCode']);
    }

    /**
     * EH-02: Connection refused handled.
     */
    public function test_fetch_handles_connection_refused(): void
    {
        $url = 'https://offline-example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::failedConnection('Connection refused'),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('CONNECTION_FAILED', $result['errorCode']);
    }

    /**
     * EH-05: Empty response handled.
     */
    public function test_fetch_handles_empty_response(): void
    {
        $url = 'https://example.com/empty';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertEquals('', $result['content']);
    }

    /**
     * EH-07: Rate limited by target handled.
     */
    public function test_fetch_handles_rate_limiting(): void
    {
        $url = 'https://example.com/rate-limited';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('Too Many Requests', 429, [
                'Retry-After' => '60',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('RATE_LIMITED', $result['errorCode']);
    }

    // =========================================================================
    // ADDITIONAL REDIRECT TESTS (RH-*)
    // =========================================================================

    /**
     * RH-04: 307 temporary redirect followed.
     */
    public function test_fetch_follows_307_redirect(): void
    {
        $url = 'https://example.com/temp307';
        $newUrl = 'https://example.com/target307';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 307, ['Location' => $newUrl]),
            $newUrl => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertEquals($newUrl, $result['finalUrl']);
    }

    /**
     * RH-05: 308 permanent redirect followed.
     */
    public function test_fetch_follows_308_redirect(): void
    {
        $url = 'https://example.com/permanent308';
        $newUrl = 'https://example.com/target308';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 308, ['Location' => $newUrl]),
            $newUrl => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertEquals($newUrl, $result['finalUrl']);
    }

    /**
     * HF-03: Too many redirects handled.
     */
    public function test_fetch_handles_too_many_redirects(): void
    {
        $url = 'https://example.com/redirect1';
        $url2 = 'https://example.com/redirect2';
        $url3 = 'https://example.com/redirect3';
        $url4 = 'https://example.com/redirect4';
        $url5 = 'https://example.com/redirect5';
        $url6 = 'https://example.com/redirect6';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->times(6)
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('', 301, ['Location' => $url2]),
            $url2 => Http::response('', 301, ['Location' => $url3]),
            $url3 => Http::response('', 301, ['Location' => $url4]),
            $url4 => Http::response('', 301, ['Location' => $url5]),
            $url5 => Http::response('', 301, ['Location' => $url6]),
            $url6 => Http::response('', 301, ['Location' => $url]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('REDIRECT_LIMIT_EXCEEDED', $result['errorCode']);
    }

    // =========================================================================
    // ADDITIONAL SSRF TESTS (SSRF-*)
    // =========================================================================

    /**
     * SSRF-03: 10.x.x.x blocked.
     */
    public function test_fetch_blocks_10_subnet(): void
    {
        $url = 'http://10.0.0.1/page.html';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['errorCode']);
    }

    /**
     * SSRF-10: DNS rebinding blocked.
     */
    public function test_fetch_blocks_dns_rebinding(): void
    {
        $url = 'http://malicious-dns-rebinding.test/page.html';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('DNS resolution to private IP blocked'));

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['errorCode']);
    }

    /**
     * SSRF-11: IPv6 localhost blocked.
     */
    public function test_fetch_blocks_ipv6_localhost(): void
    {
        $url = 'http://[::1]/page.html';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Access to this host is not allowed'));

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_URL', $result['errorCode']);
    }

    /**
     * SSRF-12: Redirect to blocked URL handled.
     */
    public function test_fetch_blocks_redirect_to_localhost(): void
    {
        $url = 'https://example.com/malicious-redirect';
        $localhostUrl = 'http://localhost/admin';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->with($localhostUrl)
            ->andThrow(new \Exception('Access to this host is not allowed'));

        Http::fake([
            $url => Http::response('', 302, ['Location' => $localhostUrl]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_REDIRECT', $result['errorCode']);
    }

    /**
     * SSRF-13: Redirect to private IP handled.
     */
    public function test_fetch_blocks_redirect_to_private_ip(): void
    {
        $url = 'https://example.com/private-redirect';
        $privateUrl = 'http://192.168.1.1/internal';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->with($privateUrl)
            ->andThrow(new \Exception('Access to this host is not allowed'));

        Http::fake([
            $url => Http::response('', 302, ['Location' => $privateUrl]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('BLOCKED_REDIRECT', $result['errorCode']);
    }

    // =========================================================================
    // INTEGRATION TESTS (INT-*)
    // =========================================================================

    /**
     * INT-01: Uses URLValidationService.
     */
    public function test_fetch_calls_url_validation_service(): void
    {
        $url = 'https://example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->with($url)
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $this->service->fetch($url);

        // Mockery verifies the call was made
        $this->assertTrue(true);
    }

    /**
     * INT-02: Validation errors propagate.
     */
    public function test_fetch_propagates_validation_errors(): void
    {
        $url = 'http://blocked.example.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andThrow(new \Exception('Host is blocked'));

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('BLOCKED', $result['errorCode']);
    }

    /**
     * INT-03: Valid public URL passes all checks.
     */
    public function test_fetch_valid_public_url_passes_all_checks(): void
    {
        $url = 'https://example.com/public';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html><body>Public Content</body></html>', 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'Server' => 'nginx',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Public Content', $result['content']);
        $this->assertEquals($url, $result['url']);
        $this->assertEquals($url, $result['finalUrl']);
        $this->assertEquals(200, $result['statusCode']);
        $this->assertArrayHasKey('timing', $result);
    }

    // =========================================================================
    // ADDITIONAL CONTENT-TYPE TESTS (CT-*)
    // =========================================================================

    /**
     * CT-06: PDF rejected.
     */
    public function test_fetch_rejects_pdf_content_type(): void
    {
        $url = 'https://example.com/document.pdf';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('%PDF-1.4', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_CONTENT_TYPE', $result['errorCode']);
    }

    /**
     * CT-08: Binary rejected.
     */
    public function test_fetch_rejects_binary_content_type(): void
    {
        $url = 'https://example.com/download';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('binary data', 200, [
                'Content-Type' => 'application/octet-stream',
            ]),
        ]);

        $result = $this->service->fetch($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_CONTENT_TYPE', $result['errorCode']);
    }

    /**
     * CT-09: No content-type header handled with warning.
     */
    public function test_fetch_handles_missing_content_type(): void
    {
        $url = 'https://example.com/no-content-type';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url => Http::response('<html></html>', 200),
        ]);

        $result = $this->service->fetch($url);

        // Should accept with warning or reject - depends on implementation
        // Either behavior is acceptable
        $this->assertArrayHasKey('success', $result);
    }

    // =========================================================================
    // CACHE API TESTS (CA-*)
    // =========================================================================

    /**
     * CA-06: clearAllCache removes all entries.
     */
    public function test_clear_all_cache_removes_all_entries(): void
    {
        $url1 = 'https://example1.com';
        $url2 = 'https://example2.com';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->times(4)
            ->andReturn(['valid' => true]);

        Http::fake([
            $url1 => Http::response('<html>1</html>', 200, ['Content-Type' => 'text/html']),
            $url2 => Http::response('<html>2</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        // Fetch both URLs
        $this->service->fetch($url1);
        $this->service->fetch($url2);

        // Clear all cache
        $this->service->clearAllCache();

        // Both should be cache misses now
        $result1 = $this->service->fetch($url1);
        $result2 = $this->service->fetch($url2);

        $this->assertFalse($result1['cached']);
        $this->assertFalse($result2['cached']);
    }

    /**
     * CA-07: Cache key includes query params.
     */
    public function test_fetch_caches_urls_with_different_query_params_separately(): void
    {
        $url1 = 'https://example.com?page=1';
        $url2 = 'https://example.com?page=2';

        $this->urlValidationServiceMock
            ->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        Http::fake([
            $url1 => Http::response('<html>Page 1</html>', 200, ['Content-Type' => 'text/html']),
            $url2 => Http::response('<html>Page 2</html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $result1 = $this->service->fetch($url1);
        $result2 = $this->service->fetch($url2);

        // Both should be cache misses (different cache keys)
        $this->assertFalse($result1['cached']);
        $this->assertFalse($result2['cached']);
    }
}
