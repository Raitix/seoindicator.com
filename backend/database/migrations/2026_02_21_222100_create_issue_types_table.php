<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates issue_types table for FEAT-014 SEO Site Audits.
     * Stores reusable templates for SEO issues with default severity and recommendations.
     */
    public function up(): void
    {
        Schema::create('issue_types', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50);
            $table->string('name', 100);
            $table->text('description');
            $table->enum('severity_default', ['critical', 'warning', 'notice']);
            $table->text('recommendation_template');
            $table->timestamps();

            // Indexes for query optimization
            $table->index('category');
            $table->index('severity_default');
            $table->unique(['category', 'name'], 'idx_category_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_types');
    }
};