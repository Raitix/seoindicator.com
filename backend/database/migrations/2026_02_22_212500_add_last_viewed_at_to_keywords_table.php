<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('keywords', 'last_viewed_at')) {
            Schema::table('keywords', function (Blueprint $table) {
                $table->timestamp('last_viewed_at')->nullable()->after('last_checked_at');
                $table->index('last_viewed_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('keywords', 'last_viewed_at')) {
            Schema::table('keywords', function (Blueprint $table) {
                $table->dropIndex(['last_viewed_at']);
                $table->dropColumn('last_viewed_at');
            });
        }
    }
};
