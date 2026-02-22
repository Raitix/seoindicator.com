<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates gsc_data table for storing imported Google Search Console metrics.
     * Stores clicks, impressions, CTR, and position data per query and date.
     *
     * @see SPEC-012: Google Search Console Integration
     * @see TASK-12-2: Create GSC Data Table
     */
    public function up(): void
    {
        Schema::create('gsc_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            
            // Search query and page
            $table->string('query', 255)->index();
            $table->string('page', 255)->nullable();
            
            // GSC metrics
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 8, 5)->default(0);
            $table->decimal('avg_position', 8, 2)->default(0);
            
            // Date of the data (not the date it was imported)
            $table->date('data_date')->index();
            
            $table->timestamps();
            
            // Composite indexes for efficient querying
            $table->index(['query', 'data_date'], 'idx_query_date');
            $table->index(['site_id', 'data_date'], 'idx_site_date');
            $table->index(['site_id', 'query'], 'idx_site_query');
            
            // Unique constraint: prevent duplicate data imports
            $table->unique(['site_id', 'query', 'page', 'data_date'], 'idx_unique_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gsc_data');
    }
};