<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates admin_activity_logs and system_metrics_cache tables
     * for the admin dashboard's activity auditing and metrics caching.
     * 
     * Task: TASK-27-1
     * Feature: FEAT-027 (Admin Dashboard - Ops + Usage)
     */
    public function up(): void
    {
        // Admin Activity Logs table - audit trail for admin actions
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('action', 100);
            $table->string('target_type', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable(); // IPv6 support (max 45 chars)
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            // Foreign key to users with SET NULL on delete
            $table->foreign('admin_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for query optimization
            $table->index('admin_user_id');
            $table->index('action');
            $table->index('created_at');
            $table->index(['admin_user_id', 'created_at']); // Composite for user activity queries
        });

        // System Metrics Cache table - cached metrics for dashboard
        Schema::create('system_metrics_cache', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key', 100)->unique();
            $table->decimal('metric_value', 15, 4);
            $table->json('metadata')->nullable();
            $table->timestamp('cached_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            // Index for expired metrics cleanup queries
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_metrics_cache');
        Schema::dropIfExists('admin_activity_logs');
    }
};
