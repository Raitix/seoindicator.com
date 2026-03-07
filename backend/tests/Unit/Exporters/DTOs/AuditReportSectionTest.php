<?php

namespace Tests\Unit\Exporters\DTOs;

use Tests\TestCase;
use App\Exporters\DTOs\AuditReportSection;

/**
 * Unit tests for AuditReportSection DTO.
 *
 * TASK-14-18: Integrate Audit Results into PDF Reports
 * Test documentation: TESTS/TEST-14-18.md
 */
class AuditReportSectionTest extends TestCase
{
    // ============================================
    // DTO INSTANTIATION TESTS (DTO-001 to DTO-005)
    // ============================================

    /**
     * DTO-001: Create with all fields.
     */
    public function test_create_with_all_fields(): void
    {
        $section = new AuditReportSection(
            summary: [
                'pages_crawled' => 100,
                'total_issues' => 25,
                'critical' => 10,
                'warning' => 10,
                'notice' => 5,
            ],
            criticalIssues: [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Test', 'description' => 'Desc', 'recommendation' => 'Fix it'],
            ],
            warningIssues: [
                ['id' => 2, 'url' => 'https://example.com/2', 'name' => 'Warning', 'description' => 'Warn', 'recommendation' => 'Fix'],
            ],
            recommendations: ['Fix all issues']
        );

        $this->assertInstanceOf(AuditReportSection::class, $section);
        $this->assertEquals(100, $section->getPagesCrawled());
        $this->assertEquals(25, $section->getTotalIssues());
    }

    /**
     * DTO-002: Create with minimum fields.
     */
    public function test_create_with_minimum_fields(): void
    {
        $section = new AuditReportSection(
            summary: [
                'pages_crawled' => 0,
                'total_issues' => 0,
                'critical' => 0,
                'warning' => 0,
                'notice' => 0,
            ]
        );

        $this->assertInstanceOf(AuditReportSection::class, $section);
        $this->assertEquals([], $section->criticalIssues);
        $this->assertEquals([], $section->warningIssues);
        $this->assertEquals([], $section->recommendations);
    }

    /**
     * DTO-003: Static fromArray method.
     */
    public function test_from_array_method(): void
    {
        $data = [
            'summary' => [
                'pages_crawled' => 50,
                'total_issues' => 10,
                'critical' => 5,
                'warning' => 3,
                'notice' => 2,
            ],
            'criticalIssues' => [],
            'warningIssues' => [],
            'recommendations' => [],
        ];

        $section = AuditReportSection::fromArray($data);

        $this->assertInstanceOf(AuditReportSection::class, $section);
        $this->assertEquals(50, $section->getPagesCrawled());
        $this->assertEquals(10, $section->getTotalIssues());
    }

    /**
     * DTO-004: toArray method.
     */
    public function test_to_array_method(): void
    {
        $section = new AuditReportSection(
            summary: [
                'pages_crawled' => 100,
                'total_issues' => 5,
                'critical' => 2,
                'warning' => 2,
                'notice' => 1,
            ],
            criticalIssues: [['id' => 1]],
            warningIssues: [['id' => 2]],
            recommendations: ['Fix issues']
        );

        $array = $section->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('summary', $array);
        $this->assertArrayHasKey('criticalIssues', $array);
        $this->assertArrayHasKey('warningIssues', $array);
        $this->assertArrayHasKey('recommendations', $array);
        $this->assertEquals(100, $array['summary']['pages_crawled']);
    }

    /**
     * DTO-005: JSON serialization.
     */
    public function test_json_serialization(): void
    {
        $section = new AuditReportSection(
            summary: [
                'pages_crawled' => 100,
                'total_issues' => 5,
                'critical' => 2,
                'warning' => 2,
                'notice' => 1,
            ],
            criticalIssues: [],
            warningIssues: [],
            recommendations: []
        );

        $json = json_encode($section);
        $decoded = json_decode($json, true);

        $this->assertJson($json);
        $this->assertArrayHasKey('summary', $decoded);
        $this->assertEquals(100, $decoded['summary']['pages_crawled']);
    }

