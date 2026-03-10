<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair production schema drift for Local SEO tables.
     */
    public function up(): void
    {
        if (Schema::hasTable('business_locations') && !Schema::hasColumn('business_locations', 'is_active')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }

        if (Schema::hasTable('local_seo_alerts') && !Schema::hasColumn('local_seo_alerts', 'read_at')) {
            Schema::table('local_seo_alerts', function (Blueprint $table) {
                $table->timestamp('read_at')->nullable();
            });
        }
    }

    /**
     * Reverse the remediation.
     */
    public function down(): void
    {
        if (Schema::hasTable('business_locations') && Schema::hasColumn('business_locations', 'is_active')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasTable('local_seo_alerts') && Schema::hasColumn('local_seo_alerts', 'read_at')) {
            Schema::table('local_seo_alerts', function (Blueprint $table) {
                $table->dropColumn('read_at');
            });
        }
    }
};
