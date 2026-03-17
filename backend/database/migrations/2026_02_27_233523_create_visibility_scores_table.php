<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the visibility_scores table to store daily calculated visibility
     * scores for each project. These scores are used for trend analysis and
     * dashboard metrics.
     */
    public function up(): void
    {
        Schema::create('visibility_scores', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            
            $table->foreignId('project_id')
                ->constrained('projects')
                ->onDelete('cascade');
            
            // Overall visibility score (0-100)
            $table->unsignedTinyInteger('score');
            
            // Component scores for transparency
            $table->decimal('position_component', 5, 2);
            $table->decimal('serp_component', 5, 2);
            $table->decimal('volume_component', 5, 2);
            
            // Metadata
            $table->unsignedInteger('keywords_count')->default(0);
            $table->date('calculation_date'); // Date-only for unique constraint
            $table->timestamp('calculated_at')->useCurrent();
            
            // Indexes for efficient queries
            $table->index('project_id', 'idx_visibility_project_id');
            $table->index('calculated_at', 'idx_visibility_calculated_at');
            $table->index('calculation_date', 'idx_visibility_calculation_date');
            
            // Unique constraint: one score per project per day
            $table->unique(['project_id', 'calculation_date'], 'unique_project_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visibility_scores');
    }
};
