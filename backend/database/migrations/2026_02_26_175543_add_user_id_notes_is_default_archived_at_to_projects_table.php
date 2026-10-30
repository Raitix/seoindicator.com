<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds user relationship, notes, is_default, and archived_at columns
     * to the projects table for multi-site project management.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add user_id column with foreign key constraint
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->onDelete('cascade');

            // Add notes column for project notes
            $table->text('notes')->nullable()->after('domain');

            // Add is_default flag for default project per user
            $table->boolean('is_default')->default(false)->after('notes');

            // Add archived_at timestamp for soft-archive functionality
            $table->timestamp('archived_at')->nullable()->after('updated_at');

            // Add indexes for performance
            $table->index('user_id', 'idx_projects_user_id');
            $table->index('archived_at', 'idx_projects_archived');
        });

        // Make user_id NOT NULL after adding the column (allows existing rows to be NULL temporarily)
        // In production, you may want to update existing rows to have a default user_id first
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop indexes FIRST (SQLite requires this before dropping columns)
            $table->dropIndex('idx_projects_archived');
            $table->dropIndex('idx_projects_user_id');

            // Drop foreign key constraint
            $table->dropForeign(['user_id']);

            // Then drop columns
            $table->dropColumn(['user_id', 'notes', 'is_default', 'archived_at']);
        });
    }
};
