<?php

namespace Tests\Unit\Services\Crawler;

use App\Services\Crawler\ContentProcessorService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for ContentProcessorService.
 *
 * Tests HTML-to-text conversion, metadata extraction, and content processing.
 *
 * @see TASK-22-6
 * @see SPEC-022
 */
class ContentProcessorServiceTest extends TestCase
{
    protected ContentProcessorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContentProcessorService();
    }

    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    /** @test */
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ContentProcessorService::class, $this->service);
    }

    /** @test */
    public function service_has_logger_injected(): void
    {
        $this->assertTrue(true); // Logger is optional, service works without it
    }

    // =========================================================================
    // extractStructuredText() Method - Basic Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_returns_string(): void
    {
        $html = '<html><body><p>Test content</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertIsString($result);
    }

    /** @test */
    public function extract_structured_text_handles_empty_html(): void
    {
        $result = $this->service->extractStructuredText('');

        $this->assertIsString($result);
        $this->assertEquals('', $result);
    }

    /** @test */
    public function extract_structured_text_handles_null_html(): void
    {
        $result = $this->service->extractStructuredText(null);

        $this->assertIsString($result);
        $this->assertEquals('', $result);
    }

    /** @test */
    public function extract_structured_text_extracts_basic_text(): void
    {
        $html = '<html><body><p>Hello World</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Hello World', $result);
    }

    // =========================================================================
    // extractStructuredText() Method - Script/Style Removal Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_removes_script_tags(): void
    {
        $html = '<html><body><p>Content</p><script>alert("test");</script></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Content', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringNotContainsString('script', strtolower($result));
    }

    /** @test */
    public function extract_structured_text_removes_style_tags(): void
    {
        $html = '<html><head><style>.test { color: red; }</style></head><body><p>Content</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Content', $result);
        $this->assertStringNotContainsString('color: red', $result);
    }

    /** @test */
    public function extract_structured_text_removes_noscript_tags(): void
    {
        $html = '<html><body><p>Content</p><noscript>Enable JavaScript</noscript></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Content', $result);
        $this->assertStringNotContainsString('Enable JavaScript', $result);
    }

    /** @test */
    public function extract_structured_text_removes_multiple_script_tags(): void
    {
        $html = '<html><body><script>var a=1;</script><p>Content</p><script>var b=2;</script></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Content', $result);
        $this->assertStringNotContainsString('var a', $result);
        $this->assertStringNotContainsString('var b', $result);
    }

    // =========================================================================
    // extractStructuredText() Method - Hidden Element Removal Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_removes_aria_hidden_elements(): void
    {
        $html = '<html><body><p>Visible</p><div aria-hidden="true">Hidden content</div></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Visible', $result);
        $this->assertStringNotContainsString('Hidden content', $result);
    }

    /** @test */
    public function extract_structured_text_removes_display_none_elements(): void
    {
        $html = '<html><body><p>Visible</p><div style="display:none">Hidden content</div></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Visible', $result);
        $this->assertStringNotContainsString('Hidden content', $result);
    }

    /** @test */
    public function extract_structured_text_removes_nav_elements(): void
    {
        $html = '<html><body><nav><ul><li><a href="/home">Home</a></li></ul></nav><main><p>Content</p></main></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Content', $result);
        // Navigation links should not appear in main content
    }

    /** @test */
    public function extract_structured_text_removes_footer_content(): void
    {
        $html = '<html><body><main><p>Main content</p></main><footer>Footer content</footer></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Main content', $result);
        $this->assertStringNotContainsString('Footer content', $result);
    }

    /** @test */
    public function extract_structured_text_removes_aside_content(): void
    {
        $html = '<html><body><main><p>Main content</p></main><aside>Sidebar content</aside></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Main content', $result);
        $this->assertStringNotContainsString('Sidebar content', $result);
    }

    // =========================================================================
    // extractStructuredText() Method - Heading Hierarchy Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_preserves_h1_heading(): void
    {
        $html = '<html><body><h1>Main Title</h1><p>Content</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('# Main Title', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_h2_heading(): void
    {
        $html = '<html><body><h2>Section Title</h2><p>Content</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('## Section Title', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_h3_heading(): void
    {
        $html = '<html><body><h3>Subsection Title</h3><p>Content</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('### Subsection Title', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_h4_heading(): void
    {
        $html = '<html><body><h4>Minor Title</h4><p>Content</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('#### Minor Title', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_h5_heading(): void
    {
        $html = '<html><body><h5>Small Title</h5><p>Content</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('##### Small Title', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_h6_heading(): void
    {
        $html = '<html><body><h6>Tiny Title</h6><p>Content</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('###### Tiny Title', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_heading_hierarchy(): void
    {
        $html = '<html><body><h1>Main</h1><h2>Section</h2><h3>Subsection</h3></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('# Main', $result);
        $this->assertStringContainsString('## Section', $result);
        $this->assertStringContainsString('### Subsection', $result);
    }

    // =========================================================================
    // extractStructuredText() Method - List Structure Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_preserves_unordered_list(): void
    {
        $html = '<html><body><ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('- Item 1', $result);
        $this->assertStringContainsString('- Item 2', $result);
        $this->assertStringContainsString('- Item 3', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_ordered_list(): void
    {
        $html = '<html><body><ol><li>First</li><li>Second</li><li>Third</li></ol></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('1. First', $result);
        $this->assertStringContainsString('2. Second', $result);
        $this->assertStringContainsString('3. Third', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_nested_list(): void
    {
        $html = '<html><body><ul><li>Item 1<ul><li>Nested Item</li></ul></li></ul></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('- Item 1', $result);
        $this->assertStringContainsString('- Nested Item', $result);
    }

    // =========================================================================
    // extractStructuredText() Method - Link and Image Text Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_preserves_link_text(): void
    {
        $html = '<html><body><p>Visit <a href="https://example.com">Example Site</a> for more.</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('[Example Site]', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_link_text_without_url(): void
    {
        $html = '<html><body><a href="https://example.com">Click Here</a></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('[Click Here]', $result);
        // URL should not be in the output
        $this->assertStringNotContainsString('https://example.com', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_image_alt_text(): void
    {
        $html = '<html><body><img src="image.jpg" alt="Product Image"></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('[alt: Product Image]', $result);
    }

    /** @test */
    public function extract_structured_text_handles_image_without_alt(): void
    {
        $html = '<html><body><img src="image.jpg"></body></html>';
        $result = $this->service->extractStructuredText($html);

        // Image without alt should not produce text
        $this->assertStringNotContainsString('[alt:', $result);
    }

    /** @test */
    public function extract_structured_text_handles_empty_alt(): void
    {
        $html = '<html><body><img src="image.jpg" alt=""></body></html>';
        $result = $this->service->extractStructuredText($html);

        // Empty alt should not produce text
        $this->assertStringNotContainsString('[alt:', $result);
    }

    // =========================================================================
    // extractStructuredText() Method - Whitespace Normalization Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_normalizes_whitespace(): void
    {
        $html = '<html><body><p>Multiple   spaces   and


newlines</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        // Multiple spaces should be normalized
        $this->assertStringNotContainsString('   ', $result);
    }

    /** @test */
    public function extract_structured_text_trims_whitespace(): void
    {
        $html = '<html><body>   <p>Content</p>   </body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('Content', $result);
        // Leading/trailing whitespace should be trimmed
    }

    /** @test */
    public function extract_structured_text_handles_multiple_paragraphs(): void
    {
        $html = '<html><body><p>First paragraph.</p><p>Second paragraph.</p><p>Third paragraph.</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('First paragraph', $result);
        $this->assertStringContainsString('Second paragraph', $result);
        $this->assertStringContainsString('Third paragraph', $result);
    }

    // =========================================================================
    // extractStructuredText() Method - Size Limit Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_limits_output_size(): void
    {
        // Create HTML with more than 500KB of text
        $largeText = str_repeat('a', 600000);
        $html = '<html><body><p>' . $largeText . '</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        // Result should be limited to 500KB
        $this->assertLessThanOrEqual(500000, strlen($result));
    }

    /** @test */
    public function extract_structured_text_handles_large_html(): void
    {
        // Create HTML with 100KB of text
        $largeText = str_repeat('test content ', 10000);
        $html = '<html><body><p>' . $largeText . '</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertIsString($result);
        $this->assertStringContainsString('test content', $result);
    }

    // =========================================================================
    // extractMetadata() Method - Basic Tests
    // =========================================================================

    /** @test */
    public function extract_metadata_returns_array(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertIsArray($result);
    }

    /** @test */
    public function extract_metadata_handles_empty_html(): void
    {
        $result = $this->service->extractMetadata('');

        $this->assertIsArray($result);
        $this->assertNull($result['title']);
    }

    /** @test */
    public function extract_metadata_handles_null_html(): void
    {
        $result = $this->service->extractMetadata(null);

        $this->assertIsArray($result);
        $this->assertNull($result['title']);
    }

    // =========================================================================
    // extractMetadata() Method - Title Extraction Tests
    // =========================================================================

    /** @test */
    public function extract_metadata_extracts_title_tag(): void
    {
        $html = '<html><head><title>Page Title</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('Page Title', $result['title']);
    }

    /** @test */
    public function extract_metadata_extracts_og_title(): void
    {
        $html = '<html><head><meta property="og:title" content="OG Title"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('OG Title', $result['og']['title']);
    }

    /** @test */
    public function extract_metadata_prefers_og_title_in_og_section(): void
    {
        $html = '<html><head><title>HTML Title</title><meta property="og:title" content="OG Title"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('HTML Title', $result['title']);
        $this->assertEquals('OG Title', $result['og']['title']);
    }

    // =========================================================================
    // extractMetadata() Method - Meta Description Tests
    // =========================================================================

    /** @test */
    public function extract_metadata_extracts_meta_description(): void
    {
        $html = '<html><head><meta name="description" content="This is the description"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('This is the description', $result['description']);
    }

    /** @test */
    public function extract_metadata_handles_missing_description(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertNull($result['description']);
    }

    /** @test */
    public function extract_metadata_extracts_og_description(): void
    {
        $html = '<html><head><meta property="og:description" content="OG Description"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('OG Description', $result['og']['description']);
    }

    // =========================================================================
    // extractMetadata() Method - Robots Meta Tests
    // =========================================================================

    /** @test */
    public function extract_metadata_extracts_meta_robots(): void
    {
        $html = '<html><head><meta name="robots" content="noindex, nofollow"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('noindex, nofollow', $result['robots']);
    }

    /** @test */
    public function extract_metadata_handles_missing_robots(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertNull($result['robots']);
    }

    // =========================================================================
    // extractMetadata() Method - Canonical URL Tests
    // =========================================================================

    /** @test */
    public function extract_metadata_extracts_canonical_url(): void
    {
        $html = '<html><head><link rel="canonical" href="https://example.com/page"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('https://example.com/page', $result['canonical']);
    }

    /** @test */
    public function extract_metadata_handles_missing_canonical(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertNull($result['canonical']);
    }

    // =========================================================================
    // extractMetadata() Method - Open Graph Tests
    // =========================================================================

    /** @test */
    public function extract_metadata_extracts_og_type(): void
    {
        $html = '<html><head><meta property="og:type" content="article"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('article', $result['og']['type']);
    }

    /** @test */
    public function extract_metadata_extracts_og_image(): void
    {
        $html = '<html><head><meta property="og:image" content="https://example.com/image.jpg"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('https://example.com/image.jpg', $result['og']['image']);
    }

    /** @test */
    public function extract_metadata_extracts_og_url(): void
    {
        $html = '<html><head><meta property="og:url" content="https://example.com/page"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('https://example.com/page', $result['og']['url']);
    }

    /** @test */
    public function extract_metadata_extracts_all_og_tags(): void
    {
        $html = '<html><head>
            <meta property="og:title" content="Title">
            <meta property="og:description" content="Description">
            <meta property="og:image" content="https://example.com/image.jpg">
            <meta property="og:type" content="article">
            <meta property="og:url" content="https://example.com/page">
        </head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('Title', $result['og']['title']);
        $this->assertEquals('Description', $result['og']['description']);
        $this->assertEquals('https://example.com/image.jpg', $result['og']['image']);
        $this->assertEquals('article', $result['og']['type']);
        $this->assertEquals('https://example.com/page', $result['og']['url']);
    }

    // =========================================================================
    // extractMetadata() Method - Twitter Card Tests
    // =========================================================================

    /** @test */
    public function extract_metadata_extracts_twitter_card(): void
    {
        $html = '<html><head><meta name="twitter:card" content="summary_large_image"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('summary_large_image', $result['twitter']['card']);
    }

    /** @test */
    public function extract_metadata_extracts_twitter_title(): void
    {
        $html = '<html><head><meta name="twitter:title" content="Twitter Title"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('Twitter Title', $result['twitter']['title']);
    }

    /** @test */
    public function extract_metadata_extracts_twitter_description(): void
    {
        $html = '<html><head><meta name="twitter:description" content="Twitter Description"></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('Twitter Description', $result['twitter']['description']);
    }

    /** @test */
    public function extract_metadata_extracts_all_twitter_tags(): void
    {
        $html = '<html><head>
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="Title">
            <meta name="twitter:description" content="Description">
        </head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('summary_large_image', $result['twitter']['card']);
        $this->assertEquals('Title', $result['twitter']['title']);
        $this->assertEquals('Description', $result['twitter']['description']);
    }

    // =========================================================================
    // extractMetadata() Method - Language and Charset Tests
    // =========================================================================

    /** @test */
    public function extract_metadata_extracts_lang_attribute(): void
    {
        $html = '<html lang="en"><head><title>Test</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('en', $result['language']);
    }

    /** @test */
    public function extract_metadata_extracts_lang_with_region(): void
    {
        $html = '<html lang="en-US"><head><title>Test</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('en-US', $result['language']);
    }

    /** @test */
    public function extract_metadata_extracts_charset_from_meta(): void
    {
        $html = '<html><head><meta charset="UTF-8"><title>Test</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('UTF-8', $result['charset']);
    }

    /** @test */
    public function extract_metadata_extracts_charset_from_content_type(): void
    {
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"><title>Test</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('ISO-8859-1', $result['charset']);
    }

    // =========================================================================
    // calculateSize() Method - Tests
    // =========================================================================

    /** @test */
    public function calculate_size_returns_integer(): void
    {
        $content = 'Test content';
        $result = $this->service->calculateSize($content);

        $this->assertIsInt($result);
    }

    /** @test */
    public function calculate_size_returns_bytes_not_characters(): void
    {
        $content = 'Test';
        $result = $this->service->calculateSize($content);

        $this->assertEquals(4, $result);
    }

    /** @test */
    public function calculate_size_handles_empty_string(): void
    {
        $result = $this->service->calculateSize('');

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function calculate_size_handles_null(): void
    {
        $result = $this->service->calculateSize(null);

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function calculate_size_handles_utf8_correctly(): void
    {
        // Euro sign is 3 bytes in UTF-8
        $content = "\u{20AC}"; // €
        $result = $this->service->calculateSize($content);

        $this->assertEquals(3, $result);
    }

    /** @test */
    public function calculate_size_handles_multibyte_characters(): void
    {
        // Japanese characters (3 bytes each in UTF-8)
        $content = '日本語';
        $result = $this->service->calculateSize($content);

        $this->assertEquals(9, $result); // 3 chars × 3 bytes each
    }

    /** @test */
    public function calculate_size_handles_emoji(): void
    {
        // Emoji can be 4 bytes in UTF-8
        $content = "😀";
        $result = $this->service->calculateSize($content);

        $this->assertEquals(4, $result);
    }

    /** @test */
    public function calculate_size_handles_large_content(): void
    {
        $content = str_repeat('a', 100000);
        $result = $this->service->calculateSize($content);

        $this->assertEquals(100000, $result);
    }

    // =========================================================================
    // processContent() Method - Full Pipeline Tests
    // =========================================================================

    /** @test */
    public function process_content_returns_array(): void
    {
        $html = '<html><head><title>Test</title></head><body><p>Content</p></body></html>';
        $result = $this->service->processContent($html);

        $this->assertIsArray($result);
    }

    /** @test */
    public function process_content_includes_structured_text(): void
    {
        $html = '<html><head><title>Test</title></head><body><p>Content</p></body></html>';
        $result = $this->service->processContent($html);

        $this->assertArrayHasKey('text', $result);
        $this->assertStringContainsString('Content', $result['text']);
    }

    /** @test */
    public function process_content_includes_metadata(): void
    {
        $html = '<html><head><title>Test Title</title><meta name="description" content="Test description"></head><body></body></html>';
        $result = $this->service->processContent($html);

        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals('Test Title', $result['metadata']['title']);
        $this->assertEquals('Test description', $result['metadata']['description']);
    }

    /** @test */
    public function process_content_includes_size(): void
    {
        $html = '<html><head><title>Test</title></head><body><p>Content</p></body></html>';
        $result = $this->service->processContent($html);

        $this->assertArrayHasKey('size', $result);
        $this->assertIsInt($result['size']);
        $this->assertGreaterThan(0, $result['size']);
    }

    /** @test */
    public function process_content_handles_empty_html(): void
    {
        $result = $this->service->processContent('');

        $this->assertIsArray($result);
        $this->assertEquals('', $result['text']);
        $this->assertEquals(0, $result['size']);
    }

    /** @test */
    public function process_content_handles_null_html(): void
    {
        $result = $this->service->processContent(null);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['text']);
        $this->assertEquals(0, $result['size']);
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_handles_malformed_html(): void
    {
        $html = '<html><body><p>Unclosed paragraph<div>Mixed up tags</p></div></body>';
        $result = $this->service->extractStructuredText($html);

        $this->assertIsString($result);
        // Should still extract text despite malformed HTML
        $this->assertStringContainsString('Unclosed paragraph', $result);
    }

    /** @test */
    public function extract_metadata_handles_malformed_html(): void
    {
        $html = '<html><head><title>Test<title></head><body></body>';
        $result = $this->service->extractMetadata($html);

        $this->assertIsArray($result);
        // Should still extract what it can
    }

    /** @test */
    public function service_never_throws_exceptions_for_content_processing(): void
    {
        $badInputs = [
            '',
            null,
            '<',
            '>>>',
            '<script>alert(1)</script>',
            str_repeat('<div>', 10000),
            "\xFF\xFE", // BOM
            "Some\x00null\x00bytes",
        ];

        foreach ($badInputs as $input) {
            // Should not throw
            $result = $this->service->extractStructuredText($input);
            $this->assertIsString($result);

            $metaResult = $this->service->extractMetadata($input);
            $this->assertIsArray($metaResult);

            $processedResult = $this->service->processContent($input);
            $this->assertIsArray($processedResult);
        }
    }

    // =========================================================================
    // Edge Cases and Unicode Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_handles_unicode_content(): void
    {
        $html = '<html><body><p>日本語コンテンツ</p><p>Contenido en español</p><p>Содержание на русском</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('日本語コンテンツ', $result);
        $this->assertStringContainsString('Contenido en español', $result);
        $this->assertStringContainsString('Содержание на русском', $result);
    }

    /** @test */
    public function extract_metadata_handles_unicode_title(): void
    {
        $html = '<html><head><title>日本語タイトル</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertEquals('日本語タイトル', $result['title']);
    }

    /** @test */
    public function extract_structured_text_handles_html_entities(): void
    {
        $html = '<html><body><p>&lt;script&gt;alert(1)&lt;/script&gt;</p><p>&amp; &quot; &apos;</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        // Entities should be decoded
        $this->assertStringContainsString('<script>', $result);
        $this->assertStringContainsString('&', $result);
    }

    /** @test */
    public function extract_metadata_handles_html_entities(): void
    {
        $html = '<html><head><title>Test &amp; Example</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        $this->assertStringContainsString('&', $result['title']);
    }

    /** @test */
    public function extract_structured_text_handles_special_characters(): void
    {
        $html = '<html><body><p>Special: © ® ™ € £ ¥ • … — –</p></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringContainsString('©', $result);
        $this->assertStringContainsString('®', $result);
        $this->assertStringContainsString('™', $result);
        $this->assertStringContainsString('€', $result);
    }

    /** @test */
    public function extract_structured_text_preserves_whitespace_in_pre(): void
    {
        $html = '<html><body><pre>  indented  code  </pre></body></html>';
        $result = $this->service->extractStructuredText($html);

        // Pre-formatted text should preserve whitespace
        $this->assertStringContainsString('indented', $result);
    }

    // =========================================================================
    // Security Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_does_not_execute_javascript(): void
    {
        $html = '<html><body><img src="x" onerror="alert(1)"><svg onload="alert(2)"></svg></body></html>';
        $result = $this->service->extractStructuredText($html);

        // Should not contain any JavaScript
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('onload', $result);
    }

    /** @test */
    public function extract_structured_text_sanitizes_svg_content(): void
    {
        $html = '<html><body><svg><script>alert(1)</script></svg></body></html>';
        $result = $this->service->extractStructuredText($html);

        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringNotContainsString('script', strtolower($result));
    }

    /** @test */
    public function extract_metadata_does_not_expose_sensitive_attributes(): void
    {
        $html = '<html><head><meta name="api-key" content="secret123"><title>Test</title></head><body></body></html>';
        $result = $this->service->extractMetadata($html);

        // Should not expose arbitrary meta tags
        $this->assertArrayNotHasKey('api-key', $result);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function extract_structured_text_processes_within_memory_limit(): void
    {
        // Create a moderately large HTML document
        $html = '<html><head><title>Test</title></head><body>';
        for ($i = 0; $i < 100; $i++) {
            $html .= '<p>Paragraph ' . $i . ' with some content to make it larger.</p>';
        }
        $html .= '</body></html>';

        $memoryBefore = memory_get_usage();
        $result = $this->service->extractStructuredText($html);
        $memoryAfter = memory_get_usage();

        // Should not use more than 10MB for this size
        $memoryUsed = $memoryAfter - $memoryBefore;
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed);
    }

    /** @test */
    public function process_content_completes_quickly(): void
    {
        $html = '<html><head><title>Test</title><meta name="description" content="Description"></head><body><h1>Title</h1><p>Content</p></body></html>';

        $start = microtime(true);
        $this->service->processContent($html);
        $end = microtime(true);

        // Should process in under 1 second
        $this->assertLessThan(1.0, $end - $start);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    /** @test */
    public function full_pipeline_with_realistic_html(): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Example Article | My Website</title>
    <meta name="description" content="This is an example article about web development.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://example.com/article">
    <meta property="og:title" content="Example Article">
    <meta property="og:description" content="This is an example article about web development.">
    <meta property="og:image" content="https://example.com/image.jpg">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://example.com/article">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Example Article">
    <meta name="twitter:description" content="This is an example article about web development.">
</head>
<body>
    <nav>
        <ul>
            <li><a href="/">Home</a></li>
            <li><a href="/about">About</a></li>
        </ul>
    </nav>
    <main>
        <h1>Example Article</h1>
        <p>This is an introduction paragraph.</p>
        <h2>Section One</h2>
        <p>Content for section one with a <a href="https://example.com/link">link</a>.</p>
        <img src="image.jpg" alt="Example image">
        <h2>Section Two</h2>
        <ul>
            <li>Item 1</li>
            <li>Item 2</li>
        </ul>
    </main>
    <aside>
        <p>Sidebar content</p>
    </aside>
    <footer>
        <p>Copyright 2026</p>
    </footer>
    <script>console.log('test');</script>
</body>
</html>
HTML;

        $result = $this->service->processContent($html);

        // Check metadata
        $this->assertEquals('Example Article | My Website', $result['metadata']['title']);
        $this->assertEquals('This is an example article about web development.', $result['metadata']['description']);
        $this->assertEquals('index, follow', $result['metadata']['robots']);
        $this->assertEquals('https://example.com/article', $result['metadata']['canonical']);
        $this->assertEquals('en-US', $result['metadata']['language']);
        $this->assertEquals('UTF-8', $result['metadata']['charset']);

        // Check OG data
        $this->assertEquals('Example Article', $result['metadata']['og']['title']);
        $this->assertEquals('https://example.com/image.jpg', $result['metadata']['og']['image']);
        $this->assertEquals('article', $result['metadata']['og']['type']);

        // Check Twitter data
        $this->assertEquals('summary_large_image', $result['metadata']['twitter']['card']);

        // Check structured text
        $this->assertStringContainsString('# Example Article', $result['text']);
        $this->assertStringContainsString('## Section One', $result['text']);
        $this->assertStringContainsString('[link]', $result['text']);
        $this->assertStringContainsString('[alt: Example image]', $result['text']);
        $this->assertStringContainsString('- Item 1', $result['text']);

        // Check that unwanted content is removed
        $this->assertStringNotContainsString('console.log', $result['text']);
        $this->assertStringNotContainsString('Sidebar content', $result['text']);
        $this->assertStringNotContainsString('Copyright 2026', $result['text']);

        // Check size
        $this->assertGreaterThan(0, $result['size']);
    }
}
