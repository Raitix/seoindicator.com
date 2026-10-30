<?php

namespace Tests\Unit\Services\Audit\Checkers;

use Tests\TestCase;
use App\Services\Audit\Checkers\H1Checker;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;

class H1CheckerTest extends TestCase
{
    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    public function test_h1_checker_can_be_instantiated(): void
    {
        $checker = new H1Checker();

        $this->assertInstanceOf(H1Checker::class, $checker);
    }

    public function test_h1_checker_implements_checker_interface(): void
    {
        $checker = new H1Checker();

        $this->assertInstanceOf(
            \App\Services\Audit\Contracts\CheckerInterface::class,
            $checker
        );
    }

    // =========================================================================
    // Missing H1 Tests (Warning)
    // =========================================================================

    public function test_missing_h1_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><p>Content</p></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
        $this->assertStringContainsString('missing', strtolower($finding->description));
    }

    public function test_empty_body_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_only_h2_to_h6_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h2>Subtitle</h2><h3>Section</h3></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_empty_html_returns_warning(): void
    {
        $html = '';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    // =========================================================================
    // Empty H1 Tests (Warning)
    // =========================================================================

    public function test_empty_h1_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1></h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
        $this->assertStringContainsString('empty', strtolower($finding->description));
    }

    public function test_whitespace_only_h1_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>     </h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_h1_with_only_br_tag_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1><br></h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    // =========================================================================
    // Single H1 Tests (No Issue)
    // =========================================================================

    public function test_single_h1_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>Main Title</h1><p>Content</p></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_h1_with_nested_elements_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1><span>Main</span> <em>Title</em></h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_h1_with_link_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1><a href="/">Site Name</a></h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    // =========================================================================
    // Multiple H1 Tests (Notice)
    // =========================================================================

    public function test_two_h1s_returns_notice(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>First Title</h1><h1>Second Title</h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
        $this->assertStringContainsString('multiple', strtolower($finding->description));
    }

    public function test_three_h1s_returns_notice(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>Title 1</h1><h1>Title 2</h1><h1>Title 3</h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
    }

    public function test_many_h1s_returns_notice(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body>';
        for ($i = 1; $i <= 5; $i++) {
            $html .= "<h1>Title {$i}</h1>";
        }
        $html .= '</body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
    }

    public function test_multiple_h1s_context_shows_count(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>First</h1><h1>Second</h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertArrayHasKey('count', $finding->context);
        $this->assertEquals(2, $finding->context['count']);
    }

    // =========================================================================
    // Mixed Headings Tests
    // =========================================================================

    public function test_h1_with_other_headings_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>Main Title</h1><h2>Section</h2><h3>Subsection</h3><h4>Detail</h4></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        // Only H1 is checked, other headings don't matter
        $this->assertNull($finding);
    }

    public function test_h2_to_h6_without_h1_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h2>Main Section</h2><h3>Subsection</h3></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    // =========================================================================
    // Special Characters Tests
    // =========================================================================

    public function test_h1_with_html_entities_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>Title &amp; Description</h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_h1_with_unicode_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>メインタイトル</h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_h1_with_emoji_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>Welcome 🚀 to Our Site</h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    public function test_null_crawl_result_returns_null(): void
    {
        $checker = new H1Checker();
        $finding = $checker->check(null);

        $this->assertNull($finding);
    }

    public function test_malformed_html_extracts_h1_if_possible(): void
    {
        $html = '<html><body><h1>Main Title';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        // Should attempt to extract H1 even from malformed HTML
        $this->assertNull($finding);
    }

    public function test_h1_with_excessive_whitespace(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1>  Main   Title  </h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        // Should handle whitespace
        $this->assertNull($finding);
    }

    public function test_h1_in_different_sections(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><header><h1>Logo</h1></header><main><h1>Page Title</h1></main></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        // Two H1s in different sections still counts
        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
    }

    public function test_hidden_h1_still_detected(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body><h1 style="display:none">Hidden Title</h1></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        // Hidden H1 is still detected
        $this->assertNull($finding);
    }

    // =========================================================================
    // Issue Finding Structure Tests
    // =========================================================================

    public function test_issue_finding_has_correct_type(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertEquals('h1_tag', $finding->issueType);
    }

    public function test_issue_finding_has_recommendation(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertNotEmpty($finding->recommendation);
        $this->assertStringContainsString('h1', strtolower($finding->recommendation));
    }

    public function test_issue_finding_has_context(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new H1Checker();
        $finding = $checker->check($result);

        $this->assertIsArray($finding->context);
        $this->assertArrayHasKey('count', $finding->context);
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
