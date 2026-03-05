<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for TASK-33-1: Add Visibility Columns to Audits Table.
 *
 * Adds visibility, public_hash, and public_hash_created_at columns
 * to support public audit sharing functionality (SPEC-33).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            // Add visibility ENUM column with default 'private'
            $table->enum('visibility', ['private', 'public'])
                ->default('private')
                ->after('completed_at');

            // Add public_hash VARCHAR(32) UNIQUE NULL column
            $table->string('public_hash', 32)
                ->nullable()
                ->unique()
                ->after('visibility');

            // Add public_hash_created_at TIMESTAMP NULL column
            $table->timestamp('public_hash_created_at')
                ->nullable()
                ->after('public_hash');

            // Index on public_hash for fast lookups
            // Note: Unique constraint already creates an index, but we add an explicit one for clarity
            $table->index('public_hash', 'idx_audits_public_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_audits_public_hash');

            // Drop unique constraint (MySQL requires dropping unique before column)
            $table->dropUnique(['public_hash']);

            // Drop columns
            $table->dropColumn([
                'visibility',
                'public_hash',
                'public_hash_created_at',
            ]);
        });
    }
};
