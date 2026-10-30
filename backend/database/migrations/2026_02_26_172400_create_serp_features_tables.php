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
        // SERP Features table - stores current feature presence for keywords
        Schema::create('serp_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
            $table->string('feature_type', 50);
            $table->boolean('is_present')->default(false);
            $table->unsignedInteger('position')->nullable(); // Position within feature (e.g., #1 in local pack)
            $table->string('url', 2083)->nullable();
            $table->string('title')->nullable();
            $table->text('snippet')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['keyword_id', 'feature_type']);
            $table->index('feature_type');
            $table->index('is_present');
            $table->index('checked_at');
        });

        // SERP Feature History table - tracks gained/lost feature changes
        Schema::create('serp_feature_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
            $table->string('feature_type', 50);
            $table->enum('action', ['gained', 'lost']);
            $table->string('previous_url', 2083)->nullable();
            $table->string('current_url', 2083)->nullable();
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamps();

            $table->index(['keyword_id', 'feature_type']);
            $table->index('feature_type');
            $table->index('detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serp_feature_history');
        Schema::dropIfExists('serp_features');
    }
};
