<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for TASK-25-11: Database Migration - Analysis Costs Table
 *
 * This test validates the migration that creates the analysis_costs table
 * for tracking per-analysis resource costs.
 *
 * Test Categories:
 * - SC-001 to SC-008: Schema Structure
 * - DP-001 to DP-006: Decimal Precision
 * - FK-001 to FK-002: Foreign Key Constraints
 * - IX-001 to IX-002: Indexes
 * - DF-001 to DF-004: Default Values
 * - RB-001 to RB-002: Migration Rollback
 * - CT-001 to CT-005: Cost Type Validation
 * - SEC-001 to SEC-003: Security Tests
 *
 * @see TASK-25-11
 * @see SPEC-025
 * @see FEAT-025
 */
class CreateAnalysisCostsTableTest extends TestCase
{
    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a test user.
     */
    protected function createUser(array $attributes = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => 'Test User',
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Create a test analysis_history record.
     */
    protected function createAnalysisHistory(int $userId, array $attributes = []): int
    {
        return DB::table('analysis_history')->insertGetId(array_merge([
            'user_id' => $userId,
            'tool_name' => 'meta-tag-analyzer',
            'input_url' => 'https://example.com',
            'status' => 'completed',
            'results' => json_encode(['title' => 'Test']),
            'analyzed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Create a test analysis_cost record.
     */
    protected function createAnalysisCost(int $analysisId, array $attributes = []): int
    {
        return DB::table('analysis_costs')->insertGetId(array_merge([
            'analysis_id' => $analysisId,
            'cost_type' => 'compute',
            'cost_units' => 1.0000,
            'cost_currency' => 'USD',
            'cost_amount' => 0.010000,
            'created_at' => now(),
        ], $attributes));
    }

    /**
     * Run the analysis_costs migration.
     */
    protected function runAnalysisCostsMigration(): void
    {
        $this->artisan('migrate', ['--path' => 'database/migrations']);
    }

    /**
     * Rollback the analysis_costs migration.
     */
    protected function rollbackAnalysisCostsMigration(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1]);
    }

    /**
     * Helper: Get table indexes.
     */
    protected function getTableIndexes(string $table): array
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = [];
            $result = DB::select("PRAGMA index_list({$table})");
            foreach ($result as $row) {
                $indexInfo = DB::select("PRAGMA index_info({$row->name})");
                $columns = array_map(fn($col) => $col->name, $indexInfo);
                $indexes[] = [
                    'name' => $row->name,
                    'columns' => $columns,
                ];
            }
            return $indexes;
        }

        // MySQL
        $result = DB::select("SHOW INDEX FROM {$table}");
        $indexes = [];
        foreach ($result as $row) {
            $indexes[$row->Key_name]['name'] = $row->Key_name;
            $indexes[$row->Key_name]['columns'][] = $row->Column_name;
        }

        return array_values($indexes);
    }

    // =========================================================================
    // Category 1: Schema Structure (SC-001 to SC-008)
    // =========================================================================

    /**
     * SC-001: Migration creates analysis_costs table.
     */
    public function test_sc001_migration_creates_analysis_costs_table(): void
    {
        $this->runAnalysisCostsMigration();

        $this->assertTrue(
            Schema::hasTable('analysis_costs'),
            'analysis_costs table should exist after migration'
        );
    }

    /**
     * SC-002: Primary key column is correct.
     */
    public function test_sc002_primary_key_column_is_correct(): void
    {
        $this->runAnalysisCostsMigration();

        $this->assertTrue(
            Schema::hasColumn('analysis_costs', 'id'),
            'id column should exist'
        );

        // Verify auto-increment
        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $id1 = $this->createAnalysisCost($analysisId);
        $id2 = $this->createAnalysisCost($analysisId);

        $this->assertGreaterThan($id1, $id2, 'id should auto-increment');
    }

    /**
     * SC-003: analysis_id column is correct.
     */
    public function test_sc003_analysis_id_column_is_correct(): void
    {
        $this->runAnalysisCostsMigration();

        $this->assertTrue(
            Schema::hasColumn('analysis_costs', 'analysis_id'),
            'analysis_id column should exist'
        );

        // Verify NOT NULL constraint
        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertNotNull($cost->analysis_id, 'analysis_id should not be null');
        $this->assertEquals($analysisId, $cost->analysis_id);
    }

    /**
     * SC-004: cost_type column is correct.
     */
    public function test_sc004_cost_type_column_is_correct(): void
    {
        $this->runAnalysisCostsMigration();

        $this->assertTrue(
            Schema::hasColumn('analysis_costs', 'cost_type'),
            'cost_type column should exist'
        );

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_type' => 'api_call']);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('api_call', $cost->cost_type);
    }

    /**
     * SC-005: cost_units column is correct.
     */
    public function test_sc005_cost_units_column_is_correct(): void
    {
        $this->runAnalysisCostsMigration();

        $this->assertTrue(
            Schema::hasColumn('analysis_costs', 'cost_units'),
            'cost_units column should exist'
        );

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_units' => 123.4567]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('123.4567', $cost->cost_units);
    }

    /**
     * SC-006: cost_currency column is correct.
     */
    public function test_sc006_cost_currency_column_is_correct(): void
    {
        $this->runAnalysisCostsMigration();

        $this->assertTrue(
            Schema::hasColumn('analysis_costs', 'cost_currency'),
            'cost_currency column should exist'
        );

        // Test default value
        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = DB::table('analysis_costs')->insertGetId([
            'analysis_id' => $analysisId,
            'cost_type' => 'compute',
            'cost_units' => 1.0,
            'cost_amount' => 0.01,
            'created_at' => now(),
        ]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('USD', $cost->cost_currency, 'cost_currency should default to USD');

        // Test custom currency
        $costId2 = $this->createAnalysisCost($analysisId, ['cost_currency' => 'EUR']);
        $cost2 = DB::table('analysis_costs')->find($costId2);
        $this->assertEquals('EUR', $cost2->cost_currency);
    }

    /**
     * SC-007: cost_amount column is correct.
     */
    public function test_sc007_cost_amount_column_is_correct(): void
    {
        $this->runAnalysisCostsMigration();

        $this->assertTrue(
            Schema::hasColumn('analysis_costs', 'cost_amount'),
            'cost_amount column should exist'
        );

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_amount' => 0.000001]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('0.000001', $cost->cost_amount);
    }

    /**
     * SC-008: description column is correct.
     */
    public function test_sc008_description_column_is_correct(): void
    {
        $this->runAnalysisCostsMigration();

        $this->assertTrue(
            Schema::hasColumn('analysis_costs', 'description'),
            'description column should exist'
        );

        // Test NULL allowed
        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['description' => null]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertNull($cost->description, 'description should allow NULL');

        // Test with description
        $desc = 'API call to Google Custom Search';
        $costId2 = $this->createAnalysisCost($analysisId, ['description' => $desc]);

        $cost2 = DB::table('analysis_costs')->find($costId2);
        $this->assertEquals($desc, $cost2->description);
    }

    // =========================================================================
    // Category 2: Decimal Precision (DP-001 to DP-006)
    // =========================================================================

    /**
     * DP-001: cost_units precision (4 decimals).
     */
    public function test_dp001_cost_units_precision_4_decimals(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_units' => 123.4567]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('123.4567', $cost->cost_units, 'Should store 4 decimal places exactly');
    }

    /**
     * DP-002: cost_units truncation (5 decimals).
     */
    public function test_dp002_cost_units_truncation_5_decimals(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);

        // MySQL may round or truncate depending on mode
        $costId = $this->createAnalysisCost($analysisId, ['cost_units' => 123.45678]);

        $cost = DB::table('analysis_costs')->find($costId);
        // Should be truncated/rounded to 4 decimals
        $this->assertMatchesRegularExpression('/^123\.456[67]$/', $cost->cost_units);
    }

    /**
     * DP-003: cost_amount precision (6 decimals).
     */
    public function test_dp003_cost_amount_precision_6_decimals(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_amount' => 0.000001]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('0.000001', $cost->cost_amount, 'Should store 6 decimal places exactly');
    }

    /**
     * DP-004: cost_amount large value.
     */
    public function test_dp004_cost_amount_large_value(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_amount' => 999999.999999]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('999999.999999', $cost->cost_amount, 'Should store max value');
    }

    /**
     * DP-005: cost_units zero value.
     */
    public function test_dp005_cost_units_zero_value(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_units' => 0]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('0.0000', $cost->cost_units, 'Should store zero with 4 decimals');
    }

    /**
     * DP-006: cost_amount zero value.
     */
    public function test_dp006_cost_amount_zero_value(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_amount' => 0]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('0.000000', $cost->cost_amount, 'Should store zero with 6 decimals');
    }

    // =========================================================================
    // Category 3: Foreign Key Constraints (FK-001 to FK-002)
    // =========================================================================

    /**
     * FK-001: Foreign key to analysis_history.id.
     */
    public function test_fk001_foreign_key_to_analysis_history_id(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);

        // Insert with valid analysis_id should succeed
        $costId = $this->createAnalysisCost($analysisId);
        $this->assertNotNull($costId, 'Insert with valid analysis_id should succeed');

        // Insert with invalid analysis_id should fail
        $this->expectException(\Exception::class);
        $this->createAnalysisCost(999999);
    }

