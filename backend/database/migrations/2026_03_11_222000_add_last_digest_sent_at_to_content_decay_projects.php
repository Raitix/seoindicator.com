<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds last_digest_sent_at column to content_decay_projects table.
     *
     * @see TASK-36-9: Queue Jobs - Content Decay Detection
     */
    public function up(): void
    {
        Schema::table('content_decay_projects', function (Blueprint $table) {
            $table->timestamp('last_digest_sent_at')->nullable()->after('last_scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_decay_projects', function (Blueprint $table) {
            $table->dropColumn('last_digest_sent_at');
        });
    }
};
