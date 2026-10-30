<?php

namespace Tests\Unit\Services\Audit;

use PHPUnit\Framework\TestCase;
use App\Services\Audit\CheckSummary;
use App\Services\Audit\BrokenLinkResult;

/**
 * Unit tests for CheckSummary DTO
 *
 * Tests the data transfer object representing aggregated link check results.
 *
 * @see TEST-14-10
 * @see TASK-14-10
 */
class CheckSummaryTest extends TestCase
{
    // =========================================================================
    // DTO CREATION TESTS
    // =========================================================================

    /**
     * Test DTO can be created with default values.
     * 
     * @test
     */
    public function dto_can_be_created_with_defaults(): void
    {
        $summary = new CheckSummary();

        $this->assertEquals(0, $summary->totalChecked);
        $this->assertEquals(0, $summary->validCount);
        $this->assertEquals(0, $summary->brokenCount);
        $this->assertEquals(0, $summary->redirectCount);
        $this->assertEquals(0, $summary->timeoutCount);
        $this->assertEquals(0, $summary->errorCount);
        $this->assertEquals([], $summary->results);
    }

    /**
     * Test DTO can be created with all properties.
     * 
     * @test
     */
    public function dto_can_be_created_with_all_properties(): void
    {
        $results = [
            $this->createMockResult('valid', 200),
            $this->createMockResult('broken', 404),
        ];

        $summary = new CheckSummary(
            totalChecked: 10,
            validCount: 5,
            brokenCount: 2,
            redirectCount: 2,
            timeoutCount: 1,
            errorCount: 0,
            results: $results
        );

        $this->assertEquals(10, $summary->totalChecked);
        $this->assertEquals(5, $summary->validCount);
        $this->assertEquals(2, $summary->brokenCount);
        $this->assertEquals(2, $summary->redirectCount);
        $this->assertEquals(1, $summary->timeoutCount);
        $this->assertEquals(0, $summary->errorCount);
        $this->assertCount(2, $summary->results);
    }

    // =========================================================================
    // STATUS CODE AGGREGATION TESTS
    // =========================================================================

    /**
     * Test byStatusCode aggregation.
     * 
     * @test
     */
    public function by_status_code_aggregation(): void
    {
        $results = [
            $this->createMockResult('valid', 200),
            $this->createMockResult('valid', 200),
            $this->createMockResult('valid', 201),
            $this->createMockResult('broken', 404),
            $this->createMockResult('redirect', 301),
        ];

        $summary = new CheckSummary(
            totalChecked: 5,
            results: $results
        );

        $byStatus = $summary->getByStatusCode();

        $this->assertEquals(2, $byStatus[200]);
        $this->assertEquals(1, $byStatus[201]);
        $this->assertEquals(1, $byStatus[404]);
        $this->assertEquals(1, $byStatus[301]);
    }

    /**
     * Test byType aggregation.
     * 
     * @test
     */
    public function by_type_aggregation(): void
    {
        $results = [
            $this->createMockResult('valid', 200),
            $this->createMockResult('valid', 200),
            $this->createMockResult('broken', 404),
            $this->createMockResult('redirect', 301),
            $this->createMockResult('timeout', 0),
        ];

        $summary = new CheckSummary(
            totalChecked: 5,
            results: $results
        );

        $byType = $summary->getByType();

        $this->assertEquals(2, $byType['valid']);
        $this->assertEquals(1, $byType['broken']);
        $this->assertEquals(1, $byType['redirect']);
        $this->assertEquals(1, $byType['timeout']);
    }

    // =========================================================================
    // HELPER METHOD TESTS
    // =========================================================================

    /**
     * Test hasIssues method.
     * 
     * @test
     */
    public function has_issues_method(): void
    {
        $summaryWithIssues = new CheckSummary(brokenCount: 2);
        $summaryNoIssues = new CheckSummary(validCount: 10);

        $this->assertTrue($summaryWithIssues->hasIssues());
        $this->assertFalse($summaryNoIssues->hasIssues());
    }

    /**
     * Test getBrokenResults method.
     * 
     * @test
     */
    public function get_broken_results_method(): void
    {
        $validResult = $this->createMockResult('valid', 200);
        $brokenResult = $this->createMockResult('broken', 404);

        $summary = new CheckSummary(
            results: [$validResult, $brokenResult]
        );

        $brokenResults = $summary->getBrokenResults();

        $this->assertCount(1, $brokenResults);
        $this->assertEquals('broken', $brokenResults[0]->type);
    }

