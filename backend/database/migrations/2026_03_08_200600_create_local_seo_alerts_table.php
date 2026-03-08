<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates local_seo_alerts table for Local SEO feature (SPEC-035).
     * Stores alerts and notifications for local SEO tracking.
     */
    public function up(): void
    {
        Schema::create('local_seo_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained('business_locations')->onDelete('cascade');
            $table->string('alert_type', 50);
            $table->string('severity', 20);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // Indexes for query optimization
            $table->index('user_id');
            $table->index('location_id');
            $table->index('alert_type');
            $table->index('is_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_seo_alerts');
    }
};
