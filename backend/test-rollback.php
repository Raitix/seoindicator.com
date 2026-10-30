<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Set SQLite in-memory for testing
config(['database.default' => 'sqlite']);
config(['database.connections.sqlite.database' => ':memory:']);

// Run migrate:fresh
Artisan::call('migrate:fresh');
echo "After migrate:fresh:" . PHP_EOL;
$migrations = DB::table('migrations')->orderBy('batch', 'desc')->get();
foreach($migrations->take(5) as $m) {
    echo "  " . $m->migration . " (batch: " . $m->batch . ")" . PHP_EOL;
}

echo PHP_EOL . "Total batches: " . $migrations->max('batch') . PHP_EOL;

// Check if admin tables exist
echo PHP_EOL . "Admin tables exist:" . PHP_EOL;
echo "  admin_activity_logs: " . (Schema::hasTable('admin_activity_logs') ? 'YES' : 'NO') . PHP_EOL;
echo "  system_metrics_cache: " . (Schema::hasTable('system_metrics_cache') ? 'YES' : 'NO') . PHP_EOL;

// Rollback
echo PHP_EOL . "Running migrate:rollback --step=1..." . PHP_EOL;
Artisan::call('migrate:rollback', ['--step' => 1]);
echo Artisan::output() . PHP_EOL;

// Check tables after rollback
echo "After rollback:" . PHP_EOL;
echo "  admin_activity_logs: " . (Schema::hasTable('admin_activity_logs') ? 'YES' : 'NO') . PHP_EOL;
echo "  system_metrics_cache: " . (Schema::hasTable('system_metrics_cache') ? 'YES' : 'NO') . PHP_EOL;

// Check what was rolled back
$migrationsAfter = DB::table('migrations')->get();
echo PHP_EOL . "Migrations remaining: " . $migrationsAfter->count() . PHP_EOL;
