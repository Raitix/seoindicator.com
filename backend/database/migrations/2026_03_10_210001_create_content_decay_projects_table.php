<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates content_decay_projects table for Content Decay Detection & Refresh Planner.
     * Stores project-level settings for monitoring content decay on sites.
     *
     * @see SPEC-036: Content Decay Detection & Refresh Planner
     * @see TASK-36-1: Database Migrations - Content Decay Tables
     */
    public function up(): void
    {
        Schema::create('content_decay_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            
            // Detection settings
            $table->boolean('detection_enabled')->default(true);
            $table->unsignedTinyInteger('window_days')->default(28);
            $table->unsignedInteger('min_clicks_threshold')->default(10);
            $table->unsignedInteger('min_impressions_threshold')->default(100);
            $table->decimal('min_decay_score', 5, 2)->default(20.00);
            
            // Digest settings
            $table->boolean('digest_enabled')->default(true);
            $table->string('digest_frequency', 20)->default('weekly'); // 'weekly', 'monthly'
            
            // Flexible settings storage
            $table->json('settings_json')->nullable();
            
            // Tracking
            $table->timestamp('last_scanned_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique('site_id'); // One project per site
            $table->index('user_id');
            $table->index('detection_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_decay_projects');
    }
};
