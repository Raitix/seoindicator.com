<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seo_analyses', function (Blueprint $table) {
            $table->string('ai_model', 100)->nullable()->after('analysis_data');
            $table->unsignedInteger('prompt_tokens')->nullable()->after('ai_model');
            $table->unsignedInteger('response_tokens')->nullable()->after('prompt_tokens');
            $table->unsignedInteger('total_tokens')->nullable()->after('response_tokens');
            $table->json('truncation_applied')->nullable()->after('total_tokens');
            $table->text('overall_summary')->nullable()->after('truncation_applied');
            $table->json('category_scores')->nullable()->after('overall_summary');
            $table->unsignedBigInteger('ai_request_id')->nullable()->after('category_scores');
            
            $table->index('ai_model');
            $table->index('ai_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite requires dropping indexes before dropping columns
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            Schema::table('seo_analyses', function (Blueprint $table) {
                $table->dropIndex(['ai_model']);
                $table->dropIndex(['ai_request_id']);
            });
        }
        
        Schema::table('seo_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'ai_model',
                'prompt_tokens',
                'response_tokens',
                'total_tokens',
                'truncation_applied',
                'overall_summary',
                'category_scores',
                'ai_request_id',
            ]);
        });
    }
};
