<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the crawl_jobs and crawl_pages tables for the headless
     * crawler service that renders JavaScript-heavy pages and extracts
     * SEO-relevant content.
     */
    public function up(): void
    {
        // Create crawl_jobs table
        Schema::create('crawl_jobs', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            
            // Foreign keys
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects')
                ->onDelete('set null');
            
            // Unique job identifier for API access (format: crawl-{uuid})
            $table->string('job_id', 64)->unique();
            
            // Target URL (max URL length per RFC 3986)
            $table->string('url', 2083);
            
            // Status tracking
            $table->enum('status', ['queued', 'running', 'completed', 'failed', 'cancelled'])
                ->default('queued');
            
            // Crawl configuration
            $table->unsignedTinyInteger('depth')->default(0);
            $table->json('options')->nullable();
            
            // Progress tracking
            $table->unsignedInteger('pages_crawled')->default(0);
            $table->unsignedBigInteger('total_size')->default(0);
            
            // Error handling
            $table->text('error')->nullable();
            
            // Timestamps for job lifecycle
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at'); // For content cleanup
            
            // Laravel timestamps
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index('user_id', 'idx_crawl_jobs_user_id');
            $table->index('status', 'idx_crawl_jobs_status');
            $table->index('job_id', 'idx_crawl_jobs_job_id');
            $table->index('expires_at', 'idx_crawl_jobs_expires_at');
        });

        // Create crawl_pages table
        Schema::create('crawl_pages', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            
            // Foreign key to crawl_jobs
            $table->foreignId('crawl_job_id')
                ->constrained('crawl_jobs')
                ->onDelete('cascade');
            
            // Page URL (max URL length per RFC 3986)
            $table->string('url', 2083);
            
            // Depth in crawl tree
            $table->unsignedTinyInteger('depth');
            
            // Page content
            $table->string('title', 500)->nullable();
            $table->mediumText('rendered_html')->nullable(); // Up to 16MB
            $table->mediumText('structured_text')->nullable(); // Up to 16MB
            
            // Metadata
            $table->json('meta')->nullable();
            $table->json('links')->nullable();
            
            // Size tracking
            $table->unsignedInteger('size')->default(0);
            
            // Timestamps
            $table->timestamp('crawled_at')->nullable();
            
            // Error handling
            $table->text('error')->nullable();
            
            // Laravel timestamps
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index('crawl_job_id', 'idx_crawl_pages_job_id');
            $table->index(['crawl_job_id', 'depth'], 'idx_crawl_pages_job_depth');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_pages');
        Schema::dropIfExists('crawl_jobs');
    }
};
