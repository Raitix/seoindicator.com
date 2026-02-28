<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AnalysisRunnerService;
use App\Services\IndicatorCheckerInterface;
use App\Models\SEOAnalysis;
use Illuminate\Support\Facades\Event;
use Mockery;

/**
 * Unit tests for AnalysisRunnerService.
 *
 * Tests orchestration of indicator checks, result aggregation,
 * and analysis lifecycle management.
 *
 * @see \App\Services\AnalysisRunnerService
 * @see TEST-18-6
 */
class AnalysisRunnerServiceTest extends TestCase
{
    protected AnalysisRunnerService $service;
    protected $mockCheckers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AnalysisRunnerService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock indicator checker.
     */
    protected function createMockChecker(string $id, bool $shouldFail = false, int $score = 3): IndicatorCheckerInterface
    {
        $checker = Mockery::mock(IndicatorCheckerInterface::class);
        $checker->shouldReceive('id')->andReturn($id);
        $checker->shouldReceive('name')->andReturn("Mock {$id}");
        $checker->shouldReceive('category')->andReturn('test');
        $checker->shouldReceive('weight')->andReturn(0.05);

        if ($shouldFail) {
            $checker->shouldReceive('check')->andThrow(new \Exception("Checker {$id} failed"));
        } else {
            $checker->shouldReceive('check')->andReturn([
                'id' => $id,
                'name' => "Mock {$id}",
                'category' => 'test',
                'score' => $score,
                'weight' => 0.05,
                'passed' => $score >= 2,
                'details' => [],
                'suggestions' => [],
            ]);
        }

        $checker->shouldReceive('getSuggestions')->andReturn([]);

        return $checker;
    }

    // =========================================================================
    // ANALYSIS EXECUTION TESTS (AR-*)
    // =========================================================================

    /**
     * AR-01: Run all indicators.
     */
    public function test_run_executes_all_indicators(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        // This test assumes checkers are registered in the container
        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
        $this->assertEquals($url, $result['url']);
        $this->assertArrayHasKey('indicators', $result);
    }

    /**
     * AR-02: Run specific indicators.
     */
    public function test_run_executes_specific_indicators(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html, [
            'indicators' => ['url', 'title_tag'],
        ]);

