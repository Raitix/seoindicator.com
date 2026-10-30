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
        Schema::create('ai_suggestion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('session_id', 255)->nullable();
            $table->string('indicator', 50);
            $table->string('provider', 50);
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('latency_ms');
            $table->boolean('success');
            $table->text('error_message')->nullable();
            $table->string('request_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'indicator'], 'idx_user_indicator');
            $table->index(['provider', 'created_at'], 'idx_provider_created');
            $table->index('success', 'idx_success');
            $table->index('request_hash', 'idx_request_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_suggestion_logs');
    }
};
