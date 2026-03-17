<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add missing columns to ai_query_results table.
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
        Schema::table('ai_query_results', function (Blueprint $table) {
            // Brand tracking fields
            $table->boolean('brand_mentioned')->default(false)->after('mentions');
            $table->text('brand_context')->nullable()->after('brand_mentioned');
            $table->json('competitor_mentions')->nullable()->after('brand_context');
            
            // Response metadata
            $table->json('citations')->nullable()->after('competitor_mentions');
            $table->unsignedInteger('query_tokens')->nullable()->after('tokens_used');
            $table->unsignedInteger('response_tokens')->nullable()->after('query_tokens');
            
            // Performance tracking
            $table->boolean('cache_hit')->default(false)->after('queried_at');
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
                'brand_context',
                'competitor_mentions',
                'citations',
                'query_tokens',
                'response_tokens',
                'cache_hit',
            ]);
        });
    }
};
