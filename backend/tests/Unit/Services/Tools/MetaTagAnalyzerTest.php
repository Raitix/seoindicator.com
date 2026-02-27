<?php

namespace Tests\Unit\Services\Tools;

use Tests\TestCase;
use App\Services\Tools\Analyzers\MetaTagAnalyzer;
use App\Services\Tools\DTO\MetaTagResult;

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
 * @see \App\Services\Tools\Analyzers\MetaTagAnalyzer
 */
class MetaTagAnalyzerTest extends TestCase
{
    protected MetaTagAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new MetaTagAnalyzer();
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
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Page Title', $result->title['value']);
        $this->assertEquals(10, $result->title['length']);
    }

    /**
     * TITLE-002: Missing title
     */
    public function test_missing_title(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertNull($result->title['value']);
        $this->assertWarningContains($result, 'Missing title tag');
    }

    /**
     * TITLE-003: Empty title
     */
    public function test_empty_title(): void
    {
        $html = '<!DOCTYPE html><html><head><title></title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('', $result->title['value']);
        $this->assertWarningContains($result, 'empty');
    }

    /**
     * TITLE-004: Long title (65 chars) - too long warning
     */
    public function test_long_title_warning(): void
    {
        $title = str_repeat('a', 65);
        $html = "<html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals(65, $result->title['length']);
        $this->assertNotNull($result->title['warning']);
        $this->assertStringContainsString('long', strtolower($result->title['warning']));
    }

    /**
     * TITLE-005: Short title (29 chars) - too short warning
     */
    public function test_short_title_warning(): void
    {
        $title = str_repeat('a', 29);
        $html = "<html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals(29, $result->title['length']);
        $this->assertNotNull($result->title['warning']);
        $this->assertStringContainsString('short', strtolower($result->title['warning']));
    }

    /**
     * TITLE-006: Optimal title length (50 chars) - no warning
     */
    public function test_optimal_title_no_warning(): void
    {
        $title = str_repeat('a', 50);
        $html = "<html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals(50, $result->title['length']);
        $this->assertNull($result->title['warning']);
    }

    /**
     * TITLE-007: Duplicate title tags
     */
    public function test_duplicate_title_tags(): void
    {
        $html = '<html><head><title>First</title><title>Second</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertWarningContains($result, 'Multiple title tags');
    }

    /**
     * TITLE-008: Title with HTML entities
     */
    public function test_title_with_entities(): void
    {
        $html = '<html><head><title>Bob &amp; Alice &lt;Test&gt;</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Bob & Alice <Test>', $result->title['value']);
    }

    /**
     * TITLE-009: Title in body (still extracted)
     */
    public function test_title_in_body(): void
    {
        $html = '<html><head></head><body><title>Body Title</title></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        // Should still find title even if in body
        $this->assertEquals('Body Title', $result->title['value']);
    }

    /**
     * TITLE-010: Title with leading/trailing whitespace
     */
    public function test_title_with_whitespace(): void
    {
        $html = '<html><head><title>  Title  </title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Title', $result->title['value']);
    }

    /**
     * TITLE-011: Title with newlines
     */
    public function test_title_with_newlines(): void
    {
        $html = "<html><head><title>Title\nMore</title></head><body></body></html>";
        $result = $this->analyzer->analyze($html, 'https://example.com');

        // Should normalize whitespace
        $this->assertEquals('Title More', $result->title['value']);
    }

    /**
     * TITLE-012: Title length calculation
     */
    public function test_title_length_calculation(): void
    {
        $html = '<html><head><title>Test Title</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals(10, $result->title['length']);
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
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('This is a description', $result->description['value']);
        $this->assertEquals(19, $result->description['length']);
    }

    /**
     * DESC-002: Missing meta description
     */
    public function test_missing_meta_description(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertNull($result->description['value']);
        $this->assertWarningContains($result, 'Missing meta description');
    }

    /**
     * DESC-003: Empty meta description
     */
    public function test_empty_meta_description(): void
    {
        $html = '<html><head><meta name="description" content=""></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('', $result->description['value']);
        $this->assertWarningContains($result, 'empty');
    }

    /**
     * DESC-004: Long description (161 chars)
     */
    public function test_long_description_warning(): void
    {
        $desc = str_repeat('a', 161);
        $html = "<html><head><meta name=\"description\" content=\"{$desc}\"></head><body></body></html>";
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertNotNull($result->description['warning']);
        $this->assertStringContainsString('long', strtolower($result->description['warning']));
    }

    /**
     * DESC-005: Short description (119 chars)
     */
    public function test_short_description_warning(): void
    {
        $desc = str_repeat('a', 119);
        $html = "<html><head><meta name=\"description\" content=\"{$desc}\"></head><body></body></html>";
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertNotNull($result->description['warning']);
        $this->assertStringContainsString('short', strtolower($result->description['warning']));
    }

    /**
     * DESC-006: Optimal description length (140 chars)
     */
    public function test_optimal_description_no_warning(): void
    {
        $desc = str_repeat('a', 140);
        $html = "<html><head><meta name=\"description\" content=\"{$desc}\"></head><body></body></html>";
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertNull($result->description['warning']);
    }

    /**
     * DESC-007: Duplicate meta descriptions
     */
    public function test_duplicate_meta_descriptions(): void
    {
        $html = '<html><head><meta name="description" content="First"><meta name="description" content="Second"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertWarningContains($result, 'Multiple meta descriptions');
    }

    /**
     * DESC-008: Description with quotes
     */
    public function test_description_with_quotes(): void
    {
        $html = '<html><head><meta name="description" content="He said &quot;Hello&quot;"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertStringContainsString('"Hello"', $result->description['value']);
    }

    /**
     * DESC-009: Description with HTML entities
     */
    public function test_description_with_entities(): void
    {
        $html = '<html><head><meta name="description" content="Bob &amp; Alice"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Bob & Alice', $result->description['value']);
    }

    /**
     * DESC-010: og:description fallback
     */
    public function test_og_description_fallback(): void
    {
        $html = '<html><head><meta property="og:description" content="OG Desc"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        // Should use og:description as fallback
        $this->assertEquals('OG Desc', $result->description['value']);
    }

    /**
     * DESC-011: Case-insensitive meta name
     */
    public function test_case_insensitive_meta_name(): void
    {
        $html = '<html><head><META NAME="DESCRIPTION" CONTENT="Test"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Test', $result->description['value']);
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
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        $this->assertEquals('https://example.com/page', $result->canonical);
    }

    /**
     * CANON-002: Missing canonical
     */
    public function test_missing_canonical(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        $this->assertNull($result->canonical);
    }

    /**
     * CANON-003: Relative canonical URL
     */
    public function test_relative_canonical_url(): void
    {
        $html = '<html><head><link rel="canonical" href="/page"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        $this->assertEquals('https://example.com/page', $result->canonical);
    }

    /**
     * CANON-004: Invalid canonical URL
     */
    public function test_invalid_canonical_url(): void
    {
        $html = '<html><head><link rel="canonical" href="not-a-url"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        // Should handle gracefully
        $this->assertNull($result->canonical);
    }

    /**
     * CANON-005: Multiple canonical tags
     */
    public function test_multiple_canonical_tags(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/a"><link rel="canonical" href="https://example.com/b"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        $this->assertWarningContains($result, 'Multiple canonical');
    }

    /**
     * CANON-006: Self-referencing canonical
     */
    public function test_self_referencing_canonical(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/page"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        $this->assertEquals('https://example.com/page', $result->canonical);
        // Should not have warning for self-reference
        $this->assertWarningNotContains($result, 'canonical');
    }

    /**
     * CANON-007: Cross-domain canonical
     */
    public function test_cross_domain_canonical(): void
    {
        $html = '<html><head><link rel="canonical" href="https://other.com/page"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        $this->assertEquals('https://other.com/page', $result->canonical);
        // Should note cross-domain canonical
        $this->assertWarningContains($result, 'different domain');
    }

    /**
     * CANON-008: Canonical in body
     */
    public function test_canonical_in_body(): void
    {
        $html = '<html><head></head><body><link rel="canonical" href="https://example.com/page"></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        $this->assertEquals('https://example.com/page', $result->canonical);
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
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertFalse($result->robots['noindex']);
        $this->assertFalse($result->robots['nofollow']);
    }

    /**
     * ROBOTS-002: noindex
     */
    public function test_robots_noindex(): void
    {
        $html = '<html><head><meta name="robots" content="noindex"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertTrue($result->robots['noindex']);
    }

    /**
     * ROBOTS-003: nofollow
     */
    public function test_robots_nofollow(): void
    {
        $html = '<html><head><meta name="robots" content="nofollow"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertTrue($result->robots['nofollow']);
    }

    /**
     * ROBOTS-004: none (noindex + nofollow)
     */
    public function test_robots_none(): void
    {
        $html = '<html><head><meta name="robots" content="none"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertTrue($result->robots['noindex']);
        $this->assertTrue($result->robots['nofollow']);
    }

    /**
     * ROBOTS-005: noarchive
     */
    public function test_robots_noarchive(): void
    {
        $html = '<html><head><meta name="robots" content="noarchive"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertTrue($result->robots['noarchive']);
    }

    /**
     * ROBOTS-006: Multiple directives
     */
    public function test_robots_multiple_directives(): void
    {
        $html = '<html><head><meta name="robots" content="noindex, nofollow, noarchive"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertTrue($result->robots['noindex']);
        $this->assertTrue($result->robots['nofollow']);
        $this->assertTrue($result->robots['noarchive']);
    }

    /**
     * ROBOTS-007: Case insensitive
     */
    public function test_robots_case_insensitive(): void
    {
        $html = '<html><head><meta name="robots" content="NOINDEX, Follow"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertTrue($result->robots['noindex']);
        $this->assertFalse($result->robots['nofollow']);
    }

    /**
     * ROBOTS-008: Missing robots meta (default index, follow)
     */
    public function test_robots_default(): void
    {
        $html = '<html><head></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertFalse($result->robots['noindex']);
        $this->assertFalse($result->robots['nofollow']);
    }

    /**
     * ROBOTS-009: X-Robots-Tag header (simulated)
     */
    public function test_x_robots_tag_header(): void
    {
        $html = '<html><head></head><body></body></html>';
        $headers = ['X-Robots-Tag' => 'noindex'];
        $result = $this->analyzer->analyze($html, 'https://example.com', $headers);

        $this->assertTrue($result->robots['noindex']);
    }

    /**
     * ROBOTS-010: Conflicting directives (merged)
     */
    public function test_robots_conflicting_directives(): void
    {
        $html = '<html><head><meta name="robots" content="index"><meta name="robots" content="noindex"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        // Most restrictive wins
        $this->assertTrue($result->robots['noindex']);
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
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('OG Title', $result->openGraph['title']);
    }

    /**
     * OG-002: og:description
     */
    public function test_og_description(): void
    {
        $html = '<html><head><meta property="og:description" content="OG Desc"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('OG Desc', $result->openGraph['description']);
    }

    /**
     * OG-003: og:image
     */
    public function test_og_image(): void
    {
        $html = '<html><head><meta property="og:image" content="https://example.com/image.jpg"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('https://example.com/image.jpg', $result->openGraph['image']);
    }

    /**
     * OG-004: og:url
     */
    public function test_og_url(): void
    {
        $html = '<html><head><meta property="og:url" content="https://example.com/page"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('https://example.com/page', $result->openGraph['url']);
    }

    /**
     * OG-005: og:type
     */
    public function test_og_type(): void
    {
        $html = '<html><head><meta property="og:type" content="article"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('article', $result->openGraph['type']);
    }

    /**
     * OG-006: og:site_name
     */
    public function test_og_site_name(): void
    {
        $html = '<html><head><meta property="og:site_name" content="Example Site"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Example Site', $result->openGraph['siteName']);
    }

    /**
     * OG-007: Missing og:image warning
     */
    public function test_missing_og_image_warning(): void
    {
        $html = '<html><head><meta property="og:title" content="Test"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertWarningContains($result, 'Open Graph image');
    }

    /**
     * OG-008: og:image dimensions
     */
    public function test_og_image_dimensions(): void
    {
        $html = '<html><head>
            <meta property="og:image" content="https://example.com/image.jpg">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
        </head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals(1200, $result->openGraph['imageWidth']);
        $this->assertEquals(630, $result->openGraph['imageHeight']);
    }

    /**
     * OG-009: Multiple og:image
     */
    public function test_multiple_og_images(): void
    {
        $html = '<html><head>
            <meta property="og:image" content="https://example.com/image1.jpg">
            <meta property="og:image" content="https://example.com/image2.jpg">
        </head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertIsArray($result->openGraph['images']);
        $this->assertCount(2, $result->openGraph['images']);
    }

    /**
     * OG-010: og:locale
     */
    public function test_og_locale(): void
    {
        $html = '<html><head><meta property="og:locale" content="en_US"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('en_US', $result->openGraph['locale']);
    }

    /**
     * OG-011: Article type extra properties
     */
    public function test_article_properties(): void
    {
        $html = '<html><head>
            <meta property="og:type" content="article">
            <meta property="article:published_time" content="2026-02-27T00:00:00Z">
            <meta property="article:author" content="John Doe">
        </head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('article', $result->openGraph['type']);
        $this->assertEquals('2026-02-27T00:00:00Z', $result->openGraph['articlePublishedTime']);
    }

    /**
     * OG-012: Invalid og:image URL
     */
    public function test_invalid_og_image_url(): void
    {
        $html = '<html><head><meta property="og:image" content="not-a-url"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertWarningContains($result, 'invalid');
    }

    /**
     * OG-013: Relative og:image URL
     */
    public function test_relative_og_image_url(): void
    {
        $html = '<html><head><meta property="og:image" content="/image.jpg"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('https://example.com/image.jpg', $result->openGraph['image']);
    }

    /**
     * OG-014: Fallback from meta title
     */
    public function test_og_title_fallback(): void
    {
        $html = '<html><head><title>Page Title</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        // og:title should fall back to title
        $this->assertEquals('Page Title', $result->openGraph['title']);
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
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        $this->assertEquals('Page Title', $result->serpPreview['title']);
        $this->assertEquals('example.com/page', $result->serpPreview['url']);
        $this->assertEquals('This is a description for the page.', $result->serpPreview['description']);
    }

    /**
     * SERP-002: Title truncation
     */
    public function test_title_truncation_in_preview(): void
    {
        $longTitle = str_repeat('a', 70);
        $html = "<html><head><title>{$longTitle}</title></head><body></body></html>";
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertLessThanOrEqual(63, strlen($result->serpPreview['title']));
        $this->assertStringEndsWith('...', $result->serpPreview['title']);
    }

    /**
     * SERP-003: Description truncation
     */
    public function test_description_truncation_in_preview(): void
    {
        $longDesc = str_repeat('a', 200);
        $html = "<html><head><meta name=\"description\" content=\"{$longDesc}\"></head><body></body></html>";
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertLessThanOrEqual(163, strlen($result->serpPreview['description']));
        $this->assertStringEndsWith('...', $result->serpPreview['description']);
    }

    /**
     * SERP-004: URL display format
     */
    public function test_url_display_format(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://www.example.com/page');

        // Should clean up URL for display
        $this->assertStringNotContainsString('https://', $result->serpPreview['url']);
    }

    /**
     * SERP-005: Date in preview
     */
    public function test_date_in_preview(): void
    {
        $html = '<html><head>
            <title>Article Title</title>
            <meta property="article:published_time" content="2026-02-27">
        </head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertNotNull($result->serpPreview['date']);
    }

    /**
     * SERP-006: Missing title fallback
     */
    public function test_missing_title_fallback_in_preview(): void
    {
        $html = '<html><head></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com/page');

        // Should use URL or default text
        $this->assertNotEmpty($result->serpPreview['title']);
    }

    /**
     * SERP-007: Missing description fallback
     */
    public function test_missing_description_fallback_in_preview(): void
    {
        $html = '<html><head><title>Test</title></head><body><p>This is body content that should be used as fallback.</p></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        // Should generate snippet from body
        $this->assertNotEmpty($result->serpPreview['description']);
    }

    /**
     * SERP-008: Rich result indicators
     */
    public function test_rich_result_indicators(): void
    {
        $html = '<html><head>
            <title>Recipe Title</title>
            <script type="application/ld+json">{"@type":"Recipe"}</script>
        </head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertTrue($result->serpPreview['hasRichResults']);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Empty HTML
     */
    public function test_empty_html(): void
    {
        $result = $this->analyzer->analyze('', 'https://example.com');

        $this->assertFalse($result->success);
        $this->assertNotEmpty($result->warnings);
    }

    /**
     * EC-002: Malformed HTML
     */
    public function test_malformed_html(): void
    {
        $html = '<html><title>Test';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Test', $result->title['value']);
    }

    /**
     * EC-003: Non-HTML content
     */
    public function test_non_html_content(): void
    {
        $result = $this->analyzer->analyze('{"json": "data"}', 'https://example.com');

        $this->assertFalse($result->success);
    }

    /**
     * EC-004: Very large page
     */
    public function test_very_large_page(): void
    {
        $largeContent = str_repeat('<p>Content</p>', 100000);
        $html = "<html><head><title>Test</title></head><body>{$largeContent}</body></html>";

        // Should not throw or timeout
        $result = $this->analyzer->analyze($html, 'https://example.com');
        $this->assertEquals('Test', $result->title['value']);
    }

    /**
     * EC-005: Encoding issues
     */
    public function test_encoding_issues(): void
    {
        $html = '<html><head><title>Tëst Ñamé</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertStringContainsString('Test', $result->title['value']);
    }

    /**
     * EC-006: Script in title (XSS prevention)
     */
    public function test_script_in_title(): void
    {
        $html = '<html><head><title><script>alert(1)</script>Title</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertStringNotContainsString('<script>', $result->title['value']);
    }

    /**
     * EC-007: HTML entities in title
     */
    public function test_html_entities_in_title(): void
    {
        $html = '<html><head><title>&lt;Title&gt;</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('<Title>', $result->title['value']);
    }

    /**
     * EC-008: Comments in head
     */
    public function test_comments_in_head(): void
    {
        $html = '<html><head><!-- comment --><title>Test</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Test', $result->title['value']);
    }

    /**
     * EC-009: Title in comment (not extracted)
     */
    public function test_title_in_comment(): void
    {
        $html = '<html><head><!-- <title>Fake</title> --><title>Real</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Real', $result->title['value']);
    }

    /**
     * EC-010: Base tag resolution
     */
    public function test_base_tag_resolution(): void
    {
        $html = '<html><head><base href="https://base.com/"><link rel="canonical" href="/page"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        // Should resolve relative to base or page URL
        $this->assertStringContainsString('/page', $result->canonical);
    }

    /**
     * EC-011: Multiple head sections
     */
    public function test_multiple_head_sections(): void
    {
        $html = '<html><head><title>First</title></head><head><meta name="description" content="Second"></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('First', $result->title['value']);
        $this->assertEquals('Second', $result->description['value']);
    }

    /**
     * EC-012: Title attribute on other elements
     */
    public function test_title_attribute_on_other_elements(): void
    {
        $html = '<html><head><title>Real Title</title></head><body><div title="Div Title"></div></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('Real Title', $result->title['value']);
    }

    /**
     * EC-013: Unicode content
     */
    public function test_unicode_content(): void
    {
        $html = '<html><head><title>🎉 Party Time 🎊</title></head><body></body></html>';
        $result = $this->analyzer->analyze($html, 'https://example.com');

        $this->assertEquals('🎉 Party Time 🎊', $result->title['value']);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function assertWarningContains(MetaTagResult $result, string $text): void
    {
        $found = false;
        foreach ($result->warnings as $warning) {
            if (stripos($warning, $text) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Expected warning containing '{$text}' not found");
    }

    protected function assertWarningNotContains(MetaTagResult $result, string $text): void
    {
        foreach ($result->warnings as $warning) {
            $this->assertStringNotContainsStringIgnoringCase($text, $warning);
        }
    }
}
