<?php

namespace Tests\Unit\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

/**
 * Tests for create_public_audit_views_table migration
 *
 * TASK-33-2: Create Public Audit Views Table
 *
 * @group migration
 * @group public-audit-views
 */
class CreatePublicAuditViewsTableTest extends TestCase
{
    /**
     * Table name being tested
     */
    private const TABLE_NAME = 'public_audit_views';

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /*
    |--------------------------------------------------------------------------
    | Migration Structure Tests (MS)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox MS-001: Table exists after migration
     */
    public function table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable(self::TABLE_NAME),
            "Table [{$this->getTableName()}] should exist"
        );
    }

    /**
     * @test
     * @testdox MS-002: Table has all required columns
     */
    public function table_has_all_required_columns(): void
    {
        $expectedColumns = [
            'id',
            'audit_id',
            'public_hash',
            'viewed_at',
            'referrer',
            'ip_hash',
            'user_agent',
            'country_code',
            'is_lead_capture',
            'lead_email',
            'created_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn(self::TABLE_NAME, $column),
                "Column [{$column}] should exist in table"
            );
        }
    }

    /**
     * @test
     * @testdox MS-003: id column is auto-increment primary key
     */
    public function id_column_is_auto_increment_primary_key(): void
    {
        $column = Schema::getColumnType(self::TABLE_NAME, 'id');

        // Verify it's a bigint
        $this->assertContains($column, ['bigint', 'integer']);

        // Verify auto-increment
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'id'
            ", [$tableName]);

            $this->assertStringContainsString('auto_increment', $result->EXTRA ?? '');
        }
    }

    /**
     * @test
     * @testdox MS-004: audit_id column is bigint unsigned not null
     */
    public function audit_id_column_is_bigint_unsigned_not_null(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'audit_id'
            ", [$tableName]);

            $this->assertStringContainsString('bigint', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertStringContainsString('unsigned', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertEquals('NO', $result->IS_NULLABLE ?? '');
        } else {
            // SQLite fallback
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'audit_id'));
        }
    }

    /**
     * @test
     * @testdox MS-005: public_hash column is varchar(32) not null
     */
    public function public_hash_column_is_varchar_32_not_null(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'public_hash'
            ", [$tableName]);

            $this->assertStringContainsString('varchar', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertStringContainsString('32', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertEquals('NO', $result->IS_NULLABLE ?? '');
        } else {
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'public_hash'));
        }
    }

    /**
     * @test
     * @testdox MS-006: viewed_at column is timestamp with default current_timestamp
     */
    public function viewed_at_column_is_timestamp_with_default(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, COLUMN_DEFAULT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'viewed_at'
            ", [$tableName]);

            $this->assertStringContainsString('timestamp', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertNotNull($result->COLUMN_DEFAULT);
        } else {
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'viewed_at'));
        }
    }

    /**
     * @test
     * @testdox MS-007: referrer column is varchar(500) nullable
     */
    public function referrer_column_is_varchar_500_nullable(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'referrer'
            ", [$tableName]);

            $this->assertStringContainsString('varchar', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertStringContainsString('500', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertEquals('YES', $result->IS_NULLABLE ?? '');
        } else {
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'referrer'));
        }
    }

    /**
     * @test
     * @testdox MS-008: ip_hash column is varchar(64) nullable for SHA-256 hash
     */
    public function ip_hash_column_is_varchar_64_nullable(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'ip_hash'
            ", [$tableName]);

            $this->assertStringContainsString('varchar', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertStringContainsString('64', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertEquals('YES', $result->IS_NULLABLE ?? '');
        } else {
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'ip_hash'));
        }
    }

    /**
     * @test
     * @testdox MS-009: user_agent column is varchar(500) nullable
     */
    public function user_agent_column_is_varchar_500_nullable(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'user_agent'
            ", [$tableName]);

            $this->assertStringContainsString('varchar', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertStringContainsString('500', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertEquals('YES', $result->IS_NULLABLE ?? '');
        } else {
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'user_agent'));
        }
    }

    /**
     * @test
     * @testdox MS-010: country_code column is varchar(2) nullable for ISO codes
     */
    public function country_code_column_is_varchar_2_nullable(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'country_code'
            ", [$tableName]);

            $this->assertStringContainsString('varchar', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertStringContainsString('2', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertEquals('YES', $result->IS_NULLABLE ?? '');
        } else {
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'country_code'));
        }
    }

    /**
     * @test
     * @testdox MS-011: is_lead_capture column is tinyint(1) with default 0
     */
    public function is_lead_capture_column_is_tinyint_with_default_0(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, COLUMN_DEFAULT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'is_lead_capture'
            ", [$tableName]);

            $this->assertStringContainsString('tinyint', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertEquals('0', $result->COLUMN_DEFAULT ?? '');
        } else {
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'is_lead_capture'));
        }
    }

    /**
     * @test
     * @testdox MS-012: lead_email column is varchar(255) nullable
     */
    public function lead_email_column_is_varchar_255_nullable(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, IS_NULLABLE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'lead_email'
            ", [$tableName]);

            $this->assertStringContainsString('varchar', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertStringContainsString('255', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertEquals('YES', $result->IS_NULLABLE ?? '');
        } else {
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'lead_email'));
        }
    }

    /**
     * @test
     * @testdox MS-013: created_at column is timestamp with default
     */
    public function created_at_column_is_timestamp_with_default(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $result = DB::selectOne("
                SELECT COLUMN_TYPE, COLUMN_DEFAULT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'created_at'
            ", [$tableName]);

            $this->assertStringContainsString('timestamp', strtolower($result->COLUMN_TYPE ?? ''));
            $this->assertNotNull($result->COLUMN_DEFAULT);
        } else {
            $this->assertTrue(Schema::hasColumn(self::TABLE_NAME, 'created_at'));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests (IX)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox IX-001: Index exists on audit_id column
     */
    public function index_exists_on_audit_id_column(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $indexes = DB::select("
                SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'audit_id'
            ", [$tableName]);

            $indexNames = array_column($indexes, 'INDEX_NAME');
            $this->assertNotEmpty(
                array_filter($indexNames, fn($name) => $name !== 'PRIMARY'),
                'Index on audit_id should exist'
            );
        }
    }

    /**
     * @test
     * @testdox IX-002: Index exists on public_hash column
     */
    public function index_exists_on_public_hash_column(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $indexes = DB::select("
                SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'public_hash'
            ", [$tableName]);

            $indexNames = array_column($indexes, 'INDEX_NAME');
            $this->assertNotEmpty(
                array_filter($indexNames, fn($name) => $name !== 'PRIMARY'),
                'Index on public_hash should exist'
            );
        }
    }

    /**
     * @test
     * @testdox IX-003: Index exists on viewed_at column
     */
    public function index_exists_on_viewed_at_column(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $indexes = DB::select("
                SELECT INDEX_NAME
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'viewed_at'
            ", [$tableName]);

            $indexNames = array_column($indexes, 'INDEX_NAME');
            $this->assertNotEmpty(
                array_filter($indexNames, fn($name) => $name !== 'PRIMARY'),
                'Index on viewed_at should exist'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Foreign Key Tests (FK)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox FK-001: Foreign key exists on audit_id referencing audits table
     */
    public function foreign_key_exists_on_audit_id(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $foreignKeys = DB::select("
                SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);

            $auditForeignKey = collect($foreignKeys)->firstWhere('COLUMN_NAME', 'audit_id');

            $this->assertNotNull($auditForeignKey, 'Foreign key on audit_id should exist');
            $this->assertEquals('audits', $auditForeignKey->REFERENCED_TABLE_NAME);
            $this->assertEquals('id', $auditForeignKey->REFERENCED_COLUMN_NAME);
        }
    }

    /**
     * @test
     * @testdox FK-002: Foreign key uses CASCADE delete
     */
    public function foreign_key_uses_cascade_delete(): void
    {
        $connection = Schema::getConnection();
        $tableName = self::TABLE_NAME;

        if ($connection->getDriverName() === 'mysql') {
            $foreignKeys = DB::select("
                SELECT DELETE_RULE
                FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
            ", [$tableName]);

            $this->assertNotEmpty($foreignKeys, 'Foreign key constraints should exist');

            $auditConstraint = collect($foreignKeys)->first();
            $this->assertEquals('CASCADE', $auditConstraint->DELETE_RULE ?? '');
        }
    }

    /**
     * @test
     * @testdox FK-003: Cascade delete works when audit is deleted
     */
    public function cascade_delete_works_when_audit_is_deleted(): void
    {
        // Create an audit
        $audit = \App\Models\Audit::factory()->create();

        // Create a public audit view
        \App\Models\PublicAuditView::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => 'test' . str_repeat('a', 28),
        ]);

        // Verify the view exists
        $this->assertDatabaseHas(self::TABLE_NAME, [
            'audit_id' => $audit->id,
        ]);

        // Delete the audit
        $audit->delete();

        // Verify the view was cascade deleted
        $this->assertDatabaseMissing(self::TABLE_NAME, [
            'audit_id' => $audit->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Migration Data Tests (MD)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox MD-001: Can insert a record with all fields
     */
    public function can_insert_record_with_all_fields(): void
    {
        $audit = \App\Models\Audit::factory()->create();

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => 'aBcD1234EfGh5678IjKl9012MnOp3456',
            'viewed_at' => now(),
            'referrer' => 'https://example.com/page',
            'ip_hash' => hash('sha256', '192.168.1.1' . config('app.key')),
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'country_code' => 'US',
            'is_lead_capture' => true,
            'lead_email' => 'test@example.com',
            'created_at' => now(),
        ]);

        $this->assertIsInt($viewId);
        $this->assertGreaterThan(0, $viewId);
    }

    /**
     * @test
     * @testdox MD-002: Can insert record with minimal required fields
     */
    public function can_insert_record_with_minimal_fields(): void
    {
        $audit = \App\Models\Audit::factory()->create();

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => 'minimal0000000000000000000000',
        ]);

        $this->assertIsInt($viewId);
        $this->assertGreaterThan(0, $viewId);
    }

    /**
     * @test
     * @testdox MD-003: public_hash is 32 characters
     */
    public function public_hash_is_32_characters(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $hash = str_repeat('a', 32);

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
        ]);

        $view = DB::table(self::TABLE_NAME)->find($viewId);
        $this->assertEquals(32, strlen($view->public_hash));
    }

    /**
     * @test
     * @testdox MD-004: country_code is 2 characters (ISO format)
     */
    public function country_code_is_2_characters(): void
    {
        $audit = \App\Models\Audit::factory()->create();

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('b', 32),
            'country_code' => 'LV',
        ]);

        $view = DB::table(self::TABLE_NAME)->find($viewId);
        $this->assertEquals(2, strlen($view->country_code));
    }

    /**
     * @test
     * @testdox MD-005: ip_hash is 64 characters (SHA-256)
     */
    public function ip_hash_is_64_characters(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $ipHash = hash('sha256', '192.168.1.1' . config('app.key'));

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('c', 32),
            'ip_hash' => $ipHash,
        ]);

        $view = DB::table(self::TABLE_NAME)->find($viewId);
        $this->assertEquals(64, strlen($view->ip_hash));
    }

    /*
    |--------------------------------------------------------------------------
    | Migration Reversibility Tests (MR)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox MR-001: Migration can be rolled back
     */
    public function migration_can_be_rolled_back(): void
    {
        // Run rollback
        $this->artisan('migrate:rollback', [
            '--step' => 1,
            '--force' => true,
        ]);

        // Verify table is dropped
        $this->assertFalse(
            Schema::hasTable(self::TABLE_NAME),
            "Table [{$this->getTableName()}] should not exist after rollback"
        );

        // Re-run migration for other tests
        $this->artisan('migrate', ['--force' => true]);
    }

    /**
     * @test
     * @testdox MR-002: Migration can be re-run after rollback
     */
    public function migration_can_be_rerun_after_rollback(): void
    {
        // Rollback
        $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true]);

        // Re-run
        $this->artisan('migrate', ['--force' => true]);

        // Verify table exists again
        $this->assertTrue(
            Schema::hasTable(self::TABLE_NAME),
            "Table [{$this->getTableName()}] should exist after re-running migration"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Edge Cases (EC)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox EC-001: Can store long referrer URLs (up to 500 chars)
     */
    public function can_store_long_referrer_urls(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $longReferrer = 'https://example.com/' . str_repeat('a', 470);

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('d', 32),
            'referrer' => $longReferrer,
        ]);

        $view = DB::table(self::TABLE_NAME)->find($viewId);
        $this->assertEquals($longReferrer, $view->referrer);
    }

    /**
     * @test
     * @testdox EC-002: Can store long user agent strings (up to 500 chars)
     */
    public function can_store_long_user_agent_strings(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $longUserAgent = 'Mozilla/5.0 ' . str_repeat('a', 480);

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('e', 32),
            'user_agent' => $longUserAgent,
        ]);

        $view = DB::table(self::TABLE_NAME)->find($viewId);
        $this->assertEquals($longUserAgent, $view->user_agent);
    }

    /**
     * @test
     * @testdox EC-003: Multiple views can exist for same audit
     */
    public function multiple_views_can_exist_for_same_audit(): void
    {
        $audit = \App\Models\Audit::factory()->create();

        // Insert multiple views
        for ($i = 0; $i < 5; $i++) {
            DB::table(self::TABLE_NAME)->insert([
                'audit_id' => $audit->id,
                'public_hash' => str_repeat((string) $i, 32),
                'viewed_at' => now()->addMinutes($i),
            ]);
        }

        $count = DB::table(self::TABLE_NAME)->where('audit_id', $audit->id)->count();
        $this->assertEquals(5, $count);
    }

    /**
     * @test
     * @testdox EC-004: Multiple views can exist for same public_hash
     */
    public function multiple_views_can_exist_for_same_public_hash(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $hash = str_repeat('f', 32);

        // Insert multiple views with same hash (tracking multiple page views)
        for ($i = 0; $i < 3; $i++) {
            DB::table(self::TABLE_NAME)->insert([
                'audit_id' => $audit->id,
                'public_hash' => $hash,
                'viewed_at' => now()->addMinutes($i),
            ]);
        }

        $count = DB::table(self::TABLE_NAME)->where('public_hash', $hash)->count();
        $this->assertEquals(3, $count);
    }

    /**
     * @test
     * @testdox EC-005: viewed_at can be in the past
     */
    public function viewed_at_can_be_in_the_past(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $pastDate = now()->subDays(30);

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('g', 32),
            'viewed_at' => $pastDate,
        ]);

        $view = DB::table(self::TABLE_NAME)->find($viewId);
        $this->assertNotNull($view->viewed_at);
    }

    /**
     * @test
     * @testdox EC-006: is_lead_capture can be true or false
     */
    public function is_lead_capture_can_be_true_or_false(): void
    {
        $audit = \App\Models\Audit::factory()->create();

        // Test true
        $viewId1 = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('h', 32),
            'is_lead_capture' => true,
        ]);

        // Test false
        $viewId2 = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('i', 32),
            'is_lead_capture' => false,
        ]);

        $view1 = DB::table(self::TABLE_NAME)->find($viewId1);
        $view2 = DB::table(self::TABLE_NAME)->find($viewId2);

        $this->assertEquals(1, $view1->is_lead_capture);
        $this->assertEquals(0, $view2->is_lead_capture);
    }

    /**
     * @test
     * @testdox EC-007: lead_email can be null or have value
     */
    public function lead_email_can_be_null_or_have_value(): void
    {
        $audit = \App\Models\Audit::factory()->create();

        // Test with email
        $viewId1 = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('j', 32),
            'lead_email' => 'user@example.com',
        ]);

        // Test without email
        $viewId2 = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('k', 32),
            'lead_email' => null,
        ]);

        $view1 = DB::table(self::TABLE_NAME)->find($viewId1);
        $view2 = DB::table(self::TABLE_NAME)->find($viewId2);

        $this->assertEquals('user@example.com', $view1->lead_email);
        $this->assertNull($view2->lead_email);
    }

    /*
    |--------------------------------------------------------------------------
    | Performance Tests (PF)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox PF-001: Index on audit_id improves query performance
     */
    public function index_on_audit_id_improves_query_performance(): void
    {
        $audit = \App\Models\Audit::factory()->create();

        // Insert 100 views
        for ($i = 0; $i < 100; $i++) {
            DB::table(self::TABLE_NAME)->insert([
                'audit_id' => $audit->id,
                'public_hash' => str_repeat((string) ($i % 10), 32),
                'viewed_at' => now()->addMinutes($i),
            ]);
        }

        // Query with index should be fast
        $startTime = microtime(true);
        $count = DB::table(self::TABLE_NAME)->where('audit_id', $audit->id)->count();
        $queryTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(100, $count);
        $this->assertLessThan(100, $queryTime, 'Query should complete in under 100ms');
    }

    /**
     * @test
     * @testdox PF-002: Index on public_hash improves query performance
     */
    public function index_on_public_hash_improves_query_performance(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $targetHash = str_repeat('x', 32);

        // Insert views with various hashes
        for ($i = 0; $i < 100; $i++) {
            DB::table(self::TABLE_NAME)->insert([
                'audit_id' => $audit->id,
                'public_hash' => $i === 50 ? $targetHash : str_repeat((string) ($i % 10), 32),
                'viewed_at' => now()->addMinutes($i),
            ]);
        }

        // Query with index should be fast
        $startTime = microtime(true);
        $count = DB::table(self::TABLE_NAME)->where('public_hash', $targetHash)->count();
        $queryTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(1, $count);
        $this->assertLessThan(100, $queryTime, 'Query should complete in under 100ms');
    }

    /**
     * @test
     * @testdox PF-003: Index on viewed_at enables efficient date range queries
     */
    public function index_on_viewed_at_enables_date_range_queries(): void
    {
        $audit = \App\Models\Audit::factory()->create();

        // Insert views with different dates
        for ($i = 0; $i < 30; $i++) {
            DB::table(self::TABLE_NAME)->insert([
                'audit_id' => $audit->id,
                'public_hash' => str_repeat((string) $i, 32),
                'viewed_at' => now()->subDays(30 - $i),
            ]);
        }

        // Query last 7 days
        $startTime = microtime(true);
        $count = DB::table(self::TABLE_NAME)
            ->where('viewed_at', '>=', now()->subDays(7))
            ->count();
        $queryTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(7, $count);
        $this->assertLessThan(100, $queryTime, 'Query should complete in under 100ms');
    }

    /*
    |--------------------------------------------------------------------------
    | Security Tests (SC)
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @testdox SC-001: Invalid audit_id is rejected by foreign key
     */
    public function invalid_audit_id_is_rejected_by_foreign_key(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table(self::TABLE_NAME)->insert([
            'audit_id' => 999999, // Non-existent audit
            'public_hash' => str_repeat('z', 32),
        ]);
    }

    /**
     * @test
     * @testdox SC-002: SQL injection in public_hash is prevented
     */
    public function sql_injection_in_public_hash_is_prevented(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $maliciousHash = "'; DROP TABLE public_audit_views; --";

        // Should fail validation (32 char alphanumeric required)
        try {
            DB::table(self::TABLE_NAME)->insert([
                'audit_id' => $audit->id,
                'public_hash' => $maliciousHash,
            ]);

            // If it somehow inserted, verify table still exists
            $this->assertTrue(Schema::hasTable(self::TABLE_NAME));
        } catch (\Exception $e) {
            // Expected - invalid hash format
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     * @testdox SC-003: XSS in referrer is stored safely (not executed)
     */
    public function xss_in_referrer_is_stored_safely(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $xssReferrer = '<script>alert("xss")</script>';

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('m', 32),
            'referrer' => $xssReferrer,
        ]);

        $view = DB::table(self::TABLE_NAME)->find($viewId);

        // XSS string is stored as-is (not sanitized at DB level)
        // Model should handle escaping on output
        $this->assertStringContainsString('<script>', $view->referrer);
    }

    /**
     * @test
     * @testdox SC-004: XSS in user_agent is stored safely
     */
    public function xss_in_user_agent_is_stored_safely(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $xssUserAgent = '<img src=x onerror=alert(1)>';

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('n', 32),
            'user_agent' => $xssUserAgent,
        ]);

        $view = DB::table(self::TABLE_NAME)->find($viewId);
        $this->assertStringContainsString('<img', $view->user_agent);
    }

    /**
     * @test
     * @testdox SC-005: XSS in lead_email is stored safely
     */
    public function xss_in_lead_email_is_stored_safely(): void
    {
        $audit = \App\Models\Audit::factory()->create();
        $xssEmail = 'test<script>alert(1)</script>@example.com';

        $viewId = DB::table(self::TABLE_NAME)->insertGetId([
            'audit_id' => $audit->id,
            'public_hash' => str_repeat('o', 32),
            'lead_email' => $xssEmail,
        ]);

        $view = DB::table(self::TABLE_NAME)->find($viewId);
        $this->assertStringContainsString('<script>', $view->lead_email);
    }

    /**
     * Helper to get table name with prefix
     */
    private function getTableName(): string
    {
        return self::TABLE_NAME;
    }
}
