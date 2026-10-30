<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates keywords table for the keyword rank tracking feature.
     * This is a minimal migration to support competitor_rankings foreign key.
     * Full implementation will be done in SPEC-001-1.
     */
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('keyword');
            $table->text('target_url')->nullable();
            $table->string('search_engine')->default('google');
            $table->string('location')->default('us');
            $table->string('device')->default('desktop');
            $table->string('status')->default('active');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            // Indexes for query optimization
            $table->index('user_id');
            $table->index('status');
            $table->index('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
