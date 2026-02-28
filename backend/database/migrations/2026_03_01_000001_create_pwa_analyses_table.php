<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the pwa_analyses table for storing cached PWA manifest analysis results.
     *
     * @see TASK-24-1
     * @see SPEC-024
     * @see FEAT-024
     */
    public function up(): void
    {
        Schema::create('pwa_analyses', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to sites
            $table->unsignedBigInteger('site_id');
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->onDelete('cascade');

            // Manifest presence and validity
            $table->boolean('has_manifest')->default(false);
            $table->string('manifest_url', 500)->nullable();
            $table->boolean('manifest_valid')->default(false);
            $table->json('manifest_json')->nullable();

            // Field presence flags
            $table->boolean('has_name')->default(false);
            $table->boolean('has_short_name')->default(false);
            $table->boolean('has_start_url')->default(false);
            $table->boolean('has_display')->default(false);
            $table->boolean('has_background_color')->default(false);
            $table->boolean('has_theme_color')->default(false);
            $table->boolean('has_description')->default(false);

            // Icon validation
            $table->unsignedInteger('icons_total')->default(0);
            $table->unsignedInteger('icons_valid')->default(0);
            $table->boolean('icons_192_present')->default(false);
            $table->boolean('icons_512_present')->default(false);
            $table->boolean('icons_180_present')->default(false);
            $table->json('icon_errors')->nullable();

            // Overall score
            $table->unsignedTinyInteger('pwa_score')->default(0); // 0-100
            $table->enum('readiness_level', ['none', 'basic', 'enhanced', 'full'])
                ->default('none');

            // Timestamps
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['site_id', 'analyzed_at'], 'idx_site_analyzed');
            $table->index('pwa_score', 'idx_pwa_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pwa_analyses');
    }
};
