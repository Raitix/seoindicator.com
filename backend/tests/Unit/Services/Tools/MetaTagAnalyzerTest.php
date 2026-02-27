<?php

namespace Tests\Unit\Services\Tools;

use Tests\TestCase;
use App\Services\Tools\MetaTagAnalyzer;
use App\Services\Tools\ToolResult;
use App\Services\URLValidationService;
use App\Services\HtmlParserService;
use Illuminate\Support\Facades\Http;

/**
 * Unit tests for TASK-16-2: Meta Tag Analyzer
 *
 * Tests the MetaTagAnalyzer service responsible for:
 * - Title tag extraction and validation
 * - Meta description extraction and validation
 * - Open Graph tag extraction
 * - Canonical URL detection
 * - Warning generation
 * - SERP preview generation
 *
 * @see \App\Services\Tools\MetaTagAnalyzer
 */
class MetaTagAnalyzerTest extends TestCase
{
    protected MetaTagAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = app(MetaTagAnalyzer::class);
    }

    /**
     * Create a mock HTTP response for testing.
     */
    protected function mockHtmlResponse(string $html, int $status = 200): void
    {
        Http::fake([
            '*' => Http::response($html, $status, ['Content-Type' => 'text/html']),
        ]);
    }

    // =========================================================================
    // TITLE TAG EXTRACTION TESTS (TITLE)
    // =========================================================================

    /**
     * TITLE-001: Standard title extraction
     */
    public function test_standard_title_extraction(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page Title</title></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals('Page Title', $result->data['title']['value']);
        $this->assertEquals(10, $result->data['title']['length']);
    }

    /**
     * TITLE-002: Missing title
     */
    public function test_missing_title(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertNull($result->data['title']['value']);
        $this->assertWarningContains($result, 'Missing title tag');
    }

    /**
     * TITLE-003: Empty title
     */
    public function test_empty_title(): void
    {
        $html = '<!DOCTYPE html><html><head><title></title></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals('', $result->data['title']['value']);
    }

    /**
     * TITLE-004: Long title (65 chars) - too long warning
     */
    public function test_long_title_warning(): void
    {
        $title = str_repeat('a', 65);
        $html = "<html><head><title>{$title}</title></head><body></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals(65, $result->data['title']['length']);
        $this->assertWarningContains($result, 'long');
    }

    /**
     * TITLE-005: Short title (29 chars) - too short warning
     */
    public function test_short_title_warning(): void
    {
        $title = str_repeat('a', 29);
        $html = "<html><head><title>{$title}</title></head><body></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals(29, $result->data['title']['length']);
        $this->assertWarningContains($result, 'short');
    }

    /**
     * TITLE-006: Optimal title length (50 chars) - no warning
     */
    public function test_optimal_title_no_warning(): void
    {
        $title = str_repeat('a', 50);
        $html = "<html><head><title>{$title}</title></head><body></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals(50, $result->data['title']['length']);
        // Should not have a title-related warning
        $this->assertWarningNotContains($result, 'title');
    }

    /**
     * TITLE-007: Title with HTML entities
     */
    public function test_title_with_entities(): void
    {
        $html = '<html><head><title>Bob &amp; Alice &lt;Test&gt;</title></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals('Bob & Alice <Test>', $result->data['title']['value']);
    }

    /**
     * TITLE-008: Title with leading/trailing whitespace
     */
    public function test_title_with_whitespace(): void
    {
        $html = '<html><head><title>  Title  </title></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals('Title', $result->data['title']['value']);
    }

    // =========================================================================
    // META DESCRIPTION TESTS (DESC)
    // =========================================================================

    /**
     * DESC-001: Standard meta description
     */
    public function test_standard_meta_description(): void
    {
        $html = '<html><head><meta name="description" content="This is a description"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals('This is a description', $result->data['description']['value']);
        $this->assertEquals(19, $result->data['description']['length']);
    }

    /**
     * DESC-002: Missing meta description
     */
    public function test_missing_meta_description(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertNull($result->data['description']['value']);
        $this->assertWarningContains($result, 'Missing meta description');
    }

    /**
     * DESC-003: Long description (161 chars)
     */
    public function test_long_description_warning(): void
    {
        $desc = str_repeat('a', 161);
        $html = "<html><head><meta name=\"description\" content=\"{$desc}\"></head><body></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertWarningContains($result, 'long');
    }

    /**
     * DESC-004: Short description (119 chars)
     */
    public function test_short_description_warning(): void
    {
        $desc = str_repeat('a', 119);
        $html = "<html><head><meta name=\"description\" content=\"{$desc}\"></head><body></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertWarningContains($result, 'short');
    }

    /**
     * DESC-005: Optimal description length (140 chars)
     */
    public function test_optimal_description_no_warning(): void
    {
        $desc = str_repeat('a', 140);
        $html = "<html><head><meta name=\"description\" content=\"{$desc}\"></head><body></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertWarningNotContains($result, 'description');
    }

    /**
     * DESC-006: Description with HTML entities
     */
    public function test_description_with_entities(): void
    {
        $html = '<html><head><meta name="description" content="Bob &amp; Alice"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals('Bob & Alice', $result->data['description']['value']);
    }

    // =========================================================================
    // CANONICAL URL TESTS (CANON)
    // =========================================================================

    /**
     * CANON-001: Standard canonical URL
     */
    public function test_standard_canonical_url(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/page"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com/page');

        $this->assertTrue($result->success);
        $this->assertEquals('https://example.com/page', $result->data['canonical']['value']);
    }

    /**
     * CANON-002: Missing canonical
     */
    public function test_missing_canonical(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com/page');

        $this->assertTrue($result->success);
        $this->assertNull($result->data['canonical']['value']);
    }

    // =========================================================================
    // META ROBOTS TESTS (ROBOTS)
    // =========================================================================

    /**
     * ROBOTS-001: index, follow
     */
    public function test_robots_index_follow(): void
    {
        $html = '<html><head><meta name="robots" content="index, follow"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertFalse($result->data['robots']['isNoIndex']);
        $this->assertFalse($result->data['robots']['isNoFollow']);
    }

    /**
     * ROBOTS-002: noindex
     */
    public function test_robots_noindex(): void
    {
        $html = '<html><head><meta name="robots" content="noindex"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['robots']['isNoIndex']);
    }

    /**
     * ROBOTS-003: nofollow
     */
    public function test_robots_nofollow(): void
    {
        $html = '<html><head><meta name="robots" content="nofollow"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['robots']['isNoFollow']);
    }

    // =========================================================================
    // OPEN GRAPH TESTS (OG)
    // =========================================================================

    /**
     * OG-001: og:title
     */
    public function test_og_title(): void
    {
        $html = '<html><head><meta property="og:title" content="OG Title"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals('OG Title', $result->data['openGraph']['tags']['title']);
    }

    /**
     * OG-002: og:description
     */
    public function test_og_description(): void
    {
        $html = '<html><head><meta property="og:description" content="OG Desc"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals('OG Desc', $result->data['openGraph']['tags']['description']);
    }

    /**
     * OG-003: og:image
     */
    public function test_og_image(): void
    {
        $html = '<html><head><meta property="og:image" content="https://example.com/image.jpg"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertEquals('https://example.com/image.jpg', $result->data['openGraph']['tags']['image']);
    }

    /**
     * OG-004: Missing og:image warning
     */
    public function test_missing_og_image_warning(): void
    {
        $html = '<html><head><meta property="og:title" content="Test"></head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertWarningContains($result, 'Open Graph image');
    }

    // =========================================================================
    // SERP PREVIEW TESTS (SERP)
    // =========================================================================

    /**
     * SERP-001: Standard preview
     */
    public function test_standard_serp_preview(): void
    {
        $html = '<html><head>
            <title>Page Title</title>
            <meta name="description" content="This is a description for the page.">
        </head><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com/page');

        $this->assertTrue($result->success);
        $this->assertEquals('Page Title', $result->data['serpPreview']['title']);
        $this->assertStringContainsString('example.com', $result->data['serpPreview']['url']);
        $this->assertEquals('This is a description for the page.', $result->data['serpPreview']['description']);
    }

    /**
     * SERP-002: Title truncation
     */
    public function test_title_truncation_in_preview(): void
    {
        $longTitle = str_repeat('a', 70);
        $html = "<html><head><title>{$longTitle}</title></head><body></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertLessThanOrEqual(63, strlen($result->data['serpPreview']['title']));
        $this->assertTrue($result->data['serpPreview']['titleTruncated']);
    }

    /**
     * SERP-003: Description truncation
     */
    public function test_description_truncation_in_preview(): void
    {
        $longDesc = str_repeat('a', 200);
        $html = "<html><head><meta name=\"description\" content=\"{$longDesc}\"></head><body></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertTrue($result->success);
        $this->assertLessThanOrEqual(163, strlen($result->data['serpPreview']['description']));
        $this->assertTrue($result->data['serpPreview']['descriptionTruncated']);
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /**
     * ERR-001: Invalid URL
     */
    public function test_invalid_url(): void
    {
        $result = $this->analyzer->analyze('not-a-valid-url');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
    }

    /**
     * ERR-002: Connection failure
     */
    public function test_connection_failure(): void
    {
        Http::fake([
            '*' => Http::failedConnection('Connection refused'),
        ]);

        $result = $this->analyzer->analyze('https://nonexistent.example.com');

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
    }

    /**
     * ERR-003: HTTP error response
     */
    public function test_http_error_response(): void
    {
        Http::fake([
            '*' => Http::response('<html><body>Not Found</body></html>', 404),
        ]);

        $result = $this->analyzer->analyze('https://example.com/notfound');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('404', $result->error);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function assertWarningContains(ToolResult $result, string $text): void
    {
        $this->assertTrue($result->success, 'Result must be successful to check warnings');
        
        $found = false;
        foreach ($result->data['warnings'] as $warning) {
            if (stripos($warning['message'] ?? '', $text) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Expected warning containing '{$text}' not found");
    }

    protected function assertWarningNotContains(ToolResult $result, string $text): void
    {
        if (!$result->success) {
            return; // No warnings to check if result failed
        }
        
        foreach ($result->data['warnings'] as $warning) {
            $this->assertStringNotContainsStringIgnoringCase($text, $warning['message'] ?? '');
        }
    }
}
