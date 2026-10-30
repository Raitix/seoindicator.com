<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for TASK-8-2: Modify Existing Tables for Multi-Site Support
 *
 * Adds project_id column to keywords, competitors, reports, and alert_settings tables.
 * This enables data scoping by project for multi-site functionality.
 *
 * @see TASK-8-2
 * @see SPEC-008
 * @see FEAT-008
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add project_id to keywords table
        Schema::table('keywords', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('user_id')
                ->constrained('projects')
                ->onDelete('cascade');
            
            $table->index('project_id');
        });

        // Add project_id to competitors table
        Schema::table('competitors', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('user_id')
                ->constrained('projects')
                ->onDelete('cascade');
            
            $table->index('project_id');
        });

        // Add project_id to reports table
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('site_id')
                ->constrained('projects')
                ->onDelete('cascade');
            
            $table->index('project_id');
        });

        // Add project_id to alert_settings table
        Schema::table('alert_settings', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('user_id')
                ->constrained('projects')
                ->onDelete('cascade');
            
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alert_settings', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('competitors', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('keywords', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
