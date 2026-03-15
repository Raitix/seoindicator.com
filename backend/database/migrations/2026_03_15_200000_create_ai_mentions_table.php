<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for AI Mentions table.
 *
 * Stores individual mention records for brand and competitor tracking.
 * This is a per-mention tracking table (distinct from ai_mention_analytics aggregation table).
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
        Schema::create('ai_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('ai_visibility_projects')->cascadeOnDelete();
            $table->foreignId('query_result_id')->nullable()->constrained('ai_query_results')->nullOnDelete();
            
            // Entity being mentioned
            $table->enum('entity_type', ['brand', 'competitor'])->index();
            $table->string('entity_name')->index();
            
            // Mention details
            $table->enum('mention_type', ['recommendation', 'comparison', 'mention', 'citation', 'neutral'])->default('neutral');
            $table->enum('sentiment', ['positive', 'negative', 'neutral', 'mixed'])->default('neutral');
            $table->decimal('sentiment_score', 3, 2)->nullable()->comment('0.00 to 1.00');
            
            // Context and content
            $table->text('context')->nullable()->comment('Text snippet around mention (max 200 chars)');
            $table->string('platform', 50)->index();
            
            // Timestamps
            $table->timestamp('mentioned_at')->nullable();
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['project_id', 'entity_type', 'mentioned_at']);
            $table->index(['project_id', 'platform', 'mentioned_at']);
            $table->index(['project_id', 'sentiment', 'mentioned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_mentions');
    }
};
