<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     Creates annotations table for keyword-level SEO event annotations.
     * Allows users to document significant changes, updates, and events
     * that may impact keyword rankings.
     *
     * @see TASK-9-1
     */
    public function up(): void
    {
        Schema::create('annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 50)->default('other');
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('keyword_id', 'idx_annotations_keyword_id');
            $table->index('project_id', 'idx_annotations_project_id');
            $table->index('date', 'idx_annotations_date');
            $table->index('category', 'idx_annotations_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annotations');
    }
};
