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
        Schema::create('ai_suggestions_cache', function (Blueprint $table) {
            $table->id();
            $table->string('request_hash', 64)->unique();
            $table->string('indicator', 50);
            $table->string('context_hash', 64);
            $table->text('response_json');
            $table->string('provider', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at')->default(\Illuminate\Support\Facades\DB::raw('CURRENT_TIMESTAMP'));

            $table->index('request_hash', 'idx_hash');
            $table->index('expires_at', 'idx_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions_cache');
    }
};
