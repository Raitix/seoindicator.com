<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add metadata column to ai_visibility_alerts table.
 *
 * The metadata column stores additional alert information such as:
 * - competitor_name for competitor_gain alerts
 * - direction for sentiment_change alerts
 * - period_type for all alerts
 *
 * @see TASK-34-19
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_visibility_alerts', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_visibility_alerts', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
