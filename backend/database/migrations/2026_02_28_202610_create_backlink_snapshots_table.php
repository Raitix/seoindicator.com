<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates backlink_snapshots table for FEAT-023 Backlinks Indicator.
     * Stores historical backlink metrics for trend analysis.
     *
     * @see TASK-23-1
     * @see SPEC-023
     */
    public function up(): void
    {
        Schema::create('backlink_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            
            // Snapshot metrics
            $table->unsignedInteger('total_backlinks')->default(0);
            $table->unsignedInteger('referring_domains_count')->default(0);
            $table->unsignedInteger('dofollow_count')->default(0);
            $table->unsignedInteger('nofollow_count')->default(0);
            
            // Quality scores (DECIMAL(5,2) = 0.00 to 999.99)
            $table->decimal('avg_domain_trust', 5, 2)->default(0.00);
            $table->decimal('anchor_diversity_score', 5, 2)->default(0.00);
            
            // Snapshot date
            $table->date('snapshot_date');
            
            // Only created_at needed for snapshots (immutable records)
            $table->timestamp('created_at')->useCurrent();

            // Unique constraint: one snapshot per project per day
            $table->unique(['project_id', 'snapshot_date'], 'backlink_snapshots_project_date_unique');

            // Index for date-based queries
            $table->index('snapshot_date', 'idx_snapshot_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_snapshots');
    }
};
