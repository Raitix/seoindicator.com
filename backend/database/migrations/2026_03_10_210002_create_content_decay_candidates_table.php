<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates content_decay_candidates table for Content Decay Detection & Refresh Planner.
     * Stores individual pages detected as having content decay.
     *
     * @see SPEC-036: Content Decay Detection & Refresh Planner
     * @see TASK-36-1: Database Migrations - Content Decay Tables
     */
    public function up(): void
    {
        Schema::create('content_decay_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('content_decay_projects')->onDelete('cascade');
            
            // Page identification
            $table->string('page_url', 2048);
            $table->char('page_url_hash', 64); // SHA-256 hash for indexing
            $table->string('page_title', 500)->nullable();
            $table->string('page_type', 50)->default('other'); // 'blog', 'product', 'landing', 'category', 'other'
            
            // Decay metrics
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->decimal('decay_score', 5, 2); // 0-100
            $table->decimal('detection_confidence', 5, 2)->default(85.00); // 0-100
            
            // Estimates
            $table->unsignedBigInteger('lost_click_estimate')->nullable();
            $table->unsignedBigInteger('recovery_upside_estimate')->nullable();
            
            // Reason tracking
            $table->string('primary_reason_code', 50);
            $table->json('reason_codes'); // Array of reason codes
            $table->json('signal_sources')->nullable(); // Which signals triggered this
            $table->json('evidence_json')->nullable(); // Supporting evidence
            
            // Status workflow
            $table->enum('status', ['backlog', 'planned', 'in_progress', 'refreshed', 'recovered', 'ignored'])->default('backlog');
            
            // Timestamps for status transitions
            $table->timestamp('first_detected_at');
            $table->timestamp('last_detected_at');
            $table->timestamp('planned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamp('ignored_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['project_id', 'page_url_hash'], 'idx_project_page_hash');
            $table->index(['project_id', 'status'], 'idx_project_status');
            $table->index(['project_id', 'decay_score'], 'idx_project_score');
            $table->index(['project_id', 'severity'], 'idx_project_severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_decay_candidates');
    }
};
