<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds audit configuration options and cancelled status to audits table.
     * TASK-14-13: AuditController API endpoints.
     */
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            // Add audit configuration options
            $table->unsignedInteger('max_pages')->default(500)->after('site_id');
            $table->unsignedInteger('max_depth')->default(3)->after('max_pages');
            $table->string('user_agent')->nullable()->after('max_depth');
            $table->text('error_message')->nullable()->after('issues_found');
        });

        // Modify status enum to include 'cancelled'
        // SQLite doesn't support ALTER COLUMN, so we need to handle this differently
        // For MySQL/PostgreSQL, this would be:
        // ALTER TABLE audits MODIFY COLUMN status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending'
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn(['max_pages', 'max_depth', 'user_agent', 'error_message']);
        });
    }
};
