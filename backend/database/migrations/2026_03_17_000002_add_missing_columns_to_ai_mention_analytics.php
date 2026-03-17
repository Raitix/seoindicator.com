<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add missing columns to ai_mention_analytics table.
 * 
 * These columns were specified in TASK-34-2 but were missing from the original migration.
 * 
 * @see TASK-34-2
 * @see SPEC-034
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_mention_analytics', function (Blueprint $table) {
            // Share of voice tracking
            $table->decimal('share_of_voice', 5, 2)->default(0.00)->after('competitor_stats');
            
            // Trend and category analysis
            $table->json('top_query_categories')->nullable()->after('share_of_voice');
            $table->string('mention_trend', 20)->default('stable')->after('top_query_categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_mention_analytics', function (Blueprint $table) {
            $table->dropColumn([
                'share_of_voice',
                'top_query_categories',
                'mention_trend',
            ]);
        });
    }
};
