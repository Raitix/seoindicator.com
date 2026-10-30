<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops old backlinks tables from FEAT-013 that used site_id.
     * These tables are being replaced with project_id-based tables for FEAT-023.
     *
     * Note: Only drops if tables exist with old schema.
     */
    public function up(): void
    {
        // Drop old backlink tables from FEAT-013 (site_id-based)
        Schema::dropIfExists('backlink_changes');
        Schema::dropIfExists('backlink_summaries');
        Schema::dropIfExists('backlinks');
    }

    /**
     * Reverse the migrations.
     *
     * Note: Cannot recreate old tables as they had different schema.
     * This is a one-way migration for FEAT-023.
     */
    public function down(): void
    {
        // Old tables would need to be recreated manually from FEAT-013 migrations
        // This is intentional - FEAT-023 replaces the old backlink system
    }
};
