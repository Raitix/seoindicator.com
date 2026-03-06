<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check SQL mode
$result = DB::select('SELECT @@sql_mode');
echo "SQL Mode: " . $result[0]->{'@@sql_mode'} . "\n\n";

// Test insert
$impression = new \App\Models\BadgeImpression();
$impression->audit_id = \App\Models\Audit::first()->id ?? 1;
$impression->public_hash = 'test123test123test123test123te';
$impression->click_target = 'verylongtarget'; // 14 chars
$impression->save();

$stored = DB::table('badge_impressions')->where('id', $impression->id)->first();
echo "Stored click_target: '" . $stored->click_target . "' (length: " . strlen($stored->click_target) . ")\n";

// Cleanup
$impression->delete();
