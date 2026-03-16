<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add mention_type and context columns to ai_query_results table.
 *
 * These columns are required by RunQueryJob but were missing from
 * the original analytics columns migration.
 *
 * @see TASK-34-18
 * @see SPEC-034
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_query_results', function (Blueprint $table) {
            $table->string('mention_type', 50)->nullable()->after('competitor_mentions');
            $table->text('context')->nullable()->after('sentiment_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_query_results', function (Blueprint $table) {
            $table->dropColumn([
                'mention_type',
                'context',
            ]);
        });
    }
};
