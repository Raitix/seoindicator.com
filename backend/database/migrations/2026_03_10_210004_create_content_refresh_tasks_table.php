<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates content_refresh_tasks table for Content Decay Detection & Refresh Planner.
     * Stores individual refresh tasks for decay candidates.
     *
     * @see SPEC-036: Content Decay Detection & Refresh Planner
     * @see TASK-36-1: Database Migrations - Content Decay Tables
     */
    public function up(): void
    {
        Schema::create('content_refresh_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('content_decay_candidates')->onDelete('cascade');
            
            // Task details
            $table->string('title', 255);
            $table->string('recommendation_type', 50); // 'update_content', 'add_keywords', 'fix_technical', 'update_meta', 'add_internal_links', 'consolidate', 'redirect', 'other'
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Assignment
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('due_date')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->string('source', 50)->default('system'); // 'system', 'manual', 'ai_suggestion'
            
            // Flexible metadata
            $table->json('metadata_json')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['candidate_id', 'status'], 'idx_candidate_status');
            $table->index(['owner_user_id', 'due_date'], 'idx_owner_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_refresh_tasks');
    }
};
