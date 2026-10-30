<?php

namespace Tests\Unit\Services\Tools;

use Tests\TestCase;
use App\Services\Tools\KeywordDensityChecker;
use App\Services\Tools\ToolResult;
use Illuminate\Support\Facades\Http;

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
 * @see \App\Services\Tools\KeywordDensityChecker
 */
class KeywordDensityCheckerTest extends TestCase
{
    protected KeywordDensityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = app(KeywordDensityChecker::class);
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
    // KEYWORD FREQUENCY TESTS (FREQ)
    // =========================================================================

    /**
     * FREQ-001: Single occurrence
     */
    public function test_single_occurrence(): void
    {
        $html = '<html><body><p>SEO is great</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['keywordCount']);
    }

    /**
     * FREQ-002: Multiple occurrences
     */
    public function test_multiple_occurrences(): void
    {
        $html = '<html><body><p>SEO SEO SEO</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['keywordCount']);
    }

    /**
     * FREQ-003: Case insensitive matching
     */
    public function test_case_insensitive_matching(): void
    {
        $html = '<html><body><p>Seo SEO seo</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'seo']);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['keywordCount']);
    }

    /**
     * FREQ-004: No occurrence
     */
    public function test_no_occurrence(): void
    {
        $html = '<html><body><p>Hello world</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['keywordCount']);
    }

    /**
     * FREQ-005: With punctuation
     */
    public function test_with_punctuation(): void
    {
        $html = '<html><body><p>SEO, SEO! SEO.</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals(3, $result->data['keywordCount']);
    }

    /**
     * FREQ-006: In quotes
     */
    public function test_in_quotes(): void
    {
        $html = '<html><body><p>"SEO" is great</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['keywordCount']);
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
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEqualsWithDelta(1.0, $result->data['density'], 0.2);
    }

    /**
     * DENSITY-002: 2% density
     */
    public function test_two_percent_density(): void
    {
        $words = 'SEO ' . str_repeat('word ', 49) . 'SEO';
        $html = "<html><body><p>{$words}</p></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEqualsWithDelta(2.0, $result->data['density'], 0.2);
    }

    /**
     * DENSITY-003: 0% density
     */
    public function test_zero_percent_density(): void
    {
        $words = str_repeat('word ', 100);
        $html = "<html><body><p>{$words}</p></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals(0.0, $result->data['density']);
    }

    /**
     * DENSITY-004: High density
     */
    public function test_high_density(): void
    {
        $words = 'SEO SEO SEO SEO SEO ' . str_repeat('word ', 95);
        $html = "<html><body><p>{$words}</p></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEqualsWithDelta(5.0, $result->data['density'], 0.3);
    }

    /**
     * DENSITY-005: Zero words edge case
     */
    public function test_zero_words_edge_case(): void
    {
        $html = '<html><body></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals(0.0, $result->data['density']);
    }

