<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds last_login_at and plan fields to users table
     * for admin dashboard user statistics.
     * 
     * Task: TASK-27-18
     * Feature: FEAT-027 (Admin Dashboard - Ops + Usage)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('avatar');
            $table->string('plan', 50)->default('free')->after('last_login_at');
            
            // Index for active users query (logged in within last 30 days)
            $table->index('last_login_at');
            $table->index('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_login_at']);
            $table->dropIndex(['plan']);
            $table->dropColumn(['last_login_at', 'plan']);
        });
    }
};
