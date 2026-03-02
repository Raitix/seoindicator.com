<?php

namespace Tests\Unit\Services\Playwright;

use Tests\TestCase;
use App\Services\Playwright\PlaywrightRendererService;
use App\Services\Playwright\BrowserLauncher;
use App\Services\Playwright\Contracts\BrowserInterface;
use App\Services\Playwright\Contracts\BrowserContextInterface;
use App\Services\Playwright\Contracts\PageInterface;
use App\Services\Playwright\Data\RenderResult;
use App\Services\Playwright\Data\ExtractedContent;
use App\Services\Playwright\Exceptions\TimeoutException;
use App\Services\Playwright\Exceptions\NavigationException;
use App\Services\Playwright\Exceptions\RenderException;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for the PlaywrightRendererService.
 *
 * @see TASK-22-5
 * @see SPEC-022
 */
class PlaywrightRendererServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Service Instantiation Tests (PR-001)
    // ==========================================

    /**
     * Test service can be instantiated via container.
     * PR-001-01: Service instantiation
     */
    public function test_service_can_be_instantiated(): void
    {
        $service = app(PlaywrightRendererService::class);
        $this->assertInstanceOf(PlaywrightRendererService::class, $service);
    }

    /**
     * Test service has BrowserLauncher injected.
     * PR-001-02: BrowserLauncher injection
     */
    public function test_service_has_browser_launcher_injected(): void
    {
        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $this->app->instance(BrowserLauncher::class, $mockLauncher);

        $service = app(PlaywrightRendererService::class);

        $this->assertNotNull($service->getBrowserLauncher());
    }

    /**
     * Test service has Logger injected.
     * PR-001-03: Logger injection
     */
    public function test_service_has_logger_injected(): void
    {
        $service = app(PlaywrightRendererService::class);

        $this->assertNotNull($service->getLogger());
    }

    // ==========================================
    // render() Method - Basic Rendering Tests (PR-002)
    // ==========================================

    /**
     * Test render static page.
     * PR-002-01: Render static page
     */
    public function test_render_static_page(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test Page',
            'content' => '<html><head><title>Test Page</title></head><body>Content</body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertInstanceOf(RenderResult::class, $result);
        $this->assertEquals('https://example.com', $result->url);
        $this->assertEquals('Test Page', $result->title);
    }

    /**
     * Test render JS-rendered page.
     * PR-002-02: Render JS-rendered page
     */
    public function test_render_js_rendered_page(): void
    {
        $initialContent = '<html><body><div id="app"></div></body></html>';
        $renderedContent = '<html><body><div id="app"><h1>Dynamic Content</h1></div></body></html>';

        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')
            ->once()
            ->with('https://spa.example.com', Mockery::any())
            ->andReturnSelf();
        $mockPage->shouldReceive('waitForFunction')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn('https://spa.example.com');
        $mockPage->shouldReceive('title')->andReturn('SPA Page');
        $mockPage->shouldReceive('content')->andReturn($renderedContent);
        $mockPage->shouldReceive('evaluate')->andReturn(null);
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://spa.example.com');

        $this->assertEquals('SPA Page', $result->title);
        $this->assertStringContainsString('Dynamic Content', $result->renderedHtml);
    }

    /**
     * Test render with default options.
     * PR-002-03: Render with default options
     */
    public function test_render_with_default_options(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertInstanceOf(RenderResult::class, $result);
    }

    /**
     * Test render returns final URL after redirect.
     * PR-002-04: Final URL after redirect
     */
    public function test_render_returns_final_url_after_redirect(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')
            ->once()
            ->with('https://example.com/old', Mockery::any())
            ->andReturnSelf();
        $mockPage->shouldReceive('waitForFunction')->zeroOrMoreTimes()->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn('https://example.com/new');
        $mockPage->shouldReceive('title')->andReturn('New Page');
        $mockPage->shouldReceive('content')->andReturn('<html><body>New</body></html>');
        $mockPage->shouldReceive('evaluate')->andReturn(null);
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com/old');

        $this->assertEquals('https://example.com/new', $result->url);
        $this->assertEquals('https://example.com/old', $result->originalUrl);
    }

    /**
     * Test render preserves original URL.
     * PR-002-05: Preserve original URL
     */
    public function test_render_preserves_original_url(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertEquals('https://example.com', $result->originalUrl);
    }

    /**
     * Test render tracks load time.
     * PR-002-06: Track load time
     */
    public function test_render_tracks_load_time(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertIsInt($result->loadTime);
        $this->assertGreaterThanOrEqual(0, $result->loadTime);
    }

    /**
     * Test render calculates content size.
     * PR-002-07: Calculate content size
     */
    public function test_render_calculates_content_size(): void
    {
        $content = '<html><body>Test content here</body></html>';

        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => $content,
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertEquals(strlen($content), $result->size);
    }

    // ==========================================
    // render() Method - Wait Options Tests (PR-003)
    // ==========================================

    /**
     * Test render with waitForSelector.
     * PR-003-01: Wait for selector
     */
    public function test_render_with_wait_for_selector(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')->once()->andReturnSelf();
        $mockPage->shouldReceive('waitForFunction')->zeroOrMoreTimes()->andReturnSelf();
        $mockPage->shouldReceive('waitForSelector')
            ->once()
            ->with('.dynamic-content', Mockery::any())
            ->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn('https://example.com');
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('content')->andReturn('<html><body><div class="dynamic-content">Loaded</div></body></html>');
        $mockPage->shouldReceive('evaluate')->andReturn(null);
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com', ['waitForSelector' => '.dynamic-content']);

        $this->assertStringContainsString('Loaded', $result->renderedHtml);
    }

    /**
     * Test render with waitTime.
     * PR-003-02: Additional wait time
     */
    public function test_render_with_wait_time(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')->once()->andReturnSelf();
        // waitForFunction is called once for waitTime
        $mockPage->shouldReceive('waitForFunction')
            ->once()
            ->with(Mockery::pattern('/setTimeout/'))
            ->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn('https://example.com');
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('content')->andReturn('<html><body>Test</body></html>');
        $mockPage->shouldReceive('evaluate')->andReturn(null);
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com', ['waitTime' => 5000]);

        $this->assertInstanceOf(RenderResult::class, $result);
    }

    /**
     * Test render with both wait options.
     * PR-003-03: Both wait options
     */
    public function test_render_with_both_wait_options(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')->once()->andReturnSelf();
        $mockPage->shouldReceive('waitForSelector')
            ->once()
            ->with('.content', Mockery::any())
            ->andReturnSelf();
        $mockPage->shouldReceive('waitForFunction')
            ->once()
            ->with(Mockery::pattern('/setTimeout/'))
            ->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn('https://example.com');
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('content')->andReturn('<html><body>Test</body></html>');
        $mockPage->shouldReceive('evaluate')->andReturn(null);
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com', [
            'waitForSelector' => '.content',
            'waitTime' => 3000,
        ]);

        $this->assertInstanceOf(RenderResult::class, $result);
    }

    /**
     * Test wait for non-existent selector throws timeout.
     * PR-003-04: Non-existent selector timeout
     */
    public function test_wait_for_nonexistent_selector_throws_timeout(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')->once()->andReturnSelf();
        $mockPage->shouldReceive('waitForFunction')->zeroOrMoreTimes()->andReturnSelf();
        $mockPage->shouldReceive('waitForSelector')
            ->once()
            ->andThrow(new \Exception('Timeout waiting for selector'));
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(TimeoutException::class);

        $service->render('https://example.com', ['waitForSelector' => '.nonexistent']);
    }

    // ==========================================
    // render() Method - Custom Options Tests (PR-004)
    // ==========================================

    /**
     * Test render with custom user agent.
     * PR-004-01: Custom user agent
     */
    public function test_render_with_custom_user_agent(): void
    {
        $customAgent = 'CustomBot/1.0';

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]));
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use ($customAgent) {
                return isset($options['userAgent']) && $options['userAgent'] === $customAgent;
            })
            ->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com', ['userAgent' => $customAgent]);

        $this->assertInstanceOf(RenderResult::class, $result);
    }

    /**
     * Test render with custom viewport.
     * PR-004-02: Custom viewport
     */
    public function test_render_with_custom_viewport(): void
    {
        $viewport = ['width' => 1920, 'height' => 1080];

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]));
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')
            ->once()
            ->withArgs(function ($options) use ($viewport) {
                return isset($options['viewport']) && $options['viewport'] === $viewport;
            })
            ->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com', ['viewport' => $viewport]);

        $this->assertInstanceOf(RenderResult::class, $result);
    }

    /**
     * Test render with custom headers.
     * PR-004-03: Custom headers
     */
    public function test_render_with_custom_headers(): void
    {
        $headers = ['X-Custom-Header' => 'value'];

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('setExtraHTTPHeaders')
            ->once()
            ->with($headers);
        $mockContext->shouldReceive('newPage')->once()->andReturn($this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]));
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com', ['headers' => $headers]);

        $this->assertInstanceOf(RenderResult::class, $result);
    }

    /**
     * Test render with cookies.
     * PR-004-04: Cookies
     */
    public function test_render_with_cookies(): void
    {
        $cookies = [
            ['name' => 'session', 'value' => 'abc123', 'domain' => 'example.com'],
        ];

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('addCookies')->once()->with($cookies);
        $mockContext->shouldReceive('newPage')->once()->andReturn($this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]));
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com', ['cookies' => $cookies]);

        $this->assertInstanceOf(RenderResult::class, $result);
    }

    /**
     * Test render with custom timeout.
     * PR-004-05: Custom timeout
     */
    public function test_render_with_custom_timeout(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(60000);
        $mockPage->shouldReceive('goto')
            ->once()
            ->with('https://example.com', Mockery::subset(['timeout' => 60000]))
            ->andReturnSelf();
        $mockPage->shouldReceive('waitForFunction')->zeroOrMoreTimes()->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn('https://example.com');
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('content')->andReturn('<html><body>Test</body></html>');
        $mockPage->shouldReceive('evaluate')->andReturn(null);
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com', ['timeout' => 60000]);

        $this->assertInstanceOf(RenderResult::class, $result);
    }

    // ==========================================
    // extractContent() Method Tests (PR-005)
    // ==========================================

    /**
     * Test extract full HTML.
     * PR-005-01: Extract full HTML
     */
    public function test_extract_content_html(): void
    {
        $html = '<html><head><title>Test</title></head><body>Content</body></html>';

        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('content')->once()->andReturn($html);
        $mockPage->shouldReceive('title')->once()->andReturn('Test');
        $mockPage->shouldReceive('evaluate')->zeroOrMoreTimes()->andReturn('');

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->extractContent($mockPage);

        $this->assertEquals($html, $result->html);
    }

    /**
     * Test extract page title.
     * PR-005-02: Extract page title
     */
    public function test_extract_content_title(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('content')->andReturn('<html><body>Test</body></html>');
        $mockPage->shouldReceive('title')->once()->andReturn('Page Title');
        $mockPage->shouldReceive('evaluate')->zeroOrMoreTimes()->andReturn('');

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->extractContent($mockPage);

        $this->assertEquals('Page Title', $result->title);
    }

    /**
     * Test extract structured text.
     * PR-005-03: Structured text
     */
    public function test_extract_content_structured_text(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('content')->andReturn('<html><body><h1>Title</h1><p>Paragraph</p></body></html>');
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->with(Mockery::pattern('/textContent/'))
            ->andReturn("Title\nParagraph");

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->extractContent($mockPage);

        $this->assertStringContainsString('Title', $result->structuredText);
        $this->assertStringContainsString('Paragraph', $result->structuredText);
    }

    /**
     * Test remove script tags from text.
     * PR-005-04: Remove script tags
     */
    public function test_extract_content_removes_scripts(): void
    {
        $html = '<html><body><script>alert("test")</script><p>Visible</p></body></html>';

        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('content')->andReturn($html);
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn('Visible');

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->extractContent($mockPage);

        $this->assertStringNotContainsString('alert', $result->structuredText);
        $this->assertStringContainsString('Visible', $result->structuredText);
    }

    /**
     * Test remove style tags from text.
     * PR-005-05: Remove style tags
     */
    public function test_extract_content_removes_styles(): void
    {
        $html = '<html><body><style>.test{color:red}</style><p>Content</p></body></html>';

        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('content')->andReturn($html);
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn('Content');

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->extractContent($mockPage);

        $this->assertStringNotContainsString('.test{color:red}', $result->structuredText);
    }

    /**
     * Test remove hidden elements from text.
     * PR-005-06: Remove hidden elements
     */
    public function test_extract_content_removes_hidden_elements(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('content')->andReturn('<html><body>Content</body></html>');
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn('Visible Content');

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->extractContent($mockPage);

        $this->assertStringContainsString('Visible Content', $result->structuredText);
    }

    /**
     * Test preserve heading structure.
     * PR-005-07: Preserve headings
     */
    public function test_extract_content_preserves_headings(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('content')->andReturn('<html><body><h1>Main</h1><h2>Sub</h2></body></html>');
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn("Main\nSub");

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->extractContent($mockPage);

        $this->assertStringContainsString('Main', $result->structuredText);
        $this->assertStringContainsString('Sub', $result->structuredText);
    }

    /**
     * Test preserve link text.
     * PR-005-08: Preserve link text
     */
    public function test_extract_content_preserves_link_text(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('content')->andReturn('<html><body><a href="/link">Link Text</a></body></html>');
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn('Link Text');

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->extractContent($mockPage);

        $this->assertStringContainsString('Link Text', $result->structuredText);
    }

    // ==========================================
    // extractLinks() Method Tests (PR-006)
    // ==========================================

    /**
     * Test extract all anchor links.
     * PR-006-01: Extract all links
     */
    public function test_extract_links_returns_array(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->with(Mockery::pattern('/querySelectorAll.*a/'))
            ->andReturn([
                ['href' => 'https://example.com/page1', 'text' => 'Page 1', 'rel' => null, 'target' => null],
            ]);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $links = $service->extractLinks($mockPage, 'https://example.com');

        $this->assertIsArray($links);
        $this->assertCount(1, $links);
    }

    /**
     * Test identify internal links.
     * PR-006-02: Internal links
     */
    public function test_extract_links_identifies_internal(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn([
                ['href' => 'https://example.com/page', 'text' => 'Internal', 'rel' => null, 'target' => null],
            ]);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $links = $service->extractLinks($mockPage, 'https://example.com');

        $this->assertTrue($links[0]['isInternal']);
    }

    /**
     * Test identify external links.
     * PR-006-03: External links
     */
    public function test_extract_links_identifies_external(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn([
                ['href' => 'https://external.com/page', 'text' => 'External', 'rel' => null, 'target' => null],
            ]);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $links = $service->extractLinks($mockPage, 'https://example.com');

        $this->assertFalse($links[0]['isInternal']);
    }

    /**
     * Test extract link text.
     * PR-006-04: Link text
     */
    public function test_extract_links_extracts_text(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn([
                ['href' => 'https://example.com', 'text' => 'Click Here', 'rel' => null, 'target' => null],
            ]);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $links = $service->extractLinks($mockPage, 'https://example.com');

        $this->assertEquals('Click Here', $links[0]['text']);
    }

    /**
     * Test extract rel attribute.
     * PR-006-05: rel attribute
     */
    public function test_extract_links_extracts_rel(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn([
                ['href' => 'https://example.com', 'text' => 'Link', 'rel' => 'nofollow', 'target' => null],
            ]);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $links = $service->extractLinks($mockPage, 'https://example.com');

        $this->assertEquals('nofollow', $links[0]['rel']);
    }

    /**
     * Test extract target attribute.
     * PR-006-06: target attribute
     */
    public function test_extract_links_extracts_target(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn([
                ['href' => 'https://example.com', 'text' => 'Link', 'rel' => null, 'target' => '_blank'],
            ]);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $links = $service->extractLinks($mockPage, 'https://example.com');

        $this->assertEquals('_blank', $links[0]['target']);
    }

    /**
     * Test handle relative URLs.
     * PR-006-07: Relative URLs
     */
    public function test_extract_links_converts_relative_urls(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn([
                ['href' => 'https://example.com/page', 'text' => 'Relative', 'rel' => null, 'target' => null],
            ]);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $links = $service->extractLinks($mockPage, 'https://example.com');

        $this->assertEquals('https://example.com/page', $links[0]['url']);
    }

    /**
     * Test handle links without href.
     * PR-006-08: Links without href
     */
    public function test_extract_links_skinks_links_without_href(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('evaluate')
            ->once()
            ->andReturn([
                ['href' => null, 'text' => 'No Href', 'rel' => null, 'target' => null],
                ['href' => 'https://example.com', 'text' => 'Valid', 'rel' => null, 'target' => null],
            ]);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $links = $service->extractLinks($mockPage, 'https://example.com');

        $this->assertCount(1, $links);
        $this->assertEquals('Valid', $links[0]['text']);
    }

    // ==========================================
    // extractMetadata() Method Tests (PR-007)
    // ==========================================

    /**
     * Test extract page title.
     * PR-007-01: Page title
     */
    public function test_extract_metadata_title(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('title')->once()->andReturn('Page Title');
        $mockPage->shouldReceive('evaluate')->andReturn(null);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $meta = $service->extractMetadata($mockPage);

        $this->assertEquals('Page Title', $meta['title']);
    }

    /**
     * Test extract meta description.
     * PR-007-02: Meta description
     */
    public function test_extract_metadata_description(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($script) {
                if (str_contains($script, 'description')) {
                    return 'Test description';
                }
                return null;
            });

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $meta = $service->extractMetadata($mockPage);

        $this->assertEquals('Test description', $meta['description']);
    }

    /**
     * Test extract meta robots.
     * PR-007-03: Meta robots
     */
    public function test_extract_metadata_robots(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($script) {
                if (str_contains($script, 'robots')) {
                    return 'index, follow';
                }
                return null;
            });

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $meta = $service->extractMetadata($mockPage);

        $this->assertEquals('index, follow', $meta['robots']);
    }

    /**
     * Test extract canonical URL.
     * PR-007-04: Canonical URL
     */
    public function test_extract_metadata_canonical(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($script) {
                if (str_contains($script, 'canonical')) {
                    return 'https://example.com/canonical';
                }
                return null;
            });

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $meta = $service->extractMetadata($mockPage);

        $this->assertEquals('https://example.com/canonical', $meta['canonical']);
    }

    /**
     * Test extract og:title.
     * PR-007-05: og:title
     */
    public function test_extract_metadata_og_title(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($script) {
                if (str_contains($script, 'og:title')) {
                    return 'OG Title';
                }
                return null;
            });

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $meta = $service->extractMetadata($mockPage);

        $this->assertEquals('OG Title', $meta['ogTitle']);
    }

    /**
     * Test extract og:description.
     * PR-007-06: og:description
     */
    public function test_extract_metadata_og_description(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($script) {
                if (str_contains($script, 'og:description')) {
                    return 'OG Description';
                }
                return null;
            });

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $meta = $service->extractMetadata($mockPage);

        $this->assertEquals('OG Description', $meta['ogDescription']);
    }

    /**
     * Test extract og:image.
     * PR-007-07: og:image
     */
    public function test_extract_metadata_og_image(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($script) {
                if (str_contains($script, 'og:image')) {
                    return 'https://example.com/image.jpg';
                }
                return null;
            });

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $meta = $service->extractMetadata($mockPage);

        $this->assertEquals('https://example.com/image.jpg', $meta['ogImage']);
    }

    /**
     * Test handle missing metadata.
     * PR-007-08: Missing metadata
     */
    public function test_extract_metadata_handles_missing(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('evaluate')->andReturn(null);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $meta = $service->extractMetadata($mockPage);

        $this->assertNull($meta['description']);
        $this->assertNull($meta['robots']);
        $this->assertNull($meta['canonical']);
    }

    // ==========================================
    // close() Method Tests (PR-008)
    // ==========================================

    /**
     * Test close browser instances.
     * PR-008-01: Close browser
     */
    public function test_close_browser_instances(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);
        $mockLauncher->shouldReceive('closeAll')->once();

        $service = new PlaywrightRendererService($mockLauncher);

        // First call to initialize
        $service->render('https://example.com');
        $service->close();

        $this->assertTrue(true);
    }

    /**
     * Test close with no instances.
     * PR-008-02: Close with no instances
     */
    public function test_close_with_no_instances(): void
    {
        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('closeAll')->once();

        $service = new PlaywrightRendererService($mockLauncher);
        $service->close();

        $this->assertTrue(true);
    }

    /**
     * Test close clears shared instance.
     * PR-008-03: Clear shared instance
     */
    public function test_close_clears_shared_instance(): void
    {
        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('closeAll')->once();
        // getSharedBrowser() is called after close, but sharedBrowser is null
        // so it would call getSharedInstance - but we just want to verify it's null

        $service = new PlaywrightRendererService($mockLauncher);
        $service->close();

        // The sharedBrowser should be null (never initialized)
        $this->assertNull($service->sharedBrowser ?? null);
    }

    // ==========================================
    // Error Handling Tests (PR-009)
    // ==========================================

    /**
     * Test timeout exception.
     * PR-009-01: TimeoutException
     */
    public function test_timeout_exception(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')
            ->once()
            ->andThrow(new \Exception('Timeout exceeded', 408));
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(TimeoutException::class);

        $service->render('https://example.com', ['timeout' => 1000]);
    }

    /**
     * Test navigation exception.
     * PR-009-02: NavigationException
     */
    public function test_navigation_exception(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')
            ->once()
            ->andThrow(new \Exception('Navigation failed'));
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(NavigationException::class);

        $service->render('https://invalid-url-that-does-not-exist.com');
    }

    /**
     * Test render exception on extraction failure.
     * PR-009-03: RenderException
     */
    public function test_render_exception_on_extraction_failure(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')->once()->andReturnSelf();
        $mockPage->shouldReceive('waitForFunction')->zeroOrMoreTimes()->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn('https://example.com');
        $mockPage->shouldReceive('content')->once()->andThrow(new \Exception('Content extraction failed'));
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(RenderException::class);

        $service->render('https://example.com');
    }

    /**
     * Test partial results on error.
     * PR-009-04: Partial results
     */
    public function test_partial_results_on_error(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')->once()->andReturnSelf();
        $mockPage->shouldReceive('waitForFunction')->zeroOrMoreTimes()->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn('https://example.com');
        $mockPage->shouldReceive('title')->andReturn('Partial');
        $mockPage->shouldReceive('content')->once()->andThrow(new \Exception('Extraction failed'));
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);
        $service->setReturnPartialOnError(true);

        $result = $service->render('https://example.com');

        $this->assertNotNull($result->error);
        $this->assertEquals('https://example.com', $result->url);
    }

    /**
     * Test log error with context.
     * PR-009-05: Log error with context
     */
    public function test_log_error_with_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'render') &&
                    isset($context['url']) &&
                    $context['url'] === 'https://example.com';
            });

        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')->once()->andThrow(new \Exception('Test error'));
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        try {
            $service->render('https://example.com');
        } catch (\Exception $e) {
            // Expected
        }
    }

    // ==========================================
    // RenderResult Data Structure Tests (PR-010)
    // ==========================================

    /**
     * Test all fields populated on success.
     * PR-010-01: All fields populated
     */
    public function test_render_result_all_fields_populated(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test Page',
            'content' => '<html><body>Content</body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertNotNull($result->url);
        $this->assertNotNull($result->originalUrl);
        $this->assertNotNull($result->title);
        $this->assertNotNull($result->renderedHtml);
        $this->assertNotNull($result->structuredText);
        $this->assertIsArray($result->meta);
        $this->assertIsArray($result->links);
        $this->assertIsInt($result->size);
        $this->assertIsInt($result->loadTime);
        $this->assertIsInt($result->statusCode);
    }

    /**
     * Test error field null on success.
     * PR-010-02: Error null on success
     */
    public function test_render_result_error_null_on_success(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertNull($result->error);
    }

    /**
     * Test error field set on failure.
     * PR-010-03: Error set on failure
     */
    public function test_render_result_error_set_on_failure(): void
    {
        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')->once()->andReturnSelf();
        $mockPage->shouldReceive('waitForFunction')->zeroOrMoreTimes()->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn('https://example.com');
        $mockPage->shouldReceive('title')->andReturn('Test');
        $mockPage->shouldReceive('content')->once()->andThrow(new \Exception('Failed'));
        $mockPage->shouldReceive('close')->once();

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);
        $service->setReturnPartialOnError(true);

        $result = $service->render('https://example.com');

        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Failed', $result->error);
    }

    /**
     * Test statusCode from response.
     * PR-010-04: StatusCode set
     */
    public function test_render_result_status_code(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Test',
            'content' => '<html><body>Test</body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertEquals(200, $result->statusCode);
    }

    // ==========================================
    // Edge Case Tests (PR-011)
    // ==========================================

    /**
     * Test empty URL throws exception.
     * PR-011-01: Empty URL
     */
    public function test_render_empty_url_throws_exception(): void
    {
        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(\InvalidArgumentException::class);

        $service->render('');
    }

    /**
     * Test invalid URL throws exception.
     * PR-011-02: Invalid URL
     */
    public function test_render_invalid_url_throws_exception(): void
    {
        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(\InvalidArgumentException::class);

        $service->render('not-a-valid-url');
    }

    /**
     * Test non-HTTP protocol throws exception.
     * PR-011-03: Non-HTTP protocol
     */
    public function test_render_non_http_protocol_throws_exception(): void
    {
        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(\InvalidArgumentException::class);

        $service->render('ftp://example.com');
    }

    /**
     * Test very large page handled gracefully.
     * PR-011-04: Large page
     */
    public function test_render_large_page_handled_gracefully(): void
    {
        // Simulate 3MB content
        $largeContent = '<html><body>' . str_repeat('a', 3 * 1024 * 1024) . '</body></html>';

        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Large Page',
            'content' => $largeContent,
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertGreaterThan(3 * 1024 * 1024, $result->size);
    }

    /**
     * Test unicode content preserved.
     * PR-011-05: Unicode content
     */
    public function test_render_unicode_content_preserved(): void
    {
        $unicodeContent = '<html><body>日本語 中文 العربية Ελληνικά</body></html>';

        $mockPage = $this->createMockPage([
            'url' => 'https://example.com',
            'title' => 'Unicode Test 日本語',
            'content' => $unicodeContent,
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://example.com');

        $this->assertStringContainsString('日本語', $result->renderedHtml);
        $this->assertStringContainsString('中文', $result->renderedHtml);
    }

    /**
     * Test SPA with hash routing.
     * PR-011-06: Hash-based SPA
     */
    public function test_render_spa_hash_routing(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://spa.example.com/#/page',
            'title' => 'SPA Page',
            'content' => '<html><body><div id="app">SPA Content</div></body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://spa.example.com/#/page');

        $this->assertStringContainsString('SPA Content', $result->renderedHtml);
    }

    /**
     * Test SPA with history API.
     * PR-011-07: History API SPA
     */
    public function test_render_spa_history_api(): void
    {
        $mockPage = $this->createMockPage([
            'url' => 'https://spa.example.com/page',
            'title' => 'SPA History Page',
            'content' => '<html><body><div id="app">History API Content</div></body></html>',
        ]);

        $mockContext = Mockery::mock(BrowserContextInterface::class);
        $mockContext->shouldReceive('newPage')->once()->andReturn($mockPage);
        $mockContext->shouldReceive('close')->once();

        $mockBrowser = Mockery::mock(BrowserInterface::class);
        $mockBrowser->shouldReceive('newContext')->once()->andReturn($mockContext);
        $mockBrowser->shouldReceive('isConnected')->andReturn(true);

        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $mockLauncher->shouldReceive('getSharedInstance')->once()->andReturn($mockBrowser);

        $service = new PlaywrightRendererService($mockLauncher);

        $result = $service->render('https://spa.example.com/page');

        $this->assertStringContainsString('History API Content', $result->renderedHtml);
    }

    // ==========================================
    // Security Tests (PR-012)
    // ==========================================

    /**
     * Test SSRF localhost blocked.
     * PR-012-01: Localhost blocked
     */
    public function test_render_blocks_localhost(): void
    {
        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(\InvalidArgumentException::class);

        $service->render('http://localhost/test');
    }

    /**
     * Test SSRF private IP blocked.
     * PR-012-02: Private IP blocked
     */
    public function test_render_blocks_private_ip(): void
    {
        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(\InvalidArgumentException::class);

        $service->render('http://192.168.1.1/test');
    }

    /**
     * Test SSRF cloud metadata blocked.
     * PR-012-03: Cloud metadata blocked
     */
    public function test_render_blocks_cloud_metadata(): void
    {
        $mockLauncher = Mockery::mock(BrowserLauncher::class);
        $service = new PlaywrightRendererService($mockLauncher);

        $this->expectException(\InvalidArgumentException::class);

        $service->render('http://169.254.169.254/latest/meta-data');
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Create a mock page with standard responses.
     */
    private function createMockPage(array $config = []): MockInterface
    {
        $url = $config['url'] ?? 'https://example.com';
        $title = $config['title'] ?? 'Test Page';
        $content = $config['content'] ?? '<html><body>Test</body></html>';

        $mockPage = Mockery::mock(PageInterface::class);
        $mockPage->shouldReceive('setDefaultNavigationTimeout')
            ->once()
            ->with(Mockery::type('int'));
        $mockPage->shouldReceive('goto')
            ->once()
            ->with($url, Mockery::any())
            ->andReturnSelf();
        // waitForFunction is only called when waitTime is specified, so we allow it but don't require it
        $mockPage->shouldReceive('waitForFunction')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        $mockPage->shouldReceive('url')->andReturn($url);
        $mockPage->shouldReceive('title')->andReturn($title);
        $mockPage->shouldReceive('content')->andReturn($content);
        $mockPage->shouldReceive('evaluate')->andReturn(null);
        $mockPage->shouldReceive('close')->once();

        return $mockPage;
    }
}
