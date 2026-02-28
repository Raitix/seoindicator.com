<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for seo_analyses table
 * 
 * Stores SEO analysis results with user/session tracking, domain info,
 * scores, and cached analysis data.
 * 
 * @see TASK-18-1
 * @see SPEC-018
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_analyses', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // User tracking (nullable for anonymous analyses)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');
            
            // Session tracking for anonymous users
            $table->string('session_id', 64)->nullable();
            
            // Domain and URL information
            $table->string('domain', 500);
            $table->string('normalized_url', 500);
            
            // Analysis results
            $table->unsignedTinyInteger('overall_score');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])
                ->default('pending');
            $table->text('error_message')->nullable();
            $table->json('analysis_data');
            
            // Timestamps for caching
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Standard timestamps
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['user_id', 'domain'], 'seo_analyses_user_id_domain_index');
            $table->index(['session_id', 'domain'], 'seo_analyses_session_id_domain_index');
            $table->index('normalized_url', 'seo_analyses_normalized_url_index');
            $table->index('status', 'seo_analyses_status_index');
            $table->index('expires_at', 'seo_analyses_expires_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_analyses');
    }
};
