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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 100);
            $table->string('model_id', 255);
            $table->string('display_name', 255);
            $table->decimal('cost_per_1k_input', 10, 6)->default(0.000000);
            $table->decimal('cost_per_1k_output', 10, 6)->default(0.000000);
            $table->unsignedInteger('context_window')->default(4096);
            $table->boolean('is_free_tier')->default(false);
            $table->unsignedInteger('free_tier_limit')->nullable();
            $table->json('capabilities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Unique index on provider + model_id
            $table->unique(['provider', 'model_id']);
            
            // Index on is_active for filtering active models
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
