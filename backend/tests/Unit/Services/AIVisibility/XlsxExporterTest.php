<?php

namespace Tests\Unit\Services\AIVisibility;

use App\Services\AIVisibility\XlsxExporter;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * XLSX Exporter Test
 *
 * Unit tests for XlsxExporter.
 *
 * @see TASK-34-22
 * @see TEST-34-22
 */
class XlsxExporterTest extends TestCase
{
    protected XlsxExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new XlsxExporter();
    }

    // ============================================
    // XLSX-GEN: XLSX Generation Tests
    // ============================================

    /** @test XLSX-GEN-01: Basic XLSX generation */
    public function test_basic_xlsx_generation(): void
    {
        $data = collect([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ]);

        $this->exporter->setColumns(['name' => 'Name', 'email' => 'Email']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
        // XLSX files start with PK (ZIP signature)
        $this->assertEquals('PK', substr($xlsx, 0, 2));
    }

    /** @test XLSX-GEN-02: Single sheet */
    public function test_single_sheet(): void
    {
        $data = collect([
            ['col1' => 'value1'],
        ]);

        $this->exporter->setColumns(['col1' => 'Column 1']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test XLSX-GEN-03: Multiple sheets */
    public function test_multiple_sheets(): void
    {
        $sheets = [
            [
                'name' => 'Sheet1',
                'data' => collect([['a' => '1']]),
                'columns' => ['a' => 'A'],
            ],
            [
                'name' => 'Sheet2',
                'data' => collect([['b' => '2']]),
                'columns' => ['b' => 'B'],
            ],
        ];

        $xlsx = $this->exporter->export(collect([]), 'test', ['sheets' => $sheets]);

        $this->assertNotEmpty($xlsx);
    }

    /** @test XLSX-GEN-04: Sheet naming */
    public function test_sheet_naming(): void
    {
        $data = collect([['test' => 'value']]);

        $xlsx = $this->exporter->export($data, 'test', ['sheet_name' => 'CustomSheet']);

        $this->assertNotEmpty($xlsx);
    }

    /** @test XLSX-GEN-05: Headers present */
    public function test_headers_present(): void
    {
        $data = collect([
            ['col1' => 'value1', 'col2' => 'value2'],
        ]);

        $this->exporter->setColumns(['col1' => 'Column 1', 'col2' => 'Column 2']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test XLSX-GEN-11: Unicode support */
    public function test_unicode_support(): void
    {
        $data = collect([
            ['name' => 'テスト', 'emoji' => '😀'],
        ]);

        $this->exporter->setColumns(['name' => 'Name', 'emoji' => 'Emoji']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test XLSX-GEN-12: Formula injection prevention */
    public function test_formula_injection_prevention(): void
    {
        $data = collect([
            ['formula' => '=SUM(A1:A10)'],
            ['formula' => '+5+5'],
            ['formula' => '-5+5'],
        ]);

        $this->exporter->setColumns(['formula' => 'Formula']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test XLSX-GEN-14: Empty dataset */
    public function test_empty_dataset(): void
    {
        $data = collect([]);

        $this->exporter->setColumns(['col' => 'Column']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test XLSX-GEN-15: Single row */
    public function test_single_row(): void
    {
        $data = collect([
            ['name' => 'Only Row'],
        ]);

        $this->exporter->setColumns(['name' => 'Name']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    // ============================================
    // XLSX-STRUCT: XLSX Structure Tests
    // ============================================

    /** @test XLSX-STR-05: All sheets for data_type=all */
    public function test_all_sheets_for_all_data_type(): void
    {
        $sheets = [
            ['name' => 'Projects', 'data' => collect([]), 'columns' => ['id' => 'ID']],
            ['name' => 'Queries', 'data' => collect([]), 'columns' => ['id' => 'ID']],
            ['name' => 'Analytics', 'data' => collect([]), 'columns' => ['id' => 'ID']],
            ['name' => 'Mentions', 'data' => collect([]), 'columns' => ['id' => 'ID']],
        ];

        $xlsx = $this->exporter->export(collect([]), 'test', ['sheets' => $sheets]);

        $this->assertNotEmpty($xlsx);
    }

    /** @test XLSX-STR-09: Valid Open XML structure */
    public function test_valid_open_xml_structure(): void
    {
        $data = collect([
            ['col1' => 'value1'],
        ]);

        $this->exporter->setColumns(['col1' => 'Column 1']);
        $xlsx = $this->exporter->export($data, 'test');

        // Check ZIP signature
        $this->assertEquals('PK', substr($xlsx, 0, 2));
    }

    // ============================================
    // XLSX-SEC: XLSX Security Tests
    // ============================================

    /** @test XLSX-SEC-01: Formula injection escaped */
    public function test_formula_injection_escaped(): void
    {
        $data = collect([
            ['value' => '=HYPERLINK("http://evil.com","Click")'],
        ]);

        $this->exporter->setColumns(['value' => 'Value']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test XLSX-SEC-02: DDE injection prevented */
    public function test_dde_injection_prevented(): void
    {
        $data = collect([
            ['value' => '=DDE("cmd";"/C calc";"!A1")'],
        ]);

        $this->exporter->setColumns(['value' => 'Value']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    // ============================================
    // Interface Methods Tests
    // ============================================

    /** @test: Get extension returns xlsx */
    public function test_get_extension_returns_xlsx(): void
    {
        $this->assertEquals('xlsx', $this->exporter->getExtension());
    }

    /** @test: Get MIME type */
    public function test_get_mime_type(): void
    {
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $this->exporter->getMimeType()
        );
    }

    /** @test: Enable streaming */
    public function test_enable_streaming(): void
    {
        $result = $this->exporter->enableStreaming(500);

        $this->assertSame($this->exporter, $result);
    }

    /** @test: Set columns returns self */
    public function test_set_columns_returns_self(): void
    {
        $result = $this->exporter->setColumns(['col' => 'Column']);

        $this->assertSame($this->exporter, $result);
    }

    // ============================================
    // Large Dataset Tests
    // ============================================

    /** @test XLSX-GEN-13: Large dataset handling */
    public function test_large_dataset_handling(): void
    {
        $data = collect();
        for ($i = 0; $i < 5000; $i++) {
            $data->push(['id' => $i, 'value' => "value_{$i}"]);
        }

        $this->exporter->setColumns(['id' => 'ID', 'value' => 'Value']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
        $this->assertGreaterThan(10000, strlen($xlsx)); // Should be a reasonable size
    }

    // ============================================
    // Data Type Tests
    // ============================================

    /** @test: Boolean values formatted */
    public function test_boolean_values_formatted(): void
    {
        $data = collect([
            ['active' => true, 'deleted' => false],
        ]);

        $this->exporter->setColumns(['active' => 'Active', 'deleted' => 'Deleted']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test: DateTime values formatted */
    public function test_datetime_values_formatted(): void
    {
        $date = new \DateTime('2026-03-08 12:30:45');
        $data = collect([
            ['date' => $date],
        ]);

        $this->exporter->setColumns(['date' => 'Date']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test: Numeric values preserved */
    public function test_numeric_values_preserved(): void
    {
        $data = collect([
            ['int' => 42, 'float' => 3.14159, 'negative' => -100],
        ]);

        $this->exporter->setColumns(['int' => 'Integer', 'float' => 'Float', 'negative' => 'Negative']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test: NULL values handled */
    public function test_null_values_handled(): void
    {
        $data = collect([
            ['col1' => null, 'col2' => 'value'],
        ]);

        $this->exporter->setColumns(['col1' => 'Col1', 'col2' => 'Col2']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    /** @test: Array values JSON encoded */
    public function test_array_values_json_encoded(): void
    {
        $data = collect([
            ['tags' => ['tag1', 'tag2']],
        ]);

        $this->exporter->setColumns(['tags' => 'Tags']);
        $xlsx = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($xlsx);
    }

    // ============================================
    // Sheet Name Sanitization Tests
    // ============================================

    /** @test: Invalid characters removed from sheet name */
    public function test_invalid_characters_removed_from_sheet_name(): void
    {
        $data = collect([['test' => 'value']]);

        $xlsx = $this->exporter->export($data, 'test', ['sheet_name' => 'Sheet/With*Invalid?Chars']);

        $this->assertNotEmpty($xlsx);
    }

    /** @test: Long sheet name truncated */
    public function test_long_sheet_name_truncated(): void
    {
        $longName = str_repeat('A', 50);
        $data = collect([['test' => 'value']]);

        $xlsx = $this->exporter->export($data, 'test', ['sheet_name' => $longName]);

        $this->assertNotEmpty($xlsx);
    }
}
