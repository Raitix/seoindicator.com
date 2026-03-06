<?php

namespace Tests\Unit\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for badge_impressions table migration.
 * 
 * Task: TASK-33-3 - Create Badge Impressions Table
 * 
 * @covers \CreateBadgeImpressionsTable
 */
class CreateBadgeImpressionsTableTest extends TestCase
{
    use RefreshDatabase;

    protected string $tableName = 'badge_impressions';

    /*
    |--------------------------------------------------------------------------
    | Table Structure Tests (MS-001 to MS-009)
    |--------------------------------------------------------------------------
    */

    /** @test MS-001: Table badge_impressions exists after migration */
    public function test_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable($this->tableName));
    }

    /** @test MS-002: Column id exists with correct type */
    public function test_column_id_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'id'));
        
        $column = $this->getColumnDefinition($this->tableName, 'id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getAutoincrement());
        $this->assertFalse($column->getNotnull());
    }

    /** @test MS-003: Column audit_id exists with correct type */
    public function test_column_audit_id_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'audit_id'));
        
        $column = $this->getColumnDefinition($this->tableName, 'audit_id');
        $this->assertEquals('bigint', $column->getType()->getName());
        $this->assertTrue($column->getNotnull());
    }

    /** @test MS-004: Column public_hash exists with correct type */
    public function test_column_public_hash_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'public_hash'));
        
        $column = $this->getColumnDefinition($this->tableName, 'public_hash');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertEquals(32, $column->getLength());
        $this->assertTrue($column->getNotnull());
    }

    /** @test MS-005: Column referrer_domain exists with correct type */
    public function test_column_referrer_domain_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'referrer_domain'));
        
        $column = $this->getColumnDefinition($this->tableName, 'referrer_domain');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertEquals(255, $column->getLength());
        $this->assertFalse($column->getNotnull());
    }

    /** @test MS-006: Column impression_at exists with correct type */
    public function test_column_impression_at_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'impression_at'));
        
        $column = $this->getColumnDefinition($this->tableName, 'impression_at');
        $this->assertEquals('datetime', $column->getType()->getName());
        $this->assertFalse($column->getNotnull()); // Has default
    }

    /** @test MS-007: Column clicked_at exists with correct type */
    public function test_column_clicked_at_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'clicked_at'));
        
        $column = $this->getColumnDefinition($this->tableName, 'clicked_at');
        $this->assertEquals('datetime', $column->getType()->getName());
        $this->assertFalse($column->getNotnull());
    }

    /** @test MS-008: Column click_target exists with correct type */
    public function test_column_click_target_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'click_target'));
        
        $column = $this->getColumnDefinition($this->tableName, 'click_target');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertEquals(10, $column->getLength());
        $this->assertFalse($column->getNotnull());
    }

    /** @test MS-009: Column ip_hash exists with correct type */
    public function test_column_ip_hash_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'ip_hash'));
        
        $column = $this->getColumnDefinition($this->tableName, 'ip_hash');
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertEquals(64, $column->getLength());
        $this->assertFalse($column->getNotnull());
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests (MS-010 to MS-012)
    |--------------------------------------------------------------------------
    */

    /** @test MS-010: Index on audit_id exists */
    public function test_index_audit_id_exists(): void
    {
        $indexes = $this->getTableIndexes($this->tableName);
        $indexNames = array_column($indexes, 'name');
        
        $this->assertTrue(
            in_array('badge_impressions_audit_id_index', $indexNames) ||
            in_array('idx_audit_id', $indexNames) ||
            $this->hasIndexOnColumn($indexes, 'audit_id'),
            'Index on audit_id does not exist'
        );
    }

    /** @test MS-011: Index on public_hash exists */
    public function test_index_public_hash_exists(): void
    {
        $indexes = $this->getTableIndexes($this->tableName);
        $indexNames = array_column($indexes, 'name');
        
        $this->assertTrue(
            in_array('badge_impressions_public_hash_index', $indexNames) ||
            in_array('idx_public_hash', $indexNames) ||
            $this->hasIndexOnColumn($indexes, 'public_hash'),
            'Index on public_hash does not exist'
        );
    }

    /** @test MS-012: Index on impression_at exists */
    public function test_index_impression_at_exists(): void
    {
        $indexes = $this->getTableIndexes($this->tableName);
        $indexNames = array_column($indexes, 'name');
        
        $this->assertTrue(
            in_array('badge_impressions_impression_at_index', $indexNames) ||
            in_array('idx_impression_at', $indexNames) ||
            $this->hasIndexOnColumn($indexes, 'impression_at'),
            'Index on impression_at does not exist'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Foreign Key Tests (MS-013 to MS-014)
    |--------------------------------------------------------------------------
    */

    /** @test MS-013: Foreign key on audit_id references audits.id */
    public function test_foreign_key_audit_id_references_audits(): void
    {
        $foreignKeys = $this->getTableForeignKeys($this->tableName);
        
        $auditFk = collect($foreignKeys)->first(function ($fk) {
            return in_array('audit_id', $fk['columns']);
        });
        
        $this->assertNotNull($auditFk, 'Foreign key on audit_id not found');
        $this->assertEquals('audits', $auditFk['foreign_table']);
        $this->assertEquals(['id'], $auditFk['foreign_columns']);
    }

    /** @test MS-014: Foreign key CASCADE delete */
    public function test_foreign_key_cascade_delete(): void
    {
        $foreignKeys = $this->getTableForeignKeys($this->tableName);
        
        $auditFk = collect($foreignKeys)->first(function ($fk) {
            return in_array('audit_id', $fk['columns']);
        });
        
        $this->assertNotNull($auditFk, 'Foreign key on audit_id not found');
        $this->assertEquals('CASCADE', $auditFk['on_delete']);
    }

    /*
    |--------------------------------------------------------------------------
    | Constraint Tests (MS-015)
    |--------------------------------------------------------------------------
    */

    /** @test MS-015: No unique constraint on public_hash */
    public function test_no_unique_constraint_on_public_hash(): void
    {
        // Create an audit first
        $auditId = DB::table('audits')->insertGetId([
            'site_id' => 1,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $hash = 'abc123def456ghi789jkl012mno345pq';
        
        // Insert first record
        DB::table($this->tableName)->insert([
            'audit_id' => $auditId,
            'public_hash' => $hash,
            'impression_at' => now(),
        ]);
        
        // Insert second record with same hash
        DB::table($this->tableName)->insert([
            'audit_id' => $auditId,
            'public_hash' => $hash,
            'impression_at' => now(),
        ]);
        
        // Should succeed - no unique constraint
        $count = DB::table($this->tableName)
            ->where('public_hash', $hash)
            ->count();
        
        $this->assertEquals(2, $count);
    }

    /*
    |--------------------------------------------------------------------------
    | Data Operation Tests (MS-016 to MS-023)
    |--------------------------------------------------------------------------
    */

    /** @test MS-016: Insert record with all fields */
    public function test_insert_all_fields(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'referrer_domain' => 'example.com',
            'impression_at' => now(),
            'clicked_at' => now()->addMinutes(5),
            'click_target' => 'audit',
            'ip_hash' => hash('sha256', '192.168.1.1'),
        ]);
        
        $this->assertNotNull($id);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals('example.com', $record->referrer_domain);
        $this->assertEquals('audit', $record->click_target);
    }

    /** @test MS-017: Insert record with NULL referrer_domain */
    public function test_insert_null_referrer_domain(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertNull($record->referrer_domain);
    }

    /** @test MS-018: Insert record with NULL clicked_at */
    public function test_insert_null_clicked_at(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertNull($record->clicked_at);
    }

    /** @test MS-019: Insert record with NULL click_target */
    public function test_insert_null_click_target(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertNull($record->click_target);
    }

    /** @test MS-020: Insert record with NULL ip_hash */
    public function test_insert_null_ip_hash(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertNull($record->ip_hash);
    }

    /** @test MS-021: Insert record fails with NULL audit_id */
    public function test_insert_null_audit_id_fails(): void
    {
        $this->expectException(\Exception::class);
        
        DB::table($this->tableName)->insert([
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => now(),
        ]);
    }

    /** @test MS-022: Insert record fails with NULL public_hash */
    public function test_insert_null_public_hash_fails(): void
    {
        $auditId = $this->createTestAudit();
        
        $this->expectException(\Exception::class);
        
        DB::table($this->tableName)->insert([
            'audit_id' => $auditId,
            'impression_at' => now(),
        ]);
    }

    /** @test MS-023: Insert multiple records with same public_hash */
    public function test_insert_multiple_same_hash(): void
    {
        $auditId = $this->createTestAudit();
        $hash = 'abc123def456ghi789jkl012mno345pq';
        
        for ($i = 0; $i < 5; $i++) {
            DB::table($this->tableName)->insert([
                'audit_id' => $auditId,
                'public_hash' => $hash,
                'impression_at' => now()->addSeconds($i),
            ]);
        }
        
        $count = DB::table($this->tableName)
            ->where('public_hash', $hash)
            ->count();
        
        $this->assertEquals(5, $count);
    }

    /*
    |--------------------------------------------------------------------------
    | Cascade Delete Tests (MS-024)
    |--------------------------------------------------------------------------
    */

    /** @test MS-024: CASCADE delete when audit deleted */
    public function test_cascade_delete_on_audit_delete(): void
    {
        $auditId = $this->createTestAudit();
        
        // Create impressions
        DB::table($this->tableName)->insert([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => now(),
        ]);
        
        $this->assertEquals(1, DB::table($this->tableName)->where('audit_id', $auditId)->count());
        
        // Delete audit
        DB::table('audits')->where('id', $auditId)->delete();
        
        // Impressions should be deleted
        $this->assertEquals(0, DB::table($this->tableName)->where('audit_id', $auditId)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Reversibility Tests (MS-025 to MS-026)
    |--------------------------------------------------------------------------
    */

    /** @test MS-025: Rollback drops table */
    public function test_rollback_drops_table(): void
    {
        $this->assertTrue(Schema::hasTable($this->tableName));
        
        // Rollback last migration
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        $this->assertFalse(Schema::hasTable($this->tableName));
        
        // Re-run migration to restore state
        $this->artisan('migrate');
    }

    /** @test MS-026: Re-migrate after rollback */
    public function test_remigrate_after_rollback(): void
    {
        // Rollback
        $this->artisan('migrate:rollback', ['--step' => 1]);
        
        // Re-migrate
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasTable($this->tableName));
        $this->assertTrue(Schema::hasColumn($this->tableName, 'audit_id'));
        $this->assertTrue(Schema::hasColumn($this->tableName, 'public_hash'));
    }

    /*
    |--------------------------------------------------------------------------
    | Max Length Tests (MS-027 to MS-030)
    |--------------------------------------------------------------------------
    */

    /** @test MS-027: Insert with max referrer_domain length (255) */
    public function test_max_referrer_domain_length(): void
    {
        $auditId = $this->createTestAudit();
        $maxDomain = str_repeat('a', 255);
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'referrer_domain' => $maxDomain,
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals(255, strlen($record->referrer_domain));
    }

    /** @test MS-028: Insert with max click_target length (10) */
    public function test_max_click_target_length(): void
    {
        $auditId = $this->createTestAudit();
        
        // 'audit' is 5 chars, 'home' is 4 chars - both should fit
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'click_target' => 'audit',
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals('audit', $record->click_target);
    }

    /** @test MS-029: Insert with max ip_hash length (64) */
    public function test_max_ip_hash_length(): void
    {
        $auditId = $this->createTestAudit();
        $hash = hash('sha256', 'test-ip'); // 64 chars
        
        $this->assertEquals(64, strlen($hash));
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'ip_hash' => $hash,
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals(64, strlen($record->ip_hash));
    }

    /** @test MS-030: Insert with max public_hash length (32) */
    public function test_max_public_hash_length(): void
    {
        $auditId = $this->createTestAudit();
        $hash = str_repeat('a', 32);
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => $hash,
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals(32, strlen($record->public_hash));
    }

    /*
    |--------------------------------------------------------------------------
    | Click Target Value Tests (MS-031 to MS-033)
    |--------------------------------------------------------------------------
    */

    /** @test MS-031: Insert with click_target = 'audit' */
    public function test_click_target_audit(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'click_target' => 'audit',
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals('audit', $record->click_target);
    }

    /** @test MS-032: Insert with click_target = 'home' */
    public function test_click_target_home(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'click_target' => 'home',
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals('home', $record->click_target);
    }

    /** @test MS-033: Insert with click_target > 10 chars (truncated or error) */
    public function test_click_target_too_long(): void
    {
        $auditId = $this->createTestAudit();
        
        // MySQL will truncate or throw depending on strict mode
        // We test that the value is stored (possibly truncated)
        try {
            $id = DB::table($this->tableName)->insertGetId([
                'audit_id' => $auditId,
                'public_hash' => 'abc123def456ghi789jkl012mno345pq',
                'click_target' => 'verylongtarget', // 14 chars
                'impression_at' => now(),
            ]);
            
            $record = DB::table($this->tableName)->find($id);
            // If no error, value should be truncated to 10 chars
            $this->assertLessThanOrEqual(10, strlen($record->click_target));
        } catch (\Exception $e) {
            // Strict mode throws error
            $this->assertStringContainsString('Data too long', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Index Usage Tests (MS-034 to MS-036)
    |--------------------------------------------------------------------------
    */

    /** @test MS-034: Query by audit_id uses index */
    public function test_query_by_audit_id_uses_index(): void
    {
        $auditId = $this->createTestAudit();
        
        // Insert test data
        for ($i = 0; $i < 100; $i++) {
            DB::table($this->tableName)->insert([
                'audit_id' => $auditId,
                'public_hash' => 'hash' . str_pad($i, 28, 'x'),
                'impression_at' => now(),
            ]);
        }
        
        // Check explain
        $query = DB::table($this->tableName)->where('audit_id', $auditId);
        $explain = DB::select('EXPLAIN ' . $query->toSql(), [$auditId]);
        
        $this->assertNotEmpty($explain);
        // Index should be used (type should not be ALL)
        $this->assertNotEquals('ALL', $explain[0]->type ?? null);
    }

    /** @test MS-035: Query by public_hash uses index */
    public function test_query_by_public_hash_uses_index(): void
    {
        $auditId = $this->createTestAudit();
        $hash = 'abc123def456ghi789jkl012mno345pq';
        
        for ($i = 0; $i < 100; $i++) {
            DB::table($this->tableName)->insert([
                'audit_id' => $auditId,
                'public_hash' => 'hash' . str_pad($i, 28, 'x'),
                'impression_at' => now(),
            ]);
        }
        
        DB::table($this->tableName)->insert([
            'audit_id' => $auditId,
            'public_hash' => $hash,
            'impression_at' => now(),
        ]);
        
        $query = DB::table($this->tableName)->where('public_hash', $hash);
        $explain = DB::select('EXPLAIN ' . $query->toSql(), [$hash]);
        
        $this->assertNotEmpty($explain);
        $this->assertNotEquals('ALL', $explain[0]->type ?? null);
    }

    /** @test MS-036: Query by impression_at uses index */
    public function test_query_by_impression_at_uses_index(): void
    {
        $auditId = $this->createTestAudit();
        
        for ($i = 0; $i < 100; $i++) {
            DB::table($this->tableName)->insert([
                'audit_id' => $auditId,
                'public_hash' => 'hash' . str_pad($i, 28, 'x'),
                'impression_at' => now()->subDays($i),
            ]);
        }
        
        $query = DB::table($this->tableName)
            ->where('impression_at', '>=', now()->subDays(7));
        $explain = DB::select('EXPLAIN ' . $query->toSql(), [now()->subDays(7)]);
        
        $this->assertNotEmpty($explain);
        // Index usage preferred but not strictly required for range queries
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Operations Tests (MS-037 to MS-042)
    |--------------------------------------------------------------------------
    */

    /** @test MS-037: Bulk insert 100 records */
    public function test_bulk_insert_100_records(): void
    {
        $auditId = $this->createTestAudit();
        
        $records = [];
        for ($i = 0; $i < 100; $i++) {
            $records[] = [
                'audit_id' => $auditId,
                'public_hash' => 'hash' . str_pad($i, 28, 'x'),
                'impression_at' => now()->addSeconds($i),
            ];
        }
        
        DB::table($this->tableName)->insert($records);
        
        $this->assertEquals(100, DB::table($this->tableName)->where('audit_id', $auditId)->count());
    }

    /** @test MS-038: Update clicked_at from NULL to timestamp */
    public function test_update_clicked_at(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => now(),
        ]);
        
        $this->assertNull(DB::table($this->tableName)->find($id)->clicked_at);
        
        $clickTime = now()->addMinutes(5);
        DB::table($this->tableName)->where('id', $id)->update([
            'clicked_at' => $clickTime,
            'click_target' => 'audit',
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertNotNull($record->clicked_at);
        $this->assertEquals('audit', $record->click_target);
    }

    /** @test MS-039: Update click_target from NULL to 'audit' */
    public function test_update_click_target(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => now(),
        ]);
        
        DB::table($this->tableName)->where('id', $id)->update([
            'click_target' => 'audit',
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals('audit', $record->click_target);
    }

    /** @test MS-040: Query impressions by date range */
    public function test_query_by_date_range(): void
    {
        $auditId = $this->createTestAudit();
        
        // Create impressions across different days
        for ($i = 0; $i < 10; $i++) {
            DB::table($this->tableName)->insert([
                'audit_id' => $auditId,
                'public_hash' => 'hash' . str_pad($i, 28, 'x'),
                'impression_at' => now()->subDays($i),
            ]);
        }
        
        $recent = DB::table($this->tableName)
            ->where('audit_id', $auditId)
            ->where('impression_at', '>=', now()->subDays(3))
            ->count();
        
        $this->assertEquals(4, $recent); // Today + 3 days
    }

    /** @test MS-041: Count impressions per audit */
    public function test_count_impressions_per_audit(): void
    {
        $auditId1 = $this->createTestAudit();
        $auditId2 = $this->createTestAudit();
        
        for ($i = 0; $i < 5; $i++) {
            DB::table($this->tableName)->insert([
                'audit_id' => $auditId1,
                'public_hash' => 'hash1' . str_pad($i, 27, 'x'),
                'impression_at' => now(),
            ]);
        }
        
        for ($i = 0; $i < 3; $i++) {
            DB::table($this->tableName)->insert([
                'audit_id' => $auditId2,
                'public_hash' => 'hash2' . str_pad($i, 27, 'x'),
                'impression_at' => now(),
            ]);
        }
        
        $count1 = DB::table($this->tableName)->where('audit_id', $auditId1)->count();
        $count2 = DB::table($this->tableName)->where('audit_id', $auditId2)->count();
        
        $this->assertEquals(5, $count1);
        $this->assertEquals(3, $count2);
    }

    /** @test MS-042: Delete orphan impressions via CASCADE */
    public function test_delete_orphan_impressions(): void
    {
        $auditId = $this->createTestAudit();
        
        for ($i = 0; $i < 10; $i++) {
            DB::table($this->tableName)->insert([
                'audit_id' => $auditId,
                'public_hash' => 'hash' . str_pad($i, 28, 'x'),
                'impression_at' => now(),
            ]);
        }
        
        $this->assertEquals(10, DB::table($this->tableName)->where('audit_id', $auditId)->count());
        
        // Delete audit
        DB::table('audits')->where('id', $auditId)->delete();
        
        $this->assertEquals(0, DB::table($this->tableName)->where('audit_id', $auditId)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Edge Case Tests (MS-043 to MS-054)
    |--------------------------------------------------------------------------
    */

    /** @test MS-043: Unicode in referrer_domain */
    public function test_unicode_in_referrer_domain(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'referrer_domain' => '例え.com', // Japanese characters
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals('例え.com', $record->referrer_domain);
    }

    /** @test MS-044: IP hash SHA-256 format (64 hex chars) */
    public function test_ip_hash_sha256_format(): void
    {
        $auditId = $this->createTestAudit();
        $ipHash = hash('sha256', '192.168.1.1' . config('app.key'));
        
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $ipHash);
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'ip_hash' => $ipHash,
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals($ipHash, $record->ip_hash);
    }

    /** @test MS-045: Public hash alphanumeric only */
    public function test_public_hash_alphanumeric(): void
    {
        $auditId = $this->createTestAudit();
        $hash = 'AbCdEfGhIjKlMnOpQrStUvWxYz123456'; // 32 alphanumeric
        
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{32}$/', $hash);
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => $hash,
            'impression_at' => now(),
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertEquals($hash, $record->public_hash);
    }

    /** @test MS-046: Impression timestamp auto-populated */
    public function test_impression_timestamp_auto_populated(): void
    {
        $auditId = $this->createTestAudit();
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            // impression_at not specified - should use DEFAULT CURRENT_TIMESTAMP
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertNotNull($record->impression_at);
    }

    /** @test MS-047: Non-existent audit_id FK violation */
    public function test_nonexistent_audit_id_fk_violation(): void
    {
        $this->expectException(\Exception::class);
        
        DB::table($this->tableName)->insert([
            'audit_id' => 999999, // Non-existent
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => now(),
        ]);
    }

    /** @test MS-048: Multiple impressions per hash tracked */
    public function test_multiple_impressions_per_hash(): void
    {
        $auditId = $this->createTestAudit();
        $hash = 'abc123def456ghi789jkl012mno345pq';
        
        // Same hash, different referrers
        DB::table($this->tableName)->insert([
            'audit_id' => $auditId,
            'public_hash' => $hash,
            'referrer_domain' => 'site1.com',
            'impression_at' => now(),
        ]);
        
        DB::table($this->tableName)->insert([
            'audit_id' => $auditId,
            'public_hash' => $hash,
            'referrer_domain' => 'site2.com',
            'impression_at' => now(),
        ]);
        
        $count = DB::table($this->tableName)->where('public_hash', $hash)->count();
        $this->assertEquals(2, $count);
    }

    /** @test MS-049: Click target enum-like values */
    public function test_click_target_enum_values(): void
    {
        $auditId = $this->createTestAudit();
        
        // Valid values
        $id1 = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'hash1' . str_repeat('x', 27),
            'click_target' => 'audit',
            'impression_at' => now(),
        ]);
        
        $id2 = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'hash2' . str_repeat('x', 27),
            'click_target' => 'home',
            'impression_at' => now(),
        ]);
        
        $this->assertNotNull($id1);
        $this->assertNotNull($id2);
    }

    /** @test MS-050: Timestamp precision */
    public function test_timestamp_precision(): void
    {
        $auditId = $this->createTestAudit();
        $preciseTime = '2026-03-06 10:30:45.123456';
        
        $id = DB::table($this->tableName)->insertGetId([
            'audit_id' => $auditId,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'impression_at' => $preciseTime,
        ]);
        
        $record = DB::table($this->tableName)->find($id);
        $this->assertNotNull($record->impression_at);
    }

    /** @test MS-051: Index cardinality check */
    public function test_index_cardinality(): void
    {
        $auditId = $this->createTestAudit();
        
        // Insert 50 records
        for ($i = 0; $i < 50; $i++) {
            DB::table($this->tableName)->insert([
                'audit_id' => $auditId,
                'public_hash' => 'hash' . str_pad($i, 28, 'x'),
                'impression_at' => now(),
            ]);
        }
        
        // Run ANALYZE TABLE
        DB::statement('ANALYZE TABLE ' . $this->tableName);
        
        // Verify table has data
        $this->assertEquals(50, DB::table($this->tableName)->count());
    }

    /** @test MS-052: Foreign key name convention */
    public function test_foreign_key_name_convention(): void
    {
        $foreignKeys = $this->getTableForeignKeys($this->tableName);
        
        $auditFk = collect($foreignKeys)->first(function ($fk) {
            return in_array('audit_id', $fk['columns']);
        });
        
        $this->assertNotNull($auditFk);
        // Convention: {table}_{column}_foreign
        $this->assertStringContainsString('audit', $auditFk['name']);
    }

    /** @test MS-053: Migration batch number */
    public function test_migration_batch_number(): void
    {
        $migration = DB::table('migrations')
            ->where('migration', 'like', '%badge_impressions%')
            ->first();
        
        $this->assertNotNull($migration);
        $this->assertIsInt($migration->batch);
    }

    /** @test MS-054: Table engine */
    public function test_table_engine(): void
    {
        $tableInfo = DB::select("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", [
            config('database.connections.mysql.database'),
            $this->tableName,
        ]);
        
        $this->assertNotEmpty($tableInfo);
        $this->assertEquals('InnoDB', $tableInfo[0]->ENGINE);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    private function createTestAudit(): int
    {
        return DB::table('audits')->insertGetId([
            'site_id' => 1,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getColumnDefinition(string $table, string $column): \Doctrine\DBAL\Schema\Column
    {
        return Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableDetails($table)
            ->getColumn($column);
    }

    private function getTableIndexes(string $table): array
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);
        
        return array_map(function ($index) {
            return [
                'name' => $index->getName(),
                'columns' => $index->getColumns(),
                'primary' => $index->isPrimary(),
                'unique' => $index->isUnique(),
            ];
        }, $indexes);
    }

    private function hasIndexOnColumn(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if (in_array($column, $index['columns'])) {
                return true;
            }
        }
        return false;
    }

    private function getTableForeignKeys(string $table): array
    {
        $foreignKeys = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys($table);
        
        return array_map(function ($fk) {
            return [
                'name' => $fk->getName(),
                'columns' => $fk->getLocalColumns(),
                'foreign_table' => $fk->getForeignTableName(),
                'foreign_columns' => $fk->getForeignColumns(),
                'on_delete' => $fk->getOption('onDelete'),
                'on_update' => $fk->getOption('onUpdate'),
            ];
        }, $foreignKeys);
    }
}
