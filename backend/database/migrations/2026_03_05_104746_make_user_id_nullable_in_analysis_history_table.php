<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes user_id nullable to support guest/unauthenticated API access.
     * Required for public tool endpoints like mobile-friendly checker.
     * 
     * Uses raw SQL instead of Schema::change() to avoid doctrine/dbal dependency.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE analysis_history MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First set any NULL values to 0 before making NOT NULL
        DB::statement('UPDATE analysis_history SET user_id = 0 WHERE user_id IS NULL');
        DB::statement('ALTER TABLE analysis_history MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL');
    }
};
