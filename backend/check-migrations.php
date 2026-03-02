<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo 'Migration batches in database:' . PHP_EOL;
$migrations = DB::table('migrations')->orderBy('batch', 'desc')->get();
foreach($migrations as $m) {
    echo $m->migration . ' (batch: ' . $m->batch . ')' . PHP_EOL;
}

echo PHP_EOL . 'Last 3 migrations:' . PHP_EOL;
$lastMigrations = DB::table('migrations')->orderBy('id', 'desc')->take(3)->get();
foreach($lastMigrations as $m) {
    echo $m->migration . ' (batch: ' . $m->batch . ')' . PHP_EOL;
}
