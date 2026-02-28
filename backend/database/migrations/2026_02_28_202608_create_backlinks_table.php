<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates backlinks table for FEAT-023 Backlinks Indicator.
     * Stores individual backlink records with project association.
     *
     * @see TASK-23-1
     * @see SPEC-023
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $isSqlite = stripos($driver, 'sqlite') !== false;
        $isMysql = stripos($driver, 'mysql') !== false || stripos($driver, 'mariadb') !== false;

        Schema::create('backlinks', function (Blueprint $table) use ($driver, $isMysql, $isSqlite) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('source_url', 2083);
            $table->string('source_domain', 255);
            $table->string('target_url', 2083);
            $table->string('anchor_text', 500)->nullable();
            
            // SQLite doesn't support ENUM, use string with validation in app layer
            if ($isSqlite) {
                $table->string('link_type', 20)->default('unknown');
            } else {
                $table->enum('link_type', ['dofollow', 'nofollow', 'unknown'])->default('unknown');
            }
            
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for common queries with unique names
            $table->index(['project_id', 'source_domain'], 'backlinks_project_domain_index');
            $table->index('is_active', 'backlinks_is_active_index');
        });
        
        // Add source_url and target_url indexes separately for MySQL (needs length prefix)
        if ($isMysql) {
            DB::statement('CREATE INDEX backlinks_source_url_index ON backlinks (source_url(255))');
            DB::statement('CREATE INDEX backlinks_target_url_index ON backlinks (target_url(255))');
        } else {
            Schema::table('backlinks', function (Blueprint $table) {
                $table->index('source_url', 'backlinks_source_url_index');
                $table->index('target_url', 'backlinks_target_url_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backlinks');
    }
};