    /**
     * FK-002: Cascade delete on analysis delete.
     */
    public function test_fk002_cascade_delete_on_analysis_delete(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId);

        // Verify record exists
        $this->assertDatabaseHas('analysis_costs', ['id' => $costId]);

        // Delete analysis
        DB::table('analysis_history')->where('id', $analysisId)->delete();

        // Verify cost was cascade deleted
        $this->assertDatabaseMissing('analysis_costs', ['id' => $costId]);
    }

    // =========================================================================
    // Category 4: Indexes (IX-001 to IX-002)
    // =========================================================================

    /**
     * IX-001: Index on analysis_id.
     */
    public function test_ix001_index_on_analysis_id(): void
    {
        $this->runAnalysisCostsMigration();

        $indexes = $this->getTableIndexes('analysis_costs');

        $found = false;
        foreach ($indexes as $index) {
            if (in_array('analysis_id', $index['columns'])) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Index on analysis_id should exist'
        );
    }

    /**
     * IX-002: Index on cost_type.
     */
    public function test_ix002_index_on_cost_type(): void
    {
        $this->runAnalysisCostsMigration();

        $indexes = $this->getTableIndexes('analysis_costs');

        $found = false;
        foreach ($indexes as $index) {
            if (in_array('cost_type', $index['columns'])) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Index on cost_type should exist'
        );
    }

    // =========================================================================
    // Category 5: Default Values (DF-001 to DF-004)
    // =========================================================================

    /**
     * DF-001: Default cost_units is 0.
     */
    public function test_df001_default_cost_units_is_0(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = DB::table('analysis_costs')->insertGetId([
            'analysis_id' => $analysisId,
            'cost_type' => 'compute',
            'cost_amount' => 0.01,
            'created_at' => now(),
        ]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('0.0000', $cost->cost_units, 'cost_units should default to 0');
    }

    /**
     * DF-002: Default cost_currency is USD.
     */
    public function test_df002_default_cost_currency_is_usd(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = DB::table('analysis_costs')->insertGetId([
            'analysis_id' => $analysisId,
            'cost_type' => 'compute',
            'cost_units' => 1.0,
            'cost_amount' => 0.01,
            'created_at' => now(),
        ]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('USD', $cost->cost_currency, 'cost_currency should default to USD');
    }

    /**
     * DF-003: Default cost_amount is 0.
     */
    public function test_df003_default_cost_amount_is_0(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = DB::table('analysis_costs')->insertGetId([
            'analysis_id' => $analysisId,
            'cost_type' => 'compute',
            'cost_units' => 1.0,
            'created_at' => now(),
        ]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals('0.000000', $cost->cost_amount, 'cost_amount should default to 0');
    }

    /**
     * DF-004: Default created_at is set.
     */
    public function test_df004_default_created_at_is_set(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertNotNull($cost->created_at, 'created_at should be set');
    }

    // =========================================================================
    // Category 6: Migration Rollback (RB-001 to RB-002)
    // =========================================================================

    /**
     * RB-001: Migration down drops table.
     */
    public function test_rb001_migration_down_drops_table(): void
    {
        $this->runAnalysisCostsMigration();

        $this->assertTrue(Schema::hasTable('analysis_costs'), 'Table should exist after migration');

        $this->rollbackAnalysisCostsMigration();

        $this->assertFalse(
            Schema::hasTable('analysis_costs'),
            'Table should not exist after rollback'
        );
    }

    /**
     * RB-002: Migration re-run after rollback.
     */
    public function test_rb002_migration_rerun_after_rollback(): void
    {
        // Run migration
        $this->runAnalysisCostsMigration();
        $this->assertTrue(Schema::hasTable('analysis_costs'));

        // Rollback
        $this->rollbackAnalysisCostsMigration();
        $this->assertFalse(Schema::hasTable('analysis_costs'));

        // Re-run migration
        $this->runAnalysisCostsMigration();
        $this->assertTrue(
            Schema::hasTable('analysis_costs'),
            'Table should exist after re-run'
        );

        // Verify schema is correct
        $this->assertTrue(Schema::hasColumn('analysis_costs', 'id'));
        $this->assertTrue(Schema::hasColumn('analysis_costs', 'analysis_id'));
        $this->assertTrue(Schema::hasColumn('analysis_costs', 'cost_type'));
        $this->assertTrue(Schema::hasColumn('analysis_costs', 'cost_amount'));
    }

    // =========================================================================
    // Category 7: Cost Type Validation (CT-001 to CT-005)
    // =========================================================================

    /**
     * CT-001: Valid cost_type: api_call.
     */
    public function test_ct001_valid_cost_type_api_call(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_type' => 'api_call']);

        $this->assertNotNull($costId, 'api_call should be valid cost_type');
    }

    /**
     * CT-002: Valid cost_type: compute.
     */
    public function test_ct002_valid_cost_type_compute(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_type' => 'compute']);

        $this->assertNotNull($costId, 'compute should be valid cost_type');
    }

    /**
     * CT-003: Valid cost_type: storage.
     */
    public function test_ct003_valid_cost_type_storage(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_type' => 'storage']);

        $this->assertNotNull($costId, 'storage should be valid cost_type');
    }

    /**
     * CT-004: Valid cost_type: variable.
     */
    public function test_ct004_valid_cost_type_variable(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_type' => 'variable']);

        $this->assertNotNull($costId, 'variable should be valid cost_type');
    }

    /**
     * CT-005: Custom cost_type allowed.
     */
    public function test_ct005_custom_cost_type_allowed(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $costId = $this->createAnalysisCost($analysisId, ['cost_type' => 'custom_type']);

        $this->assertNotNull($costId, 'Custom cost_type should be allowed (VARCHAR, not enum)');
    }

    // =========================================================================
    // Category 8: Security Tests (SEC-001 to SEC-003)
    // =========================================================================

    /**
     * SEC-001: SQL injection prevention in description.
     */
    public function test_sec001_sql_injection_prevention(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $maliciousDesc = "'; DROP TABLE analysis_costs; --";

        $costId = $this->createAnalysisCost($analysisId, ['description' => $maliciousDesc]);

        $cost = DB::table('analysis_costs')->find($costId);
        $this->assertEquals($maliciousDesc, $cost->description);

        // Table should still exist
        $this->assertTrue(Schema::hasTable('analysis_costs'));
    }

    /**
     * SEC-002: XSS prevention in description.
     */
    public function test_sec002_xss_prevention_in_description(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);
        $xssDesc = '<script>alert("xss")</script>';

        $costId = $this->createAnalysisCost($analysisId, ['description' => $xssDesc]);

        $cost = DB::table('analysis_costs')->find($costId);
        // Script tags should be stored as strings, not executed
        $this->assertEquals($xssDesc, $cost->description);
    }

    /**
     * SEC-003: Negative cost_amount.
     */
    public function test_sec003_negative_cost_amount(): void
    {
        $this->runAnalysisCostsMigration();

        $userId = $this->createUser();
        $analysisId = $this->createAnalysisHistory($userId);

        // Negative amounts should be accepted (for credits/refunds)
        // Or rejected if CHECK constraint exists
        $costId = $this->createAnalysisCost($analysisId, ['cost_amount' => -1.000000]);

        $cost = DB::table('analysis_costs')->find($costId);
        // DECIMAL type accepts negative values unless CHECK constraint added
        $this->assertNotNull($cost, 'Negative cost_amount stored');
    }
}
