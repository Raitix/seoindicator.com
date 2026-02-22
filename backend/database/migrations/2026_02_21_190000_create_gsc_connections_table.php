<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates gsc_connections table for Google Search Console OAuth integration.
     * Stores OAuth tokens and property information for GSC API access.
     *
     * Security: Tokens are encrypted at rest using Laravel's encryption.
     *
     * @see SPEC-012: Google Search Console Integration
     * @see TASK-12-1: Create GSC Connections Table
     */
    public function up(): void
    {
        Schema::create('gsc_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Property URLs (site_url is the domain, property_url is the full GSC property)
            $table->string('site_url', 500);
            $table->string('property_url', 500);
            
            // OAuth tokens (encrypted at application level)
            // Using text for encrypted tokens which can be longer
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('token_expires_at');
            
            // Connection status
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->timestamp('last_synced_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('token_expires_at');
            
            // Unique constraint: one connection per user per property
            $table->unique(['user_id', 'property_url'], 'idx_user_property');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gsc_connections');
    }
};