    /**
     * Test getRedirectResults method.
     * 
     * @test
     */
    public function get_redirect_results_method(): void
    {
        $validResult = $this->createMockResult('valid', 200);
        $redirectResult = $this->createMockResult('redirect', 301);

        $summary = new CheckSummary(
            results: [$validResult, $redirectResult]
        );

        $redirectResults = $summary->getRedirectResults();

        $this->assertCount(1, $redirectResults);
        $this->assertEquals('redirect', $redirectResults[0]->type);
    }

    /**
     * Test getErrorResults method.
     * 
     * @test
     */
    public function get_error_results_method(): void
    {
        $validResult = $this->createMockResult('valid', 200);
        $errorResult = $this->createMockResult('error', 500);
        $timeoutResult = $this->createMockResult('timeout', 0);

        $summary = new CheckSummary(
            results: [$validResult, $errorResult, $timeoutResult]
        );

        $errorResults = $summary->getErrorResults();

        $this->assertCount(2, $errorResults);
    }

    /**
     * Test getSuccessRate method.
     * 
     * @test
     */
    public function get_success_rate_method(): void
    {
        $summary = new CheckSummary(
            totalChecked: 10,
            validCount: 7,
            brokenCount: 2,
            redirectCount: 1
        );

        $this->assertEquals(70.0, $summary->getSuccessRate());
    }

    /**
     * Test getSuccessRate with zero checked.
     * 
     * @test
     */
    public function get_success_rate_with_zero_checked(): void
    {
        $summary = new CheckSummary();

        $this->assertEquals(0.0, $summary->getSuccessRate());
    }

    // =========================================================================
    // SERIALIZATION TESTS
    // =========================================================================

    /**
     * Test toArray serialization.
     * 
     * @test
     */
    public function to_array_serialization(): void
    {
        $summary = new CheckSummary(
            totalChecked: 10,
            validCount: 7,
            brokenCount: 2,
            redirectCount: 1,
            timeoutCount: 0,
            errorCount: 0
        );

        $array = $summary->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(10, $array['totalChecked']);
        $this->assertEquals(7, $array['validCount']);
        $this->assertEquals(2, $array['brokenCount']);
        $this->assertEquals(1, $array['redirectCount']);
        $this->assertEquals(70.0, $array['successRate']);
    }

    /**
     * Test JSON serialization.
     * 
     * @test
     */
    public function json_serialization(): void
    {
        $summary = new CheckSummary(
            totalChecked: 5,
            validCount: 3,
            brokenCount: 2
        );

        $json = json_encode($summary);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertEquals(5, $decoded['totalChecked']);
        $this->assertEquals(3, $decoded['validCount']);
        $this->assertEquals(2, $decoded['brokenCount']);
    }

    // =========================================================================
    // ADD RESULT TESTS
    // =========================================================================

    /**
     * Test addResult method.
     * 
     * @test
     */
    public function add_result_method(): void
    {
        $summary = new CheckSummary();

        $summary->addResult($this->createMockResult('valid', 200));
        $summary->addResult($this->createMockResult('broken', 404));
        $summary->addResult($this->createMockResult('redirect', 301));

        $this->assertEquals(3, $summary->totalChecked);
        $this->assertEquals(1, $summary->validCount);
        $this->assertEquals(1, $summary->brokenCount);
        $this->assertEquals(1, $summary->redirectCount);
    }

    /**
     * Test addResult increments correct counters.
     * 
     * @test
     */
    public function add_result_increments_correct_counters(): void
    {
        $summary = new CheckSummary();

        $summary->addResult($this->createMockResult('valid', 200));
        $this->assertEquals(1, $summary->validCount);

        $summary->addResult($this->createMockResult('broken', 404));
        $this->assertEquals(1, $summary->brokenCount);

        $summary->addResult($this->createMockResult('redirect', 301));
        $this->assertEquals(1, $summary->redirectCount);

        $summary->addResult($this->createMockResult('timeout', 0));
        $this->assertEquals(1, $summary->timeoutCount);

        $summary->addResult($this->createMockResult('error', 500));
        $this->assertEquals(1, $summary->errorCount);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create a mock result for testing.
     */
    private function createMockResult(string $type, int $statusCode): BrokenLinkResult
    {
        return new BrokenLinkResult(
            url: 'https://example.com/' . $type,
            sourceUrl: 'https://example.com/source',
            statusCode: $statusCode,
            type: $type
        );
    }
}