    /**
     * DENSITY-006: Density status "good"
     */
    public function test_density_status_good(): void
    {
        $words = 'SEO ' . str_repeat('word ', 66);
        $html = "<html><body><p>{$words}</p></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals('good', $result->data['densityStatus']);
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
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['placement']['inTitle']);
    }

    /**
     * PLACE-002: Keyword not in title
     */
    public function test_keyword_not_in_title(): void
    {
        $html = '<html><head><title>Guide</title></head><body><p>SEO content</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertFalse($result->data['placement']['inTitle']);
    }

    /**
     * PLACE-003: Keyword in H1
     */
    public function test_keyword_in_h1(): void
    {
        $html = '<html><body><h1>SEO Tips</h1><p>Content</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['placement']['inH1']);
    }

    /**
     * PLACE-004: Keyword in H2
     */
    public function test_keyword_in_h2(): void
    {
        $html = '<html><body><h1>Title</h1><h2>SEO Guide</h2><p>Content</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertContains('H2', $result->data['placement']['headings']);
    }

    /**
     * PLACE-005: Keyword in first paragraph
     */
    public function test_keyword_in_first_paragraph(): void
    {
        $html = '<html><body><p>SEO is important for rankings.</p><p>More content.</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['placement']['inFirstParagraph']);
    }

    /**
     * PLACE-006: Keyword in meta description
     */
    public function test_keyword_in_meta_description(): void
    {
        $html = '<html><head><meta name="description" content="SEO tips and tricks"></head><body><p>Content</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['placement']['inMetaDescription']);
    }

    /**
     * PLACE-007: Keyword in URL
     */
    public function test_keyword_in_url(): void
    {
        $html = '<html><body><p>Content</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com/seo-guide', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertTrue($result->data['placement']['inUrl']);
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
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals('low', $result->data['densityStatus']);
    }

    /**
     * STUFF-002: 0.5% - 2% density - good status
     */
    public function test_good_density_range(): void
    {
        $words = 'SEO ' . str_repeat('word ', 66);
        $html = "<html><body><p>{$words}</p></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals('good', $result->data['densityStatus']);
    }

    /**
     * STUFF-003: 2% - 3% density - acceptable status
     */
    public function test_acceptable_density_range(): void
    {
        $words = 'SEO SEO SEO ' . str_repeat('word ', 97);
        $html = "<html><body><p>{$words}</p></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals('acceptable', $result->data['densityStatus']);
    }

    /**
     * STUFF-004: Over 3% density - stuffing status
     */
    public function test_stuffing_density(): void
    {
        $words = 'SEO SEO SEO SEO ' . str_repeat('word ', 96);
        $html = "<html><body><p>{$words}</p></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertEquals('stuffing', $result->data['densityStatus']);
    }

    /**
     * STUFF-005: Warning message for stuffing
     */
    public function test_warning_message_for_stuffing(): void
    {
        $words = 'SEO SEO SEO SEO SEO ' . str_repeat('word ', 95);
        $html = "<html><body><p>{$words}</p></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->data['warnings']);
        $this->assertStringContainsString('stuffing', strtolower($result->data['warnings'][0]['message'] ?? ''));
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
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'seo tools']);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['keywordCount']);
    }

    /**
     * PHRASE-002: Phrase case insensitive
     */
    public function test_phrase_case_insensitive(): void
    {
        $html = '<html><body><p>Best SEO TOOLS available</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'seo tools']);

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->data['keywordCount']);
    }

    /**
     * PHRASE-003: Partial phrase not counted
     */
    public function test_partial_phrase_not_counted(): void
    {
        $html = '<html><body><p>SEO is great. Tools are useful.</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'seo tools']);

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->data['keywordCount']);
    }

    // =========================================================================
    // ERROR HANDLING TESTS
    // =========================================================================

    /**
     * ERR-001: Missing keyword parameter
     */
    public function test_missing_keyword_parameter(): void
    {
        $result = $this->checker->analyze('https://example.com', []);

        $this->assertFalse($result->success);
        $this->assertEquals('MISSING_KEYWORD', $result->errorCode);
    }

    /**
     * ERR-002: Invalid URL
     */
    public function test_invalid_url(): void
    {
        $result = $this->checker->analyze('not-a-valid-url', ['keyword' => 'SEO']);

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
    }

    /**
     * ERR-003: Connection failure
     */
    public function test_connection_failure(): void
    {
        Http::fake([
            '*' => Http::failedConnection('Connection refused'),
        ]);

        $result = $this->checker->analyze('https://nonexistent.example.com', ['keyword' => 'SEO']);

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
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
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertRecommendationContains($result, 'title');
    }

    /**
     * REC-002: Keyword missing from title recommendation
     */
    public function test_keyword_missing_from_title_recommendation(): void
    {
        $html = '<html><head><title>Guide</title></head><body><p>SEO content</p></body></html>';
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertRecommendationContains($result, 'Consider adding');
    }

    /**
     * REC-003: Good density recommendation
     */
    public function test_good_density_recommendation(): void
    {
        $words = 'SEO ' . str_repeat('word ', 66);
        $html = "<html><body><p>{$words}</p></body></html>";
        $this->mockHtmlResponse($html);

        $result = $this->checker->analyze('https://example.com', ['keyword' => 'SEO']);

        $this->assertTrue($result->success);
        $this->assertRecommendationContains($result, 'optimal');
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function assertRecommendationContains(ToolResult $result, string $text): void
    {
        $this->assertTrue($result->success, 'Result must be successful to check recommendations');
        
        $found = false;
        foreach ($result->data['recommendations'] as $rec) {
            if (stripos($rec['message'] ?? '', $text) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Expected recommendation containing '{$text}' not found");
    }
}
