<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds backlink_component column to visibility_scores table for
     * storing the backlink profile score contribution to overall visibility.
     *
     * @see TASK-23-15
     */
    public function up(): void
    {
        Schema::table('visibility_scores', function (Blueprint $table) {
            // Add backlink component column after volume_component
            $table->decimal('backlink_component', 5, 2)->default(0.00)->after('volume_component');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visibility_scores', function (Blueprint $table) {
            $table->dropColumn('backlink_component');
        });
    }
};
