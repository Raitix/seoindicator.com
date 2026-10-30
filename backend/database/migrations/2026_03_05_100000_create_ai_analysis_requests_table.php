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
        Schema::create('ai_analysis_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained('seo_analyses')->onDelete('cascade');
            $table->string('model', 100);
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('response_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->json('truncation_strategies')->nullable();
            $table->string('request_hash', 64)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['analysis_id', 'status']);
            $table->index('model');
            $table->index('request_hash');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_requests');
    }
};
