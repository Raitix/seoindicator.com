<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates credits and credit_transactions tables for the billing system.
     * - credits: Tracks user credit balance and totals
     * - credit_transactions: Immutable audit trail of all credit movements
     *
     * Task: TASK-26-1
     * Feature: FEAT-026 (Billing, Credits & Model Selection)
     */
    public function up(): void
    {
        // Create credits table
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->decimal('total_purchased', 10, 2)->default(0.00);
            $table->decimal('total_spent', 10, 2)->default(0.00);
            $table->timestamps();

            // One credits record per user
            $table->unique('user_id');
        });

        // Create credit_transactions table
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->enum('type', ['purchase', 'usage', 'refund', 'bonus']);
            $table->string('description', 500);
            
            // Polymorphic reference (nullable)
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Payment provider info (nullable)
            $table->string('payment_provider', 20)->nullable();
            $table->string('payment_id', 255)->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            // No updated_at - transactions are immutable
            $table->timestamp('created_at')->useCurrent();

            // Indexes for query optimization
            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['payment_provider', 'payment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('credits');
    }
};
