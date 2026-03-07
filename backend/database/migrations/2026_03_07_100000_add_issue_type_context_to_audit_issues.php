<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds issue_type and context columns to audit_issues table.
     * Adds type column to issue_types table.
     *
     * @see TASK-14-8
     */
    public function up(): void
    {
        // Add columns to audit_issues table
        Schema::table('audit_issues', function (Blueprint $table) {
            $table->string('issue_type', 100)->nullable()->after('issue_type_id');
            $table->json('context')->nullable()->after('recommendation');
        });

        // Add type column to issue_types table
        Schema::table('issue_types', function (Blueprint $table) {
            $table->string('type', 100)->nullable()->after('id');
        });

        // Populate type column from name
        DB::table('issue_types')->update(['type' => DB::raw('name')]);

        // Populate issue_type from issue_types relation
        $issues = DB::table('audit_issues')->get();
        foreach ($issues as $issue) {
            $issueType = DB::table('issue_types')->where('id', $issue->issue_type_id)->first();
            if ($issueType) {
                DB::table('audit_issues')
                    ->where('id', $issue->id)
                    ->update(['issue_type' => $issueType->type ?? $issueType->name]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_issues', function (Blueprint $table) {
            $table->dropColumn(['issue_type', 'context']);
        });

        Schema::table('issue_types', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
