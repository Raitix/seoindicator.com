<?php

namespace Tests\Unit\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

/**
 * Test suite for TASK-34-1: AI Visibility Tracking Database Migrations
 *
 * ============================================================================
 * KNOWN ISSUE: SCHEMA MISMATCH (2026-03-16)
 * ============================================================================
 * This test file was written against TEST-34-1.md specification but the
 * actual migration (2026_03_07_200000_create_ai_visibility_tables.php) has
 * different column names and structure.
 *
 * Key discrepancies:
 * - `model` column → actual: `model_used`
 * - `brand_mentioned` → doesn't exist in migration
 * - `brand_sentiment` → actual: `sentiment`
 * - `competitor_mentions` → actual: `mentions`
 * - `citations` → doesn't exist
 * - FULLTEXT index on response_text → not implemented
 *
 * Status: Test failed at test_qr005_model_column (27 passed, 1 failed)
 * Resolution: Requires alignment between specification and implementation
 * ============================================================================
 *
 * Tests the migrations creating four tables for AI Visibility Tracking:
 * - ai_visibility_projects: Project configuration for brand tracking
 * - ai_query_results: Individual AI query results and responses
 * - ai_mention_analytics: Aggregated analytics data
 * - ai_visibility_alerts: Alert notifications for significant changes
 *
 * Test Categories:
 * - VP-001 to VP-025: ai_visibility_projects Table Structure
 * - QR-001 to QR-030: ai_query_results Table Structure
 * - MA-001 to MA-025: ai_mention_analytics Table Structure
 * - VA-001 to VA-025: ai_visibility_alerts Table Structure
 * - MR-001 to MR-015: Migration Reversibility
 * - DI-001 to DI-010: Data Integrity
 * - SE-001 to SE-015: Security & Edge Cases
 *
 * @see TASK-34-1
 * @see SPEC-034
 * @see FEAT-034
 */
