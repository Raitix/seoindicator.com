<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates backlink_summaries table for FEAT-013 Backlink Monitoring.
     * Stores aggregated backlink metrics per domain per site.
     */
    public function up(): void
    {
        Schema::create('backlink_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('domain', 255);
            $table->unsignedInteger('total_backlinks')->default(0);
            $table->unsignedInteger('referring_domains')->default(0);
            $table->float('domain_authority', 5, 2)->unsigned()->default(0.00);
            $table->timestamp('checked_at')->useCurrent();

            // Unique constraint: one summary per domain per site
            $table->unique(['site_id', 'domain'], 'idx_site_domain');

            // Indexes for common queries
            $table->index('site_id');
            $table->index('domain');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlink_summaries');
    }
};