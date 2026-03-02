<?php

namespace Tests\Unit\Services\Crawler;

use PHPUnit\Framework\TestCase;
use App\Services\Crawler\LinkExtractorService;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Unit tests for LinkExtractorService - TASK-22-10
 * 
 * Tests link extraction from rendered HTML including:
 * - Anchor links, images, stylesheets, icons, iframes
 * - JavaScript-generated links
 * - Internal/external link detection
 * - URL normalization
 * - Attribute extraction
 */
class LinkExtractorServiceTest extends TestCase
{
    private LinkExtractorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LinkExtractorService('https://example.com');
    }

    // ============================================
    // LE - Link Extraction Tests
    // ============================================

    /**
     * LE-001: Extract anchor links from HTML
     */
    public function test_extract_anchor_links_from_html(): void
    {
        $html = '<html><body><a href="https://example.com/page">Link Text</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertCount(1, $links);
        $this->assertEquals('https://example.com/page', $links[0]['url']);
        $this->assertEquals('Link Text', $links[0]['text']);
        $this->assertEquals('link', $links[0]['type']);
    }

    /**
     * LE-002: Extract image src attributes
     */
    public function test_extract_image_src_attributes(): void
    {
        $html = '<html><body><img src="/images/logo.png" alt="Logo"></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertCount(1, $links);
        $this->assertEquals('https://example.com/images/logo.png', $links[0]['url']);
        $this->assertEquals('image', $links[0]['type']);
    }

    /**
     * LE-003: Extract stylesheet links
     */
    public function test_extract_stylesheet_links(): void
    {
        $html = '<html><head><link rel="stylesheet" href="/styles/main.css"></head></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertCount(1, $links);
        $this->assertEquals('stylesheet', $links[0]['type']);
        $this->assertEquals('https://example.com/styles/main.css', $links[0]['url']);
    }

    /**
     * LE-004: Extract icon links
     */
    public function test_extract_icon_links(): void
    {
        $html = '<html><head><link rel="icon" href="/favicon.ico"></head></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertCount(1, $links);
        $this->assertEquals('icon', $links[0]['type']);
    }

    /**
     * LE-005: Extract iframe src (configurable)
     */
    public function test_extract_iframe_src(): void
    {
        $html = '<html><body><iframe src="https://embed.example.com"></iframe></body></html>';
        
        $service = new LinkExtractorService('https://example.com', includeIframes: true);
        $links = $service->extract($html, 'https://example.com');
        
        $this->assertCount(1, $links);
        $this->assertEquals('iframe', $links[0]['type']);
    }

    /**
     * LE-005b: Skip iframe when disabled
     */
    public function test_skip_iframe_when_disabled(): void
    {
        $html = '<html><body><iframe src="https://embed.example.com"></iframe></body></html>';
        
        $service = new LinkExtractorService('https://example.com', includeIframes: false);
        $links = $service->extract($html, 'https://example.com');
        
        $this->assertCount(0, $links);
    }

    /**
     * LE-006: Handle JavaScript onclick links
     */
    public function test_handle_javascript_onclick_links(): void
    {
        $html = '<html><body>
            <a href="#" onclick="location.href=\'/page\'">JS Link</a>
            <a onclick="window.location=\'/other\'">Another JS Link</a>
        </body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        // Should extract URLs from onclick handlers
        $urls = array_column($links, 'url');
        $this->assertContains('https://example.com/page', $urls);
    }

    /**
     * LE-007: Identify internal vs external links
     */
    public function test_identify_internal_vs_external_links(): void
    {
        $html = '<html><body>
            <a href="/internal">Internal</a>
            <a href="https://example.com/page">Also Internal</a>
            <a href="https://external.com">External</a>
        </body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $internalLinks = array_filter($links, fn($l) => $l['internal'] === true);
        $externalLinks = array_filter($links, fn($l) => $l['internal'] === false);
        
        $this->assertCount(2, $internalLinks);
        $this->assertCount(1, $externalLinks);
    }

    /**
     * LE-008: Handle relative URLs
     */
    public function test_handle_relative_urls(): void
    {
        $html = '<html><body><a href="/about">About</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertEquals('https://example.com/about', $links[0]['url']);
    }

    /**
     * LE-009: Handle protocol-relative URLs
     */
    public function test_handle_protocol_relative_urls(): void
    {
        $html = '<html><body><a href="//example.com/page">Link</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertEquals('https://example.com/page', $links[0]['url']);
    }

    /**
     * LE-010: Handle malformed URLs
     */
    public function test_handle_malformed_urls(): void
    {
        $html = '<html><body>
            <a href="javascript:void(0)">JS Void</a>
            <a href="mailto:test@example.com">Email</a>
            <a href="tel:+1234567890">Phone</a>
            <a href="#">Anchor</a>
        </body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        // These should all be filtered out
        $this->assertCount(0, $links);
    }

    /**
     * LE-011: Extract link attributes
     */
    public function test_extract_link_attributes(): void
    {
        $html = '<html><body>
            <a href="/page" title="Page Title" rel="nofollow" target="_blank">Link</a>
        </body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertEquals('Page Title', $links[0]['title']);
        $this->assertEquals('nofollow', $links[0]['rel']);
        $this->assertEquals('_blank', $links[0]['target']);
    }

    /**
     * LE-012: Handle empty anchor text
     */
    public function test_handle_empty_anchor_text(): void
    {
        $html = '<html><body><a href="/page"></a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertCount(1, $links);
        $this->assertNull($links[0]['text']);
    }

    /**
     * LE-013: Unicode in URLs
     */
    public function test_unicode_in_urls(): void
    {
        $html = '<html><body><a href="/café">Café</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertCount(1, $links);
        // Should be URL-encoded
        $this->assertStringContainsString('caf', $links[0]['url']);
    }

    /**
     * LE-014: Handle anchor fragments
     */
    public function test_handle_anchor_fragments(): void
    {
        $html = '<html><body>
            <a href="/page#section">With Fragment</a>
            <a href="#section">Just Fragment</a>
        </body></html>';
        
        $links = $this->service->extract($html, 'https://example.com', stripFragments: false);
        
        $this->assertCount(1, $links);
        $this->assertStringContainsString('#section', $links[0]['url']);
    }

    /**
     * LE-014b: Strip anchor fragments
     */
    public function test_strip_anchor_fragments(): void
    {
        $html = '<html><body><a href="/page#section">With Fragment</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com', stripFragments: true);
        
        $this->assertEquals('https://example.com/page', $links[0]['url']);
    }

    /**
     * LE-015: Skip mailto/tel links
     */
    public function test_skip_mailto_tel_links(): void
    {
        $html = '<html><body>
            <a href="mailto:test@example.com">Email</a>
            <a href="tel:+1234567890">Phone</a>
            <a href="/page">Valid Link</a>
        </body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertCount(1, $links);
        $this->assertEquals('https://example.com/page', $links[0]['url']);
    }

    // ============================================
    // LD - Link Data Structure Tests
    // ============================================

    /**
     * LD-001: Valid link structure
     */
    public function test_valid_link_structure(): void
    {
        $html = '<html><body><a href="/page" title="Title" rel="nofollow">Text</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com', depth: 1, parentUrl: 'https://example.com');
        
        $this->assertArrayHasKey('url', $links[0]);
        $this->assertArrayHasKey('text', $links[0]);
        $this->assertArrayHasKey('title', $links[0]);
        $this->assertArrayHasKey('rel', $links[0]);
        $this->assertArrayHasKey('target', $links[0]);
        $this->assertArrayHasKey('internal', $links[0]);
        $this->assertArrayHasKey('type', $links[0]);
        $this->assertArrayHasKey('depth', $links[0]);
        $this->assertArrayHasKey('parentUrl', $links[0]);
        $this->assertArrayHasKey('discovered', $links[0]);
    }

    /**
     * LD-002: Missing optional fields
     */
    public function test_missing_optional_fields(): void
    {
        $html = '<html><body><a href="/page">Text</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertNull($links[0]['title']);
        $this->assertNull($links[0]['rel']);
        $this->assertNull($links[0]['target']);
    }

    /**
     * LD-003/LD-004: Internal/external link flags tested above
     */

    /**
     * LD-005: Depth assignment
     */
    public function test_depth_assignment(): void
    {
        $html = '<html><body><a href="/page">Link</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com', depth: 2);
        
        $this->assertEquals(2, $links[0]['depth']);
    }

    /**
     * LD-006: Parent URL tracking
     */
    public function test_parent_url_tracking(): void
    {
        $html = '<html><body><a href="/page">Link</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com', parentUrl: 'https://example.com/home');
        
        $this->assertEquals('https://example.com/home', $links[0]['parentUrl']);
    }

    /**
     * LD-007: Timestamp recording
     */
    public function test_timestamp_recording(): void
    {
        $html = '<html><body><a href="/page">Link</a></body></html>';
        
        $links = $this->service->extract($html, 'https://example.com');
        
        $this->assertNotNull($links[0]['discovered']);
        $this->assertInstanceOf(\DateTimeInterface::class, $links[0]['discovered']);
    }
}
