<?php

namespace Tests\Feature\Database\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feature tests for Content Decay database migrations.
 * 
 * Tests verify schema structure, indexes, foreign keys, unique constraints,
 * and rollback functionality for all 4 tables required by SPEC-036.
 * 
 * @see TESTS/TEST-36-1 for test documentation
 * @see TASK-36-1 for task details
 */
class ContentDecayMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tables created by the Content Decay migrations.
     */
    private const TABLES = [
        'content_decay_projects',
        'content_decay_candidates',
        'content_decay_signals',
        'content_refresh_tasks',
    ];

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if we're running on SQLite.
     */
    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Get SQLite column info.
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

    /**
     * Get all indexes for a table.
     */
    private function getTableIndexes(string $table): array
    {
        if ($this->isSqlite()) {
            return DB::select("PRAGMA index_list({$table})");
        }
        return DB::connection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);
    }

    /**
     * Get all foreign keys for a table.
     */
    private function getTableForeignKeys(string $table): array
    {
        if ($this->isSqlite()) {
            return DB::select("PRAGMA foreign_key_list({$table})");
        }
        return DB::connection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys($table);
    }

    /**
     * Check if table has an index on a specific column.
     */
    private function hasIndexOnColumn(array $indexes, string $column): bool
    {
        if ($this->isSqlite()) {
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
        foreach ($indexes as $index) {
            if (in_array($column, $index->getColumns())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if table has a unique index on a specific column.
     */
    private function hasUniqueIndexOnColumn(array $indexes, string $column): bool
    {
        if ($this->isSqlite()) {
            foreach ($indexes as $index) {
                if ($index->unique) {
                    $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                    foreach ($indexInfo as $info) {
                        if ($info->name === $column) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }
        foreach ($indexes as $index) {
            if ($index->isUnique() && in_array($column, $index->getColumns())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if table has a composite index on specific columns.
     */
    private function hasCompositeIndex(array $indexes, array $columns): bool
    {
        if ($this->isSqlite()) {
            foreach ($indexes as $index) {
                $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                $indexColumns = array_map(fn($info) => $info->name, $indexInfo);
                if (count($indexColumns) === count($columns) && 
                    empty(array_diff($columns, $indexColumns))) {
                    return true;
                }
            }
            return false;
        }
        foreach ($indexes as $index) {
            $indexColumns = $index->getColumns();
            if (count($indexColumns) === count($columns) && 
                empty(array_diff($columns, $indexColumns))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if table has a composite unique index on specific columns.
     */
    private function hasCompositeUniqueIndex(array $indexes, array $columns): bool
    {
        if ($this->isSqlite()) {
            foreach ($indexes as $index) {
                if ($index->unique) {
                    $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                    $indexColumns = array_map(fn($info) => $info->name, $indexInfo);
                    if (count($indexColumns) === count($columns) && 
                        empty(array_diff($columns, $indexColumns))) {
                        return true;
                    }
                }
            }
            return false;
        }
        foreach ($indexes as $index) {
            if ($index->isUnique()) {
                $indexColumns = $index->getColumns();
                if (count($indexColumns) === count($columns) && 
                    empty(array_diff($columns, $indexColumns))) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if table has a foreign key constraint.
     */
    private function hasForeignKey(array $foreignKeys, string $column, string $foreignTable, string $foreignColumn): bool
    {
        if ($this->isSqlite()) {
            foreach ($foreignKeys as $fk) {
                if ($fk->from === $column && $fk->table === $foreignTable && $fk->to === $foreignColumn) {
                    return true;
                }
            }
            return false;
        }
        foreach ($foreignKeys as $fk) {
            $localColumns = $fk->getLocalColumns();
            if (in_array($column, $localColumns)) {
                $foreignColumns = $fk->getForeignColumns();
                $tableName = $fk->getForeignTableName();
                if ($tableName === $foreignTable && in_array($foreignColumn, $foreignColumns)) {
                    return true;
                }
            }
        }
        return false;
    }

    // =========================================================================
    // content_decay_projects Table Tests (CDP)
    // =========================================================================

    /** @test */
    public function cdp_01_content_decay_projects_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('content_decay_projects'));
    }

    /** @test */
    public function cdp_02_content_decay_projects_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'id'));
    }

    /** @test */
    public function cdp_03_content_decay_projects_has_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'user_id'));
    }

    /** @test */
    public function cdp_04_content_decay_projects_has_site_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'site_id'));
    }

    /** @test */
    public function cdp_05_content_decay_projects_has_detection_enabled_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'detection_enabled'));
    }

    /** @test */
    public function cdp_06_content_decay_projects_has_window_days_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'window_days'));
    }

    /** @test */
    public function cdp_07_content_decay_projects_has_min_clicks_threshold_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'min_clicks_threshold'));
    }

    /** @test */
    public function cdp_08_content_decay_projects_has_min_impressions_threshold_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'min_impressions_threshold'));
    }

    /** @test */
    public function cdp_09_content_decay_projects_has_min_decay_score_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'min_decay_score'));
    }

    /** @test */
    public function cdp_10_content_decay_projects_has_digest_enabled_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'digest_enabled'));
    }

    /** @test */
    public function cdp_11_content_decay_projects_has_digest_frequency_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'digest_frequency'));
    }

    /** @test */
    public function cdp_12_content_decay_projects_has_settings_json_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'settings_json'));
    }

    /** @test */
    public function cdp_13_content_decay_projects_has_last_scanned_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'last_scanned_at'));
    }

    /** @test */
    public function cdp_14_content_decay_projects_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'created_at'));
        $this->assertTrue(Schema::hasColumn('content_decay_projects', 'updated_at'));
    }

    /** @test */
    public function cdp_15_content_decay_projects_has_unique_site_id_index(): void
    {
        $indexes = $this->getTableIndexes('content_decay_projects');
        $this->assertTrue($this->hasUniqueIndexOnColumn($indexes, 'site_id'));
    }

    /** @test */
    public function cdp_16_content_decay_projects_has_user_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('content_decay_projects');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'user_id', 'users', 'id'));
    }

    /** @test */
    public function cdp_17_content_decay_projects_has_site_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('content_decay_projects');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'site_id', 'sites', 'id'));
    }

    // =========================================================================
    // content_decay_candidates Table Tests (CDC)
    // =========================================================================

    /** @test */
    public function cdc_01_content_decay_candidates_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('content_decay_candidates'));
    }

    /** @test */
    public function cdc_02_content_decay_candidates_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'id'));
    }

    /** @test */
    public function cdc_03_content_decay_candidates_has_project_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'project_id'));
    }

    /** @test */
    public function cdc_04_content_decay_candidates_has_page_url_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'page_url'));
    }

    /** @test */
    public function cdc_05_content_decay_candidates_has_page_url_hash_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'page_url_hash'));
    }

    /** @test */
    public function cdc_06_content_decay_candidates_has_page_title_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'page_title'));
    }

    /** @test */
    public function cdc_07_content_decay_candidates_has_page_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'page_type'));
    }

    /** @test */
    public function cdc_08_content_decay_candidates_has_severity_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'severity'));
    }

    /** @test */
    public function cdc_09_content_decay_candidates_has_decay_score_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'decay_score'));
    }

    /** @test */
    public function cdc_10_content_decay_candidates_has_detection_confidence_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'detection_confidence'));
    }

    /** @test */
    public function cdc_11_content_decay_candidates_has_lost_click_estimate_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'lost_click_estimate'));
    }

    /** @test */
    public function cdc_12_content_decay_candidates_has_recovery_upside_estimate_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'recovery_upside_estimate'));
    }

    /** @test */
    public function cdc_13_content_decay_candidates_has_primary_reason_code_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'primary_reason_code'));
    }

    /** @test */
    public function cdc_14_content_decay_candidates_has_reason_codes_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'reason_codes'));
    }

    /** @test */
    public function cdc_15_content_decay_candidates_has_signal_sources_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'signal_sources'));
    }

    /** @test */
    public function cdc_16_content_decay_candidates_has_evidence_json_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'evidence_json'));
    }

    /** @test */
    public function cdc_17_content_decay_candidates_has_status_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'status'));
    }

    /** @test */
    public function cdc_18_content_decay_candidates_has_first_detected_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'first_detected_at'));
    }

    /** @test */
    public function cdc_19_content_decay_candidates_has_last_detected_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'last_detected_at'));
    }

    /** @test */
    public function cdc_20_content_decay_candidates_has_planned_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'planned_at'));
    }

    /** @test */
    public function cdc_21_content_decay_candidates_has_started_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'started_at'));
    }

    /** @test */
    public function cdc_22_content_decay_candidates_has_refreshed_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'refreshed_at'));
    }

    /** @test */
    public function cdc_23_content_decay_candidates_has_recovered_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'recovered_at'));
    }

    /** @test */
    public function cdc_24_content_decay_candidates_has_ignored_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'ignored_at'));
    }

    /** @test */
    public function cdc_25_content_decay_candidates_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'created_at'));
        $this->assertTrue(Schema::hasColumn('content_decay_candidates', 'updated_at'));
    }

    /** @test */
    public function cdc_26_content_decay_candidates_has_unique_project_page_hash_index(): void
    {
        $indexes = $this->getTableIndexes('content_decay_candidates');
        $this->assertTrue($this->hasCompositeUniqueIndex($indexes, ['project_id', 'page_url_hash']));
    }

    /** @test */
    public function cdc_27_content_decay_candidates_has_project_status_composite_index(): void
    {
        $indexes = $this->getTableIndexes('content_decay_candidates');
        $this->assertTrue($this->hasCompositeIndex($indexes, ['project_id', 'status']));
    }

    /** @test */
    public function cdc_28_content_decay_candidates_has_project_score_composite_index(): void
    {
        $indexes = $this->getTableIndexes('content_decay_candidates');
        $this->assertTrue($this->hasCompositeIndex($indexes, ['project_id', 'decay_score']));
    }

    /** @test */
    public function cdc_29_content_decay_candidates_has_project_severity_composite_index(): void
    {
        $indexes = $this->getTableIndexes('content_decay_candidates');
        $this->assertTrue($this->hasCompositeIndex($indexes, ['project_id', 'severity']));
    }

    /** @test */
    public function cdc_30_content_decay_candidates_has_project_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('content_decay_candidates');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'project_id', 'content_decay_projects', 'id'));
    }

    // =========================================================================
    // content_decay_signals Table Tests (CDS)
    // =========================================================================

    /** @test */
    public function cds_01_content_decay_signals_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('content_decay_signals'));
    }

    /** @test */
    public function cds_02_content_decay_signals_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'id'));
    }

    /** @test */
    public function cds_03_content_decay_signals_has_candidate_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'candidate_id'));
    }

    /** @test */
    public function cds_04_content_decay_signals_has_previous_start_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'previous_start'));
    }

    /** @test */
    public function cds_05_content_decay_signals_has_previous_end_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'previous_end'));
    }

    /** @test */
    public function cds_06_content_decay_signals_has_current_start_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'current_start'));
    }

    /** @test */
    public function cds_07_content_decay_signals_has_current_end_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'current_end'));
    }

    /** @test */
    public function cds_08_content_decay_signals_has_previous_clicks_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'previous_clicks'));
    }

    /** @test */
    public function cds_09_content_decay_signals_has_current_clicks_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'current_clicks'));
    }

    /** @test */
    public function cds_10_content_decay_signals_has_previous_impressions_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'previous_impressions'));
    }

    /** @test */
    public function cds_11_content_decay_signals_has_current_impressions_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'current_impressions'));
    }

    /** @test */
    public function cds_12_content_decay_signals_has_previous_ctr_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'previous_ctr'));
    }

    /** @test */
    public function cds_13_content_decay_signals_has_current_ctr_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'current_ctr'));
    }

    /** @test */
    public function cds_14_content_decay_signals_has_previous_avg_position_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'previous_avg_position'));
    }

    /** @test */
    public function cds_15_content_decay_signals_has_current_avg_position_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'current_avg_position'));
    }

    /** @test */
    public function cds_16_content_decay_signals_has_previous_rank_visibility_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'previous_rank_visibility'));
    }

    /** @test */
    public function cds_17_content_decay_signals_has_current_rank_visibility_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'current_rank_visibility'));
    }

    /** @test */
    public function cds_18_content_decay_signals_has_previous_ai_visibility_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'previous_ai_visibility'));
    }

    /** @test */
    public function cds_19_content_decay_signals_has_current_ai_visibility_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'current_ai_visibility'));
    }

    /** @test */
    public function cds_20_content_decay_signals_has_unresolved_audit_issues_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'unresolved_audit_issues'));
    }

    /** @test */
    public function cds_21_content_decay_signals_has_content_age_days_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'content_age_days'));
    }

    /** @test */
    public function cds_22_content_decay_signals_has_last_annotation_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'last_annotation_at'));
    }

    /** @test */
    public function cds_23_content_decay_signals_has_evidence_json_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'evidence_json'));
    }

    /** @test */
    public function cds_24_content_decay_signals_has_created_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_decay_signals', 'created_at'));
    }

    /** @test */
    public function cds_25_content_decay_signals_has_candidate_created_composite_index(): void
    {
        $indexes = $this->getTableIndexes('content_decay_signals');
        $this->assertTrue($this->hasCompositeIndex($indexes, ['candidate_id', 'created_at']));
    }

    /** @test */
    public function cds_26_content_decay_signals_has_unique_window_index(): void
    {
        $indexes = $this->getTableIndexes('content_decay_signals');
        $this->assertTrue($this->hasCompositeUniqueIndex($indexes, [
            'candidate_id',
            'previous_start',
            'previous_end',
            'current_start',
            'current_end'
        ]));
    }

    /** @test */
    public function cds_27_content_decay_signals_has_candidate_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('content_decay_signals');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'candidate_id', 'content_decay_candidates', 'id'));
    }

    // =========================================================================
    // content_refresh_tasks Table Tests (CRT)
    // =========================================================================

    /** @test */
    public function crt_01_content_refresh_tasks_table_exists_after_migration(): void
    {
        $this->assertTrue(Schema::hasTable('content_refresh_tasks'));
    }

    /** @test */
    public function crt_02_content_refresh_tasks_has_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'id'));
    }

    /** @test */
    public function crt_03_content_refresh_tasks_has_candidate_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'candidate_id'));
    }

    /** @test */
    public function crt_04_content_refresh_tasks_has_title_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'title'));
    }

    /** @test */
    public function crt_05_content_refresh_tasks_has_recommendation_type_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'recommendation_type'));
    }

    /** @test */
    public function crt_06_content_refresh_tasks_has_description_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'description'));
    }

    /** @test */
    public function crt_07_content_refresh_tasks_has_notes_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'notes'));
    }

    /** @test */
    public function crt_08_content_refresh_tasks_has_owner_user_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'owner_user_id'));
    }

    /** @test */
    public function crt_09_content_refresh_tasks_has_due_date_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'due_date'));
    }

    /** @test */
    public function crt_10_content_refresh_tasks_has_status_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'status'));
    }

    /** @test */
    public function crt_11_content_refresh_tasks_has_source_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'source'));
    }

    /** @test */
    public function crt_12_content_refresh_tasks_has_metadata_json_column(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'metadata_json'));
    }

    /** @test */
    public function crt_13_content_refresh_tasks_has_timestamps(): void
    {
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'created_at'));
        $this->assertTrue(Schema::hasColumn('content_refresh_tasks', 'updated_at'));
    }

    /** @test */
    public function crt_14_content_refresh_tasks_has_candidate_status_composite_index(): void
    {
        $indexes = $this->getTableIndexes('content_refresh_tasks');
        $this->assertTrue($this->hasCompositeIndex($indexes, ['candidate_id', 'status']));
    }

    /** @test */
    public function crt_15_content_refresh_tasks_has_owner_due_composite_index(): void
    {
        $indexes = $this->getTableIndexes('content_refresh_tasks');
        $this->assertTrue($this->hasCompositeIndex($indexes, ['owner_user_id', 'due_date']));
    }

    /** @test */
    public function crt_16_content_refresh_tasks_has_candidate_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('content_refresh_tasks');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'candidate_id', 'content_decay_candidates', 'id'));
    }

    /** @test */
    public function crt_17_content_refresh_tasks_has_owner_user_id_foreign_key(): void
    {
        $foreignKeys = $this->getTableForeignKeys('content_refresh_tasks');
        $this->assertTrue($this->hasForeignKey($foreignKeys, 'owner_user_id', 'users', 'id'));
    }

    // =========================================================================
    // Rollback Tests (RB)
    // =========================================================================

    /** @test */
    public function rb_01_all_tables_created_after_migration(): void
    {
        foreach (self::TABLES as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table {$table} should exist after migration"
            );
        }
    }

    /** @test */
    public function rb_02_tables_dropped_after_rollback(): void
    {
        // Migrate down in reverse order
        $this->artisan('migrate:rollback', [
            '--step' => 4, // Rollback all 4 migration files
        ]);

        foreach (self::TABLES as $table) {
            $this->assertFalse(
                Schema::hasTable($table),
                "Table {$table} should not exist after rollback"
            );
        }
    }

    /** @test */
    public function rb_03_fresh_migration_after_rollback(): void
    {
        // This test assumes rollback tests ran before
        // and verifies we can re-migrate
        $this->artisan('migrate');

        foreach (self::TABLES as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table {$table} should exist after fresh migration"
            );
        }
    }

    // =========================================================================
    // Migration Order Tests (MO)
    // =========================================================================

    /** @test */
    public function mo_01_projects_created_before_candidates(): void
    {
        // Verify foreign key constraint exists
        // This implicitly tests that projects table exists first
        $foreignKeys = $this->getTableForeignKeys('content_decay_candidates');
        $this->assertTrue(
            $this->hasForeignKey($foreignKeys, 'project_id', 'content_decay_projects', 'id'),
            'candidates should reference projects table'
        );
    }

    /** @test */
    public function mo_02_candidates_created_before_signals(): void
    {
        // Verify foreign key constraint exists
        $foreignKeys = $this->getTableForeignKeys('content_decay_signals');
        $this->assertTrue(
            $this->hasForeignKey($foreignKeys, 'candidate_id', 'content_decay_candidates', 'id'),
            'signals should reference candidates table'
        );
    }

    /** @test */
    public function mo_03_candidates_created_before_tasks(): void
    {
        // Verify foreign key constraint exists
        $foreignKeys = $this->getTableForeignKeys('content_refresh_tasks');
        $this->assertTrue(
            $this->hasForeignKey($foreignKeys, 'candidate_id', 'content_decay_candidates', 'id'),
            'tasks should reference candidates table'
        );
    }

    // =========================================================================
    // Integration Tests (INT)
    // =========================================================================

    /** @test */
    public function int_01_json_reason_codes_column_stores_and_retrieves_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement ContentDecayCandidate model first.');
    }

    /** @test */
    public function int_02_json_signal_sources_column_stores_and_retrieves_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement ContentDecayCandidate model first.');
    }

    /** @test */
    public function int_03_json_evidence_column_stores_and_retrieves_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement ContentDecayCandidate model first.');
    }

    /** @test */
    public function int_04_json_settings_column_stores_and_retrieves_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement ContentDecayProject model first.');
    }

    /** @test */
    public function int_05_decimal_decay_score_precision_stored_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement ContentDecayCandidate model first.');
    }

    /** @test */
    public function int_06_decimal_detection_confidence_precision_stored_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement ContentDecayCandidate model first.');
    }

    /** @test */
    public function int_07_decimal_ctr_precision_stored_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement ContentDecaySignal model first.');
    }

    /** @test */
    public function int_08_decimal_avg_position_precision_stored_correctly(): void
    {
        $this->markTestSkipped('Requires Developer to implement ContentDecaySignal model first.');
    }
}
