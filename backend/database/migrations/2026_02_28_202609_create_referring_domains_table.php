<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates referring_domains table for FEAT-023 Backlinks Indicator.
     * Stores aggregated domain-level backlink metrics per project.
     *
     * @see TASK-23-1
     * @see SPEC-023
     */
    public function up(): void
    {
        Schema::create('referring_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('domain', 255);
            $table->unsignedInteger('backlinks_count')->default(0);
            
            // Domain trust score (0-100 heuristic)
            $table->unsignedTinyInteger('domain_trust_score')->default(0);
            
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            // Unique constraint: one record per domain per project
            $table->unique(['project_id', 'domain'], 'referring_domains_project_domain_unique');

            // Index for sorting by backlinks count
            $table->index('backlinks_count', 'referring_domains_backlinks_count_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referring_domains');
    }
};
