<?php

namespace Tests\Unit\Exporters;

use Tests\TestCase;
use App\Exporters\PdfReportExporter;
use App\Exporters\DTOs\AuditReportSection;

/**
 * Unit tests for Audit PDF Section rendering.
 *
 * TASK-14-18: Integrate Audit Results into PDF Reports
 * Test documentation: TESTS/TEST-14-18.md
 */
class AuditPdfSectionTest extends TestCase
{
    private PdfReportExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new PdfReportExporter();
    }

    // ============================================
    // SERVICE INSTANTIATION TESTS (PDF-001 to PDF-003)
    // ============================================

    /**
     * PDF-001: Service instantiated.
     */
    public function test_service_instantiated(): void
    {
        $this->assertInstanceOf(PdfReportExporter::class, $this->exporter);
    }

    /**
     * PDF-002: Has addAuditSection method via buildAuditSection.
     */
    public function test_has_build_audit_section_method(): void
    {
        $this->assertTrue(method_exists($this->exporter, 'buildAuditSection'));
    }

    /**
     * PDF-003: Method accepts data array.
     */
    public function test_method_accepts_data_array(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $data = ['audit_data' => null];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    // ============================================
    // SECTION HEADER RENDERING TESTS (PDF-010 to PDF-015)
    // ============================================

    /**
     * PDF-010: Section header rendered.
     */
    public function test_section_header_rendered(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('TECHNICAL SEO AUDIT', $pdf);
    }

    /**
     * PDF-011: Section has summary subheader.
     */
    public function test_section_has_summary_subheader(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 5, 'critical' => 2, 'warning' => 2, 'notice' => 1],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Summary', $pdf);
    }

    /**
     * PDF-012: Pages crawled displayed.
     */
    public function test_pages_crawled_displayed(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 250, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Pages Crawled', $pdf);
        $this->assertStringContainsString('250', $pdf);
    }

    /**
     * PDF-013: Total issues displayed.
     */
    public function test_total_issues_displayed(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 42, 'critical' => 10, 'warning' => 20, 'notice' => 12],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Total Issues', $pdf);
        $this->assertStringContainsString('42', $pdf);
    }

    /**
     * PDF-014: Section positioned after existing content.
     */
    public function test_section_positioned_correctly(): void
    {
        $report = ['name' => 'Test', 'sections' => ['cover', 'audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // Should have multiple pages
        $this->assertStringContainsString('COVER PAGE', $pdf);
        $this->assertStringContainsString('TECHNICAL SEO AUDIT', $pdf);
    }

    /**
     * PDF-015: Section header styled correctly.
     */
    public function test_section_header_styled(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // Header should be in uppercase
        $this->assertStringContainsString('TECHNICAL SEO AUDIT', $pdf);
    }

    // ============================================
    // SUMMARY BOXES RENDERING TESTS (PDF-020 to PDF-035)
    // ============================================

    /**
     * PDF-020: Critical count box rendered.
     */
    public function test_critical_count_box_rendered(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 10, 'critical' => 10, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('CRITICAL', $pdf);
        $this->assertStringContainsString('10', $pdf);
    }

    /**
     * PDF-021: Warning count box rendered.
     */
    public function test_warning_count_box_rendered(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 5, 'critical' => 0, 'warning' => 5, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('WARNING', $pdf);
        $this->assertStringContainsString('5', $pdf);
    }

    /**
     * PDF-022: Notice count box rendered.
     */
    public function test_notice_count_box_rendered(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 3, 'critical' => 0, 'warning' => 0, 'notice' => 3],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('NOTICE', $pdf);
        $this->assertStringContainsString('3', $pdf);
    }

    /**
     * PDF-027: Zero counts displayed.
     */
    public function test_zero_counts_displayed(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('CRITICAL: 0', $pdf);
        $this->assertStringContainsString('WARNING: 0', $pdf);
        $this->assertStringContainsString('NOTICE: 0', $pdf);
    }

    /**
     * PDF-028: Large counts displayed correctly.
     */
    public function test_large_counts_displayed(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 999, 'critical' => 500, 'warning' => 499, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('999', $pdf);
        $this->assertStringContainsString('500', $pdf);
    }

    /**
     * PDF-029: Box labels present.
     */
    public function test_box_labels_present(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 10, 'critical' => 3, 'warning' => 5, 'notice' => 2],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('CRITICAL', $pdf);
        $this->assertStringContainsString('WARNING', $pdf);
        $this->assertStringContainsString('NOTICE', $pdf);
    }

    /**
     * PDF-033: Summary with all zeros.
     */
    public function test_summary_with_all_zeros(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('CRITICAL: 0', $pdf);
        $this->assertStringContainsString('WARNING: 0', $pdf);
        $this->assertStringContainsString('NOTICE: 0', $pdf);
    }

    /**
     * PDF-034: Summary with mixed counts.
     */
    public function test_summary_with_mixed_counts(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 25, 'critical' => 10, 'warning' => 10, 'notice' => 5],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('CRITICAL: 10', $pdf);
        $this->assertStringContainsString('WARNING: 10', $pdf);
        $this->assertStringContainsString('NOTICE: 5', $pdf);
    }

    // ============================================
    // CRITICAL ISSUES SECTION TESTS (PDF-040 to PDF-050)
    // ============================================

    /**
     * PDF-040: Critical section header.
     */
    public function test_critical_section_header(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Missing title', 'description' => 'No title', 'recommendation' => 'Add title'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Critical Issues', $pdf);
    }

    /**
     * PDF-041: Critical issues listed.
     */
    public function test_critical_issues_listed(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Missing title tag', 'description' => 'No title', 'recommendation' => 'Add title'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Missing title tag', $pdf);
    }

    /**
     * PDF-042: Issue URLs displayed.
     */
    public function test_issue_urls_displayed(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com/page', 'name' => 'Issue', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('https://example.com/page', $pdf);
    }

    /**
     * PDF-043: Issue recommendations displayed.
     */
    public function test_issue_recommendations_displayed(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Issue', 'description' => 'Desc', 'recommendation' => 'Add a unique title tag'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Add a unique title tag', $pdf);
    }

    /**
     * PDF-044: Issues numbered.
     */
    public function test_issues_numbered(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 2, 'critical' => 2, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com/1', 'name' => 'Issue 1', 'description' => 'Desc', 'recommendation' => 'Fix 1'],
                ['id' => 2, 'url' => 'https://example.com/2', 'name' => 'Issue 2', 'description' => 'Desc', 'recommendation' => 'Fix 2'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('1.', $pdf);
        $this->assertStringContainsString('2.', $pdf);
    }

    /**
     * PDF-045: Limited to 10 critical issues.
     */
    public function test_limited_to_10_critical_issues(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        
        // Create 15 critical issues
        $issues = [];
        for ($i = 1; $i <= 15; $i++) {
            $issues[] = ['id' => $i, 'url' => "https://example.com/$i", 'name' => "Issue $i", 'description' => 'Desc', 'recommendation' => 'Fix'];
        }
        
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 15, 'critical' => 15, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => $issues,
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // Should have issue 1-10 but not 11-15
        $this->assertStringContainsString('Issue 10', $pdf);
        $this->assertStringNotContainsString('Issue 11', $pdf);
    }

    /**
     * PDF-046: No critical issues - section hidden.
     */
    public function test_no_critical_issues_section_hidden(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // Should not have Critical Issues section header when no issues
        $this->assertStringNotContainsString('Critical Issues', $pdf);
    }

    // ============================================
    // WARNINGS SECTION TESTS (PDF-055 to PDF-065)
    // ============================================

    /**
     * PDF-055: Warnings section header.
     */
    public function test_warnings_section_header(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 0, 'warning' => 1, 'notice' => 0],
            'warningIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Missing meta', 'description' => 'No meta', 'recommendation' => 'Add meta'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Warnings', $pdf);
    }

    /**
     * PDF-056: Warnings listed.
     */
    public function test_warnings_listed(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 0, 'warning' => 1, 'notice' => 0],
            'warningIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Missing meta description', 'description' => 'No meta', 'recommendation' => 'Add meta'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Missing meta description', $pdf);
    }

    /**
     * PDF-057: Warning URLs displayed.
     */
    public function test_warning_urls_displayed(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 0, 'warning' => 1, 'notice' => 0],
            'warningIssues' => [
                ['id' => 1, 'url' => 'https://example.com/warning-page', 'name' => 'Warning', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('https://example.com/warning-page', $pdf);
    }

    /**
     * PDF-059: Limited to 5 warnings.
     */
    public function test_limited_to_5_warnings(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        
        // Create 10 warnings
        $issues = [];
        for ($i = 1; $i <= 10; $i++) {
            $issues[] = ['id' => $i, 'url' => "https://example.com/$i", 'name' => "Warning $i", 'description' => 'Desc', 'recommendation' => 'Fix'];
        }
        
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 10, 'critical' => 0, 'warning' => 10, 'notice' => 0],
            'warningIssues' => $issues,
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // Should have warning 1-5 but not 6-10
        $this->assertStringContainsString('Warning 5', $pdf);
        $this->assertStringNotContainsString('Warning 6', $pdf);
    }

    /**
     * PDF-060: No warnings - section hidden.
     */
    public function test_no_warnings_section_hidden(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringNotContainsString('Warnings', $pdf);
    }

    /**
     * PDF-062: Warnings after critical section.
     */
    public function test_warnings_after_critical_section(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 2, 'critical' => 1, 'warning' => 1, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Critical', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
            'warningIssues' => [
                ['id' => 2, 'url' => 'https://example.com', 'name' => 'Warning', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // Both sections should be present
        $this->assertStringContainsString('Critical Issues', $pdf);
        $this->assertStringContainsString('Warnings', $pdf);
    }

    // ============================================
    // RECOMMENDATIONS SECTION TESTS (PDF-070 to PDF-080)
    // ============================================

    /**
     * PDF-070: Recommendations section header.
     */
    public function test_recommendations_section_header(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            'recommendations' => ['Fix all issues'],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Recommendations', $pdf);
    }

    /**
     * PDF-071: Recommendations listed as bullet points.
     */
    public function test_recommendations_listed_as_bullet_points(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            'recommendations' => ['Fix critical issues first', 'Add meta descriptions'],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Fix critical issues first', $pdf);
        $this->assertStringContainsString('Add meta descriptions', $pdf);
    }

    /**
     * PDF-072: Limited to 5 recommendations.
     */
    public function test_limited_to_5_recommendations(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        
        $recommendations = [];
        for ($i = 1; $i <= 10; $i++) {
            $recommendations[] = "Recommendation $i";
        }
        
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            'recommendations' => $recommendations,
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Recommendation 5', $pdf);
        $this->assertStringNotContainsString('Recommendation 6', $pdf);
    }

    /**
     * PDF-073: No recommendations - section hidden.
     */
    public function test_no_recommendations_section_hidden(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringNotContainsString('Recommendations', $pdf);
    }

    /**
     * PDF-077: Bullet character used.
     */
    public function test_bullet_character_used(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
            'recommendations' => ['Fix this issue'],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // Bullet point character (*) should be present
        $this->assertStringContainsString('*', $pdf);
    }

    // ============================================
    // XSS PREVENTION TESTS (PDF-100 to PDF-105)
    // ============================================

    /**
     * PDF-100: Script tags escaped.
     */
    public function test_script_tags_escaped(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => '<script>alert(1)</script>', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // Script tags should be stripped
        $this->assertStringNotContainsString('<script>', $pdf);
    }

    /**
     * PDF-101: HTML entities escaped.
     */
    public function test_html_entities_escaped(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => '<b>Bold</b> issue', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // HTML tags should be stripped
        $this->assertStringNotContainsString('<b>', $pdf);
    }

    /**
     * PDF-102: Special chars in URLs.
     */
    public function test_special_chars_in_urls(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com/page?foo=bar&baz=qux', 'name' => 'Issue', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // PDF should still be valid
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
    }

    /**
     * PDF-104: Malformed HTML.
     */
    public function test_malformed_html(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => '<div>Unclosed', 'description' => '<p>Bad HTML', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // PDF should still be valid
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
    }

    /**
     * PDF-105: Unicode in issue text.
     */
    public function test_unicode_in_issue_text(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 1, 'critical' => 1, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Issue with émojis 🎉', 'description' => 'Unicode: ñ, ü, 漢字', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        // PDF should still be valid
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
    }

    // ============================================
    // EDGE CASES TESTS (PDF-110 to PDF-120)
    // ============================================

    /**
     * PDF-110: Empty audit data.
     */
    public function test_empty_audit_data(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $data = ['audit_data' => null];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Audit data not available', $pdf);
    }

    /**
     * PDF-111: Null audit data.
     */
    public function test_null_audit_data(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $data = [];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Audit data not available', $pdf);
    }

    /**
     * PDF-112: All counts zero.
     */
    public function test_all_counts_zero(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 0, 'total_issues' => 0, 'critical' => 0, 'warning' => 0, 'notice' => 0],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Pages Crawled: 0', $pdf);
        $this->assertStringContainsString('Total Issues: 0', $pdf);
    }

    /**
     * PDF-118: All critical, no warnings.
     */
    public function test_all_critical_no_warnings(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 5, 'critical' => 5, 'warning' => 0, 'notice' => 0],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Critical', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Critical Issues', $pdf);
        $this->assertStringNotContainsString('Warnings', $pdf);
    }

    /**
     * PDF-119: All warnings, no critical.
     */
    public function test_all_warnings_no_critical(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 3, 'critical' => 0, 'warning' => 3, 'notice' => 0],
            'warningIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Warning', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Warnings', $pdf);
        $this->assertStringNotContainsString('Critical Issues', $pdf);
    }

    /**
     * PDF-120: Mixed severity with all counts.
     */
    public function test_mixed_severity_with_all_counts(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 25, 'critical' => 10, 'warning' => 10, 'notice' => 5],
            'criticalIssues' => [
                ['id' => 1, 'url' => 'https://example.com', 'name' => 'Critical', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
            'warningIssues' => [
                ['id' => 2, 'url' => 'https://example.com', 'name' => 'Warning', 'description' => 'Desc', 'recommendation' => 'Fix'],
            ],
            'recommendations' => ['Fix all issues'],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Critical Issues', $pdf);
        $this->assertStringContainsString('Warnings', $pdf);
        $this->assertStringContainsString('Recommendations', $pdf);
    }

    /**
     * Test audit section with other sections combined.
     */
    public function test_audit_section_with_other_sections(): void
    {
        $report = [
            'name' => 'Full Report',
            'date_range_start' => '2026-03-01',
            'date_range_end' => '2026-03-07',
            'sections' => ['cover', 'audit'],
        ];
        $auditSection = AuditReportSection::fromArray([
            'summary' => ['pages_crawled' => 100, 'total_issues' => 5, 'critical' => 2, 'warning' => 2, 'notice' => 1],
        ]);
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('COVER PAGE', $pdf);
        $this->assertStringContainsString('TECHNICAL SEO AUDIT', $pdf);
    }

    /**
     * Test audit section accepts AuditReportSection DTO object.
     */
    public function test_audit_section_accepts_dto_object(): void
    {
        $report = ['name' => 'Test', 'sections' => ['audit']];
        $auditSection = new AuditReportSection(
            summary: ['pages_crawled' => 50, 'total_issues' => 3, 'critical' => 1, 'warning' => 1, 'notice' => 1],
            criticalIssues: [['id' => 1, 'url' => 'https://example.com', 'name' => 'Test', 'description' => 'Desc', 'recommendation' => 'Fix']],
            warningIssues: [],
            recommendations: ['Fix it']
        );
        $data = ['audit_data' => $auditSection];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('TECHNICAL SEO AUDIT', $pdf);
        $this->assertStringContainsString('Pages Crawled: 50', $pdf);
    }
}
