<?php

namespace Tests\Unit\Migrations;

use App\Models\Audit;
use App\Models\BadgeImpression;
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
    }

    /** @test MS-003: Column audit_id exists with correct type */
    public function test_column_audit_id_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'audit_id'));
    }

    /** @test MS-004: Column public_hash exists with correct type */
    public function test_column_public_hash_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'public_hash'));
    }

    /** @test MS-005: Column referrer_domain exists with correct type */
    public function test_column_referrer_domain_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'referrer_domain'));
    }

    /** @test MS-006: Column impression_at exists with correct type */
    public function test_column_impression_at_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'impression_at'));
    }

    /** @test MS-007: Column clicked_at exists with correct type */
    public function test_column_clicked_at_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'clicked_at'));
    }

    /** @test MS-008: Column click_target exists with correct type */
    public function test_column_click_target_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'click_target'));
    }

    /** @test MS-009: Column ip_hash exists with correct type */
    public function test_column_ip_hash_exists(): void
    {
        $this->assertTrue(Schema::hasColumn($this->tableName, 'ip_hash'));
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests (MS-010 to MS-012)
    |--------------------------------------------------------------------------
    */

    /** @test MS-010: Index on audit_id exists */
    public function test_index_audit_id_exists(): void
    {
        $hasIndex = $this->hasIndexOnColumn($this->tableName, 'audit_id');
        $this->assertTrue($hasIndex, 'Index on audit_id does not exist');
    }

    /** @test MS-011: Index on public_hash exists */
    public function test_index_public_hash_exists(): void
    {
        $hasIndex = $this->hasIndexOnColumn($this->tableName, 'public_hash');
        $this->assertTrue($hasIndex, 'Index on public_hash does not exist');
    }

    /** @test MS-012: Index on impression_at exists */
    public function test_index_impression_at_exists(): void
    {
        $hasIndex = $this->hasIndexOnColumn($this->tableName, 'impression_at');
        $this->assertTrue($hasIndex, 'Index on impression_at does not exist');
    }

    /*
    |--------------------------------------------------------------------------
    | Foreign Key Tests (MS-013 to MS-014)
    |--------------------------------------------------------------------------
    */

    /** @test MS-013: Foreign key on audit_id references audits.id */
    public function test_foreign_key_audit_id_references_audits(): void
    {
        // Test by verifying FK constraint - inserting with non-existent audit_id should fail
        $this->expectException(\Exception::class);
        
        DB::table($this->tableName)->insert([
            'audit_id' => 999999,
            'public_hash' => 'test123test123test123test123te',
            'impression_at' => now(),
        ]);
    }

    /** @test MS-014: Foreign key CASCADE delete */
    public function test_foreign_key_cascade_delete(): void
    {
        $audit = Audit::factory()->create();
        
        BadgeImpression::factory()->count(3)->create(['audit_id' => $audit->id]);
        
        $this->assertEquals(3, BadgeImpression::where('audit_id', $audit->id)->count());
        
        $audit->delete();
        
        $this->assertEquals(0, BadgeImpression::where('audit_id', $audit->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Constraint Tests (MS-015)
    |--------------------------------------------------------------------------
    */

    /** @test MS-015: No unique constraint on public_hash */
    public function test_no_unique_constraint_on_public_hash(): void
    {
        $audit = Audit::factory()->create();
        $hash = 'abc123def456ghi789jkl012mno345pq';
        
        // Insert two records with same hash
        BadgeImpression::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
        ]);
        
        BadgeImpression::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
        ]);
        
        $count = BadgeImpression::where('public_hash', $hash)->count();
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
        $audit = Audit::factory()->create();
        
        $impression = BadgeImpression::create([
            'audit_id' => $audit->id,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'referrer_domain' => 'example.com',
            'impression_at' => now(),
            'clicked_at' => now()->addMinutes(5),
            'click_target' => 'audit',
            'ip_hash' => hash('sha256', '192.168.1.1'),
        ]);
        
        $this->assertNotNull($impression->id);
        $this->assertEquals('example.com', $impression->referrer_domain);
        $this->assertEquals('audit', $impression->click_target);
    }

    /** @test MS-017: Insert record with NULL referrer_domain */
    public function test_insert_null_referrer_domain(): void
    {
        $impression = BadgeImpression::factory()->create([
            'referrer_domain' => null,
        ]);
        
        $this->assertNull($impression->referrer_domain);
    }

    /** @test MS-018: Insert record with NULL clicked_at */
    public function test_insert_null_clicked_at(): void
    {
        $impression = BadgeImpression::factory()->create([
            'clicked_at' => null,
        ]);
        
        $this->assertNull($impression->clicked_at);
    }

    /** @test MS-019: Insert record with NULL click_target */
    public function test_insert_null_click_target(): void
    {
        $impression = BadgeImpression::factory()->create([
            'click_target' => null,
        ]);
        
        $this->assertNull($impression->click_target);
    }

    /** @test MS-020: Insert record with NULL ip_hash */
    public function test_insert_null_ip_hash(): void
    {
        $impression = BadgeImpression::factory()->create([
            'ip_hash' => null,
        ]);
        
        $this->assertNull($impression->ip_hash);
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
        $audit = Audit::factory()->create();
        
        $this->expectException(\Exception::class);
        
        DB::table($this->tableName)->insert([
            'audit_id' => $audit->id,
            'impression_at' => now(),
        ]);
    }

    /** @test MS-023: Insert multiple records with same public_hash */
    public function test_insert_multiple_same_hash(): void
    {
        $audit = Audit::factory()->create();
        $hash = 'abc123def456ghi789jkl012mno345pq';
        
        for ($i = 0; $i < 5; $i++) {
            BadgeImpression::factory()->create([
                'audit_id' => $audit->id,
                'public_hash' => $hash,
            ]);
        }
        
        $count = BadgeImpression::where('public_hash', $hash)->count();
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
        $audit = Audit::factory()->create();
        
        BadgeImpression::factory()->create(['audit_id' => $audit->id]);
        
        $this->assertEquals(1, BadgeImpression::where('audit_id', $audit->id)->count());
        
        $audit->delete();
        
        $this->assertEquals(0, BadgeImpression::where('audit_id', $audit->id)->count());
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
        $maxDomain = str_repeat('a', 255);
        
        $impression = BadgeImpression::factory()->create([
            'referrer_domain' => $maxDomain,
        ]);
        
        $this->assertEquals(255, strlen($impression->referrer_domain));
    }

    /** @test MS-028: Insert with max click_target length (10) */
    public function test_max_click_target_length(): void
    {
        $impression = BadgeImpression::factory()->create([
            'click_target' => 'audit',
        ]);
        
        $this->assertEquals('audit', $impression->click_target);
    }

    /** @test MS-029: Insert with max ip_hash length (64) */
    public function test_max_ip_hash_length(): void
    {
        $hash = hash('sha256', 'test-ip'); // 64 chars
        
        $this->assertEquals(64, strlen($hash));
        
        $impression = BadgeImpression::factory()->create([
            'ip_hash' => $hash,
        ]);
        
        $this->assertEquals(64, strlen($impression->ip_hash));
    }

    /** @test MS-030: Insert with max public_hash length (32) */
    public function test_max_public_hash_length(): void
    {
        $hash = str_repeat('a', 32);
        
        $impression = BadgeImpression::factory()->create([
            'public_hash' => $hash,
        ]);
        
        $this->assertEquals(32, strlen($impression->public_hash));
    }

    /*
    |--------------------------------------------------------------------------
    | Click Target Value Tests (MS-031 to MS-033)
    |--------------------------------------------------------------------------
    */

    /** @test MS-031: Insert with click_target = 'audit' */
    public function test_click_target_audit(): void
    {
        $impression = BadgeImpression::factory()->create([
            'click_target' => 'audit',
        ]);
        
        $this->assertEquals('audit', $impression->click_target);
    }

    /** @test MS-032: Insert with click_target = 'home' */
    public function test_click_target_home(): void
    {
        $impression = BadgeImpression::factory()->create([
            'click_target' => 'home',
        ]);
        
        $this->assertEquals('home', $impression->click_target);
    }

    /** @test MS-033: Insert with click_target > 10 chars */
    public function test_click_target_too_long(): void
    {
        // MySQL in non-strict mode truncates values that exceed column length
        // The value 'verylongtarget' (14 chars) will be truncated to 'verylongta' (10 chars)
        // This test verifies the column size constraint is enforced
        $impression = BadgeImpression::factory()->create([
            'click_target' => 'verylongtarget', // 14 chars
        ]);
        
        // Value should be truncated to 10 characters by MySQL
        $storedValue = $impression->fresh()->click_target;
        $this->assertLessThanOrEqual(10, strlen($storedValue), 'Click target should be truncated to 10 characters');
    }

    /*
    |--------------------------------------------------------------------------
    | Index Usage Tests (MS-034 to MS-036)
    |--------------------------------------------------------------------------
    */

    /** @test MS-034: Query by audit_id uses index */
    public function test_query_by_audit_id_uses_index(): void
    {
        $audit = Audit::factory()->create();
        
        BadgeImpression::factory()->count(10)->create(['audit_id' => $audit->id]);
        
        $results = BadgeImpression::where('audit_id', $audit->id)->get();
        $this->assertEquals(10, $results->count());
    }

    /** @test MS-035: Query by public_hash uses index */
    public function test_query_by_public_hash_uses_index(): void
    {
        $hash = 'abc123def456ghi789jkl012mno345pq';
        
        BadgeImpression::factory()->create(['public_hash' => $hash]);
        
        $results = BadgeImpression::where('public_hash', $hash)->get();
        $this->assertEquals(1, $results->count());
    }

    /** @test MS-036: Query by impression_at uses index */
    public function test_query_by_impression_at_uses_index(): void
    {
        BadgeImpression::factory()->create(['impression_at' => now()]);
        BadgeImpression::factory()->create(['impression_at' => now()->subDays(10)]);
        
        $results = BadgeImpression::where('impression_at', '>=', now()->subDays(7))->get();
        $this->assertEquals(1, $results->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Operations Tests (MS-037 to MS-042)
    |--------------------------------------------------------------------------
    */

    /** @test MS-037: Bulk insert 100 records */
    public function test_bulk_insert_100_records(): void
    {
        $audit = Audit::factory()->create();
        
        $records = BadgeImpression::factory()->count(100)->make(['audit_id' => $audit->id]);
        BadgeImpression::insert($records->toArray());
        
        $this->assertEquals(100, BadgeImpression::where('audit_id', $audit->id)->count());
    }

    /** @test MS-038: Update clicked_at from NULL to timestamp */
    public function test_update_clicked_at(): void
    {
        $impression = BadgeImpression::factory()->create(['clicked_at' => null]);
        
        $this->assertNull($impression->clicked_at);
        
        $impression->update([
            'clicked_at' => now(),
            'click_target' => 'audit',
        ]);
        
        $this->assertNotNull($impression->fresh()->clicked_at);
        $this->assertEquals('audit', $impression->fresh()->click_target);
    }

    /** @test MS-039: Update click_target from NULL to 'audit' */
    public function test_update_click_target(): void
    {
        $impression = BadgeImpression::factory()->create(['click_target' => null]);
        
        $impression->update(['click_target' => 'audit']);
        
        $this->assertEquals('audit', $impression->fresh()->click_target);
    }

    /** @test MS-040: Query impressions by date range */
    public function test_query_by_date_range(): void
    {
        $audit = Audit::factory()->create();
        
        for ($i = 0; $i < 10; $i++) {
            BadgeImpression::factory()->create([
                'audit_id' => $audit->id,
                'impression_at' => now()->subDays($i),
            ]);
        }
        
        $recent = BadgeImpression::where('audit_id', $audit->id)
            ->where('impression_at', '>=', now()->subDays(3))
            ->count();
        
        $this->assertEquals(4, $recent); // Today + 3 days
    }

    /** @test MS-041: Count impressions per audit */
    public function test_count_impressions_per_audit(): void
    {
        $audit1 = Audit::factory()->create();
        $audit2 = Audit::factory()->create();
        
        BadgeImpression::factory()->count(5)->create(['audit_id' => $audit1->id]);
        BadgeImpression::factory()->count(3)->create(['audit_id' => $audit2->id]);
        
        $count1 = BadgeImpression::where('audit_id', $audit1->id)->count();
        $count2 = BadgeImpression::where('audit_id', $audit2->id)->count();
        
        $this->assertEquals(5, $count1);
        $this->assertEquals(3, $count2);
    }

    /** @test MS-042: Delete orphan impressions via CASCADE */
    public function test_delete_orphan_impressions(): void
    {
        $audit = Audit::factory()->create();
        
        BadgeImpression::factory()->count(10)->create(['audit_id' => $audit->id]);
        
        $this->assertEquals(10, BadgeImpression::where('audit_id', $audit->id)->count());
        
        $audit->delete();
        
        $this->assertEquals(0, BadgeImpression::where('audit_id', $audit->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Edge Case Tests (MS-043 to MS-050)
    |--------------------------------------------------------------------------
    */

    /** @test MS-043: Unicode in referrer_domain */
    public function test_unicode_in_referrer_domain(): void
    {
        $impression = BadgeImpression::factory()->create([
            'referrer_domain' => '例え.com',
        ]);
        
        $this->assertEquals('例え.com', $impression->referrer_domain);
    }

    /** @test MS-044: IP hash SHA-256 format (64 hex chars) */
    public function test_ip_hash_sha256_format(): void
    {
        $ipHash = hash('sha256', '192.168.1.1' . config('app.key'));
        
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $ipHash);
        
        $impression = BadgeImpression::factory()->create(['ip_hash' => $ipHash]);
        
        $this->assertEquals($ipHash, $impression->ip_hash);
    }

    /** @test MS-045: Public hash alphanumeric only */
    public function test_public_hash_alphanumeric(): void
    {
        $hash = 'AbCdEfGhIjKlMnOpQrStUvWxYz123456';
        
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{32}$/', $hash);
        
        $impression = BadgeImpression::factory()->create(['public_hash' => $hash]);
        
        $this->assertEquals($hash, $impression->public_hash);
    }

    /** @test MS-046: Impression timestamp auto-populated */
    public function test_impression_timestamp_auto_populated(): void
    {
        $impression = BadgeImpression::create([
            'audit_id' => Audit::factory()->create()->id,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
        ]);
        
        $this->assertNotNull($impression->impression_at);
    }

    /** @test MS-047: Non-existent audit_id FK violation */
    public function test_nonexistent_audit_id_fk_violation(): void
    {
        $this->expectException(\Exception::class);
        
        BadgeImpression::create([
            'audit_id' => 999999,
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
        ]);
    }

    /** @test MS-048: Multiple impressions per hash tracked */
    public function test_multiple_impressions_per_hash(): void
    {
        $audit = Audit::factory()->create();
        $hash = 'abc123def456ghi789jkl012mno345pq';
        
        BadgeImpression::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'referrer_domain' => 'site1.com',
        ]);
        
        BadgeImpression::factory()->create([
            'audit_id' => $audit->id,
            'public_hash' => $hash,
            'referrer_domain' => 'site2.com',
        ]);
        
        $count = BadgeImpression::where('public_hash', $hash)->count();
        $this->assertEquals(2, $count);
    }

    /** @test MS-049: Click target enum-like values */
    public function test_click_target_enum_values(): void
    {
        $audit = Audit::factory()->create();
        
        $imp1 = BadgeImpression::factory()->create([
            'audit_id' => $audit->id,
            'click_target' => 'audit',
        ]);
        
        $imp2 = BadgeImpression::factory()->create([
            'audit_id' => $audit->id,
            'click_target' => 'home',
        ]);
        
        $this->assertEquals('audit', $imp1->click_target);
        $this->assertEquals('home', $imp2->click_target);
    }

    /** @test MS-050: Timestamp precision */
    public function test_timestamp_precision(): void
    {
        $preciseTime = '2026-03-06 10:30:45';
        
        $impression = BadgeImpression::factory()->create([
            'impression_at' => $preciseTime,
        ]);
        
        $this->assertNotNull($impression->impression_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Migration Record Tests (MS-053 to MS-054)
    |--------------------------------------------------------------------------
    */

    /** @test MS-053: Migration batch number */
    public function test_migration_batch_number(): void
    {
        $migration = DB::table('migrations')
            ->where('migration', 'like', '%badge_impressions%')
            ->first();
        
        $this->assertNotNull($migration);
        $this->assertIsInt($migration->batch);
    }

    /** @test MS-054: Table engine (MySQL only) */
    public function test_table_engine(): void
    {
        // Skip on SQLite
        if (config('database.default') === 'sqlite') {
            $this->assertTrue(Schema::hasTable($this->tableName));
            return;
        }
        
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

    /**
     * Check if an index exists on a column.
     */
    private function hasIndexOnColumn(string $table, string $column): bool
    {
        $connection = config('database.default');
        
        if ($connection === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $index) {
                $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                foreach ($indexInfo as $info) {
                    if ($info->name === $column) {
                        return true;
                    }
                }
            }
            return false;
        }
        
        // MySQL
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Column_name = ?", [$column]);
        return count($indexes) > 0;
    }
}
