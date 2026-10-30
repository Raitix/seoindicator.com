<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates keyword_suggestions and dismissed_suggestions tables for FEAT-005.
     * These tables support the keyword discovery feature.
     *
     * @see TASK-5-1
     * @see SPEC-005
     */
    public function up(): void
    {
        // Create keyword_suggestions table
        Schema::create('keyword_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('keyword'); // VARCHAR(255) to support unique constraint
            $table->string('source'); // 'suggest', 'competitor', 'related'
            $table->integer('search_volume')->nullable();
            $table->integer('difficulty_score')->nullable();
            $table->integer('opportunity_score')->nullable();
            $table->boolean('is_tracking')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('discovered_at')->useCurrent();
            $table->timestamps();

            // Unique constraint: same keyword can only appear once per site
            $table->unique(['site_id', 'keyword']);

            // Indexes for common queries
            $table->index('site_id', 'idx_suggestions_site_id');
            $table->index('keyword', 'idx_suggestions_keyword');
            $table->index('opportunity_score', 'idx_suggestions_opportunity');
        });

        // Create dismissed_suggestions table
        Schema::create('dismissed_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('keyword'); // VARCHAR(255) for consistency
            $table->timestamp('dismissed_at')->useCurrent();
            $table->timestamps();

            // Index for site queries
            $table->index('site_id', 'idx_dismissed_site');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dismissed_suggestions');
        Schema::dropIfExists('keyword_suggestions');
    }
};
