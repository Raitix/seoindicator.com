<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add ai_visibility_preferences column to users table.
 *
 * Stores user preferences for AI Visibility alerts:
 * - email_notifications_enabled: Whether to send email notifications
 * - webhook_url: Optional webhook URL for alert delivery
 * - alert_types: Array of alert types to receive (mention_spike, mention_drop, competitor_gain)
 *
 * @see TASK-34-20
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('ai_visibility_preferences')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ai_visibility_preferences');
        });
    }
};
