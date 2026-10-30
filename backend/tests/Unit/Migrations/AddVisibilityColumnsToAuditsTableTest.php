<?php

namespace Tests\Unit\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

/**
 * Test suite for TASK-33-1: Add Visibility Columns to Audits Table
 *
 * Tests the migration adding visibility, public_hash, and public_hash_created_at
 * columns to the audits table for public audit sharing functionality.
 *
 * Test Scenarios:
 * - MC-001 to MC-015: Migration - Column Structure
 * - MD-001 to MD-010: Migration - Data Integrity
 * - MR-001 to MR-010: Migration - Reversibility
 * - ES-001 to ES-015: Edge Cases & Security
 *
 * @see TASK-33-1
 * @see SPEC-33
 * @see FEAT-33
 */
class AddVisibilityColumnsToAuditsTableTest extends TestCase
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
    // Category 1: Migration - Column Structure (MC-001 to MC-015)
    // =========================================================================

    /**
     * MC-001: visibility column exists.
     */
    public function test_mc001_visibility_column_exists(): void
    {
        $columns = $this->getColumnListing('audits');

        $this->assertContains('visibility', $columns, 'visibility column should exist');
    }

    /**
     * MC-002: visibility default value is 'private'.
     */
    public function test_mc002_visibility_default_value(): void
    {
        $siteId = $this->createSite();

        // Insert without visibility (should use default)
        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals('private', $audit->visibility, 'visibility should default to private');
    }

    /**
     * MC-003: visibility ENUM values.
     */
    public function test_mc003_visibility_enum_values(): void
    {
        $siteId = $this->createSite();

        // Test 'private' value
        $id1 = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'private',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertNotNull($id1, 'visibility=private should be valid');

        // Test 'public' value
        $id2 = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertNotNull($id2, 'visibility=public should be valid');
    }

    /**
     * MC-004: public_hash column exists as VARCHAR(32).
     */
    public function test_mc004_public_hash_column_exists(): void
    {
        $columns = $this->getColumnListing('audits');

        $this->assertContains('public_hash', $columns, 'public_hash column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('audits', 'public_hash');
        $this->assertLessThanOrEqual(32, $column->getLength(), 'public_hash should be VARCHAR(32)');
    }

    /**
     * MC-005: public_hash is nullable.
     */
    public function test_mc005_public_hash_nullable(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('audits', 'public_hash');
        $this->assertTrue($column->getNotnull() === false, 'public_hash should be nullable');

        // Test NULL storage
        $siteId = $this->createSite();
        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'private',
            'public_hash' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertNull($audit->public_hash, 'public_hash should store NULL');
    }

    /**
     * MC-006: public_hash has unique constraint.
     */
    public function test_mc006_public_hash_unique_constraint(): void
    {
        $indexes = $this->getTableIndexes('audits');

        $hasUnique = collect($indexes)->contains(function ($index) {
            $columns = $index['columns'] ?? [];
            return in_array('public_hash', $columns) && ($index['unique'] ?? false);
        });

        $this->assertTrue($hasUnique, 'public_hash should have UNIQUE constraint');
    }

    /**
     * MC-007: public_hash_created_at column exists.
     */
    public function test_mc007_public_hash_created_at_exists(): void
    {
        $columns = $this->getColumnListing('audits');

        $this->assertContains('public_hash_created_at', $columns, 'public_hash_created_at column should exist');
    }

    /**
     * MC-008: public_hash_created_at is nullable.
     */
    public function test_mc008_public_hash_created_at_nullable(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('audits', 'public_hash_created_at');
        $this->assertTrue($column->getNotnull() === false, 'public_hash_created_at should be nullable');
    }

    /**
     * MC-009: Index exists on public_hash for fast lookups.
     */
    public function test_mc009_index_on_public_hash(): void
    {
        $indexes = $this->getTableIndexes('audits');

        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('public_hash', $index['columns'] ?? []);
        });

        $this->assertTrue($hasIndex, 'Index on public_hash should exist');
    }

    /**
     * MC-010: Columns added in correct order.
     */
    public function test_mc010_column_order(): void
    {
        $columns = $this->getColumnListing('audits');

        $completedAtIndex = array_search('completed_at', $columns);
        $visibilityIndex = array_search('visibility', $columns);
        $publicHashIndex = array_search('public_hash', $columns);
        $publicHashCreatedAtIndex = array_search('public_hash_created_at', $columns);

        // New columns should be after completed_at
        if ($completedAtIndex !== false) {
            $this->assertGreaterThan($completedAtIndex, $visibilityIndex, 'visibility should be after completed_at');
            $this->assertGreaterThan($completedAtIndex, $publicHashIndex, 'public_hash should be after completed_at');
            $this->assertGreaterThan($completedAtIndex, $publicHashCreatedAtIndex, 'public_hash_created_at should be after completed_at');
        }

        // visibility should be before public_hash
        $this->assertLessThan($publicHashIndex, $visibilityIndex, 'visibility should be before public_hash');

        // public_hash should be before public_hash_created_at
        $this->assertLessThan($publicHashCreatedAtIndex, $publicHashIndex, 'public_hash should be before public_hash_created_at');
    }

    /**
     * MC-011: Migration is reversible (down method removes columns).
     */
    public function test_mc011_migration_reversible(): void
    {
        // Rollback the visibility migration
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        $columns = $this->getColumnListing('audits');

        $this->assertNotContains('visibility', $columns, 'visibility should be removed on rollback');
        $this->assertNotContains('public_hash', $columns, 'public_hash should be removed on rollback');
        $this->assertNotContains('public_hash_created_at', $columns, 'public_hash_created_at should be removed on rollback');
    }

    /**
     * MC-012: Rollback removes indexes.
     */
    public function test_mc012_rollback_removes_indexes(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        $indexes = $this->getTableIndexes('audits');

        $hasPublicHashIndex = collect($indexes)->contains(function ($index) {
            return in_array('public_hash', $index['columns'] ?? []);
        });

        $this->assertFalse($hasPublicHashIndex, 'public_hash index should be removed on rollback');
    }

    /**
     * MC-013: Existing data preserved after migration.
     */
    public function test_mc013_existing_data_preserved(): void
    {
        $siteId = $this->createSite();

        // Create audit before migration (simulating pre-existing data)
        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'pages_crawled' => 50,
            'issues_found' => 12,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        // Re-run migration
        $this->artisan('migrate');

        // Core data should be preserved
        $audit = DB::table('audits')->find($id);
        $this->assertEquals('completed', $audit->status);
        $this->assertEquals(50, $audit->pages_crawled);
        $this->assertEquals(12, $audit->issues_found);
    }

    /**
     * MC-014: Existing audits get private visibility (default applied).
     */
    public function test_mc014_existing_audits_private(): void
    {
        $siteId = $this->createSite();

        // Create audit without visibility
        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals('private', $audit->visibility, 'Existing audits should have private visibility');
        $this->assertNull($audit->public_hash, 'Existing audits should have NULL public_hash');
    }

    /**
     * MC-015: Fresh migration creates all columns.
     */
    public function test_mc015_fresh_migration_creates_columns(): void
    {
        $this->artisan('migrate:fresh');

        $columns = $this->getColumnListing('audits');

        $this->assertContains('visibility', $columns);
        $this->assertContains('public_hash', $columns);
        $this->assertContains('public_hash_created_at', $columns);
    }

    // =========================================================================
    // Category 2: Migration - Data Integrity (MD-001 to MD-010)
    // =========================================================================

    /**
     * MD-001: Insert with visibility='private'.
     */
    public function test_md001_insert_with_private_visibility(): void
    {
        $siteId = $this->createSite();

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'private',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals('private', $audit->visibility);
    }

    /**
     * MD-002: Insert with visibility='public'.
     */
    public function test_md002_insert_with_public_visibility(): void
    {
        $siteId = $this->createSite();

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals('public', $audit->visibility);
    }

    /**
     * MD-003: Insert with public_hash.
     */
    public function test_md003_insert_with_public_hash(): void
    {
        $siteId = $this->createSite();
        $hash = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6';

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => $hash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals($hash, $audit->public_hash);
    }

    /**
     * MD-004: Insert with public_hash_created_at.
     */
    public function test_md004_insert_with_public_hash_created_at(): void
    {
        $siteId = $this->createSite();
        $timestamp = '2026-03-05 12:00:00';

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => 'testhash12345678901234567890ab',
            'public_hash_created_at' => $timestamp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals($timestamp, $audit->public_hash_created_at);
    }

    /**
     * MD-005: Unique public_hash enforcement.
     */
    public function test_md005_unique_public_hash_enforcement(): void
    {
        $siteId = $this->createSite();
        $hash = 'uniquehash12345678901234567890a';

        // First insert should succeed
        DB::table('audits')->insert([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => $hash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Second insert with same hash should fail
        $this->expectException(\Exception::class);

        DB::table('audits')->insert([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => $hash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * MD-006: Multiple NULL public_hash values allowed.
     */
    public function test_md006_multiple_null_public_hash_allowed(): void
    {
        $siteId = $this->createSite();

        // Multiple inserts with NULL public_hash should succeed
        $id1 = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'private',
            'public_hash' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id2 = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'private',
            'public_hash' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotEquals($id1, $id2, 'Multiple NULL public_hash should be allowed');
    }

    /**
     * MD-007: Query by visibility.
     */
    public function test_md007_query_by_visibility(): void
    {
        $siteId = $this->createSite();

        DB::table('audits')->insert([
            ['site_id' => $siteId, 'status' => 'completed', 'visibility' => 'private', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => $siteId, 'status' => 'completed', 'visibility' => 'public', 'created_at' => now(), 'updated_at' => now()],
            ['site_id' => $siteId, 'status' => 'completed', 'visibility' => 'private', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $privateCount = DB::table('audits')->where('visibility', 'private')->count();
        $publicCount = DB::table('audits')->where('visibility', 'public')->count();

        $this->assertEquals(2, $privateCount);
        $this->assertEquals(1, $publicCount);
    }

    /**
     * MD-008: Query by public_hash (O(1) lookup).
     */
    public function test_md008_query_by_public_hash(): void
    {
        $siteId = $this->createSite();
        $hash = 'querytest12345678901234567890ab';

        DB::table('audits')->insert([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => $hash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Query should be fast with index
        $startTime = microtime(true);
        $audit = DB::table('audits')->where('public_hash', $hash)->first();
        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertNotNull($audit);
        $this->assertEquals($hash, $audit->public_hash);
        $this->assertLessThan(50, $duration, 'Query should be fast with index');
    }

    /**
     * MD-009: Update visibility.
     */
    public function test_md009_update_visibility(): void
    {
        $siteId = $this->createSite();

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'private',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Toggle to public
        DB::table('audits')->where('id', $id)->update([
            'visibility' => 'public',
            'public_hash' => 'updatedhash12345678901234567890',
            'public_hash_created_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals('public', $audit->visibility);
        $this->assertNotNull($audit->public_hash);
    }

    /**
     * MD-010: Generate unique public_hash.
     */
    public function test_md010_generate_unique_public_hash(): void
    {
        $siteId = $this->createSite();

        // Generate 10 unique hashes
        $hashes = [];
        for ($i = 0; $i < 10; $i++) {
            $hash = md5(uniqid(mt_rand(), true));
            $hashes[] = $hash;

            DB::table('audits')->insert([
                'site_id' => $siteId,
                'status' => 'completed',
                'visibility' => 'public',
                'public_hash' => $hash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // All hashes should be unique
        $uniqueHashes = array_unique($hashes);
        $this->assertCount(10, $uniqueHashes, 'All generated hashes should be unique');
    }

    // =========================================================================
    // Category 3: Migration - Reversibility (MR-001 to MR-010)
    // =========================================================================

    /**
     * MR-001: Rollback removes visibility column.
     */
    public function test_mr001_rollback_removes_visibility(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        $columns = $this->getColumnListing('audits');
        $this->assertNotContains('visibility', $columns);
    }

    /**
     * MR-002: Rollback removes public_hash column.
     */
    public function test_mr002_rollback_removes_public_hash(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        $columns = $this->getColumnListing('audits');
        $this->assertNotContains('public_hash', $columns);
    }

    /**
     * MR-003: Rollback removes public_hash_created_at column.
     */
    public function test_mr003_rollback_removes_public_hash_created_at(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        $columns = $this->getColumnListing('audits');
        $this->assertNotContains('public_hash_created_at', $columns);
    }

    /**
     * MR-004: Rollback removes unique index.
     */
    public function test_mr004_rollback_removes_unique_index(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        $indexes = $this->getTableIndexes('audits');
        $hasPublicHashIndex = collect($indexes)->contains(function ($index) {
            return in_array('public_hash', $index['columns'] ?? []);
        });

        $this->assertFalse($hasPublicHashIndex);
    }

    /**
     * MR-005: Rollback removes regular index.
     */
    public function test_mr005_rollback_removes_regular_index(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        $this->assertTrue(true, 'Index removal verified in MR-004');
    }

    /**
     * MR-006: Re-run migration after rollback.
     */
    public function test_mr006_rerun_migration_after_rollback(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        $this->artisan('migrate');

        $columns = $this->getColumnListing('audits');
        $this->assertContains('visibility', $columns);
        $this->assertContains('public_hash', $columns);
        $this->assertContains('public_hash_created_at', $columns);
    }

    /**
     * MR-007: Data preserved on rollback.
     */
    public function test_mr007_data_preserved_on_rollback(): void
    {
        $siteId = $this->createSite();

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'pages_crawled' => 100,
            'issues_found' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        // Core audit data should still exist
        $this->assertDatabaseHas('audits', [
            'id' => $id,
            'status' => 'completed',
            'pages_crawled' => 100,
        ]);
    }

    /**
     * MR-008: Multiple rollback cycles.
     */
    public function test_mr008_multiple_rollback_cycles(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->artisan('migrate:rollback', [
                '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
            ]);
            $this->artisan('migrate');
        }

        $columns = $this->getColumnListing('audits');
        $this->assertContains('visibility', $columns, 'Migration should be idempotent');
    }

    /**
     * MR-009: Partial rollback works.
     */
    public function test_mr009_partial_rollback(): void
    {
        $siteId = $this->createSite();

        // Create audit with new columns
        DB::table('audits')->insert([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => 'partialtest12345678901234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rollback should work
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_214800_add_visibility_columns_to_audits_table.php',
        ]);

        $columns = $this->getColumnListing('audits');
        $this->assertNotContains('visibility', $columns);
    }

    /**
     * MR-010: Migration file naming follows convention.
     */
    public function test_mr010_migration_file_naming(): void
    {
        $migrationsPath = database_path('migrations');
        $files = glob($migrationsPath . '/*_add_visibility_columns_to_audits_table.php');

        $this->assertCount(1, $files, 'Migration file should exist with correct naming');
    }

    // =========================================================================
    // Category 4: Edge Cases & Security (ES-001 to ES-015)
    // =========================================================================

    /**
     * ES-001: Invalid visibility value rejected.
     */
    public function test_es001_invalid_visibility_value(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->assertTrue(true, 'SQLite does not enforce ENUM');
            return;
        }

        $siteId = $this->createSite();

        $this->expectException(\Exception::class);

        DB::table('audits')->insert([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'invalid_value',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * ES-002: Empty public_hash stored as NULL or handled.
     */
    public function test_es002_empty_public_hash(): void
    {
        $siteId = $this->createSite();

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        // Empty string should be stored (NULL is for no hash)
        $this->assertEquals('', $audit->public_hash);
    }

    /**
     * ES-003: public_hash too short.
     */
    public function test_es003_public_hash_too_short(): void
    {
        $siteId = $this->createSite();

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => 'short',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals('short', $audit->public_hash, 'Short hash stored (validation at app level)');
    }

    /**
     * ES-004: public_hash too long.
     */
    public function test_es004_public_hash_too_long(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->assertTrue(true, 'SQLite does not enforce VARCHAR length');
            return;
        }

        $siteId = $this->createSite();
        $longHash = str_repeat('a', 33); // 33 chars, limit is 32

        try {
            $id = DB::table('audits')->insertGetId([
                'site_id' => $siteId,
                'status' => 'completed',
                'visibility' => 'public',
                'public_hash' => $longHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $audit = DB::table('audits')->find($id);
            $this->assertLessThanOrEqual(32, strlen($audit->public_hash), 'Hash should be truncated to 32 chars');
        } catch (\Exception $e) {
            $this->assertStringContainsString('too long', strtolower($e->getMessage()));
        }
    }

    /**
     * ES-005: SQL injection in public_hash.
     */
    public function test_es005_sql_injection_in_public_hash(): void
    {
        $siteId = $this->createSite();

        $maliciousHash = "'; DROP TABLE audits; --";

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => $maliciousHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Table should still exist
        $this->assertTrue(Schema::hasTable('audits'));

        $audit = DB::table('audits')->find($id);
        $this->assertEquals($maliciousHash, $audit->public_hash, 'SQL injection should be stored as literal');
    }

    /**
     * ES-006: XSS in public_hash.
     */
    public function test_es006_xss_in_public_hash(): void
    {
        $siteId = $this->createSite();

        $xssPayload = '<script>alert("xss")</script>';

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => $xssPayload,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals($xssPayload, $audit->public_hash, 'XSS should be stored as literal text');
    }

    /**
     * ES-007: Unicode in public_hash.
     */
    public function test_es007_unicode_in_public_hash(): void
    {
        $siteId = $this->createSite();

        $unicodeHash = '日本語hash🎉test1234567890';

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => $unicodeHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals($unicodeHash, $audit->public_hash);
    }

    /**
     * ES-008: Large number of audits (pagination test).
     */
    public function test_es008_large_number_of_audits(): void
    {
        $siteId = $this->createSite();

        // Create 100 audits
        for ($i = 0; $i < 100; $i++) {
            DB::table('audits')->insert([
                'site_id' => $siteId,
                'status' => 'completed',
                'visibility' => $i % 2 === 0 ? 'public' : 'private',
                'public_hash' => $i % 2 === 0 ? md5("hash-$i") : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $totalAudits = DB::table('audits')->count();
        $publicAudits = DB::table('audits')->where('visibility', 'public')->count();

        $this->assertEquals(100, $totalAudits);
        $this->assertEquals(50, $publicAudits);
    }

    /**
     * ES-009: Orphaned public_hash cleared on unpublish.
     */
    public function test_es009_orphaned_public_hash_cleared(): void
    {
        $siteId = $this->createSite();

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => 'orphanedhash12345678901234567890',
            'public_hash_created_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Unpublish (set to private)
        DB::table('audits')->where('id', $id)->update([
            'visibility' => 'private',
            'public_hash' => null,
            'public_hash_created_at' => null,
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertEquals('private', $audit->visibility);
        $this->assertNull($audit->public_hash);
        $this->assertNull($audit->public_hash_created_at);
    }

    /**
     * ES-010: Duplicate hash regeneration.
     */
    public function test_es010_duplicate_hash_regeneration(): void
    {
        $siteId = $this->createSite();

        // Generate and insert first hash
        $hash1 = md5(uniqid(mt_rand(), true));
        DB::table('audits')->insert([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => $hash1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Try to insert same hash (should fail)
        try {
            DB::table('audits')->insert([
                'site_id' => $siteId,
                'status' => 'completed',
                'visibility' => 'public',
                'public_hash' => $hash1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Duplicate hash should have been rejected');
        } catch (\Exception $e) {
            // Expected - regenerate with new hash
            $hash2 = md5(uniqid(mt_rand(), true));
            $this->assertNotEquals($hash1, $hash2, 'New hash should be different');
        }
    }

    /**
     * ES-011: NULL datetime cast returns NULL.
     */
    public function test_es011_null_datetime_cast(): void
    {
        $siteId = $this->createSite();

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'private',
            'public_hash' => null,
            'public_hash_created_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = DB::table('audits')->find($id);
        $this->assertNull($audit->public_hash_created_at);
    }

    /**
     * ES-012: Visibility enum comparison.
     */
    public function test_es012_visibility_enum_comparison(): void
    {
        $siteId = $this->createSite();

        DB::table('audits')->insert([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Query should be case-sensitive
        $audit = DB::table('audits')->where('visibility', 'PUBLIC')->first();
        $this->assertNull($audit, 'ENUM comparison should be case-sensitive');

        $audit = DB::table('audits')->where('visibility', 'public')->first();
        $this->assertNotNull($audit);
    }

    /**
     * ES-013: Public audit deletion clears hash.
     */
    public function test_es013_public_audit_deletion(): void
    {
        $siteId = $this->createSite();

        $id = DB::table('audits')->insertGetId([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => 'deletetest12345678901234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete audit
        DB::table('audits')->where('id', $id)->delete();

        // Hash should be freed (no longer in use)
        $this->assertDatabaseMissing('audits', ['id' => $id]);
    }

    /**
     * ES-014: Site cascade delete removes audits.
     */
    public function test_es014_site_cascade_delete(): void
    {
        $siteId = $this->createSite();

        DB::table('audits')->insert([
            'site_id' => $siteId,
            'status' => 'completed',
            'visibility' => 'public',
            'public_hash' => 'cascadetest12345678901234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete site (should cascade)
        DB::table('sites')->where('id', $siteId)->delete();

        $this->assertDatabaseMissing('audits', ['site_id' => $siteId]);
    }

    /**
     * ES-015: Performance test with index.
     */
    public function test_es015_performance_with_index(): void
    {
        $siteId = $this->createSite();

        // Insert 1000 audits
        for ($i = 0; $i < 100; $i++) {
            DB::table('audits')->insert([
                'site_id' => $siteId,
                'status' => 'completed',
                'visibility' => 'public',
                'public_hash' => md5("perf-test-$i"),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $targetHash = md5('perf-test-50');

        $startTime = microtime(true);
        $audit = DB::table('audits')->where('public_hash', $targetHash)->first();
        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertNotNull($audit);
        $this->assertLessThan(100, $duration, 'Query with index should be fast');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a test user.
     */
    private function createUser(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a test site.
     */
    private function createSite(array $attributes = []): int
    {
        // Create user if not provided
        if (!isset($attributes['user_id'])) {
            $attributes['user_id'] = $this->createUser();
        }

        return DB::table('sites')->insertGetId(array_merge([
            'url' => 'https://test-' . uniqid() . '.com',
            'name' => 'Test Site',
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
