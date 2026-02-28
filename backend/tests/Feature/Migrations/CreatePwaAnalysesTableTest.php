<?php

namespace Tests\Feature\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Test suite for TASK-24-1: Database Migration - PWA Analyses Table
 *
 * This test validates the migration that creates the pwa_analyses table
 * for storing cached PWA manifest analysis results.
 *
 * Test Categories:
 * - SC-001 to SC-011: Schema Structure
 * - FK-001 to FK-003: Foreign Key Constraints
 * - IX-001 to IX-003: Indexes
 * - EV-001 to EV-004: Enum Validation
 * - RB-001 to RB-002: Migration Rollback
 * - JH-001 to JH-004: JSON Handling
 * - SR-001 to SR-003: Score Range Validation
 * - SEC-001 to SEC-003: Security Tests
 *
 * @see TASK-24-1
 * @see SPEC-024
 * @see FEAT-024
 */
class CreatePwaAnalysesTableTest extends TestCase
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
     * Create a test site.
     */
    protected function createSite(array $attributes = []): int
    {
        // Ensure we have a user_id
        if (!isset($attributes['user_id'])) {
            $userId = $this->createUser();
            $attributes['user_id'] = $userId;
        }

        return DB::table('sites')->insertGetId(array_merge([
            'url' => 'https://example-' . uniqid() . '.com',
            'name' => 'Test Site',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Run the pwa_analyses migration.
     */
    protected function runPwaAnalysesMigration(): void
    {
        $this->artisan('migrate', ['--path' => 'database/migrations']);
    }

    /**
     * Rollback the pwa_analyses migration.
     */
    protected function rollbackPwaAnalysesMigration(): void
    {
        $this->artisan('migrate:rollback', ['--step' => 1]);
    }

    /**
     * Create a test pwa_analysis record.
     */
    protected function createPwaAnalysis(int $siteId, array $attributes = []): int
    {
        return DB::table('pwa_analyses')->insertGetId(array_merge([
            'site_id' => $siteId,
            'has_manifest' => 1,
            'manifest_valid' => 1,
            'pwa_score' => 85,
            'readiness_level' => 'enhanced',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    // =========================================================================
    // Category 1: Schema Structure (SC-001 to SC-011)
    // =========================================================================

    /**
     * SC-001: Migration creates pwa_analyses table.
     */
    public function test_sc001_migration_creates_pwa_analyses_table(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(
            Schema::hasTable('pwa_analyses'),
            'pwa_analyses table should exist after migration'
        );
    }

    /**
     * SC-002: Primary key column is correct.
     */
    public function test_sc002_primary_key_column_is_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(
            Schema::hasColumn('pwa_analyses', 'id'),
            'id column should exist'
        );

        // Verify auto-increment
        $siteId = $this->createSite();
        $id1 = $this->createPwaAnalysis($siteId);
        $id2 = $this->createPwaAnalysis($siteId);

        $this->assertGreaterThan($id1, $id2, 'id should auto-increment');
    }

    /**
     * SC-003: site_id column is correct.
     */
    public function test_sc003_site_id_column_is_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(
            Schema::hasColumn('pwa_analyses', 'site_id'),
            'site_id column should exist'
        );

        // Verify NOT NULL constraint
        $siteId = $this->createSite();
        $analysisId = $this->createPwaAnalysis($siteId);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertNotNull($analysis->site_id, 'site_id should not be null');
    }

    /**
     * SC-004: Boolean field columns are correct.
     */
    public function test_sc004_boolean_field_columns_are_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $booleanFields = [
            'has_manifest',
            'manifest_valid',
            'has_name',
            'has_short_name',
            'has_start_url',
            'has_display',
            'has_background_color',
            'has_theme_color',
            'has_description',
            'icons_192_present',
            'icons_512_present',
            'icons_180_present',
        ];

        foreach ($booleanFields as $field) {
            $this->assertTrue(
                Schema::hasColumn('pwa_analyses', $field),
                "{$field} column should exist"
            );
        }

        // Test default value is 0
        $siteId = $this->createSite();
        $analysisId = DB::table('pwa_analyses')->insertGetId([
            'site_id' => $siteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        foreach ($booleanFields as $field) {
            $this->assertEquals(0, $analysis->$field, "{$field} should default to 0");
        }
    }

    /**
     * SC-005: manifest_url column is correct.
     */
    public function test_sc005_manifest_url_column_is_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(
            Schema::hasColumn('pwa_analyses', 'manifest_url'),
            'manifest_url column should exist'
        );

        // Test NULL allowed
        $siteId = $this->createSite();
        $analysisId = $this->createPwaAnalysis($siteId, ['manifest_url' => null]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertNull($analysis->manifest_url, 'manifest_url should allow NULL');

        // Test valid URL
        $analysisId = $this->createPwaAnalysis($siteId, [
            'manifest_url' => 'https://example.com/manifest.json',
        ]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertEquals(
            'https://example.com/manifest.json',
            $analysis->manifest_url,
            'manifest_url should store valid URL'
        );
    }

    /**
     * SC-006: manifest_json column is correct.
     */
    public function test_sc006_manifest_json_column_is_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(
            Schema::hasColumn('pwa_analyses', 'manifest_json'),
            'manifest_json column should exist'
        );

        // Test valid JSON
        $siteId = $this->createSite();
        $manifestJson = json_encode(['name' => 'Test App', 'short_name' => 'Test']);
        $analysisId = $this->createPwaAnalysis($siteId, ['manifest_json' => $manifestJson]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $decoded = json_decode($analysis->manifest_json, true);
        $this->assertEquals('Test App', $decoded['name'], 'manifest_json should store valid JSON');
    }

    /**
     * SC-007: icon_errors column is correct.
     */
    public function test_sc007_icon_errors_column_is_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(
            Schema::hasColumn('pwa_analyses', 'icon_errors'),
            'icon_errors column should exist'
        );

        // Test valid JSON array
        $siteId = $this->createSite();
        $iconErrors = json_encode([
            ['size' => '192x192', 'error' => 'Icon not found'],
        ]);
        $analysisId = $this->createPwaAnalysis($siteId, ['icon_errors' => $iconErrors]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $decoded = json_decode($analysis->icon_errors, true);
        $this->assertCount(1, $decoded, 'icon_errors should store valid JSON array');
    }

    /**
     * SC-008: Icon count columns are correct.
     */
    public function test_sc008_icon_count_columns_are_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(
            Schema::hasColumn('pwa_analyses', 'icons_total'),
            'icons_total column should exist'
        );
        $this->assertTrue(
            Schema::hasColumn('pwa_analyses', 'icons_valid'),
            'icons_valid column should exist'
        );

        // Test default value is 0
        $siteId = $this->createSite();
        $analysisId = DB::table('pwa_analyses')->insertGetId([
            'site_id' => $siteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertEquals(0, $analysis->icons_total, 'icons_total should default to 0');
        $this->assertEquals(0, $analysis->icons_valid, 'icons_valid should default to 0');
    }

    /**
     * SC-009: pwa_score column is correct.
     */
    public function test_sc009_pwa_score_column_is_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(
            Schema::hasColumn('pwa_analyses', 'pwa_score'),
            'pwa_score column should exist'
        );

        // Test default value is 0
        $siteId = $this->createSite();
        $analysisId = DB::table('pwa_analyses')->insertGetId([
            'site_id' => $siteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertEquals(0, $analysis->pwa_score, 'pwa_score should default to 0');
    }

    /**
     * SC-010: readiness_level column is correct.
     */
    public function test_sc010_readiness_level_column_is_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(
            Schema::hasColumn('pwa_analyses', 'readiness_level'),
            'readiness_level column should exist'
        );

        // Test default value is 'none'
        $siteId = $this->createSite();
        $analysisId = DB::table('pwa_analyses')->insertGetId([
            'site_id' => $siteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertEquals('none', $analysis->readiness_level, 'readiness_level should default to none');
    }

    /**
     * SC-011: Timestamp columns are correct.
     */
    public function test_sc011_timestamp_columns_are_correct(): void
    {
        $this->runPwaAnalysesMigration();

        $timestampColumns = ['analyzed_at', 'created_at', 'updated_at'];

        foreach ($timestampColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('pwa_analyses', $column),
                "{$column} column should exist"
            );
        }

        // Test timestamps are set
        $siteId = $this->createSite();
        $analysisId = $this->createPwaAnalysis($siteId);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertNotNull($analysis->created_at, 'created_at should be set');
        $this->assertNotNull($analysis->updated_at, 'updated_at should be set');
    }

    // =========================================================================
    // Category 2: Foreign Key Constraints (FK-001 to FK-003)
    // =========================================================================

    /**
     * FK-001: Foreign key to sites.id.
     */
    public function test_fk001_foreign_key_to_sites_id(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();

        // Insert with valid site_id should succeed
        $analysisId = $this->createPwaAnalysis($siteId);
        $this->assertNotNull($analysisId, 'Insert with valid site_id should succeed');

        // Insert with invalid site_id should fail
        $this->expectException(\Exception::class);
        $this->createPwaAnalysis(999999);
    }

    /**
     * FK-002: Cascade delete behavior.
     */
    public function test_fk002_cascade_delete_behavior(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $analysisId = $this->createPwaAnalysis($siteId);

        // Verify record exists
        $this->assertDatabaseHas('pwa_analyses', ['id' => $analysisId]);

        // Delete site
        DB::table('sites')->where('id', $siteId)->delete();

        // Verify analysis was cascade deleted
        $this->assertDatabaseMissing('pwa_analyses', ['id' => $analysisId]);
    }

    /**
     * FK-003: Multiple analyses per site allowed.
     */
    public function test_fk003_multiple_analyses_per_site_allowed(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();

        // Create multiple analyses with different analyzed_at timestamps
        $analysis1 = $this->createPwaAnalysis($siteId, [
            'analyzed_at' => now()->subDay(),
        ]);
        $analysis2 = $this->createPwaAnalysis($siteId, [
            'analyzed_at' => now(),
        ]);

        $this->assertNotEquals($analysis1, $analysis2, 'Should allow multiple analyses per site');

        $count = DB::table('pwa_analyses')
            ->where('site_id', $siteId)
            ->count();

        $this->assertEquals(2, $count, 'Should have 2 analyses for site');
    }

    // =========================================================================
    // Category 3: Indexes (IX-001 to IX-003)
    // =========================================================================

    /**
     * IX-001: Composite index on (site_id, analyzed_at).
     */
    public function test_ix001_composite_index_site_id_analyzed_at(): void
    {
        $this->runPwaAnalysesMigration();

        $indexes = $this->getTableIndexes('pwa_analyses');

        $found = false;
        foreach ($indexes as $index) {
            if (in_array('site_id', $index['columns']) && in_array('analyzed_at', $index['columns'])) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Composite index on (site_id, analyzed_at) should exist'
        );
    }

    /**
     * IX-002: Index on pwa_score.
     */
    public function test_ix002_index_on_pwa_score(): void
    {
        $this->runPwaAnalysesMigration();

        $indexes = $this->getTableIndexes('pwa_analyses');

        $found = false;
        foreach ($indexes as $index) {
            if (in_array('pwa_score', $index['columns'])) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Index on pwa_score should exist'
        );
    }

    /**
     * IX-003: Foreign key index on site_id.
     */
    public function test_ix003_foreign_key_index_on_site_id(): void
    {
        $this->runPwaAnalysesMigration();

        $indexes = $this->getTableIndexes('pwa_analyses');

        $found = false;
        foreach ($indexes as $index) {
            if (in_array('site_id', $index['columns'])) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Index on site_id (FK) should exist'
        );
    }

    /**
     * Helper: Get table indexes.
     */
    protected function getTableIndexes(string $table): array
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: Query sqlite_master for indexes
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
    // Category 4: Enum Validation (EV-001 to EV-004)
    // =========================================================================

    /**
     * EV-001: Valid readiness_level values.
     */
    public function test_ev001_valid_readiness_level_values(): void
    {
        $this->runPwaAnalysesMigration();

        $validValues = ['none', 'basic', 'enhanced', 'full'];
        $siteId = $this->createSite();

        foreach ($validValues as $value) {
            $analysisId = $this->createPwaAnalysis($siteId, ['readiness_level' => $value]);
            $this->assertNotNull($analysisId, "readiness_level '{$value}' should be valid");
        }
    }

    /**
     * EV-002: Invalid readiness_level rejected.
     */
    public function test_ev002_invalid_readiness_level_rejected(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();

        $this->expectException(\Exception::class);
        $this->createPwaAnalysis($siteId, ['readiness_level' => 'invalid']);
    }

    /**
     * EV-003: Default readiness_level is 'none'.
     */
    public function test_ev003_default_readiness_level_is_none(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $analysisId = DB::table('pwa_analyses')->insertGetId([
            'site_id' => $siteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertEquals('none', $analysis->readiness_level);
    }

    /**
     * EV-004: Case sensitivity for readiness_level.
     */
    public function test_ev004_case_sensitivity_for_readiness_level(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();

        $this->expectException(\Exception::class);
        $this->createPwaAnalysis($siteId, ['readiness_level' => 'BASIC']);
    }

    // =========================================================================
    // Category 5: Migration Rollback (RB-001 to RB-002)
    // =========================================================================

    /**
     * RB-001: Migration down drops table.
     */
    public function test_rb001_migration_down_drops_table(): void
    {
        $this->runPwaAnalysesMigration();

        $this->assertTrue(Schema::hasTable('pwa_analyses'), 'Table should exist after migration');

        $this->rollbackPwaAnalysesMigration();

        $this->assertFalse(
            Schema::hasTable('pwa_analyses'),
            'Table should not exist after rollback'
        );
    }

    /**
     * RB-002: Migration re-run after rollback.
     */
    public function test_rb002_migration_rerun_after_rollback(): void
    {
        // Run migration
        $this->runPwaAnalysesMigration();
        $this->assertTrue(Schema::hasTable('pwa_analyses'));

        // Rollback
        $this->rollbackPwaAnalysesMigration();
        $this->assertFalse(Schema::hasTable('pwa_analyses'));

        // Re-run migration
        $this->runPwaAnalysesMigration();
        $this->assertTrue(
            Schema::hasTable('pwa_analyses'),
            'Table should exist after re-run'
        );

        // Verify schema is correct
        $this->assertTrue(Schema::hasColumn('pwa_analyses', 'id'));
        $this->assertTrue(Schema::hasColumn('pwa_analyses', 'site_id'));
        $this->assertTrue(Schema::hasColumn('pwa_analyses', 'pwa_score'));
        $this->assertTrue(Schema::hasColumn('pwa_analyses', 'readiness_level'));
    }

    // =========================================================================
    // Category 6: JSON Handling (JH-001 to JH-004)
    // =========================================================================

    /**
     * JH-001: Valid JSON in manifest_json.
     */
    public function test_jh001_valid_json_in_manifest_json(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $manifestJson = json_encode([
            'name' => 'Test App',
            'short_name' => 'Test',
            'start_url' => '/',
            'display' => 'standalone',
            'icons' => [
                ['src' => '/icon-192.png', 'sizes' => '192x192'],
            ],
        ]);

        $analysisId = $this->createPwaAnalysis($siteId, ['manifest_json' => $manifestJson]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $decoded = json_decode($analysis->manifest_json, true);

        $this->assertEquals('Test App', $decoded['name']);
        $this->assertCount(1, $decoded['icons']);
    }

    /**
     * JH-002: Null JSON values allowed.
     */
    public function test_jh002_null_json_values_allowed(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $analysisId = $this->createPwaAnalysis($siteId, [
            'manifest_json' => null,
            'icon_errors' => null,
        ]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertNull($analysis->manifest_json);
        $this->assertNull($analysis->icon_errors);
    }

    /**
     * JH-003: Empty JSON object.
     */
    public function test_jh003_empty_json_object(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $analysisId = $this->createPwaAnalysis($siteId, ['manifest_json' => '{}']);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $decoded = json_decode($analysis->manifest_json, true);

        $this->assertIsArray($decoded);
        $this->assertEmpty($decoded);
    }

    /**
     * JH-004: Nested JSON object.
     */
    public function test_jh004_nested_json_object(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $manifestJson = json_encode([
            'name' => 'App',
            'icons' => [
                [
                    'src' => '/icon.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
            ],
            'screenshots' => [
                [
                    'src' => '/screenshot1.png',
                    'sizes' => '1280x720',
                ],
            ],
        ]);

        $analysisId = $this->createPwaAnalysis($siteId, ['manifest_json' => $manifestJson]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $decoded = json_decode($analysis->manifest_json, true);

        $this->assertEquals('/icon.png', $decoded['icons'][0]['src']);
        $this->assertEquals('1280x720', $decoded['screenshots'][0]['sizes']);
    }

    // =========================================================================
    // Category 7: Score Range Validation (SR-001 to SR-003)
    // =========================================================================

    /**
     * SR-001: pwa_score minimum value (0).
     */
    public function test_sr001_pwa_score_minimum_value(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $analysisId = $this->createPwaAnalysis($siteId, ['pwa_score' => 0]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertEquals(0, $analysis->pwa_score);
    }

    /**
     * SR-002: pwa_score maximum value (100).
     */
    public function test_sr002_pwa_score_maximum_value(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $analysisId = $this->createPwaAnalysis($siteId, ['pwa_score' => 100]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertEquals(100, $analysis->pwa_score);
    }

    /**
     * SR-003: pwa_score out of range (stored, application should validate).
     */
    public function test_sr003_pwa_score_out_of_range(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();

        // TINYINT UNSIGNED can store 0-255, so 101 is accepted
        $analysisId = $this->createPwaAnalysis($siteId, ['pwa_score' => 101]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertEquals(101, $analysis->pwa_score);

        // Note: Application layer should validate 0-100 range
    }

    // =========================================================================
    // Category 8: Security Tests (SEC-001 to SEC-003)
    // =========================================================================

    /**
     * SEC-001: SQL injection prevention.
     */
    public function test_sec001_sql_injection_prevention(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $maliciousUrl = "https://example.com/manifest.json'; DROP TABLE pwa_analyses; --";

        $analysisId = $this->createPwaAnalysis($siteId, ['manifest_url' => $maliciousUrl]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $this->assertEquals($maliciousUrl, $analysis->manifest_url);

        // Table should still exist
        $this->assertTrue(Schema::hasTable('pwa_analyses'));
    }

    /**
     * SEC-002: XSS prevention in JSON.
     */
    public function test_sec002_xss_prevention_in_json(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();
        $manifestJson = json_encode([
            'name' => '<script>alert("xss")</script>',
        ]);

        $analysisId = $this->createPwaAnalysis($siteId, ['manifest_json' => $manifestJson]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $decoded = json_decode($analysis->manifest_json, true);

        // Script tag should be stored as string, not executed
        $this->assertEquals('<script>alert("xss")</script>', $decoded['name']);
    }

    /**
     * SEC-003: Large JSON handling.
     */
    public function test_sec003_large_json_handling(): void
    {
        $this->runPwaAnalysesMigration();

        $siteId = $this->createSite();

        // Create a large JSON object (~50KB)
        $largeArray = array_fill(0, 1000, [
            'src' => str_repeat('a', 50),
            'sizes' => '192x192',
            'type' => 'image/png',
        ]);
        $manifestJson = json_encode(['icons' => $largeArray]);

        $analysisId = $this->createPwaAnalysis($siteId, ['manifest_json' => $manifestJson]);

        $analysis = DB::table('pwa_analyses')->find($analysisId);
        $decoded = json_decode($analysis->manifest_json, true);

        $this->assertCount(1000, $decoded['icons']);
    }
}
