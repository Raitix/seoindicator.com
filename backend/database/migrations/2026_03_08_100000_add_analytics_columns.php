<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add additional columns for AnalyticsAggregator tests.
 *
 * @see TASK-34-13
 * @see SPEC-034
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add columns to ai_query_results
        Schema::table('ai_query_results', function (Blueprint $table) {
            $table->boolean('brand_mentioned')->default(false)->after('mentions');
            $table->json('competitor_mentions')->nullable()->after('brand_mentioned');
            $table->string('query_category', 100)->nullable()->after('competitor_mentions');
            $table->decimal('sentiment_score', 5, 2)->nullable()->after('sentiment');

            $table->index('brand_mentioned');
            $table->index('query_category');
        });

        // Add columns to ai_mention_analytics
        Schema::table('ai_mention_analytics', function (Blueprint $table) {
            $table->string('period', 20)->nullable()->after('platform');
            $table->unsignedInteger('mixed_mentions')->default(0)->after('negative_mentions');
            $table->decimal('average_sentiment', 5, 2)->default(0)->after('mixed_mentions');
            $table->json('competitor_mentions')->nullable()->after('average_sentiment');
            $table->timestamp('aggregated_at')->nullable()->after('period_end');

            $table->index(['project_id', 'period']);
            $table->unique(['project_id', 'platform', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_query_results', function (Blueprint $table) {
            $table->dropIndex(['brand_mentioned']);
            $table->dropIndex(['query_category']);
            $table->dropColumn([
                'brand_mentioned',
                'competitor_mentions',
                'query_category',
                'sentiment_score',
            ]);
        });

        Schema::table('ai_mention_analytics', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'platform', 'period']);
            $table->dropIndex(['project_id', 'period']);
            $table->dropColumn([
                'period',
                'mixed_mentions',
                'average_sentiment',
                'competitor_mentions',
                'aggregated_at',
            ]);
        });
    }
};
