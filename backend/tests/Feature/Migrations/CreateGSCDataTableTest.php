<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\GSCData;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Migration tests for TASK-12-2: Create GSC Data Table
 *
 * Tests verify:
 * - Table structure matches specification
 * - Indexes are created
 * - Data types are correct
 * - Unique constraints prevent duplicates
 */
class CreateGSCDataTableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the gsc_data table exists.
     */
    public function test_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('gsc_data'),
            'gsc_data table should exist'
        );
    }

    /**
     * Test that all required columns exist.
     */
    public function test_required_columns_exist(): void
    {
        $columns = [
            'id',
            'project_id',
            'query',
            'page',
            'clicks',
            'impressions',
            'ctr',
            'avg_position',
            'data_date',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('gsc_data', $column),
                "Column '{$column}' should exist in gsc_data table"
            );
        }
    }

    /**
     * Test that composite indexes exist.
     */
    public function test_indexes_exist(): void
    {
        $table = 'gsc_data';
        $indexes = $this->getTableIndexes($table);

        // Check for query index
        $this->assertIndexExists($indexes, 'query');

        // Check for data_date index
        $this->assertIndexExists($indexes, 'data_date');

        // Check for composite indexes
        $this->assertIndexExists($indexes, 'idx_query_date');
        $this->assertIndexExists($indexes, 'idx_project_date');
        $this->assertIndexExists($indexes, 'idx_project_query');
        $this->assertIndexExists($indexes, 'idx_date');
    }

    /**
     * Test that unique constraint prevents duplicate data.
     */
    public function test_unique_constraint(): void
    {
        $duplicateData = [
            'project_id' => 1,
            'query' => 'seo tools',
            'page' => '/blog/seo-tools',
            'clicks' => 100,
            'impressions' => 5000,
            'ctr' => 0.02,
            'avg_position' => 3.5,
            'data_date' => '2026-02-21',
        ];

        // Create first record
        DB::table('gsc_data')->insert($duplicateData);

        // Attempt to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('gsc_data')->insert($duplicateData);
    }

    /**
     * Test that page column can be null.
     */
    public function test_page_nullable(): void
    {
        $data = [
            'project_id' => 1,
            'query' => 'seo tools',
            'page' => null,
            'clicks' => 100,
            'impressions' => 5000,
            'ctr' => 0.02,
            'avg_position' => 3.5,
            'data_date' => '2026-02-21',
        ];

        DB::table('gsc_data')->insert($data);
        $record = DB::table('gsc_data')->where('query', 'seo tools')->first();

        $this->assertNull($record->page);
    }

    /**
     * Test that numeric fields have correct types.
     */
    public function test_numeric_field_types(): void
    {
        $data = [
            'project_id' => 1,
            'query' => 'test query',
            'page' => '/test',
            'clicks' => 123,
            'impressions' => 4567,
            'ctr' => 0.027,
            'avg_position' => 5.3,
            'data_date' => '2026-02-21',
        ];

        DB::table('gsc_data')->insert($data);
        $record = DB::table('gsc_data')->where('query', 'test query')->first();

        $this->assertEquals(123, $record->clicks);
        $this->assertEquals(4567, $record->impressions);
        $this->assertEqualsWithDelta(0.027, $record->ctr, 0.001);
        $this->assertEqualsWithDelta(5.3, $record->avg_position, 0.1);
    }

    /**
     * Test that data_date is stored as date only.
     */
    public function test_data_date_storage(): void
    {
        $data = [
            'project_id' => 1,
            'query' => 'date test',
            'page' => null,
            'clicks' => 50,
            'impressions' => 1000,
            'ctr' => 0.05,
            'avg_position' => 7.2,
            'data_date' => '2026-02-21',
        ];

        DB::table('gsc_data')->insert($data);
        $record = DB::table('gsc_data')->where('query', 'date test')->first();

        $this->assertEquals('2026-02-21', $record->data_date);
    }

    /**
     * Test factory creates valid data.
     */
    public function test_factory_creates_valid_data(): void
    {
        $gscData = GSCData::factory()->create();

        $this->assertDatabaseHas('gsc_data', [
            'id' => $gscData->id,
            'query' => $gscData->query,
        ]);

        $this->assertNotNull($gscData->clicks);
        $this->assertNotNull($gscData->impressions);
        $this->assertNotNull($gscData->ctr);
        $this->assertNotNull($gscData->avg_position);
        $this->assertNotNull($gscData->data_date);
    }

    /**
     * Test factory states work correctly.
     */
    public function test_factory_states(): void
    {
        // Recent state
        $recent = GSCData::factory()->recent()->make();
        $recentDate = new \DateTime($recent->data_date);
        $now = new \DateTime();
        $diff = $now->diff($recentDate)->days;
        
        $this->assertLessThanOrEqual(7, $diff);

        // High performing state
        $highPerforming = GSCData::factory()->highPerforming()->make();
        $this->assertGreaterThanOrEqual(500, $highPerforming->clicks);
        $this->assertGreaterThanOrEqual(0.05, $highPerforming->ctr);
        $this->assertLessThanOrEqual(5.0, $highPerforming->avg_position);

        // Opportunity state
        $opportunity = GSCData::factory()->opportunity()->make();
        $this->assertGreaterThanOrEqual(2000, $opportunity->impressions);
        $this->assertLessThanOrEqual(0.03, $opportunity->ctr);
        $this->assertLessThanOrEqual(15.0, $opportunity->avg_position);
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Helper: Get table indexes.
     */
    protected function getTableIndexes(string $table): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = [];
            $result = DB::select("PRAGMA index_list('{$table}')");
            foreach ($result as $row) {
                $indexInfo = DB::select("PRAGMA index_info('{$row->name}')");
                foreach ($indexInfo as $info) {
                    $indexes[$row->name][] = $info->name;
                }
            }
            return $indexes;
        }

        // MySQL
        $indexes = [];
        $result = DB::select("SHOW INDEX FROM {$table}");
        foreach ($result as $row) {
            $indexes[$row->Key_name][] = $row->Column_name;
        }
        return $indexes;
    }

    /**
     * Helper: Assert index exists.
     */
    protected function assertIndexExists(array $indexes, string $indexName): void
    {
        $found = collect($indexes)->contains(function ($columns, $name) use ($indexName) {
            return str_contains(strtolower($name), strtolower($indexName));
        });

        $this->assertTrue($found, "Index '{$indexName}' does not exist");
    }
}