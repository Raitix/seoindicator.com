<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates team_members table for multi-user collaboration at the team level.
     * Links users to accounts (teams) with roles and invitation tracking.
     */
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'editor', 'viewer'])->default('viewer');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('invitation_token', 128)->nullable();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamps();

            // Unique constraint: one user can have only one membership per account
            $table->unique(['account_id', 'user_id'], 'idx_team_account_user');

            // Indexes for common queries
            $table->index('role');
            $table->index('invitation_token');
            $table->index('accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
