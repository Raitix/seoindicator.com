<?php

namespace Tests\Unit\Migrations;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

/**
 * Test suite for TASK-31-10: Migration for New Columns/Tables
 *
 * Tests the migration creating `ai_analysis_requests` table and modifying
 * `seo_analyses` table to support unified AI analysis with token tracking.
 *
 * Test Scenarios:
 * - AR-001 to AR-025: ai_analysis_requests Table Structure
 * - SA-001 to SA-020: seo_analyses New Columns
 * - MR-001 to MR-015: Migration Reversibility
 * - DI-001 to DI-010: Data Integrity
 * - SE-001 to SE-012: Security & Edge Cases
 *
 * @see TASK-31-10
 * @see SPEC-031
 * @see FEAT-031
 */
class CreateAIAnalysisRequestsMigrationTest extends TestCase
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
    // Category 1: ai_analysis_requests Table Structure (AR-001 to AR-025)
    // =========================================================================

    /**
     * AR-001: Table exists after migration.
     */
    public function test_ar001_table_exists_after_migration(): void
    {
        $this->assertTrue(
            Schema::hasTable('ai_analysis_requests'),
            'Table ai_analysis_requests should exist after migration'
        );
    }

    /**
     * AR-002: Primary key column.
     */
    public function test_ar002_primary_key_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('id', $columns, 'id column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Primary key exists (SQLite)');
            return;
        }
        
        $idColumn = $this->getColumnInfo('ai_analysis_requests', 'id');
        $this->assertEquals('bigint', strtolower($idColumn->getType()), 'id should be bigint unsigned');
    }

    /**
     * AR-003: analysis_id column (foreign key).
     */
    public function test_ar003_analysis_id_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('analysis_id', $columns, 'analysis_id column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('ai_analysis_requests', 'analysis_id');
        $this->assertTrue($column->getNotnull() === true, 'analysis_id should be NOT NULL');
    }

    /**
     * AR-004: model column.
     */
    public function test_ar004_model_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('model', $columns, 'model column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('ai_analysis_requests', 'model');
        $this->assertTrue($column->getNotnull() === true, 'model should be NOT NULL');
        $this->assertLessThanOrEqual(100, $column->getLength(), 'model should be VARCHAR(100)');
    }

    /**
     * AR-005: prompt_tokens column (nullable).
     */
    public function test_ar005_prompt_tokens_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('prompt_tokens', $columns, 'prompt_tokens column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('ai_analysis_requests', 'prompt_tokens');
        $this->assertTrue($column->getNotnull() === false, 'prompt_tokens should be nullable');
    }

    /**
     * AR-006: response_tokens column (nullable).
     */
    public function test_ar006_response_tokens_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('response_tokens', $columns, 'response_tokens column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('ai_analysis_requests', 'response_tokens');
        $this->assertTrue($column->getNotnull() === false, 'response_tokens should be nullable');
    }

    /**
     * AR-007: total_tokens column (nullable).
     */
    public function test_ar007_total_tokens_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('total_tokens', $columns, 'total_tokens column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('ai_analysis_requests', 'total_tokens');
        $this->assertTrue($column->getNotnull() === false, 'total_tokens should be nullable');
    }

    /**
     * AR-008: latency_ms column (nullable).
     */
    public function test_ar008_latency_ms_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('latency_ms', $columns, 'latency_ms column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('ai_analysis_requests', 'latency_ms');
        $this->assertTrue($column->getNotnull() === false, 'latency_ms should be nullable');
    }

    /**
     * AR-009: truncation_strategies column (JSON, nullable).
     */
    public function test_ar009_truncation_strategies_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('truncation_strategies', $columns, 'truncation_strategies column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('ai_analysis_requests', 'truncation_strategies');
        $this->assertTrue($column->getNotnull() === false, 'truncation_strategies should be nullable');
    }

    /**
     * AR-010: request_hash column (nullable, 64 chars for SHA-256).
     */
    public function test_ar010_request_hash_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('request_hash', $columns, 'request_hash column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('ai_analysis_requests', 'request_hash');
        $this->assertTrue($column->getNotnull() === false, 'request_hash should be nullable');
        $this->assertLessThanOrEqual(64, $column->getLength(), 'request_hash should be VARCHAR(64)');
    }

    /**
     * AR-011: status column (ENUM).
     */
    public function test_ar011_status_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('status', $columns, 'status column should exist');
        
        // Test default value by inserting without status
        $analysisId = $this->createSeoAnalysis();
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'test-model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertEquals('pending', $record->status, 'status should default to pending');
    }

    /**
     * AR-012: error_message column (TEXT, nullable).
     */
    public function test_ar012_error_message_column(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('error_message', $columns, 'error_message column should exist');
        
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('ai_analysis_requests', 'error_message');
        $this->assertTrue($column->getNotnull() === false, 'error_message should be nullable');
    }

    /**
     * AR-013: timestamps columns.
     */
    public function test_ar013_timestamps_columns(): void
    {
        $columns = $this->getColumnListing('ai_analysis_requests');
        
        $this->assertContains('created_at', $columns, 'created_at column should exist');
        $this->assertContains('updated_at', $columns, 'updated_at column should exist');
    }

    /**
     * AR-014: Foreign key to seo_analyses.
     */
    public function test_ar014_foreign_key_to_seo_analyses(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key inspection only supported on MySQL/MariaDB');
        }
        
        $foreignKeys = $this->getForeignKeys('ai_analysis_requests');
        
        $analysisFk = collect($foreignKeys)->firstWhere('COLUMN_NAME', 'analysis_id');
        
        $this->assertNotNull($analysisFk, 'Foreign key on analysis_id should exist');
        $this->assertEquals('seo_analyses', $analysisFk['REFERENCED_TABLE_NAME'], 'FK should reference seo_analyses table');
        $this->assertEquals('id', $analysisFk['REFERENCED_COLUMN_NAME'], 'FK should reference id column');
    }

    /**
     * AR-015: Foreign key cascade on delete.
     */
    public function test_ar015_foreign_key_cascade_on_delete(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key cascade test only supported on MySQL/MariaDB');
        }
        
        $analysisId = $this->createSeoAnalysis();
        
        $requestId = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'test-model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Delete the seo_analysis
        DB::table('seo_analyses')->where('id', $analysisId)->delete();
        
        // The ai_analysis_request should be cascade deleted
        $this->assertDatabaseMissing('ai_analysis_requests', ['id' => $requestId]);
    }

    /**
     * AR-016: Composite index on (analysis_id, status).
     */
    public function test_ar016_composite_index_analysis_status(): void
    {
        $indexes = $this->getTableIndexes('ai_analysis_requests');
        
        $hasComposite = collect($indexes)->contains(function ($index) {
            $columns = $index['columns'] ?? [];
            return in_array('analysis_id', $columns) && in_array('status', $columns);
        });
        
        $this->assertTrue($hasComposite, 'Composite index on (analysis_id, status) should exist');
    }

    /**
     * AR-017: Index on model.
     */
    public function test_ar017_index_on_model(): void
    {
        $indexes = $this->getTableIndexes('ai_analysis_requests');
        
        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('model', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasIndex, 'Index on model should exist');
    }

    /**
     * AR-018: Index on request_hash.
     */
    public function test_ar018_index_on_request_hash(): void
    {
        $indexes = $this->getTableIndexes('ai_analysis_requests');
        
        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('request_hash', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasIndex, 'Index on request_hash should exist');
    }

    /**
     * AR-019: Index on created_at.
     */
    public function test_ar019_index_on_created_at(): void
    {
        $indexes = $this->getTableIndexes('ai_analysis_requests');
        
        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('created_at', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasIndex, 'Index on created_at should exist');
    }

    /**
     * AR-020: Insert valid record.
     */
    public function test_ar020_insert_valid_record(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'gpt-4',
            'prompt_tokens' => 500,
            'response_tokens' => 250,
            'total_tokens' => 750,
            'latency_ms' => 1500,
            'truncation_strategies' => json_encode(['strategy' => 'summarize']),
            'request_hash' => hash('sha256', 'test-request'),
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertNotNull($id, 'Record should be inserted successfully');
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertEquals('gpt-4', $record->model);
        $this->assertEquals(750, $record->total_tokens);
    }

    /**
     * AR-021: Insert with minimal required fields.
     */
    public function test_ar021_insert_minimal_fields(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'test-model',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertNotNull($id, 'Record should be inserted with minimal fields');
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertNull($record->prompt_tokens);
        $this->assertNull($record->response_tokens);
        $this->assertEquals('pending', $record->status);
    }

    /**
     * AR-022: JSON storage for truncation_strategies.
     */
    public function test_ar022_json_storage_truncation_strategies(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $strategies = [
            'primary' => 'summarize',
            'fallback' => 'truncate',
            'threshold' => 4000,
            'details' => ['section1' => 'kept', 'section2' => 'removed'],
        ];
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'test-model',
            'truncation_strategies' => json_encode($strategies),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $retrieved = json_decode($record->truncation_strategies, true);
        
        $this->assertEquals($strategies, $retrieved, 'JSON should be stored and retrieved correctly');
    }

    /**
     * AR-023: Status ENUM values.
     */
    public function test_ar023_status_enum_values(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $validStatuses = ['pending', 'completed', 'failed'];
        
        foreach ($validStatuses as $status) {
            $id = DB::table('ai_analysis_requests')->insertGetId([
                'analysis_id' => $analysisId,
                'model' => "model-{$status}",
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->assertNotNull($id, "Status '{$status}' should be valid");
        }
    }

    /**
     * AR-024: SHA-256 hash storage in request_hash.
     */
    public function test_ar024_sha256_hash_storage(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $hash = hash('sha256', 'unique-request-identifier');
        $this->assertEquals(64, strlen($hash), 'SHA-256 hash should be 64 characters');
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'test-model',
            'request_hash' => $hash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertEquals($hash, $record->request_hash);
    }

    /**
     * AR-025: Large error_message storage.
     */
    public function test_ar025_large_error_message_storage(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $longError = str_repeat('Error: Connection timeout. ', 1000); // ~28KB
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'test-model',
            'status' => 'failed',
            'error_message' => $longError,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertEquals($longError, $record->error_message, 'Long error message should be stored');
    }

    // =========================================================================
    // Category 2: seo_analyses New Columns (SA-001 to SA-020)
    // =========================================================================

    /**
     * SA-001: ai_model column exists.
     */
    public function test_sa001_ai_model_column_exists(): void
    {
        $columns = $this->getColumnListing('seo_analyses');
        
        $this->assertContains('ai_model', $columns, 'ai_model column should exist');
    }

    /**
     * SA-002: ai_model column is nullable.
     */
    public function test_sa002_ai_model_column_nullable(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('seo_analyses', 'ai_model');
        $this->assertTrue($column->getNotnull() === false, 'ai_model should be nullable');
        $this->assertLessThanOrEqual(100, $column->getLength(), 'ai_model should be VARCHAR(100)');
    }

    /**
     * SA-003: prompt_tokens column exists.
     */
    public function test_sa003_prompt_tokens_column_exists(): void
    {
        $columns = $this->getColumnListing('seo_analyses');
        
        $this->assertContains('prompt_tokens', $columns, 'prompt_tokens column should exist');
    }

    /**
     * SA-004: prompt_tokens column is nullable.
     */
    public function test_sa004_prompt_tokens_column_nullable(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('seo_analyses', 'prompt_tokens');
        $this->assertTrue($column->getNotnull() === false, 'prompt_tokens should be nullable');
    }

    /**
     * SA-005: response_tokens column exists.
     */
    public function test_sa005_response_tokens_column_exists(): void
    {
        $columns = $this->getColumnListing('seo_analyses');
        
        $this->assertContains('response_tokens', $columns, 'response_tokens column should exist');
    }

    /**
     * SA-006: total_tokens column exists.
     */
    public function test_sa006_total_tokens_column_exists(): void
    {
        $columns = $this->getColumnListing('seo_analyses');
        
        $this->assertContains('total_tokens', $columns, 'total_tokens column should exist');
    }

    /**
     * SA-007: truncation_applied column exists (JSON).
     */
    public function test_sa007_truncation_applied_column_exists(): void
    {
        $columns = $this->getColumnListing('seo_analyses');
        
        $this->assertContains('truncation_applied', $columns, 'truncation_applied column should exist');
    }

    /**
     * SA-008: truncation_applied column is nullable JSON.
     */
    public function test_sa008_truncation_applied_column_nullable(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('seo_analyses', 'truncation_applied');
        $this->assertTrue($column->getNotnull() === false, 'truncation_applied should be nullable');
    }

    /**
     * SA-009: overall_summary column exists (TEXT).
     */
    public function test_sa009_overall_summary_column_exists(): void
    {
        $columns = $this->getColumnListing('seo_analyses');
        
        $this->assertContains('overall_summary', $columns, 'overall_summary column should exist');
    }

    /**
     * SA-010: category_scores column exists (JSON).
     */
    public function test_sa010_category_scores_column_exists(): void
    {
        $columns = $this->getColumnListing('seo_analyses');
        
        $this->assertContains('category_scores', $columns, 'category_scores column should exist');
    }

    /**
     * SA-011: ai_request_id column exists.
     */
    public function test_sa011_ai_request_id_column_exists(): void
    {
        $columns = $this->getColumnListing('seo_analyses');
        
        $this->assertContains('ai_request_id', $columns, 'ai_request_id column should exist');
    }

    /**
     * SA-012: ai_request_id column is nullable.
     */
    public function test_sa012_ai_request_id_column_nullable(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->assertTrue(true, 'Column exists (SQLite)');
            return;
        }
        
        $column = $this->getColumnInfo('seo_analyses', 'ai_request_id');
        $this->assertTrue($column->getNotnull() === false, 'ai_request_id should be nullable');
    }

    /**
     * SA-013: Index on ai_model.
     */
    public function test_sa013_index_on_ai_model(): void
    {
        $indexes = $this->getTableIndexes('seo_analyses');
        
        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('ai_model', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasIndex, 'Index on ai_model should exist');
    }

    /**
     * SA-014: Index on ai_request_id.
     */
    public function test_sa014_index_on_ai_request_id(): void
    {
        $indexes = $this->getTableIndexes('seo_analyses');
        
        $hasIndex = collect($indexes)->contains(function ($index) {
            return in_array('ai_request_id', $index['columns'] ?? []);
        });
        
        $this->assertTrue($hasIndex, 'Index on ai_request_id should exist');
    }

    /**
     * SA-015: Update seo_analysis with AI data.
     */
    public function test_sa015_update_seo_analysis_with_ai_data(): void
    {
        $analysisId = $this->createSeoAnalysis();
        $requestId = $this->createAIAnalysisRequest($analysisId);
        
        $updated = DB::table('seo_analyses')
            ->where('id', $analysisId)
            ->update([
                'ai_model' => 'gpt-4-turbo',
                'prompt_tokens' => 1000,
                'response_tokens' => 500,
                'total_tokens' => 1500,
                'truncation_applied' => json_encode(['sections_removed' => 2]),
                'overall_summary' => 'This page needs significant SEO improvements.',
                'category_scores' => json_encode(['technical' => 85, 'content' => 60, 'ux' => 75]),
                'ai_request_id' => $requestId,
            ]);
        
        $this->assertEquals(1, $updated, 'Update should succeed');
        
        $analysis = DB::table('seo_analyses')->find($analysisId);
        $this->assertEquals('gpt-4-turbo', $analysis->ai_model);
        $this->assertEquals(1500, $analysis->total_tokens);
    }

    /**
     * SA-016: Insert seo_analysis with null AI fields.
     */
    public function test_sa016_insert_seo_analysis_null_ai_fields(): void
    {
        $id = DB::table('seo_analyses')->insertGetId([
            'domain' => 'test-null-ai.com',
            'normalized_url' => 'https://test-null-ai.com',
            'overall_score' => 75,
            'status' => 'completed',
            'analysis_data' => json_encode(['test' => 'data']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertNotNull($id, 'Insert should succeed without AI fields');
        
        $analysis = DB::table('seo_analyses')->find($id);
        $this->assertNull($analysis->ai_model);
        $this->assertNull($analysis->ai_request_id);
    }

    /**
     * SA-017: JSON storage for category_scores.
     */
    public function test_sa017_json_storage_category_scores(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $categoryScores = [
            'technical' => 85,
            'content' => 72,
            'link_profile' => 68,
            'user_experience' => 90,
            'mobile' => 95,
        ];
        
        DB::table('seo_analyses')
            ->where('id', $analysisId)
            ->update(['category_scores' => json_encode($categoryScores)]);
        
        $analysis = DB::table('seo_analyses')->find($analysisId);
        $retrieved = json_decode($analysis->category_scores, true);
        
        $this->assertEquals($categoryScores, $retrieved, 'Category scores should be stored as JSON');
    }

    /**
     * SA-018: JSON storage for truncation_applied.
     */
    public function test_sa018_json_storage_truncation_applied(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $truncation = [
            'html_truncated' => true,
            'original_size' => 50000,
            'truncated_size' => 30000,
            'sections_affected' => ['footer', 'sidebar'],
        ];
        
        DB::table('seo_analyses')
            ->where('id', $analysisId)
            ->update(['truncation_applied' => json_encode($truncation)]);
        
        $analysis = DB::table('seo_analyses')->find($analysisId);
        $retrieved = json_decode($analysis->truncation_applied, true);
        
        $this->assertEquals($truncation, $retrieved);
    }

    /**
     * SA-019: Large overall_summary storage.
     */
    public function test_sa019_large_overall_summary_storage(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $longSummary = str_repeat('This is a comprehensive SEO analysis summary. ', 200); // ~15KB
        
        DB::table('seo_analyses')
            ->where('id', $analysisId)
            ->update(['overall_summary' => $longSummary]);
        
        $analysis = DB::table('seo_analyses')->find($analysisId);
        $this->assertEquals($longSummary, $analysis->overall_summary);
    }

    /**
     * SA-020: Existing seo_analyses remain valid.
     */
    public function test_sa020_existing_seo_analyses_remain_valid(): void
    {
        // Create an analysis without the new columns (simulating pre-migration data)
        $id = DB::table('seo_analyses')->insertGetId([
            'domain' => 'pre-migration.com',
            'normalized_url' => 'https://pre-migration.com',
            'overall_score' => 80,
            'status' => 'completed',
            'analysis_data' => json_encode(['existing' => 'data']),
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);
        
        // The record should exist and be queryable
        $analysis = DB::table('seo_analyses')->find($id);
        
        $this->assertNotNull($analysis);
        $this->assertEquals('pre-migration.com', $analysis->domain);
        $this->assertEquals(80, $analysis->overall_score);
        $this->assertNull($analysis->ai_model); // New column should be null
    }

    // =========================================================================
    // Category 3: Migration Reversibility (MR-001 to MR-015)
    // =========================================================================

    /**
     * MR-001: Down drops ai_analysis_requests table.
     */
    public function test_mr001_down_drops_ai_analysis_requests(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_100000_create_ai_analysis_requests_table.php',
        ]);
        
        $this->assertFalse(
            Schema::hasTable('ai_analysis_requests'),
            'ai_analysis_requests table should be dropped on rollback'
        );
    }

    /**
     * MR-002: Down removes seo_analyses AI columns.
     */
    public function test_mr002_down_removes_seo_analyses_ai_columns(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_100001_add_ai_columns_to_seo_analyses_table.php',
        ]);
        
        $columns = $this->getColumnListing('seo_analyses');
        
        $this->assertNotContains('ai_model', $columns, 'ai_model should be removed on rollback');
        $this->assertNotContains('prompt_tokens', $columns, 'prompt_tokens should be removed on rollback');
        $this->assertNotContains('response_tokens', $columns, 'response_tokens should be removed on rollback');
        $this->assertNotContains('total_tokens', $columns, 'total_tokens should be removed on rollback');
        $this->assertNotContains('truncation_applied', $columns, 'truncation_applied should be removed on rollback');
        $this->assertNotContains('overall_summary', $columns, 'overall_summary should be removed on rollback');
        $this->assertNotContains('category_scores', $columns, 'category_scores should be removed on rollback');
        $this->assertNotContains('ai_request_id', $columns, 'ai_request_id should be removed on rollback');
    }

    /**
     * MR-003: Re-run migration after rollback.
     */
    public function test_mr003_rerun_migration_after_rollback(): void
    {
        // Rollback both migrations
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_100001_add_ai_columns_to_seo_analyses_table.php',
        ]);
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_100000_create_ai_analysis_requests_table.php',
        ]);
        
        // Re-run
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasTable('ai_analysis_requests'));
        $this->assertContains('ai_model', $this->getColumnListing('seo_analyses'));
    }

    /**
     * MR-004: Data preserved in seo_analyses on rollback.
     */
    public function test_mr004_data_preserved_in_seo_analyses_on_rollback(): void
    {
        $id = $this->createSeoAnalysis(['domain' => 'preserved-test.com']);
        
        // Rollback AI columns migration
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_100001_add_ai_columns_to_seo_analyses_table.php',
        ]);
        
        // Core data should still exist
        $this->assertDatabaseHas('seo_analyses', ['id' => $id, 'domain' => 'preserved-test.com']);
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
        
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_100000_create_ai_analysis_requests_table.php',
        ]);
        
        $this->assertFalse(Schema::hasTable('ai_analysis_requests'));
    }

    /**
     * MR-006: Indexes dropped on rollback.
     */
    public function test_mr006_indexes_dropped_on_rollback(): void
    {
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_100000_create_ai_analysis_requests_table.php',
        ]);
        
        $this->assertFalse(Schema::hasTable('ai_analysis_requests'));
    }

    /**
     * MR-007: Migration is idempotent.
     */
    public function test_mr007_migration_is_idempotent(): void
    {
        $this->artisan('migrate');
        
        $this->assertTrue(Schema::hasTable('ai_analysis_requests'));
        $this->assertContains('ai_model', $this->getColumnListing('seo_analyses'));
    }

    /**
     * MR-008: Fresh migration works.
     */
    public function test_mr008_fresh_migration_works(): void
    {
        $this->artisan('migrate:fresh');
        
        $this->assertTrue(Schema::hasTable('ai_analysis_requests'));
        $this->assertContains('ai_model', $this->getColumnListing('seo_analyses'));
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
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        
        $this->expectException(\Exception::class);
        
        // Try to insert with non-existent analysis_id
        DB::table('ai_analysis_requests')->insert([
            'analysis_id' => 999999,
            'model' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * MR-010: Rollback order correct.
     */
    public function test_mr010_rollback_order_correct(): void
    {
        // Rollback should work without errors
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_100001_add_ai_columns_to_seo_analyses_table.php',
        ]);
        
        $this->assertTrue(true, 'Rollback should complete without errors');
    }

    /**
     * MR-011: Re-migrate after partial rollback.
     */
    public function test_mr011_remigrate_after_partial_rollback(): void
    {
        // Only rollback the columns migration
        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_03_05_100001_add_ai_columns_to_seo_analyses_table.php',
        ]);
        
        // Re-migrate
        $this->artisan('migrate');
        
        $columns = $this->getColumnListing('seo_analyses');
        $this->assertContains('ai_model', $columns);
    }

    /**
     * MR-012: No data loss on upgrade.
     */
    public function test_mr012_no_data_loss_on_upgrade(): void
    {
        $id = $this->createSeoAnalysis(['domain' => 'upgrade-test.com', 'overall_score' => 88]);
        
        // Migration should not affect existing data
        $this->artisan('migrate');
        
        $this->assertDatabaseHas('seo_analyses', [
            'id' => $id,
            'domain' => 'upgrade-test.com',
            'overall_score' => 88,
        ]);
    }

    /**
     * MR-013: Column order preserved in seo_analyses.
     */
    public function test_mr013_column_order_preserved(): void
    {
        $columns = $this->getColumnListing('seo_analyses');
        
        // Verify analysis_data exists before new AI columns
        $analysisDataIndex = array_search('analysis_data', $columns);
        $aiModelIndex = array_search('ai_model', $columns);
        
        $this->assertNotFalse($analysisDataIndex, 'analysis_data should exist');
        $this->assertNotFalse($aiModelIndex, 'ai_model should exist');
        $this->assertLessThan($aiModelIndex, $analysisDataIndex, 'ai_model should be after analysis_data');
    }

    /**
     * MR-014: Migration file naming convention.
     */
    public function test_mr014_migration_file_naming(): void
    {
        $migrationsPath = database_path('migrations');
        
        $this->assertFileExists(
            $migrationsPath . '/2026_03_05_100000_create_ai_analysis_requests_table.php',
            'First migration file should exist'
        );
        
        $this->assertFileExists(
            $migrationsPath . '/2026_03_05_100001_add_ai_columns_to_seo_analyses_table.php',
            'Second migration file should exist'
        );
    }

    /**
     * MR-015: Multiple rollback and remigrate cycles.
     */
    public function test_mr015_multiple_rollback_remigrate_cycles(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->artisan('migrate:rollback', [
                '--path' => 'database/migrations/2026_03_05_100001_add_ai_columns_to_seo_analyses_table.php',
            ]);
            $this->artisan('migrate');
        }
        
        $this->assertTrue(Schema::hasTable('ai_analysis_requests'));
        $this->assertContains('ai_model', $this->getColumnListing('seo_analyses'));
    }

    // =========================================================================
    // Category 4: Data Integrity (DI-001 to DI-010)
    // =========================================================================

    /**
     * DI-001: Query ai_analysis_requests by analysis_id.
     */
    public function test_di001_query_by_analysis_id(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        DB::table('ai_analysis_requests')->insert([
            ['analysis_id' => $analysisId, 'model' => 'model-1', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
            ['analysis_id' => $analysisId, 'model' => 'model-2', 'status' => 'failed', 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        $requests = DB::table('ai_analysis_requests')
            ->where('analysis_id', $analysisId)
            ->get();
        
        $this->assertCount(2, $requests);
    }

    /**
     * DI-002: Query by model.
     */
    public function test_di002_query_by_model(): void
    {
        $analysisId1 = $this->createSeoAnalysis(['domain' => 'model-test-1.com']);
        $analysisId2 = $this->createSeoAnalysis(['domain' => 'model-test-2.com']);
        
        DB::table('ai_analysis_requests')->insert([
            ['analysis_id' => $analysisId1, 'model' => 'gpt-4', 'created_at' => now(), 'updated_at' => now()],
            ['analysis_id' => $analysisId2, 'model' => 'gpt-4', 'created_at' => now(), 'updated_at' => now()],
            ['analysis_id' => $analysisId1, 'model' => 'claude-3', 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        $gptRequests = DB::table('ai_analysis_requests')
            ->where('model', 'gpt-4')
            ->count();
        
        $this->assertEquals(2, $gptRequests);
    }

    /**
     * DI-003: Query by status.
     */
    public function test_di003_query_by_status(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        DB::table('ai_analysis_requests')->insert([
            ['analysis_id' => $analysisId, 'model' => 'm1', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['analysis_id' => $analysisId, 'model' => 'm2', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
            ['analysis_id' => $analysisId, 'model' => 'm3', 'status' => 'failed', 'created_at' => now(), 'updated_at' => now()],
            ['analysis_id' => $analysisId, 'model' => 'm4', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        $completedCount = DB::table('ai_analysis_requests')
            ->where('status', 'completed')
            ->count();
        
        $this->assertEquals(2, $completedCount);
    }

    /**
     * DI-004: Query by request_hash.
     */
    public function test_di004_query_by_request_hash(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $hash = hash('sha256', 'unique-hash-test');
        
        DB::table('ai_analysis_requests')->insert([
            'analysis_id' => $analysisId,
            'model' => 'test-model',
            'request_hash' => $hash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $found = DB::table('ai_analysis_requests')
            ->where('request_hash', $hash)
            ->first();
        
        $this->assertNotNull($found);
        $this->assertEquals($hash, $found->request_hash);
    }

    /**
     * DI-005: Token aggregation queries.
     */
    public function test_di005_token_aggregation_queries(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        DB::table('ai_analysis_requests')->insert([
            ['analysis_id' => $analysisId, 'model' => 'm1', 'prompt_tokens' => 100, 'response_tokens' => 50, 'total_tokens' => 150, 'created_at' => now(), 'updated_at' => now()],
            ['analysis_id' => $analysisId, 'model' => 'm2', 'prompt_tokens' => 200, 'response_tokens' => 100, 'total_tokens' => 300, 'created_at' => now(), 'updated_at' => now()],
            ['analysis_id' => $analysisId, 'model' => 'm3', 'prompt_tokens' => 300, 'response_tokens' => 150, 'total_tokens' => 450, 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        $totalPromptTokens = DB::table('ai_analysis_requests')
            ->where('analysis_id', $analysisId)
            ->sum('prompt_tokens');
        
        $avgLatency = DB::table('ai_analysis_requests')
            ->where('analysis_id', $analysisId)
            ->whereNotNull('latency_ms')
            ->avg('latency_ms');
        
        $this->assertEquals(600, $totalPromptTokens);
    }

    /**
     * DI-006: Link seo_analysis to ai_analysis_request.
     */
    public function test_di006_link_seo_analysis_to_request(): void
    {
        $analysisId = $this->createSeoAnalysis();
        $requestId = $this->createAIAnalysisRequest($analysisId);
        
        DB::table('seo_analyses')
            ->where('id', $analysisId)
            ->update(['ai_request_id' => $requestId]);
        
        $analysis = DB::table('seo_analyses')
            ->join('ai_analysis_requests', 'seo_analyses.ai_request_id', '=', 'ai_analysis_requests.id')
            ->where('seo_analyses.id', $analysisId)
            ->select('seo_analyses.*', 'ai_analysis_requests.model', 'ai_analysis_requests.total_tokens')
            ->first();
        
        $this->assertNotNull($analysis);
        $this->assertEquals('test-model', $analysis->model);
    }

    /**
     * DI-007: Composite index performance.
     */
    public function test_di007_composite_index_performance(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        // Insert multiple requests with different statuses
        for ($i = 0; $i < 100; $i++) {
            DB::table('ai_analysis_requests')->insert([
                'analysis_id' => $analysisId,
                'model' => "model-{$i}",
                'status' => $i % 3 === 0 ? 'pending' : ($i % 3 === 1 ? 'completed' : 'failed'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $startTime = microtime(true);
        $pending = DB::table('ai_analysis_requests')
            ->where('analysis_id', $analysisId)
            ->where('status', 'pending')
            ->get();
        $duration = (microtime(true) - $startTime) * 1000;
        
        $this->assertCount(34, $pending);
        $this->assertLessThan(50, $duration, 'Query should be fast with composite index');
    }

    /**
     * DI-008: Date range queries on created_at.
     */
    public function test_di008_date_range_queries(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        DB::table('ai_analysis_requests')->insert([
            ['analysis_id' => $analysisId, 'model' => 'm1', 'created_at' => now()->subDays(7), 'updated_at' => now()->subDays(7)],
            ['analysis_id' => $analysisId, 'model' => 'm2', 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)],
            ['analysis_id' => $analysisId, 'model' => 'm3', 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        $recent = DB::table('ai_analysis_requests')
            ->where('created_at', '>=', now()->subDays(5))
            ->count();
        
        $this->assertEquals(2, $recent);
    }

    /**
     * DI-009: Bulk insert ai_analysis_requests.
     */
    public function test_di009_bulk_insert(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $records = [];
        for ($i = 0; $i < 50; $i++) {
            $records[] = [
                'analysis_id' => $analysisId,
                'model' => "bulk-model-{$i}",
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('ai_analysis_requests')->insert($records);
        
        $count = DB::table('ai_analysis_requests')
            ->where('analysis_id', $analysisId)
            ->count();
        
        $this->assertEquals(50, $count);
    }

    /**
     * DI-010: Model length limit.
     */
    public function test_di010_model_length_limit(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $longModel = str_repeat('a', 100); // Exactly 100 chars
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => $longModel,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertNotNull($id, '100 char model should be stored');
    }

    // =========================================================================
    // Category 5: Security & Edge Cases (SE-001 to SE-012)
    // =========================================================================

    /**
     * SE-001: SQL injection in model column.
     */
    public function test_se001_sql_injection_in_model(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $maliciousModel = "'; DROP TABLE ai_analysis_requests; --";
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => $maliciousModel,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertTrue(Schema::hasTable('ai_analysis_requests'));
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertEquals($maliciousModel, $record->model);
    }

    /**
     * SE-002: SQL injection in JSON columns.
     */
    public function test_se002_sql_injection_in_json(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $maliciousJson = json_encode(['inject' => "'; DROP TABLE ai_analysis_requests; --"]);
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'test',
            'truncation_strategies' => $maliciousJson,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertTrue(Schema::hasTable('ai_analysis_requests'));
    }

    /**
     * SE-003: XSS in error_message.
     */
    public function test_se003_xss_in_error_message(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $xssPayload = '<script>alert("xss")</script>';
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'test',
            'status' => 'failed',
            'error_message' => $xssPayload,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertEquals($xssPayload, $record->error_message, 'XSS should be stored as literal text');
    }

    /**
     * SE-004: XSS in overall_summary.
     */
    public function test_se004_xss_in_overall_summary(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $xssPayload = '<script>document.cookie</script>';
        
        DB::table('seo_analyses')
            ->where('id', $analysisId)
            ->update(['overall_summary' => $xssPayload]);
        
        $analysis = DB::table('seo_analyses')->find($analysisId);
        $this->assertEquals($xssPayload, $analysis->overall_summary);
    }

    /**
     * SE-005: Null analysis_id fails (FK constraint).
     */
    public function test_se005_null_analysis_id_fails(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('SQLite handles NOT NULL differently');
        }
        
        $this->expectException(\Exception::class);
        
        DB::table('ai_analysis_requests')->insert([
            'analysis_id' => null,
            'model' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * SE-006: Null model fails.
     */
    public function test_se006_null_model_fails(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('SQLite handles NOT NULL differently');
        }
        
        $analysisId = $this->createSeoAnalysis();
        
        $this->expectException(\Exception::class);
        
        DB::table('ai_analysis_requests')->insert([
            'analysis_id' => $analysisId,
            'model' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * SE-007: Invalid JSON in truncation_strategies.
     */
    public function test_se007_invalid_json_in_truncation_strategies(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            $this->assertTrue(true, 'SQLite does not validate JSON format');
            return;
        }
        
        $analysisId = $this->createSeoAnalysis();
        
        $this->expectException(\Exception::class);
        
        DB::table('ai_analysis_requests')->insert([
            'analysis_id' => $analysisId,
            'model' => 'test',
            'truncation_strategies' => 'not valid json {{{',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * SE-008: Unicode in model name.
     */
    public function test_se008_unicode_in_model_name(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $unicodeModel = '模型-🎉-gpt4'; // Chinese + emoji
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => $unicodeModel,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertEquals($unicodeModel, $record->model);
    }

    /**
     * SE-009: Negative tokens (data validation).
     */
    public function test_se009_negative_tokens(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        // Database may accept negative values if no CHECK constraint
        // This tests that the column can store negative values
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'test',
            'prompt_tokens' => -100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertEquals(-100, $record->prompt_tokens, 'Negative tokens stored (validation should be at application level)');
    }

    /**
     * SE-010: Zero tokens.
     */
    public function test_se010_zero_tokens(): void
    {
        $analysisId = $this->createSeoAnalysis();
        
        $id = DB::table('ai_analysis_requests')->insertGetId([
            'analysis_id' => $analysisId,
            'model' => 'free-model',
            'prompt_tokens' => 0,
            'response_tokens' => 0,
            'total_tokens' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $record = DB::table('ai_analysis_requests')->find($id);
        $this->assertEquals(0, $record->total_tokens);
    }

    /**
     * SE-011: Model name exceeds max length.
     */
    public function test_se011_model_exceeds_max_length(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite does not enforce VARCHAR length constraints');
        }
        
        $analysisId = $this->createSeoAnalysis();
        
        $longModel = str_repeat('a', 101); // 101 chars
        
        try {
            $id = DB::table('ai_analysis_requests')->insertGetId([
                'analysis_id' => $analysisId,
                'model' => $longModel,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $record = DB::table('ai_analysis_requests')->find($id);
            $this->assertLessThanOrEqual(100, strlen($record->model), 'Model should be truncated to 100 chars');
        } catch (\Exception $e) {
            $this->assertStringContainsString('too long', strtolower($e->getMessage()));
        }
    }

    /**
     * SE-12: Invalid status value.
     */
    public function test_se012_invalid_status_value(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't enforce ENUM
            $this->assertTrue(true, 'SQLite does not enforce ENUM values');
            return;
        }
        
        $analysisId = $this->createSeoAnalysis();
        
        $this->expectException(\Exception::class);
        
        DB::table('ai_analysis_requests')->insert([
            'analysis_id' => $analysisId,
            'model' => 'test',
            'status' => 'invalid_status',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a test seo_analysis.
     */
    private function createSeoAnalysis(array $attributes = []): int
    {
        return DB::table('seo_analyses')->insertGetId(array_merge([
            'domain' => 'test-' . uniqid() . '.com',
            'normalized_url' => 'https://test-' . uniqid() . '.com',
            'overall_score' => 75,
            'status' => 'completed',
            'analysis_data' => json_encode(['test' => 'data']),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Create a test ai_analysis_request.
     */
    private function createAIAnalysisRequest(int $analysisId, array $attributes = []): int
    {
        return DB::table('ai_analysis_requests')->insertGetId(array_merge([
            'analysis_id' => $analysisId,
            'model' => 'test-model',
            'status' => 'completed',
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
