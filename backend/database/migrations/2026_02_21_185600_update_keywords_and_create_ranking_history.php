<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates keywords table and creates ranking_history table per SPEC-001 requirements.
     * This migration:
     * 1. Adds site_id column + FK to keywords table (if not exists)
     * 2. Adds unique constraint for keyword uniqueness per site
     * 3. Creates ranking_history table
     */
    public function up(): void
    {
        // Add site_id column + FK to keywords table if it doesn't exist
        if (!Schema::hasColumn('keywords', 'site_id')) {
            Schema::table('keywords', function (Blueprint $table) {
                $table->foreignId('site_id')
                    ->nullable()
                    ->constrained('sites')
                    ->onDelete('cascade')
                    ->after('user_id');
            });
        } else {
            // Ensure FK exists if column already present
            try {
                Schema::table('keywords', function (Blueprint $table) {
                    $table->foreign('site_id')
                        ->references('id')
                        ->on('sites')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Ignore if FK already exists or unsupported by driver
            }
        }

        // Add unique constraint (site_id, keyword, search_engine, location, device)
        try {
            Schema::table('keywords', function (Blueprint $table) {
                $table->unique(
                    ['site_id', 'keyword', 'search_engine', 'location', 'device'],
                    'keywords_unique'
                );
            });
        } catch (\Throwable $e) {
            // Ignore if index already exists
        }

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
        Schema::dropIfExists('ranking_history');

        // Drop unique constraint if exists
        try {
            Schema::table('keywords', function (Blueprint $table) {
                $table->dropUnique('keywords_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if index doesn't exist
        }

        // Remove site_id column if it was added
        if (Schema::hasColumn('keywords', 'site_id')) {
            try {
                Schema::table('keywords', function (Blueprint $table) {
                    $table->dropForeign(['site_id']);
                });
            } catch (\Throwable $e) {
                // Ignore if FK doesn't exist
            }

            Schema::table('keywords', function (Blueprint $table) {
                $table->dropColumn('site_id');
            });
        }
    }
};
