<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for TASK-33-4: Create Public Audits Table.
 *
 * Creates the public_audits table for managing public audit sharing
 * functionality (SPEC-33).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('public_audits', function (Blueprint $table) {
            $table->id();

            // Foreign key to audits table
            $table->foreignId('audit_id')
                ->constrained('audits')
                ->cascadeOnDelete();

            // Public hash for sharing (32-char alphanumeric)
            $table->string('public_hash', 32)
                ->nullable()
                ->unique();

            // Visibility status (private/public)
            $table->enum('visibility', ['private', 'public'])
                ->default('private');

            // Timestamp for when hash was created
            $table->timestamp('public_hash_created_at')
                ->nullable();

            $table->timestamps();

            // Index for fast public hash lookups
            $table->index('public_hash', 'idx_public_audits_hash');

            // Index for visibility filtering
            $table->index('visibility', 'idx_public_audits_visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_audits');
    }
};
