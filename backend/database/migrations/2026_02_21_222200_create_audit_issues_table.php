<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates audit_issues table for FEAT-014 SEO Site Audits.
     * Stores detailed issue records for audits.
     */
    public function up(): void
    {
        Schema::create('audit_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->onDelete('cascade');
            $table->string('url', 2083);
            $table->foreignId('issue_type_id')->constrained()->onDelete('cascade');
            $table->enum('severity', ['critical', 'warning', 'notice']);
            $table->text('description');
            $table->text('recommendation');
            $table->enum('status', ['open', 'fixed', 'ignored'])->default('open');
            $table->timestamps();

            // Indexes for query optimization
            $table->index(['audit_id', 'severity'], 'idx_audit_severity');
            $table->index('severity');
            $table->index('status');
            $table->index(['audit_id', 'status'], 'idx_audit_status');
            $table->index('issue_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_issues');
    }
};