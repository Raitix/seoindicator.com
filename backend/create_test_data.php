<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get existing data
$site = \App\Models\Site::first();
$user = \App\Models\User::first();

echo "Site ID: {$site->id}\n";
echo "User ID: {$user->id}\n";
echo "Keywords: " . \App\Models\Keyword::count() . "\n";

// Create some ranking data for the keywords
$keywords = \App\Models\Keyword::where('site_id', $site->id)->get();
foreach ($keywords as $keyword) {
    $existingRankings = \App\Models\Ranking::where('keyword_id', $keyword->id)->count();
    if ($existingRankings === 0) {
        for ($i = 0; $i < 7; $i++) {
            \App\Models\Ranking::create([
                'keyword_id' => $keyword->id,
                'position' => rand(1, 50),
                'url' => 'https://example.com/page',
                'checked_at' => now()->subDays($i)
            ]);
        }
    }
}
echo "Rankings: " . \App\Models\Ranking::count() . " records\n";

echo "\n=== Test Data Ready ===\n";