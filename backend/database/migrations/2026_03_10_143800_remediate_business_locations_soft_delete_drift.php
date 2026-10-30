<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair business_locations soft-delete schema drift in production.
     */
    public function up(): void
    {
        if (Schema::hasTable('business_locations') && !Schema::hasColumn('business_locations', 'deleted_at')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the remediation.
     */
    public function down(): void
    {
        if (Schema::hasTable('business_locations') && Schema::hasColumn('business_locations', 'deleted_at')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
