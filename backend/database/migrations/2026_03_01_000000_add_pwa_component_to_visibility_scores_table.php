<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds PWA component to visibility_scores table for TASK-24-13.
     * PWA readiness contributes 10% weight to overall visibility score.
     */
    public function up(): void
    {
        Schema::table('visibility_scores', function (Blueprint $table) {
            $table->decimal('pwa_component', 5, 2)->default(0.00)->after('volume_component');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visibility_scores', function (Blueprint $table) {
            $table->dropColumn('pwa_component');
        });
    }
};
