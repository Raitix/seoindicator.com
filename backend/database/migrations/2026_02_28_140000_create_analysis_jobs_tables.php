<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the analysis_jobs and analysis_job_results tables for the
     * progressive analysis system that processes indicators concurrently
     * with throttling and provides real-time UI updates.
     */
    public function up(): void
    {
        // Create analysis_jobs table
        Schema::create('analysis_jobs', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            
            // Foreign keys
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            $table->foreignId('site_id')
                ->constrained('sites')
                ->onDelete('cascade');
            
            // Status tracking
            $table->enum('status', ['pending', 'running', 'completed', 'cancelled', 'failed'])
                ->default('pending');
            
            // Progress tracking
            $table->unsignedInteger('total_indicators')->default(0);
            $table->unsignedInteger('completed_indicators')->default(0);
            $table->unsignedInteger('failed_indicators')->default(0);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            
            // Timestamps for job lifecycle
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Error handling
            $table->text('error_message')->nullable();
            
            // Laravel timestamps
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index('user_id', 'idx_jobs_user_id');
            $table->index('site_id', 'idx_jobs_site_id');
            $table->index('status', 'idx_jobs_status');
            $table->index('created_at', 'idx_jobs_created_at');
        });

        // Create analysis_job_results table
        Schema::create('analysis_job_results', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            
            // Foreign key to analysis_jobs
            $table->foreignId('job_id')
                ->constrained('analysis_jobs')
                ->onDelete('cascade');
            
            // Indicator identification
            $table->string('indicator_slug', 100);
            $table->string('indicator_name', 255);
            
            // Status tracking
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'skipped'])
                ->default('pending');
            
            // Score data
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('max_score', 5, 2)->nullable();
            $table->unsignedTinyInteger('percentage')->nullable();
            
            // Flexible data storage
            $table->json('data')->nullable();
            $table->json('recommendations')->nullable();
            
            // Error handling
            $table->text('error_message')->nullable();
            
            // Processing metadata
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();
            
            // Laravel timestamps
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index('job_id', 'idx_job_results_job_id');
            $table->index('status', 'idx_job_results_status');
            
            // Unique constraint: one result per indicator per job
            $table->unique(['job_id', 'indicator_slug'], 'idx_job_indicator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_job_results');
        Schema::dropIfExists('analysis_jobs');
    }
};
