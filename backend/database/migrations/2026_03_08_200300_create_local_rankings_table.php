<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates local_rankings table for Local SEO feature (SPEC-035).
     * Stores local search ranking positions for keywords.
     */
    public function up(): void
    {
        Schema::create('local_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('business_locations')->onDelete('cascade');
            $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
            $table->string('search_location');
            $table->decimal('search_lat', 10, 8)->nullable();
            $table->decimal('search_lng', 11, 8)->nullable();
            $table->unsignedTinyInteger('local_pack_position')->nullable();
            $table->unsignedInteger('local_organic_position')->nullable();
            $table->json('competitors_in_pack')->nullable();
            $table->timestamp('checked_at')->useCurrent();

            // Indexes for query optimization
            $table->index(['location_id', 'keyword_id']);
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_rankings');
    }
};
