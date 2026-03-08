<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates gbp_posts table for Local SEO feature (SPEC-035).
     * Stores Google Business Profile posts data.
     */
    public function up(): void
    {
        Schema::create('gbp_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('business_locations')->onDelete('cascade');
            $table->string('google_post_id', 255);
            $table->string('post_type', 50);
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('media_url', 500)->nullable();
            $table->string('call_to_action', 100)->nullable();
            $table->string('call_to_action_url', 500)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Unique constraint for Google post ID
            $table->unique('google_post_id');
            
            // Index for query optimization
            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gbp_posts');
    }
};
