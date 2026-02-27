<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds delivery tracking fields to reports table for TASK-4-19.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('error');
            $table->string('delivery_status')->default('pending')->after('delivered_at');
            $table->json('delivery_log')->nullable()->after('delivery_status');
        });

        // Add index for delivery status queries
        Schema::table('reports', function (Blueprint $table) {
            $table->index('delivery_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['delivery_status']);
            $table->dropColumn(['delivered_at', 'delivery_status', 'delivery_log']);
        });
    }
};
