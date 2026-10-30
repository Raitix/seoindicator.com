<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * @Task TASK-35-13
     * @Description Add GBP OAuth token columns to business_locations table
     */
    public function up(): void
    {
        Schema::table('business_locations', function (Blueprint $table) {
            $table->text('gbp_access_token')->nullable()->after('gbp_place_id');
            $table->text('gbp_refresh_token')->nullable()->after('gbp_access_token');
            $table->timestamp('gbp_token_expires_at')->nullable()->after('gbp_refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_locations', function (Blueprint $table) {
            $table->dropColumn([
                'gbp_access_token',
                'gbp_refresh_token',
                'gbp_token_expires_at',
            ]);
        });
    }
};
