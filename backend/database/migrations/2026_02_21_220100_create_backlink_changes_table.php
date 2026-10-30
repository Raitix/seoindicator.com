<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates backlink_changes table for FEAT-013 Backlink Monitoring.
     * Tracks changes (new, lost, modified) for backlinks.
     *
     * Note: Uses sites table instead of projects for consistency with existing schema.
     */
    public function up(): void
    {
        Schema::create('backlink_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('backlink_id')->constrained()->onDelete('cascade');
            $table->enum('change_type', ['new', 'lost', 'modified']);
            $table->json('details');
            $table->timestamp('detected_at')->useCurrent();

            // Indexes for change queries
            $table->index(['site_id', 'change_type'], 'idx_site_change');
            $table->index('detected_at');
            $table->index('backlink_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_changes');
    }
};
