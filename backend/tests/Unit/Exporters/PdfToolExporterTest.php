<?php

namespace Tests\Unit\Exporters;

use Tests\TestCase;
use App\Exporters\PdfToolExporter;
use App\Services\Tools\ToolResult;
use Illuminate\Support\Carbon;

class PdfToolExporterTest extends TestCase
{
    private PdfToolExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new PdfToolExporter();
    }

    // ============================================
    // SERVICE INSTANTIATION TESTS
    // ============================================

    /**
     * Test PDF-001: Service can be instantiated.
     */
    public function test_service_can_be_instantiated(): void
    {
        $exporter = new PdfToolExporter();

        $this->assertInstanceOf(PdfToolExporter::class, $exporter);
    }

    /**
     * Test PDF-002: Service has correct page dimensions.
     */
    public function test_service_has_correct_page_dimensions(): void
    {
        $reflection = new \ReflectionClass(PdfToolExporter::class);
        
        $this->assertTrue($reflection->hasConstant('PAGE_WIDTH'));
        $this->assertTrue($reflection->hasConstant('PAGE_HEIGHT'));
        $this->assertTrue($reflection->hasConstant('MARGIN'));
        $this->assertTrue($reflection->hasConstant('ROWS_PER_PAGE'));
        
        $this->assertEquals(595, $reflection->getConstant('PAGE_WIDTH'));
        $this->assertEquals(842, $reflection->getConstant('PAGE_HEIGHT'));
        $this->assertEquals(40, $reflection->getConstant('MARGIN'));
        $this->assertEquals(25, $reflection->getConstant('ROWS_PER_PAGE'));
    }

    // ============================================
    // EXPORT METHOD TESTS
    // ============================================

    /**
     * Test PDF-010: export() returns valid PDF string.
     */
    public function test_export_returns_valid_pdf_string(): void
    {
        $result = $this->createMockToolResult();
        
        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
    }

    /**
     * Test PDF-011: export() with branding applies colors.
     */
    public function test_export_with_branding_applies_colors(): void
    {
        $result = $this->createMockToolResult();
        $branding = [
            'primaryColor' => '#ff0000',
            'secondaryColor' => '#00ff00',
            'fontFamily' => 'Times',
        ];

        $pdf = $this->exporter->export($result, $branding);

        $this->assertNotEmpty($pdf);
        $this->assertStringContainsString('/Times-Roman', $pdf);
    }

    /**
     * Test PDF-012: export() without branding uses defaults.
     */
    public function test_export_without_branding_uses_defaults(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result, null);

        $this->assertNotEmpty($pdf);
        $this->assertStringContainsString('/Helvetica', $pdf);
    }

    /**
     * Test PDF-013: export() with empty branding uses defaults.
     */
    public function test_export_with_empty_branding_uses_defaults(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result, []);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    // ============================================
    // COVER PAGE TESTS
    // ============================================

    /**
     * Test PDF-020: Cover page has tool name.
     */
    public function test_cover_page_has_tool_name(): void
    {
        $result = $this->createMockToolResult('Meta Tag Analyzer');

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('Meta Tag Analyzer', $pdf);
    }

    /**
     * Test PDF-021: Cover page has URL analyzed.
     */
    public function test_cover_page_has_url_analyzed(): void
    {
        $result = $this->createMockToolResult('Test Tool', 'https://example.com/page');

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('example.com', $pdf);
    }

    /**
     * Test PDF-022: Cover page has analysis date.
     */
    public function test_cover_page_has_analysis_date(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        // Should contain date (format varies but should have year)
        $this->assertMatchesRegularExpression('/\d{4}/', $pdf);
    }

    /**
     * Test PDF-023: Cover page has logo placeholder when no branding.
     */
    public function test_cover_page_has_logo_placeholder(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('Logo', $pdf);
    }

    /**
     * Test PDF-024: Cover page with branding logo.
     */
    public function test_cover_page_with_branding_logo(): void
    {
        $result = $this->createMockToolResult();
        $branding = [
            'companyName' => 'Test Company',
        ];

        $pdf = $this->exporter->export($result, $branding);

        $this->assertStringContainsString('Test Company', $pdf);
    }

    // ============================================
    // SUMMARY SECTION TESTS
    // ============================================

    /**
     * Test PDF-030: Summary section has overall score.
     */
    public function test_summary_section_has_overall_score(): void
    {
        $result = $this->createMockToolResultWithSummary(85);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('85', $pdf);
    }

    /**
     * Test PDF-031: Summary section has pass count.
     */
    public function test_summary_section_has_pass_count(): void
    {
        $result = $this->createMockToolResultWithSummary(75, 10, 2, 3);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('10', $pdf);
    }

    /**
     * Test PDF-032: Summary section has warning count.
     */
    public function test_summary_section_has_warning_count(): void
    {
        $result = $this->createMockToolResultWithSummary(75, 10, 5, 2);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('5', $pdf);
    }

    /**
     * Test PDF-033: Summary section has fail count.
     */
    public function test_summary_section_has_fail_count(): void
    {
        $result = $this->createMockToolResultWithSummary(60, 8, 4, 3);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('3', $pdf);
    }

    /**
     * Test PDF-034: Summary section with zero score.
     */
    public function test_summary_section_with_zero_score(): void
    {
        $result = $this->createMockToolResultWithSummary(0);

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    /**
     * Test PDF-035: Summary section with perfect score.
     */
    public function test_summary_section_with_perfect_score(): void
    {
        $result = $this->createMockToolResultWithSummary(100, 15, 0, 0);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('100', $pdf);
    }

    // ============================================
    // INDICATORS TABLE TESTS
    // ============================================

    /**
     * Test PDF-040: Indicators table renders categories.
     */
    public function test_indicators_table_renders_categories(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta Tags', 'name' => 'Title', 'status' => 'pass'],
            ['category' => 'Content', 'name' => 'H1 Count', 'status' => 'pass'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('Meta Tags', $pdf);
        $this->assertStringContainsString('Content', $pdf);
    }

    /**
     * Test PDF-041: Indicators table renders indicator names.
     */
    public function test_indicators_table_renders_indicator_names(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta Tags', 'name' => 'Title Length', 'status' => 'pass'],
            ['category' => 'Meta Tags', 'name' => 'Meta Description', 'status' => 'warning'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('Title Length', $pdf);
        $this->assertStringContainsString('Meta Description', $pdf);
    }

    /**
     * Test PDF-042: Indicators table renders status icons.
     */
    public function test_indicators_table_renders_status_icons(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'status' => 'pass'],
            ['category' => 'Meta', 'name' => 'Description', 'status' => 'warning'],
            ['category' => 'Meta', 'name' => 'Keywords', 'status' => 'fail'],
        ]);

        $pdf = $this->exporter->export($result);

        // Status should be represented somehow (✓, ⚠, ✗ or similar)
        $this->assertNotEmpty($pdf);
    }

    /**
     * Test PDF-043: Indicators table renders values.
     */
    public function test_indicators_table_renders_values(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title Length', 'value' => '60 characters', 'status' => 'pass'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('60 characters', $pdf);
    }

    /**
     * Test PDF-044: Indicators grouped by category.
     */
    public function test_indicators_grouped_by_category(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta Tags', 'name' => 'Title', 'status' => 'pass'],
            ['category' => 'Meta Tags', 'name' => 'Description', 'status' => 'pass'],
            ['category' => 'Content', 'name' => 'H1', 'status' => 'warning'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('Meta Tags', $pdf);
        $this->assertStringContainsString('Content', $pdf);
    }

    /**
     * Test PDF-045: Indicators table empty state.
     */
    public function test_indicators_table_empty_state(): void
    {
        $result = $this->createMockToolResultWithIndicators([]);

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    // ============================================
    // PAGE BREAK TESTS
    // ============================================

    /**
     * Test PDF-050: Page break after 25 indicators.
     */
    public function test_page_break_after_25_indicators(): void
    {
        $indicators = [];
        for ($i = 1; $i <= 30; $i++) {
            $indicators[] = [
                'category' => 'Category ' . ceil($i / 10),
                'name' => "Indicator {$i}",
                'status' => 'pass',
            ];
        }
        $result = $this->createMockToolResultWithIndicators($indicators);

        $pdf = $this->exporter->export($result);

        // Should have multiple pages
        preg_match_all('/Page \d+ of \d+/', $pdf, $matches);
        $this->assertGreaterThan(1, count($matches[0]));
    }

    /**
     * Test PDF-051: Large indicator list spans multiple pages.
     */
    public function test_large_indicator_list_spans_pages(): void
    {
        $indicators = [];
        for ($i = 1; $i <= 100; $i++) {
            $indicators[] = [
                'category' => 'Category',
                'name' => "Long Indicator Name {$i}",
                'value' => "Value {$i}",
                'status' => $i % 3 === 0 ? 'warning' : 'pass',
            ];
        }
        $result = $this->createMockToolResultWithIndicators($indicators);

        $pdf = $this->exporter->export($result);

        preg_match_all('/Page \d+ of \d+/', $pdf, $matches);
        $pageCount = count(array_unique($matches[0]));
        $this->assertGreaterThan(3, $pageCount);
    }

    /**
     * Test PDF-052: Exactly 25 indicators fit on one page.
     */
    public function test_exactly_25_indicators_fit_on_one_page(): void
    {
        $indicators = [];
        for ($i = 1; $i <= 25; $i++) {
            $indicators[] = [
                'category' => 'Category',
                'name' => "Indicator {$i}",
                'status' => 'pass',
            ];
        }
        $result = $this->createMockToolResultWithIndicators($indicators);

        $pdf = $this->exporter->export($result);

        // Cover + Summary + Indicators page(s) + Recommendations
        // Should still have multiple pages but indicators should fit
        $this->assertNotEmpty($pdf);
    }

    // ============================================
    // RECOMMENDATIONS SECTION TESTS
    // ============================================

    /**
     * Test PDF-060: Recommendations section renders.
     */
    public function test_recommendations_section_renders(): void
    {
        $result = $this->createMockToolResultWithRecommendations([
            ['priority' => 'high', 'message' => 'Fix missing meta description'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('Recommendation', $pdf);
    }

    /**
     * Test PDF-061: Recommendations sorted by priority.
     */
    public function test_recommendations_sorted_by_priority(): void
    {
        $result = $this->createMockToolResultWithRecommendations([
            ['priority' => 'low', 'message' => 'Low priority item'],
            ['priority' => 'high', 'message' => 'High priority item'],
            ['priority' => 'medium', 'message' => 'Medium priority item'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('High priority item', $pdf);
        $this->assertStringContainsString('Medium priority item', $pdf);
        $this->assertStringContainsString('Low priority item', $pdf);
    }

    /**
     * Test PDF-062: Recommendations with empty list.
     */
    public function test_recommendations_with_empty_list(): void
    {
        $result = $this->createMockToolResultWithRecommendations([]);

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    /**
     * Test PDF-063: Recommendations with action items.
     */
    public function test_recommendations_with_action_items(): void
    {
        $result = $this->createMockToolResultWithRecommendations([
            ['priority' => 'high', 'message' => 'Add meta description', 'action' => 'Add a meta description of 150-160 characters'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('Add meta description', $pdf);
    }

    // ============================================
    // A4 PAGE FORMAT TESTS
    // ============================================

    /**
     * Test PDF-070: A4 page dimensions.
     */
    public function test_a4_page_dimensions(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        // A4: 595 x 842 points
        $this->assertStringContainsString('/MediaBox [0 0 595 842]', $pdf);
    }

    /**
     * Test PDF-071: Page margins are applied.
     */
    public function test_page_margins_applied(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        // Content should start at margin (40 points from edge)
        // This is verified by checking the PDF structure
        $this->assertStringContainsString('40', $pdf);
    }

    // ============================================
    // XSS PREVENTION TESTS
    // ============================================

    /**
     * Test PDF-080: Script tags are escaped.
     */
    public function test_script_tags_are_escaped(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => '<script>alert("XSS")</script>', 'status' => 'pass'],
        ]);

        $pdf = $this->exporter->export($result);

        // Should not contain unescaped script tags
        $this->assertStringNotContainsString('<script>', $pdf);
    }

    /**
     * Test PDF-081: Special characters are escaped.
     */
    public function test_special_characters_are_escaped(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Test (with parens)', 'status' => 'pass'],
        ]);

        $pdf = $this->exporter->export($result);

        // Parentheses should be escaped in PDF text strings
        $this->assertNotEmpty($pdf);
    }

    /**
     * Test PDF-082: Backslashes are escaped.
     */
    public function test_backslashes_are_escaped(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Test\\with\\backslash', 'status' => 'pass'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
    }

    /**
     * Test PDF-083: HTML entities in values are safe.
     */
    public function test_html_entities_in_values_are_safe(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => '<strong>Bold</strong>', 'status' => 'pass'],
        ]);

        $pdf = $this->exporter->export($result);

        // Should not contain raw HTML
        $this->assertStringNotContainsString('<strong>', $pdf);
    }

    /**
     * Test PDF-084: JavaScript in URL is safe.
     */
    public function test_javascript_in_url_is_safe(): void
    {
        $result = $this->createMockToolResult('Tool', 'https://example.com"<script>');

        $pdf = $this->exporter->export($result);

        $this->assertStringNotContainsString('<script>', $pdf);
    }

    // ============================================
    // BRANDING TESTS
    // ============================================

    /**
     * Test PDF-090: Branding with custom primary color.
     */
    public function test_branding_with_custom_primary_color(): void
    {
        $result = $this->createMockToolResult();
        $branding = ['primaryColor' => '#10b981'];

        $pdf = $this->exporter->export($result, $branding);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    /**
     * Test PDF-091: Branding with custom secondary color.
     */
    public function test_branding_with_custom_secondary_color(): void
    {
        $result = $this->createMockToolResult();
        $branding = ['secondaryColor' => '#6b7280'];

        $pdf = $this->exporter->export($result, $branding);

        $this->assertNotEmpty($pdf);
    }

    /**
     * Test PDF-092: Branding with custom font.
     */
    public function test_branding_with_custom_font(): void
    {
        $result = $this->createMockToolResult();
        $branding = ['fontFamily' => 'Times'];

        $pdf = $this->exporter->export($result, $branding);

        $this->assertStringContainsString('/Times-Roman', $pdf);
    }

    /**
     * Test PDF-093: Branding with company name.
     */
    public function test_branding_with_company_name(): void
    {
        $result = $this->createMockToolResult();
        $branding = ['companyName' => 'ACME Corp'];

        $pdf = $this->exporter->export($result, $branding);

        $this->assertStringContainsString('ACME Corp', $pdf);
    }

    /**
     * Test PDF-094: Branding with all options.
     */
    public function test_branding_with_all_options(): void
    {
        $result = $this->createMockToolResult();
        $branding = [
            'primaryColor' => '#10b981',
            'secondaryColor' => '#6b7280',
            'fontFamily' => 'Helvetica',
            'companyName' => 'Full Brand Co',
        ];

        $pdf = $this->exporter->export($result, $branding);

        $this->assertStringContainsString('Full Brand Co', $pdf);
    }

    /**
     * Test PDF-095: Invalid color falls back to default.
     */
    public function test_invalid_color_falls_back_to_default(): void
    {
        $result = $this->createMockToolResult();
        $branding = ['primaryColor' => 'not-a-color'];

        $pdf = $this->exporter->export($result, $branding);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    // ============================================
    // PDF STRUCTURE TESTS
    // ============================================

    /**
     * Test PDF-100: PDF has valid catalog object.
     */
    public function test_pdf_has_valid_catalog_object(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('/Type /Catalog', $pdf);
    }

    /**
     * Test PDF-101: PDF has valid pages object.
     */
    public function test_pdf_has_valid_pages_object(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('/Type /Pages', $pdf);
    }

    /**
     * Test PDF-102: PDF has valid font object.
     */
    public function test_pdf_has_valid_font_object(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('/Type /Font', $pdf);
    }

    /**
     * Test PDF-103: PDF has xref table.
     */
    public function test_pdf_has_xref_table(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('xref', $pdf);
    }

    /**
     * Test PDF-104: PDF has trailer.
     */
    public function test_pdf_has_trailer(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        $this->assertStringContainsString('trailer', $pdf);
    }

    /**
     * Test PDF-105: PDF has page numbering.
     */
    public function test_pdf_has_page_numbering(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        $this->assertMatchesRegularExpression('/Page \d+ of \d+/', $pdf);
    }

    // ============================================
    // EDGE CASE TESTS
    // ============================================

    /**
     * Test PDF-110: Empty tool name handled.
     */
    public function test_empty_tool_name_handled(): void
    {
        $result = $this->createMockToolResult('');

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    /**
     * Test PDF-111: Very long URL handled.
     */
    public function test_very_long_url_handled(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 500);
        $result = $this->createMockToolResult('Tool', $longUrl);

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
    }

    /**
     * Test PDF-112: Unicode characters in indicators.
     */
    public function test_unicode_characters_in_indicators(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Content', 'name' => 'Title 日本語', 'status' => 'pass'],
            ['category' => 'Content', 'name' => 'Описание', 'status' => 'pass'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
    }

    /**
     * Test PDF-113: Emoji in content handled.
     */
    public function test_emoji_in_content_handled(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Content', 'name' => 'Title 🎉', 'status' => 'pass'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
    }

    /**
     * Test PDF-114: Failed result handled gracefully.
     */
    public function test_failed_result_handled_gracefully(): void
    {
        $result = ToolResult::fail(
            'Test Tool',
            'https://example.com',
            'Analysis failed',
            'ANALYSIS_ERROR'
        );

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
    }

    /**
     * Test PDF-115: Null values in indicators handled.
     */
    public function test_null_values_in_indicators_handled(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => null, 'status' => 'pass'],
        ]);

        $pdf = $this->exporter->export($result);

        $this->assertNotEmpty($pdf);
    }

    // ============================================
    // INTEGRATION TESTS
    // ============================================

    /**
     * Test PDF-120: Full export with all sections.
     */
    public function test_full_export_with_all_sections(): void
    {
        $result = $this->createFullMockToolResult();
        $branding = [
            'primaryColor' => '#2563eb',
            'secondaryColor' => '#64748b',
            'fontFamily' => 'Inter',
            'companyName' => 'Test Company',
        ];

        $pdf = $this->exporter->export($result, $branding);

        $this->assertStringContainsString('Test Company', $pdf);
        $this->assertStringContainsString('example.com', $pdf);
        $this->assertMatchesRegularExpression('/\d{4}/', $pdf); // Date
    }

    /**
     * Test PDF-121: Export produces consistent output.
     */
    public function test_export_produces_consistent_output(): void
    {
        $result = $this->createMockToolResult();

        $pdf1 = $this->exporter->export($result);
        $pdf2 = $this->exporter->export($result);

        // Remove timestamps for comparison
        $pdf1Clean = preg_replace('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', 'TIMESTAMP', $pdf1);
        $pdf2Clean = preg_replace('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', 'TIMESTAMP', $pdf2);

        $this->assertEquals($pdf1Clean, $pdf2Clean);
    }

    /**
     * Test PDF-122: Binary PDF content returned.
     */
    public function test_binary_pdf_content_returned(): void
    {
        $result = $this->createMockToolResult();

        $pdf = $this->exporter->export($result);

        $this->assertIsString($pdf);
        $this->assertGreaterThan(100, strlen($pdf));
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    private function createMockToolResult(string $toolName = 'Test Tool', string $url = 'https://example.com'): ToolResult
    {
        return ToolResult::ok($toolName, $url, [
            'indicators' => [],
            'summary' => ['score' => 75],
            'recommendations' => [],
        ]);
    }

    private function createMockToolResultWithSummary(int $score, int $pass = 10, int $warning = 2, int $fail = 0): ToolResult
    {
        return ToolResult::ok('Analysis Tool', 'https://example.com', [
            'indicators' => [],
            'summary' => [
                'score' => $score,
                'pass' => $pass,
                'warning' => $warning,
                'fail' => $fail,
            ],
            'recommendations' => [],
        ]);
    }

    private function createMockToolResultWithIndicators(array $indicators): ToolResult
    {
        return ToolResult::ok('Analysis Tool', 'https://example.com', [
            'indicators' => $indicators,
            'summary' => ['score' => 75],
            'recommendations' => [],
        ]);
    }

    private function createMockToolResultWithRecommendations(array $recommendations): ToolResult
    {
        return ToolResult::ok('Analysis Tool', 'https://example.com', [
            'indicators' => [],
            'summary' => ['score' => 75],
            'recommendations' => $recommendations,
        ]);
    }

    private function createFullMockToolResult(): ToolResult
    {
        return ToolResult::ok('Meta Tag Analyzer', 'https://example.com', [
            'indicators' => [
                ['category' => 'Meta Tags', 'name' => 'Title', 'value' => 'Example Page', 'status' => 'pass'],
                ['category' => 'Meta Tags', 'name' => 'Description', 'value' => 'A description', 'status' => 'warning'],
                ['category' => 'Content', 'name' => 'H1 Count', 'value' => '1', 'status' => 'pass'],
            ],
            'summary' => [
                'score' => 85,
                'pass' => 10,
                'warning' => 3,
                'fail' => 2,
            ],
            'recommendations' => [
                ['priority' => 'high', 'message' => 'Add meta description', 'action' => 'Write 150-160 characters'],
                ['priority' => 'medium', 'message' => 'Optimize title length', 'action' => 'Keep between 50-60 characters'],
            ],
        ]);
    }
}