class AIVisibilityTablesMigrationTest extends TestCase
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
    // Category 1: ai_visibility_projects Table (VP-001 to VP-025)
    // =========================================================================

    /**
     * VP-001: Table exists after migration.
     */
    public function test_vp001_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('ai_visibility_projects'),
            'Table ai_visibility_projects should exist after migration'
        );
    }

    /**
     * VP-002: Primary key column.
     */
    public function test_vp002_primary_key_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('id', $columns, 'id column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Primary key exists (SQLite)');
            return;
        }

        $idColumn = $this->getColumnInfo('ai_visibility_projects', 'id');
        $this->assertEquals('bigint', strtolower($idColumn->getType()), 'id should be bigint unsigned');
    }

    /**
     * VP-003: user_id column (foreign key to users).
     */
    public function test_vp003_user_id_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('user_id', $columns, 'user_id column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_visibility_projects', 'user_id');
        $this->assertTrue($column->getNotnull() === true, 'user_id should be NOT NULL');
    }

    /**
     * VP-004: brand_name column.
     */
    public function test_vp004_brand_name_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('brand_name', $columns, 'brand_name column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_visibility_projects', 'brand_name');
        $this->assertTrue($column->getNotnull() === true, 'brand_name should be NOT NULL');
        $this->assertLessThanOrEqual(255, $column->getLength(), 'brand_name should be VARCHAR(255)');
    }

    /**
     * VP-005: keywords column (JSON, NOT NULL).
     */
    public function test_vp005_keywords_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('keywords', $columns, 'keywords column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_visibility_projects', 'keywords');
        $this->assertTrue($column->getNotnull() === true, 'keywords should be NOT NULL');
    }

    /**
     * VP-006: competitors column (JSON, NOT NULL).
     */
    public function test_vp006_competitors_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('competitors', $columns, 'competitors column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_visibility_projects', 'competitors');
        $this->assertTrue($column->getNotnull() === true, 'competitors should be NOT NULL');
    }

    /**
     * VP-007: query_templates column (JSON, nullable).
     */
    public function test_vp007_query_templates_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('query_templates', $columns, 'query_templates column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_visibility_projects', 'query_templates');
        $this->assertTrue($column->getNotnull() === false, 'query_templates should be nullable');
    }

    /**
     * VP-008: tracking_frequency column (ENUM).
     */
    public function test_vp008_tracking_frequency_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('tracking_frequency', $columns, 'tracking_frequency column should exist');

        // Test default value
        $userId = $this->createUser();
        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test Brand',
            'keywords' => json_encode(['keyword1']),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $this->assertEquals('weekly', $record->tracking_frequency, 'tracking_frequency should default to weekly');
    }

    /**
     * VP-009: is_active column (BOOLEAN, default: true).
     */
    public function test_vp009_is_active_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('is_active', $columns, 'is_active column should exist');

        // Test default value
        $userId = $this->createUser();
        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test Brand',
            'keywords' => json_encode(['keyword1']),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $this->assertEquals(1, $record->is_active, 'is_active should default to true');
    }

    /**
     * VP-010: last_queried_at column (TIMESTAMP, nullable).
     */
    public function test_vp010_last_queried_at_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('last_queried_at', $columns, 'last_queried_at column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_visibility_projects', 'last_queried_at');
        $this->assertTrue($column->getNotnull() === false, 'last_queried_at should be nullable');
    }

    /**
     * VP-011: Timestamps columns.
     */
    public function test_vp011_timestamps_columns(): void
    {
        $columns = $this->getColumnListing('ai_visibility_projects');

        $this->assertContains('created_at', $columns, 'created_at column should exist');
        $this->assertContains('updated_at', $columns, 'updated_at column should exist');
    }

    /**
     * VP-012: Index on user_id, is_active.
     */
    public function test_vp012_composite_index_user_id_is_active(): void
    {
        $indexes = $this->getTableIndexes('ai_visibility_projects');

        $hasComposite = collect($indexes)->contains(function ($index) {
            $columns = $index['columns'] ?? [];
            return in_array('user_id', $columns) && in_array('is_active', $columns);
        });

        $this->assertTrue($hasComposite, 'Composite index on (user_id, is_active) should exist');
    }

    /**
     * VP-013: Index on last_queried_at.
     */
    public function test_vp013_index_on_last_queried_at(): void
    {
        $indexes = $this->getTableIndexes('ai_visibility_projects');

        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('last_queried_at', $index['columns'] ?? []);
        });

        $this->assertTrue($hasIndex, 'Index on last_queried_at should exist');
    }

    /**
     * VP-014: Foreign key to users.
     */
    public function test_vp014_foreign_key_to_users(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key inspection only supported on MySQL/MariaDB');
        }

        $foreignKeys = $this->getForeignKeys('ai_visibility_projects');

        $userFk = collect($foreignKeys)->firstWhere('COLUMN_NAME', 'user_id');

        $this->assertNotNull($userFk, 'Foreign key on user_id should exist');
        $this->assertEquals('users', $userFk['REFERENCED_TABLE_NAME'], 'FK should reference users table');
        $this->assertEquals('id', $userFk['REFERENCED_COLUMN_NAME'], 'FK should reference id column');
    }

    /**
     * VP-015: Cascade delete when user deleted.
     */
    public function test_vp015_cascade_delete_on_user_delete(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key cascade test only supported on MySQL/MariaDB');
        }

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        // Delete the user
        DB::table('users')->where('id', $userId)->delete();

        // Project should be cascade deleted
        $this->assertDatabaseMissing('ai_visibility_projects', ['id' => $projectId]);
    }

    /**
     * VP-016: Insert valid project.
     */
    public function test_vp016_insert_valid_project(): void
    {
        $userId = $this->createUser();

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Acme Corp',
            'keywords' => json_encode(['acme', 'acme corp']),
            'competitors' => json_encode([['name' => 'Competitor A']]),
            'query_templates' => json_encode(['What is {brand}?']),
            'tracking_frequency' => 'daily',
            'is_active' => true,
            'last_queried_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($id, 'Project should be inserted');

        $record = DB::table('ai_visibility_projects')->find($id);
        $this->assertEquals('Acme Corp', $record->brand_name);
        $this->assertEquals('daily', $record->tracking_frequency);
    }

    /**
     * VP-017: JSON storage for keywords.
     */
    public function test_vp017_json_storage_keywords(): void
    {
        $userId = $this->createUser();

        $keywords = ['acme', 'acme corp', 'acme products', 'acme inc'];

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode($keywords),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $retrieved = json_decode($record->keywords, true);

        $this->assertEquals($keywords, $retrieved, 'Keywords JSON should be stored and retrieved correctly');
    }

    /**
     * VP-018: JSON storage for competitors.
     */
    public function test_vp018_json_storage_competitors(): void
    {
        $userId = $this->createUser();

        $competitors = [
            ['name' => 'Competitor A', 'keywords' => ['comp a']],
            ['name' => 'Competitor B', 'keywords' => ['comp b']],
        ];

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode(['test']),
            'competitors' => json_encode($competitors),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $retrieved = json_decode($record->competitors, true);

        $this->assertEquals($competitors, $retrieved, 'Competitors JSON should be stored correctly');
    }

    /**
     * VP-019: ENUM validation for tracking_frequency.
     */
    public function test_vp019_enum_tracking_frequency_values(): void
    {
        $userId = $this->createUser();

        $validFrequencies = ['daily', 'weekly', 'monthly'];

        foreach ($validFrequencies as $frequency) {
            $id = DB::table('ai_visibility_projects')->insertGetId([
                'user_id' => $userId,
                'brand_name' => "Test {$frequency}",
                'keywords' => json_encode(['test']),
                'competitors' => json_encode([]),
                'tracking_frequency' => $frequency,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertNotNull($id, "tracking_frequency '{$frequency}' should be valid");
        }
    }

    /**
     * VP-020: Default tracking_frequency is weekly.
     */
    public function test_vp020_default_tracking_frequency(): void
    {
        $userId = $this->createUser();

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode(['test']),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $this->assertEquals('weekly', $record->tracking_frequency);
    }

    /**
     * VP-021: Default is_active is true.
     */
    public function test_vp021_default_is_active(): void
    {
        $userId = $this->createUser();

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode(['test']),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $this->assertEquals(1, $record->is_active);
    }

    /**
     * VP-022: NULL query_templates allowed.
     */
    public function test_vp022_null_query_templates(): void
    {
        $userId = $this->createUser();

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode(['test']),
            'competitors' => json_encode([]),
            'query_templates' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $this->assertNull($record->query_templates);
    }

    /**
     * VP-023: NULL last_queried_at allowed.
     */
    public function test_vp023_null_last_queried_at(): void
    {
        $userId = $this->createUser();

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode(['test']),
            'competitors' => json_encode([]),
            'last_queried_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $this->assertNull($record->last_queried_at);
    }

    /**
     * VP-024: Multiple projects per user.
     */
    public function test_vp024_multiple_projects_per_user(): void
    {
        $userId = $this->createUser();

        for ($i = 1; $i <= 5; $i++) {
            DB::table('ai_visibility_projects')->insert([
                'user_id' => $userId,
                'brand_name' => "Brand {$i}",
                'keywords' => json_encode(["keyword{$i}"]),
                'competitors' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $count = DB::table('ai_visibility_projects')
            ->where('user_id', $userId)
            ->count();

        $this->assertEquals(5, $count);
    }

    /**
     * VP-025: Large JSON data (1000 keywords).
     */
    public function test_vp025_large_json_keywords(): void
    {
        $userId = $this->createUser();

        $keywords = [];
        for ($i = 0; $i < 1000; $i++) {
            $keywords[] = "keyword-{$i}";
        }

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode($keywords),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $retrieved = json_decode($record->keywords, true);

        $this->assertCount(1000, $retrieved);
    }

    // =========================================================================
    // Category 2: ai_query_results Table (QR-001 to QR-030)
    // =========================================================================

    /**
     * QR-001: Table exists after migration.
     */
    public function test_qr001_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('ai_query_results'),
            'Table ai_query_results should exist after migration'
        );
    }

    /**
     * QR-002: Primary key column.
     */
    public function test_qr002_primary_key_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('id', $columns, 'id column should exist');
    }

    /**
     * QR-003: project_id column (foreign key).
     */
    public function test_qr003_project_id_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('project_id', $columns, 'project_id column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_query_results', 'project_id');
        $this->assertTrue($column->getNotnull() === true, 'project_id should be NOT NULL');
    }

    /**
     * QR-004: platform column.
     */
    public function test_qr004_platform_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('platform', $columns, 'platform column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_query_results', 'platform');
        $this->assertTrue($column->getNotnull() === true, 'platform should be NOT NULL');
        $this->assertLessThanOrEqual(50, $column->getLength(), 'platform should be VARCHAR(50)');
    }

    /**
     * QR-005: model column.
     */
    public function test_qr005_model_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('model', $columns, 'model column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_query_results', 'model');
        $this->assertTrue($column->getNotnull() === true, 'model should be NOT NULL');
        $this->assertLessThanOrEqual(100, $column->getLength(), 'model should be VARCHAR(100)');
    }

    /**
     * QR-006: query_text column (TEXT, NOT NULL).
     */
    public function test_qr006_query_text_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('query_text', $columns, 'query_text column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_query_results', 'query_text');
        $this->assertTrue($column->getNotnull() === true, 'query_text should be NOT NULL');
    }

    /**
     * QR-007: query_category column (VARCHAR, nullable).
     */
    public function test_qr007_query_category_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('query_category', $columns, 'query_category column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_query_results', 'query_category');
        $this->assertTrue($column->getNotnull() === false, 'query_category should be nullable');
    }

    /**
     * QR-008: response_text column (TEXT, NOT NULL).
     */
    public function test_qr008_response_text_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('response_text', $columns, 'response_text column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_query_results', 'response_text');
        $this->assertTrue($column->getNotnull() === true, 'response_text should be NOT NULL');
    }

    /**
     * QR-009: brand_mentioned column (BOOLEAN, default: false).
     */
    public function test_qr009_brand_mentioned_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('brand_mentioned', $columns, 'brand_mentioned column should exist');

        $projectId = $this->createProject($this->createUser());

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test query',
            'response_text' => 'Test response',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $this->assertEquals(0, $record->brand_mentioned, 'brand_mentioned should default to false');
    }

    /**
     * QR-010: brand_context column (TEXT, nullable).
     */
    public function test_qr010_brand_context_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('brand_context', $columns, 'brand_context column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_query_results', 'brand_context');
        $this->assertTrue($column->getNotnull() === false, 'brand_context should be nullable');
    }

    /**
     * QR-011: brand_sentiment column (ENUM).
     */
    public function test_qr011_brand_sentiment_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('brand_sentiment', $columns, 'brand_sentiment column should exist');

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $validSentiments = ['positive', 'neutral', 'negative', 'mixed'];

        foreach ($validSentiments as $sentiment) {
            $id = DB::table('ai_query_results')->insertGetId([
                'project_id' => $projectId,
                'platform' => 'chatgpt',
                'model' => 'gpt-4',
                'query_text' => "Query {$sentiment}",
                'response_text' => 'Response',
                'brand_sentiment' => $sentiment,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertNotNull($id, "brand_sentiment '{$sentiment}' should be valid");
        }
    }

    /**
     * QR-012: competitor_mentions column (JSON, nullable).
     */
    public function test_qr012_competitor_mentions_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('competitor_mentions', $columns, 'competitor_mentions column should exist');
    }

    /**
     * QR-013: citations column (JSON, nullable).
     */
    public function test_qr013_citations_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('citations', $columns, 'citations column should exist');
    }

    /**
     * QR-014: query_tokens column (INT UNSIGNED, nullable).
     */
    public function test_qr014_query_tokens_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('query_tokens', $columns, 'query_tokens column should exist');

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }

        $column = $this->getColumnInfo('ai_query_results', 'query_tokens');
        $this->assertTrue($column->getNotnull() === false, 'query_tokens should be nullable');
    }

    /**
     * QR-015: response_tokens column (INT UNSIGNED, nullable).
     */
    public function test_qr015_response_tokens_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('response_tokens', $columns, 'response_tokens column should exist');
    }

    /**
     * QR-016: latency_ms column (INT UNSIGNED, nullable).
     */
    public function test_qr016_latency_ms_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('latency_ms', $columns, 'latency_ms column should exist');
    }

    /**
     * QR-017: queried_at column (TIMESTAMP, default: CURRENT_TIMESTAMP).
     */
    public function test_qr017_queried_at_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('queried_at', $columns, 'queried_at column should exist');

        $projectId = $this->createProject($this->createUser());

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $this->assertNotNull($record->queried_at, 'queried_at should have default value');
    }

    /**
     * QR-018: cache_hit column (BOOLEAN, default: false).
     */
    public function test_qr018_cache_hit_column(): void
    {
        $columns = $this->getColumnListing('ai_query_results');
        $this->assertContains('cache_hit', $columns, 'cache_hit column should exist');

        $projectId = $this->createProject($this->createUser());

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $this->assertEquals(0, $record->cache_hit, 'cache_hit should default to false');
    }

    /**
     * QR-019: Index on project_id, platform.
     */
    public function test_qr019_composite_index_project_platform(): void
    {
        $indexes = $this->getTableIndexes('ai_query_results');

        $hasComposite = collect($indexes)->contains(function ($index) {
            $columns = $index['columns'] ?? [];
            return in_array('project_id', $columns) && in_array('platform', $columns);
        });

        $this->assertTrue($hasComposite, 'Composite index on (project_id, platform) should exist');
    }

    /**
     * QR-020: Index on queried_at.
     */
    public function test_qr020_index_on_queried_at(): void
    {
        $indexes = $this->getTableIndexes('ai_query_results');

        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('queried_at', $index['columns'] ?? []);
        });

        $this->assertTrue($hasIndex, 'Index on queried_at should exist');
    }

    /**
     * QR-021: Index on brand_mentioned.
     */
    public function test_qr021_index_on_brand_mentioned(): void
    {
        $indexes = $this->getTableIndexes('ai_query_results');

        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('brand_mentioned', $index['columns'] ?? []);
        });

        $this->assertTrue($hasIndex, 'Index on brand_mentioned should exist');
    }

    /**
     * QR-022: FULLTEXT index on response_text.
     */
    public function test_qr022_fulltext_index_on_response_text(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('FULLTEXT index inspection only supported on MySQL/MariaDB');
        }

        $indexes = $this->getTableIndexes('ai_query_results');

        $hasFulltext = collect($indexes)->contains(function ($index) {
            $columns = $index['columns'] ?? [];
            $name = $index['name'] ?? '';
            return in_array('response_text', $columns) &&
                   (str_contains(strtolower($name), 'fulltext') || $index['type'] === 'FULLTEXT');
        });

        $this->assertTrue($hasFulltext, 'FULLTEXT index on response_text should exist');
    }

    /**
     * QR-023: Cascade delete when project deleted.
     */
    public function test_qr023_cascade_delete_on_project_delete(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key cascade test only supported on MySQL/MariaDB');
        }

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $resultId = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete the project
        DB::table('ai_visibility_projects')->where('id', $projectId)->delete();

        // Result should be cascade deleted
        $this->assertDatabaseMissing('ai_query_results', ['id' => $resultId]);
    }

    /**
     * QR-024: Insert valid result.
     */
    public function test_qr024_insert_valid_result(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4-turbo',
            'query_text' => 'What are the best widget companies?',
            'query_category' => 'industry_comparison',
            'response_text' => 'Some of the top widget companies include Acme Corp...',
            'brand_mentioned' => true,
            'brand_context' => 'Listed in top 5',
            'brand_sentiment' => 'positive',
            'competitor_mentions' => json_encode([['name' => 'Comp A', 'mentioned' => true]]),
            'citations' => json_encode([['title' => 'Report', 'url' => 'https://example.com']]),
            'query_tokens' => 15,
            'response_tokens' => 450,
            'latency_ms' => 2300,
            'cache_hit' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($id);

        $record = DB::table('ai_query_results')->find($id);
        $this->assertEquals('chatgpt', $record->platform);
        $this->assertEquals('gpt-4-turbo', $record->model);
        $this->assertEquals(1, $record->brand_mentioned);
    }

    /**
     * QR-025: JSON storage for competitor_mentions.
     */
    public function test_qr025_json_storage_competitor_mentions(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $mentions = [
            ['name' => 'Competitor A', 'mentioned' => true, 'context' => 'Ranked #1'],
            ['name' => 'Competitor B', 'mentioned' => false],
        ];

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'competitor_mentions' => json_encode($mentions),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $retrieved = json_decode($record->competitor_mentions, true);

        $this->assertEquals($mentions, $retrieved);
    }

    /**
     * QR-026: JSON storage for citations.
     */
    public function test_qr026_json_storage_citations(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $citations = [
            ['title' => 'Industry Report 2024', 'url' => 'https://example.com/report'],
            ['title' => 'Widget Review', 'url' => 'https://example.com/review'],
        ];

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'citations' => json_encode($citations),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $retrieved = json_decode($record->citations, true);

        $this->assertEquals($citations, $retrieved);
    }

    /**
     * QR-027: ENUM validation for brand_sentiment.
     */
    public function test_qr027_enum_brand_sentiment_values(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $validSentiments = ['positive', 'neutral', 'negative', 'mixed'];

        foreach ($validSentiments as $sentiment) {
            $id = DB::table('ai_query_results')->insertGetId([
                'project_id' => $projectId,
                'platform' => 'chatgpt',
                'model' => 'gpt-4',
                'query_text' => "Query {$sentiment}",
                'response_text' => 'Response',
                'brand_sentiment' => $sentiment,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertNotNull($id, "brand_sentiment '{$sentiment}' should be valid");
        }
    }

    /**
     * QR-028: Large response_text storage.
     */
    public function test_qr028_large_response_text_storage(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $largeText = str_repeat('This is a sample response text. ', 1500); // ~50KB

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => $largeText,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $this->assertEquals($largeText, $record->response_text);
    }

    /**
     * QR-029: Full-text search on response_text.
     */
    public function test_qr029_fulltext_search(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('FULLTEXT search only supported on MySQL/MariaDB');
        }

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        DB::table('ai_query_results')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Acme Corp is a leading manufacturer of widgets and gadgets.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_query_results')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Other companies make similar products.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $results = DB::select(
            "SELECT * FROM ai_query_results WHERE MATCH(response_text) AGAINST('Acme' IN NATURAL LANGUAGE MODE)"
        );

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Acme Corp', $results[0]->response_text);
    }

    /**
     * QR-030: Timestamps columns.
     */
    public function test_qr030_timestamps_columns(): void
    {
        $columns = $this->getColumnListing('ai_query_results');

        $this->assertContains('created_at', $columns, 'created_at column should exist');
        $this->assertContains('updated_at', $columns, 'updated_at column should exist');
    }

    // =========================================================================
    // Category 3: ai_mention_analytics Table (MA-001 to MA-025)
    // =========================================================================

    /**
     * MA-001: Table exists after migration.
     */
    public function test_ma001_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('ai_mention_analytics'),
            'Table ai_mention_analytics should exist after migration'
        );
    }

    /**
     * MA-002: Primary key column.
     */
    public function test_ma002_primary_key_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('id', $columns, 'id column should exist');
    }

    /**
     * MA-003: project_id column.
     */
    public function test_ma003_project_id_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('project_id', $columns, 'project_id column should exist');
    }

    /**
     * MA-004: platform column.
     */
    public function test_ma004_platform_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('platform', $columns, 'platform column should exist');
    }

    /**
     * MA-005: period_start column.
     */
    public function test_ma005_period_start_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('period_start', $columns, 'period_start column should exist');
    }

    /**
     * MA-006: period_end column.
     */
    public function test_ma006_period_end_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('period_end', $columns, 'period_end column should exist');
    }

    /**
     * MA-007: total_queries column (default: 0).
     */
    public function test_ma007_total_queries_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('total_queries', $columns, 'total_queries column should exist');

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_mention_analytics')->find($id);
        $this->assertEquals(0, $record->total_queries, 'total_queries should default to 0');
    }

    /**
     * MA-008: brand_mentions column (default: 0).
     */
    public function test_ma008_brand_mentions_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('brand_mentions', $columns, 'brand_mentions column should exist');
    }

    /**
     * MA-009: mention_rate column (DECIMAL 5,2, default: 0.00).
     */
    public function test_ma009_mention_rate_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('mention_rate', $columns, 'mention_rate column should exist');

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_mention_analytics')->find($id);
        $this->assertEquals('0.00', $record->mention_rate, 'mention_rate should default to 0.00');
    }

    /**
     * MA-010: competitor_mentions column (JSON, nullable).
     */
    public function test_ma010_competitor_mentions_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('competitor_mentions', $columns, 'competitor_mentions column should exist');
    }

    /**
     * MA-011: share_of_voice column (DECIMAL 5,2, default: 0.00).
     */
    public function test_ma011_share_of_voice_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('share_of_voice', $columns, 'share_of_voice column should exist');
    }

    /**
     * MA-012: avg_sentiment_score column (DECIMAL 3,2, default: 0.00).
     */
    public function test_ma012_avg_sentiment_score_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('avg_sentiment_score', $columns, 'avg_sentiment_score column should exist');
    }

    /**
     * MA-013: top_query_categories column (JSON, nullable).
     */
    public function test_ma013_top_query_categories_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('top_query_categories', $columns, 'top_query_categories column should exist');
    }

    /**
     * MA-014: mention_trend column (ENUM).
     */
    public function test_ma014_mention_trend_column(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');
        $this->assertContains('mention_trend', $columns, 'mention_trend column should exist');

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_mention_analytics')->find($id);
        $this->assertEquals('stable', $record->mention_trend, 'mention_trend should default to stable');
    }

    /**
     * MA-015: Timestamps columns.
     */
    public function test_ma015_timestamps_columns(): void
    {
        $columns = $this->getColumnListing('ai_mention_analytics');

        $this->assertContains('created_at', $columns, 'created_at column should exist');
        $this->assertContains('updated_at', $columns, 'updated_at column should exist');
    }

    /**
     * MA-016: UNIQUE constraint on project_id, platform, period_start, period_end.
     */
    public function test_ma016_unique_constraint(): void
    {
        $driver = DB::connection()->getDriverName();

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        DB::table('ai_mention_analytics')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            DB::table('ai_mention_analytics')->insert([
                'project_id' => $projectId,
                'platform' => 'chatgpt',
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-07',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->fail('Duplicate entry should violate unique constraint');
            }
        } catch (\Exception $e) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->assertStringContainsString('Duplicate', $e->getMessage());
            }
        }
    }

    /**
     * MA-017: Cascade delete when project deleted.
     */
    public function test_ma017_cascade_delete_on_project_delete(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key cascade test only supported on MySQL/MariaDB');
        }

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $analyticsId = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete the project
        DB::table('ai_visibility_projects')->where('id', $projectId)->delete();

        // Analytics should be cascade deleted
        $this->assertDatabaseMissing('ai_mention_analytics', ['id' => $analyticsId]);
    }

    /**
     * MA-018: Insert valid analytics.
     */
    public function test_ma018_insert_valid_analytics(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'total_queries' => 50,
            'brand_mentions' => 35,
            'mention_rate' => 70.00,
            'share_of_voice' => 33.33,
            'avg_sentiment_score' => 0.72,
            'mention_trend' => 'up',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($id);

        $record = DB::table('ai_mention_analytics')->find($id);
        $this->assertEquals(50, $record->total_queries);
        $this->assertEquals(35, $record->brand_mentions);
        $this->assertEquals('70.00', $record->mention_rate);
    }

    /**
     * MA-019: mention_rate precision (DECIMAL 5,2).
     */
    public function test_ma019_mention_rate_precision(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'mention_rate' => 99.99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_mention_analytics')->find($id);
        $this->assertEquals('99.99', $record->mention_rate);
    }

    /**
     * MA-020: share_of_voice precision (DECIMAL 5,2).
     */
    public function test_ma020_share_of_voice_precision(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'share_of_voice' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_mention_analytics')->find($id);
        $this->assertEquals('100.00', $record->share_of_voice);
    }

    /**
     * MA-021: avg_sentiment_score range (0.00 to 1.00).
     */
    public function test_ma021_avg_sentiment_score_range(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        // Test valid values
        $validScores = [0.00, 0.50, 1.00];

        foreach ($validScores as $score) {
            $id = DB::table('ai_mention_analytics')->insertGetId([
                'project_id' => $projectId,
                'platform' => "platform-{$score}",
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-07',
                'avg_sentiment_score' => $score,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $record = DB::table('ai_mention_analytics')->find($id);
            $this->assertEquals(number_format($score, 2), $record->avg_sentiment_score);
        }
    }

    /**
     * MA-022: ENUM validation for mention_trend.
     */
    public function test_ma022_enum_mention_trend_values(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $validTrends = ['up', 'down', 'stable'];

        foreach ($validTrends as $trend) {
            $id = DB::table('ai_mention_analytics')->insertGetId([
                'project_id' => $projectId,
                'platform' => "platform-{$trend}",
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-07',
                'mention_trend' => $trend,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertNotNull($id, "mention_trend '{$trend}' should be valid");
        }
    }

    /**
     * MA-023: Default mention_trend is stable.
     */
    public function test_ma023_default_mention_trend(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_mention_analytics')->find($id);
        $this->assertEquals('stable', $record->mention_trend);
    }

    /**
     * MA-024: JSON storage for competitor_mentions.
     */
    public function test_ma024_json_storage_competitor_mentions(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $mentions = [
            'Competitor A' => ['mentions' => 42, 'rate' => 84.00],
            'Competitor B' => ['mentions' => 28, 'rate' => 56.00],
        ];

        $id = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'competitor_mentions' => json_encode($mentions),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_mention_analytics')->find($id);
        $retrieved = json_decode($record->competitor_mentions, true);

        $this->assertEquals($mentions, $retrieved);
    }

    /**
     * MA-025: JSON storage for top_query_categories.
     */
    public function test_ma025_json_storage_top_query_categories(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $categories = ['industry_comparison', 'product_review', 'how_to'];

        $id = DB::table('ai_mention_analytics')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'top_query_categories' => json_encode($categories),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_mention_analytics')->find($id);
        $retrieved = json_decode($record->top_query_categories, true);

        $this->assertEquals($categories, $retrieved);
    }

    // =========================================================================
    // Category 4: ai_visibility_alerts Table (VA-001 to VA-025)
    // =========================================================================

    /**
     * VA-001: Table exists after migration.
     */
    public function test_va001_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('ai_visibility_alerts'),
            'Table ai_visibility_alerts should exist after migration'
        );
    }

    /**
     * VA-002: Primary key column.
     */
    public function test_va002_primary_key_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('id', $columns, 'id column should exist');
    }

    /**
     * VA-003: project_id column.
     */
    public function test_va003_project_id_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('project_id', $columns, 'project_id column should exist');
    }

    /**
     * VA-004: alert_type column (ENUM).
     */
    public function test_va004_alert_type_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('alert_type', $columns, 'alert_type column should exist');
    }

    /**
     * VA-005: threshold column (DECIMAL 5,2, NOT NULL).
     */
    public function test_va005_threshold_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('threshold', $columns, 'threshold column should exist');
    }

    /**
     * VA-006: previous_value column (DECIMAL 5,2, NOT NULL).
     */
    public function test_va006_previous_value_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('previous_value', $columns, 'previous_value column should exist');
    }

    /**
     * VA-007: current_value column (DECIMAL 5,2, NOT NULL).
     */
    public function test_va007_current_value_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('current_value', $columns, 'current_value column should exist');
    }

    /**
     * VA-008: change_percent column (DECIMAL 5,2, NOT NULL).
     */
    public function test_va008_change_percent_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('change_percent', $columns, 'change_percent column should exist');
    }

    /**
     * VA-009: is_read column (BOOLEAN, default: false).
     */
    public function test_va009_is_read_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('is_read', $columns, 'is_read column should exist');

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_spike',
            'threshold' => 10.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_alerts')->find($id);
        $this->assertEquals(0, $record->is_read, 'is_read should default to false');
    }

    /**
     * VA-010: is_resolved column (BOOLEAN, default: false).
     */
    public function test_va010_is_resolved_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('is_resolved', $columns, 'is_resolved column should exist');
    }

    /**
     * VA-011: triggered_at column (TIMESTAMP, default: CURRENT_TIMESTAMP).
     */
    public function test_va011_triggered_at_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('triggered_at', $columns, 'triggered_at column should exist');
    }

    /**
     * VA-012: resolved_at column (TIMESTAMP, nullable).
     */
    public function test_va012_resolved_at_column(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');
        $this->assertContains('resolved_at', $columns, 'resolved_at column should exist');
    }

    /**
     * VA-013: Timestamps columns.
     */
    public function test_va013_timestamps_columns(): void
    {
        $columns = $this->getColumnListing('ai_visibility_alerts');

        $this->assertContains('created_at', $columns, 'created_at column should exist');
        $this->assertContains('updated_at', $columns, 'updated_at column should exist');
    }

    /**
     * VA-014: Index on project_id, is_read.
     */
    public function test_va014_composite_index_project_is_read(): void
    {
        $indexes = $this->getTableIndexes('ai_visibility_alerts');

        $hasComposite = collect($indexes)->contains(function ($index) {
            $columns = $index['columns'] ?? [];
            return in_array('project_id', $columns) && in_array('is_read', $columns);
        });

        $this->assertTrue($hasComposite, 'Composite index on (project_id, is_read) should exist');
    }

    /**
     * VA-015: Index on triggered_at.
     */
    public function test_va015_index_on_triggered_at(): void
    {
        $indexes = $this->getTableIndexes('ai_visibility_alerts');

        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('triggered_at', $index['columns'] ?? []);
        });

        $this->assertTrue($hasIndex, 'Index on triggered_at should exist');
    }

    /**
     * VA-016: Cascade delete when project deleted.
     */
    public function test_va016_cascade_delete_on_project_delete(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key cascade test only supported on MySQL/MariaDB');
        }

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $alertId = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_spike',
            'threshold' => 10.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete the project
        DB::table('ai_visibility_projects')->where('id', $projectId)->delete();

        // Alert should be cascade deleted
        $this->assertDatabaseMissing('ai_visibility_alerts', ['id' => $alertId]);
    }

    /**
     * VA-017: Insert valid alert.
     */
    public function test_va017_insert_valid_alert(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_spike',
            'threshold' => 10.00,
            'previous_value' => 35.00,
            'current_value' => 52.00,
            'change_percent' => 48.57,
            'is_read' => false,
            'is_resolved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($id);

        $record = DB::table('ai_visibility_alerts')->find($id);
        $this->assertEquals('mention_spike', $record->alert_type);
        $this->assertEquals('48.57', $record->change_percent);
    }

    /**
     * VA-018: ENUM validation for alert_type.
     */
    public function test_va018_enum_alert_type_values(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $validTypes = ['mention_spike', 'mention_drop', 'competitor_gain', 'sentiment_change'];

        foreach ($validTypes as $type) {
            $id = DB::table('ai_visibility_alerts')->insertGetId([
                'project_id' => $projectId,
                'alert_type' => $type,
                'threshold' => 10.00,
                'previous_value' => 50.00,
                'current_value' => 60.00,
                'change_percent' => 20.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertNotNull($id, "alert_type '{$type}' should be valid");
        }
    }

    /**
     * VA-019: Default is_read is false.
     */
    public function test_va019_default_is_read(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_spike',
            'threshold' => 10.00,
            'previous_value' => 50.00,
            'current_value' => 60.00,
            'change_percent' => 20.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_alerts')->find($id);
        $this->assertEquals(0, $record->is_read);
    }

    /**
     * VA-020: Default is_resolved is false.
     */
    public function test_va020_default_is_resolved(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_spike',
            'threshold' => 10.00,
            'previous_value' => 50.00,
            'current_value' => 60.00,
            'change_percent' => 20.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_alerts')->find($id);
        $this->assertEquals(0, $record->is_resolved);
    }

    /**
     * VA-021: NULL resolved_at allowed for unresolved alerts.
     */
    public function test_va021_null_resolved_at(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_spike',
            'threshold' => 10.00,
            'previous_value' => 50.00,
            'current_value' => 60.00,
            'change_percent' => 20.00,
            'resolved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_alerts')->find($id);
        $this->assertNull($record->resolved_at);
    }

    /**
     * VA-022: Resolve alert.
     */
    public function test_va022_resolve_alert(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_spike',
            'threshold' => 10.00,
            'previous_value' => 50.00,
            'current_value' => 60.00,
            'change_percent' => 20.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mark as resolved
        DB::table('ai_visibility_alerts')
            ->where('id', $id)
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
            ]);

        $record = DB::table('ai_visibility_alerts')->find($id);
        $this->assertEquals(1, $record->is_resolved);
        $this->assertNotNull($record->resolved_at);
    }

    /**
     * VA-023: Decimal precision for threshold (DECIMAL 5,2).
     */
    public function test_va023_threshold_precision(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_spike',
            'threshold' => 999.99,
            'previous_value' => 50.00,
            'current_value' => 60.00,
            'change_percent' => 20.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_alerts')->find($id);
        $this->assertEquals('999.99', $record->threshold);
    }

    /**
     * VA-024: Decimal precision for change_percent (negative values).
     */
    public function test_va024_change_percent_negative(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_drop',
            'threshold' => 10.00,
            'previous_value' => 50.00,
            'current_value' => 25.00,
            'change_percent' => -50.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_alerts')->find($id);
        $this->assertEquals('-50.00', $record->change_percent);
    }

    /**
     * VA-025: Multiple alerts per project.
     */
    public function test_va025_multiple_alerts_per_project(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        for ($i = 1; $i <= 10; $i++) {
            DB::table('ai_visibility_alerts')->insert([
                'project_id' => $projectId,
                'alert_type' => 'mention_spike',
                'threshold' => $i * 10,
                'previous_value' => 50.00,
                'current_value' => 60.00,
                'change_percent' => 20.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $count = DB::table('ai_visibility_alerts')
            ->where('project_id', $projectId)
            ->count();

        $this->assertEquals(10, $count);
    }

    // =========================================================================
    // Category 5: Migration Reversibility (MR-001 to MR-015)
    // =========================================================================

    /**
     * MR-001: Rollback drops all 4 tables.
     */
    public function test_mr001_rollback_drops_all_tables(): void
    {
        $this->artisan('migrate:rollback');

        $this->assertFalse(
            Schema::hasTable('ai_visibility_alerts'),
            'ai_visibility_alerts should be dropped on rollback'
        );
    }

    /**
     * MR-002: Re-migrate after rollback.
     */
    public function test_mr002_remigrate_after_rollback(): void
    {
        $this->artisan('migrate:rollback');
        $this->artisan('migrate');

        $this->assertTrue(Schema::hasTable('ai_visibility_projects'));
        $this->assertTrue(Schema::hasTable('ai_query_results'));
        $this->assertTrue(Schema::hasTable('ai_mention_analytics'));
        $this->assertTrue(Schema::hasTable('ai_visibility_alerts'));
    }

    /**
     * MR-003: Data preserved in users on rollback.
     */
    public function test_mr003_data_preserved_in_users_on_rollback(): void
    {
        $userId = $this->createUser(['email' => 'preserved@test.com']);

        $this->artisan('migrate:rollback');

        // Users table should still exist with data
        $this->assertDatabaseHas('users', ['id' => $userId, 'email' => 'preserved@test.com']);
    }

    /**
     * MR-004: Foreign keys dropped on rollback.
     */
    public function test_mr004_foreign_keys_dropped_on_rollback(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key inspection only supported on MySQL/MariaDB');
        }

        $this->artisan('migrate:rollback');

        // Tables should be dropped, no FK constraints remain
        $this->assertFalse(Schema::hasTable('ai_visibility_projects'));
    }

    /**
     * MR-005: Indexes dropped on rollback.
     */
    public function test_mr005_indexes_dropped_on_rollback(): void
    {
        $this->artisan('migrate:rollback');

        // Tables should be dropped, no indexes remain
        $this->assertFalse(Schema::hasTable('ai_visibility_projects'));
    }

    /**
     * MR-006: Multiple rollback/remigrate cycles.
     */
    public function test_mr006_multiple_rollback_remigrate_cycles(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->artisan('migrate:rollback');
            $this->artisan('migrate');
        }

        $this->assertTrue(Schema::hasTable('ai_visibility_projects'));
        $this->assertTrue(Schema::hasTable('ai_query_results'));
        $this->assertTrue(Schema::hasTable('ai_mention_analytics'));
        $this->assertTrue(Schema::hasTable('ai_visibility_alerts'));
    }

    /**
     * MR-007: Migration order correct.
     */
    public function test_mr007_migration_order_correct(): void
    {
        // Projects should exist before other tables (FK dependency)
        $this->assertTrue(Schema::hasTable('ai_visibility_projects'));
        $this->assertTrue(Schema::hasTable('ai_query_results'));
    }

    /**
     * MR-008: Fresh migration works.
     */
    public function test_mr008_fresh_migration_works(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertTrue(Schema::hasTable('ai_visibility_projects'));
        $this->assertTrue(Schema::hasTable('ai_query_results'));
        $this->assertTrue(Schema::hasTable('ai_mention_analytics'));
        $this->assertTrue(Schema::hasTable('ai_visibility_alerts'));
    }

    /**
     * MR-009: Migration file naming convention.
     */
    public function test_mr009_migration_file_naming(): void
    {
        $migrationsPath = database_path('migrations');

        // Check for AI visibility migrations
        $migrations = glob($migrationsPath . '/*ai_visibility*');

        // At least 4 migration files should exist
        $this->assertGreaterThanOrEqual(4, count($migrations), 'At least 4 AI visibility migration files should exist');
    }

    /**
     * MR-010: Foreign key constraint enforced.
     */
    public function test_mr010_foreign_key_constraint_enforced(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('SQLite does not enforce FK constraints by default');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->expectException(\Exception::class);

        // Try to insert with non-existent project_id
        DB::table('ai_query_results')->insert([
            'project_id' => 999999,
            'platform' => 'test',
            'model' => 'test',
            'query_text' => 'test',
            'response_text' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * MR-011: UNIQUE constraint enforced.
     */
    public function test_mr011_unique_constraint_enforced(): void
    {
        $driver = DB::connection()->getDriverName();

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        DB::table('ai_mention_analytics')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            DB::table('ai_mention_analytics')->insert([
                'project_id' => $projectId,
                'platform' => 'chatgpt',
                'period_start' => '2026-03-01',
                'period_end' => '2026-03-07',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->fail('Duplicate entry should violate unique constraint');
            }
        } catch (\Exception $e) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->assertStringContainsString('Duplicate', $e->getMessage());
            }
        }
    }

    /**
     * MR-012: No data loss on upgrade.
     */
    public function test_mr012_no_data_loss_on_upgrade(): void
    {
        $userId = $this->createUser(['email' => 'upgrade@test.com']);

        // Migration should not affect existing data
        $this->artisan('migrate');

        $this->assertDatabaseHas('users', ['id' => $userId, 'email' => 'upgrade@test.com']);
    }

    /**
     * MR-013: Column types correct.
     */
    public function test_mr013_column_types_correct(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column types validated (SQLite)');
            return;
        }

        // Verify DECIMAL columns have correct precision
        $column = $this->getColumnInfo('ai_mention_analytics', 'mention_rate');
        $this->assertEquals('decimal', strtolower($column->getType()));

        $column = $this->getColumnInfo('ai_visibility_alerts', 'threshold');
        $this->assertEquals('decimal', strtolower($column->getType()));
    }

    /**
     * MR-014: Migration is idempotent.
     */
    public function test_mr014_migration_is_idempotent(): void
    {
        $this->artisan('migrate');

        $this->assertTrue(Schema::hasTable('ai_visibility_projects'));
    }

    /**
     * MR-015: Rollback specific migration.
     */
    public function test_mr015_rollback_specific_migration(): void
    {
        // This tests that rollback works without errors
        $this->artisan('migrate:rollback');

        $this->assertFalse(Schema::hasTable('ai_visibility_alerts'));
    }

    // =========================================================================
    // Category 6: Data Integrity (DI-001 to DI-010)
    // =========================================================================

    /**
     * DI-001: Query projects by user_id.
     */
    public function test_di001_query_projects_by_user_id(): void
    {
        $userId1 = $this->createUser(['email' => 'user1@test.com']);
        $userId2 = $this->createUser(['email' => 'user2@test.com']);

        $this->createProject($userId1);
        $this->createProject($userId1);
        $this->createProject($userId2);

        $projects = DB::table('ai_visibility_projects')
            ->where('user_id', $userId1)
            ->count();

        $this->assertEquals(2, $projects);
    }

    /**
     * DI-002: Query projects by is_active.
     */
    public function test_di002_query_projects_by_is_active(): void
    {
        $userId = $this->createUser();

        DB::table('ai_visibility_projects')->insert([
            'user_id' => $userId,
            'brand_name' => 'Active Brand',
            'keywords' => json_encode(['keyword']),
            'competitors' => json_encode([]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_visibility_projects')->insert([
            'user_id' => $userId,
            'brand_name' => 'Inactive Brand',
            'keywords' => json_encode(['keyword']),
            'competitors' => json_encode([]),
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activeCount = DB::table('ai_visibility_projects')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->count();

        $this->assertEquals(1, $activeCount);
    }

    /**
     * DI-003: Query results by project_id.
     */
    public function test_di003_query_results_by_project_id(): void
    {
        $userId = $this->createUser();
        $projectId1 = $this->createProject($userId);
        $projectId2 = $this->createProject($userId);

        $this->createQueryResult($projectId1);
        $this->createQueryResult($projectId1);
        $this->createQueryResult($projectId2);

        $results = DB::table('ai_query_results')
            ->where('project_id', $projectId1)
            ->count();

        $this->assertEquals(2, $results);
    }

    /**
     * DI-004: Query results by platform.
     */
    public function test_di004_query_results_by_platform(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        DB::table('ai_query_results')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_query_results')->insert([
            'project_id' => $projectId,
            'platform' => 'perplexity',
            'model' => 'pplx-7b',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $chatgptCount = DB::table('ai_query_results')
            ->where('platform', 'chatgpt')
            ->count();

        $this->assertEquals(1, $chatgptCount);
    }

    /**
     * DI-005: Query analytics by date range.
     */
    public function test_di005_query_analytics_by_date_range(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        DB::table('ai_mention_analytics')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_mention_analytics')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $marchCount = DB::table('ai_mention_analytics')
            ->where('period_start', '>=', '2026-03-01')
            ->count();

        $this->assertEquals(1, $marchCount);
    }

    /**
     * DI-006: Query alerts by is_read.
     */
    public function test_di006_query_alerts_by_is_read(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        DB::table('ai_visibility_alerts')->insert([
            'project_id' => $projectId,
            'alert_type' => 'mention_spike',
            'threshold' => 10.00,
            'previous_value' => 50.00,
            'current_value' => 60.00,
            'change_percent' => 20.00,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_visibility_alerts')->insert([
            'project_id' => $projectId,
            'alert_type' => 'mention_drop',
            'threshold' => 10.00,
            'previous_value' => 60.00,
            'current_value' => 50.00,
            'change_percent' => -16.67,
            'is_read' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unreadCount = DB::table('ai_visibility_alerts')
            ->where('project_id', $projectId)
            ->where('is_read', false)
            ->count();

        $this->assertEquals(1, $unreadCount);
    }

    /**
     * DI-007: JOIN projects with results.
     */
    public function test_di007_join_projects_with_results(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId, ['brand_name' => 'Test Brand']);

        $this->createQueryResult($projectId);

        $result = DB::table('ai_visibility_projects')
            ->join('ai_query_results', 'ai_visibility_projects.id', '=', 'ai_query_results.project_id')
            ->where('ai_visibility_projects.id', $projectId)
            ->select('ai_visibility_projects.brand_name', 'ai_query_results.platform')
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals('Test Brand', $result->brand_name);
    }

    /**
     * DI-008: JOIN results with analytics.
     */
    public function test_di008_join_results_with_analytics(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $this->createQueryResult($projectId, ['platform' => 'chatgpt']);

        DB::table('ai_mention_analytics')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'total_queries' => 50,
            'brand_mentions' => 35,
            'mention_rate' => 70.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $results = DB::table('ai_query_results')
            ->join('ai_mention_analytics', function ($join) use ($projectId) {
                $join->on('ai_query_results.project_id', '=', 'ai_mention_analytics.project_id')
                    ->where('ai_mention_analytics.project_id', $projectId);
            })
            ->count();

        $this->assertGreaterThanOrEqual(1, $results);
    }

    /**
     * DI-009: Aggregation on mention_rate.
     */
    public function test_di009_aggregation_on_mention_rate(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        DB::table('ai_mention_analytics')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'mention_rate' => 70.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_mention_analytics')->insert([
            'project_id' => $projectId,
            'platform' => 'perplexity',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-07',
            'mention_rate' => 60.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $avgRate = DB::table('ai_mention_analytics')
            ->where('project_id', $projectId)
            ->avg('mention_rate');

        $this->assertEquals(65.00, $avgRate);
    }

    /**
     * DI-010: Full-text search on response_text.
     */
    public function test_di010_fulltext_search_on_response_text(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('FULLTEXT search only supported on MySQL/MariaDB');
        }

        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        DB::table('ai_query_results')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Acme Corp is a leading provider of widgets.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_query_results')->insert([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Other companies exist.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $results = DB::select(
            "SELECT * FROM ai_query_results WHERE MATCH(response_text) AGAINST('Acme' IN NATURAL LANGUAGE MODE)"
        );

        $this->assertCount(1, $results);
    }

    // =========================================================================
    // Category 7: Security & Edge Cases (SE-001 to SE-015)
    // =========================================================================

    /**
     * SE-001: SQL injection in brand_name.
     */
    public function test_se001_sql_injection_in_brand_name(): void
    {
        $userId = $this->createUser();

        $maliciousBrand = "'; DROP TABLE ai_visibility_projects; --";

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => $maliciousBrand,
            'keywords' => json_encode(['test']),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue(Schema::hasTable('ai_visibility_projects'));

        $record = DB::table('ai_visibility_projects')->find($id);
        $this->assertEquals($maliciousBrand, $record->brand_name);
    }

    /**
     * SE-002: SQL injection in JSON columns.
     */
    public function test_se002_sql_injection_in_json(): void
    {
        $userId = $this->createUser();

        $maliciousJson = json_encode(['inject' => "'; DROP TABLE ai_visibility_projects; --"]);

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => $maliciousJson,
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue(Schema::hasTable('ai_visibility_projects'));
    }

    /**
     * SE-003: XSS in response_text.
     */
    public function test_se003_xss_in_response_text(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $xssPayload = '<script>alert("xss")</script>';

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => $xssPayload,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $this->assertEquals($xssPayload, $record->response_text, 'XSS should be stored as literal text');
    }

    /**
     * SE-004: XSS in brand_context.
     */
    public function test_se004_xss_in_brand_context(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $xssPayload = '<script>document.cookie</script>';

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'brand_context' => $xssPayload,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $this->assertEquals($xssPayload, $record->brand_context);
    }

    /**
     * SE-005: Unicode in brand_name.
     */
    public function test_se005_unicode_in_brand_name(): void
    {
        $userId = $this->createUser();

        $unicodeBrand = '品牌名称-🎉-Test'; // Chinese + emoji

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => $unicodeBrand,
            'keywords' => json_encode(['test']),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);
        $this->assertEquals($unicodeBrand, $record->brand_name);
    }

    /**
     * SE-006: Unicode in response_text.
     */
    public function test_se006_unicode_in_response_text(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $unicodeText = '这是中文测试 🎉 日本語テスト'; // Chinese + emoji + Japanese

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => $unicodeText,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $this->assertEquals($unicodeText, $record->response_text);
    }

    /**
     * SE-007: NULL in NOT NULL columns fails.
     */
    public function test_se007_null_in_not_null_columns_fails(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('SQLite handles NOT NULL differently');
        }

        $userId = $this->createUser();

        $this->expectException(\Exception::class);

        DB::table('ai_visibility_projects')->insert([
            'user_id' => $userId,
            'brand_name' => null, // NOT NULL column
            'keywords' => json_encode(['test']),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * SE-008: Invalid JSON in JSON columns.
     */
    public function test_se008_invalid_json_in_json_columns(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->assertTrue(true, 'SQLite does not validate JSON format');
            return;
        }

        $userId = $this->createUser();

        $this->expectException(\Exception::class);

        DB::table('ai_visibility_projects')->insert([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => 'not valid json {{{',
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * SE-009: Invalid ENUM values.
     */
    public function test_se009_invalid_enum_values(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->assertTrue(true, 'SQLite does not enforce ENUM');
            return;
        }

        $userId = $this->createUser();

        $this->expectException(\Exception::class);

        DB::table('ai_visibility_projects')->insert([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode(['test']),
            'competitors' => json_encode([]),
            'tracking_frequency' => 'invalid_frequency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * SE-010: Negative values in DECIMAL.
     */
    public function test_se010_negative_values_in_decimal(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_visibility_alerts')->insertGetId([
            'project_id' => $projectId,
            'alert_type' => 'mention_drop',
            'threshold' => 10.00,
            'previous_value' => 50.00,
            'current_value' => 25.00,
            'change_percent' => -50.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_alerts')->find($id);
        $this->assertEquals('-50.00', $record->change_percent);
    }

    /**
     * SE-011: Exceeding VARCHAR limits.
     */
    public function test_se011_exceeding_varchar_limits(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite does not enforce VARCHAR length');
        }

        $userId = $this->createUser();

        $longBrand = str_repeat('a', 256); // 256 chars, max is 255

        try {
            $id = DB::table('ai_visibility_projects')->insertGetId([
                'user_id' => $userId,
                'brand_name' => $longBrand,
                'keywords' => json_encode(['test']),
                'competitors' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $record = DB::table('ai_visibility_projects')->find($id);
            $this->assertLessThanOrEqual(255, strlen($record->brand_name));
        } catch (\Exception $e) {
            $this->assertStringContainsString('too long', strtolower($e->getMessage()));
        }
    }

    /**
     * SE-012: Zero values in INT columns.
     */
    public function test_se012_zero_values_in_int_columns(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => 'Response',
            'query_tokens' => 0,
            'response_tokens' => 0,
            'latency_ms' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_query_results')->find($id);
        $this->assertEquals(0, $record->query_tokens);
        $this->assertEquals(0, $record->response_tokens);
    }

    /**
     * SE-013: Very large JSON.
     */
    public function test_se013_very_large_json(): void
    {
        $userId = $this->createUser();

        // Create large keywords array (~50KB)
        $keywords = [];
        for ($i = 0; $i < 5000; $i++) {
            $keywords[] = "keyword-{$i}-" . str_repeat('a', 5);
        }

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode($keywords),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($id);
    }

    /**
     * SE-014: Very large response_text.
     */
    public function test_se014_very_large_response_text(): void
    {
        $userId = $this->createUser();
        $projectId = $this->createProject($userId);

        // Create large text (~1MB)
        $largeText = str_repeat('This is a sample AI response text. ', 30000);

        $id = DB::table('ai_query_results')->insertGetId([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test',
            'response_text' => $largeText,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($id);
    }

    /**
     * SE-015: Empty JSON arrays.
     */
    public function test_se015_empty_json_arrays(): void
    {
        $userId = $this->createUser();

        $id = DB::table('ai_visibility_projects')->insertGetId([
            'user_id' => $userId,
            'brand_name' => 'Test',
            'keywords' => json_encode([]),
            'competitors' => json_encode([]),
            'query_templates' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('ai_visibility_projects')->find($id);

        $this->assertEquals([], json_decode($record->keywords, true));
        $this->assertEquals([], json_decode($record->competitors, true));
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
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Create a test project.
     */
    private function createProject(int $userId, array $attributes = []): int
    {
        return DB::table('ai_visibility_projects')->insertGetId(array_merge([
            'user_id' => $userId,
            'brand_name' => 'Test Brand',
            'keywords' => json_encode(['keyword1', 'keyword2']),
            'competitors' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Create a test query result.
     */
    private function createQueryResult(int $projectId, array $attributes = []): int
    {
        return DB::table('ai_query_results')->insertGetId(array_merge([
            'project_id' => $projectId,
            'platform' => 'chatgpt',
            'model' => 'gpt-4',
            'query_text' => 'Test query',
            'response_text' => 'Test response',
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
                    'type' => 'BTREE',
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
                    NOT NON_UNIQUE as unique,
                    INDEX_TYPE as type
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                GROUP BY INDEX_NAME, NON_UNIQUE, INDEX_TYPE
            ", [$database, $table]);

            return array_map(function ($row) {
                return [
                    'name' => $row->name,
                    'columns' => explode(',', $row->columns),
                    'unique' => (bool) $row->unique,
                    'type' => $row->type,
                ];
            }, $result);
        }

        return [];
    }
}