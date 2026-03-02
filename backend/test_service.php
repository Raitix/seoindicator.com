<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Service instantiation test:\n";

try {
    $service = app(\App\Services\SEO\BacklinkSuggestionService::class);
    echo "✓ BacklinkSuggestionService instantiated successfully\n";
    echo "  Class: " . get_class($service) . "\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}

try {
    $scorer = app(\App\Services\SEO\BacklinkProfileScorer::class);
    echo "✓ BacklinkProfileScorer instantiated successfully\n";
    echo "  Class: " . get_class($scorer) . "\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
}

echo "\nProduction verification complete.\n";
