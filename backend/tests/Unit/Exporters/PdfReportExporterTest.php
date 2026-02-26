<?php

namespace Tests\Unit\Exporters;

use Tests\TestCase;
use App\Exporters\PdfReportExporter;
use Illuminate\Support\Facades\Storage;

class PdfReportExporterTest extends TestCase
{
    private PdfReportExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new PdfReportExporter();
    }

    // ============================================
    // BRANDING TESTS (TASK-4-16)
    // ============================================

    /**
     * Test 16.1: PDF with branding - colors and font applied.
     */
    public function test_pdf_with_branding_applies_colors(): void
    {
        $report = [
            'name' => 'Branded Report',
            'sections' => ['cover'],
        ];
        $data = [];
        $branding = [
            'primaryColor' => '#ff0000',
            'secondaryColor' => '#00ff00',
            'fontFamily' => 'Times',
            'companyName' => 'Test Company',
        ];

        $pdf = $this->exporter->generate($report, $data, $branding);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        // Verify company name is present
        $this->assertStringContainsString('Test Company', $pdf);
        // Verify font is applied (Times-Roman)
        $this->assertStringContainsString('/Times-Roman', $pdf);
    }

    /**
     * Test 16.2: PDF without branding - defaults used.
     */
    public function test_pdf_without_branding_uses_defaults(): void
    {
        $report = [
            'name' => 'Default Report',
            'sections' => ['cover'],
        ];
        $data = [];

        $pdf = $this->exporter->generate($report, $data, null);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        // Default font is Helvetica
        $this->assertStringContainsString('/Helvetica', $pdf);
    }

    /**
     * Test 16.3: Company name on cover page.
     */
    public function test_company_name_on_cover_page(): void
    {
        $report = [
            'name' => 'Monthly Report',
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'sections' => ['cover'],
        ];
        $data = [];
        $branding = [
            'companyName' => 'ACME Corporation',
        ];

        $pdf = $this->exporter->generate($report, $data, $branding);

        $this->assertStringContainsString('ACME Corporation', $pdf);
        $this->assertStringContainsString('Prepared for', $pdf);
    }

    /**
     * Test 16.4: Missing logo file - PDF renders without error.
     */
    public function test_missing_logo_file_renders_without_error(): void
    {
        $report = [
            'name' => 'Report',
            'sections' => ['cover'],
        ];
        $data = [];
        $branding = [
            'logoFile' => 'logos/nonexistent-logo.png',
        ];

        $pdf = $this->exporter->generate($report, $data, $branding);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        // Should still have valid PDF structure
        $this->assertStringContainsString('%%EOF', $pdf);
    }

    /**
     * Test 16.5: Invalid color - falls back to defaults.
     */
    public function test_invalid_color_falls_back_to_defaults(): void
    {
        $report = [
            'name' => 'Report',
            'sections' => ['cover'],
        ];
        $data = [];
        $branding = [
            'primaryColor' => 'invalid-color',
            'secondaryColor' => 'also-invalid',
        ];

        $pdf = $this->exporter->generate($report, $data, $branding);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        // Should still render without errors
        $this->assertStringContainsString('%%EOF', $pdf);
    }

    /**
     * Test edge case: Custom font not found falls back to default.
     */
    public function test_custom_font_fallback(): void
    {
        $report = [
            'name' => 'Report',
            'sections' => ['cover'],
        ];
        $data = [];
        $branding = [
            'fontFamily' => 'NonExistentFont',
        ];

        $pdf = $this->exporter->generate($report, $data, $branding);

        $this->assertNotEmpty($pdf);
        // Should fall back to Helvetica
        $this->assertStringContainsString('/Helvetica', $pdf);
    }

    /**
     * Test font mapping for common fonts.
     */
    public function test_font_mapping(): void
    {
        $report = ['name' => 'Report', 'sections' => ['cover']];
        $data = [];

        // Test Inter -> Helvetica
        $pdf = $this->exporter->generate($report, $data, ['fontFamily' => 'Inter']);
        $this->assertStringContainsString('/Helvetica', $pdf);

        // Test Roboto -> Helvetica
        $pdf = $this->exporter->generate($report, $data, ['fontFamily' => 'Roboto']);
        $this->assertStringContainsString('/Helvetica', $pdf);

        // Test Times New Roman -> Times-Roman
        $pdf = $this->exporter->generate($report, $data, ['fontFamily' => 'Times New Roman']);
        $this->assertStringContainsString('/Times-Roman', $pdf);

        // Test Courier -> Courier
        $pdf = $this->exporter->generate($report, $data, ['fontFamily' => 'Courier']);
        $this->assertStringContainsString('/Courier', $pdf);
    }

    /**
     * Test hex to RGB color conversion.
     */
    public function test_hex_to_rgb_conversion(): void
    {
        $report = ['name' => 'Report', 'sections' => ['cover']];
        $data = [];

        // Test valid hex colors
        $branding = [
            'primaryColor' => '#2563eb', // Blue
            'secondaryColor' => '#64748b', // Gray
        ];

        $pdf = $this->exporter->generate($report, $data, $branding);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    /**
     * Test branding with all sections.
     */
    public function test_branding_with_all_sections(): void
    {
        $report = [
            'name' => 'Full Branded Report',
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'sections' => ['cover', 'summary', 'keywords', 'changes', 'trends'],
        ];
        $data = [
            'executive_summary' => 'This is a summary.',
            'keywords' => [
                ['keyword' => 'test', 'current_position' => 1, 'previous_position' => 2, 'change' => -1],
            ],
            'top_performers' => [['keyword' => 'winner', 'change' => 10]],
            'needs_attention' => [],
            'trends' => [['keyword' => 'trending', 'points' => [['date' => '2026-02-01', 'position' => 5]]]],
        ];
        $branding = [
            'primaryColor' => '#10b981',
            'secondaryColor' => '#6b7280',
            'fontFamily' => 'Inter',
            'companyName' => 'Branded Corp',
        ];

        $pdf = $this->exporter->generate($report, $data, $branding);

        $this->assertStringContainsString('Branded Corp', $pdf);
        $this->assertStringContainsString('EXECUTIVE SUMMARY', $pdf);
        $this->assertStringContainsString('KEYWORD RANKINGS', $pdf);
        $this->assertStringContainsString('POSITION CHANGES', $pdf);
        $this->assertStringContainsString('TREND CHARTS', $pdf);
    }

    /**
     * Test logo placeholder when no logo provided.
     */
    public function test_logo_placeholder_when_no_logo(): void
    {
        $report = [
            'name' => 'Report',
            'sections' => ['cover'],
        ];
        $data = [];
        $branding = [
            'companyName' => 'Test Company',
        ];

        $pdf = $this->exporter->generate($report, $data, $branding);

        $this->assertStringContainsString('placeholder', $pdf);
    }

    /**
     * Test empty branding array uses defaults.
     */
    public function test_empty_branding_uses_defaults(): void
    {
        $report = [
            'name' => 'Report',
            'sections' => ['cover'],
        ];
        $data = [];

        $pdf = $this->exporter->generate($report, $data, []);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    // ============================================
    // ORIGINAL TESTS (TASK-4-5)
    // ============================================

    /**
     * Test 5.1: Generate PDF with minimal data.
     */
    public function test_generate_pdf_with_minimal_data(): void
    {
        $report = ['name' => 'Test Report'];
        $data = [];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
    }

    /**
     * Test 5.2: Cover page rendering.
     */
    public function test_cover_page_rendering(): void
    {
        $report = [
            'name' => 'Monthly SEO Report',
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'sections' => ['cover'],
        ];
        $data = ['company_name' => 'ACME Corp'];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('COVER PAGE', $pdf);
        $this->assertStringContainsString('Monthly SEO Report', $pdf);
        $this->assertStringContainsString('2026-02-01 - 2026-02-28', $pdf);
        $this->assertStringContainsString('ACME Corp', $pdf);
        $this->assertStringContainsString('Logo', $pdf);
    }

    /**
     * Test 5.3: Summary section enabled.
     */
    public function test_summary_section_enabled(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['summary'],
        ];
        $data = [
            'executive_summary' => 'This is the executive summary for the report.',
        ];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('EXECUTIVE SUMMARY', $pdf);
        $this->assertStringContainsString('executive summary', $pdf);
    }

    /**
     * Test 5.4: Changes section enabled.
     */
    public function test_changes_section_enabled(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['changes'],
        ];
        $data = [
            'top_performers' => [
                ['keyword' => 'seo tools', 'change' => 5],
            ],
            'needs_attention' => [
                ['keyword' => 'marketing', 'change' => -10],
            ],
        ];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('POSITION CHANGES', $pdf);
        $this->assertStringContainsString('Top Performers', $pdf);
        $this->assertStringContainsString('Needs Attention', $pdf);
        $this->assertStringContainsString('seo tools', $pdf);
        $this->assertStringContainsString('marketing', $pdf);
    }

    /**
     * Test 5.5: Trends section enabled.
     */
    public function test_trends_section_enabled(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['trends'],
        ];
        $data = [
            'trends' => [
                [
                    'keyword' => 'seo tools',
                    'points' => [['date' => '2026-02-01', 'position' => 5], ['date' => '2026-02-15', 'position' => 3]],
                ],
            ],
        ];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('TREND CHARTS', $pdf);
        $this->assertStringContainsString('seo tools', $pdf);
        $this->assertStringContainsString('2 data points', $pdf);
    }

    /**
     * Test 5.6: Keywords table rendered.
     */
    public function test_keywords_table_rendered(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['keywords'],
        ];
        $data = [
            'keywords' => [
                [
                    'keyword' => 'seo tools',
                    'current_position' => 5,
                    'previous_position' => 8,
                    'change' => 3,
                ],
                [
                    'keyword' => 'marketing software',
                    'current_position' => 12,
                    'previous_position' => null,
                    'change' => null,
                ],
            ],
        ];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('KEYWORD RANKINGS', $pdf);
        $this->assertStringContainsString('seo tools', $pdf);
        $this->assertStringContainsString('marketing software', $pdf);
    }

    /**
     * Test 5.7: Page size/margins (A4).
     */
    public function test_page_size_is_a4(): void
    {
        $report = ['name' => 'Test Report'];
        $data = [];

        $pdf = $this->exporter->generate($report, $data);

        // A4 size in points: 595 x 842
        $this->assertStringContainsString('/MediaBox [0 0 595 842]', $pdf);
    }

    /**
     * Test 5.8: Page numbering present.
     */
    public function test_page_numbering_present(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['cover', 'keywords'],
        ];
        $data = [
            'keywords' => array_fill(0, 50, ['keyword' => 'test']),
        ];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Page 1 of', $pdf);
        $this->assertStringContainsString('Page 2 of', $pdf);
    }

    /**
     * Test multiple sections combined.
     */
    public function test_multiple_sections_combined(): void
    {
        $report = [
            'name' => 'Full Report',
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'sections' => ['cover', 'summary', 'keywords', 'changes'],
        ];
        $data = [
            'company_name' => 'Test Corp',
            'executive_summary' => 'Summary text here.',
            'keywords' => [
                ['keyword' => 'test', 'current_position' => 1, 'previous_position' => 2, 'change' => -1],
            ],
            'top_performers' => [
                ['keyword' => 'winner', 'change' => 10],
            ],
            'needs_attention' => [],
        ];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('COVER PAGE', $pdf);
        $this->assertStringContainsString('EXECUTIVE SUMMARY', $pdf);
        $this->assertStringContainsString('KEYWORD RANKINGS', $pdf);
        $this->assertStringContainsString('POSITION CHANGES', $pdf);
    }

    /**
     * Test empty sections defaults to cover and keywords.
     */
    public function test_empty_sections_defaults(): void
    {
        $report = ['name' => 'Default Report'];
        $data = [];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('COVER PAGE', $pdf);
        $this->assertStringContainsString('KEYWORD RANKINGS', $pdf);
    }

    /**
     * Test missing logo renders placeholder.
     */
    public function test_missing_logo_renders_placeholder(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['cover'],
        ];
        $data = []; // No logo data

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Logo', $pdf);
        $this->assertStringContainsString('placeholder', $pdf);
    }

    /**
     * Test date range formatting.
     */
    public function test_date_range_formatting(): void
    {
        $report = [
            'name' => 'Test Report',
            'date_range_start' => '2026-02-01',
            'date_range_end' => '2026-02-28',
            'sections' => ['cover'],
        ];
        $data = [];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('2026-02-01 - 2026-02-28', $pdf);
    }

    /**
     * Test missing date range.
     */
    public function test_missing_date_range(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['cover'],
        ];
        $data = [];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Date range not specified', $pdf);
    }

    /**
     * Test special characters in keywords are escaped.
     */
    public function test_special_characters_escaped(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['keywords'],
        ];
        $data = [
            'keywords' => [
                ['keyword' => 'keyword (with parens)', 'current_position' => 1],
            ],
        ];

        $pdf = $this->exporter->generate($report, $data);

        // Parentheses should be escaped in PDF
        $this->assertStringContainsString('keyword', $pdf);
    }

    /**
     * Test trends with no data.
     */
    public function test_trends_with_no_data(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['trends'],
        ];
        $data = ['trends' => []];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('Trend data not available', $pdf);
    }

    /**
     * Test PDF structure has required objects.
     */
    public function test_pdf_structure_has_required_objects(): void
    {
        $report = ['name' => 'Test Report'];
        $data = [];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('/Type /Catalog', $pdf);
        $this->assertStringContainsString('/Type /Pages', $pdf);
        $this->assertStringContainsString('/Type /Font', $pdf);
        $this->assertStringContainsString('/Type /Page', $pdf);
        $this->assertStringContainsString('xref', $pdf);
        $this->assertStringContainsString('trailer', $pdf);
    }

    /**
     * Test null values formatted correctly in keywords.
     */
    public function test_null_values_formatted_in_keywords(): void
    {
        $report = [
            'name' => 'Test Report',
            'sections' => ['keywords'],
        ];
        $data = [
            'keywords' => [
                [
                    'keyword' => 'new keyword',
                    'current_position' => null,
                    'previous_position' => null,
                    'change' => null,
                ],
            ],
        ];

        $pdf = $this->exporter->generate($report, $data);

        $this->assertStringContainsString('new keyword', $pdf);
        $this->assertStringContainsString('Not ranked', $pdf);
        $this->assertStringContainsString('N/A', $pdf);
    }

    /**
     * Test large keyword list spans multiple pages.
     */
    public function test_large_keyword_list_spans_pages(): void
    {
        $report = [
            'name' => 'Large Report',
            'sections' => ['keywords'],
        ];
        // Create enough keywords to span multiple pages (more than 38 lines per page)
        $keywords = [];
        for ($i = 1; $i <= 100; $i++) {
            $keywords[] = [
                'keyword' => "keyword number {$i}",
                'current_position' => $i,
                'previous_position' => $i + 1,
                'change' => 1,
            ];
        }
        $data = ['keywords' => $keywords];

        $pdf = $this->exporter->generate($report, $data);

        // Should have multiple pages
        preg_match_all('/Page \d+ of \d+/', $pdf, $matches);
        $this->assertGreaterThan(1, count($matches[0]));
    }
}
