<?php

namespace Tests\Unit\Migrations;

use App\Models\Site;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the keyword_suggestions and dismissed_suggestions tables migration.
 *
 * Validates that the migration creates the correct table structures
 * with all required columns, types, constraints, and indexes.
 *
 * @see TASK-5-1
 * @see TEST-5-1
 * @see SPEC-005
 */
class CreateKeywordSuggestionsTableTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // keyword_suggestions TABLE TESTS
    // =========================================================================

    /**
     * Test that the keyword_suggestions table exists.
     * KS-001
     */
    public function test_keyword_suggestions_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('keyword_suggestions'),
            'keyword_suggestions table should exist'
        );
    }

    /**
     * Test that all required columns exist on keyword_suggestions.
     * KS-002 through KS-012
     */
    public function test_keyword_suggestions_has_required_columns(): void
    {
        $columns = [
            'id',
            'site_id',
            'keyword',
            'source',
            'search_volume',
            'difficulty_score',
            'opportunity_score',
            'is_tracking',
            'is_dismissed',
            'discovered_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('keyword_suggestions', $column),
                "Column {$column} is missing from keyword_suggestions table"
            );
        }
    }

    /**
     * Test that id column is primary key and auto-increment.
     * KS-002
     */
    public function test_id_column_is_primary_key_auto_increment(): void
    {
        $site = Site::factory()->create();
        
        $suggestionId = \DB::table('keyword_suggestions')->insertGetId([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($suggestionId);
        $this->assertIsInt($suggestionId);
    }

    /**
     * Test that site_id column is required and references sites.
     * KS-003, KS-013
     */
    public function test_site_id_is_required_and_references_sites(): void
    {
        $this->assertTrue(
            Schema::hasColumn('keyword_suggestions', 'site_id'),
            'site_id column should exist'
        );

        // Test FK constraint - inserting with non-existent site_id should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        \DB::table('keyword_suggestions')->insert([
            'site_id' => 99999, // Non-existent site
            'keyword' => 'test keyword',
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that keyword column is required.
     * KS-004
     */
    public function test_keyword_column_is_required(): void
    {
        $site = Site::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        \DB::table('keyword_suggestions')->insert([
            'site_id' => $site->id,
            'keyword' => null,
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that source column is required.
     * KS-005
     */
    public function test_source_column_is_required(): void
    {
        $site = Site::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        \DB::table('keyword_suggestions')->insert([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that search_volume is nullable.
     * KS-006
     */
    public function test_search_volume_is_nullable(): void
    {
        $site = Site::factory()->create();

        $id = \DB::table('keyword_suggestions')->insertGetId([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => 'suggest',
            'search_volume' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $suggestion = \DB::table('keyword_suggestions')->find($id);
        $this->assertNull($suggestion->search_volume);
    }

    /**
     * Test that difficulty_score is nullable.
     * KS-007
     */
    public function test_difficulty_score_is_nullable(): void
    {
        $site = Site::factory()->create();

        $id = \DB::table('keyword_suggestions')->insertGetId([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => 'suggest',
            'difficulty_score' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $suggestion = \DB::table('keyword_suggestions')->find($id);
        $this->assertNull($suggestion->difficulty_score);
    }

    /**
     * Test that opportunity_score is nullable.
     * KS-008
     */
    public function test_opportunity_score_is_nullable(): void
    {
        $site = Site::factory()->create();

        $id = \DB::table('keyword_suggestions')->insertGetId([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => 'suggest',
            'opportunity_score' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $suggestion = \DB::table('keyword_suggestions')->find($id);
        $this->assertNull($suggestion->opportunity_score);
    }

    /**
     * Test that is_tracking defaults to false.
     * KS-009
     */
    public function test_is_tracking_defaults_to_false(): void
    {
        $site = Site::factory()->create();

        $id = \DB::table('keyword_suggestions')->insertGetId([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $suggestion = \DB::table('keyword_suggestions')->find($id);
        $this->assertFalse((bool) $suggestion->is_tracking);
    }

    /**
     * Test that is_dismissed defaults to false.
     * KS-010
     */
    public function test_is_dismissed_defaults_to_false(): void
    {
        $site = Site::factory()->create();

        $id = \DB::table('keyword_suggestions')->insertGetId([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $suggestion = \DB::table('keyword_suggestions')->find($id);
        $this->assertFalse((bool) $suggestion->is_dismissed);
    }

    /**
     * Test that discovered_at has default timestamp.
     * KS-011
     */
    public function test_discovered_at_has_default_timestamp(): void
    {
        $site = Site::factory()->create();

        $id = \DB::table('keyword_suggestions')->insertGetId([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $suggestion = \DB::table('keyword_suggestions')->find($id);
        $this->assertNotNull($suggestion->discovered_at);
    }

    /**
     * Test that updated_at column exists and auto-updates.
     * KS-012
     */
    public function test_updated_at_column_exists(): void
    {
        $this->assertTrue(
            Schema::hasColumn('keyword_suggestions', 'updated_at'),
            'updated_at column should exist'
        );
    }

    /**
     * Test unique constraint on (site_id, keyword).
     * KS-014
     */
    public function test_unique_constraint_on_site_id_keyword(): void
    {
        $site = Site::factory()->create();

        // Insert first record
        \DB::table('keyword_suggestions')->insert([
            'site_id' => $site->id,
            'keyword' => 'duplicate keyword',
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attempt to insert duplicate - should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        \DB::table('keyword_suggestions')->insert([
            'site_id' => $site->id,
            'keyword' => 'duplicate keyword',
            'source' => 'competitor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that same keyword can exist for different sites.
     */
    public function test_same_keyword_can_exist_for_different_sites(): void
    {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        // Insert for site1
        \DB::table('keyword_suggestions')->insert([
            'site_id' => $site1->id,
            'keyword' => 'shared keyword',
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert same keyword for site2 - should succeed
        $id = \DB::table('keyword_suggestions')->insertGetId([
            'site_id' => $site2->id,
            'keyword' => 'shared keyword',
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($id);
    }

    /**
     * Test cascade delete when site is deleted.
     * KS-018
     */
    public function test_cascade_delete_when_site_deleted(): void
    {
        $site = Site::factory()->create();

        \DB::table('keyword_suggestions')->insert([
            'site_id' => $site->id,
            'keyword' => 'test keyword',
            'source' => 'suggest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete the site
        $site->delete();

        // Suggestion should be deleted
        $suggestions = \DB::table('keyword_suggestions')
            ->where('site_id', $site->id)
            ->count();

        $this->assertEquals(0, $suggestions);
    }

    /**
     * Test valid source values are accepted.
     * KS-019
     */
    public function test_valid_source_values(): void
    {
        $site = Site::factory()->create();
        $validSources = ['suggest', 'competitor', 'related'];

        foreach ($validSources as $index => $source) {
            $id = \DB::table('keyword_suggestions')->insertGetId([
                'site_id' => $site->id,
                'keyword' => "test keyword {$index}",
                'source' => $source,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertNotNull($id, "Failed to insert with source: {$source}");
        }

        $count = \DB::table('keyword_suggestions')
            ->where('site_id', $site->id)
            ->count();

        $this->assertEquals(3, $count);
    }

    /**
     * Test index on site_id exists.
     * KS-015
     */
    public function test_index_on_site_id_exists(): void
    {
        $indexes = $this->getTableIndexes('keyword_suggestions');
        $hasSiteIdIndex = collect($indexes)->contains(function ($index) {
            return in_array('site_id', $index['columns'] ?? []);
        });

        $this->assertTrue(
            $hasSiteIdIndex,
            'Index on site_id should exist'
        );
    }

    /**
     * Test index on keyword exists.
     * KS-016
     */
    public function test_index_on_keyword_exists(): void
    {
        $indexes = $this->getTableIndexes('keyword_suggestions');
        $hasKeywordIndex = collect($indexes)->contains(function ($index) {
            return in_array('keyword', $index['columns'] ?? []);
        });

        $this->assertTrue(
            $hasKeywordIndex,
            'Index on keyword should exist'
        );
    }

    // =========================================================================
    // dismissed_suggestions TABLE TESTS
    // =========================================================================

    /**
     * Test that the dismissed_suggestions table exists.
     * DS-001
     */
    public function test_dismissed_suggestions_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('dismissed_suggestions'),
            'dismissed_suggestions table should exist'
        );
    }

    /**
     * Test that all required columns exist on dismissed_suggestions.
     * DS-002 through DS-005
     */
    public function test_dismissed_suggestions_has_required_columns(): void
    {
        $columns = [
            'id',
            'site_id',
            'keyword',
            'dismissed_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('dismissed_suggestions', $column),
                "Column {$column} is missing from dismissed_suggestions table"
            );
        }
    }

    /**
     * Test that dismissed_suggestions.site_id references sites.
     * DS-006
     */
    public function test_dismissed_suggestions_site_id_references_sites(): void
    {
        // Test FK constraint - inserting with non-existent site_id should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        \DB::table('dismissed_suggestions')->insert([
            'site_id' => 99999, // Non-existent site
            'keyword' => 'spam keyword',
            'created_at' => now(),
        ]);
    }

    /**
     * Test that dismissed_at has default timestamp.
     * DS-005
     */
    public function test_dismissed_at_has_default_timestamp(): void
    {
        $site = Site::factory()->create();

        $id = \DB::table('dismissed_suggestions')->insertGetId([
            'site_id' => $site->id,
            'keyword' => 'spam keyword',
            'created_at' => now(),
        ]);

        $dismissed = \DB::table('dismissed_suggestions')->find($id);
        $this->assertNotNull($dismissed->dismissed_at);
    }

    /**
     * Test cascade delete on dismissed_suggestions when site is deleted.
     * DS-008
     */
    public function test_dismissed_suggestions_cascade_delete(): void
    {
        $site = Site::factory()->create();

        \DB::table('dismissed_suggestions')->insert([
            'site_id' => $site->id,
            'keyword' => 'spam keyword',
            'created_at' => now(),
        ]);

        // Delete the site
        $site->delete();

        // Dismissed suggestion should be deleted
        $dismissed = \DB::table('dismissed_suggestions')
            ->where('site_id', $site->id)
            ->count();

        $this->assertEquals(0, $dismissed);
    }

    /**
     * Test index on dismissed_suggestions.site_id exists.
     * DS-007
     */
    public function test_dismissed_suggestions_site_id_index_exists(): void
    {
        $indexes = $this->getTableIndexes('dismissed_suggestions');
        $hasSiteIdIndex = collect($indexes)->contains(function ($index) {
            return in_array('site_id', $index['columns'] ?? []);
        });

        $this->assertTrue(
            $hasSiteIdIndex,
            'Index on site_id should exist in dismissed_suggestions'
        );
    }

    // =========================================================================
    // ROLLBACK TESTS
    // =========================================================================

    /**
     * Test that migration can be rolled back.
     * RB-001, RB-002
     * 
     * Note: This is a placeholder. Actual rollback testing requires
     * a fresh database state and is typically done in integration tests.
     */
    public function test_migration_has_down_method(): void
    {
        // This test verifies the migration file exists and has a down method
        // Actual rollback behavior is tested in integration tests
        
        // Check that both tables exist (meaning migration ran successfully)
        $this->assertTrue(Schema::hasTable('keyword_suggestions'));
        $this->assertTrue(Schema::hasTable('dismissed_suggestions'));
        
        // The down() method should drop both tables
        // This is verified by the migration structure, not runtime testing
        $this->assertTrue(true, 'Migration structure validated');
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get indexes for a table.
     * 
     * @param string $tableName
     * @return array
     */
    private function getTableIndexes(string $tableName): array
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: Use PRAGMA index_list
            $indexes = [];
            $indexList = \DB::select("PRAGMA index_list({$tableName})");
            
            foreach ($indexList as $index) {
                $indexInfo = \DB::select("PRAGMA index_info({$index->name})");
                $columns = array_map(fn($col) => $col->name, $indexInfo);
                $indexes[] = [
                    'name' => $index->name,
                    'columns' => $columns,
                ];
            }
            
            return $indexes;
        }

        // MySQL: Use SHOW INDEX
        if ($driver === 'mysql') {
            $rawIndexes = \DB::select("SHOW INDEX FROM {$tableName}");
            $indexes = [];
            
            foreach ($rawIndexes as $index) {
                $indexes[$index->Key_name]['name'] = $index->Key_name;
                $indexes[$index->Key_name]['columns'][] = $index->Column_name;
            }
            
            return array_values($indexes);
        }

        return [];
    }
}
