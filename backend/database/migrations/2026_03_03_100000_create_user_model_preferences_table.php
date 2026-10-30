<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for user_model_preferences table.
 *
 * TASK-26-19: User Model Preferences
 *
 * Stores user preferences for default AI models used in analyses.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_model_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('model_id')->constrained('ai_models')->onDelete('cascade');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Unique constraint: user can only have one preference per model
            $table->unique(['user_id', 'model_id']);

            // Indexes for quick lookups
            $table->index('user_id');
            $table->index('model_id');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_model_preferences');
    }
};
