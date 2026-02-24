<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Keyword;
use App\Models\Report;
use App\Models\ReportTemplate;
use App\Models\Site;
use App\Repositories\ReportRepository;
use App\Repositories\ReportTemplateRepository;
use App\Exporters\CsvReportExporter;
use App\Exporters\PdfReportExporter;
use App\Services\ReportDataAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Comprehensive unit tests for report-related services.
 * This file provides additional coverage and edge case testing.
 */
class ReportServicesTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // Report Model Additional Tests
    // ============================================

    /**
     * Test report type validation via factory states.
     */
    public function test_report_type_variations(): void
    {
        $site = Site::factory()->create();

        $types = ['daily', 'weekly', 'monthly', 'custom', 'one-time'];

        foreach ($types as $type) {
            $report = Report::factory()->create([
                'site_id' => $site->id,
                'type' => $type,
            ]);

            $this->assertEquals($type, $report->type);
        }
    }

    /**
     * Test report format variations.
     */
    public function test_report_format_variations(): void
    {
        $site = Site::factory()->create();

        $reportPdf = Report::factory()->create(['site_id' => $site->id, 'format' => 'pdf']);
        $reportCsv = Report::factory()->create(['site_id' => $site->id, 'format' => 'csv']);

        $this->assertEquals('pdf', $reportPdf->format);
        $this->assertEquals('csv', $reportCsv->format);
    }

    // ============================================
    // ReportTemplate Model Additional Tests
    // ============================================

    /**
     * Test template sections are preserved correctly.
     */
    public function test_template_sections_preserved(): void
    {
        $sections = ['cover', 'summary', 'keywords', 'changes', 'trends'];

        $template = ReportTemplate::factory()->create([
            'sections' => $sections,
        ]);

        $this->assertEquals($sections, $template->sections);
    }

    /**
     * Test template branding structure.
     */
    public function test_template_branding_structure(): void
    {
        $branding = [
            'logo_url' => 'https://example.com/logo.png',
            'primary_color' => '#2563eb',
            'secondary_color' => '#64748b',
            'font_family' => 'Inter',
        ];

        $template = ReportTemplate::factory()->create([
            'branding' => $branding,
        ]);

        $this->assertEquals($branding, $template->branding);
    }

    // ============================================
    // ReportRepository Additional Tests
    // ============================================

    /**
     * Test repository handles empty results gracefully.
     */
    public function test_repository_empty_results(): void
    {
        $repo = new ReportRepository();

        $this->assertCount(0, $repo->findBySite(9999));
        $this->assertCount(0, $repo->findByStatus('nonexistent'));
        $this->assertNull($repo->find(9999));
    }

    /**
     * Test repository pagination edge cases.
     */
    public function test_repository_pagination_edge_cases(): void
    {
        $site = Site::factory()->create();
        Report::factory()->count(5)->create(['site_id' => $site->id]);

        $repo = new ReportRepository();

        // Page beyond available data
        $result = $repo->findBySite($site->id, perPage: 10, page: 100);
        $this->assertCount(0, $result->items());

        // Per page of 1
        $result = $repo->findBySite($site->id, perPage: 1, page: 1);
        $this->assertCount(1, $result->items());
    }

    // ============================================
    // ReportTemplateRepository Additional Tests
    // ============================================

    /**
     * Test findDefault returns null when no default exists.
     */
    public function test_find_default_returns_null_when_none(): void
    {
        $site = Site::factory()->create();
        ReportTemplate::factory()->create(['site_id' => $site->id, 'is_default' => false]);

        $repo = new ReportTemplateRepository();
        $result = $repo->findDefault($site->id);

        $this->assertNull($result);
    }

    /**
     * Test findBySite with null site_id (global templates).
     */
    public function test_find_by_site_with_null(): void
    {
        ReportTemplate::factory()->create(['site_id' => null]);

        $repo = new ReportTemplateRepository();
        $result = $repo->findBySite(null);

        $this->assertCount(1, $result);
    }

    // ============================================
    // CSVExporter Additional Tests
    // ============================================

    /**
     * Test CSV exporter handles large datasets.
     */
    public function test_csv_large_dataset(): void
    {
        $keywords = [];
        for ($i = 1; $i <= 1000; $i++) {
            $keywords[] = [
                'keyword' => "keyword {$i}",
                'current_position' => $i,
                'previous_position' => $i + 1,
                'change' => -1,
                'search_engine' => 'google',
                'location' => 'us',
                'last_checked_at' => '2026-02-24 10:00:00',
            ];
        }

        $exporter = new CsvReportExporter();
        $csv = $exporter->generate(['keywords' => $keywords]);

        $lines = substr_count($csv, "\n");
        // 1000 data rows + 1 header = 1001 lines (minus potential trailing newline)
        $this->assertGreaterThanOrEqual(1000, $lines);
    }

    /**
     * Test CSV exporter with mixed null values.
     */
    public function test_csv_mixed_null_values(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => 'full data',
                    'current_position' => 1,
                    'previous_position' => 2,
                    'change' => -1,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24',
                ],
                [
                    'keyword' => 'partial data',
                    'current_position' => null,
                    'previous_position' => 5,
                    'change' => null,
                    'search_engine' => 'google',
                    'location' => null,
                    'last_checked_at' => null,
                ],
            ],
        ];

        $exporter = new CsvReportExporter();
        $csv = $exporter->generate($data);

        $this->assertStringContainsString('full data', $csv);
        $this->assertStringContainsString('partial data', $csv);
        $this->assertStringContainsString('Not ranked', $csv);
        $this->assertStringContainsString('N/A', $csv);
    }

    // ============================================
    // PDFExporter Additional Tests
    // ============================================

    /**
     * Test PDF exporter handles empty sections gracefully.
     */
    public function test_pdf_empty_sections(): void
    {
        $exporter = new PdfReportExporter();
        $pdf = $exporter->generate(['sections' => []], []);

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('COVER PAGE', $pdf);
    }

    /**
     * Test PDF exporter with all section types.
     */
    public function test_pdf_all_sections(): void
    {
        $report = [
            'name' => 'Complete Report',
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'sections' => ['cover', 'summary', 'keywords', 'changes', 'trends'],
        ];

        $data = [
            'company_name' => 'Test Corp',
            'executive_summary' => 'Monthly summary here.',
            'keywords' => [
                ['keyword' => 'test', 'current_position' => 1, 'previous_position' => 2, 'change' => -1],
            ],
            'top_performers' => [
                ['keyword' => 'winner', 'change' => -5],
            ],
            'needs_attention' => [
                ['keyword' => 'loser', 'change' => 10],
            ],
            'trends' => [
                ['keyword' => 'test', 'points' => [['date' => '2026-02-01', 'position' => 5]]],
            ],
        ];

        $exporter = new PdfReportExporter();
        $pdf = $exporter->generate($report, $data);

        $this->assertStringContainsString('COVER PAGE', $pdf);
        $this->assertStringContainsString('EXECUTIVE SUMMARY', $pdf);
        $this->assertStringContainsString('KEYWORD RANKINGS', $pdf);
        $this->assertStringContainsString('POSITION CHANGES', $pdf);
        $this->assertStringContainsString('TREND CHARTS', $pdf);
    }

    // ============================================
    // ReportDataAggregator Additional Tests
    // ============================================

    /**
     * Test aggregator with no keywords.
     */
    public function test_aggregator_no_keywords(): void
    {
        $site = Site::factory()->create();

        $aggregator = new ReportDataAggregator();
        $result = $aggregator->aggregate($site->id, [
            'start' => '2026-02-01',
            'end' => '2026-02-28',
        ]);

        $this->assertEquals($site->id, $result['site_id']);
        $this->assertEmpty($result['keywords']);
        $this->assertEmpty($result['top_performers']);
        $this->assertEmpty($result['needs_attention']);
    }

    /**
     * Test aggregator with invalid date range.
     */
    public function test_aggregator_invalid_date_range(): void
    {
        $site = Site::factory()->create();
        Keyword::factory()->for($site)->create();

        $aggregator = new ReportDataAggregator();
        $result = $aggregator->aggregate($site->id, [
            'start' => null,
            'end' => null,
        ]);

        $this->assertNull($result['date_range']['start']);
        $this->assertNull($result['date_range']['end']);
    }

    // ============================================
    // Integration-style Tests
    // ============================================

    /**
     * Test full report generation flow.
     */
    public function test_full_report_generation_flow(): void
    {
        $site = Site::factory()->create();

        // Create template
        $template = ReportTemplate::factory()->create([
            'site_id' => $site->id,
            'sections' => ['cover', 'keywords'],
        ]);

        // Create report using repository
        $repo = new ReportRepository();
        $report = $repo->create([
            'site_id' => $site->id,
            'name' => 'Test Report',
            'type' => 'weekly',
            'format' => 'pdf',
            'status' => 'pending',
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'sections' => ['cover', 'keywords'],
        ]);

        $this->assertInstanceOf(Report::class, $report);
        $this->assertEquals('pending', $report->status);

        // Update status
        $repo->update($report->id, ['status' => 'completed', 'generated_at' => now()]);
        $this->assertEquals('completed', $report->fresh()->status);
    }

    /**
     * Test template default behavior across multiple sites.
     */
    public function test_template_default_multiple_sites(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        // Create default for site1
        $template1 = ReportTemplate::factory()->create([
            'site_id' => $site1->id,
            'is_default' => true,
        ]);

        // Create default for site2
        $template2 = ReportTemplate::factory()->create([
            'site_id' => $site2->id,
            'is_default' => true,
        ]);

        // Both should remain default (different sites)
        $this->assertTrue($template1->fresh()->is_default);
        $this->assertTrue($template2->fresh()->is_default);
    }
}
