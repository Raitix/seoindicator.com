<?php

namespace Tests\Unit\Services\Tools;

use Tests\TestCase;
use App\Services\Tools\Analyzers\KeywordDensityChecker;
use App\Services\Tools\DTO\KeywordDensityResult;

/**
 * Unit tests for TASK-16-3: Keyword Density Checker
 *
 * Tests the KeywordDensityChecker service responsible for:
 * - Text extraction from HTML
 * - Keyword frequency counting
 * - Density percentage calculation
 * - Placement analysis
 * - Keyword stuffing detection
 *
 * @see \App\Services\Tools\Analyzers\KeywordDensityChecker
 */
class KeywordDensityCheckerTest extends TestCase
{
    protected KeywordDensityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new KeywordDensityChecker();
    }

    // =========================================================================
    // TEXT EXTRACTION TESTS (TEXT)
    // =========================================================================

    /**
     * TEXT-001: Simple paragraph extraction
     */
    public function test_simple_paragraph_extraction(): void
    {
        $html = '<html><body><p>Hello world</p></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertEquals('Hello world', $text);
    }

    /**
     * TEXT-002: Remove script tags
     */
    public function test_remove_script_tags(): void
    {
        $html = '<html><body><script>alert(1)</script><p>Text</p></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringNotContainsString('alert', $text);
        $this->assertStringContainsString('Text', $text);
    }

    /**
     * TEXT-003: Remove style tags
     */
    public function test_remove_style_tags(): void
    {
        $html = '<html><head><style>.a{color:red}</style></head><body><p>Text</p></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringNotContainsString('color', $text);
        $this->assertStringContainsString('Text', $text);
    }

    /**
     * TEXT-004: Remove noscript tags
     */
    public function test_remove_noscript_tags(): void
    {
        $html = '<html><body><noscript>Hidden</noscript><p>Text</p></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringNotContainsString('Hidden', $text);
        $this->assertStringContainsString('Text', $text);
    }

    /**
     * TEXT-005: Remove hidden elements
     */
    public function test_remove_hidden_elements(): void
    {
        $html = '<html><body><div style="display:none">Hidden</div><p>Visible</p></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringNotContainsString('Hidden', $text);
        $this->assertStringContainsString('Visible', $text);
    }

    /**
     * TEXT-006: Preserve text in links
     */
    public function test_preserve_text_in_links(): void
    {
        $html = '<html><body><a href="#">Link text</a></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringContainsString('Link text', $text);
    }

    /**
     * TEXT-007: Extract from multiple paragraphs
     */
    public function test_extract_from_multiple_paragraphs(): void
    {
        $html = '<html><body><p>First</p><p>Second</p><p>Third</p></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringContainsString('First', $text);
        $this->assertStringContainsString('Second', $text);
        $this->assertStringContainsString('Third', $text);
    }

    /**
     * TEXT-008: Handle br tags
     */
    public function test_handle_br_tags(): void
    {
        $html = '<html><body>Line1<br>Line2</body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringContainsString('Line1', $text);
        $this->assertStringContainsString('Line2', $text);
    }

    /**
     * TEXT-009: Decode HTML entities
     */
    public function test_decode_html_entities(): void
    {
        $html = '<html><body><p>Bob &amp; Alice</p></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringContainsString('Bob & Alice', $text);
    }

    /**
     * TEXT-010: Normalize whitespace
     */
    public function test_normalize_whitespace(): void
    {
        $html = '<html><body><p>Word1   Word2</p></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringContainsString('Word1 Word2', $text);
    }

    /**
     * TEXT-011: Extract from headings
     */
    public function test_extract_from_headings(): void
    {
        $html = '<html><body><h1>Title</h1><h2>Subtitle</h2></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringContainsString('Title', $text);
        $this->assertStringContainsString('Subtitle', $text);
    }

    /**
     * TEXT-012: Skip meta tags
     */
    public function test_skip_meta_tags(): void
    {
        $html = '<html><head><meta name="description" content="Meta text"></head><body>Body text</body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringNotContainsString('Meta text', $text);
        $this->assertStringContainsString('Body text', $text);
    }

    /**
     * TEXT-013: Extract from lists
     */
    public function test_extract_from_lists(): void
    {
        $html = '<html><body><ul><li>Item1</li><li>Item2</li></ul></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringContainsString('Item1', $text);
        $this->assertStringContainsString('Item2', $text);
    }

    /**
     * TEXT-014: Extract from tables
     */
    public function test_extract_from_tables(): void
    {
        $html = '<html><body><table><tr><td>Cell1</td><td>Cell2</td></tr></table></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringContainsString('Cell1', $text);
        $this->assertStringContainsString('Cell2', $text);
    }

    /**
     * TEXT-015: Remove comments
     */
    public function test_remove_comments(): void
    {
        $html = '<html><body><!-- comment --><p>Text</p></body></html>';
        $text = $this->checker->extractText($html);

        $this->assertStringNotContainsString('comment', $text);
        $this->assertStringContainsString('Text', $text);
    }

    // =========================================================================
    // KEYWORD FREQUENCY TESTS (FREQ)
    // =========================================================================

    /**
     * FREQ-001: Single occurrence
     */
    public function test_single_occurrence(): void
    {
        $html = '<html><body><p>SEO is great</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * FREQ-002: Multiple occurrences
     */
    public function test_multiple_occurrences(): void
    {
        $html = '<html><body><p>SEO SEO SEO</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(3, $result->keywordCount);
    }

    /**
     * FREQ-003: Case insensitive matching
     */
    public function test_case_insensitive_matching(): void
    {
        $html = '<html><body><p>Seo SEO seo</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'seo');

        $this->assertEquals(3, $result->keywordCount);
    }

    /**
     * FREQ-004: No occurrence
     */
    public function test_no_occurrence(): void
    {
        $html = '<html><body><p>Hello world</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(0, $result->keywordCount);
    }

    /**
     * FREQ-005: Partial match not counted
     */
    public function test_partial_match_not_counted(): void
    {
        $html = '<html><body><p>SEOTools is great</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(0, $result->keywordCount);
    }

    /**
     * FREQ-006: Word boundary with hyphen
     */
    public function test_word_boundary_hyphen(): void
    {
        $html = '<html><body><p>SEO-Tools SEO Tools</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        // "SEO-Tools" might not count as "SEO" depending on implementation
        // Let's check that "SEO Tools" counts
        $this->assertGreaterThanOrEqual(1, $result->keywordCount);
    }

    /**
     * FREQ-007: With punctuation
     */
    public function test_with_punctuation(): void
    {
        $html = '<html><body><p>SEO, SEO! SEO.</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(3, $result->keywordCount);
    }

    /**
     * FREQ-008: In quotes
     */
    public function test_in_quotes(): void
    {
        $html = '<html><body><p>"SEO" is great</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * FREQ-009: Unicode text
     */
    public function test_unicode_text(): void
    {
        $html = '<html><body><p>Café café CAFÉ</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'café');

        $this->assertEquals(3, $result->keywordCount);
    }

    /**
     * FREQ-010: Numbers in keyword
     */
    public function test_numbers_in_keyword(): void
    {
        $html = '<html><body><p>SEO2024 SEO2024</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO2024');

        $this->assertEquals(2, $result->keywordCount);
    }

    /**
     * FREQ-011: Apostrophe handling
     */
    public function test_apostrophe_handling(): void
    {
        $html = '<html><body><p>SEO SEOs SEOs</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        // Only exact match should count
        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * FREQ-012: Mixed case phrase
     */
    public function test_mixed_case_phrase(): void
    {
        $html = '<html><body><p>Seo Tools seo tools</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'seo tools');

        $this->assertEquals(2, $result->keywordCount);
    }

    // =========================================================================
    // DENSITY CALCULATION TESTS (DENSITY)
    // =========================================================================

    /**
     * DENSITY-001: 1% density
     */
    public function test_one_percent_density(): void
    {
        $words = str_repeat('word ', 99) . 'SEO';
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(1.0, $result->density, '', 0.1);
    }

    /**
     * DENSITY-002: 2% density
     */
    public function test_two_percent_density(): void
    {
        $words = 'SEO ' . str_repeat('word ', 49) . 'SEO';
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(2.0, $result->density, '', 0.1);
    }

    /**
     * DENSITY-003: 0% density
     */
    public function test_zero_percent_density(): void
    {
        $words = str_repeat('word ', 100);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(0.0, $result->density);
    }

    /**
     * DENSITY-004: High density
     */
    public function test_high_density(): void
    {
        $words = 'SEO SEO SEO SEO SEO ' . str_repeat('word ', 95);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(5.0, $result->density, '', 0.1);
    }

    /**
     * DENSITY-005: Phrase keyword density
     */
    public function test_phrase_keyword_density(): void
    {
        $words = 'seo tools seo tools ' . str_repeat('word ', 96);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'seo tools');

        $this->assertEquals(2.0, $result->density, '', 0.1);
    }

    /**
     * DENSITY-006: Zero words edge case
     */
    public function test_zero_words_edge_case(): void
    {
        $html = '<html><body></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(0.0, $result->density);
    }

    /**
     * DENSITY-007: Decimal precision
     */
    public function test_decimal_precision(): void
    {
        $words = 'SEO SEO ' . str_repeat('word ', 95);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        // Should have proper decimal precision
        $this->assertIsFloat($result->density);
        $this->assertLessThan(3, $result->density);
    }

    /**
     * DENSITY-008: Very low density
     */
    public function test_very_low_density(): void
    {
        $words = 'SEO ' . str_repeat('word ', 199);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(0.5, $result->density, '', 0.1);
    }

    /**
     * DENSITY-009: Density never over 100%
     */
    public function test_density_never_over_100(): void
    {
        // This is technically impossible but let's verify
        $html = '<html><body><p>SEO SEO SEO SEO SEO</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertLessThanOrEqual(100, $result->density);
    }

    /**
     * DENSITY-010: Density status "good"
     */
    public function test_density_status_good(): void
    {
        $words = 'SEO ' . str_repeat('word ', 66);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals('good', $result->densityStatus);
    }

    // =========================================================================
    // PLACEMENT ANALYSIS TESTS (PLACE)
    // =========================================================================

    /**
     * PLACE-001: Keyword in title
     */
    public function test_keyword_in_title(): void
    {
        $html = '<html><head><title>SEO Guide</title></head><body><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertTrue($result->placement['inTitle']);
    }

    /**
     * PLACE-002: Keyword not in title
     */
    public function test_keyword_not_in_title(): void
    {
        $html = '<html><head><title>Guide</title></head><body><p>SEO content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertFalse($result->placement['inTitle']);
    }

    /**
     * PLACE-003: Keyword in H1
     */
    public function test_keyword_in_h1(): void
    {
        $html = '<html><body><h1>SEO Tips</h1><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertTrue($result->placement['inH1']);
    }

    /**
     * PLACE-004: Keyword in H2
     */
    public function test_keyword_in_h2(): void
    {
        $html = '<html><body><h1>Title</h1><h2>SEO Guide</h2><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertContains('H2', $result->placement['headings']);
    }

    /**
     * PLACE-005: Keyword in first paragraph
     */
    public function test_keyword_in_first_paragraph(): void
    {
        $html = '<html><body><p>SEO is important for rankings.</p><p>More content.</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertTrue($result->placement['inFirstParagraph']);
    }

    /**
     * PLACE-006: Keyword in meta description
     */
    public function test_keyword_in_meta_description(): void
    {
        $html = '<html><head><meta name="description" content="SEO tips and tricks"></head><body><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertTrue($result->placement['inMetaDescription']);
    }

    /**
     * PLACE-007: Keyword in multiple headings
     */
    public function test_keyword_in_multiple_headings(): void
    {
        $html = '<html><body><h1>SEO Guide</h1><h2>SEO Tips</h2><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertContains('H1', $result->placement['headings']);
        $this->assertContains('H2', $result->placement['headings']);
    }

    /**
     * PLACE-008: Case insensitive heading match
     */
    public function test_case_insensitive_heading_match(): void
    {
        $html = '<html><body><h1>seo TOOLS</h1><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO Tools');

        $this->assertTrue($result->placement['inH1']);
    }

    /**
     * PLACE-009: Keyword not in body
     */
    public function test_keyword_not_in_body(): void
    {
        $html = '<html><head><title>SEO Guide</title></head><body><p>Hello world</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(0, $result->keywordCount);
    }

    /**
     * PLACE-010: Keyword in URL
     */
    public function test_keyword_in_url(): void
    {
        $html = '<html><body><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com/seo-guide', 'SEO');

        $this->assertTrue($result->placement['inUrl']);
    }

    /**
     * PLACE-011: Keyword in alt text
     */
    public function test_keyword_in_alt_text(): void
    {
        $html = '<html><body><img src="image.jpg" alt="SEO image"><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertTrue($result->placement['inAltText']);
    }

    /**
     * PLACE-012: Keyword in anchor text
     */
    public function test_keyword_in_anchor_text(): void
    {
        $html = '<html><body><a href="#">SEO link</a><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertTrue($result->placement['inAnchorText']);
    }

    // =========================================================================
    // KEYWORD STUFFING DETECTION TESTS (STUFF)
    // =========================================================================

    /**
     * STUFF-001: Under 0.5% density - low status
     */
    public function test_under_half_percent_density(): void
    {
        $words = 'SEO ' . str_repeat('word ', 299);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals('low', $result->densityStatus);
    }

    /**
     * STUFF-002: 0.5% - 2% density - good status
     */
    public function test_good_density_range(): void
    {
        $words = 'SEO ' . str_repeat('word ', 66);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals('good', $result->densityStatus);
    }

    /**
     * STUFF-003: 2% - 3% density - acceptable status
     */
    public function test_acceptable_density_range(): void
    {
        $words = 'SEO SEO SEO ' . str_repeat('word ', 97);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals('acceptable', $result->densityStatus);
    }

    /**
     * STUFF-004: Over 3% density - stuffing status
     */
    public function test_stuffing_density(): void
    {
        $words = 'SEO SEO SEO SEO ' . str_repeat('word ', 96);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals('stuffing', $result->densityStatus);
    }

    /**
     * STUFF-005: Exactly 0.5% - good status
     */
    public function test_exactly_half_percent(): void
    {
        $words = 'SEO ' . str_repeat('word ', 199);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals('good', $result->densityStatus);
    }

    /**
     * STUFF-006: Exactly 2% - good status
     */
    public function test_exactly_two_percent(): void
    {
        $words = 'SEO SEO ' . str_repeat('word ', 98);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals('good', $result->densityStatus);
    }

    /**
     * STUFF-007: Exactly 3% - acceptable status
     */
    public function test_exactly_three_percent(): void
    {
        $words = 'SEO SEO SEO ' . str_repeat('word ', 97);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals('acceptable', $result->densityStatus);
    }

    /**
     * STUFF-008: Warning message for stuffing
     */
    public function test_warning_message_for_stuffing(): void
    {
        $words = 'SEO SEO SEO SEO SEO ' . str_repeat('word ', 95);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertNotEmpty($result->warnings);
        $this->assertStringContainsString('stuffing', strtolower(implode(' ', $result->warnings)));
    }

    // =========================================================================
    // PHRASE KEYWORD TESTS (PHRASE)
    // =========================================================================

    /**
     * PHRASE-001: Two-word phrase
     */
    public function test_two_word_phrase(): void
    {
        $html = '<html><body><p>Best seo tools for beginners</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'seo tools');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * PHRASE-002: Three-word phrase
     */
    public function test_three_word_phrase(): void
    {
        $html = '<html><body><p>Find the best seo tools today</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'best seo tools');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * PHRASE-003: Phrase with hyphen
     */
    public function test_phrase_with_hyphen(): void
    {
        $html = '<html><body><p>SEO-friendly content works best</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'seo-friendly');

        $this->assertGreaterThanOrEqual(1, $result->keywordCount);
    }

    /**
     * PHRASE-004: Phrase case insensitive
     */
    public function test_phrase_case_insensitive(): void
    {
        $html = '<html><body><p>Best SEO TOOLS available</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'seo tools');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * PHRASE-005: Partial phrase not counted
     */
    public function test_partial_phrase_not_counted(): void
    {
        $html = '<html><body><p>SEO is great. Tools are useful.</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'seo tools');

        $this->assertEquals(0, $result->keywordCount);
    }

    /**
     * PHRASE-006: Phrase across sentences
     */
    public function test_phrase_across_sentences(): void
    {
        $html = '<html><body><p>SEO. Tools.</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'seo tools');

        $this->assertEquals(0, $result->keywordCount);
    }

    /**
     * PHRASE-007: Phrase in title
     */
    public function test_phrase_in_title(): void
    {
        $html = '<html><head><title>Best SEO Tools Guide</title></head><body><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'seo tools');

        $this->assertTrue($result->placement['inTitle']);
    }

    /**
     * PHRASE-008: Phrase density calculation
     */
    public function test_phrase_density_calculation(): void
    {
        $words = 'seo tools seo tools seo tools ' . str_repeat('word ', 94);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'seo tools');

        $this->assertEquals(3, $result->keywordCount);
    }

    /**
     * PHRASE-009: Long phrase handling
     */
    public function test_long_phrase_handling(): void
    {
        $longPhrase = 'the best seo tools for beginners in 2024';
        $html = '<html><body><p>The Best SEO Tools For Beginners In 2024 are listed here.</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', $longPhrase);

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * PHRASE-010: Single word vs phrase separate tracking
     */
    public function test_single_word_vs_phrase_tracking(): void
    {
        $html = '<html><body><p>SEO seo seo</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(3, $result->keywordCount);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Empty HTML
     */
    public function test_empty_html(): void
    {
        $result = $this->checker->analyze('', 'https://example.com', 'SEO');

        $this->assertFalse($result->success);
        $this->assertEquals(0, $result->keywordCount);
    }

    /**
     * EC-002: No body
     */
    public function test_no_body(): void
    {
        $html = '<html><head><title>Test</title></head></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(0, $result->keywordCount);
    }

    /**
     * EC-003: Very long text
     */
    public function test_very_long_text(): void
    {
        $longText = str_repeat('SEO content ', 10000);
        $html = "<html><body><p>{$longText}</p></body></html>";

        // Should not throw or timeout
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');
        $this->assertGreaterThan(0, $result->keywordCount);
    }

    /**
     * EC-004: Special characters in keyword
     */
    public function test_special_characters_in_keyword(): void
    {
        $html = '<html><body><p>C++ programming is fun</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'C++');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * EC-005: Unicode keyword
     */
    public function test_unicode_keyword(): void
    {
        $html = '<html><body><p>Café is great</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'café');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * EC-006: Empty keyword
     */
    public function test_empty_keyword(): void
    {
        $html = '<html><body><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', '');

        $this->assertFalse($result->success);
    }

    /**
     * EC-007: Only script/style content
     */
    public function test_only_script_style_content(): void
    {
        $html = '<html><body><script>alert(1)</script><style>.a{}</style></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(0, $result->keywordCount);
    }

    /**
     * EC-008: Nested elements
     */
    public function test_nested_elements(): void
    {
        $html = '<html><body><div><div><div>SEO content</div></div></div></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * EC-009: Malformed HTML
     */
    public function test_malformed_html(): void
    {
        $html = '<html><body><p>SEO content';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * EC-010: Very long keyword
     */
    public function test_very_long_keyword(): void
    {
        $longKeyword = str_repeat('a', 100);
        $html = '<html><body><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', $longKeyword);

        $this->assertEquals(0, $result->keywordCount);
    }

    /**
     * EC-011: Numbers in keyword
     */
    public function test_numbers_in_keyword(): void
    {
        $html = '<html><body><p>SEO2024 is the future</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO2024');

        $this->assertEquals(1, $result->keywordCount);
    }

    /**
     * EC-012: Keyword with apostrophe
     */
    public function test_keyword_with_apostrophe(): void
    {
        $html = '<html><body><p>SEO\'s impact is huge</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', "SEO's");

        $this->assertGreaterThanOrEqual(1, $result->keywordCount);
    }

    // =========================================================================
    // RECOMMENDATION TESTS (REC)
    // =========================================================================

    /**
     * REC-001: Keyword in title recommendation
     */
    public function test_keyword_in_title_recommendation(): void
    {
        $html = '<html><head><title>SEO Guide</title></head><body><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertRecommendationContains($result, 'title');
    }

    /**
     * REC-002: Keyword missing from title recommendation
     */
    public function test_keyword_missing_from_title_recommendation(): void
    {
        $html = '<html><head><title>Guide</title></head><body><p>SEO content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertRecommendationContains($result, 'Consider adding');
    }

    /**
     * REC-003: Keyword in H1 recommendation
     */
    public function test_keyword_in_h1_recommendation(): void
    {
        $html = '<html><body><h1>SEO Tips</h1><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertRecommendationContains($result, 'H1');
    }

    /**
     * REC-004: Low density recommendation
     */
    public function test_low_density_recommendation(): void
    {
        $words = 'SEO ' . str_repeat('word ', 399);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertRecommendationContains($result, 'more keyword');
    }

    /**
     * REC-005: Good density recommendation
     */
    public function test_good_density_recommendation(): void
    {
        $words = 'SEO ' . str_repeat('word ', 66);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertRecommendationContains($result, 'Optimal');
    }

    /**
     * REC-006: High density recommendation
     */
    public function test_high_density_recommendation(): void
    {
        $words = 'SEO SEO SEO SEO ' . str_repeat('word ', 96);
        $html = "<html><body><p>{$words}</p></body></html>";
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertRecommendationContains($result, 'stuffing');
    }

    /**
     * REC-007: Missing from first paragraph
     */
    public function test_missing_from_first_paragraph_recommendation(): void
    {
        $html = '<html><body><p>First paragraph without keyword.</p><p>SEO in second.</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertRecommendationContains($result, 'first paragraph');
    }

    /**
     * REC-008: In meta description recommendation
     */
    public function test_in_meta_description_recommendation(): void
    {
        $html = '<html><head><meta name="description" content="SEO tips"></head><body><p>Content</p></body></html>';
        $result = $this->checker->analyze($html, 'https://example.com', 'SEO');

        $this->assertRecommendationContains($result, 'meta description');
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function assertRecommendationContains(KeywordDensityResult $result, string $text): void
    {
        $found = false;
        foreach ($result->recommendations as $rec) {
            if (stripos($rec, $text) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Expected recommendation containing '{$text}' not found");
    }
}
