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
        Schema::create('user_onboarding', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->json('completed_steps')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('first_keyword', 255)->nullable();
            $table->unsignedBigInteger('keyword_id')->nullable();
            $table->json('alert_preferences')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();

            // Unique index on user_id
            $table->unique('user_id', 'idx_user_onboarding_user');
            
            // Index on completed_at
            $table->index('completed_at', 'idx_user_onboarding_completed');

            // Foreign key constraints
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('set null');

            $table->foreign('keyword_id')
                ->references('id')
                ->on('keywords')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_onboarding');
    }
};
