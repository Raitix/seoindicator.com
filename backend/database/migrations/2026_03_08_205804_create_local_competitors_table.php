<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('local_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('business_locations')->onDelete('cascade');
            $table->string('competitor_name');
            $table->string('competitor_gbp_id')->nullable();
            $table->string('competitor_address', 500)->nullable();
            $table->decimal('competitor_rating', 2, 1)->nullable();
            $table->unsignedInteger('competitor_review_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_competitors');
    }
};
