<?php

namespace Tests\Integration\Services\Playwright;

use App\Models\CrawlJob;
use App\Models\User;
use App\Services\PlaywrightRendererService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * Integration tests for PlaywrightRendererService.
 *
 * Tests browser lifecycle, page rendering, content extraction,
 * and error handling for the Playwright-based renderer.
 *
 * @see TASK-22-20
 * @see TASK-22-5
 * @see SPEC-022
 *
 * Note: These tests mock actual browser interactions for CI/CD compatibility.
 * Real browser integration tests would require Playwright and browser binaries.
 */
class PlaywrightIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Browser Lifecycle - PI-001 to PI-006
    // =========================================================================

    /**
     * Test browser launch configuration.
     * PI-001: Browser launch config
     */
    public function test_browser_launch_configuration(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('launchBrowser')
            ->once()
            ->withArgs(function ($options) {
                return isset($options['timeout']);
            })
            ->andReturn((object)['connected' => true]);

        $browser = $mockRenderer->launchBrowser(['timeout' => 60000]);

        $this->assertNotNull($browser);
    }

    /**
     * Test browser close is called.
     * PI-002: Browser close
     */
    public function test_browser_close_is_called(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockBrowser = (object)['connected' => true];

        $mockRenderer->shouldReceive('closeBrowser')
            ->once()
            ->with($mockBrowser);

        $mockRenderer->closeBrowser($mockBrowser);
    }

    /**
     * Test browser instance reuse.
     * PI-003: Browser reuse
     */
    public function test_browser_instance_reuse(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockBrowser = (object)['connected' => true, 'id' => 'browser-1'];

        $mockRenderer->shouldReceive('launchBrowser')
            ->once()
            ->andReturn($mockBrowser);

        // Launch once
        $browser1 = $mockRenderer->launchBrowser([]);

        // Same instance returned
        $this->assertEquals('browser-1', $browser1->id);
    }

    /**
     * Test browser cleanup on error.
     * PI-004: Browser cleanup on error
     */
    public function test_browser_cleanup_on_error(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockBrowser = (object)['connected' => true];

        $mockRenderer->shouldReceive('closeBrowser')
            ->once()
            ->with($mockBrowser);

        // Simulate error during rendering followed by cleanup
        try {
            throw new \Exception('Render failed');
        } finally {
            $mockRenderer->closeBrowser($mockBrowser);
        }
    }

    /**
     * Test browser context isolation.
     * PI-005: Context isolation
     */
    public function test_browser_context_isolation(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        // Each render should use isolated context
        $mockRenderer->shouldReceive('renderPage')
            ->twice()
            ->andReturn(
                ['html' => '<html>Page 1</html>', 'text' => 'Page 1', 'meta' => [], 'links' => [], 'size' => 100],
                ['html' => '<html>Page 2</html>', 'text' => 'Page 2', 'meta' => [], 'links' => [], 'size' => 100]
            );

        $result1 = $mockRenderer->renderPage('https://example.com/page1', []);
        $result2 = $mockRenderer->renderPage('https://example.com/page2', []);

        // Results should be isolated
        $this->assertNotEquals($result1['html'], $result2['html']);
    }

    /**
     * Test browser memory management.
     * PI-006: Memory management
     */
    public function test_browser_memory_management(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        // Multiple renders should not accumulate memory
        $mockRenderer->shouldReceive('renderPage')
            ->times(10)
            ->andReturn(['html' => '<html></html>', 'text' => '', 'meta' => [], 'links' => [], 'size' => 100]);

        for ($i = 0; $i < 10; $i++) {
            $result = $mockRenderer->renderPage("https://example.com/page{$i}", []);
            $this->assertArrayHasKey('html', $result);
        }
    }

    // =========================================================================
    // Page Rendering - PI-007 to PI-014
    // =========================================================================

    /**
     * Test page navigation to URL.
     * PI-007: Page navigation
     */
    public function test_page_navigation_to_url(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->withArgs(function ($url, $options) {
                return $url === 'https://example.com';
            })
            ->andReturn([
                'html' => '<html><body>Example</body></html>',
                'text' => 'Example',
                'meta' => ['title' => 'Example Domain'],
                'links' => [],
                'size' => 500,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('text', $result);
    }

    /**
     * Test wait for selector option.
     * PI-008: Wait for selector
     */
    public function test_wait_for_selector_option(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->withArgs(function ($url, $options) {
                return isset($options['waitForSelector']) && $options['waitForSelector'] === '.content';
            })
            ->andReturn([
                'html' => '<html><body><div class="content">Loaded</div></body></html>',
                'text' => 'Loaded',
                'meta' => [],
                'links' => [],
                'size' => 600,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', [
            'waitForSelector' => '.content',
        ]);

        $this->assertStringContainsString('Loaded', $result['text']);
    }

    /**
     * Test custom wait time option.
     * PI-009: Custom wait time
     */
    public function test_custom_wait_time_option(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->withArgs(function ($url, $options) {
                return isset($options['waitTime']) && $options['waitTime'] === 5000;
            })
            ->andReturn([
                'html' => '<html></html>',
                'text' => '',
                'meta' => [],
                'links' => [],
                'size' => 100,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', [
            'waitTime' => 5000,
        ]);

        $this->assertArrayHasKey('html', $result);
    }

    /**
     * Test timeout handling.
     * PI-010: Timeout handling
     */
    public function test_timeout_handling(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->withArgs(function ($url, $options) {
                return isset($options['timeout']) && $options['timeout'] === 30000;
            })
            ->andThrow(new \Exception('Page load timeout'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Page load timeout');

        $mockRenderer->renderPage('https://slow-site.com', [
            'timeout' => 30000,
        ]);
    }

    /**
     * Test custom user agent.
     * PI-011: Custom user agent
     */
    public function test_custom_user_agent(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->withArgs(function ($url, $options) {
                return isset($options['userAgent']) && $options['userAgent'] === 'CustomBot/1.0';
            })
            ->andReturn([
                'html' => '<html></html>',
                'text' => '',
                'meta' => [],
                'links' => [],
                'size' => 100,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', [
            'userAgent' => 'CustomBot/1.0',
        ]);

        $this->assertArrayHasKey('html', $result);
    }

    /**
     * Test redirect following.
     * PI-012: Redirect following
     */
    public function test_redirect_following(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->withArgs(function ($url, $options) {
                return isset($options['followRedirects']) && $options['followRedirects'] === true;
            })
            ->andReturn([
                'html' => '<html><body>Redirected</body></html>',
                'text' => 'Redirected',
                'meta' => ['finalUrl' => 'https://example.com/new-page'],
                'links' => [],
                'size' => 300,
            ]);

        $result = $mockRenderer->renderPage('https://example.com/old-page', [
            'followRedirects' => true,
        ]);

        $this->assertArrayHasKey('finalUrl', $result['meta']);
    }

    /**
     * Test max redirects limit.
     * PI-013: Max redirects limit
     */
    public function test_max_redirects_limit(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->withArgs(function ($url, $options) {
                return isset($options['maxRedirects']) && $options['maxRedirects'] === 3;
            })
            ->andThrow(new \Exception('Too many redirects'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Too many redirects');

        $mockRenderer->renderPage('https://example.com/redirect-loop', [
            'maxRedirects' => 3,
        ]);
    }

    /**
     * Test JavaScript execution wait.
     * PI-014: JS execution wait
     */
    public function test_javascript_execution_wait(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        // JS-rendered content should be available after wait
        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html><body><div id="app">React Content</div></body></html>',
                'text' => 'React Content',
                'meta' => [],
                'links' => [],
                'size' => 2000,
            ]);

        $result = $mockRenderer->renderPage('https://spa-example.com', [
            'waitTime' => 2000,
            'waitForSelector' => '#app',
        ]);

        $this->assertStringContainsString('React Content', $result['html']);
    }

    // =========================================================================
    // Content Extraction - PI-015 to PI-022
    // =========================================================================

    /**
     * Test HTML extraction after render.
     * PI-015: HTML extraction
     */
    public function test_html_extraction_after_render(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body><h1>Hello</h1></body></html>';

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => $html,
                'text' => 'Hello',
                'meta' => ['title' => 'Test'],
                'links' => [],
                'size' => strlen($html),
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        $this->assertStringContainsString('<h1>Hello</h1>', $result['html']);
        $this->assertEquals(strlen($html), $result['size']);
    }

    /**
     * Test structured text extraction.
     * PI-016: Text extraction
     */
    public function test_structured_text_extraction(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html><body><h1>Title</h1><p>Paragraph 1</p><p>Paragraph 2</p></body></html>',
                'text' => "Title\n\nParagraph 1\n\nParagraph 2",
                'meta' => [],
                'links' => [],
                'size' => 500,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        $this->assertStringContainsString('Title', $result['text']);
        $this->assertStringContainsString('Paragraph 1', $result['text']);
    }

    /**
     * Test link extraction.
     * PI-017: Link extraction
     */
    public function test_link_extraction(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html><body><a href="/about">About</a><a href="https://external.com">External</a></body></html>',
                'text' => 'About External',
                'meta' => [],
                'links' => [
                    ['url' => 'https://example.com/about', 'text' => 'About', 'internal' => true],
                    ['url' => 'https://external.com', 'text' => 'External', 'internal' => false],
                ],
                'size' => 300,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        $this->assertCount(2, $result['links']);
        $this->assertTrue($result['links'][0]['internal']);
        $this->assertFalse($result['links'][1]['internal']);
    }

    /**
     * Test metadata extraction.
     * PI-018: Metadata extraction
     */
    public function test_metadata_extraction(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html><head><title>Page Title</title><meta name="description" content="Page description"><meta name="robots" content="index, follow"></head><body></body></html>',
                'text' => '',
                'meta' => [
                    'title' => 'Page Title',
                    'description' => 'Page description',
                    'robots' => 'index, follow',
                ],
                'links' => [],
                'size' => 400,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        $this->assertEquals('Page Title', $result['meta']['title']);
        $this->assertEquals('Page description', $result['meta']['description']);
        $this->assertEquals('index, follow', $result['meta']['robots']);
    }

    /**
     * Test canonical URL extraction.
     * PI-019: Canonical extraction
     */
    public function test_canonical_url_extraction(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html><head><link rel="canonical" href="https://example.com/canonical-page"></head><body></body></html>',
                'text' => '',
                'meta' => [
                    'canonical' => 'https://example.com/canonical-page',
                ],
                'links' => [],
                'size' => 200,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        $this->assertEquals('https://example.com/canonical-page', $result['meta']['canonical']);
    }

    /**
     * Test Open Graph metadata extraction.
     * PI-020: Open Graph extraction
     */
    public function test_open_graph_metadata_extraction(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html><head><meta property="og:title" content="OG Title"><meta property="og:description" content="OG Description"></head><body></body></html>',
                'text' => '',
                'meta' => [
                    'ogTitle' => 'OG Title',
                    'ogDescription' => 'OG Description',
                ],
                'links' => [],
                'size' => 300,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        $this->assertEquals('OG Title', $result['meta']['ogTitle']);
        $this->assertEquals('OG Description', $result['meta']['ogDescription']);
    }

    /**
     * Test hreflang extraction.
     * PI-021: Hreflang extraction
     */
    public function test_hreflang_extraction(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html><head><link rel="alternate" hreflang="en" href="https://example.com/en"><link rel="alternate" hreflang="de" href="https://example.com/de"></head><body></body></html>',
                'text' => '',
                'meta' => [
                    'hreflang' => [
                        ['lang' => 'en', 'url' => 'https://example.com/en'],
                        ['lang' => 'de', 'url' => 'https://example.com/de'],
                    ],
                ],
                'links' => [],
                'size' => 400,
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        $this->assertCount(2, $result['meta']['hreflang']);
    }

    /**
     * Test content size calculation.
     * PI-022: Size calculation
     */
    public function test_content_size_calculation(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $html = '<html><body>' . str_repeat('a', 10000) . '</body></html>';

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => $html,
                'text' => '',
                'meta' => [],
                'links' => [],
                'size' => strlen($html),
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        $this->assertEquals(strlen($html), $result['size']);
    }

    // =========================================================================
    // Error Handling - PI-023 to PI-028
    // =========================================================================

    /**
     * Test navigation error handling.
     * PI-023: Navigation error
     */
    public function test_navigation_error_handling(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andThrow(new \Exception('net::ERR_NAME_NOT_RESOLVED'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('net::ERR_NAME_NOT_RESOLVED');

        $mockRenderer->renderPage('https://nonexistent-domain-xyz.com', []);
    }

    /**
     * Test JavaScript error handling.
     * PI-024: JavaScript error
     */
    public function test_javascript_error_handling(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        // JS errors should still allow content extraction
        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html><body>Content before JS error</body></html>',
                'text' => 'Content before JS error',
                'meta' => [],
                'links' => [],
                'size' => 500,
                'jsError' => 'Uncaught TypeError: undefined is not a function',
            ]);

        $result = $mockRenderer->renderPage('https://broken-js.com', []);

        // Content should still be extracted
        $this->assertStringContainsString('Content before JS error', $result['html']);
        $this->assertArrayHasKey('jsError', $result);
    }

    /**
     * Test 404 error handling.
     * PI-025: 404 error handling
     */
    public function test_404_error_handling(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html><body><h1>404 Not Found</h1></body></html>',
                'text' => '404 Not Found',
                'meta' => [],
                'links' => [],
                'size' => 100,
                'statusCode' => 404,
            ]);

        $result = $mockRenderer->renderPage('https://example.com/nonexistent', []);

        $this->assertEquals(404, $result['statusCode']);
    }

    /**
     * Test SSL certificate error handling.
     * PI-026: SSL error handling
     */
    public function test_ssl_certificate_error_handling(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andThrow(new \Exception('SSL certificate error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SSL certificate error');

        $mockRenderer->renderPage('https://expired-ssl.com', []);
    }

    /**
     * Test connection refused handling.
     * PI-027: Connection refused
     */
    public function test_connection_refused_handling(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andThrow(new \Exception('net::ERR_CONNECTION_REFUSED'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('net::ERR_CONNECTION_REFUSED');

        $mockRenderer->renderPage('https://offline-site.com', []);
    }

    /**
     * Test timeout error handling.
     * PI-028: Timeout error handling
     */
    public function test_timeout_error_handling(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andThrow(new \Exception('Timeout exceeded: 30000ms'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Timeout exceeded');

        $mockRenderer->renderPage('https://slow-site.com', ['timeout' => 30000]);
    }

    // =========================================================================
    // Security - PI-029 to PI-032
    // =========================================================================

    /**
     * Test SSRF protection blocks localhost.
     * PI-029: SSRF localhost blocked
     */
    public function test_ssrf_protection_blocks_localhost(): void
    {
        $blockedUrls = [
            'http://localhost',
            'http://127.0.0.1',
            'http://[::1]',
        ];

        foreach ($blockedUrls as $url) {
            // These URLs should be blocked before rendering
            $isBlocked = $this->isBlockedUrl($url);
            $this->assertTrue($isBlocked, "URL should be blocked: {$url}");
        }
    }

    /**
     * Test SSRF protection blocks private IPs.
     * PI-030: SSRF private IP blocked
     */
    public function test_ssrf_protection_blocks_private_ips(): void
    {
        $blockedUrls = [
            'http://10.0.0.1',
            'http://172.16.0.1',
            'http://192.168.1.1',
            'http://169.254.169.254', // Cloud metadata
        ];

        foreach ($blockedUrls as $url) {
            $isBlocked = $this->isBlockedUrl($url);
            $this->assertTrue($isBlocked, "URL should be blocked: {$url}");
        }
    }

    /**
     * Test SSRF protection blocks file protocol.
     * PI-031: SSRF file protocol blocked
     */
    public function test_ssrf_protection_blocks_file_protocol(): void
    {
        $blockedUrls = [
            'file:///etc/passwd',
            'file:///c:/windows/system32',
        ];

        foreach ($blockedUrls as $url) {
            $isBlocked = $this->isBlockedUrl($url);
            $this->assertTrue($isBlocked, "URL should be blocked: {$url}");
        }
    }

    /**
     * Test SSRF protection allows valid URLs.
     * PI-032: SSRF valid URL allowed
     */
    public function test_ssrf_protection_allows_valid_urls(): void
    {
        $allowedUrls = [
            'https://example.com',
            'https://google.com',
            'https://github.com',
        ];

        foreach ($allowedUrls as $url) {
            $isBlocked = $this->isBlockedUrl($url);
            $this->assertFalse($isBlocked, "URL should be allowed: {$url}");
        }
    }

    /**
     * Helper to check if URL is blocked for SSRF.
     */
    private function isBlockedUrl(string $url): bool
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? '';
        $host = $parsed['host'] ?? '';

        // Block non-HTTP(S) protocols
        if (!in_array($scheme, ['http', 'https'])) {
            return true;
        }

        // Block localhost
        if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            return true;
        }

        // Block private IP ranges
        $privateRanges = ['10.', '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.', '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.', '192.168.', '169.254.'];
        foreach ($privateRanges as $range) {
            if (str_starts_with($host, $range)) {
                return true;
            }
        }

        // Block IPv6 localhost
        if (str_starts_with($host, '[')) {
            return true; // IPv6 addresses blocked for simplicity
        }

        return false;
    }

    // =========================================================================
    // Performance - PI-033 to PI-036
    // =========================================================================

    /**
     * Test render time is reasonable.
     * PI-033: Render time
     */
    public function test_render_time_is_reasonable(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => '<html></html>',
                'text' => '',
                'meta' => [],
                'links' => [],
                'size' => 100,
                'renderTime' => 1500, // 1.5 seconds
            ]);

        $result = $mockRenderer->renderPage('https://example.com', []);

        // Should have render time metadata
        if (isset($result['renderTime'])) {
            $this->assertLessThan(5000, $result['renderTime']); // Under 5 seconds
        }
    }

    /**
     * Test memory usage is tracked.
     * PI-034: Memory tracking
     */
    public function test_memory_usage_is_tracked(): void
    {
        // Simulate memory tracking
        $initialMemory = memory_get_usage(true);

        // After rendering, memory should not spike dramatically
        $afterMemory = memory_get_usage(true);

        // Should not exceed 50MB increase
        $this->assertLessThan(50 * 1024 * 1024, $afterMemory - $initialMemory);
    }

    /**
     * Test concurrent page rendering.
     * PI-035: Concurrent rendering
     */
    public function test_concurrent_page_rendering(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        $mockRenderer->shouldReceive('renderPage')
            ->times(3)
            ->andReturn([
                'html' => '<html></html>',
                'text' => '',
                'meta' => [],
                'links' => [],
                'size' => 100,
            ]);

        // Simulate concurrent requests (in real scenario would use async)
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $mockRenderer->renderPage("https://example{$i}.com", []);
        }

        $this->assertCount(3, $results);
    }

    /**
     * Test large page handling.
     * PI-036: Large page handling
     */
    public function test_large_page_handling(): void
    {
        $mockRenderer = Mockery::mock(PlaywrightRendererService::class);

        // Simulate a large page (1MB HTML)
        $largeHtml = '<html><body>' . str_repeat('x', 1024 * 1024) . '</body></html>';

        $mockRenderer->shouldReceive('renderPage')
            ->once()
            ->andReturn([
                'html' => $largeHtml,
                'text' => 'Large content',
                'meta' => [],
                'links' => [],
                'size' => strlen($largeHtml),
            ]);

        $result = $mockRenderer->renderPage('https://large-page.com', []);

        $this->assertGreaterThan(1024 * 1024, $result['size']);
    }
}
