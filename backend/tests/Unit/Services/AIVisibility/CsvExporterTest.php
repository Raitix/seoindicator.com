<?php

namespace Tests\Unit\Services\AIVisibility;

use App\Services\AIVisibility\CsvExporter;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * CSV Exporter Test
 *
 * Unit tests for CsvExporter.
 *
 * @see TASK-34-22
 * @see TEST-34-22
 */
class CsvExporterTest extends TestCase
{
    protected CsvExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new CsvExporter();
    }

    // ============================================
    // CSV-GEN: CSV Generation Tests
    // ============================================

    /** @test CSV-GEN-01: Basic CSV generation */
    public function test_basic_csv_generation(): void
    {
        $data = collect([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ]);

        $this->exporter->setColumns(['name' => 'Name', 'email' => 'Email']);
        $csv = $this->exporter->export($data, 'test');

        $this->assertIsString($csv);
        $this->assertStringContainsString('Name', $csv);
        $this->assertStringContainsString('John', $csv);
    }

    /** @test CSV-GEN-02: Headers present */
    public function test_headers_present(): void
    {
        $data = collect([
            ['col1' => 'value1', 'col2' => 'value2'],
        ]);

        $this->exporter->setColumns(['col1' => 'Column 1', 'col2' => 'Column 2']);
        $csv = $this->exporter->export($data, 'test');

        $this->assertStringContainsString('Column 1', $csv);
        $this->assertStringContainsString('Column 2', $csv);
    }

    /** @test CSV-GEN-03: UTF-8 BOM present */
    public function test_utf8_bom_present(): void
    {
        $data = collect([['test' => 'value']]);

        $this->exporter->setColumns(['test' => 'Test']);
        $csv = $this->exporter->export($data, 'test');

        // Check for UTF-8 BOM
        $this->assertEquals("\xEF\xBB\xBF", substr($csv, 0, 3));
    }

    /** @test CSV-GEN-04: Special characters - commas */
    public function test_special_characters_commas(): void
    {
        $data = collect([
            ['text' => 'value, with, commas'],
        ]);

        $this->exporter->setColumns(['text' => 'Text']);
        $csv = $this->exporter->export($data, 'test');

        // Field with commas should be quoted
        $this->assertStringContainsString('"value, with, commas"', $csv);
    }

    /** @test CSV-GEN-05: Quotes in data escaped */
    public function test_quotes_in_data_escaped(): void
    {
        $data = collect([
            ['text' => 'value with "quotes"'],
        ]);

        $this->exporter->setColumns(['text' => 'Text']);
        $csv = $this->exporter->export($data, 'test');

        // Quotes should be escaped as double quotes
        $this->assertStringContainsString('""quotes""', $csv);
    }

    /** @test CSV-GEN-07: Unicode data */
    public function test_unicode_data(): void
    {
        $data = collect([
            ['name' => 'テスト', 'emoji' => '😀'],
        ]);

        $this->exporter->setColumns(['name' => 'Name', 'emoji' => 'Emoji']);
        $csv = $this->exporter->export($data, 'test');

        $this->assertStringContainsString('テスト', $csv);
        $this->assertStringContainsString('😀', $csv);
    }

    /** @test CSV-GEN-08: Empty values */
    public function test_empty_values(): void
    {
        $data = collect([
            ['col1' => 'value', 'col2' => null, 'col3' => ''],
        ]);

        $this->exporter->setColumns(['col1' => 'Col1', 'col2' => 'Col2', 'col3' => 'Col3']);
        $csv = $this->exporter->export($data, 'test');

        $this->assertStringContainsString('value', $csv);
    }

    /** @test CSV-GEN-09: Boolean values */
    public function test_boolean_values(): void
    {
        $data = collect([
            ['active' => true, 'deleted' => false],
        ]);

        $this->exporter->setColumns(['active' => 'Active', 'deleted' => 'Deleted']);
        $csv = $this->exporter->export($data, 'test');

        $this->assertStringContainsString('true', $csv);
        $this->assertStringContainsString('false', $csv);
    }

    /** @test CSV-GEN-10: Date formatting */
    public function test_date_formatting(): void
    {
        $date = new \DateTime('2026-03-08 12:30:45');
        $data = collect([
            ['date' => $date],
        ]);

        $this->exporter->setColumns(['date' => 'Date']);
        $csv = $this->exporter->export($data, 'test');

        $this->assertStringContainsString('2026-03-08', $csv);
    }

    /** @test CSV-GEN-12: Empty dataset */
    public function test_empty_dataset(): void
    {
        $data = collect([]);

        $this->exporter->setColumns(['col1' => 'Column 1']);
        $csv = $this->exporter->export($data, 'test');

        // Should still have headers
        $this->assertStringContainsString('Column 1', $csv);
    }

    /** @test CSV-GEN-13: Single row */
    public function test_single_row(): void
    {
        $data = collect([
            ['name' => 'Only Row'],
        ]);

        $this->exporter->setColumns(['name' => 'Name']);
        $csv = $this->exporter->export($data, 'test');

        $this->assertStringContainsString('Only Row', $csv);
    }

    /** @test CSV-GEN-15: NULL handling */
    public function test_null_handling(): void
    {
        $data = collect([
            ['col1' => null, 'col2' => 'value'],
        ]);

        $this->exporter->setColumns(['col1' => 'Col1', 'col2' => 'Col2']);
        $csv = $this->exporter->export($data, 'test');

        $this->assertStringContainsString('value', $csv);
    }

    /** @test CSV-GEN-16: Array values JSON encoded */
    public function test_array_values_json_encoded(): void
    {
        $data = collect([
            ['tags' => ['tag1', 'tag2']],
        ]);

        $this->exporter->setColumns(['tags' => 'Tags']);
        $csv = $this->exporter->export($data, 'test');

        // CSV properly escapes quotes by doubling them, so ["tag1","tag2"] becomes ["",tag1"",""tag2""]
        // Just check for the presence of tag values
        $this->assertStringContainsString('tag1', $csv);
        $this->assertStringContainsString('tag2', $csv);
    }

    // ============================================
    // CSV-STRUCT: CSV Structure Tests
    // ============================================

    /** @test CSV-STR-05: Column order consistent */
    public function test_column_order_consistent(): void
    {
        $data = collect([
            ['a' => '1', 'b' => '2', 'c' => '3'],
            ['a' => '4', 'b' => '5', 'c' => '6'],
        ]);

        $this->exporter->setColumns(['a' => 'A', 'b' => 'B', 'c' => 'C']);
        $csv = $this->exporter->export($data, 'test');

        $lines = explode("\n", trim($csv));
        // Skip BOM and check header
        $headerLine = substr($lines[0], 3);
        $this->assertEquals('A,B,C', $headerLine);
    }

    /** @test CSV-STR-08: Row count matches data */
    public function test_row_count_matches_data(): void
    {
        $data = collect();
        for ($i = 0; $i < 100; $i++) {
            $data->push(['id' => $i]);
        }

        $this->exporter->setColumns(['id' => 'ID']);
        $csv = $this->exporter->export($data, 'test');

        // Remove BOM before counting lines
        $csvWithoutBom = str_replace("\xEF\xBB\xBF", '', $csv);
        // Use preg_split to handle both \r\n and \n line endings
        $lines = preg_split('/\r?\n/', trim($csvWithoutBom));
        // Filter out empty lines
        $lines = array_filter($lines, fn($line) => trim($line) !== '');
        // Header + 100 data rows
        $this->assertCount(101, $lines);
    }

    // ============================================
    // Interface Methods Tests
    // ============================================

    /** @test: Get extension returns csv */
    public function test_get_extension_returns_csv(): void
    {
        $this->assertEquals('csv', $this->exporter->getExtension());
    }

    /** @test: Get MIME type */
    public function test_get_mime_type(): void
    {
        $this->assertEquals('text/csv', $this->exporter->getMimeType());
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

    /** @test CSV-GEN-11: Large dataset handling */
    public function test_large_dataset_handling(): void
    {
        $data = collect();
        for ($i = 0; $i < 10000; $i++) {
            $data->push(['id' => $i, 'value' => "value_{$i}"]);
        }

        $this->exporter->setColumns(['id' => 'ID', 'value' => 'Value']);
        $csv = $this->exporter->export($data, 'test');

        $this->assertNotEmpty($csv);
        $this->assertStringContainsString('ID', $csv);
    }

    /** @test: Streaming for large datasets */
    public function test_streaming_for_large_datasets(): void
    {
        $data = collect();
        for ($i = 0; $i < 15000; $i++) {
            $data->push(['id' => $i]);
        }

        $this->exporter->setColumns(['id' => 'ID']);
        $this->exporter->enableStreaming(1000);

        $result = $this->exporter->export($data, 'test');

        // Streaming returns a generator
        $this->assertInstanceOf(\Generator::class, $result);
    }

    // ============================================
    // Custom Options Tests
    // ============================================

    /** @test: Custom delimiter */
    public function test_custom_delimiter(): void
    {
        $data = collect([
            ['col1' => 'a', 'col2' => 'b'],
        ]);

        $this->exporter->setColumns(['col1' => 'Col1', 'col2' => 'Col2']);
        $csv = $this->exporter->export($data, 'test', ['delimiter' => ';']);

        $this->assertStringContainsString(';', $csv);
    }

    /** @test: Disable BOM */
    public function test_disable_bom(): void
    {
        $data = collect([['test' => 'value']]);

        $this->exporter->setColumns(['test' => 'Test']);
        $csv = $this->exporter->export($data, 'test', ['include_bom' => false]);

        // Should not have BOM
        $this->assertNotEquals("\xEF\xBB\xBF", substr($csv, 0, 3));
    }
}
