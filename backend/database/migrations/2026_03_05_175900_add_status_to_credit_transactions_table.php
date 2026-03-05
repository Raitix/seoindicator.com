<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds status column to credit_transactions for payment workflow tracking.
     * Required for TASK-26-13 (PayPal Order Endpoint).
     * 
     * Status values:
     * - pending: Payment initiated but not completed
     * - completed: Payment successful, credits added
     * - failed: Payment failed
     * - cancelled: Payment cancelled by user
     * - refunded: Payment refunded
     */
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('completed')->after('type');
        });

        // Update existing transactions to 'completed' status
        DB::table('credit_transactions')->update(['status' => 'completed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
