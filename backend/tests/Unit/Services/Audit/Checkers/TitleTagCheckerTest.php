<?php

namespace Tests\Unit\Services\Audit\Checkers;

use Tests\TestCase;
use App\Services\Audit\Checkers\TitleTagChecker;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;

class TitleTagCheckerTest extends TestCase
{
    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    public function test_title_tag_checker_can_be_instantiated(): void
    {
        $checker = new TitleTagChecker();

        $this->assertInstanceOf(TitleTagChecker::class, $checker);
    }

    public function test_title_tag_checker_implements_checker_interface(): void
    {
        $checker = new TitleTagChecker();

        $this->assertInstanceOf(
            \App\Services\Audit\Contracts\CheckerInterface::class,
            $checker
        );
    }

    // =========================================================================
    // Missing Title Tests (Critical)
    // =========================================================================

    public function test_missing_title_tag_returns_critical_issue(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body><p>Content</p></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('critical', $finding->severity);
        $this->assertStringContainsString('missing', strtolower($finding->description));
    }

    public function test_no_head_section_returns_critical_issue(): void
    {
        $html = '<!DOCTYPE html><html><body><p>Content</p></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('critical', $finding->severity);
    }

    public function test_empty_html_returns_critical_issue(): void
    {
        $html = '';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('critical', $finding->severity);
    }

    // =========================================================================
    // Empty Title Tests (Critical)
    // =========================================================================

    public function test_empty_title_tag_returns_critical_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title></title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('critical', $finding->severity);
        $this->assertStringContainsString('empty', strtolower($finding->description));
    }

    public function test_whitespace_only_title_returns_critical_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title>     </title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('critical', $finding->severity);
    }

    public function test_newline_only_title_returns_critical_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title>' . "\n\n" . '</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('critical', $finding->severity);
    }

    // =========================================================================
    // Optimal Length Tests (No Issue)
    // =========================================================================

    public function test_optimal_title_length_returns_no_issue(): void
    {
        // 55 characters - optimal range
        $title = 'Optimal Page Title for Best SEO Results Here';
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_title_at_50_chars_returns_no_issue(): void
    {
        // Exactly 50 characters
        $title = str_repeat('a', 50);
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_title_at_60_chars_returns_no_issue(): void
    {
        // Exactly 60 characters
        $title = str_repeat('a', 60);
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    // =========================================================================
    // Too Long Title Tests (Warning)
    // =========================================================================

    public function test_title_at_61_chars_returns_warning(): void
    {
        // 61 characters - just over the limit
        $title = str_repeat('a', 61);
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
        $this->assertStringContainsString('long', strtolower($finding->description));
    }

    public function test_title_at_100_chars_returns_warning(): void
    {
        $title = str_repeat('Title ', 16) . 'End'; // ~100 characters
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_title_at_200_chars_returns_warning(): void
    {
        $title = str_repeat('Very Long Title ', 12); // ~200 characters
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    // =========================================================================
    // Too Short Title Tests (Notice)
    // =========================================================================

    public function test_title_at_29_chars_returns_notice(): void
    {
        // 29 characters - just under minimum
        $title = str_repeat('a', 29);
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
        $this->assertStringContainsString('short', strtolower($finding->description));
    }

    public function test_title_at_10_chars_returns_notice(): void
    {
        $title = 'Short Titl'; // 10 characters
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
    }

    public function test_title_at_30_chars_returns_no_issue(): void
    {
        // Exactly 30 characters - minimum acceptable
        $title = str_repeat('a', 30);
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    // =========================================================================
    // Special Characters Tests
    // =========================================================================

    public function test_title_with_html_entities_returns_no_issue(): void
    {
        // 55 characters after decoding entities
        $title = 'Title &amp; Description - Optimal Length Test Here';
        $html = '<!DOCTYPE html><html><head><title>Title &amp; Description - Optimal Length Test Here</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        // Should decode entities and measure properly
        $this->assertNull($finding);
    }

    public function test_title_with_unicode_returns_no_issue(): void
    {
        // Japanese title - need at least 30 characters (each Japanese char = 1 char in mb_strlen)
        $title = '日本語のウェブサイトタイトル - 最適な長さのテストページ！';
        $this->assertGreaterThanOrEqual(30, mb_strlen($title));
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        // Should handle multibyte characters properly
        $this->assertNull($finding);
    }

    public function test_title_with_emoji_returns_no_issue(): void
    {
        // Emoji title - 47 chars (emoji counts as 1 char in mb_strlen)
        $title = 'Great Website 🚀 - Check Our Services Today! Quality!';
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        // Emoji are multibyte - should be counted properly
        $this->assertNull($finding);
    }

    public function test_title_with_special_chars_still_works(): void
    {
        $title = 'Best "Quotes" & \'Apostrophes\' - 50% Off Today!'; // 47 chars
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    // =========================================================================
    // Multiple Title Tags Tests
    // =========================================================================

    public function test_multiple_title_tags_uses_first(): void
    {
        $html = '<!DOCTYPE html><html><head><title>First Title - Good Length Here</title><title>Second Title</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        // Should use first title which is valid (35 chars)
        $this->assertNull($finding);
    }

    public function test_title_in_body_is_ignored(): void
    {
        // Note: "Real Title Here" is 15 chars - below 30 minimum, triggers notice
        $html = '<!DOCTYPE html><html><head><title>Real Title Here</title></head><body><title>Fake Title In Body</title></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        // Should use title from head, but it's too short
        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    public function test_null_crawl_result_returns_null(): void
    {
        $checker = new TitleTagChecker();
        $finding = $checker->check(null);

        $this->assertNull($finding);
    }

    public function test_malformed_html_extracts_title_if_possible(): void
    {
        // Note: "Test Title Here" is 15 chars - below 30 minimum, triggers notice
        $html = '<html><title>Test Title Here';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        // Should attempt to extract title even from malformed HTML
        // But it's too short
        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
    }

    public function test_title_with_nested_tags(): void
    {
        // Note: HTML tags inside <title> are invalid and treated as literal text content
        // The title "Main <span>Title</span>" is 23 characters - below the 30-char minimum
        $html = '<!DOCTYPE html><html><head><title>Main <span>Title</span></title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        // Should detect short title (23 chars < 30 minimum)
        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
        $this->assertStringContainsString('short', strtolower($finding->description));
    }

    public function test_title_with_excessive_whitespace(): void
    {
        $title = '  Title   With    Excessive     Whitespace  ';
        $html = "<!DOCTYPE html><html><head><title>{$title}</title></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        // Should trim whitespace before measuring
        $this->assertNull($finding);
    }

    // =========================================================================
    // Issue Finding Structure Tests
    // =========================================================================

    public function test_issue_finding_has_correct_type(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertEquals('title_tag', $finding->issueType);
    }

    public function test_issue_finding_has_recommendation(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertNotEmpty($finding->recommendation);
        $this->assertStringContainsString('title', strtolower($finding->recommendation));
    }

    public function test_issue_finding_has_context(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Short</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new TitleTagChecker();
        $finding = $checker->check($result);

        $this->assertIsArray($finding->context);
        $this->assertArrayHasKey('title', $finding->context);
        $this->assertArrayHasKey('length', $finding->context);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createCrawlResult(string $html): CrawlResult
    {
        return new CrawlResult(
            url: 'https://example.com',
            statusCode: 200,
            responseTimeMs: 100.0,
            html: $html
        );
    }
}
