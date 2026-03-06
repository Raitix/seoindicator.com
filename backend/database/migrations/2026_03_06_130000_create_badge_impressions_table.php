<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the badge_impressions table to track analytics for embedded
     * badge views and clicks on third-party sites.
     */
    public function up(): void
    {
        Schema::create('badge_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->string('public_hash', 32);
            $table->string('referrer_domain', 255)->nullable();
            $table->timestamp('impression_at')->useCurrent();
            $table->timestamp('clicked_at')->nullable();
            $table->string('click_target', 10)->nullable();
            $table->string('ip_hash', 64)->nullable();

            // Indexes for query performance
            $table->index('audit_id');
            $table->index('public_hash');
            $table->index('impression_at');

            // Note: No unique constraint on public_hash - multiple impressions per hash allowed
            // This is intentional for analytics tracking (same embed can have many views)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_impressions');
    }
};