    // ============================================
    // SUMMARY FIELD TESTS (DTO-010 to DTO-020)
    // ============================================

    /**
     * DTO-010: Summary contains pages_crawled.
     */
    public function test_summary_contains_pages_crawled(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 250, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0]
        );

        $this->assertEquals(250, $section->getPagesCrawled());
    }

    /**
     * DTO-011: Summary contains total_issues.
     */
    public function test_summary_contains_total_issues(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 42, 'critical' => 0, 'warning' => 0, 'notice' => 0]
        );

        $this->assertEquals(42, $section->getTotalIssues());
    }

    /**
     * DTO-012: Summary contains severity counts.
     */
    public function test_summary_contains_severity_counts(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 15, 'critical' => 5, 'warning' => 7, 'notice' => 3]
        );

        $this->assertEquals(5, $section->getCriticalCount());
        $this->assertEquals(7, $section->getWarningCount());
        $this->assertEquals(3, $section->getNoticeCount());
    }

    /**
     * DTO-013: Summary severity counts sum correctly.
     */
    public function test_severity_counts_sum_correctly(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 15, 'critical' => 5, 'warning' => 7, 'notice' => 3]
        );

        $sum = $section->getCriticalCount() + $section->getWarningCount() + $section->getNoticeCount();
        $this->assertEquals($section->getTotalIssues(), $sum);
    }

    /**
     * DTO-014: Summary with zero issues.
     */
    public function test_summary_with_zero_issues(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0]
        );

        $this->assertEquals(0, $section->getTotalIssues());
        $this->assertEquals(0, $section->getCriticalCount());
        $this->assertEquals(0, $section->getWarningCount());
        $this->assertEquals(0, $section->getNoticeCount());
    }

    /**
     * DTO-015: Summary with only critical issues.
     */
    public function test_summary_with_only_critical_issues(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 100, 'total_issues' => 5, 'critical' => 5, 'warning' => 0, 'notice' => 0]
        );

        $this->assertEquals(5, $section->getCriticalCount());
        $this->assertEquals(0, $section->getWarningCount());
        $this->assertEquals(0, $section->getNoticeCount());
    }

    /**
     * DTO-016: Summary with only warnings.
     */
    public function test_summary_with_only_warnings(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 100, 'total_issues' => 3, 'critical' => 0, 'warning' => 3, 'notice' => 0]
        );

        $this->assertEquals(0, $section->getCriticalCount());
        $this->assertEquals(3, $section->getWarningCount());
        $this->assertEquals(0, $section->getNoticeCount());
    }

    /**
     * DTO-017: Summary with only notices.
     */
    public function test_summary_with_only_notices(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 100, 'total_issues' => 2, 'critical' => 0, 'warning' => 0, 'notice' => 2]
        );

        $this->assertEquals(0, $section->getCriticalCount());
        $this->assertEquals(0, $section->getWarningCount());
        $this->assertEquals(2, $section->getNoticeCount());
    }

    /**
     * DTO-018: Pages crawled is integer.
     */
    public function test_pages_crawled_is_integer(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0]
        );

        $this->assertIsInt($section->getPagesCrawled());
    }

    /**
     * DTO-019: Total issues is integer.
     */
    public function test_total_issues_is_integer(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 50, 'critical' => 0, 'warning' => 0, 'notice' => 0]
        );

        $this->assertIsInt($section->getTotalIssues());
    }

    /**
     * DTO-020: Severity counts are integers.
     */
    public function test_severity_counts_are_integers(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 10, 'critical' => 3, 'warning' => 4, 'notice' => 3]
        );

        $this->assertIsInt($section->getCriticalCount());
        $this->assertIsInt($section->getWarningCount());
        $this->assertIsInt($section->getNoticeCount());
    }

    // ============================================
    // CRITICAL ISSUES FIELD TESTS (DTO-025 to DTO-030)
    // ============================================

    /**
     * DTO-025: Critical issues array.
     */
    public function test_critical_issues_array(): void
    {
        $issues = [
            ['id' => 1, 'url' => 'https://example.com', 'name' => 'Issue 1', 'description' => 'Desc', 'recommendation' => 'Fix'],
        ];

        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            criticalIssues: $issues
        );

        $this->assertIsArray($section->criticalIssues);
        $this->assertCount(1, $section->criticalIssues);
    }

    /**
     * DTO-026: Critical issue structure.
     */
    public function test_critical_issue_structure(): void
    {
        $issue = [
            'id' => 1,
            'url' => 'https://example.com/page',
            'name' => 'Missing title tag',
            'description' => 'Page is missing a title tag',
            'recommendation' => 'Add a unique title tag',
        ];

        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            criticalIssues: [$issue]
        );

        $this->assertArrayHasKey('id', $section->criticalIssues[0]);
        $this->assertArrayHasKey('url', $section->criticalIssues[0]);
        $this->assertArrayHasKey('name', $section->criticalIssues[0]);
        $this->assertArrayHasKey('description', $section->criticalIssues[0]);
        $this->assertArrayHasKey('recommendation', $section->criticalIssues[0]);
    }

    /**
     * DTO-027: Critical issues limited to 10.
     */
    public function test_critical_issues_limited_to_10(): void
    {
        // Create 15 issues
        $issues = [];
        for ($i = 1; $i <= 15; $i++) {
            $issues[] = ['id' => $i, 'url' => "https://example.com/$i", 'name' => "Issue $i", 'description' => 'Desc', 'recommendation' => 'Fix'];
        }

        $section = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 0, 'total_issues' => 15, 'critical' => 15, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => $issues,
        ]);

        $this->assertLessThanOrEqual(10, count($section->criticalIssues));
    }

    /**
     * DTO-028: Critical issues with fewer than 10.
     */
    public function test_critical_issues_with_fewer_than_10(): void
    {
        $issues = [
            ['id' => 1, 'url' => 'https://example.com', 'name' => 'Issue 1', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ['id' => 2, 'url' => 'https://example.com/2', 'name' => 'Issue 2', 'description' => 'Desc', 'recommendation' => 'Fix'],
        ];

        $section = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 0, 'total_issues' => 2, 'critical' => 2, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => $issues,
        ]);

        $this->assertCount(2, $section->criticalIssues);
    }

    /**
     * DTO-029: Critical issues empty array.
     */
    public function test_critical_issues_empty_array(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            criticalIssues: []
        );

        $this->assertIsArray($section->criticalIssues);
        $this->assertEmpty($section->criticalIssues);
    }

    /**
     * DTO-030: Critical issue severity is critical.
     */
    public function test_critical_issue_severity_is_critical(): void
    {
        $issues = [
            ['id' => 1, 'url' => 'https://example.com', 'name' => 'Issue', 'description' => 'Desc', 'recommendation' => 'Fix', 'severity' => 'critical'],
        ];

        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            criticalIssues: $issues
        );

        $this->assertEquals('critical', $section->criticalIssues[0]['severity']);
    }

    // ============================================
    // WARNING ISSUES FIELD TESTS (DTO-035 to DTO-040)
    // ============================================

    /**
     * DTO-035: Warning issues array.
     */
    public function test_warning_issues_array(): void
    {
        $issues = [
            ['id' => 1, 'url' => 'https://example.com', 'name' => 'Warning 1', 'description' => 'Desc', 'recommendation' => 'Fix'],
        ];

        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 1, 'critical' => 0, 'warning' => 1, 'notice' => 0],
            warningIssues: $issues
        );

        $this->assertIsArray($section->warningIssues);
        $this->assertCount(1, $section->warningIssues);
    }

    /**
     * DTO-036: Warning issue structure.
     */
    public function test_warning_issue_structure(): void
    {
        $issue = [
            'id' => 1,
            'url' => 'https://example.com/page',
            'name' => 'Missing meta description',
            'description' => 'Page is missing meta description',
            'recommendation' => 'Add a meta description',
        ];

        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 1, 'critical' => 0, 'warning' => 1, 'notice' => 0],
            warningIssues: [$issue]
        );

        $this->assertArrayHasKey('id', $section->warningIssues[0]);
        $this->assertArrayHasKey('url', $section->warningIssues[0]);
        $this->assertArrayHasKey('name', $section->warningIssues[0]);
        $this->assertArrayHasKey('description', $section->warningIssues[0]);
        $this->assertArrayHasKey('recommendation', $section->warningIssues[0]);
    }

    /**
     * DTO-037: Warning issues limited to 5.
     */
    public function test_warning_issues_limited_to_5(): void
    {
        // Create 10 warnings
        $issues = [];
        for ($i = 1; $i <= 10; $i++) {
            $issues[] = ['id' => $i, 'url' => "https://example.com/$i", 'name' => "Warning $i", 'description' => 'Desc', 'recommendation' => 'Fix'];
        }

        $section = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 0, 'total_issues' => 10, 'critical' => 0, 'warning' => 10, 'notice' => 0],
            'warningIssues' => $issues,
        ]);

        $this->assertLessThanOrEqual(5, count($section->warningIssues));
    }

    /**
     * DTO-038: Warning issues with fewer than 5.
     */
    public function test_warning_issues_with_fewer_than_5(): void
    {
        $issues = [
            ['id' => 1, 'url' => 'https://example.com', 'name' => 'Warning 1', 'description' => 'Desc', 'recommendation' => 'Fix'],
        ];

        $section = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 0, 'total_issues' => 1, 'critical' => 0, 'warning' => 1, 'notice' => 0],
            'warningIssues' => $issues,
        ]);

        $this->assertCount(1, $section->warningIssues);
    }

    /**
     * DTO-039: Warning issues empty array.
     */
    public function test_warning_issues_empty_array(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            warningIssues: []
        );

        $this->assertIsArray($section->warningIssues);
        $this->assertEmpty($section->warningIssues);
    }

    /**
     * DTO-040: Warning issue severity is warning.
     */
    public function test_warning_issue_severity_is_warning(): void
    {
        $issues = [
            ['id' => 1, 'url' => 'https://example.com', 'name' => 'Warning', 'description' => 'Desc', 'recommendation' => 'Fix', 'severity' => 'warning'],
        ];

        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 1, 'critical' => 0, 'warning' => 1, 'notice' => 0],
            warningIssues: $issues
        );

        $this->assertEquals('warning', $section->warningIssues[0]['severity']);
    }

    // ============================================
    // RECOMMENDATIONS FIELD TESTS (DTO-045 to DTO-050)
    // ============================================

    /**
     * DTO-045: Recommendations array.
     */
    public function test_recommendations_array(): void
    {
        $recommendations = ['Fix all critical issues', 'Add meta descriptions'];

        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            recommendations: $recommendations
        );

        $this->assertIsArray($section->recommendations);
        $this->assertCount(2, $section->recommendations);
    }

    /**
     * DTO-046: Recommendations limited to 5.
     */
    public function test_recommendations_limited_to_5(): void
    {
        $recommendations = [];
        for ($i = 1; $i <= 10; $i++) {
            $recommendations[] = "Recommendation $i";
        }

        $section = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            'recommendations' => $recommendations,
        ]);

        $this->assertLessThanOrEqual(5, count($section->recommendations));
    }

    /**
     * DTO-047: Recommendations with fewer than 5.
     */
    public function test_recommendations_with_fewer_than_5(): void
    {
        $recommendations = ['Fix issue 1', 'Fix issue 2'];

        $section = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            'recommendations' => $recommendations,
        ]);

        $this->assertCount(2, $section->recommendations);
    }

    /**
     * DTO-048: Recommendations empty array.
     */
    public function test_recommendations_empty_array(): void
    {
        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            recommendations: []
        );

        $this->assertIsArray($section->recommendations);
        $this->assertEmpty($section->recommendations);
    }

    /**
     * DTO-049: Recommendations are strings.
     */
    public function test_recommendations_are_strings(): void
    {
        $recommendations = ['Fix this', 'Fix that'];

        $section = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            recommendations: $recommendations
        );

        foreach ($section->recommendations as $rec) {
            $this->assertIsString($rec);
        }
    }

    /**
     * DTO-050: Recommendations derived from issues.
     */
    public function test_recommendations_derived_from_issues(): void
    {
        $recommendations = [
            'Fix all critical issues immediately',
            'Add unique title tags to all pages',
            'Review and fix broken links',
        ];

        $section = new AuditReportSection(
            summary: ['pages_crawled' => 100, 'total_issues' => 5, 'critical' => 3, 'warning' => 2, 'notice' => 0],
            recommendations: $recommendations
        );

        $this->assertContains('Fix all critical issues immediately', $section->recommendations);
    }

    // ============================================
    // HELPER METHOD TESTS
    // ============================================

    /**
     * Test hasCriticalIssues method.
     */
    public function test_has_critical_issues(): void
    {
        $sectionWithIssues = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            criticalIssues: [['id' => 1]]
        );

        $sectionWithoutIssues = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            criticalIssues: []
        );

        $this->assertTrue($sectionWithIssues->hasCriticalIssues());
        $this->assertFalse($sectionWithoutIssues->hasCriticalIssues());
    }

    /**
     * Test hasWarningIssues method.
     */
    public function test_has_warning_issues(): void
    {
        $sectionWithIssues = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 1, 'critical' => 0, 'warning' => 1, 'notice' => 0],
            warningIssues: [['id' => 1]]
        );

        $sectionWithoutIssues = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            warningIssues: []
        );

        $this->assertTrue($sectionWithIssues->hasWarningIssues());
        $this->assertFalse($sectionWithoutIssues->hasWarningIssues());
    }

    /**
     * Test hasRecommendations method.
     */
    public function test_has_recommendations(): void
    {
        $sectionWithRecs = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            recommendations: ['Fix this']
        );

        $sectionWithoutRecs = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            recommendations: []
        );

        $this->assertTrue($sectionWithRecs->hasRecommendations());
        $this->assertFalse($sectionWithoutRecs->hasRecommendations());
    }

    /**
     * Test hasContent method.
     */
    public function test_has_content(): void
    {
        $sectionWithContent = new AuditReportSection(
            summary: ['pages_crawled' => 100, 'total_issues' => 5, 'critical' => 3, 'warning' => 2, 'notice' => 0]
        );

        $sectionEmpty = new AuditReportSection(
            summary: ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0]
        );

        $this->assertTrue($sectionWithContent->hasContent());
        $this->assertFalse($sectionEmpty->hasContent());
    }

    /**
     * Test isValidSummary method.
     */
    public function test_is_valid_summary(): void
    {
        $validSection = new AuditReportSection(
            summary: ['pages_crawled' => 100, 'total_issues' => 15, 'critical' => 5, 'warning' => 7, 'notice' => 3]
        );

        $invalidSection = new AuditReportSection(
            summary: ['pages_crawled' => 100, 'total_issues' => 10, 'critical' => 5, 'warning' => 3, 'notice' => 1] // Sum is 9, not 10
        );

        $this->assertTrue($validSection->isValidSummary());
        $this->assertFalse($invalidSection->isValidSummary());
    }

    /**
     * Test fromArray with missing keys uses defaults.
     */
    public function test_from_array_with_missing_keys(): void
    {
        $section = AuditReportSection::fromArray([]);

        $this->assertEquals(0, $section->getPagesCrawled());
        $this->assertEquals(0, $section->getTotalIssues());
        $this->assertEquals(0, $section->getCriticalCount());
        $this->assertEquals(0, $section->getWarningCount());
        $this->assertEquals(0, $section->getNoticeCount());
    }
}