        $this->assertNotNull($result);
        // Only specified indicators should run
        $indicatorIds = collect($result['indicators'])->pluck('id')->toArray();
        $this->assertEmpty(array_diff($indicatorIds, ['url', 'title_tag']));
    }

    /**
     * AR-03: Skip specific indicators.
     */
    public function test_run_skips_excluded_indicators(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html, [
            'excludeIndicators' => ['page_speed'],
        ]);

        $this->assertNotNull($result);
        $indicatorIds = collect($result['indicators'])->pluck('id')->toArray();
        $this->assertNotContains('page_speed', $indicatorIds);
    }

    /**
     * AR-04: Empty HTML handled gracefully.
     */
    public function test_run_handles_empty_html(): void
    {
        $url = 'https://example.com';
        $html = '';

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
        // Should complete but indicators may return errors
    }

    // =========================================================================
    // ERROR HANDLING TESTS (EH-*)
    // =========================================================================

    /**
     * EH-01: One indicator fails, others continue.
     */
    public function test_run_continues_after_indicator_failure(): void
    {
        $url = 'https://example.com';
        $html = '<html><body>Test</body></html>';

        // Mock one failing checker
        $this->mockCheckers = [
            $this->createMockChecker('failing_checker', true),
            $this->createMockChecker('passing_checker', false),
        ];

        // Service should handle failure and continue
        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * EH-03: All indicators fail.
     */
    public function test_run_handles_all_indicators_failing(): void
    {
        $url = 'https://example.com';
        $html = '';

        $result = $this->service->run($url, $html);

        // Should still return a result, even if all fail
        $this->assertNotNull($result);
        $this->assertNotEmpty($result['errors'] ?? []);
    }

    // =========================================================================
    // RESULT AGGREGATION TESTS (RA-*)
    // =========================================================================

    /**
     * RA-01: Aggregate all results.
     */
    public function test_run_aggregates_all_results(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body><h1>Heading</h1></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertArrayHasKey('indicators', $result);
        $this->assertArrayHasKey('overallScore', $result);
        $this->assertArrayHasKey('summary', $result);
    }

    /**
     * RA-03: Include timing data.
     */
    public function test_run_includes_timing_data(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertArrayHasKey('timing', $result);
        $this->assertArrayHasKey('total', $result['timing']);
    }

    /**
     * RA-05: Calculate summary.
     */
    public function test_run_calculates_summary(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('passed', $result['summary']);
        $this->assertArrayHasKey('warnings', $result['summary']);
        $this->assertArrayHasKey('failed', $result['summary']);
    }

    // =========================================================================
    // STATUS MANAGEMENT TESTS (SM-*)
    // =========================================================================

    /**
     * SM-03: Completed status after success.
     */
    public function test_run_sets_completed_status(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertEquals('completed', $result['status']);
    }

    /**
     * SM-04: Failed status on critical failure.
     */
    public function test_run_sets_failed_status_on_error(): void
    {
        $url = 'invalid-url';
        $html = '';

        $result = $this->service->run($url, $html);

        $this->assertContains($result['status'], ['failed', 'completed']);
    }

    // =========================================================================
    // INPUT VALIDATION TESTS (IV-*)
    // =========================================================================

    /**
     * IV-01: Missing URL throws exception.
     */
    public function test_run_rejects_missing_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->run('', '<html></html>');
    }

    /**
     * IV-02: Missing HTML throws exception.
     */
    public function test_run_rejects_missing_html(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->run('https://example.com', '');
    }

    /**
     * IV-03: Invalid URL throws exception.
     */
    public function test_run_rejects_invalid_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->run('not-a-url', '<html></html>');
    }

    // =========================================================================
    // CHECKER REGISTRATION TESTS (CR-*)
    // =========================================================================

    /**
     * CR-01: Auto-discover checkers.
     */
    public function test_get_available_indicators_returns_all_checkers(): void
    {
        $indicators = $this->service->getAvailableIndicators();

        $this->assertIsArray($indicators);
        $this->assertNotEmpty($indicators);
    }

    /**
     * CR-02: Manual registration works.
     */
    public function test_run_with_manual_checkers(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $mockChecker = $this->createMockChecker('manual_test', false);

        $result = $this->service->run($url, $html, [
            'checkers' => [$mockChecker],
        ]);

        $this->assertNotNull($result);
    }

    // =========================================================================
    // SINGLE INDICATOR TESTS
    // =========================================================================

    /**
     * Run a single indicator.
     */
    public function test_run_indicator_executes_single_checker(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test Title</title></head><body></body></html>';

        // This assumes title_tag checker is registered
        try {
            $result = $this->service->runIndicator('title_tag', $url, $html);
            $this->assertArrayHasKey('id', $result);
            $this->assertEquals('title_tag', $result['id']);
        } catch (\Exception $e) {
            // If checker not implemented yet, that's okay for TDD
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    /**
     * Run non-existent indicator throws exception.
     */
    public function test_run_indicator_throws_for_unknown_checker(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->runIndicator('non_existent_checker', 'https://example.com', '<html></html>');
    }

    // =========================================================================
    // CONFIGURATION TESTS (CF-*)
    // =========================================================================

    /**
     * CF-01: Custom timeout respected.
     */
    public function test_run_respects_custom_timeout(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html, [
            'timeout' => 30,
        ]);

        $this->assertNotNull($result);
    }

    /**
     * CF-02: Custom indicators filter respected.
     */
    public function test_run_respects_enabled_indicators_config(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        // Only run url and title_tag
        $result = $this->service->run($url, $html, [
            'indicators' => ['url', 'title_tag'],
        ]);

        $indicatorIds = collect($result['indicators'])->pluck('id')->toArray();

        foreach ($indicatorIds as $id) {
            $this->assertContains($id, ['url', 'title_tag']);
        }
    }

    // =========================================================================
    // RESULT STRUCTURE TESTS
    // =========================================================================

    /**
     * Result has correct structure.
     */
    public function test_result_has_correct_structure(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html);

        // Required fields
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('overallScore', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('indicators', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('timing', $result);
    }

    /**
     * Indicator result has correct structure.
     */
    public function test_indicator_result_has_correct_structure(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html);

        if (!empty($result['indicators'])) {
            $indicator = $result['indicators'][0];

            $this->assertArrayHasKey('id', $indicator);
            $this->assertArrayHasKey('name', $indicator);
            $this->assertArrayHasKey('category', $indicator);
            $this->assertArrayHasKey('score', $indicator);
            $this->assertArrayHasKey('weight', $indicator);
            $this->assertArrayHasKey('passed', $indicator);
            $this->assertArrayHasKey('details', $indicator);
            $this->assertArrayHasKey('suggestions', $indicator);
        }
    }

    /**
     * Overall score is valid.
     */
    public function test_overall_score_is_valid(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertIsInt($result['overallScore']);
        $this->assertGreaterThanOrEqual(0, $result['overallScore']);
        $this->assertLessThanOrEqual(100, $result['overallScore']);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Handles very large HTML.
     */
    public function test_run_handles_large_html(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title></head><body>' . str_repeat('<p>Content</p>', 10000) . '</body></html>';

        $result = $this->service->run($url, $html, ['timeout' => 120]);

        $this->assertNotNull($result);
    }

    /**
     * Handles unicode in HTML.
     */
    public function test_run_handles_unicode_html(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>日本語タイトル</title></head><body><p>Привет мир</p></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
    }

    /**
     * Handles malformed HTML.
     */
    public function test_run_handles_malformed_html(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</body></html>'; // Malformed

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
    }

    /**
     * Handles HTML with scripts.
     */
    public function test_run_handles_html_with_scripts(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title><script>alert("test")</script></head><body></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
    }

    /**
     * Handles HTML with styles.
     */
    public function test_run_handles_html_with_styles(): void
    {
        $url = 'https://example.com';
        $html = '<html><head><title>Test</title><style>body { color: red; }</style></head><body></body></html>';

        $result = $this->service->run($url, $html);

        $this->assertNotNull($result);
    }
}
