<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates backlinks table for FEAT-013 Backlink Monitoring.
     * Stores detailed backlink data per site.
     *
     * Note: Uses sites table instead of projects for consistency with existing schema.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $isSqlite = stripos($driver, 'sqlite') !== false;
        $isMysql = stripos($driver, 'mysql') !== false || stripos($driver, 'mariadb') !== false;

        if (Schema::hasTable('backlinks')) {
            if ($isMysql) {
                $this->ensureIndex('idx_source_target', "CREATE INDEX idx_source_target ON backlinks (source_url(191), target_url(191))");
                $this->ensureIndex('idx_site_active', "CREATE INDEX idx_site_active ON backlinks (site_id, is_active)");
                $this->ensureIndex('backlinks_domain_authority_index', "CREATE INDEX backlinks_domain_authority_index ON backlinks (domain_authority)");
                $this->ensureIndex('backlinks_first_seen_index', "CREATE INDEX backlinks_first_seen_index ON backlinks (first_seen)");
            }
            return;
        }

        Schema::create('backlinks', function (Blueprint $table) use ($driver, $isMysql) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('source_url', 2083);
            $table->string('target_url', 2083);
            $table->string('anchor_text', 255);
            $table->enum('link_type', ['dofollow', 'nofollow'])->default('dofollow');
            $table->float('domain_authority', 5, 2)->unsigned()->default(0.00);
            $table->float('page_authority', 5, 2)->unsigned()->default(0.00);
            $table->date('first_seen');
            $table->date('last_checked');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_toxic')->default(false);
            $table->timestamps();

            // Indexes for common queries
            if ($isMysql) {
                $table->index([
                    DB::raw('source_url(191)'),
                    DB::raw('target_url(191)')
                ], 'idx_source_target');
            } else {
                $table->index(['source_url', 'target_url'], 'idx_source_target');
            }
            $table->index(['site_id', 'is_active'], 'idx_site_active');
            $table->index('domain_authority');
            $table->index('first_seen');
        });
    }

    private function ensureIndex(string $indexName, string $createSql): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (stripos($driver, 'sqlite') !== false) {
            return;
        }

        $exists = DB::select("SHOW INDEX FROM backlinks WHERE Key_name = ?", [$indexName]);
        if (count($exists) === 0) {
            DB::statement($createSql);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlinks');
    }
};
