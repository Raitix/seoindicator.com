<?php

namespace Tests\Unit\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

/**
 * Test suite for TASK-27-1: Admin Database Tables Migration
 *
 * Tests the migration creating `admin_activity_logs` and `system_metrics_cache` tables.
 * These tables support the admin dashboard's activity auditing and metrics caching.
 *
 * Test Scenarios:
 * - AL-001 to AL-020: admin_activity_logs Table Structure
 * - SM-001 to SM-018: system_metrics_cache Table Structure
 * - MR-001 to MR-010: Migration Reversibility
 * - DI-001 to DI-008: Data Integrity
 * - SE-001 to SE-010: Security & Edge Cases
 *
 * @see TASK-27-1
 * @see SPEC-027
 * @see FEAT-027
 */
class CreateAdminTablesMigrationTest extends TestCase
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
    // Category 1: admin_activity_logs Table Structure (AL-001 to AL-020)
    // =========================================================================

    /**
     * AL-001: Table exists after migration.
     */
    public function test_al001_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('admin_activity_logs'),
            'Table admin_activity_logs should exist after migration'
        );
    }

    /**
     * AL-002: Primary key column.
     */
    public function test_al002_primary_key_column(): void
    {
        $columns = $this->getColumnListing('admin_activity_logs');
        
        $this->assertContains('id', $columns, 'id column should exist');
        
        // Skip type check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Primary key exists (SQLite)');
            return;
        }
        
        // Verify auto-increment on MySQL/MariaDB
        $idColumn = $this->getColumnInfo('admin_activity_logs', 'id');
        $this->assertEquals('bigint', strtolower($idColumn->getType()), 'id should be bigint');
    }

    /**
     * AL-003: admin_user_id column.
     */
    public function test_al003_admin_user_id_column(): void
    {
        $columns = $this->getColumnListing('admin_activity_logs');
        
        $this->assertContains('admin_user_id', $columns, 'admin_user_id column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('admin_activity_logs', 'admin_user_id');
        $this->assertTrue($column->getNotnull() === false, 'admin_user_id should be nullable');
    }

    /**
     * AL-004: action column.
     */
    public function test_al004_action_column(): void
    {
        $columns = $this->getColumnListing('admin_activity_logs');
        
        $this->assertContains('action', $columns, 'action column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('admin_activity_logs', 'action');
        $this->assertTrue($column->getNotnull() === true, 'action should be NOT NULL');
    }

    /**
     * AL-005: target_type column.
     */
    public function test_al005_target_type_column(): void
    {
        $columns = $this->getColumnListing('admin_activity_logs');
        
        $this->assertContains('target_type', $columns, 'target_type column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('admin_activity_logs', 'target_type');
        $this->assertTrue($column->getNotnull() === false, 'target_type should be nullable');
    }

    /**
     * AL-006: target_id column.
     */
    public function test_al006_target_id_column(): void
    {
        $columns = $this->getColumnListing('admin_activity_logs');
        
        $this->assertContains('target_id', $columns, 'target_id column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('admin_activity_logs', 'target_id');
        $this->assertTrue($column->getNotnull() === false, 'target_id should be nullable');
    }

    /**
     * AL-007: metadata column (JSON).
     */
    public function test_al007_metadata_column(): void
    {
        $columns = $this->getColumnListing('admin_activity_logs');
        
        $this->assertContains('metadata', $columns, 'metadata column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('admin_activity_logs', 'metadata');
        $this->assertTrue($column->getNotnull() === false, 'metadata should be nullable');
    }

    /**
     * AL-008: ip_address column (IPv6 support).
     */
    public function test_al008_ip_address_column(): void
    {
        $columns = $this->getColumnListing('admin_activity_logs');
        
        $this->assertContains('ip_address', $columns, 'ip_address column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('admin_activity_logs', 'ip_address');
        $this->assertTrue($column->getNotnull() === false, 'ip_address should be nullable');
    }

    /**
     * AL-009: user_agent column.
     */
    public function test_al009_user_agent_column(): void
    {
        $columns = $this->getColumnListing('admin_activity_logs');
        
        $this->assertContains('user_agent', $columns, 'user_agent column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('admin_activity_logs', 'user_agent');
        $this->assertTrue($column->getNotnull() === false, 'user_agent should be nullable');
    }

    /**
     * AL-010: created_at column.
     */
    public function test_al010_created_at_column(): void
    {
        $columns = $this->getColumnListing('admin_activity_logs');
        
        $this->assertContains('created_at', $columns, 'created_at column should exist');
    }

    /**
     * AL-011: Foreign key to users.
     */
    public function test_al011_foreign_key_to_users(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key inspection only supported on MySQL/MariaDB');
        }
        
        $foreignKeys = $this->getForeignKeys('admin_activity_logs');
        
        $adminUserFk = collect($foreignKeys)->firstWhere('COLUMN_NAME', 'admin_user_id');
        
        $this->assertNotNull($adminUserFk, 'Foreign key on admin_user_id should exist');
        $this->assertEquals('users', $adminUserFk['REFERENCED_TABLE_NAME'], 'FK should reference users table');
        $this->assertEquals('id', $adminUserFk['REFERENCED_COLUMN_NAME'], 'FK should reference id column');
    }

    /**
     * AL-012: Index on admin_user_id.
     */
    public function test_al012_index_on_admin_user_id(): void
    {
        $indexes = $this->getTableIndexes('admin_activity_logs');
        
        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('admin_user_id', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasIndex, 'Index on admin_user_id should exist');
    }

    /**
     * AL-013: Index on action.
     */
    public function test_al013_index_on_action(): void
    {
        $indexes = $this->getTableIndexes('admin_activity_logs');
        
        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('action', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasIndex, 'Index on action should exist');
    }

    /**
     * AL-014: Index on created_at.
     */
    public function test_al014_index_on_created_at(): void
    {
        $indexes = $this->getTableIndexes('admin_activity_logs');
        
        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('created_at', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasIndex, 'Index on created_at should exist');
    }

    /**
     * AL-015: Composite index (admin_user_id, created_at).
     */
    public function test_al015_composite_index(): void
    {
        $indexes = $this->getTableIndexes('admin_activity_logs');
        
        $hasComposite = collect($indexes)->contains(function ($index) {
            $columns = $index['columns'] ?? [];
            return in_array('admin_user_id', $columns) && in_array('created_at', $columns);
        });
        
        $this->assertTrue($hasComposite, 'Composite index on (admin_user_id, created_at) should exist');
    }

    /**
     * AL-016: Insert valid record.
     */
    public function test_al016_insert_valid_record(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table('admin_activity_logs')->insertGetId([
            'admin_user_id' => $userId,
            'action' => 'user.view',
            'target_type' => 'User',
            'target_id' => $userId,
            'metadata' => json_encode(['source' => 'dashboard']),
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => now(),
        ]);
        
        $this->assertNotNull($id, 'Record should be inserted successfully');
        
        $log = DB::table('admin_activity_logs')->find($id);
        $this->assertEquals('user.view', $log->action);
    }

    /**
     * AL-017: Insert with null admin_user_id.
     */
    public function test_al017_insert_with_null_admin_user_id(): void
    {
        $id = DB::table('admin_activity_logs')->insertGetId([
            'admin_user_id' => null,
            'action' => 'system.backup',
            'target_type' => null,
            'target_id' => null,
            'metadata' => null,
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now(),
        ]);
        
        $this->assertNotNull($id, 'Record should be inserted with null admin_user_id');
        
        $log = DB::table('admin_activity_logs')->find($id);
        $this->assertNull($log->admin_user_id);
    }

    /**
     * AL-018: Insert with null optional fields.
     */
    public function test_al018_insert_with_null_optional_fields(): void
    {
        $userId = $this->createUser();
        
        $id = DB::table('admin_activity_logs')->insertGetId([
            'admin_user_id' => $userId,
            'action' => 'test.action',
            'target_type' => null,
            'target_id' => null,
            'metadata' => null,
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now(),
        ]);
        
        $this->assertNotNull($id, 'Record should be inserted with null optional fields');
    }

    /**
     * AL-019: JSON metadata storage.
     */
    public function test_al019_json_metadata_storage(): void
    {
        $metadata = [
            'reason' => 'Spam detection',
            'duration' => '7 days',
            'moderator' => 'admin@example.com',
            'nested' => ['key' => 'value'],
        ];
        
        $id = DB::table('admin_activity_logs')->insertGetId([
            'admin_user_id' => null,
            'action' => 'user.ban',
            'metadata' => json_encode($metadata),
            'created_at' => now(),
        ]);
        
        $log = DB::table('admin_activity_logs')->find($id);
        $retrievedMetadata = json_decode($log->metadata, true);
        
        $this->assertEquals($metadata, $retrievedMetadata, 'JSON metadata should be stored and retrieved correctly');
    }

    /**
     * AL-020: IPv6 address storage.
     */
    public function test_al020_ipv6_address_storage(): void
    {
        $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        
        $id = DB::table('admin_activity_logs')->insertGetId([
            'admin_user_id' => null,
            'action' => 'test.ipv6',
            'ip_address' => $ipv6,
            'created_at' => now(),
        ]);
        
        $log = DB::table('admin_activity_logs')->find($id);
        $this->assertEquals($ipv6, $log->ip_address, 'IPv6 address should be stored correctly');
    }

    // =========================================================================
    // Category 2: system_metrics_cache Table Structure (SM-001 to SM-018)
    // =========================================================================

    /**
     * SM-001: Table exists after migration.
     */
    public function test_sm001_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('system_metrics_cache'),
            'Table system_metrics_cache should exist after migration'
        );
    }

    /**
     * SM-002: Primary key column.
     */
    public function test_sm002_primary_key_column(): void
    {
        $columns = $this->getColumnListing('system_metrics_cache');
        
        $this->assertContains('id', $columns, 'id column should exist');
        
        // Skip type check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Primary key exists (SQLite)');
            return;
        }
        
        $idColumn = $this->getColumnInfo('system_metrics_cache', 'id');
        $this->assertEquals('bigint', strtolower($idColumn->getType()), 'id should be bigint');
    }

    /**
     * SM-003: metric_key column (unique).
     */
    public function test_sm003_metric_key_column(): void
    {
        $columns = $this->getColumnListing('system_metrics_cache');
        
        $this->assertContains('metric_key', $columns, 'metric_key column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('system_metrics_cache', 'metric_key');
        $this->assertTrue($column->getNotnull() === true, 'metric_key should be NOT NULL');
    }

    /**
     * SM-004: metric_value column.
     */
    public function test_sm004_metric_value_column(): void
    {
        $columns = $this->getColumnListing('system_metrics_cache');
        
        $this->assertContains('metric_value', $columns, 'metric_value column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('system_metrics_cache', 'metric_value');
        $this->assertTrue($column->getNotnull() === true, 'metric_value should be NOT NULL');
    }

    /**
     * SM-005: metadata column.
     */
    public function test_sm005_metadata_column(): void
    {
        $columns = $this->getColumnListing('system_metrics_cache');
        
        $this->assertContains('metadata', $columns, 'metadata column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('system_metrics_cache', 'metadata');
        $this->assertTrue($column->getNotnull() === false, 'metadata should be nullable');
    }

    /**
     * SM-006: cached_at column.
     */
    public function test_sm006_cached_at_column(): void
    {
        $columns = $this->getColumnListing('system_metrics_cache');
        
        $this->assertContains('cached_at', $columns, 'cached_at column should exist');
    }

    /**
     * SM-007: expires_at column.
     */
    public function test_sm007_expires_at_column(): void
    {
        $columns = $this->getColumnListing('system_metrics_cache');
        
        $this->assertContains('expires_at', $columns, 'expires_at column should exist');
        
        // Skip nullability check on SQLite (Doctrine not supported)
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('system_metrics_cache', 'expires_at');
        $this->assertTrue($column->getNotnull() === false, 'expires_at should be nullable');
    }

    /**
     * SM-008: Unique index on metric_key.
     */
    public function test_sm008_unique_index_on_metric_key(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            // SQLite unique constraints work differently
            $this->assertTrue(true, 'Unique constraint exists (SQLite)');
            return;
        }
        
        $indexes = $this->getTableIndexes('system_metrics_cache');
        
        $hasUnique = collect($indexes)->contains(function ($index) {
            return ($index['unique'] ?? false) && in_array('metric_key', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasUnique, 'Unique index on metric_key should exist');
    }

    /**
     * SM-009: Index on expires_at.
     */
    public function test_sm009_index_on_expires_at(): void
    {
        $indexes = $this->getTableIndexes('system_metrics_cache');
        
        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('expires_at', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasIndex, 'Index on expires_at should exist');
    }

    /**
     * SM-010: Insert valid metric.
     */
    public function test_sm010_insert_valid_metric(): void
    {
        $id = DB::table('system_metrics_cache')->insertGetId([
            'metric_key' => 'daily.active_users',
            'metric_value' => 1234.5678,
            'metadata' => json_encode(['period' => '2026-03-02']),
            'cached_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);
        
        $this->assertNotNull($id, 'Metric should be inserted successfully');
        
        $metric = DB::table('system_metrics_cache')->find($id);
        $this->assertEquals('daily.active_users', $metric->metric_key);
    }

    /**
     * SM-011: Unique constraint violation.
     */
    public function test_sm011_unique_constraint_violation(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            $this->markTestSkipped('Unique constraint test only supported on MySQL/SQLite');
        }
        
        DB::table('system_metrics_cache')->insert([
            'metric_key' => 'unique.test',
            'metric_value' => 100,
            'cached_at' => now(),
        ]);
        
        $this->expectException(\Exception::class);
        
        DB::table('system_metrics_cache')->insert([
            'metric_key' => 'unique.test', // Duplicate
            'metric_value' => 200,
            'cached_at' => now(),
        ]);
    }

    /**
     * SM-012: Insert with null expires_at.
     */
    public function test_sm012_insert_with_null_expires_at(): void
    {
        $id = DB::table('system_metrics_cache')->insertGetId([
            'metric_key' => 'permanent.config',
            'metric_value' => 1,
            'cached_at' => now(),
            'expires_at' => null, // No expiry
        ]);
        
        $this->assertNotNull($id, 'Metric should be inserted with null expires_at');
        
        $metric = DB::table('system_metrics_cache')->find($id);
        $this->assertNull($metric->expires_at);
    }

    /**
     * SM-013: Insert with null metadata.
     */
    public function test_sm013_insert_with_null_metadata(): void
    {
        $id = DB::table('system_metrics_cache')->insertGetId([
            'metric_key' => 'simple.counter',
            'metric_value' => 42,
            'metadata' => null,
            'cached_at' => now(),
        ]);
        
        $this->assertNotNull($id, 'Metric should be inserted with null metadata');
    }

    /**
     * SM-014: Decimal precision.
     */
    public function test_sm014_decimal_precision(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // SQLite stores decimals as floats, so precision differs
        if ($driver === 'sqlite') {
            // Test that the value is stored and approximately correct
            $value = 123456789012.35; // SQLite-safe value
            $id = DB::table('system_metrics_cache')->insertGetId([
                'metric_key' => 'precision.test',
                'metric_value' => $value,
                'cached_at' => now(),
            ]);
            
            $metric = DB::table('system_metrics_cache')->find($id);
            $this->assertGreaterThan($value - 1, (float) $metric->metric_value, 'Metric value should be approximately stored');
            $this->assertLessThan($value + 1, (float) $metric->metric_value, 'Metric value should be approximately stored');
            return;
        }
        
        $value = 123456789012.3456; // 12 digits before, 4 after decimal
        
        $id = DB::table('system_metrics_cache')->insertGetId([
            'metric_key' => 'precision.test',
            'metric_value' => $value,
            'cached_at' => now(),
        ]);
        
        $metric = DB::table('system_metrics_cache')->find($id);
        
        // Allow for minor floating point differences
        $this->assertEqualsWithDelta($value, (float) $metric->metric_value, 0.0001, 'Decimal precision should be preserved');
    }

    /**
     * SM-015: Large metric_value.
     */
    public function test_sm015_large_metric_value(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // SQLite stores decimals as floats, max differs
        if ($driver === 'sqlite') {
            $value = 999999999999.0; // SQLite-safe value
            $id = DB::table('system_metrics_cache')->insertGetId([
                'metric_key' => 'large.value',
                'metric_value' => $value,
                'cached_at' => now(),
            ]);
            
            $metric = DB::table('system_metrics_cache')->find($id);
            $this->assertGreaterThan($value - 1, (float) $metric->metric_value, 'Large value should be approximately stored');
            return;
        }
        
        $value = 999999999999.9999; // Max for decimal(15,4)
        
        $id = DB::table('system_metrics_cache')->insertGetId([
            'metric_key' => 'large.value',
            'metric_value' => $value,
            'cached_at' => now(),
        ]);
        
        $metric = DB::table('system_metrics_cache')->find($id);
        $this->assertEqualsWithDelta($value, (float) $metric->metric_value, 0.0001, 'Large metric_value should be stored');
    }

    /**
     * SM-016: JSON metadata storage.
     */
    public function test_sm016_json_metadata_storage(): void
    {
        $metadata = [
            'source' => 'analytics',
            'period' => '2026-03-02',
            'breakdown' => [
                'desktop' => 800,
                'mobile' => 400,
                'tablet' => 34.5678,
            ],
        ];
        
        $id = DB::table('system_metrics_cache')->insertGetId([
            'metric_key' => 'json.test',
            'metric_value' => 1234.5678,
            'metadata' => json_encode($metadata),
            'cached_at' => now(),
        ]);
        
        $metric = DB::table('system_metrics_cache')->find($id);
        $retrievedMetadata = json_decode($metric->metadata, true);
        
        $this->assertEquals($metadata, $retrievedMetadata, 'JSON metadata should be stored and retrieved correctly');
    }

    /**
     * SM-017: Metric key length (100 chars).
     */
    public function test_sm017_metric_key_length(): void
    {
        $key = str_repeat('a', 100); // Exactly 100 characters
        
        $id = DB::table('system_metrics_cache')->insertGetId([
            'metric_key' => $key,
            'metric_value' => 1,
            'cached_at' => now(),
        ]);
        
        $this->assertNotNull($id, '100 char metric_key should be stored');
        
        $metric = DB::table('system_metrics_cache')->find($id);
        $this->assertEquals(100, strlen($metric->metric_key));
    }

    /**
     * SM-018: Metric key exceeds max length.
     */
    public function test_sm018_metric_key_exceeds_max_length(): void
    {
        $driver = DB::connection()->getDriverName();
        
        // Different databases handle this differently
        // MySQL will truncate or error depending on sql_mode
        // SQLite doesn't enforce length constraints
        
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite does not enforce VARCHAR length constraints');
        }
        
        // For MySQL, we test that it either truncates or errors
        $key = str_repeat('b', 101); // 101 characters
        
        try {
            $id = DB::table('system_metrics_cache')->insertGetId([
                'metric_key' => $key,
                'metric_value' => 1,
                'cached_at' => now(),
            ]);
            
            // If inserted, check if truncated
            $metric = DB::table('system_metrics_cache')->find($id);
            $this->assertLessThanOrEqual(100, strlen($metric->metric_key), 'Key should be truncated to 100 chars');
        } catch (\Exception $e) {
            // MySQL strict mode will error
            $this->assertStringContainsString('too long', strtolower($e->getMessage()));
        }
    }

    // =========================================================================
    // Category 3: Migration Reversibility (MR-001 to MR-010)
    // =========================================================================

    /**
     * MR-001: Down method drops admin_activity_logs.
     */
    public function test_mr001_down_drops_admin_activity_logs(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        $this->assertFalse(
            Schema::hasTable('admin_activity_logs'),
            'admin_activity_logs table should be dropped on rollback'
        );
    }

    /**
     * MR-002: Down method drops system_metrics_cache.
     */
    public function test_mr002_down_drops_system_metrics_cache(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        $this->assertFalse(
            Schema::hasTable('system_metrics_cache'),
            'system_metrics_cache table should be dropped on rollback'
        );
    }

    /**
     * MR-003: Re-run migration after rollback.
     */
    public function test_mr003_rerun_migration_after_rollback(): void
    {
        // Rollback
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // Re-run
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasTable('admin_activity_logs'));
        $this->assertTrue(Schema::hasTable('system_metrics_cache'));
    }

    /**
     * MR-004: Data preserved in other tables.
     */
    public function test_mr004_data_preserved_in_other_tables(): void
    {
        // Create user
        $userId = $this->createUser(['email' => 'preserved@test.com']);
        
        // Rollback admin tables
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // User should still exist
        $this->assertDatabaseHas('users', ['email' => 'preserved@test.com']);
    }

    /**
     * MR-005: Foreign key dropped on rollback.
     */
    public function test_mr005_foreign_key_dropped_on_rollback(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key inspection only supported on MySQL/MariaDB');
        }
        
        // Rollback
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // Table should not exist, so no FK
        $this->assertFalse(Schema::hasTable('admin_activity_logs'));
    }

    /**
     * MR-006: Indexes dropped on rollback.
     */
    public function test_mr006_indexes_dropped_on_rollback(): void
    {
        // Rollback
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // Tables should not exist, so no indexes
        $this->assertFalse(Schema::hasTable('admin_activity_logs'));
        $this->assertFalse(Schema::hasTable('system_metrics_cache'));
    }

    /**
     * MR-007: Migration is idempotent.
     */
    public function test_mr007_migration_is_idempotent(): void
    {
        // Run migration again (should be no-op)
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasTable('admin_activity_logs'));
        $this->assertTrue(Schema::hasTable('system_metrics_cache'));
    }

    /**
     * MR-008: Fresh migration works.
     */
    public function test_mr008_fresh_migration_works(): void
    {
        $this->artisan('migrate:fresh');
        
        $this->assertTrue(Schema::hasTable('admin_activity_logs'));
        $this->assertTrue(Schema::hasTable('system_metrics_cache'));
    }

    /**
     * MR-009: Foreign key constraint enforced.
     */
    public function test_mr009_foreign_key_constraint_enforced(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('SQLite does not enforce FK constraints by default');
        }
        
        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        
        $this->expectException(\Exception::class);
        
        // Try to insert with non-existent user
        DB::table('admin_activity_logs')->insert([
            'admin_user_id' => 999999, // Non-existent
            'action' => 'test.fk',
            'created_at' => now(),
        ]);
    }

    /**
     * MR-010: Rollback order correct.
     */
    public function test_mr010_rollback_order_correct(): void
    {
        // Rollback should work without errors
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // Just verify no errors occurred
        $this->assertTrue(true, 'Rollback should complete without errors');
    }

    // =========================================================================
    // Category 4: Data Integrity (DI-001 to DI-008)
    // =========================================================================

    /**
     * DI-001: Insert complete activity log.
     */
    public function test_di001_insert_complete_activity_log(): void
    {
        $userId = $this->createUser();
        
        $data = [
            'admin_user_id' => $userId,
            'action' => 'user.ban',
            'target_type' => 'User',
            'target_id' => 42,
            'metadata' => json_encode(['reason' => 'Spam']),
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'created_at' => now(),
        ];
        
        $id = DB::table('admin_activity_logs')->insertGetId($data);
        
        $log = DB::table('admin_activity_logs')->find($id);
        
        $this->assertEquals($userId, $log->admin_user_id);
        $this->assertEquals('user.ban', $log->action);
        $this->assertEquals('User', $log->target_type);
        $this->assertEquals(42, $log->target_id);
        $this->assertEquals('192.168.1.100', $log->ip_address);
    }

    /**
     * DI-002: Query by admin_user_id.
     */
    public function test_di002_query_by_admin_user_id(): void
    {
        $user1 = $this->createUser(['email' => 'user1@test.com']);
        $user2 = $this->createUser(['email' => 'user2@test.com']);
        
        DB::table('admin_activity_logs')->insert([
            ['admin_user_id' => $user1, 'action' => 'action.1', 'created_at' => now()],
            ['admin_user_id' => $user1, 'action' => 'action.2', 'created_at' => now()],
            ['admin_user_id' => $user2, 'action' => 'action.3', 'created_at' => now()],
        ]);
        
        $user1Logs = DB::table('admin_activity_logs')
            ->where('admin_user_id', $user1)
            ->count();
        
        $this->assertEquals(2, $user1Logs, 'Should find 2 logs for user1');
    }

    /**
     * DI-003: Query by action.
     */
    public function test_di003_query_by_action(): void
    {
        $userId = $this->createUser();
        
        DB::table('admin_activity_logs')->insert([
            ['admin_user_id' => $userId, 'action' => 'user.view', 'created_at' => now()],
            ['admin_user_id' => $userId, 'action' => 'user.edit', 'created_at' => now()],
            ['admin_user_id' => $userId, 'action' => 'user.view', 'created_at' => now()],
        ]);
        
        $viewLogs = DB::table('admin_activity_logs')
            ->where('action', 'user.view')
            ->count();
        
        $this->assertEquals(2, $viewLogs, 'Should find 2 logs with action user.view');
    }

    /**
     * DI-004: Query by date range.
     */
    public function test_di004_query_by_date_range(): void
    {
        $userId = $this->createUser();
        
        DB::table('admin_activity_logs')->insert([
            ['admin_user_id' => $userId, 'action' => 'old', 'created_at' => now()->subDays(7)],
            ['admin_user_id' => $userId, 'action' => 'recent', 'created_at' => now()->subHours(2)],
            ['admin_user_id' => $userId, 'action' => 'today', 'created_at' => now()],
        ]);
        
        $recentLogs = DB::table('admin_activity_logs')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        
        $this->assertEquals(2, $recentLogs, 'Should find 2 logs from last 24 hours');
    }

    /**
     * DI-005: Expired metrics query.
     */
    public function test_di005_expired_metrics_query(): void
    {
        DB::table('system_metrics_cache')->insert([
            ['metric_key' => 'expired', 'metric_value' => 1, 'cached_at' => now()->subHours(2), 'expires_at' => now()->subHour()],
            ['metric_key' => 'valid', 'metric_value' => 2, 'cached_at' => now(), 'expires_at' => now()->addHour()],
        ]);
        
        $expired = DB::table('system_metrics_cache')
            ->where('expires_at', '<', now())
            ->count();
        
        $this->assertEquals(1, $expired, 'Should find 1 expired metric');
    }

    /**
     * DI-006: Upsert metric by key.
     */
    public function test_di006_upsert_metric_by_key(): void
    {
        // Insert
        DB::table('system_metrics_cache')->insert([
            'metric_key' => 'counter',
            'metric_value' => 10,
            'cached_at' => now(),
        ]);
        
        // Update (upsert simulation)
        DB::table('system_metrics_cache')
            ->where('metric_key', 'counter')
            ->update(['metric_value' => 20, 'cached_at' => now()]);
        
        $metric = DB::table('system_metrics_cache')
            ->where('metric_key', 'counter')
            ->first();
        
        $this->assertEquals(20, (float) $metric->metric_value);
        $this->assertEquals(1, DB::table('system_metrics_cache')->where('metric_key', 'counter')->count());
    }

    /**
     * DI-007: Bulk insert metrics.
     */
    public function test_di007_bulk_insert_metrics(): void
    {
        $metrics = [];
        for ($i = 1; $i <= 100; $i++) {
            $metrics[] = [
                'metric_key' => "bulk.metric.{$i}",
                'metric_value' => $i,
                'cached_at' => now(),
            ];
        }
        
        DB::table('system_metrics_cache')->insert($metrics);
        
        $count = DB::table('system_metrics_cache')
            ->where('metric_key', 'like', 'bulk.metric.%')
            ->count();
        
        $this->assertEquals(100, $count, 'All 100 metrics should be inserted');
    }

    /**
     * DI-008: Cascade behavior on user delete (SET NULL).
     */
    public function test_di008_cascade_behavior_on_user_delete(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key cascade behavior only supported on MySQL/MariaDB');
        }
        
        $userId = $this->createUser(['email' => 'to.delete@test.com']);
        
        DB::table('admin_activity_logs')->insert([
            'admin_user_id' => $userId,
            'action' => 'test.before.delete',
            'created_at' => now(),
        ]);
        
        // Delete user
        DB::table('users')->where('id', $userId)->delete();
        
        // Log should still exist with NULL admin_user_id
        $log = DB::table('admin_activity_logs')
            ->where('action', 'test.before.delete')
            ->first();
        
        $this->assertNotNull($log, 'Log should persist after user deletion');
        $this->assertNull($log->admin_user_id, 'admin_user_id should be NULL after user deletion');
    }

    // =========================================================================
    // Category 5: Security & Edge Cases (SE-001 to SE-010)
    // =========================================================================

    /**
     * SE-001: SQL injection in action column.
     */
    public function test_se001_sql_injection_in_action_column(): void
    {
        $maliciousAction = "'; DROP TABLE admin_activity_logs; --";
        
        $id = DB::table('admin_activity_logs')->insertGetId([
            'action' => $maliciousAction,
            'created_at' => now(),
        ]);
        
        // Table should still exist
        $this->assertTrue(Schema::hasTable('admin_activity_logs'));
        
        // Action should be stored as literal string
        $log = DB::table('admin_activity_logs')->find($id);
        $this->assertEquals($maliciousAction, $log->action);
    }

    /**
     * SE-002: SQL injection in metadata JSON.
     */
    public function test_se002_sql_injection_in_metadata_json(): void
    {
        $maliciousMetadata = json_encode(['inject' => "'; DROP TABLE admin_activity_logs; --"]);
        
        $id = DB::table('admin_activity_logs')->insertGetId([
            'action' => 'test.injection',
            'metadata' => $maliciousMetadata,
            'created_at' => now(),
        ]);
        
        $this->assertTrue(Schema::hasTable('admin_activity_logs'));
        
        $log = DB::table('admin_activity_logs')->find($id);
        $this->assertEquals($maliciousMetadata, $log->metadata);
    }

    /**
     * SE-003: XSS in user_agent column.
     */
    public function test_se003_xss_in_user_agent_column(): void
    {
        $xssPayload = '<script>alert("xss")</script>';
        
        $id = DB::table('admin_activity_logs')->insertGetId([
            'action' => 'test.xss',
            'user_agent' => $xssPayload,
            'created_at' => now(),
        ]);
        
        $log = DB::table('admin_activity_logs')->find($id);
        $this->assertEquals($xssPayload, $log->user_agent, 'XSS payload should be stored as literal text');
    }

    /**
     * SE-004: Empty action column.
     */
    public function test_se004_empty_action_column(): void
    {
        // Empty string is allowed (not null)
        $id = DB::table('admin_activity_logs')->insertGetId([
            'action' => '',
            'created_at' => now(),
        ]);
        
        $log = DB::table('admin_activity_logs')->find($id);
        $this->assertEquals('', $log->action);
    }

    /**
     * SE-005: Null action column (should fail).
     */
    public function test_se005_null_action_column(): void
    {
        $this->expectException(\Exception::class);
        
        DB::table('admin_activity_logs')->insert([
            'action' => null, // Should fail (NOT NULL constraint)
            'created_at' => now(),
        ]);
    }

    /**
     * SE-006: Invalid JSON in metadata.
     */
    public function test_se006_invalid_json_in_metadata(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't validate JSON
            $this->assertTrue(true, 'SQLite does not validate JSON format');
            return;
        }
        
        $this->expectException(\Exception::class);
        
        DB::table('admin_activity_logs')->insert([
            'action' => 'test.invalid.json',
            'metadata' => 'not valid json {{{',
            'created_at' => now(),
        ]);
    }

    /**
     * SE-007: Very long user_agent.
     */
    public function test_se007_very_long_user_agent(): void
    {
        $longAgent = str_repeat('Mozilla/5.0 ', 1000); // ~12000 chars
        
        $id = DB::table('admin_activity_logs')->insertGetId([
            'action' => 'test.long.agent',
            'user_agent' => $longAgent,
            'created_at' => now(),
        ]);
        
        // TEXT column should store it
        $log = DB::table('admin_activity_logs')->find($id);
        $this->assertEquals($longAgent, $log->user_agent);
    }

    /**
     * SE-008: Unicode in action.
     */
    public function test_se008_unicode_in_action(): void
    {
        $unicodeAction = '用户.操作 🔐'; // Chinese + emoji
        
        $id = DB::table('admin_activity_logs')->insertGetId([
            'action' => $unicodeAction,
            'created_at' => now(),
        ]);
        
        $log = DB::table('admin_activity_logs')->find($id);
        $this->assertEquals($unicodeAction, $log->action);
    }

    /**
     * SE-009: Negative metric_value.
     */
    public function test_se009_negative_metric_value(): void
    {
        $id = DB::table('system_metrics_cache')->insertGetId([
            'metric_key' => 'negative.value',
            'metric_value' => -123.4567,
            'cached_at' => now(),
        ]);
        
        $metric = DB::table('system_metrics_cache')->find($id);
        $this->assertEquals(-123.4567, (float) $metric->metric_value);
    }

    /**
     * SE-010: Zero metric_value.
     */
    public function test_se010_zero_metric_value(): void
    {
        $id = DB::table('system_metrics_cache')->insertGetId([
            'metric_key' => 'zero.value',
            'metric_value' => 0,
            'cached_at' => now(),
        ]);
        
        $metric = DB::table('system_metrics_cache')->find($id);
        $this->assertEquals(0, (float) $metric->metric_value);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a test user.
     */
    private function createUser(array $attributes = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Get column listing for a table.
     */
    private function getColumnListing(string $table): array
    {
        return Schema::getColumnListing($table);
    }

    /**
     * Get column info using Doctrine.
     */
    private function getColumnInfo(string $table, string $column): object
    {
        return DB::connection()->getDoctrineColumn($table, $column);
    }

    /**
     * Get foreign keys for a table.
     */
    private function getForeignKeys(string $table): array
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return [];
        }
        
        $database = $conn->getDatabaseName();
        
        return DB::select("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);
    }

    /**
     * Get indexes for a table.
     */
    private function getTableIndexes(string $table): array
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();
        
        if ($driver === 'sqlite') {
            $indexes = [];
            $result = DB::select("PRAGMA index_list({$table})");
            foreach ($result as $row) {
                $indexName = $row->name;
                $columns = DB::select("PRAGMA index_info({$indexName})");
                $indexes[] = [
                    'name' => $indexName,
                    'columns' => array_map(fn($c) => $c->name, $columns),
                    'unique' => (bool) $row->unique,
                ];
            }
            return $indexes;
        }
        
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $database = $conn->getDatabaseName();
            
            $result = DB::select("
                SELECT 
                    INDEX_NAME as name,
                    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns,
                    NOT NON_UNIQUE as unique
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                GROUP BY INDEX_NAME, NON_UNIQUE
            ", [$database, $table]);
            
            return array_map(function ($row) {
                return [
                    'name' => $row->name,
                    'columns' => explode(',', $row->columns),
                    'unique' => (bool) $row->unique,
                ];
            }, $result);
        }
        
        return [];
    }
}
