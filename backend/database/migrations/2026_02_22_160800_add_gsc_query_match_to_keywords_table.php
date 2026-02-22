<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('keywords', 'gsc_query_match')) {
            Schema::table('keywords', function (Blueprint $table) {
                $table->string('gsc_query_match', 255)
                    ->nullable()
                    ->comment('Fuzzy match to GSC queries')
                    ->after('keyword');
            });
        }

        // Add index for efficient matching queries
        try {
            Schema::table('keywords', function (Blueprint $table) {
                $table->index('gsc_query_match', 'keywords_gsc_query_match_index');
            });
        } catch (\Throwable $e) {
            // Ignore if index already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('keywords', function (Blueprint $table) {
                $table->dropIndex('keywords_gsc_query_match_index');
            });
        } catch (\Throwable $e) {
            // Ignore if index doesn't exist
        }

        if (Schema::hasColumn('keywords', 'gsc_query_match')) {
            Schema::table('keywords', function (Blueprint $table) {
                $table->dropColumn('gsc_query_match');
            });
        }
    }
};
