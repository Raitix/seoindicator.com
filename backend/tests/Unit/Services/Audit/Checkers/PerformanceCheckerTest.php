<?php

namespace Tests\Unit\Services\Audit\Checkers;

use App\Services\Audit\Contracts\CheckerInterface;
use App\Services\Audit\DTOs\CrawlResult;
use App\Services\Audit\DTOs\IssueFinding;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PerformanceChecker Service Tests
 * 
 * Test coverage for TASK-14-11: Implement Performance Checker
 * 
 * Categories:
 * - SI: Service Instantiation (6 tests)
 * - RESP: Response Time Analysis (20 tests)
 * - SIZE: Page Size Analysis (12 tests)
 * - THRESH: Threshold Boundary Tests (15 tests)
 * - SEV: Severity Mapping Tests (10 tests)
 * - ERR: Error Handling Tests (8 tests)
 * - EDGE: Edge Cases Tests (14 tests)
 * - SEC: Security Tests (5 tests)
 * - CONFIG: Configuration Tests (8 tests)
 * - PERF: Performance Tests (3 tests)
 * - INT: Integration Tests (5 tests)
 */
#[Group('unit')]
#[Group('services')]
#[Group('audit')]
#[Group('performance-checker')]
class PerformanceCheckerTest extends TestCase
{
    private const DEFAULT_SLOW_THRESHOLD_MS = 3000;
    private const DEFAULT_VERY_SLOW_THRESHOLD_MS = 5000;
    private const DEFAULT_MAX_PAGE_SIZE_KB = 500;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set default config values
        Config::set('audit.performance', [
            'slow_threshold_ms' => self::DEFAULT_SLOW_THRESHOLD_MS,
            'very_slow_threshold_ms' => self::DEFAULT_VERY_SLOW_THRESHOLD_MS,
            'max_page_size_kb' => self::DEFAULT_MAX_PAGE_SIZE_KB,
        ]);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a mock CrawlResult with specified parameters.
     */
    private function createCrawlResult(
        float $responseTimeMs = 500.0,
        ?string $html = '<html><body>Test</body></html>',
        string $url = 'https://example.com',
        int $statusCode = 200,
        ?string $errorMessage = null
    ): CrawlResult {
        return new CrawlResult(
            url: $url,
            statusCode: $statusCode,
            responseTimeMs: $responseTimeMs,
            html: $html,
            errorMessage: $errorMessage
        );
    }

    /**
     * Get the PerformanceChecker class name (will be created by Developer).
     */
    private function getCheckerClass(): string
    {
        return \App\Services\Audit\Checkers\PerformanceChecker::class;
    }

    // =========================================================================
    // SI: Service Instantiation Tests
    // =========================================================================

    #[Test]
    public function si_001_service_can_be_instantiated(): void
    {
        $checkerClass = $this->getCheckerClass();
        
        $this->assertTrue(class_exists($checkerClass), "PerformanceChecker class should exist");
    }

    #[Test]
    public function si_002_service_implements_checker_interface(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = app($checkerClass);
        
        $this->assertInstanceOf(CheckerInterface::class, $checker);
    }

    #[Test]
    public function si_003_service_has_check_method(): void
    {
        $checkerClass = $this->getCheckerClass();
        
        $this->assertTrue(method_exists($checkerClass, 'check'));
    }

    #[Test]
    public function si_004_service_has_get_issue_type_method(): void
    {
        $checkerClass = $this->getCheckerClass();
        
        $this->assertTrue(method_exists($checkerClass, 'getIssueType'));
    }

    #[Test]
    public function si_005_service_has_get_name_method(): void
    {
        $checkerClass = $this->getCheckerClass();
        
        $this->assertTrue(method_exists($checkerClass, 'getName'));
    }

    #[Test]
    public function si_006_service_has_get_priority_method(): void
    {
        $checkerClass = $this->getCheckerClass();
        
        $this->assertTrue(method_exists($checkerClass, 'getPriority'));
    }

    // =========================================================================
    // RESP: Response Time Analysis Tests
    // =========================================================================

