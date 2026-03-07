<?php

namespace Tests\Unit\Services\Audit\Checkers;

use Tests\TestCase;
use App\Services\Audit\Checkers\MetaDescriptionChecker;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;

class MetaDescriptionCheckerTest extends TestCase
{
    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    public function test_meta_description_checker_can_be_instantiated(): void
    {
        $checker = new MetaDescriptionChecker();

        $this->assertInstanceOf(MetaDescriptionChecker::class, $checker);
    }

    public function test_meta_description_checker_implements_checker_interface(): void
    {
        $checker = new MetaDescriptionChecker();

        $this->assertInstanceOf(
            \App\Services\Audit\Contracts\CheckerInterface::class,
            $checker
        );
    }

    // =========================================================================
    // Missing Meta Description Tests (Warning)
    // =========================================================================

    public function test_missing_meta_description_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page Title</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
        $this->assertStringContainsString('missing', strtolower($finding->description));
    }

    public function test_no_head_section_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><body><p>Content</p></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_empty_html_returns_warning(): void
    {
        $html = '';
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    // =========================================================================
    // Empty Content Attribute Tests (Warning)
    // =========================================================================

    public function test_empty_content_attribute_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description" content=""></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
        $this->assertStringContainsString('empty', strtolower($finding->description));
    }

    public function test_whitespace_only_content_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description" content="     "></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_missing_content_attribute_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    // =========================================================================
    // Optimal Length Tests (No Issue)
    // =========================================================================

    public function test_optimal_description_length_returns_no_issue(): void
    {
        // 155 characters - optimal range
        $description = 'This is an optimal meta description that provides a compelling summary of the page content for search engine results. It is between 150 and 160 characters long.';
        $this->assertGreaterThanOrEqual(150, strlen($description));
        $this->assertLessThanOrEqual(160, strlen($description));
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_description_at_50_chars_returns_no_issue(): void
    {
        // Exactly 50 characters - minimum acceptable
        $description = str_repeat('a', 50);
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_description_at_160_chars_returns_no_issue(): void
    {
        // Exactly 160 characters - maximum acceptable
        $description = str_repeat('a', 160);
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    // =========================================================================
    // Too Long Description Tests (Notice)
    // =========================================================================

    public function test_description_at_161_chars_returns_notice(): void
    {
        // 161 characters - just over the limit
        $description = str_repeat('a', 161);
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
        $this->assertStringContainsString('long', strtolower($finding->description));
    }

    public function test_description_at_200_chars_returns_notice(): void
    {
        $description = str_repeat('Long description. ', 10); // ~200 characters
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
    }

    public function test_description_at_300_chars_returns_notice(): void
    {
        $description = str_repeat('Very long meta description content here. ', 7); // ~300 characters
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
    }

    // =========================================================================
    // Too Short Description Tests (Notice)
    // =========================================================================

    public function test_description_at_49_chars_returns_notice(): void
    {
        // 49 characters - just under minimum
        $description = str_repeat('a', 49);
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
        $this->assertStringContainsString('short', strtolower($finding->description));
    }

    public function test_description_at_30_chars_returns_notice(): void
    {
        $description = 'Short description text';
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('notice', $finding->severity);
    }

    // =========================================================================
    // Special Characters Tests
    // =========================================================================

    public function test_description_with_html_entities_returns_no_issue(): void
    {
        $description = 'Description with &amp; ampersand and &lt; brackets - optimal length for SEO results here!';
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        // Should decode entities and measure properly
        $this->assertNull($finding);
    }

    public function test_description_with_unicode_returns_no_issue(): void
    {
        // Use a longer description that meets the 50-160 character requirement
        $description = 'このメタディスクリプションは、検索エンジン結果ページに表示される最適な長さのテキストです。SEO対策に最適。';
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        // Should handle multibyte characters properly
        $this->assertNull($finding);
    }

    public function test_description_with_special_chars_still_works(): void
    {
        // Use HTML entities for quotes to avoid breaking the HTML attribute
        $description = 'Best &quot;Products&quot; &amp; Services - Save 50% Today! Limited Time Offer for All New Customers.';
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    // =========================================================================
    // Multiple Meta Descriptions Tests
    // =========================================================================

    public function test_multiple_meta_descriptions_uses_first(): void
    {
        $description1 = 'First description - optimal length for SEO and search results pages.';
        $description2 = 'Second description that is also here but should be ignored.';
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description1}\"><meta name=\"description\" content=\"{$description2}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        // Should use first description which is valid
        $this->assertNull($finding);
    }

    public function test_case_insensitive_name_attribute(): void
    {
        $description = 'Description with uppercase NAME attribute - proper length.';
        
        $html = "<!DOCTYPE html><html><head><meta NAME=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        // Should find description regardless of case
        $this->assertNull($finding);
    }

    public function test_mixed_case_name_attribute(): void
    {
        $description = 'Description with mixed case Description attribute value.';
        
        $html = "<!DOCTYPE html><html><head><meta name=\"Description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        // Should find description regardless of case
        $this->assertNull($finding);
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    public function test_null_crawl_result_returns_null(): void
    {
        $checker = new MetaDescriptionChecker();
        $finding = $checker->check(null);

        $this->assertNull($finding);
    }

    public function test_malformed_html_extracts_description_if_possible(): void
    {
        $description = 'Valid description text for the malformed HTML test.';
        $html = "<html><head><meta name=\"description\" content=\"{$description}\">";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        // Should attempt to extract description even from malformed HTML
        $this->assertNull($finding);
    }

    public function test_description_with_excessive_whitespace(): void
    {
        $description = '  Description   With    Excessive     Whitespace  That Should Be Trimmed  ';
        
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        // Should trim whitespace before measuring
        $this->assertNull($finding);
    }

    public function test_other_meta_tags_ignored(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="keywords" content="test, keywords"><meta name="author" content="Test Author"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        // Should not find description among other meta tags
        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    // =========================================================================
    // Issue Finding Structure Tests
    // =========================================================================

    public function test_issue_finding_has_correct_type(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertEquals('meta_description', $finding->issueType);
    }

    public function test_issue_finding_has_recommendation(): void
    {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertNotEmpty($finding->recommendation);
        $this->assertStringContainsString('description', strtolower($finding->recommendation));
    }

    public function test_issue_finding_has_context(): void
    {
        $description = 'Too short';
        $html = "<!DOCTYPE html><html><head><meta name=\"description\" content=\"{$description}\"></head><body></body></html>";
        $result = $this->createCrawlResult($html);

        $checker = new MetaDescriptionChecker();
        $finding = $checker->check($result);

        $this->assertIsArray($finding->context);
        $this->assertArrayHasKey('description', $finding->context);
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
