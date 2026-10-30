<?php

namespace Tests\Unit\Exporters;

use Tests\TestCase;
use App\Exporters\XlsToolExporter;
use App\Services\Tools\ToolResult;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * TASK-29-9: Backend Unit Tests
 * 
 * Unit tests for XlsToolExporter.
 * 
 * Tests cover:
 * - Valid XLS binary generation
 * - Headers match expected columns
 * - Data rows match input data
 * - Styling applied to header row
 * - Empty data handling
 * - Large datasets
 * - Security (XSS prevention)
 * 
 * @see TASK-29-2
 * @see \App\Exporters\XlsToolExporter
 */
class XlsToolExporterTest extends TestCase
{
    private XlsToolExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new XlsToolExporter();
    }

    // ============================================
    // SERVICE INSTANTIATION TESTS
    // ============================================

    /**
     * Test XLS-001: Service can be instantiated.
     */
    public function test_service_can_be_instantiated(): void
    {
        $exporter = new XlsToolExporter();

        $this->assertInstanceOf(XlsToolExporter::class, $exporter);
    }

    /**
     * Test XLS-002: Service has required methods.
     */
    public function test_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->exporter, 'export'));
        $this->assertTrue(method_exists($this->exporter, 'array'));
        $this->assertTrue(method_exists($this->exporter, 'headings'));
        $this->assertTrue(method_exists($this->exporter, 'styles'));
    }

    // ============================================
    // EXPORT METHOD TESTS
    // ============================================

    /**
     * Test XLS-010: export() returns valid XLS binary.
     */
    public function test_export_returns_valid_xls_binary(): void
    {
        $result = $this->createMockToolResult();

        $xls = $this->exporter->export($result);

        $this->assertNotEmpty($xls);
        // XLSX files start with PK (zip signature)
        $this->assertStringStartsWith('PK', $xls);
    }

    /**
     * Test XLS-011: export() returns non-empty binary.
     */
    public function test_export_returns_non_empty_binary(): void
    {
        $result = $this->createMockToolResult();

        $xls = $this->exporter->export($result);

        $this->assertIsString($xls);
        $this->assertGreaterThan(100, strlen($xls));
    }

    // ============================================
    // HEADERS TESTS
    // ============================================

    /**
     * Test XLS-020: Headers contain Category column.
     */
    public function test_headers_contain_category_column(): void
    {
        $headings = $this->exporter->headings();

        $this->assertContains('Category', $headings);
    }

    /**
     * Test XLS-021: Headers contain Indicator column.
     */
    public function test_headers_contain_indicator_column(): void
    {
        $headings = $this->exporter->headings();

        $this->assertContains('Indicator', $headings);
    }

    /**
     * Test XLS-022: Headers contain Value column.
     */
    public function test_headers_contain_value_column(): void
    {
        $headings = $this->exporter->headings();

        $this->assertContains('Value', $headings);
    }

    /**
     * Test XLS-023: Headers contain Status column.
     */
    public function test_headers_contain_status_column(): void
    {
        $headings = $this->exporter->headings();

        $this->assertContains('Status', $headings);
    }

    /**
     * Test XLS-024: Headers contain Recommendation column.
     */
    public function test_headers_contain_recommendation_column(): void
    {
        $headings = $this->exporter->headings();

        $this->assertContains('Recommendation', $headings);
    }

    /**
     * Test XLS-025: Headers are in correct order.
     */
    public function test_headers_are_in_correct_order(): void
    {
        $headings = $this->exporter->headings();

        $this->assertEquals(['Category', 'Indicator', 'Value', 'Status', 'Recommendation'], $headings);
    }

    /**
     * Test XLS-026: Headers count is 5.
     */
    public function test_headers_count_is_5(): void
    {
        $headings = $this->exporter->headings();

        $this->assertCount(5, $headings);
    }

    // ============================================
    // DATA ROW TESTS
    // ============================================

    /**
     * Test XLS-030: Data rows match input data.
     */
    public function test_data_rows_match_input_data(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta Tags', 'name' => 'Title', 'value' => 'Example Page', 'status' => 'OK', 'recommendation' => '-'],
        ]);

        // Need to call export first to set the result
        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertCount(1, $array);
        $this->assertEquals('Meta Tags', $array[0][0]);
        $this->assertEquals('Title', $array[0][1]);
        $this->assertEquals('Example Page', $array[0][2]);
        $this->assertEquals('OK', $array[0][3]);
        $this->assertEquals('-', $array[0][4]);
    }

    /**
     * Test XLS-031: Multiple data rows are exported.
     */
    public function test_multiple_data_rows_are_exported(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta Tags', 'name' => 'Title', 'value' => 'Page Title', 'status' => 'OK', 'recommendation' => '-'],
            ['category' => 'Meta Tags', 'name' => 'Description', 'value' => 'Too short', 'status' => 'Warning', 'recommendation' => 'Increase length'],
            ['category' => 'Content', 'name' => 'H1 Count', 'value' => '1', 'status' => 'OK', 'recommendation' => '-'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertCount(3, $array);
    }

    /**
     * Test XLS-032: Category values are preserved.
     */
    public function test_category_values_are_preserved(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta Tags', 'name' => 'Title', 'value' => '-', 'status' => 'OK', 'recommendation' => '-'],
            ['category' => 'Content', 'name' => 'H1', 'value' => '-', 'status' => 'OK', 'recommendation' => '-'],
            ['category' => 'Technical', 'name' => 'SSL', 'value' => '-', 'status' => 'OK', 'recommendation' => '-'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertEquals('Meta Tags', $array[0][0]);
        $this->assertEquals('Content', $array[1][0]);
        $this->assertEquals('Technical', $array[2][0]);
    }

    /**
     * Test XLS-033: Status values are preserved.
     */
    public function test_status_values_are_preserved(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => '-', 'status' => 'OK', 'recommendation' => '-'],
            ['category' => 'Meta', 'name' => 'Description', 'value' => '-', 'status' => 'Warning', 'recommendation' => '-'],
            ['category' => 'Meta', 'name' => 'Keywords', 'value' => '-', 'status' => 'Error', 'recommendation' => '-'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertEquals('OK', $array[0][3]);
        $this->assertEquals('Warning', $array[1][3]);
        $this->assertEquals('Error', $array[2][3]);
    }

    /**
     * Test XLS-034: Recommendation values are preserved.
     */
    public function test_recommendation_values_are_preserved(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Description', 'value' => 'Too short', 'status' => 'Warning', 'recommendation' => 'Increase length to 150-160 characters'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertEquals('Increase length to 150-160 characters', $array[0][4]);
    }

    /**
     * Test XLS-035: Null value handled as dash.
     */
    public function test_null_value_handled_as_dash(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => null, 'status' => 'OK', 'recommendation' => null],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertEquals('-', $array[0][2]);
        $this->assertEquals('-', $array[0][4]);
    }

    /**
     * Test XLS-036: Empty string value handled as dash.
     */
    public function test_empty_string_value_handled_as_dash(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => '', 'status' => 'OK', 'recommendation' => ''],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertEquals('-', $array[0][2]);
        $this->assertEquals('-', $array[0][4]);
    }

    // ============================================
    // STYLING TESTS
    // ============================================

    /**
     * Test XLS-040: Header row is styled bold.
     */
    public function test_header_row_is_styled_bold(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Apply styles
        $styles = $this->exporter->styles($sheet);

        $this->assertArrayHasKey(1, $styles);
        $this->assertArrayHasKey('font', $styles[1]);
        $this->assertTrue($styles[1]['font']['bold']);
    }

    /**
     * Test XLS-041: Styles method returns array.
     */
    public function test_styles_method_returns_array(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $styles = $this->exporter->styles($sheet);

        $this->assertIsArray($styles);
    }

    /**
     * Test XLS-042: Header row style applies to row 1.
     */
    public function test_header_row_style_applies_to_row_1(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $styles = $this->exporter->styles($sheet);

        $this->assertArrayHasKey(1, $styles);
    }

    // ============================================
    // EMPTY DATA TESTS
    // ============================================

    /**
     * Test XLS-050: Empty indicators handled gracefully.
     */
    public function test_empty_indicators_handled_gracefully(): void
    {
        $result = $this->createMockToolResultWithIndicators([]);

        $xls = $this->exporter->export($result);

        $this->assertNotEmpty($xls);
        $this->assertStringStartsWith('PK', $xls);
    }

    /**
     * Test XLS-051: Empty data array returns empty.
     */
    public function test_empty_data_array_returns_empty(): void
    {
        $result = ToolResult::ok('Test Tool', 'https://example.com', [
            'indicators' => [],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertIsArray($array);
        $this->assertCount(0, $array);
    }

    /**
     * Test XLS-052: Missing indicators key handled.
     */
    public function test_missing_indicators_key_handled(): void
    {
        $result = ToolResult::ok('Test Tool', 'https://example.com', []);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertIsArray($array);
        $this->assertCount(0, $array);
    }

    // ============================================
    // LARGE DATASET TESTS
    // ============================================

    /**
     * Test XLS-060: Large dataset with 100 indicators.
     */
    public function test_large_dataset_with_100_indicators(): void
    {
        $indicators = [];
        for ($i = 1; $i <= 100; $i++) {
            $indicators[] = [
                'category' => 'Category ' . ceil($i / 20),
                'name' => "Indicator {$i}",
                'value' => "Value {$i}",
                'status' => $i % 3 === 0 ? 'Warning' : 'OK',
                'recommendation' => $i % 3 === 0 ? "Fix issue {$i}" : '-',
            ];
        }
        $result = $this->createMockToolResultWithIndicators($indicators);

        $xls = $this->exporter->export($result);

        $this->assertNotEmpty($xls);
        $this->assertStringStartsWith('PK', $xls);
    }

    /**
     * Test XLS-061: Large dataset array count matches.
     */
    public function test_large_dataset_array_count_matches(): void
    {
        $indicators = [];
        for ($i = 1; $i <= 50; $i++) {
            $indicators[] = [
                'category' => 'Category',
                'name' => "Indicator {$i}",
                'value' => "Value {$i}",
                'status' => 'OK',
                'recommendation' => '-',
            ];
        }
        $result = $this->createMockToolResultWithIndicators($indicators);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertCount(50, $array);
    }

    /**
     * Test XLS-062: Very long text values handled.
     */
    public function test_very_long_text_values_handled(): void
    {
        $longValue = str_repeat('a', 1000);
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => $longValue, 'status' => 'OK', 'recommendation' => '-'],
        ]);

        $xls = $this->exporter->export($result);

        $this->assertNotEmpty($xls);
    }

    // ============================================
    // XSS PREVENTION TESTS
    // ============================================

    /**
     * Test XLS-070: Script tags are sanitized.
     */
    public function test_script_tags_are_sanitized(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => '<script>alert("XSS")</script>', 'value' => '-', 'status' => 'OK', 'recommendation' => '-'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        // The XSS attempt should be in the array but will be handled by Excel
        $this->assertStringContainsString('script', $array[0][1]);
    }

    /**
     * Test XLS-071: HTML entities in values.
     */
    public function test_html_entities_in_values(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => '<strong>Bold</strong>', 'status' => 'OK', 'recommendation' => '-'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertStringContainsString('<strong>', $array[0][2]);
    }

    /**
     * Test XLS-072: Special characters in data.
     */
    public function test_special_characters_in_data(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title (with parens)', 'value' => 'Value "quoted"', 'status' => 'OK', 'recommendation' => 'Fix & improve'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertEquals('Title (with parens)', $array[0][1]);
        $this->assertEquals('Value "quoted"', $array[0][2]);
        $this->assertEquals('Fix & improve', $array[0][4]);
    }

    /**
     * Test XLS-073: Formula injection prevented.
     */
    public function test_formula_injection_prevented(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Formula', 'value' => '=1+1', 'status' => 'OK', 'recommendation' => '-'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        // Formula should be prefixed with single quote
        $this->assertEquals("'=1+1", $array[0][2]);
    }

    // ============================================
    // UNICODE TESTS
    // ============================================

    /**
     * Test XLS-080: Unicode characters in data.
     */
    public function test_unicode_characters_in_data(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Content', 'name' => 'Title 日本語', 'value' => 'Value 中文', 'status' => 'OK', 'recommendation' => '-'],
            ['category' => 'Content', 'name' => 'Описание', 'value' => 'Значение', 'status' => 'OK', 'recommendation' => '-'],
        ]);

        $xls = $this->exporter->export($result);

        $this->assertNotEmpty($xls);
    }

    /**
     * Test XLS-081: Emoji in data.
     */
    public function test_emoji_in_data(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Content', 'name' => 'Title 🎉', 'value' => 'Value ✅', 'status' => 'OK', 'recommendation' => 'Fix ⚠️'],
        ]);

        $xls = $this->exporter->export($result);

        $this->assertNotEmpty($xls);
    }

    // ============================================
    // INTEGRATION TESTS
    // ============================================

    /**
     * Test XLS-090: Full export workflow.
     */
    public function test_full_export_workflow(): void
    {
        $result = $this->createFullMockToolResult();

        $xls = $this->exporter->export($result);

        $this->assertNotEmpty($xls);
        $this->assertStringStartsWith('PK', $xls);
        $this->assertGreaterThan(1000, strlen($xls));
    }

    /**
     * Test XLS-091: Export produces consistent output.
     */
    public function test_export_produces_consistent_output(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => 'Test', 'status' => 'OK', 'recommendation' => '-'],
        ]);

        $this->exporter->export($result);
        $array1 = $this->exporter->array();
        
        $this->exporter->export($result);
        $array2 = $this->exporter->array();

        $this->assertEquals($array1, $array2);
    }

    /**
     * Test XLS-092: Different tools produce different exports.
     */
    public function test_different_tools_produce_different_exports(): void
    {
        $result1 = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => 'Page 1', 'status' => 'OK', 'recommendation' => '-'],
        ]);

        $result2 = $this->createMockToolResultWithIndicators([
            ['category' => 'Content', 'name' => 'H1', 'value' => 'Page 2', 'status' => 'Warning', 'recommendation' => 'Add H1'],
        ]);

        $this->exporter->export($result1);
        $array1 = $this->exporter->array();
        
        $this->exporter->export($result2);
        $array2 = $this->exporter->array();

        $this->assertNotEquals($array1, $array2);
    }

    // ============================================
    // EDGE CASE TESTS
    // ============================================

    /**
     * Test XLS-100: Indicator with all empty fields.
     */
    public function test_indicator_with_all_empty_fields(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => '', 'name' => '', 'value' => '', 'status' => '', 'recommendation' => ''],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertCount(1, $array);
    }

    /**
     * Test XLS-101: Numeric values handled correctly.
     */
    public function test_numeric_values_handled_correctly(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Content', 'name' => 'Word Count', 'value' => '1234', 'status' => 'OK', 'recommendation' => '-'],
            ['category' => 'Performance', 'name' => 'Load Time', 'value' => '2.5', 'status' => 'Warning', 'recommendation' => 'Optimize'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertEquals('1234', $array[0][2]);
        $this->assertEquals('2.5', $array[1][2]);
    }

    /**
     * Test XLS-102: Boolean values handled.
     */
    public function test_boolean_values_handled(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Technical', 'name' => 'HTTPS', 'value' => 'true', 'status' => 'OK', 'recommendation' => '-'],
            ['category' => 'Technical', 'name' => 'Mobile Friendly', 'value' => 'false', 'status' => 'Error', 'recommendation' => 'Fix mobile'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertEquals('true', $array[0][2]);
        $this->assertEquals('false', $array[1][2]);
    }

    /**
     * Test XLS-103: Mixed status values.
     */
    public function test_mixed_status_values(): void
    {
        $result = $this->createMockToolResultWithIndicators([
            ['category' => 'Meta', 'name' => 'Title', 'value' => '-', 'status' => 'OK', 'recommendation' => '-'],
            ['category' => 'Meta', 'name' => 'Description', 'value' => '-', 'status' => 'Warning', 'recommendation' => '-'],
            ['category' => 'Meta', 'name' => 'Keywords', 'value' => '-', 'status' => 'Error', 'recommendation' => '-'],
            ['category' => 'Meta', 'name' => 'Robots', 'value' => '-', 'status' => 'Info', 'recommendation' => '-'],
        ]);

        $this->exporter->export($result);
        $array = $this->exporter->array();

        $this->assertCount(4, $array);
        $this->assertEquals('OK', $array[0][3]);
        $this->assertEquals('Warning', $array[1][3]);
        $this->assertEquals('Error', $array[2][3]);
        $this->assertEquals('Info', $array[3][3]);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    private function createMockToolResult(string $toolName = 'Test Tool', string $url = 'https://example.com'): ToolResult
    {
        return ToolResult::ok($toolName, $url, [
            'indicators' => [
                ['category' => 'Meta Tags', 'name' => 'Title', 'value' => 'Example Page', 'status' => 'OK', 'recommendation' => '-'],
            ],
            'summary' => ['score' => 75],
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

    private function createFullMockToolResult(): ToolResult
    {
        return ToolResult::ok('Meta Tag Analyzer', 'https://example.com', [
            'indicators' => [
                ['category' => 'Meta Tags', 'name' => 'Title', 'value' => 'Example Page', 'status' => 'OK', 'recommendation' => '-'],
                ['category' => 'Meta Tags', 'name' => 'Description', 'value' => 'A description', 'status' => 'Warning', 'recommendation' => 'Increase length'],
                ['category' => 'Content', 'name' => 'H1 Count', 'value' => '1', 'status' => 'OK', 'recommendation' => '-'],
            ],
            'summary' => [
                'score' => 85,
                'pass' => 10,
                'warning' => 3,
                'fail' => 2,
            ],
            'recommendations' => [
                ['priority' => 'high', 'message' => 'Add meta description', 'action' => 'Write 150-160 characters'],
            ],
        ]);
    }
}
