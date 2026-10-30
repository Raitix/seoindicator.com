<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the analysis_costs table for tracking per-analysis resource costs.
     * This enables cost transparency and future monetization.
     */
    public function up(): void
    {
        Schema::create('analysis_costs', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to analysis_history
            $table->unsignedBigInteger('analysis_id');

            // Cost details
            $table->string('cost_type', 50);
            $table->decimal('cost_units', 10, 4)->default(0);
            $table->string('cost_currency', 3)->default('USD');
            $table->decimal('cost_amount', 10, 6)->default(0);
            $table->string('description', 255)->nullable();

            // Timestamp (costs are immutable - no updated_at)
            $table->timestamp('created_at')->useCurrent();

            // Foreign key constraints
            $table->foreign('analysis_id')
                ->references('id')
                ->on('analysis_history')
                ->onDelete('cascade');

            // Indexes for performance
            $table->index('analysis_id', 'idx_analysis_costs_analysis_id');
            $table->index('cost_type', 'idx_analysis_costs_cost_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_costs');
    }
};
