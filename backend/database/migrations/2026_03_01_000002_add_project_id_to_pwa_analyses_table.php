<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds project_id to pwa_analyses table for direct project association.
     * This allows PWA analysis to be linked directly to projects for visibility scoring.
     *
     * @see TASK-24-13
     */
    public function up(): void
    {
        Schema::table('pwa_analyses', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('site_id');
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');
            
            $table->index('project_id', 'idx_pwa_project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pwa_analyses', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['project_id']);
            // Drop index before column (required for SQLite)
            $table->dropIndex('idx_pwa_project_id');
            // Now drop the column
            $table->dropColumn('project_id');
        });
    }
};