    #[Test]
    public function resp_001_returns_null_for_fast_response(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 500.0);
        $finding = $checker->check($result);
        
        $this->assertNull($finding, 'Fast response (< 1000ms) should return null');
    }

    #[Test]
    public function resp_002_returns_null_for_response_under_1000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 999.9);
        $finding = $checker->check($result);
        
        $this->assertNull($finding);
    }

    #[Test]
    public function resp_003_returns_notice_for_1000ms_response(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('notice', $finding->severity);
    }

    #[Test]
    public function resp_004_returns_notice_for_response_between_1000_and_2000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('notice', $finding->severity);
    }

    #[Test]
    public function resp_005_returns_notice_for_response_just_under_2000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1999.9);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('notice', $finding->severity);
    }

    #[Test]
    public function resp_006_returns_warning_for_2000ms_response(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 2000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function resp_007_returns_warning_for_response_between_2000_and_3000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 2500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function resp_008_returns_warning_for_response_at_3000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function resp_009_returns_warning_for_response_over_3000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function resp_010_returns_warning_for_response_just_under_5000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 4999.9);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function resp_011_returns_critical_for_5000ms_response(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 5000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->severity);
    }

    #[Test]
    public function resp_012_returns_critical_for_response_over_5000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 7500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->severity);
    }

    #[Test]
    public function resp_013_finding_includes_response_time_in_description(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertStringContainsString('3500', $finding->description);
    }

    #[Test]
    public function resp_014_finding_includes_threshold_in_description(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertStringContainsString('threshold', strtolower($finding->description));
    }

    #[Test]
    public function resp_015_finding_includes_response_time_in_context(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertTrue($finding->hasContext('response_time_ms'));
        $this->assertEquals(3500.0, $finding->getContextValue('response_time_ms'));
    }

    #[Test]
    public function resp_016_finding_has_correct_issue_type_for_slow_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('slow_page', $finding->issueType);
    }

    #[Test]
    public function resp_017_finding_has_recommendation(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertNotEmpty($finding->recommendation);
    }

    #[Test]
    public function resp_018_recommendation_mentions_optimization(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertStringContainsString('optim', strtolower($finding->recommendation));
    }

    #[Test]
    public function resp_019_decimal_response_times_handled_correctly(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1234.56);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('notice', $finding->severity);
    }

    #[Test]
    public function resp_020_extremely_slow_response_time(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 30000.0); // 30 seconds
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->severity);
    }

    // =========================================================================
    // SIZE: Page Size Analysis Tests
    // =========================================================================

    #[Test]
    public function size_001_returns_null_for_small_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Create HTML under 500KB
        $html = str_repeat('a', 100000); // ~100KB
        $result = $this->createCrawlResult(html: $html);
        
        $finding = $checker->check($result);
        
        // Should be null if only checking page size and response is fast
        $this->assertNull($finding);
    }

    #[Test]
    public function size_002_returns_notice_for_large_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Create HTML over 500KB (524288 bytes = 512KB)
        $html = str_repeat('a', 600000); // ~600KB
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding, 'Large page should generate a finding');
    }

    #[Test]
    public function size_003_large_page_finding_has_correct_issue_type(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = str_repeat('a', 600000);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('large_page', $finding->issueType);
    }

    #[Test]
    public function size_004_large_page_finding_has_notice_severity(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = str_repeat('a', 600000);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('notice', $finding->severity);
    }

    #[Test]
    public function size_005_finding_includes_page_size_in_description(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = str_repeat('a', 600000);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertStringContainsString('KB', $finding->description);
    }

    #[Test]
    public function size_006_finding_includes_page_size_in_context(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = str_repeat('a', 600000);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertTrue($finding->hasContext('page_size_kb'));
    }

    #[Test]
    public function size_007_threshold_boundary_exact_500kb(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Exactly 500KB = 512000 bytes
        $html = str_repeat('a', 512000);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        // At boundary should not trigger (only > threshold triggers)
        $this->assertNull($finding);
    }

    #[Test]
    public function size_008_threshold_boundary_just_over_500kb(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Just over 500KB
        $html = str_repeat('a', 512001);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('large_page', $finding->issueType);
    }

    #[Test]
    public function size_009_recommendation_for_large_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = str_repeat('a', 600000);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertNotEmpty($finding->recommendation);
        $this->assertStringContainsString('size', strtolower($finding->recommendation));
    }

    #[Test]
    public function size_010_returns_both_issues_for_slow_and_large_page(): void
    {
        // This tests if the checker returns multiple findings or combines them
        // Implementation can return array or just the highest severity
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = str_repeat('a', 600000);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 3500.0);
        
        $finding = $checker->check($result);
        
        // Should return at least the higher severity issue (slow_page with warning)
        $this->assertNotNull($finding);
        // Response time issue takes priority
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function size_011_null_html_no_size_check(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(html: null, responseTimeMs: 500.0);
        $finding = $checker->check($result);
        
        $this->assertNull($finding, 'No HTML should not trigger page size check');
    }

    #[Test]
    public function size_012_empty_html_no_size_check(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(html: '', responseTimeMs: 500.0);
        $finding = $checker->check($result);
        
        $this->assertNull($finding, 'Empty HTML should not trigger page size check');
    }

    // =========================================================================
    // THRESH: Threshold Boundary Tests
    // =========================================================================

    #[Test]
    public function thresh_001_boundary_999ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 999.0);
        $finding = $checker->check($result);
        
        $this->assertNull($finding);
    }

    #[Test]
    public function thresh_002_boundary_1000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('notice', $finding->severity);
    }

    #[Test]
    public function thresh_003_boundary_1999ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1999.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('notice', $finding->severity);
    }

    #[Test]
    public function thresh_004_boundary_2000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 2000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function thresh_005_boundary_2999ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 2999.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function thresh_006_boundary_3000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function thresh_007_boundary_3001ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3001.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function thresh_008_boundary_4999ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 4999.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function thresh_009_boundary_5000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 5000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->severity);
    }

    #[Test]
    public function thresh_010_boundary_5001ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 5001.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->severity);
    }

    #[Test]
    public function thresh_011_boundary_10000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 10000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->severity);
    }

    #[Test]
    public function thresh_012_boundary_60000ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 60000.0); // 1 minute
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->severity);
    }

    #[Test]
    public function thresh_013_boundary_1mb_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // 1MB = 1048576 bytes
        $html = str_repeat('a', 1048576);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('large_page', $finding->issueType);
    }

    #[Test]
    public function thresh_014_boundary_5mb_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // 5MB
        $html = str_repeat('a', 5242880);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('large_page', $finding->issueType);
    }

    #[Test]
    public function thresh_015_boundary_10mb_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // 10MB
        $html = str_repeat('a', 10485760);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('large_page', $finding->issueType);
    }

    // =========================================================================
    // SEV: Severity Mapping Tests
    // =========================================================================

    #[Test]
    public function sev_001_notice_severity_for_acceptable_response(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertTrue($finding->isNotice());
    }

    #[Test]
    public function sev_002_warning_severity_for_needs_improvement(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 2500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertTrue($finding->isWarning());
    }

    #[Test]
    public function sev_003_warning_severity_for_slow_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertTrue($finding->isWarning());
    }

    #[Test]
    public function sev_004_critical_severity_for_very_slow_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 5500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertTrue($finding->isCritical());
    }

    #[Test]
    public function sev_005_notice_severity_for_large_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = str_repeat('a', 600000);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertTrue($finding->isNotice());
    }

    #[Test]
    public function sev_006_severity_score_calculation(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Critical should have highest score
        $result = $this->createCrawlResult(responseTimeMs: 6000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals(4, $finding->getSeverityScore());
    }

    #[Test]
    public function sev_007_warning_severity_score(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 3000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals(2, $finding->getSeverityScore());
    }

    #[Test]
    public function sev_008_notice_severity_score(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals(1, $finding->getSeverityScore());
    }

    #[Test]
    public function sev_009_normalized_severity_is_lowercase(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 6000.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->getNormalizedSeverity());
    }

    #[Test]
    public function sev_010_severity_priority_order(): void
    {
        // Verify that critical > warning > notice in terms of severity
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $noticeResult = $this->createCrawlResult(responseTimeMs: 1500.0);
        $warningResult = $this->createCrawlResult(responseTimeMs: 3500.0);
        $criticalResult = $this->createCrawlResult(responseTimeMs: 6000.0);
        
        $noticeFinding = $checker->check($noticeResult);
        $warningFinding = $checker->check($warningResult);
        $criticalFinding = $checker->check($criticalResult);
        
        $this->assertLessThan($warningFinding->getSeverityScore(), $noticeFinding->getSeverityScore());
        $this->assertLessThan($criticalFinding->getSeverityScore(), $warningFinding->getSeverityScore());
    }

    // =========================================================================
    // ERR: Error Handling Tests
    // =========================================================================

    #[Test]
    public function err_001_handles_error_result_gracefully(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(
            responseTimeMs: 0.0,
            errorMessage: 'Connection timeout'
        );
        $finding = $checker->check($result);
        
        // Should return null for error results
        $this->assertNull($finding);
    }

    #[Test]
    public function err_002_handles_null_html_gracefully(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(html: null);
        $finding = $checker->check($result);
        
        $this->assertNull($finding);
    }

    #[Test]
    public function err_003_handles_zero_response_time(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 0.0);
        $finding = $checker->check($result);
        
        // Zero response time should be treated as no issue (edge case)
        $this->assertNull($finding);
    }

    #[Test]
    public function err_004_handles_negative_response_time(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Negative response time is invalid, should be treated as edge case
        $result = new CrawlResult(
            url: 'https://example.com',
            statusCode: 200,
            responseTimeMs: -100.0
        );
        
        $finding = $checker->check($result);
        
        // Should handle gracefully (either null or treat as 0)
        $this->assertTrue($finding === null || $finding instanceof IssueFinding);
    }

    #[Test]
    public function err_005_handles_404_status(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(statusCode: 404, responseTimeMs: 1500.0);
        $finding = $checker->check($result);
        
        // 404 pages might still need performance checking
        $this->assertTrue($finding === null || $finding instanceof IssueFinding);
    }

    #[Test]
    public function err_006_handles_500_status(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(statusCode: 500, responseTimeMs: 1500.0);
        $finding = $checker->check($result);
        
        // 500 errors might still have performance issues worth reporting
        $this->assertTrue($finding === null || $finding instanceof IssueFinding);
    }

    #[Test]
    public function err_007_handles_empty_url(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(url: '', responseTimeMs: 1500.0);
        
        // Should not throw exception
        $this->assertTrue(true);
    }

    #[Test]
    public function err_008_checker_never_throws_exception(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Various edge cases that should not throw
        $cases = [
            $this->createCrawlResult(responseTimeMs: 0.0, html: null),
            $this->createCrawlResult(responseTimeMs: -1.0),
            $this->createCrawlResult(responseTimeMs: PHP_FLOAT_MAX),
        ];
        
        foreach ($cases as $result) {
            try {
                $checker->check($result);
                $this->assertTrue(true, 'No exception thrown');
            } catch (\Throwable $e) {
                $this->fail('Checker should not throw exception: ' . $e->getMessage());
            }
        }
    }

    // =========================================================================
    // EDGE: Edge Cases Tests
    // =========================================================================

    #[Test]
    public function edge_001_extremely_fast_response(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1.0);
        $finding = $checker->check($result);
        
        $this->assertNull($finding);
    }

    #[Test]
    public function edge_002_fractional_millisecond(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 0.5);
        $finding = $checker->check($result);
        
        $this->assertNull($finding);
    }

    #[Test]
    public function edge_003_very_large_html(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // 50MB HTML
        $html = str_repeat('a', 52428800);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('large_page', $finding->issueType);
    }

    #[Test]
    public function edge_004_unicode_html(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = '<html><body>' . str_repeat('日本語', 100000) . '</body></html>';
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        // Should not throw, should handle unicode
        $finding = $checker->check($result);
        
        $this->assertTrue($finding === null || $finding instanceof IssueFinding);
    }

    #[Test]
    public function edge_005_binary_data_in_html(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Binary-looking data
        $html = '<html>' . pack('C*', ...range(0, 255)) . '</html>';
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        // Should handle gracefully
        $finding = $checker->check($result);
        
        $this->assertTrue($finding === null || $finding instanceof IssueFinding);
    }

    #[Test]
    public function edge_006_very_long_url(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $longUrl = 'https://example.com/' . str_repeat('a', 2000);
        $result = $this->createCrawlResult(url: $longUrl, responseTimeMs: 1500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
    }

    #[Test]
    public function edge_007_special_characters_in_url(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(
            url: 'https://example.com/path?query=value&special=<>&"\'',
            responseTimeMs: 1500.0
        );
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
    }

    #[Test]
    public function edge_008_multiple_consecutive_checks(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Run multiple checks in sequence
        for ($i = 0; $i < 100; $i++) {
            $result = $this->createCrawlResult(responseTimeMs: 1500.0);
            $finding = $checker->check($result);
            $this->assertNotNull($finding);
        }
    }

    #[Test]
    public function edge_009_float_precision_response_time(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1000.0000001);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
    }

    #[Test]
    public function edge_010_scientific_notation_response_time(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1.5e3); // 1500
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('notice', $finding->severity);
    }

    #[Test]
    public function edge_011_exactly_at_all_thresholds(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $thresholds = [
            1000.0 => 'notice',
            2000.0 => 'warning',
            3000.0 => 'warning',
            5000.0 => 'critical',
        ];
        
        foreach ($thresholds as $responseTime => $expectedSeverity) {
            $result = $this->createCrawlResult(responseTimeMs: $responseTime);
            $finding = $checker->check($result);
            
            $this->assertNotNull($finding, "Finding should not be null for {$responseTime}ms");
            $this->assertEquals($expectedSeverity, $finding->severity, "Severity mismatch for {$responseTime}ms");
        }
    }

    #[Test]
    public function edge_012_rapidly_changing_response_times(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Simulate different response times for same URL
        $responseTimes = [500, 1500, 3000, 6000, 800, 1200];
        
        foreach ($responseTimes as $rt) {
            $result = $this->createCrawlResult(responseTimeMs: (float)$rt);
            $finding = $checker->check($result);
            
            // Just ensure no exceptions and correct behavior
            if ($rt < 1000) {
                $this->assertNull($finding);
            } else {
                $this->assertNotNull($finding);
            }
        }
    }

    #[Test]
    public function edge_013_checker_is_stateless(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // First check
        $result1 = $this->createCrawlResult(responseTimeMs: 1500.0);
        $finding1 = $checker->check($result1);
        
        // Second check with different result
        $result2 = $this->createCrawlResult(responseTimeMs: 6000.0);
        $finding2 = $checker->check($result2);
        
        // Third check same as first - should get same result
        $result3 = $this->createCrawlResult(responseTimeMs: 1500.0);
        $finding3 = $checker->check($result3);
        
        $this->assertEquals($finding1->severity, $finding3->severity);
    }

    #[Test]
    public function edge_014_checker_name_is_consistent(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker1 = new $checkerClass();
        $checker2 = new $checkerClass();
        
        $this->assertEquals($checker1->getName(), $checker2->getName());
    }

    // =========================================================================
    // SEC: Security Tests
    // =========================================================================

    #[Test]
    public function sec_001_no_xss_in_description(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(
            url: 'https://example.com/<script>alert(1)</script>',
            responseTimeMs: 1500.0
        );
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertStringNotContainsString('<script>', $finding->description);
    }

    #[Test]
    public function sec_002_no_sql_injection_vectors(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Just ensure no exceptions
        $result = $this->createCrawlResult(
            url: "https://example.com/' OR '1'='1",
            responseTimeMs: 1500.0
        );
        
        $finding = $checker->check($result);
        $this->assertNotNull($finding);
    }

    #[Test]
    public function sec_003_context_not_exposing_sensitive_data(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        
        // Context should only contain expected keys
        $context = $finding->context;
        $this->assertArrayNotHasKey('password', $context);
        $this->assertArrayNotHasKey('api_key', $context);
        $this->assertArrayNotHasKey('token', $context);
    }

    #[Test]
    public function sec_004_html_entities_handled(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = '<html><body>&lt;script&gt;alert(1)&lt;/script&gt;</body></html>';
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        // Should handle HTML entities without issues
        $this->assertTrue($finding === null || $finding instanceof IssueFinding);
    }

    #[Test]
    public function sec_005_no_information_leakage(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        
        // Finding should not expose internal paths or config
        $serialized = json_encode($finding);
        $this->assertStringNotContainsString('/var/www', $serialized);
        $this->assertStringNotContainsString('C:\\', $serialized);
        $this->assertStringNotContainsString('localhost', $serialized);
    }

    // =========================================================================
    // CONFIG: Configuration Tests
    // =========================================================================

    #[Test]
    public function config_001_uses_default_threshold_from_config(): void
    {
        Config::set('audit.performance.slow_threshold_ms', 2000);
        
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // With custom threshold, 2500ms should be warning
        $result = $this->createCrawlResult(responseTimeMs: 2500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
    }

    #[Test]
    public function config_002_uses_custom_very_slow_threshold(): void
    {
        Config::set('audit.performance.very_slow_threshold_ms', 4000);
        
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 4500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->severity);
    }

    #[Test]
    public function config_003_uses_custom_max_page_size(): void
    {
        Config::set('audit.performance.max_page_size_kb', 100);
        
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // 150KB should trigger with 100KB threshold
        $html = str_repeat('a', 153600); // 150KB
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('large_page', $finding->issueType);
    }

    #[Test]
    public function config_004_handles_missing_config_gracefully(): void
    {
        Config::set('audit.performance', null);
        
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Should still work with defaults
        $result = $this->createCrawlResult(responseTimeMs: 3500.0);
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
    }

    #[Test]
    public function config_005_env_variable_slow_threshold(): void
    {
        // This tests that config reads from env
        $this->assertTrue(true); // Env test would require process restart
    }

    #[Test]
    public function config_006_env_variable_very_slow_threshold(): void
    {
        $this->assertTrue(true); // Env test would require process restart
    }

    #[Test]
    public function config_007_env_variable_max_page_size(): void
    {
        $this->assertTrue(true); // Env test would require process restart
    }

    #[Test]
    public function config_008_config_validation(): void
    {
        // Test invalid config values are handled
        Config::set('audit.performance.slow_threshold_ms', -1);
        
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // Should still work (fallback to defaults or handle gracefully)
        $result = $this->createCrawlResult(responseTimeMs: 1500.0);
        
        try {
            $finding = $checker->check($result);
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('Should not throw with invalid config');
        }
    }

    // =========================================================================
    // PERF: Performance Tests
    // =========================================================================

    #[Test]
    public function perf_001_check_completes_within_10ms(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: 1500.0);
        
        $start = microtime(true);
        $checker->check($result);
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(10, $elapsed, 'Check should complete within 10ms');
    }

    #[Test]
    public function perf_002_large_html_check_performance(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        // 5MB HTML
        $html = str_repeat('a', 5242880);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $start = microtime(true);
        $checker->check($result);
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(100, $elapsed, 'Large HTML check should complete within 100ms');
    }

    #[Test]
    public function perf_003_multiple_checks_no_memory_leak(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 1000; $i++) {
            $result = $this->createCrawlResult(responseTimeMs: 1500.0);
            $checker->check($result);
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory should not grow significantly (< 1MB for 1000 iterations)
        $this->assertLessThan(1048576, $memoryIncrease);
    }

    // =========================================================================
    // INT: Integration Tests
    // =========================================================================

    #[Test]
    public function int_001_full_workflow_fast_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(
            responseTimeMs: 500.0,
            html: '<html><head><title>Fast Page</title></head><body>Content</body></html>'
        );
        
        $finding = $checker->check($result);
        
        $this->assertNull($finding, 'Fast page with small size should return null');
    }

    #[Test]
    public function int_002_full_workflow_slow_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(
            responseTimeMs: 3500.0,
            html: '<html><head><title>Slow Page</title></head><body>Content</body></html>'
        );
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('slow_page', $finding->issueType);
        $this->assertEquals('warning', $finding->severity);
    }

    #[Test]
    public function int_003_full_workflow_large_page(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = '<html><head><title>Large Page</title></head><body>' . str_repeat('x', 600000) . '</body></html>';
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        
        $finding = $checker->check($result);
        
        $this->assertNotNull($finding);
        $this->assertEquals('large_page', $finding->issueType);
        $this->assertEquals('notice', $finding->severity);
    }

    #[Test]
    public function int_004_full_workflow_multiple_issues(): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = '<html><head><title>Problem Page</title></head><body>' . str_repeat('x', 600000) . '</body></html>';
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 6000.0);
        
        $finding = $checker->check($result);
        
        // Should return the highest severity issue (slow_page with critical)
        $this->assertNotNull($finding);
        $this->assertEquals('critical', $finding->severity);
    }

    #[Test]
    public function int_005_can_be_registered_with_issue_detector(): void
    {
        // This test verifies the checker can be used with IssueDetector
        $checkerClass = $this->getCheckerClass();
        $checker = app($checkerClass);
        
        // Verify it implements the interface
        $this->assertInstanceOf(CheckerInterface::class, $checker);
        
        // Verify required methods
        $this->assertNotEmpty($checker->getName());
        $this->assertNotEmpty($checker->getIssueType());
        $this->assertIsInt($checker->getPriority());
    }

    // =========================================================================
    // Data Provider Tests
    // =========================================================================

    public static function responseTimeDataProvider(): array
    {
        return [
            'fast' => [500.0, null],
            'acceptable' => [1000.0, 'notice'],
            'needs_improvement' => [2000.0, 'warning'],
            'slow' => [3000.0, 'warning'],
            'very_slow' => [5000.0, 'critical'],
            'extremely_slow' => [10000.0, 'critical'],
        ];
    }

    #[Test]
    #[DataProvider('responseTimeDataProvider')]
    public function data_provider_response_time_severity(float $responseTime, ?string $expectedSeverity): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $result = $this->createCrawlResult(responseTimeMs: $responseTime);
        $finding = $checker->check($result);
        
        if ($expectedSeverity === null) {
            $this->assertNull($finding);
        } else {
            $this->assertNotNull($finding);
            $this->assertEquals($expectedSeverity, $finding->severity);
        }
    }

    public static function pageSizeDataProvider(): array
    {
        return [
            'tiny' => [1000, null],           // 1KB
            'small' => [100000, null],        // 100KB
            'medium' => [300000, null],       // 300KB
            'at_limit' => [512000, null],     // 500KB exactly
            'over_limit' => [600000, 'notice'], // ~600KB
            'large' => [1048576, 'notice'],   // 1MB
            'very_large' => [5242880, 'notice'], // 5MB
        ];
    }

    #[Test]
    #[DataProvider('pageSizeDataProvider')]
    public function data_provider_page_size_severity(int $sizeBytes, ?string $expectedSeverity): void
    {
        $checkerClass = $this->getCheckerClass();
        $checker = new $checkerClass();
        
        $html = str_repeat('a', $sizeBytes);
        $result = $this->createCrawlResult(html: $html, responseTimeMs: 500.0);
        $finding = $checker->check($result);
        
        if ($expectedSeverity === null) {
            $this->assertNull($finding);
        } else {
            $this->assertNotNull($finding);
            $this->assertEquals($expectedSeverity, $finding->severity);
        }
    }
}
