<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Run migrations
\Illuminate\Support\Facades\Artisan::call('migrate');

echo "Testing LocalSEOAlertService...\n";

// Create test data
$user = \App\Models\User::factory()->create();
$site = \App\Models\Site::factory()->create();
$location = \App\Models\BusinessLocation::create([
    'user_id' => $user->id,
    'site_id' => $site->id,
    'name' => 'Test Business',
    'gbp_place_id' => 'accounts/123/locations/456',
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'address' => '123 Main St, New York, NY 10001',
    'gbp_connected' => true,
]);

echo "Location created: {$location->id}\n";
echo "Location user_id: {$location->user_id}\n";
echo "Location gbp_place_id: " . ($location->gbp_place_id ?? 'null') . "\n";
echo "Location gbp_connected: " . ($location->gbp_connected ? 'true' : 'false') . "\n";

// Test alert creation directly
$alert = \App\Models\LocalSEOAlert::create([
    'user_id' => $user->id,
    'location_id' => $location->id,
    'alert_type' => 'ranking_improved',
    'severity' => 'info',
    'title' => 'Test Alert',
    'message' => 'Test message',
    'created_at' => now(),
]);

echo "Alert created: {$alert->id}\n";
echo "Alerts count: " . \App\Models\LocalSEOAlert::count() . "\n";
