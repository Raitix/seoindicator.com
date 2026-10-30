<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the public_audit_views table for tracking analytics
     * for public audit page views.
     *
     * TASK-33-2: Create Public Audit Views Table
     */
    public function up(): void
    {
        Schema::create('public_audit_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->onDelete('cascade');
            $table->string('public_hash', 32);
            $table->timestamp('viewed_at')->useCurrent();
            $table->string('referrer', 500)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->boolean('is_lead_capture')->default(false);
            $table->string('lead_email', 255)->nullable();
            $table->timestamps();

            // Indexes for query performance
            $table->index('audit_id');
            $table->index('public_hash');
            $table->index('viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_audit_views');
    }
};
