<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes user_id nullable to support guest/unauthenticated API access.
     * Required for public tool endpoints like mobile-friendly checker.
     * 
     * Uses raw SQL instead of Schema::change() to avoid doctrine/dbal dependency.
     * Handles both MySQL (MODIFY COLUMN) and SQLite (recreate table) syntax.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN - need to recreate table
            // For tests using :memory:, this is fine
            DB::statement('
                CREATE TABLE analysis_history_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id BIGINT UNSIGNED NULL,
                    site_id BIGINT UNSIGNED NULL,
                    tool_name VARCHAR(100) NOT NULL,
                    input_url VARCHAR(2083) NOT NULL,
                    status VARCHAR(20) DEFAULT \'completed\',
                    results JSON NULL,
                    error_message TEXT NULL,
                    analysis_duration_ms INTEGER NULL,
                    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
            
            // Copy data
            DB::statement('
                INSERT INTO analysis_history_new 
                SELECT id, user_id, site_id, tool_name, input_url, status, results, 
                       error_message, analysis_duration_ms, analyzed_at, created_at, updated_at
                FROM analysis_history
            ');
            
            // Drop old table
            DB::statement('DROP TABLE analysis_history');
            
            // Rename new table
            DB::statement('ALTER TABLE analysis_history_new RENAME TO analysis_history');
            
            // Recreate indexes
            DB::statement('CREATE INDEX idx_user_analyzed ON analysis_history(user_id, analyzed_at)');
            DB::statement('CREATE INDEX idx_tool_name ON analysis_history(tool_name)');
            DB::statement('CREATE INDEX idx_status ON analysis_history(status)');
        } else {
            // MySQL syntax
            DB::statement('ALTER TABLE analysis_history MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // For SQLite, recreate table with NOT NULL constraint
            DB::statement('
                CREATE TABLE analysis_history_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                    site_id BIGINT UNSIGNED NULL,
                    tool_name VARCHAR(100) NOT NULL,
                    input_url VARCHAR(2083) NOT NULL,
                    status VARCHAR(20) DEFAULT \'completed\',
                    results JSON NULL,
                    error_message TEXT NULL,
                    analysis_duration_ms INTEGER NULL,
                    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
            
            // Set NULL values to 0 before copy
            DB::statement('UPDATE analysis_history SET user_id = 0 WHERE user_id IS NULL');
            
            // Copy data
            DB::statement('
                INSERT INTO analysis_history_new 
                SELECT id, user_id, site_id, tool_name, input_url, status, results, 
                       error_message, analysis_duration_ms, analyzed_at, created_at, updated_at
                FROM analysis_history
            ');
            
            // Drop old table
            DB::statement('DROP TABLE analysis_history');
            
            // Rename new table
            DB::statement('ALTER TABLE analysis_history_new RENAME TO analysis_history');
            
            // Recreate indexes
            DB::statement('CREATE INDEX idx_user_analyzed ON analysis_history(user_id, analyzed_at)');
            DB::statement('CREATE INDEX idx_tool_name ON analysis_history(tool_name)');
            DB::statement('CREATE INDEX idx_status ON analysis_history(status)');
        } else {
            // MySQL syntax
            DB::statement('UPDATE analysis_history SET user_id = 0 WHERE user_id IS NULL');
            DB::statement('ALTER TABLE analysis_history MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
