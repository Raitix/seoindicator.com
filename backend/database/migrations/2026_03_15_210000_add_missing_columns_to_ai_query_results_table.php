<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing columns to ai_query_results table.
 *
 * These columns are required by RunQueryJob and AIQueryResult model
 * but were missing from the original migration.
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
            $table->boolean('brand_mentioned')->default(false)->after('mentions');
            $table->json('competitor_mentions')->nullable()->after('brand_mentioned');
            $table->string('mention_type', 50)->nullable()->after('competitor_mentions');
            $table->string('query_category', 100)->nullable()->after('mention_type');
            $table->decimal('sentiment_score', 3, 2)->nullable()->after('sentiment');
            $table->text('context')->nullable()->after('sentiment_score');
            $table->string('period', 20)->nullable()->after('context');

            $table->index(['project_id', 'brand_mentioned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_query_results', function (Blueprint $table) {
            $table->dropColumn([
                'brand_mentioned',
                'competitor_mentions',
                'mention_type',
                'query_category',
                'sentiment_score',
                'context',
                'period',
            ]);
        });
    }
};
