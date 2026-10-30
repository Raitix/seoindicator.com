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
     * Creates anchor_texts table for FEAT-023 Backlinks Indicator.
     * Stores anchor text distribution analysis per project.
     *
     * @see TASK-23-1
     * @see SPEC-023
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $isMysql = stripos($driver, 'mysql') !== false || stripos($driver, 'mariadb') !== false;

        Schema::create('anchor_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('anchor_text', 500);
            $table->unsignedInteger('backlinks_count')->default(0);
            
            // Flags for anchor text classification
            $table->boolean('is_branded')->default(false);
            $table->boolean('is_exact_match')->default(false);
            
            $table->timestamps();

            // Index for filtering branded anchors
            $table->index('is_branded', 'anchor_texts_is_branded_index');
        });
        
        // Add project + anchor text index separately for MySQL (needs length prefix)
        if ($isMysql) {
            DB::statement('CREATE INDEX anchor_texts_project_anchor_index ON anchor_texts (project_id, anchor_text(255))');
        } else {
            Schema::table('anchor_texts', function (Blueprint $table) {
                $table->index(['project_id', 'anchor_text'], 'anchor_texts_project_anchor_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anchor_texts');
    }
};
