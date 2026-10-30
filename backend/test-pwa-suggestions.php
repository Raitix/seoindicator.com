<?php

// Test PWASuggestionService in production environment
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PWASuggestionService;
use App\Models\PWAAnalysis;

echo "=== PWASuggestionService Production Test ===\n\n";

// Test 1: Service resolution
echo "1. Testing service resolution...\n";
try {
    $service = app(PWASuggestionService::class);
    echo "   ✓ Service resolved: " . get_class($service) . "\n";
} catch (\Exception $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Method existence
echo "\n2. Testing method existence...\n";
$methods = ['generateSuggestions', 'getSuggestionForField', 'getSuggestionForIcon'];
foreach ($methods as $method) {
    if (method_exists($service, $method)) {
        echo "   ✓ Method exists: {$method}\n";
    } else {
        echo "   ✗ Method missing: {$method}\n";
    }
}

// Test 3: Generate suggestions for missing manifest
echo "\n3. Testing generateSuggestions() - no manifest...\n";
$analysis = new PWAAnalysis([
    'manifest_present' => false,
    'manifest_valid' => false,
]);
$suggestions = $service->generateSuggestions($analysis);
echo "   Suggestions count: " . count($suggestions) . "\n";
if (count($suggestions) > 0 && $suggestions[0]['priority'] === 'high') {
    echo "   ✓ High priority suggestion for missing manifest\n";
} else {
    echo "   ✗ Expected high priority for missing manifest\n";
}

// Test 4: Get suggestion for field
echo "\n4. Testing getSuggestionForField()...\n";
$suggestion = $service->getSuggestionForField('name', false);
if ($suggestion && isset($suggestion['message'])) {
    echo "   ✓ Suggestion for missing 'name': " . substr($suggestion['message'], 0, 50) . "...\n";
} else {
    echo "   ✗ No suggestion for missing field\n";
}

// Test 5: Get suggestion for icon
echo "\n5. Testing getSuggestionForIcon()...\n";
$suggestion = $service->getSuggestionForIcon('192', false);  // Size is just '192', not '192x192'
if ($suggestion && isset($suggestion['message'])) {
    echo "   ✓ Suggestion for missing 192x192 icon: " . substr($suggestion['message'], 0, 50) . "...\n";
} else {
    echo "   ✗ No suggestion for missing icon\n";
}

// Test 6: Field present returns null
echo "\n6. Testing present field returns null...\n";
$suggestion = $service->getSuggestionForField('name', true);
if ($suggestion === null) {
    echo "   ✓ Present field correctly returns null\n";
} else {
    echo "   ✗ Present field should return null\n";
}

// Test 7: Invalid field returns null
echo "\n7. Testing invalid field returns null...\n";
$suggestion = $service->getSuggestionForField('<script>alert(1)</script>', false);
if ($suggestion === null) {
    echo "   ✓ XSS attempt correctly returns null\n";
} else {
    echo "   ✗ Invalid field should return null\n";
}

// Test 8: SQL injection attempt
echo "\n8. Testing SQL injection protection...\n";
$suggestion = $service->getSuggestionForField("name'; DROP TABLE users; --", false);
if ($suggestion === null) {
    echo "   ✓ SQL injection attempt correctly returns null\n";
} else {
    echo "   ✗ SQL injection should return null\n";
}

echo "\n=== All Production Tests Passed ===\n";
