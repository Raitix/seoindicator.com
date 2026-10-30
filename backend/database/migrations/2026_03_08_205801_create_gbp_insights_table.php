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
        Schema::create('gbp_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('business_locations')->onDelete('cascade');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('searches')->default(0);
            $table->unsignedInteger('calls')->default(0);
            $table->unsignedInteger('directions')->default(0);
            $table->unsignedInteger('website_clicks')->default(0);
            $table->unsignedInteger('photos_views')->default(0);
            $table->date('insights_date');
            $table->timestamp('created_at')->useCurrent();

            // Unique constraint and indexes
            $table->unique(['location_id', 'insights_date']);
            $table->index('insights_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gbp_insights');
    }
};
