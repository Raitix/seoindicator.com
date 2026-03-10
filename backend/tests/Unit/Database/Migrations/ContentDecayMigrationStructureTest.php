<?php

namespace Tests\Unit\Database\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Unit tests for Content Decay database migration column structures.
 * 
 * Tests verify precise column types, nullable states, default values,
 * and length constraints for all 4 tables required by SPEC-036.
 * 
 * @see TESTS/TEST-36-1 for test documentation
 * @see TASK-36-1 for task details
 */
class ContentDecayMigrationStructureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Check if we're running on SQLite.
     */
    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Get column info for SQLite.
     */
    private function getSqliteColumnInfo(string $table): array
    {
        $columns = [];
        $result = DB::select("PRAGMA table_info({$table})");
        foreach ($result as $row) {
            $columns[$row->name] = $row;
        }
        return $columns;
    }

    // =========================================================================
    // Column Existence Tests
    // =========================================================================

    /** @test */
    public function content_decay_projects_has_all_required_columns(): void
    {
        $columns = [
            'id', 'user_id', 'site_id', 'detection_enabled', 'window_days',
            'min_clicks_threshold', 'min_impressions_threshold', 'min_decay_score',
            'digest_enabled', 'digest_frequency', 'settings_json', 'last_scanned_at',
            'created_at', 'updated_at'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('content_decay_projects', $column), "Column {$column} should exist");
        }
    }

    /** @test */
    public function content_decay_candidates_has_all_required_columns(): void
    {
        $columns = [
            'id', 'project_id', 'page_url', 'page_url_hash', 'page_title',
            'page_type', 'severity', 'decay_score', 'detection_confidence',
            'lost_click_estimate', 'recovery_upside_estimate', 'primary_reason_code',
            'reason_codes', 'signal_sources', 'evidence_json', 'status',
            'first_detected_at', 'last_detected_at', 'planned_at', 'started_at',
            'refreshed_at', 'recovered_at', 'ignored_at', 'created_at', 'updated_at'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('content_decay_candidates', $column), "Column {$column} should exist");
        }
    }

    /** @test */
    public function content_decay_signals_has_all_required_columns(): void
    {
        $columns = [
            'id', 'candidate_id', 'previous_start', 'previous_end',
            'current_start', 'current_end', 'previous_clicks', 'current_clicks',
            'previous_impressions', 'current_impressions', 'previous_ctr',
            'current_ctr', 'previous_avg_position', 'current_avg_position',
            'previous_rank_visibility', 'current_rank_visibility',
            'previous_ai_visibility', 'current_ai_visibility',
            'unresolved_audit_issues', 'content_age_days', 'last_annotation_at',
            'evidence_json', 'created_at'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('content_decay_signals', $column), "Column {$column} should exist");
        }
    }

    /** @test */
    public function content_refresh_tasks_has_all_required_columns(): void
    {
        $columns = [
            'id', 'candidate_id', 'title', 'recommendation_type', 'description',
            'notes', 'owner_user_id', 'due_date', 'status', 'source',
            'metadata_json', 'created_at', 'updated_at'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('content_refresh_tasks', $column), "Column {$column} should exist");
        }
    }

    // =========================================================================
    // Column Type Tests (SQLite-compatible)
    // =========================================================================

    /** @test */
    public function projects_detection_enabled_is_boolean(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_projects');
            $this->assertStringContainsString('TINYINT(1)', strtoupper($columns['detection_enabled']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'detection_enabled');
            $this->assertEquals('boolean', $column->getType()->getName());
        }
    }

    /** @test */
    public function projects_window_days_is_tiny_integer(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_projects');
            $this->assertStringContainsString('INTEGER', strtoupper($columns['window_days']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'window_days');
            $this->assertEquals('smallint', $column->getType()->getName());
        }
    }

    /** @test */
    public function projects_min_clicks_threshold_is_integer(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_projects');
            $this->assertStringContainsString('INTEGER', strtoupper($columns['min_clicks_threshold']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'min_clicks_threshold');
            $this->assertEquals('integer', $column->getType()->getName());
        }
    }

    /** @test */
    public function projects_min_impressions_threshold_is_integer(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_projects');
            $this->assertStringContainsString('INTEGER', strtoupper($columns['min_impressions_threshold']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'min_impressions_threshold');
            $this->assertEquals('integer', $column->getType()->getName());
        }
    }

    /** @test */
    public function projects_min_decay_score_is_decimal(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_projects');
            $this->assertStringContainsString('NUMERIC', strtoupper($columns['min_decay_score']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'min_decay_score');
            $this->assertEquals('decimal', $column->getType()->getName());
        }
    }

    /** @test */
    public function projects_digest_enabled_is_boolean(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_projects');
            $this->assertStringContainsString('TINYINT(1)', strtoupper($columns['digest_enabled']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'digest_enabled');
            $this->assertEquals('boolean', $column->getType()->getName());
        }
    }

    /** @test */
    public function projects_digest_frequency_is_string(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_projects');
            $this->assertStringContainsString('TEXT', strtoupper($columns['digest_frequency']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'digest_frequency');
            $this->assertEquals('string', $column->getType()->getName());
        }
    }

    /** @test */
    public function projects_settings_json_is_json_type(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_projects');
            $this->assertStringContainsString('TEXT', strtoupper($columns['settings_json']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'settings_json');
            $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        }
    }

    /** @test */
    public function candidates_page_url_is_string(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_candidates');
            $this->assertStringContainsString('TEXT', strtoupper($columns['page_url']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'page_url');
            $this->assertEquals('string', $column->getType()->getName());
        }
    }

    /** @test */
    public function candidates_page_url_hash_is_fixed_length_string(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_candidates');
            $this->assertStringContainsString('CHAR', strtoupper($columns['page_url_hash']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'page_url_hash');
            $this->assertEquals('string', $column->getType()->getName());
            $this->assertEquals(64, $column->getLength());
        }
    }

    /** @test */
    public function candidates_decay_score_is_decimal(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_candidates');
            $this->assertStringContainsString('NUMERIC', strtoupper($columns['decay_score']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'decay_score');
            $this->assertEquals('decimal', $column->getType()->getName());
        }
    }

    /** @test */
    public function candidates_detection_confidence_is_decimal(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_candidates');
            $this->assertStringContainsString('NUMERIC', strtoupper($columns['detection_confidence']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'detection_confidence');
            $this->assertEquals('decimal', $column->getType()->getName());
        }
    }

    /** @test */
    public function candidates_reason_codes_is_json(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_candidates');
            $this->assertStringContainsString('TEXT', strtoupper($columns['reason_codes']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'reason_codes');
            $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        }
    }

    /** @test */
    public function candidates_signal_sources_is_json(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_candidates');
            $this->assertStringContainsString('TEXT', strtoupper($columns['signal_sources']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'signal_sources');
            $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        }
    }

    /** @test */
    public function candidates_evidence_json_is_json(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_candidates');
            $this->assertStringContainsString('TEXT', strtoupper($columns['evidence_json']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'evidence_json');
            $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        }
    }

    /** @test */
    public function signals_previous_start_is_date(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_signals');
            $this->assertTrue(
                str_contains(strtoupper($columns['previous_start']->type), 'TEXT') ||
                str_contains(strtoupper($columns['previous_start']->type), 'DATE'),
                'previous_start should be TEXT or DATE type'
            );
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'previous_start');
            $this->assertEquals('date', $column->getType()->getName());
        }
    }

    /** @test */
    public function signals_previous_ctr_is_decimal(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_signals');
            $this->assertStringContainsString('NUMERIC', strtoupper($columns['previous_ctr']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'previous_ctr');
            $this->assertEquals('decimal', $column->getType()->getName());
        }
    }

    /** @test */
    public function signals_previous_avg_position_is_decimal(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_signals');
            $this->assertStringContainsString('NUMERIC', strtoupper($columns['previous_avg_position']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'previous_avg_position');
            $this->assertEquals('decimal', $column->getType()->getName());
        }
    }

    /** @test */
    public function signals_previous_rank_visibility_is_decimal(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_signals');
            $this->assertStringContainsString('NUMERIC', strtoupper($columns['previous_rank_visibility']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'previous_rank_visibility');
            $this->assertEquals('decimal', $column->getType()->getName());
        }
    }

    /** @test */
    public function signals_evidence_json_is_json(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_decay_signals');
            $this->assertStringContainsString('TEXT', strtoupper($columns['evidence_json']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'evidence_json');
            $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        }
    }

    /** @test */
    public function tasks_title_is_string(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_refresh_tasks');
            $this->assertStringContainsString('TEXT', strtoupper($columns['title']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_refresh_tasks', 'title');
            $this->assertEquals('string', $column->getType()->getName());
        }
    }

    /** @test */
    public function tasks_description_is_text(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_refresh_tasks');
            $this->assertStringContainsString('TEXT', strtoupper($columns['description']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_refresh_tasks', 'description');
            $this->assertEquals('text', $column->getType()->getName());
        }
    }

    /** @test */
    public function tasks_notes_is_text(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_refresh_tasks');
            $this->assertStringContainsString('TEXT', strtoupper($columns['notes']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_refresh_tasks', 'notes');
            $this->assertEquals('text', $column->getType()->getName());
        }
    }

    /** @test */
    public function tasks_due_date_is_date(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_refresh_tasks');
            $this->assertTrue(
                str_contains(strtoupper($columns['due_date']->type), 'TEXT') ||
                str_contains(strtoupper($columns['due_date']->type), 'DATE'),
                'due_date should be TEXT or DATE type'
            );
        } else {
            $column = DB::connection()->getDoctrineColumn('content_refresh_tasks', 'due_date');
            $this->assertEquals('date', $column->getType()->getName());
        }
    }

    /** @test */
    public function tasks_metadata_json_is_json(): void
    {
        if ($this->isSqlite()) {
            $columns = $this->getSqliteColumnInfo('content_refresh_tasks');
            $this->assertStringContainsString('TEXT', strtoupper($columns['metadata_json']->type));
        } else {
            $column = DB::connection()->getDoctrineColumn('content_refresh_tasks', 'metadata_json');
            $this->assertTrue(in_array($column->getType()->getName(), ['json', 'text']));
        }
    }

    // =========================================================================
    // Nullable State Tests
    // =========================================================================

    /** @test */
    public function projects_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['user_id', 'site_id'];
        $columns = $this->getSqliteColumnInfo('content_decay_projects');
        
        foreach ($requiredColumns as $col) {
            // In SQLite: notnull=1 means NOT NULL, notnull=0 means nullable
            $this->assertEquals(1, $columns[$col]->notnull, "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function projects_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['settings_json', 'last_scanned_at'];
        $columns = $this->getSqliteColumnInfo('content_decay_projects');
        
        foreach ($nullableColumns as $col) {
            $this->assertEquals(0, $columns[$col]->notnull, "Column {$col} should be nullable");
        }
    }

    /** @test */
    public function candidates_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['project_id', 'page_url', 'page_url_hash', 'decay_score', 'primary_reason_code', 'reason_codes', 'status', 'first_detected_at', 'last_detected_at'];
        $columns = $this->getSqliteColumnInfo('content_decay_candidates');
        
        foreach ($requiredColumns as $col) {
            $this->assertEquals(1, $columns[$col]->notnull, "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function candidates_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['page_title', 'page_type', 'signal_sources', 'evidence_json', 'planned_at', 'started_at', 'refreshed_at', 'recovered_at', 'ignored_at'];
        $columns = $this->getSqliteColumnInfo('content_decay_candidates');
        
        foreach ($nullableColumns as $col) {
            $this->assertEquals(0, $columns[$col]->notnull, "Column {$col} should be nullable");
        }
    }

    /** @test */
    public function signals_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['candidate_id', 'previous_start', 'previous_end', 'current_start', 'current_end'];
        $columns = $this->getSqliteColumnInfo('content_decay_signals');
        
        foreach ($requiredColumns as $col) {
            $this->assertEquals(1, $columns[$col]->notnull, "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function signals_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['previous_avg_position', 'current_avg_position', 'previous_rank_visibility', 'current_rank_visibility', 'previous_ai_visibility', 'current_ai_visibility', 'content_age_days', 'last_annotation_at', 'evidence_json'];
        $columns = $this->getSqliteColumnInfo('content_decay_signals');
        
        foreach ($nullableColumns as $col) {
            $this->assertEquals(0, $columns[$col]->notnull, "Column {$col} should be nullable");
        }
    }

    /** @test */
    public function tasks_required_columns_are_not_nullable(): void
    {
        $requiredColumns = ['candidate_id', 'title', 'recommendation_type', 'status', 'source'];
        $columns = $this->getSqliteColumnInfo('content_refresh_tasks');
        
        foreach ($requiredColumns as $col) {
            $this->assertEquals(1, $columns[$col]->notnull, "Column {$col} should not be nullable");
        }
    }

    /** @test */
    public function tasks_optional_columns_are_nullable(): void
    {
        $nullableColumns = ['description', 'notes', 'owner_user_id', 'due_date', 'metadata_json'];
        $columns = $this->getSqliteColumnInfo('content_refresh_tasks');
        
        foreach ($nullableColumns as $col) {
            $this->assertEquals(0, $columns[$col]->notnull, "Column {$col} should be nullable");
        }
    }

    // =========================================================================
    // Table Column Count Tests
    // =========================================================================

    /** @test */
    public function content_decay_projects_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('content_decay_projects');
        $expectedCount = 14; // id, user_id, site_id, detection_enabled, window_days, min_clicks_threshold, min_impressions_threshold, min_decay_score, digest_enabled, digest_frequency, settings_json, last_scanned_at, created_at, updated_at
        $this->assertCount($expectedCount, $columns, 'content_decay_projects should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function content_decay_candidates_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('content_decay_candidates');
        $expectedCount = 25; // id, project_id, page_url, page_url_hash, page_title, page_type, severity, decay_score, detection_confidence, lost_click_estimate, recovery_upside_estimate, primary_reason_code, reason_codes, signal_sources, evidence_json, status, first_detected_at, last_detected_at, planned_at, started_at, refreshed_at, recovered_at, ignored_at, created_at, updated_at
        $this->assertCount($expectedCount, $columns, 'content_decay_candidates should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function content_decay_signals_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('content_decay_signals');
        $expectedCount = 23; // id, candidate_id, previous_start, previous_end, current_start, current_end, previous_clicks, current_clicks, previous_impressions, current_impressions, previous_ctr, current_ctr, previous_avg_position, current_avg_position, previous_rank_visibility, current_rank_visibility, previous_ai_visibility, current_ai_visibility, unresolved_audit_issues, content_age_days, last_annotation_at, evidence_json, created_at
        $this->assertCount($expectedCount, $columns, 'content_decay_signals should have ' . $expectedCount . ' columns');
    }

    /** @test */
    public function content_refresh_tasks_has_expected_column_count(): void
    {
        $columns = Schema::getColumnListing('content_refresh_tasks');
        $expectedCount = 13; // id, candidate_id, title, recommendation_type, description, notes, owner_user_id, due_date, status, source, metadata_json, created_at, updated_at
        $this->assertCount($expectedCount, $columns, 'content_refresh_tasks should have ' . $expectedCount . ' columns');
    }

    // =========================================================================
    // MySQL-Specific Tests (skipped on SQLite)
    // =========================================================================

    /** @test */
    public function projects_min_decay_score_has_correct_precision(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite stores decimals as REAL without precision constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'min_decay_score');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(5, $column->getPrecision());
        $this->assertEquals(2, $column->getScale());
    }

    /** @test */
    public function candidates_decay_score_has_correct_precision(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite stores decimals as REAL without precision constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'decay_score');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(5, $column->getPrecision());
        $this->assertEquals(2, $column->getScale());
    }

    /** @test */
    public function candidates_detection_confidence_has_correct_precision(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite stores decimals as REAL without precision constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'detection_confidence');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(5, $column->getPrecision());
        $this->assertEquals(2, $column->getScale());
    }

    /** @test */
    public function candidates_page_url_max_length_is_2048(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite does not enforce VARCHAR length constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'page_url');
        $this->assertEquals(2048, $column->getLength());
    }

    /** @test */
    public function candidates_page_url_hash_max_length_is_64(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite does not enforce CHAR length constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'page_url_hash');
        $this->assertEquals(64, $column->getLength());
    }

    /** @test */
    public function candidates_page_title_max_length_is_500(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite does not enforce VARCHAR length constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'page_title');
        $this->assertEquals(500, $column->getLength());
    }

    /** @test */
    public function signals_ctr_has_correct_precision(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite stores decimals as REAL without precision constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'previous_ctr');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(8, $column->getPrecision());
        $this->assertEquals(4, $column->getScale());
    }

    /** @test */
    public function signals_avg_position_has_correct_precision(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite stores decimals as REAL without precision constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'previous_avg_position');
        $this->assertEquals('decimal', $column->getType()->getName());
        $this->assertEquals(8, $column->getPrecision());
        $this->assertEquals(2, $column->getScale());
    }

    /** @test */
    public function tasks_title_max_length_is_255(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite does not enforce VARCHAR length constraints.');
        }
        $column = DB::connection()->getDoctrineColumn('content_refresh_tasks', 'title');
        $this->assertEquals(255, $column->getLength());
    }

    // =========================================================================
    // Default Value Tests
    // =========================================================================

    /** @test */
    public function projects_detection_enabled_defaults_to_true(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'detection_enabled');
        $this->assertTrue($column->getDefault());
    }

    /** @test */
    public function projects_window_days_defaults_to_28(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'window_days');
        $this->assertEquals(28, $column->getDefault());
    }

    /** @test */
    public function projects_min_clicks_threshold_defaults_to_10(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'min_clicks_threshold');
        $this->assertEquals(10, $column->getDefault());
    }

    /** @test */
    public function projects_min_impressions_threshold_defaults_to_100(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'min_impressions_threshold');
        $this->assertEquals(100, $column->getDefault());
    }

    /** @test */
    public function projects_min_decay_score_defaults_to_20(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'min_decay_score');
        $this->assertEquals('20.00', $column->getDefault());
    }

    /** @test */
    public function projects_digest_enabled_defaults_to_true(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'digest_enabled');
        $this->assertTrue($column->getDefault());
    }

    /** @test */
    public function projects_digest_frequency_defaults_to_weekly(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_projects', 'digest_frequency');
        $this->assertEquals('weekly', $column->getDefault());
    }

    /** @test */
    public function candidates_detection_confidence_defaults_to_085(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'detection_confidence');
        $this->assertEquals('0.85', $column->getDefault());
    }

    /** @test */
    public function candidates_page_type_defaults_to_other(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'page_type');
        $this->assertEquals('other', $column->getDefault());
    }

    /** @test */
    public function candidates_severity_defaults_to_medium(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'severity');
        $this->assertEquals('medium', $column->getDefault());
    }

    /** @test */
    public function candidates_status_defaults_to_backlog(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'status');
        $this->assertEquals('backlog', $column->getDefault());
    }

    /** @test */
    public function candidates_lost_click_estimate_defaults_to_zero(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'lost_click_estimate');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function candidates_recovery_upside_estimate_defaults_to_zero(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_candidates', 'recovery_upside_estimate');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function signals_clicks_default_to_zero(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'previous_clicks');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function signals_impressions_default_to_zero(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'previous_impressions');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function signals_ctr_default_to_zero(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'previous_ctr');
        $this->assertEquals('0.0000', $column->getDefault());
    }

    /** @test */
    public function signals_unresolved_audit_issues_defaults_to_zero(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_decay_signals', 'unresolved_audit_issues');
        $this->assertEquals(0, $column->getDefault());
    }

    /** @test */
    public function tasks_status_defaults_to_todo(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_refresh_tasks', 'status');
        $this->assertEquals('todo', $column->getDefault());
    }

    /** @test */
    public function tasks_source_defaults_to_system(): void
    {
        if ($this->isSqlite()) {
            $this->markTestSkipped('SQLite default value retrieval requires different approach.');
        }
        $column = DB::connection()->getDoctrineColumn('content_refresh_tasks', 'source');
        $this->assertEquals('system', $column->getDefault());
    }
}
