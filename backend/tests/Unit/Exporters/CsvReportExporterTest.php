<?php

namespace Tests\Unit\Exporters;

use Tests\TestCase;
use App\Exporters\CsvReportExporter;

class CsvReportExporterTest extends TestCase
{
    private CsvReportExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new CsvReportExporter();
    }

    /**
     * Test 4.1: Generate CSV with sample data.
     */
    public function test_generate_csv_with_sample_data(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => 'seo tools',
                    'current_position' => 5,
                    'previous_position' => 8,
                    'change' => 3,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data);

        $this->assertStringContainsString('Keyword', $csv);
        $this->assertStringContainsString('seo tools', $csv);
        $this->assertStringContainsString('5', $csv);
    }

    /**
     * Test 4.2: Column order matches spec.
     */
    public function test_column_order_matches_spec(): void
    {
        $data = ['keywords' => []];

        $csv = $this->exporter->generate($data, false); // No BOM for easier testing

        // fputcsv quotes headers with spaces, so we check for presence of each column
        $lines = explode("\n", trim($csv));
        
        $this->assertStringContainsString('Keyword', $lines[0]);
        $this->assertStringContainsString('Current Position', $lines[0]);
        $this->assertStringContainsString('Previous Position', $lines[0]);
        $this->assertStringContainsString('Change', $lines[0]);
        $this->assertStringContainsString('Search Engine', $lines[0]);
        $this->assertStringContainsString('Location', $lines[0]);
        $this->assertStringContainsString('Last Checked', $lines[0]);
        
        // Verify order by checking position in string
        $keywordPos = strpos($lines[0], 'Keyword');
        $currentPos = strpos($lines[0], 'Current Position');
        $previousPos = strpos($lines[0], 'Previous Position');
        $changePos = strpos($lines[0], 'Change');
        $searchEnginePos = strpos($lines[0], 'Search Engine');
        $locationPos = strpos($lines[0], 'Location');
        $lastCheckedPos = strpos($lines[0], 'Last Checked');
        
        $this->assertLessThan($currentPos, $keywordPos);
        $this->assertLessThan($previousPos, $currentPos);
        $this->assertLessThan($changePos, $previousPos);
        $this->assertLessThan($searchEnginePos, $changePos);
        $this->assertLessThan($locationPos, $searchEnginePos);
        $this->assertLessThan($lastCheckedPos, $locationPos);
    }

    /**
     * Test 4.3: Special characters (commas/quotes/newlines) are escaped.
     */
    public function test_special_characters_are_escaped(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => 'keyword with "quotes" and, comma',
                    'current_position' => 1,
                    'previous_position' => 2,
                    'change' => -1,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data);

        // fputcsv wraps fields with special chars in quotes
        $this->assertStringContainsString('"keyword with ""quotes"" and, comma"', $csv);
    }

    /**
     * Test 4.4: UTF-8 keywords are preserved.
     */
    public function test_utf8_keywords_are_preserved(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => 'möbel kaufen',
                    'current_position' => 3,
                    'previous_position' => 5,
                    'change' => 2,
                    'search_engine' => 'google',
                    'location' => 'de',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
                [
                    'keyword' => 'بحث جوجل', // Arabic
                    'current_position' => 10,
                    'previous_position' => 12,
                    'change' => 2,
                    'search_engine' => 'google',
                    'location' => 'sa',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
                [
                    'keyword' => '検索エンジン', // Japanese
                    'current_position' => 7,
                    'previous_position' => 9,
                    'change' => 2,
                    'search_engine' => 'google',
                    'location' => 'jp',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data);

        $this->assertStringContainsString('möbel kaufen', $csv);
        $this->assertStringContainsString('بحث جوجل', $csv);
        $this->assertStringContainsString('検索エンジン', $csv);
    }

    /**
     * Test 4.5: Missing values rendered as N/A or Not ranked.
     */
    public function test_missing_values_rendered_correctly(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => 'test keyword',
                    'current_position' => null,
                    'previous_position' => null,
                    'change' => null,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data);

        $this->assertStringContainsString('Not ranked', $csv);
        // Two N/A values for previous_position and change
        $this->assertEquals(2, substr_count($csv, 'N/A'));
    }

    /**
     * Test 4.6: Empty dataset outputs only header row.
     */
    public function test_empty_dataset_outputs_only_header(): void
    {
        $data = ['keywords' => []];

        $csv = $this->exporter->generate($data, false);

        $lines = array_filter(explode("\n", trim($csv)));
        
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('Keyword', $lines[0]);
    }

    /**
     * Test 4.7: BOM included for Excel compatibility.
     */
    public function test_bom_included_for_excel_compatibility(): void
    {
        $data = ['keywords' => []];

        $csv = $this->exporter->generate($data, true);

        // UTF-8 BOM is EF BB BF
        $bom = substr($csv, 0, 3);
        $this->assertEquals("\xEF\xBB\xBF", $bom);
    }

    /**
     * Test BOM can be disabled.
     */
    public function test_bom_can_be_disabled(): void
    {
        $data = ['keywords' => []];

        $csv = $this->exporter->generate($data, false);

        // No BOM
        $bom = substr($csv, 0, 3);
        $this->assertNotEquals("\xEF\xBB\xBF", $bom);
    }

    /**
     * Test multiple keywords in output.
     */
    public function test_multiple_keywords_in_output(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => 'first keyword',
                    'current_position' => 1,
                    'previous_position' => 2,
                    'change' => -1,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
                [
                    'keyword' => 'second keyword',
                    'current_position' => 5,
                    'previous_position' => null,
                    'change' => null,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 11:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data);

        $this->assertStringContainsString('first keyword', $csv);
        $this->assertStringContainsString('second keyword', $csv);
    }

    /**
     * Test empty keyword values handled gracefully.
     */
    public function test_empty_keyword_values_handled(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => '',
                    'current_position' => '',
                    'previous_position' => '',
                    'change' => '',
                    'search_engine' => '',
                    'location' => '',
                    'last_checked_at' => '',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data);

        // Should not throw exception
        $this->assertNotEmpty($csv);
        // Empty current_position -> Not ranked
        $this->assertStringContainsString('Not ranked', $csv);
    }

    /**
     * Test data key missing entirely.
     */
    public function test_missing_keywords_key_handled(): void
    {
        $data = [];

        $csv = $this->exporter->generate($data);

        // Should output only header
        $lines = array_filter(explode("\n", trim($csv)));
        $this->assertCount(1, $lines);
    }

    /**
     * Test very long keyword string.
     */
    public function test_very_long_keyword_string(): void
    {
        $longKeyword = str_repeat('very-long-keyword-part-', 50);
        
        $data = [
            'keywords' => [
                [
                    'keyword' => $longKeyword,
                    'current_position' => 1,
                    'previous_position' => 1,
                    'change' => 0,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data);

        $this->assertStringContainsString($longKeyword, $csv);
    }

    /**
     * Test newline in keyword is escaped.
     */
    public function test_newline_in_keyword_is_escaped(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => "keyword\nwith\nnewlines",
                    'current_position' => 1,
                    'previous_position' => 1,
                    'change' => 0,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data);

        // fputcsv wraps fields with newlines in quotes
        $this->assertStringContainsString('"keyword', $csv);
    }

    /**
     * Test numeric position values are cast correctly.
     */
    public function test_numeric_position_values_cast_correctly(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => 'test',
                    'current_position' => 1,
                    'previous_position' => 10,
                    'change' => -9,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data, false);

        $this->assertStringContainsString('test,1,10,-9', $csv);
    }

    /**
     * Test positive change value.
     */
    public function test_positive_change_value(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => 'improving keyword',
                    'current_position' => 3,
                    'previous_position' => 10,
                    'change' => 7,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data, false);

        $this->assertStringContainsString(',7,', $csv);
    }

    /**
     * Test negative change value.
     */
    public function test_negative_change_value(): void
    {
        $data = [
            'keywords' => [
                [
                    'keyword' => 'declining keyword',
                    'current_position' => 15,
                    'previous_position' => 5,
                    'change' => -10,
                    'search_engine' => 'google',
                    'location' => 'us',
                    'last_checked_at' => '2026-02-24 10:00:00',
                ],
            ],
        ];

        $csv = $this->exporter->generate($data, false);

        $this->assertStringContainsString(',-10,', $csv);
    }
}
