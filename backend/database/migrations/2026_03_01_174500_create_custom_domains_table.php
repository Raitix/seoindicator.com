<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the custom_domains table for tracking custom domains
     * set for white-label reselling.
     *
     * @see TASK-15-2
     * @see SPEC-015
     * @see FEAT-015
     */
    public function up(): void
    {
        Schema::create('custom_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('domain', 255)->unique();
            $table->boolean('dns_verified')->default(false);
            $table->boolean('ssl_provisioned')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
            $table->timestamp('verified_at')->nullable();

            // Explicit index for foreign key (SQLite doesn't auto-create)
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_domains');
    }
};
