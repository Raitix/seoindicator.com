<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds cancelled_at and partial_results columns for TASK-22-17.
     *
     * @see TASK-22-17
     * @see SPEC-022
     */
    public function up(): void
    {
        Schema::table('crawl_jobs', function (Blueprint $table) {
            // Timestamp for when job was cancelled
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            
            // Partial results storage for timeout/cancellation scenarios
            $table->json('partial_results')->nullable()->after('error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crawl_jobs', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'partial_results']);
        });
    }
};
