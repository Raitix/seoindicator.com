<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IssueTypeSeeder extends Seeder
{
    public function run()
    {
        DB::table('issue_types')->insert([
            // TODO: Add seeder data (e.g., initial issue type templates)
        ]);
    }
}