<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates competitors and competitor_rankings tables for the
     * competitor rank comparison feature (SPEC-003-1).
     */
    public function up(): void
    {
        // Create competitors table
        Schema::create('competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('display_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Unique constraint: one domain per user
            $table->unique(['user_id', 'domain']);

            // Indexes for query optimization
            $table->index('user_id');
            $table->index('domain');
        });

        // Create competitor_rankings table
        Schema::create('competitor_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained()->onDelete('cascade');
            $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('position')->nullable();
            $table->text('url')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            // Unique constraint: one ranking per competitor/keyword/date
            $table->unique(['competitor_id', 'keyword_id', 'checked_at']);

            // Indexes for query optimization
            $table->index('competitor_id');
            $table->index('keyword_id');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_rankings');
        Schema::dropIfExists('competitors');
    }
};
