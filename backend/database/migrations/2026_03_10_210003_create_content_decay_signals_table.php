<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates content_decay_signals table for Content Decay Detection & Refresh Planner.
     * Stores comparison windows (previous vs current) for each detected decay candidate.
     *
     * @see SPEC-036: Content Decay Detection & Refresh Planner
     * @see TASK-36-1: Database Migrations - Content Decay Tables
     */
    public function up(): void
    {
        Schema::create('content_decay_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('content_decay_candidates')->onDelete('cascade');
            
            // Comparison windows
            $table->date('previous_start');
            $table->date('previous_end');
            $table->date('current_start');
            $table->date('current_end');
            
            // GSC metrics - previous period
            $table->unsignedBigInteger('previous_clicks')->default(0);
            $table->unsignedBigInteger('previous_impressions')->default(0);
            $table->decimal('previous_ctr', 8, 4)->default(0);
            $table->decimal('previous_avg_position', 8, 2)->nullable();
            $table->decimal('previous_rank_visibility', 5, 2)->nullable(); // % of queries in top 10
            $table->decimal('previous_ai_visibility', 5, 2)->nullable(); // AI citation rate
            
            // GSC metrics - current period
            $table->unsignedBigInteger('current_clicks')->default(0);
            $table->unsignedBigInteger('current_impressions')->default(0);
            $table->decimal('current_ctr', 8, 4)->default(0);
            $table->decimal('current_avg_position', 8, 2)->nullable();
            $table->decimal('current_rank_visibility', 5, 2)->nullable();
            $table->decimal('current_ai_visibility', 5, 2)->nullable();
            
            // Additional signals
            $table->unsignedInteger('unresolved_audit_issues')->default(0);
            $table->unsignedInteger('content_age_days')->nullable();
            $table->timestamp('last_annotation_at')->nullable();
            
            // Evidence storage
            $table->json('evidence_json')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index(['candidate_id', 'created_at'], 'idx_candidate_created');
            $table->unique(
                ['candidate_id', 'previous_start', 'previous_end', 'current_start', 'current_end'],
                'idx_unique_window'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_decay_signals');
    }
};
