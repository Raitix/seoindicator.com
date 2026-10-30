<?php

namespace Tests\Unit\Services\Audit\Checkers;

use Tests\TestCase;
use App\Services\Audit\Checkers\ViewportChecker;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;

class ViewportCheckerTest extends TestCase
{
    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    public function test_viewport_checker_can_be_instantiated(): void
    {
        $checker = new ViewportChecker();

        $this->assertInstanceOf(ViewportChecker::class, $checker);
    }

    public function test_viewport_checker_implements_checker_interface(): void
    {
        $checker = new ViewportChecker();

        $this->assertInstanceOf(
            \App\Services\Audit\Contracts\CheckerInterface::class,
            $checker
        );
    }

    // =========================================================================
    // Missing Viewport Tests (Warning)
    // =========================================================================

    public function test_missing_viewport_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
        $this->assertStringContainsString('missing', strtolower($finding->description));
    }

    public function test_no_head_section_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_empty_html_returns_warning(): void
    {
        $html = '';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    // =========================================================================
    // Valid Viewport Tests (No Issue)
    // =========================================================================

    public function test_standard_viewport_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_viewport_with_width_only_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_viewport_with_additional_properties_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_viewport_with_user_scalable_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    // =========================================================================
    // Invalid Viewport Tests (Warning)
    // =========================================================================

    public function test_missing_width_device_width_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="initial-scale=1"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
        $this->assertStringContainsString('width=device-width', strtolower($finding->description));
    }

    public function test_fixed_width_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=960"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_empty_content_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content=""></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_missing_content_attribute_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_whitespace_content_returns_warning(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="   "></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    // =========================================================================
    // Case Insensitivity Tests
    // =========================================================================

    public function test_uppercase_name_attribute_still_works(): void
    {
        $html = '<!DOCTYPE html><html><head><meta NAME="viewport" content="width=device-width"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_mixed_case_name_attribute_still_works(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="Viewport" content="width=device-width"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_uppercase_viewport_value_still_works(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="VIEWPORT" content="width=device-width"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    // =========================================================================
    // Multiple Viewport Tags Tests
    // =========================================================================

    public function test_multiple_viewport_tags_uses_first(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width"><meta name="viewport" content="width=960"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        // Should use first viewport which is valid
        $this->assertNull($finding);
    }

    // =========================================================================
    // Edge Cases Tests
    // =========================================================================

    public function test_null_crawl_result_returns_null(): void
    {
        $checker = new ViewportChecker();
        $finding = $checker->check(null);

        $this->assertNull($finding);
    }

    public function test_malformed_html_extracts_viewport_if_possible(): void
    {
        $html = '<html><head><meta name="viewport" content="width=device-width"';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        // Should attempt to extract viewport even from malformed HTML
        $this->assertNull($finding);
    }

    public function test_other_meta_tags_ignored(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="description" content="test"><meta name="keywords" content="test"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        // Should not find viewport among other meta tags
        $this->assertInstanceOf(IssueFinding::class, $finding);
        $this->assertEquals('warning', $finding->severity);
    }

    public function test_viewport_with_extra_spaces(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="  width=device-width,  initial-scale=1  "></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        // Should handle extra spaces in content
        $this->assertNull($finding);
    }

    // =========================================================================
    // Issue Finding Structure Tests
    // =========================================================================

    public function test_issue_finding_has_correct_type(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertEquals('viewport', $finding->issueType);
    }

    public function test_issue_finding_has_recommendation(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Page</title></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNotEmpty($finding->recommendation);
        $this->assertStringContainsString('viewport', strtolower($finding->recommendation));
    }

    public function test_issue_finding_has_context(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=960"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertIsArray($finding->context);
        $this->assertArrayHasKey('content', $finding->context);
    }

    // =========================================================================
    // Mobile-First Indexing Tests
    // =========================================================================

    public function test_viewport_with_minimum_scale_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, minimum-scale=1"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
    }

    public function test_viewport_shrink_to_fit_returns_no_issue(): void
    {
        $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"></head><body></body></html>';
        $result = $this->createCrawlResult($html);

        $checker = new ViewportChecker();
        $finding = $checker->check($result);

        $this->assertNull($finding);
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
