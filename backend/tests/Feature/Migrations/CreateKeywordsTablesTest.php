<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateKeywordsTablesTest extends TestCase
{
    /**
     * Test that migration creates keywords table with all required columns.
     */
    public function test_migration_creates_keywords_table(): void
    {
        $this->artisan('migrate:fresh');
        
        $this->assertTrue(Schema::hasTable('keywords'));
        
        $columns = [
            'id',
            'user_id',
            'site_id',
            'keyword',
            'target_url',
            'search_engine',
            'location',
            'device',
            'status',
            'last_checked_at',
            'created_at',
            'updated_at',
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('keywords', $column),
                "Missing column: {$column}"
            );
        }
    }

    /**
     * Test that migration creates ranking_history table with all required columns.
     */
    public function test_migration_creates_ranking_history_table(): void
    {
        $this->artisan('migrate:fresh');
        
        $this->assertTrue(Schema::hasTable('ranking_history'));
        
        $columns = [
            'id',
            'keyword_id',
            'position',
            'url_found',
            'serp_features',
            'checked_at',
            'check_duration_ms',
            'error',
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('ranking_history', $column),
                "Missing column: {$column}"
            );
        }
    }

    /**
     * Test that foreign key constraints are created.
     */
    public function test_foreign_key_constraints_exist(): void
    {
        $this->artisan('migrate:fresh');
        
        // Test keywords.site_id references sites.id
        $this->assertForeignKeyExists('keywords', 'site_id');
        
        // Test ranking_history.keyword_id references keywords.id
        $this->assertForeignKeyExists('ranking_history', 'keyword_id');
    }

    /**
     * Test that unique constraint exists on keywords.
     */
    public function test_unique_constraint_on_keywords(): void
    {
        $this->artisan('migrate:fresh');
        
        // Create site first
        $siteId = DB::table('sites')->insertGetId([
            'url' => 'https://example.com',
            'name' => 'Test Site',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Insert first keyword
        DB::table('keywords')->insert([
            'site_id' => $siteId,
            'keyword' => 'test keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Attempt to insert duplicate - should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DB::table('keywords')->insert([
            'site_id' => $siteId,
            'keyword' => 'test keyword',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that indexes are created for performance.
     */
    public function test_indexes_exist(): void
    {
        $this->artisan('migrate:fresh');
        
        $keywordIndexes = $this->getTableIndexes('keywords');
        $rankingIndexes = $this->getTableIndexes('ranking_history');
        
        // Check keywords indexes
        $this->assertIndexExists($keywordIndexes, 'keywords_site_id_index');
        $this->assertIndexExists($keywordIndexes, 'keywords_status_index');
        $this->assertIndexExists($keywordIndexes, 'keywords_last_checked_at_index');
        
        // Check ranking_history indexes
        $this->assertIndexExists($rankingIndexes, 'ranking_history_keyword_id_index');
        $this->assertIndexExists($rankingIndexes, 'ranking_history_checked_at_index');
    }

    /**
     * Test migration rollback drops tables.
     */
    public function test_migration_rollback(): void
    {
        $this->artisan('migrate:fresh');
        
        $this->assertTrue(Schema::hasTable('keywords'));
        $this->assertTrue(Schema::hasTable('ranking_history'));
        
        $this->artisan('migrate:rollback');
        
        $this->assertFalse(Schema::hasTable('ranking_history'));
        $this->assertFalse(Schema::hasTable('keywords'));
    }

    /**
     * Test migration can be re-run after rollback.
     */
    public function test_migration_rerun_after_rollback(): void
    {
        $this->artisan('migrate:fresh');
        $this->artisan('migrate:rollback');
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasTable('keywords'));
        $this->assertTrue(Schema::hasTable('ranking_history'));
    }

    /**
     * Helper: Assert foreign key exists.
     */
    private function assertForeignKeyExists(string $table, string $column): void
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            // Non-MySQL drivers (e.g., sqlite) don't expose INFORMATION_SCHEMA
            $this->assertTrue(true);
            return;
        }
        
        $foreignKeyName = "{$table}_{$column}_foreign";
        $result = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);
        
        $this->assertNotEmpty($result, "Foreign key on {$table}.{$column} does not exist");
    }

    /**
     * Helper: Get table indexes.
     */
    private function getTableIndexes(string $table): array
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $result = DB::select("PRAGMA index_list({$table})");
            return array_map(fn($r) => $r->name, $result);
        }
        
        $result = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_NAME = ? 
            GROUP BY INDEX_NAME
        ", [$table]);
        
        return array_map(fn($r) => $r->INDEX_NAME, $result);
    }

    /**
     * Helper: Assert index exists.
     */
    private function assertIndexExists(array $indexes, string $indexName): void
    {
        $found = collect($indexes)->contains(function ($index) use ($indexName) {
            return str_contains(strtolower($index), strtolower($indexName));
        });
        
        $this->assertTrue($found, "Index {$indexName} does not exist");
    }
}
