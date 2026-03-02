<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\DepthControllerService;
use App\Services\Crawler\UrlValidatorService;
use App\Models\CrawlJob;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for the DepthControllerService.
 *
 * @see TASK-22-8
 * @see SPEC-022
 */
class DepthControllerServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Service Instantiation Tests (DC-001)
    // ==========================================

    /**
     * Test service can be instantiated via container.
     * DC-001-01: Service instantiation
     */
    public function test_service_can_be_instantiated(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $this->assertInstanceOf(DepthControllerService::class, $service);
    }

    /**
     * Test service has CrawlJob model injected.
     * DC-001-02: CrawlJob injection
     */
    public function test_service_has_crawl_job_injected(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $this->assertNotNull($service->getCrawlJob());
    }

    /**
     * Test service has Logger injected.
     * DC-001-03: Logger injection
     */
    public function test_service_has_logger_injected(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $this->assertNotNull($service->getLogger());
    }

    /**
     * Test service has UrlValidatorService injected.
     * DC-001-04: UrlValidatorService injection
     */
    public function test_service_has_url_validator_injected(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $this->assertNotNull($service->getUrlValidator());
    }

    /**
     * Test service loads config defaults.
     * DC-001-05: Config defaults loaded
     */
    public function test_service_loads_config_defaults(): void
    {
        config(['crawler.max_depth' => 2]);
        config(['crawler.max_pages' => 50]);

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        $this->assertEquals(2, $service->getDefaultMaxDepth());
        $this->assertEquals(50, $service->getDefaultMaxPages());
    }

    // ==========================================
    // shouldCrawl() Method Tests (DC-002)
    // ==========================================

    /**
     * Test URL at depth 0, maxDepth 2.
     * DC-002-01: Depth 0, maxDepth 2
     */
    public function test_should_crawl_at_depth_zero(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->with('https://example.com')
            ->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        $result = $service->shouldCrawl('https://example.com', 0, 2);

        $this->assertTrue($result);
    }

    /**
     * Test URL at depth 1, maxDepth 2.
     * DC-002-02: Depth 1, maxDepth 2
     */
    public function test_should_crawl_at_depth_one(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        $result = $service->shouldCrawl('https://example.com/page', 1, 2);

        $this->assertTrue($result);
    }

    /**
     * Test URL at depth 2, maxDepth 2.
     * DC-002-03: Depth 2, maxDepth 2
     */
    public function test_should_crawl_at_depth_two(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        $result = $service->shouldCrawl('https://example.com/page/sub', 2, 2);

        $this->assertTrue($result);
    }

    /**
     * Test URL at depth 3, maxDepth 2.
     * DC-002-04: Depth exceeds maxDepth
     */
    public function test_should_crawl_rejects_depth_exceeding_max(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        $result = $service->shouldCrawl('https://example.com/page/sub/deep', 3, 2);

        $this->assertFalse($result);
    }

    /**
     * Test URL already crawled.
     * DC-002-05: Already crawled URL
     */
    public function test_should_crawl_rejects_already_crawled(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        // Mark URL as crawled
        $service->markCrawled('https://example.com', 'test-job');

        $result = $service->shouldCrawl('https://example.com', 0, 2, 'test-job');

        $this->assertFalse($result);
    }

    /**
     * Test URL blocked by SSRF.
     * DC-002-07: SSRF blocked URL
     */
    public function test_should_crawl_rejects_ssrf_blocked(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => false, 'blocked' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        $result = $service->shouldCrawl('http://localhost', 0, 2);

        $this->assertFalse($result);
    }

    /**
     * Test empty URL.
     * DC-002-08: Empty URL
     */
    public function test_should_crawl_rejects_empty_url(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        $result = $service->shouldCrawl('', 0, 2);

        $this->assertFalse($result);
    }

    /**
     * Test null URL.
     * DC-002-09: Null URL
     */
    public function test_should_crawl_rejects_null_url(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        $result = $service->shouldCrawl(null, 0, 2);

        $this->assertFalse($result);
    }

    /**
     * Test negative depth.
     * DC-002-10: Negative depth
     */
    public function test_should_crawl_rejects_negative_depth(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        $result = $service->shouldCrawl('https://example.com', -1, 2);

        $this->assertFalse($result);
    }

    /**
     * Test URL normalized before check.
     * DC-002-12: URL normalized before check
     */
    public function test_should_crawl_normalizes_url(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->with('https://example.com/page')
            ->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        // URL with trailing slash should be normalized
        $result = $service->shouldCrawl('https://example.com/page/', 0, 2);

        $this->assertTrue($result);
    }

    // ==========================================
    // extractLinks() Method Tests - Basic Extraction (DC-003)
    // ==========================================

    /**
     * Test extract single link.
     * DC-003-01: Single link extraction
     */
    public function test_extract_links_single_link(): void
    {
        $html = '<a href="/page">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(1, $links);
        $this->assertEquals('https://example.com/page', $links[0]['url']);
    }

    /**
     * Test extract multiple links.
     * DC-003-02: Multiple links extraction
     */
    public function test_extract_links_multiple_links(): void
    {
        $html = '<a href="/page1">Link 1</a><a href="/page2">Link 2</a><a href="/page3">Link 3</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(3, $links);
    }

    /**
     * Test no links in HTML.
     * DC-003-03: No links
     */
    public function test_extract_links_no_links(): void
    {
        $html = '<p>Just text, no links here.</p>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(0, $links);
    }

    /**
     * Test empty HTML.
     * DC-003-04: Empty HTML
     */
    public function test_extract_links_empty_html(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks('', 'https://example.com');

        $this->assertCount(0, $links);
    }

    /**
     * Test null HTML.
     * DC-003-05: Null HTML
     */
    public function test_extract_links_null_html(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks(null, 'https://example.com');

        $this->assertCount(0, $links);
    }

    /**
     * Test extract from complex HTML.
     * DC-003-06: Complex HTML
     */
    public function test_extract_links_complex_html(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body><nav><a href="/">Home</a></nav><main><a href="/page1">Page 1</a></main></body></html>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(2, $links);
    }

    /**
     * Test links in script tags ignored.
     * DC-003-07: Script links ignored
     */
    public function test_extract_links_ignores_script_tags(): void
    {
        $html = '<script>var link = "https://example.com/script-link";</script><a href="/page">Real Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(1, $links);
        $this->assertStringNotContainsString('script-link', $links[0]['url']);
    }

    /**
     * Test links in comments ignored.
     * DC-003-08: Comment links ignored
     */
    public function test_extract_links_ignores_comment_links(): void
    {
        $html = '<!-- <a href="/hidden">Hidden</a> --><a href="/visible">Visible</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(1, $links);
        $this->assertStringNotContainsString('hidden', $links[0]['url']);
    }

    // ==========================================
    // extractLinks() Method Tests - URL Resolution (DC-004)
    // ==========================================

    /**
     * Test relative URL.
     * DC-004-01: Relative URL
     */
    public function test_extract_links_relative_url(): void
    {
        $html = '<a href="/page">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('https://example.com/page', $links[0]['url']);
    }

    /**
     * Test relative URL with path.
     * DC-004-02: Relative URL with path
     */
    public function test_extract_links_relative_url_with_path(): void
    {
        $html = '<a href="sub/page">Link</a>';
        $baseUrl = 'https://example.com/dir/';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('https://example.com/dir/sub/page', $links[0]['url']);
    }

    /**
     * Test protocol-relative URL.
     * DC-004-03: Protocol-relative URL
     */
    public function test_extract_links_protocol_relative_url(): void
    {
        $html = '<a href="//cdn.example.com/resource">CDN Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('https://cdn.example.com/resource', $links[0]['url']);
    }

    /**
     * Test absolute URL.
     * DC-004-04: Absolute URL
     */
    public function test_extract_links_absolute_url(): void
    {
        $html = '<a href="https://other.com/page">External</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('https://other.com/page', $links[0]['url']);
    }

    /**
     * Test root-relative URL.
     * DC-004-05: Root-relative URL
     */
    public function test_extract_links_root_relative_url(): void
    {
        $html = '<a href="/root/page">Link</a>';
        $baseUrl = 'https://example.com/dir/sub/';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('https://example.com/root/page', $links[0]['url']);
    }

    /**
     * Test parent directory URL.
     * DC-004-06: Parent directory URL
     */
    public function test_extract_links_parent_directory_url(): void
    {
        $html = '<a href="../page">Link</a>';
        $baseUrl = 'https://example.com/dir/sub/';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('https://example.com/dir/page', $links[0]['url']);
    }

    /**
     * Test query string preserved.
     * DC-004-07: Query string preserved
     */
    public function test_extract_links_query_string_preserved(): void
    {
        $html = '<a href="/page?foo=bar">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('https://example.com/page?foo=bar', $links[0]['url']);
    }

    /**
     * Test multiple query params.
     * DC-004-09: Multiple query params
     */
    public function test_extract_links_multiple_query_params(): void
    {
        $html = '<a href="/page?a=1&b=2">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertStringContainsString('a=1', $links[0]['url']);
        $this->assertStringContainsString('b=2', $links[0]['url']);
    }

    // ==========================================
    // extractLinks() Method Tests - Special URLs (DC-005)
    // ==========================================

    /**
     * Test skip mailto: links.
     * DC-005-01: Skip mailto
     */
    public function test_extract_links_skips_mailto(): void
    {
        $html = '<a href="mailto:test@example.com">Email</a><a href="/page">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(1, $links);
        $this->assertStringContainsString('/page', $links[0]['url']);
    }

    /**
     * Test skip javascript: links.
     * DC-005-02: Skip javascript
     */
    public function test_extract_links_skips_javascript(): void
    {
        $html = '<a href="javascript:void(0)">JS Link</a><a href="/page">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(1, $links);
    }

    /**
     * Test skip tel: links.
     * DC-005-03: Skip tel
     */
    public function test_extract_links_skips_tel(): void
    {
        $html = '<a href="tel:+1234567890">Phone</a><a href="/page">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(1, $links);
    }

    /**
     * Test skip empty href.
     * DC-005-07: Skip empty href
     */
    public function test_extract_links_skips_empty_href(): void
    {
        $html = '<a href="">Empty</a><a href="/page">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(1, $links);
    }

    /**
     * Test skip href="#".
     * DC-005-08: Skip hash only
     */
    public function test_extract_links_skips_hash_only(): void
    {
        $html = '<a href="#">Top</a><a href="/page">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(1, $links);
    }

    /**
     * Test skip hash fragment only.
     * DC-005-09: Skip same-page fragment
     */
    public function test_extract_links_skips_same_page_fragment(): void
    {
        $html = '<a href="#section">Section</a><a href="/page">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertCount(1, $links);
    }

    // ==========================================
    // extractLinks() Method Tests - Link Attributes (DC-006)
    // ==========================================

    /**
     * Test extract link text.
     * DC-006-01: Link text
     */
    public function test_extract_links_extracts_text(): void
    {
        $html = '<a href="/page">Link Text</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('Link Text', $links[0]['text']);
    }

    /**
     * Test trim link text.
     * DC-006-02: Trimmed text
     */
    public function test_extract_links_trims_text(): void
    {
        $html = '<a href="/page">  Trimmed Text  </a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('Trimmed Text', $links[0]['text']);
    }

    /**
     * Test extract rel attribute.
     * DC-006-05: rel attribute
     */
    public function test_extract_links_extracts_rel(): void
    {
        $html = '<a href="/page" rel="nofollow">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('nofollow', $links[0]['rel']);
    }

    /**
     * Test extract target attribute.
     * DC-006-08: target attribute
     */
    public function test_extract_links_extracts_target(): void
    {
        $html = '<a href="/page" target="_blank">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertEquals('_blank', $links[0]['target']);
    }

    /**
     * Test detect nofollow link.
     * DC-006-09: nofollow detection
     */
    public function test_extract_links_detects_nofollow(): void
    {
        $html = '<a href="/page" rel="nofollow">Link</a>';
        $baseUrl = 'https://example.com';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, $baseUrl);

        $this->assertTrue($links[0]['nofollow']);
    }

    // ==========================================
    // filterLinks() Method Tests - Basic Filtering (DC-007)
    // ==========================================

    /**
     * Test filter empty array.
     * DC-007-01: Empty array
     */
    public function test_filter_links_empty_array(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks([], ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    /**
     * Test filter null input.
     * DC-007-02: Null input
     */
    public function test_filter_links_null_input(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks(null, ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    /**
     * Test filter SSRF blocked.
     * DC-007-04: SSRF blocked
     */
    public function test_filter_links_blocks_ssrf(): void
    {
        $links = [['url' => 'http://localhost/page']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => false, 'blocked' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    /**
     * Test filter duplicates.
     * DC-007-05: Duplicate filtering
     */
    public function test_filter_links_removes_duplicates(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/page'], // duplicate
        ];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(1, $result);
    }

    /**
     * Test filter external domains.
     * DC-007-07: External domain filtering
     */
    public function test_filter_links_filters_external_domains(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://external.com/page'],
        ];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, [
            'baseUrl' => 'https://example.com',
            'followExternalLinks' => false,
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('https://example.com/page', $result[0]['url']);
    }

    /**
     * Test allow external domains.
     * DC-007-08: Allow external domains
     */
    public function test_filter_links_allows_external_domains(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://external.com/page'],
        ];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->twice()
            ->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, [
            'baseUrl' => 'https://example.com',
            'followExternalLinks' => true,
        ]);

        $this->assertCount(2, $result);
    }

    // ==========================================
    // filterLinks() Method Tests - File Extension Filtering (DC-008)
    // ==========================================

    /**
     * Test skip PDF files.
     * DC-008-01: Skip PDF
     */
    public function test_filter_links_skips_pdf(): void
    {
        $links = [['url' => 'https://example.com/doc.pdf']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    /**
     * Test skip JPG files.
     * DC-008-02: Skip JPG
     */
    public function test_filter_links_skips_jpg(): void
    {
        $links = [['url' => 'https://example.com/image.jpg']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    /**
     * Test case-insensitive extension.
     * DC-008-11: Case-insensitive extension
     */
    public function test_filter_links_case_insensitive_extension(): void
    {
        $links = [['url' => 'https://example.com/IMAGE.JPG']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    /**
     * Test extension with query.
     * DC-008-12: Extension with query
     */
    public function test_filter_links_extension_with_query(): void
    {
        $links = [['url' => 'https://example.com/doc.pdf?v=1']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    // ==========================================
    // filterLinks() Method Tests - Path Filtering (DC-009)
    // ==========================================

    /**
     * Test skip /api/* paths.
     * DC-009-01: Skip API paths
     */
    public function test_filter_links_skips_api_paths(): void
    {
        $links = [['url' => 'https://example.com/api/data']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    /**
     * Test skip /admin/* paths.
     * DC-009-02: Skip admin paths
     */
    public function test_filter_links_skips_admin_paths(): void
    {
        $links = [['url' => 'https://example.com/admin/settings']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    /**
     * Test skip /login paths.
     * DC-009-03: Skip login paths
     */
    public function test_filter_links_skips_login_paths(): void
    {
        $links = [['url' => 'https://example.com/login']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(0, $result);
    }

    // ==========================================
    // URL Normalization Tests (DC-011)
    // ==========================================

    /**
     * Test lowercase host.
     * DC-011-01: Lowercase host
     */
    public function test_normalize_url_lowercase_host(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->normalizeUrl('HTTPS://EXAMPLE.COM/page');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove default port 80.
     * DC-011-02: Remove port 80
     */
    public function test_normalize_url_remove_port_80(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->normalizeUrl('http://example.com:80/page');

        $this->assertEquals('http://example.com/page', $result);
    }

    /**
     * Test remove default port 443.
     * DC-011-03: Remove port 443
     */
    public function test_normalize_url_remove_port_443(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->normalizeUrl('https://example.com:443/page');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test keep custom port.
     * DC-011-04: Keep custom port
     */
    public function test_normalize_url_keep_custom_port(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->normalizeUrl('https://example.com:8080/page');

        $this->assertEquals('https://example.com:8080/page', $result);
    }

    /**
     * Test remove trailing slash.
     * DC-011-05: Remove trailing slash
     */
    public function test_normalize_url_remove_trailing_slash(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->normalizeUrl('https://example.com/page/');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove utm_* params.
     * DC-011-07: Remove UTM params
     */
    public function test_normalize_url_remove_utm_params(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->normalizeUrl('https://example.com/page?utm_source=google');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test remove fbclid param.
     * DC-011-08: Remove fbclid
     */
    public function test_normalize_url_remove_fbclid(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->normalizeUrl('https://example.com/page?fbclid=abc123');

        $this->assertEquals('https://example.com/page', $result);
    }

    /**
     * Test sort query parameters.
     * DC-011-13: Sort query params
     */
    public function test_normalize_url_sort_query_params(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->normalizeUrl('https://example.com/page?b=2&a=1');

        $this->assertEquals('https://example.com/page?a=1&b=2', $result);
    }

    /**
     * Test remove fragment.
     * DC-011-14: Remove fragment
     */
    public function test_normalize_url_remove_fragment(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->normalizeUrl('https://example.com/page#section');

        $this->assertEquals('https://example.com/page', $result);
    }

    // ==========================================
    // queueLinks() Method Tests (DC-012)
    // ==========================================

    /**
     * Test queue single link.
     * DC-012-01: Queue single link
     */
    public function test_queue_links_single_link(): void
    {
        $links = [['url' => 'https://example.com/page']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $service->queueLinks($links, 'test-job', 1);

        $queue = $service->getCrawlQueue('test-job');

        $this->assertCount(1, $queue);
        $this->assertEquals('https://example.com/page', $queue[0]['url']);
        $this->assertEquals(1, $queue[0]['depth']);
    }

    /**
     * Test queue multiple links.
     * DC-012-02: Queue multiple links
     */
    public function test_queue_links_multiple_links(): void
    {
        $links = [
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2'],
        ];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $service->queueLinks($links, 'test-job', 1);

        $queue = $service->getCrawlQueue('test-job');

        $this->assertCount(2, $queue);
    }

    /**
     * Test queue empty array.
     * DC-012-03: Queue empty array
     */
    public function test_queue_links_empty_array(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $service->queueLinks([], 'test-job', 1);

        $queue = $service->getCrawlQueue('test-job');

        $this->assertCount(0, $queue);
    }

    /**
     * Test store parent URL.
     * DC-012-05: Store parent URL
     */
    public function test_queue_links_stores_parent_url(): void
    {
        $links = [['url' => 'https://example.com/page']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $service->queueLinks($links, 'test-job', 1, 'https://example.com');

        $queue = $service->getCrawlQueue('test-job');

        $this->assertEquals('https://example.com', $queue[0]['parent_url']);
    }

    /**
     * Test set status pending.
     * DC-012-07: Set status pending
     */
    public function test_queue_links_sets_status_pending(): void
    {
        $links = [['url' => 'https://example.com/page']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $service->queueLinks($links, 'test-job', 1);

        $queue = $service->getCrawlQueue('test-job');

        $this->assertEquals('pending', $queue[0]['status']);
    }

    // ==========================================
    // getCrawlQueue() Method Tests (DC-013)
    // ==========================================

    /**
     * Test get empty queue.
     * DC-013-01: Empty queue
     */
    public function test_get_crawl_queue_empty(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $queue = $service->getCrawlQueue('nonexistent-job');

        $this->assertCount(0, $queue);
    }

    /**
     * Test order by depth.
     * DC-013-03: Order by depth
     */
    public function test_get_crawl_queue_orders_by_depth(): void
    {
        $links = [
            ['url' => 'https://example.com/page1', 'depth' => 1],
            ['url' => 'https://example.com/page2', 'depth' => 2],
            ['url' => 'https://example.com/page0', 'depth' => 0],
        ];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        foreach ($links as $link) {
            $service->queueLinks([['url' => $link['url']]], 'test-job', $link['depth']);
        }

        $queue = $service->getCrawlQueue('test-job');

        $this->assertEquals(0, $queue[0]['depth']);
        $this->assertEquals(1, $queue[1]['depth']);
        $this->assertEquals(2, $queue[2]['depth']);
    }

    // ==========================================
    // Depth Management Tests (DC-014)
    // ==========================================

    /**
     * Test L0: Main page only.
     * DC-014-01: L0 crawl
     */
    public function test_depth_l0_main_page_only(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        // Max depth 0 should only allow seed URL
        $this->assertTrue($service->shouldCrawl('https://example.com', 0, 0));
        $this->assertFalse($service->shouldCrawl('https://example.com/page', 1, 0));
    }

    /**
     * Test L1: One level deep.
     * DC-014-02: L1 crawl
     */
    public function test_depth_l1_one_level(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        // Max depth 1 should allow seed and direct links
        $this->assertTrue($service->shouldCrawl('https://example.com', 0, 1));
        $this->assertTrue($service->shouldCrawl('https://example.com/page', 1, 1));
        $this->assertFalse($service->shouldCrawl('https://example.com/page/sub', 2, 1));
    }

    /**
     * Test L2: Two levels deep.
     * DC-014-03: L2 crawl
     */
    public function test_depth_l2_two_levels(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);

        // Max depth 2 should allow seed, links, and sub-links
        $this->assertTrue($service->shouldCrawl('https://example.com', 0, 2));
        $this->assertTrue($service->shouldCrawl('https://example.com/page', 1, 2));
        $this->assertTrue($service->shouldCrawl('https://example.com/page/sub', 2, 2));
        $this->assertFalse($service->shouldCrawl('https://example.com/page/sub/deep', 3, 2));
    }

    // ==========================================
    // Duplicate Detection Tests (DC-015)
    // ==========================================

    /**
     * Test detect exact duplicate.
     * DC-015-01: Exact duplicate
     */
    public function test_duplicate_detection_exact(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/page'],
        ];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(1, $result);
    }

    /**
     * Test detect normalized duplicate.
     * DC-015-02: Normalized duplicate
     */
    public function test_duplicate_detection_normalized(): void
    {
        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/page/'],
        ];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(1, $result);
    }

    /**
     * Test case-insensitive host.
     * DC-015-07: Case-insensitive host
     */
    public function test_duplicate_detection_case_insensitive_host(): void
    {
        $links = [
            ['url' => 'https://EXAMPLE.COM/page'],
            ['url' => 'https://example.com/page'],
        ];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, ['baseUrl' => 'https://example.com']);

        $this->assertCount(1, $result);
    }

    // ==========================================
    // Security Tests (DC-019)
    // ==========================================

    /**
     * Test SSRF localhost blocked.
     * DC-019-03: SSRF localhost
     */
    public function test_ssrf_localhost_blocked(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => false, 'blocked' => true, 'blockReason' => 'localhost']);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->shouldCrawl('http://localhost', 0, 2);

        $this->assertFalse($result);
    }

    /**
     * Test SSRF private IP blocked.
     * DC-019-04: SSRF private IP
     */
    public function test_ssrf_private_ip_blocked(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => false, 'blocked' => true, 'blockReason' => 'private_network']);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->shouldCrawl('http://192.168.1.1', 0, 2);

        $this->assertFalse($result);
    }

    /**
     * Test SSRF cloud metadata blocked.
     * DC-019-05: SSRF cloud metadata
     */
    public function test_ssrf_cloud_metadata_blocked(): void
    {
        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => false, 'blocked' => true, 'blockReason' => 'metadata_endpoint']);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->shouldCrawl('http://169.254.169.254', 0, 2);

        $this->assertFalse($result);
    }

    // ==========================================
    // Edge Cases Tests (DC-018)
    // ==========================================

    /**
     * Test very long URL.
     * DC-018-01: Very long URL
     */
    public function test_very_long_url(): void
    {
        $longPath = str_repeat('/a', 500);
        $url = 'https://example.com' . $longPath;

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $mockValidator->shouldReceive('validate')
            ->once()
            ->andReturn(['valid' => true]);

        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->shouldCrawl($url, 0, 2);

        $this->assertTrue($result);
    }

    /**
     * Test malformed HTML.
     * DC-018-03: Malformed HTML
     */
    public function test_extract_links_malformed_html(): void
    {
        $html = '<a href="/page1">Link 1<div><a href="/page2">Link 2</a>';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, 'https://example.com');

        $this->assertGreaterThanOrEqual(1, count($links));
    }

    /**
     * Test empty body.
     * DC-018-05: Empty body
     */
    public function test_extract_links_empty_body(): void
    {
        $html = '<html><body></body></html>';

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $links = $service->extractLinks($html, 'https://example.com');

        $this->assertCount(0, $links);
    }

    /**
     * Test self-referencing links.
     * DC-018-08: Self-referencing links
     */
    public function test_self_referencing_links_skipped(): void
    {
        $links = [['url' => 'https://example.com']];

        $mockValidator = Mockery::mock(UrlValidatorService::class);
        $this->app->instance(UrlValidatorService::class, $mockValidator);

        $service = app(DepthControllerService::class);
        $result = $service->filterLinks($links, [
            'baseUrl' => 'https://example.com',
            'skipSelfReferences' => true,
        ]);

        $this->assertCount(0, $result);
    }
}
