<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add resolved_at column to ai_visibility_alerts table.
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
        Schema::table('ai_visibility_alerts', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable()->after('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_visibility_alerts', function (Blueprint $table) {
            $table->dropColumn('resolved_at');
        });
    }
};
