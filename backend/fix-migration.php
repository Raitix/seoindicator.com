<?php
// Mark migration as already run
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$lastBatch = DB::table('migrations')->max('batch');
DB::table('migrations')->insert([
    'migration' => '2026_02_27_233523_create_visibility_scores_table',
    'batch' => $lastBatch + 1
]);
echo "Migration marked as run.\n";
