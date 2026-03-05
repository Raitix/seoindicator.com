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
                    url VARCHAR(2048) NOT NULL,
                    indicator VARCHAR(50) NOT NULL,
                    score INTEGER DEFAULT 0,
                    status VARCHAR(20) DEFAULT \'pending\',
                    error_message TEXT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    input_data JSON NULL,
                    output_data JSON NULL,
                    model VARCHAR(100) NULL,
                    provider VARCHAR(50) NULL,
                    tokens_used INTEGER DEFAULT 0,
                    cost_usd DECIMAL(10, 6) DEFAULT 0,
                    analysis_duration_ms INTEGER DEFAULT 0
                )
            ');
            
            // Copy data
            DB::statement('
                INSERT INTO analysis_history_new 
                SELECT id, user_id, url, indicator, score, status, error_message, 
                       created_at, updated_at, completed_at, input_data, output_data, 
                       model, provider, tokens_used, cost_usd, analysis_duration_ms
                FROM analysis_history
            ');
            
            // Drop old table
            DB::statement('DROP TABLE analysis_history');
            
            // Rename new table
            DB::statement('ALTER TABLE analysis_history_new RENAME TO analysis_history');
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
                    url VARCHAR(2048) NOT NULL,
                    indicator VARCHAR(50) NOT NULL,
                    score INTEGER DEFAULT 0,
                    status VARCHAR(20) DEFAULT \'pending\',
                    error_message TEXT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    input_data JSON NULL,
                    output_data JSON NULL,
                    model VARCHAR(100) NULL,
                    provider VARCHAR(50) NULL,
                    tokens_used INTEGER DEFAULT 0,
                    cost_usd DECIMAL(10, 6) DEFAULT 0,
                    analysis_duration_ms INTEGER DEFAULT 0
                )
            ');
            
            // Set NULL values to 0 before copy
            DB::statement('UPDATE analysis_history SET user_id = 0 WHERE user_id IS NULL');
            
            // Copy data
            DB::statement('
                INSERT INTO analysis_history_new 
                SELECT id, user_id, url, indicator, score, status, error_message, 
                       created_at, updated_at, completed_at, input_data, output_data, 
                       model, provider, tokens_used, cost_usd, analysis_duration_ms
                FROM analysis_history
            ');
            
            // Drop old table
            DB::statement('DROP TABLE analysis_history');
            
            // Rename new table
            DB::statement('ALTER TABLE analysis_history_new RENAME TO analysis_history');
        } else {
            // MySQL syntax
            DB::statement('UPDATE analysis_history SET user_id = 0 WHERE user_id IS NULL');
            DB::statement('ALTER TABLE analysis_history MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
