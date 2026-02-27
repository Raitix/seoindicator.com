<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for tool_leads table.
 * 
 * TASK-16-5: Email Capture Modal & API
 * TASK-16-6: Lead Storage Service
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tool_leads', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('tool', 50);
            $table->string('url', 2083)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('email');
            $table->index('tool');
            $table->index('created_at');
            $table->index(['email', 'tool']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_leads');
    }
};
