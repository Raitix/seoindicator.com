<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\HtmlParserService;

/**
 * Unit tests for HtmlParserService.
 *
 * Tests HTML content parsing to extract SEO-relevant metadata.
 *
 * @see \App\Services\HtmlParserService
 */
class HtmlParserServiceTest extends TestCase
{
    protected HtmlParserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HtmlParserService();
    }

    // =========================================================================
    // BASIC PARSING TESTS
    // =========================================================================

    /**
     * Parse returns success for valid HTML.
     */
    public function test_parse_returns_success_for_valid_html(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test Title</title></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * Parse extracts title correctly.
     */
    public function test_parse_extracts_title_correctly(): void
    {
        $html = '<html><head><title>My Page Title</title></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('My Page Title', $result['data']['title']);
    }

    /**
     * Parse handles missing title.
     */
    public function test_parse_handles_missing_title(): void
    {
        $html = '<html><head></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertNull($result['data']['title']);
    }

    /**
     * Parse extracts meta description.
     */
    public function test_parse_extracts_meta_description(): void
    {
        $html = '<html><head><meta name="description" content="This is a description"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('This is a description', $result['data']['description']);
    }

    /**
     * Parse handles missing meta description.
     */
    public function test_parse_handles_missing_meta_description(): void
    {
        $html = '<html><head></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertNull($result['data']['description']);
    }

    /**
     * Parse extracts meta keywords.
     */
    public function test_parse_extracts_meta_keywords(): void
    {
        $html = '<html><head><meta name="keywords" content="keyword1, keyword2, keyword3"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals(['keyword1', 'keyword2', 'keyword3'], $result['data']['keywords']);
    }

    /**
     * Parse handles missing meta keywords.
     */
    public function test_parse_handles_missing_meta_keywords(): void
    {
        $html = '<html><head></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals([], $result['data']['keywords']);
    }

    /**
     * Parse extracts canonical URL.
     */
    public function test_parse_extracts_canonical_url(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/page"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('https://example.com/page', $result['data']['canonical']);
    }

    /**
     * Parse extracts viewport meta.
     */
    public function test_parse_extracts_viewport_meta(): void
    {
        $html = '<html><head><meta name="viewport" content="width=device-width, initial-scale=1"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('width=device-width, initial-scale=1', $result['data']['viewport']);
    }

    /**
     * Parse extracts robots meta.
     */
    public function test_parse_extracts_robots_meta(): void
    {
        $html = '<html><head><meta name="robots" content="index, follow"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('index, follow', $result['data']['robots']);
    }

    // =========================================================================
    // OPEN GRAPH TESTS
    // =========================================================================

    /**
     * Parse extracts Open Graph title.
     */
    public function test_parse_extracts_open_graph_title(): void
    {
        $html = '<html><head><meta property="og:title" content="OG Title"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('OG Title', $result['data']['ogTitle']);
    }

    /**
     * Parse extracts Open Graph description.
     */
    public function test_parse_extracts_open_graph_description(): void
    {
        $html = '<html><head><meta property="og:description" content="OG Description"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('OG Description', $result['data']['ogDescription']);
    }

    /**
     * Parse extracts Open Graph image.
     */
    public function test_parse_extracts_open_graph_image(): void
    {
        $html = '<html><head><meta property="og:image" content="https://example.com/image.jpg"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('https://example.com/image.jpg', $result['data']['ogImage']);
    }

    /**
     * Parse resolves relative Open Graph image URL.
     */
    public function test_parse_resolves_relative_open_graph_image_url(): void
    {
        $html = '<html><head><meta property="og:image" content="/images/og.jpg"></head><body></body></html>';

        $result = $this->service->parse($html, 'https://example.com/page');

        $this->assertEquals('https://example.com/images/og.jpg', $result['data']['ogImage']);
    }

    /**
     * Parse extracts Open Graph URL.
     */
    public function test_parse_extracts_open_graph_url(): void
    {
        $html = '<html><head><meta property="og:url" content="https://example.com/page"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('https://example.com/page', $result['data']['ogUrl']);
    }

    /**
     * Parse extracts Open Graph type.
     */
    public function test_parse_extracts_open_graph_type(): void
    {
        $html = '<html><head><meta property="og:type" content="article"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('article', $result['data']['ogType']);
    }

    /**
     * Parse extracts Open Graph site name.
     */
    public function test_parse_extracts_open_graph_site_name(): void
    {
        $html = '<html><head><meta property="og:site_name" content="My Site"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('My Site', $result['data']['ogSiteName']);
    }

    // =========================================================================
    // TWITTER CARD TESTS
    // =========================================================================

    /**
     * Parse extracts Twitter card type.
     */
    public function test_parse_extracts_twitter_card_type(): void
    {
        $html = '<html><head><meta name="twitter:card" content="summary_large_image"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('summary_large_image', $result['data']['twitterCard']);
    }

    /**
     * Parse extracts Twitter title.
     */
    public function test_parse_extracts_twitter_title(): void
    {
        $html = '<html><head><meta name="twitter:title" content="Twitter Title"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('Twitter Title', $result['data']['twitterTitle']);
    }

    /**
     * Parse extracts Twitter description.
     */
    public function test_parse_extracts_twitter_description(): void
    {
        $html = '<html><head><meta name="twitter:description" content="Twitter Description"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('Twitter Description', $result['data']['twitterDescription']);
    }

    /**
     * Parse extracts Twitter image.
     */
    public function test_parse_extracts_twitter_image(): void
    {
        $html = '<html><head><meta name="twitter:image" content="https://example.com/twitter.jpg"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('https://example.com/twitter.jpg', $result['data']['twitterImage']);
    }

    /**
     * Parse extracts Twitter site.
     */
    public function test_parse_extracts_twitter_site(): void
    {
        $html = '<html><head><meta name="twitter:site" content="@mysite"></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('@mysite', $result['data']['twitterSite']);
    }

    // =========================================================================
    // HEADINGS TESTS
    // =========================================================================

    /**
     * Parse extracts headings.
     */
    public function test_parse_extracts_headings(): void
    {
        $html = '<html><body><h1>Main Title</h1><h2>Subtitle</h2><h3>Section</h3></body></html>';

        $result = $this->service->parse($html);

        $this->assertCount(3, $result['data']['headings']);
        $this->assertEquals('h1', $result['data']['headings'][0]['tag']);
        $this->assertEquals('Main Title', $result['data']['headings'][0]['text']);
    }

    /**
     * Parse handles multiple H1 tags.
     */
    public function test_parse_handles_multiple_h1_tags(): void
    {
        $html = '<html><body><h1>First H1</h1><h1>Second H1</h1></body></html>';

        $result = $this->service->parse($html);

        $h1Headings = array_filter($result['data']['headings'], fn($h) => $h['tag'] === 'h1');
        $this->assertCount(2, $h1Headings);
    }

    /**
     * Parse handles missing headings.
     */
    public function test_parse_handles_missing_headings(): void
    {
        $html = '<html><body><p>Just some text</p></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals([], $result['data']['headings']);
    }

    // =========================================================================
    // IMAGES TESTS
    // =========================================================================

    /**
     * Parse extracts images with alt text.
     */
    public function test_parse_extracts_images_with_alt_text(): void
    {
        $html = '<html><body><img src="https://example.com/image.jpg" alt="An image"></body></html>';

        $result = $this->service->parse($html);

        $this->assertCount(1, $result['data']['images']);
        $this->assertEquals('https://example.com/image.jpg', $result['data']['images'][0]['src']);
        $this->assertEquals('An image', $result['data']['images'][0]['alt']);
        $this->assertTrue($result['data']['images'][0]['hasAlt']);
    }

    /**
     * Parse extracts images without alt text.
     */
    public function test_parse_extracts_images_without_alt_text(): void
    {
        $html = '<html><body><img src="https://example.com/image.jpg"></body></html>';

        $result = $this->service->parse($html);

        $this->assertCount(1, $result['data']['images']);
        $this->assertFalse($result['data']['images'][0]['hasAlt']);
    }

    /**
     * Parse resolves relative image URLs.
     */
    public function test_parse_resolves_relative_image_urls(): void
    {
        $html = '<html><body><img src="/images/photo.jpg" alt="Photo"></body></html>';

        $result = $this->service->parse($html, 'https://example.com/page');

        $this->assertEquals('https://example.com/images/photo.jpg', $result['data']['images'][0]['src']);
    }

    /**
     * Parse handles multiple images.
     */
    public function test_parse_handles_multiple_images(): void
    {
        $html = '<html><body><img src="img1.jpg"><img src="img2.jpg"><img src="img3.jpg"></body></html>';

        $result = $this->service->parse($html);

        $this->assertCount(3, $result['data']['images']);
    }

    // =========================================================================
    // LINKS TESTS
    // =========================================================================

    /**
     * Parse extracts links.
     */
    public function test_parse_extracts_links(): void
    {
        $html = '<html><body><a href="https://example.com/page">Link text</a></body></html>';

        $result = $this->service->parse($html);

        $this->assertCount(1, $result['data']['links']);
        $this->assertEquals('https://example.com/page', $result['data']['links'][0]['href']);
        $this->assertEquals('Link text', $result['data']['links'][0]['text']);
    }

    /**
     * Parse identifies nofollow links.
     */
    public function test_parse_identifies_nofollow_links(): void
    {
        $html = '<html><body><a href="https://example.com" rel="nofollow">Link</a></body></html>';

        $result = $this->service->parse($html);

        $this->assertTrue($result['data']['links'][0]['isNoFollow']);
    }

    /**
     * Parse identifies external links.
     */
    public function test_parse_identifies_external_links(): void
    {
        $html = '<html><body><a href="https://external.com">External</a></body></html>';

        $result = $this->service->parse($html, 'https://mysite.com');

        $this->assertTrue($result['data']['links'][0]['isExternal']);
    }

    /**
     * Parse identifies internal links.
     */
    public function test_parse_identifies_internal_links(): void
    {
        $html = '<html><body><a href="https://mysite.com/page">Internal</a></body></html>';

        $result = $this->service->parse($html, 'https://mysite.com');

        $this->assertFalse($result['data']['links'][0]['isExternal']);
    }

    /**
     * Parse skips anchor links.
     */
    public function test_parse_skips_anchor_links(): void
    {
        $html = '<html><body><a href="#section">Jump</a></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals([], $result['data']['links']);
    }

    /**
     * Parse skips javascript links.
     */
    public function test_parse_skips_javascript_links(): void
    {
        $html = '<html><body><a href="javascript:void(0)">Click</a></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals([], $result['data']['links']);
    }

    /**
     * Parse skips mailto links.
     */
    public function test_parse_skips_mailto_links(): void
    {
        $html = '<html><body><a href="mailto:test@example.com">Email</a></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals([], $result['data']['links']);
    }

    // =========================================================================
    // WORD COUNT TESTS
    // =========================================================================

    /**
     * Parse calculates word count correctly.
     */
    public function test_parse_calculates_word_count_correctly(): void
    {
        $html = '<html><body><p>This is a test paragraph with eight words in it.</p></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals(10, $result['data']['wordCount']);
    }

    /**
     * Parse handles empty content word count.
     */
    public function test_parse_handles_empty_content_word_count(): void
    {
        $html = '<html><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals(0, $result['data']['wordCount']);
    }

    /**
     * Parse strips script content from word count.
     */
    public function test_parse_strips_script_content_from_word_count(): void
    {
        $html = '<html><body><p>Visible text</p><script>var x = "this should not count";</script></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals(2, $result['data']['wordCount']);
    }

    /**
     * Parse strips style content from word count.
     */
    public function test_parse_strips_style_content_from_word_count(): void
    {
        $html = '<html><body><p>Styled text</p><style>.class { color: red; }</style></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals(2, $result['data']['wordCount']);
    }

    // =========================================================================
    // WARNINGS TESTS
    // =========================================================================

    /**
     * Parse generates warning for missing title.
     */
    public function test_parse_generates_warning_for_missing_title(): void
    {
        $html = '<html><head></head><body></body></html>';

        $result = $this->service->parse($html);

        $titleWarning = collect($result['data']['warnings'])->firstWhere('type', 'title_missing');
        $this->assertNotNull($titleWarning);
        $this->assertEquals('critical', $titleWarning['severity']);
    }

    /**
     * Parse generates warning for short title.
     */
    public function test_parse_generates_warning_for_short_title(): void
    {
        $html = '<html><head><title>Hi</title></head><body></body></html>';

        $result = $this->service->parse($html);

        $titleWarning = collect($result['data']['warnings'])->firstWhere('type', 'title_short');
        $this->assertNotNull($titleWarning);
    }

    /**
     * Parse generates warning for long title.
     */
    public function test_parse_generates_warning_for_long_title(): void
    {
        $html = '<html><head><title>' . str_repeat('Very Long Title ', 10) . '</title></head><body></body></html>';

        $result = $this->service->parse($html);

        $titleWarning = collect($result['data']['warnings'])->firstWhere('type', 'title_long');
        $this->assertNotNull($titleWarning);
    }

    /**
     * Parse generates warning for missing description.
     */
    public function test_parse_generates_warning_for_missing_description(): void
    {
        $html = '<html><head><title>Title</title></head><body></body></html>';

        $result = $this->service->parse($html);

        $descWarning = collect($result['data']['warnings'])->firstWhere('type', 'description_missing');
        $this->assertNotNull($descWarning);
    }

    /**
     * Parse generates warning for missing H1.
     */
    public function test_parse_generates_warning_for_missing_h1(): void
    {
        $html = '<html><head><title>Title</title><meta name="description" content="Desc"></head><body></body></html>';

        $result = $this->service->parse($html);

        $h1Warning = collect($result['data']['warnings'])->firstWhere('type', 'h1_missing');
        $this->assertNotNull($h1Warning);
    }

    /**
     * Parse generates warning for multiple H1.
     */
    public function test_parse_generates_warning_for_multiple_h1(): void
    {
        $html = '<html><head><title>Title</title></head><body><h1>First</h1><h1>Second</h1></body></html>';

        $result = $this->service->parse($html);

        $h1Warning = collect($result['data']['warnings'])->firstWhere('type', 'h1_multiple');
        $this->assertNotNull($h1Warning);
    }

    /**
     * Parse generates warning for images without alt.
     */
    public function test_parse_generates_warning_for_images_without_alt(): void
    {
        $html = '<html><head><title>Title</title></head><body><img src="img.jpg"></body></html>';

        $result = $this->service->parse($html);

        $imgWarning = collect($result['data']['warnings'])->firstWhere('type', 'images_missing_alt');
        $this->assertNotNull($imgWarning);
    }

    /**
     * Parse generates warning for missing viewport.
     */
    public function test_parse_generates_warning_for_missing_viewport(): void
    {
        $html = '<html><head><title>Title</title></head><body></body></html>';

        $result = $this->service->parse($html);

        $viewportWarning = collect($result['data']['warnings'])->firstWhere('type', 'viewport_missing');
        $this->assertNotNull($viewportWarning);
    }

    // =========================================================================
    // SIZE LIMIT TESTS
    // =========================================================================

    /**
     * Parse fails for HTML exceeding size limit.
     */
    public function test_parse_fails_for_html_exceeding_size_limit(): void
    {
        $largeHtml = str_repeat('<p>Content</p>', 2000000); // Over 10MB

        $result = $this->service->parse($largeHtml);

        $this->assertFalse($result['success']);
        $this->assertEquals('HTML content exceeds maximum size limit.', $result['error']);
    }

    /**
     * Parse handles large HTML under limit.
     */
    public function test_parse_handles_large_html_under_limit(): void
    {
        // Create HTML under 10MB
        $html = '<html><head><title>Large Page</title></head><body>';
        for ($i = 0; $i < 1000; $i++) {
            $html .= '<p>Paragraph ' . $i . '</p>';
        }
        $html .= '</body></html>';

        $result = $this->service->parse($html);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Parse handles malformed HTML.
     */
    public function test_parse_handles_malformed_html(): void
    {
        $html = '<html><head><title>Test</title><body><p>Unclosed paragraph';

        $result = $this->service->parse($html);

        // Should still parse and extract what it can
        $this->assertTrue($result['success']);
    }

    /**
     * Parse handles empty HTML.
     */
    public function test_parse_handles_empty_html(): void
    {
        $result = $this->service->parse('');

        $this->assertTrue($result['success']);
    }

    /**
     * Parse handles HTML with special characters.
     */
    public function test_parse_handles_html_with_special_characters(): void
    {
        $html = '<html><head><title>Title &amp; More &lt;test&gt;</title></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertStringContainsString('Title', $result['data']['title']);
    }

    /**
     * Parse handles Unicode content.
     */
    public function test_parse_handles_unicode_content(): void
    {
        $html = '<html><head><title>日本語タイトル</title></head><body><p>Привет мир</p></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('日本語タイトル', $result['data']['title']);
    }

    /**
     * Parse handles protocol-relative URLs.
     */
    public function test_parse_handles_protocol_relative_urls(): void
    {
        $html = '<html><body><img src="//example.com/image.jpg" alt="Img"></body></html>';

        $result = $this->service->parse($html, 'https://mysite.com');

        $this->assertEquals('https://example.com/image.jpg', $result['data']['images'][0]['src']);
    }

    /**
     * Parse handles data URIs.
     */
    public function test_parse_handles_data_uris(): void
    {
        $html = '<html><body><img src="data:image/png;base64,abc123" alt="Data"></body></html>';

        $result = $this->service->parse($html);

        $this->assertStringStartsWith('data:', $result['data']['images'][0]['src']);
    }

    /**
     * Parse cleans whitespace from title.
     */
    public function test_parse_cleans_whitespace_from_title(): void
    {
        $html = '<html><head><title>  Title  with   spaces  </title></head><body></body></html>';

        $result = $this->service->parse($html);

        $this->assertEquals('Title with spaces', $result['data']['title']);
    }

    /**
     * Full SEO analysis returns all expected fields.
     */
    public function test_full_seo_analysis_returns_all_expected_fields(): void
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A comprehensive SEO analysis page">
    <meta name="keywords" content="seo, analysis, tools">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://example.com/page">
    <meta property="og:title" content="OG Title">
    <meta property="og:description" content="OG Description">
    <meta property="og:image" content="https://example.com/og.jpg">
    <meta property="og:url" content="https://example.com/page">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Example Site">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Twitter Title">
    <meta name="twitter:description" content="Twitter Description">
    <meta name="twitter:image" content="https://example.com/twitter.jpg">
    <meta name="twitter:site" content="@example">
    <title>Example Page Title</title>
</head>
<body>
    <h1>Main Heading</h1>
    <h2>Subheading</h2>
    <p>This is a paragraph with some text content for word count testing.</p>
    <img src="image.jpg" alt="An image">
    <a href="https://external.com" rel="nofollow">External Link</a>
    <a href="/internal">Internal Link</a>
</body>
</html>';

        $result = $this->service->parse($html, 'https://example.com/page');

        $this->assertTrue($result['success']);
        $this->assertEquals('Example Page Title', $result['data']['title']);
        $this->assertEquals('A comprehensive SEO analysis page', $result['data']['description']);
        $this->assertEquals(['seo', 'analysis', 'tools'], $result['data']['keywords']);
        $this->assertEquals('https://example.com/page', $result['data']['canonical']);
        $this->assertEquals('OG Title', $result['data']['ogTitle']);
        $this->assertEquals('Twitter Title', $result['data']['twitterTitle']);
        $this->assertCount(2, $result['data']['headings']);
        $this->assertCount(1, $result['data']['images']);
        $this->assertCount(2, $result['data']['links']);
        $this->assertGreaterThan(0, $result['data']['wordCount']);
        $this->assertIsArray($result['data']['warnings']);
    }
}
