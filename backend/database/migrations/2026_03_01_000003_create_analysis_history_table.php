<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the analysis_history table for tracking all tool analyses
     * performed by users.
     */
    public function up(): void
    {
        Schema::create('analysis_history', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('site_id')->nullable();

            // Analysis details
            $table->string('tool_name', 100);
            $table->string('input_url', 2083);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->json('results')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('analysis_duration_ms')->nullable();
            $table->timestamp('analyzed_at')->useCurrent();

            // Timestamps
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->onDelete('set null');

            // Indexes for performance
            // Composite index for user's analysis history queries (most common query pattern)
            $table->index(['user_id', 'analyzed_at'], 'idx_user_analyzed');

            // Index for filtering by tool
            $table->index('tool_name', 'idx_tool_name');

            // Index for filtering by status
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_history');
    }
};
