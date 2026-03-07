<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for AI Visibility Tracking tables.
 *
 * Creates tables for:
 * - ai_visibility_projects: Brand tracking projects
 * - ai_query_results: Query results from AI platforms
 * - ai_mention_analytics: Aggregated analytics
 * - ai_visibility_alerts: Alert records
 *
 * @see SPEC-034
 * @see TASK-34-1
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // AI Visibility Projects table
        Schema::create('ai_visibility_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('brand_name');
            $table->json('keywords')->nullable();
            $table->json('competitors')->nullable();
            $table->json('query_templates')->nullable();
            $table->enum('tracking_frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_queried_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index('last_queried_at');
        });

        // AI Query Results table
        Schema::create('ai_query_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('ai_visibility_projects')->cascadeOnDelete();
            $table->string('platform', 50);
            $table->text('query_text');
            $table->text('response_text')->nullable();
            $table->string('model_used', 100)->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->json('mentions')->nullable();
            $table->string('sentiment', 20)->nullable();
            $table->timestamp('queried_at');
            $table->timestamps();

            $table->index(['project_id', 'platform']);
            $table->index(['project_id', 'success']);
            $table->index('queried_at');
        });

        // AI Mention Analytics table
        Schema::create('ai_mention_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('ai_visibility_projects')->cascadeOnDelete();
            $table->string('platform', 50);
            $table->unsignedInteger('total_queries')->default(0);
            $table->unsignedInteger('brand_mentions')->default(0);
            $table->decimal('mention_rate', 5, 2)->default(0);
            $table->unsignedInteger('positive_mentions')->default(0);
            $table->unsignedInteger('neutral_mentions')->default(0);
            $table->unsignedInteger('negative_mentions')->default(0);
            $table->json('competitor_stats')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'platform']);
            $table->index(['project_id', 'period_start', 'period_end']);
        });

        // AI Visibility Alerts table
        Schema::create('ai_visibility_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('ai_visibility_projects')->cascadeOnDelete();
            $table->string('alert_type', 50);
            $table->decimal('threshold', 5, 2);
            $table->decimal('previous_value', 10, 2);
            $table->decimal('current_value', 10, 2);
            $table->decimal('change_percent', 6, 2);
            $table->timestamp('triggered_at');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'alert_type']);
            $table->index(['project_id', 'triggered_at']);
            $table->index(['is_read', 'is_resolved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_visibility_alerts');
        Schema::dropIfExists('ai_mention_analytics');
        Schema::dropIfExists('ai_query_results');
        Schema::dropIfExists('ai_visibility_projects');
    }
};
