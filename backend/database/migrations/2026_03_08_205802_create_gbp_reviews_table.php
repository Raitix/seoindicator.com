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
        Schema::create('gbp_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('business_locations')->onDelete('cascade');
            $table->string('google_review_id', 255);
            $table->string('author_name')->nullable();
            $table->string('author_photo', 500)->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('review_text')->nullable();
            $table->string('review_text_sentiment', 20)->nullable();
            $table->text('owner_response')->nullable();
            $table->timestamp('owner_response_date')->nullable();
            $table->timestamp('review_date')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique('google_review_id');
            $table->index('location_id');
            $table->index('rating');
            $table->index('review_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gbp_reviews');
    }
};
