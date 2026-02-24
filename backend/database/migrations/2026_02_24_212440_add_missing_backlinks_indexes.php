<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Corrective migration for TASK-13-2.
     * Adds missing indexes to the backlinks table that weren't created during initial migration.
     * This migration is safe to run multiple times - it checks for index existence before creating.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $isMysql = stripos($driver, 'mysql') !== false || stripos($driver, 'mariadb') !== false;

        if (!$isMysql) {
            // SQLite and other drivers don't need these fixes
            return;
        }

        if (!Schema::hasTable('backlinks')) {
            return;
        }

        // Add idx_source_target for backlink queries by source and target URLs
        $this->ensureIndex('idx_source_target', "CREATE INDEX idx_source_target ON backlinks (source_url(191), target_url(191))");

        // Add idx_site_active for filtering by site and active status
        $this->ensureIndex('idx_site_active', "CREATE INDEX idx_site_active ON backlinks (site_id, is_active)");

        // Add domain_authority index for sorting/filtering by authority
        $this->ensureIndex('backlinks_domain_authority_index', "CREATE INDEX backlinks_domain_authority_index ON backlinks (domain_authority)");

        // Add first_seen index for date-based queries
        $this->ensureIndex('backlinks_first_seen_index', "CREATE INDEX backlinks_first_seen_index ON backlinks (first_seen)");
    }

    /**
     * Check if an index exists and create it if not.
     */
    private function ensureIndex(string $indexName, string $createSql): void
    {
        $exists = DB::select("SHOW INDEX FROM backlinks WHERE Key_name = ?", [$indexName]);
        if (count($exists) === 0) {
            DB::statement($createSql);
            echo "Created index: {$indexName}\n";
        } else {
            echo "Index already exists: {$indexName}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $isMysql = stripos($driver, 'mysql') !== false || stripos($driver, 'mariadb') !== false;

        if (!$isMysql || !Schema::hasTable('backlinks')) {
            return;
        }

        $indexes = ['idx_source_target', 'idx_site_active', 'backlinks_domain_authority_index', 'backlinks_first_seen_index'];

        foreach ($indexes as $indexName) {
            $exists = DB::select("SHOW INDEX FROM backlinks WHERE Key_name = ?", [$indexName]);
            if (count($exists) > 0) {
                DB::statement("ALTER TABLE backlinks DROP INDEX {$indexName}");
            }
        }
    }
};
