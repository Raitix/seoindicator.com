<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates tables for automated SEO reports:
     * - reports
     * - report_templates
     * - scheduled_reports
     * - report_branding
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type');
            $table->string('format')->default('pdf');
            $table->string('status')->default('pending');
            $table->date('date_range_start');
            $table->date('date_range_end');
            $table->date('compare_range_start')->nullable();
            $table->date('compare_range_end')->nullable();
            $table->json('sections')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('site_id');
            $table->index('status');
            $table->index('generated_at');
        });

        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->json('sections');
            $table->json('branding')->nullable();
            $table->text('cover_page_text')->nullable();
            $table->text('executive_summary')->nullable();
            $table->timestamps();

            $table->index('site_id');
            $table->index('is_default');
        });

        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('report_templates')->nullOnDelete();
            $table->string('name');
            $table->string('frequency');
            $table->string('format')->default('pdf');
            $table->json('recipients');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_run')->nullable();
            $table->timestamp('next_run')->nullable();
            $table->timestamps();

            $table->index('site_id');
            $table->index('next_run');
            $table->index('enabled');
        });

        Schema::create('report_branding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->unique()->constrained()->onDelete('cascade');
            $table->string('logo_url')->nullable();
            $table->string('logo_file')->nullable();
            $table->string('primary_color')->default('#2563eb');
            $table->string('secondary_color')->default('#64748b');
            $table->string('font_family')->default('Inter');
            $table->string('company_name')->nullable();
            $table->string('company_website')->nullable();
            $table->timestamps();

            $table->index('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_branding');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('reports');
    }
};
