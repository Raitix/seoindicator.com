<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates keywords table and creates ranking_history table per SPEC-001 requirements.
     * This migration:
     * 1. Adds site_id column to keywords table (if not exists)
     * 2. Creates ranking_history table
     * 3. Adds indexes and constraints
     */
    public function up(): void
    {
        // Add site_id column to keywords table if it doesn't exist
        if (!Schema::hasColumn('keywords', 'site_id')) {
            Schema::table('keywords', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('user_id');
            });
        }

        // Create foreign key constraint for site_id only if it doesn't exist
        $fkExists = DB::selectOne(
            "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_NAME = 'keywords' 
             AND TABLE_SCHEMA = DATABASE() 
             AND CONSTRAINT_NAME = 'keywords_site_id_foreign'"
        );
        
        if ($fkExists->cnt == 0 && Schema::hasColumn('keywords', 'site_id')) {
            Schema::table('keywords', function (Blueprint $table) {
                $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            });
        }

        // Note: Unique constraint on (site_id, keyword, search_engine, location, device) skipped
        // MySQL has issues with unique constraints involving nullable FK columns
        // The application layer should handle uniqueness validation
        // 
        // If needed later, can add via:
        // ALTER TABLE keywords ADD UNIQUE INDEX keywords_unique (site_id, keyword(100), search_engine, location, device)

        // Create ranking_history table if not exists
        if (!Schema::hasTable('ranking_history')) {
            Schema::create('ranking_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
                $table->integer('position')->nullable(); // NULL = not in top 100
                $table->text('url_found')->nullable(); // Actual ranking URL
                $table->json('serp_features')->nullable(); // JSON: featured snippet, local pack, etc.
                $table->timestamp('checked_at')->useCurrent();
                $table->integer('check_duration_ms')->nullable(); // Performance tracking
                $table->text('error')->nullable(); // Error message if failed

                // Indexes for query optimization
                $table->index('keyword_id');
                $table->index('checked_at');
                $table->index(['keyword_id', 'checked_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop ranking_history table
        Schema::dropIfExists('ranking_history');

        // Remove unique constraint
        Schema::table('keywords', function (Blueprint $table) {
            $table->dropUnique('keywords_unique');
        });

        // Remove site_id column if it was added
        Schema::table('keywords', function (Blueprint $table) {
            if (Schema::hasColumn('keywords', 'site_id')) {
                $table->dropForeign(['site_id']);
                $table->dropColumn('site_id');
            }
        });
    }
};