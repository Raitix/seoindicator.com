<?php

require __DIR__ . '/backend/vendor/autoload.php';

$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Service Container Integration Test ===\n\n";

// Test 1: Provider binding
$provider = app(\App\Services\AI\Contracts\AIProviderInterface::class);
echo "1. Provider class: " . get_class($provider) . "\n";
echo "   Expected: App\Services\AI\Providers\StaticFallbackProvider\n";
echo "   Match: " . (get_class($provider) === 'App\Services\AI\Providers\StaticFallbackProvider' ? 'YES' : 'NO') . "\n\n";

// Test 2: Provider methods
echo "2. Provider name: " . $provider->getName() . "\n";
echo "   Expected: static_fallback\n";
echo "   Match: " . ($provider->getName() === 'static_fallback' ? 'YES' : 'NO') . "\n\n";

echo "3. Is available: " . ($provider->isAvailable() ? 'true' : 'false') . "\n";
echo "   Expected: true\n";
echo "   Match: " . ($provider->isAvailable() ? 'YES' : 'NO') . "\n\n";

// Test 4: Service integration
$service = app(\App\Services\AI\AISuggestionService::class);
echo "4. Service class: " . get_class($service) . "\n";
echo "   Expected: App\Services\AI\AISuggestionService\n";
echo "   Match: " . (get_class($service) === 'App\Services\AI\AISuggestionService' ? 'YES' : 'NO') . "\n\n";

// Test 5: End-to-end suggestion generation
echo "5. Testing suggestion generation...\n";
$result = $service->getSuggestions('title', ['title' => 'Test Page Title', 'keyword' => 'test']);
echo "   Provider: " . $result->provider . " (expected: static_fallback)\n";
echo "   Match: " . ($result->provider === 'static_fallback' ? 'YES' : 'NO') . "\n";
echo "   Score: " . $result->score . "\n";
echo "   Issues: " . count($result->issues) . "\n";
echo "   Suggestions: " . count($result->suggestions) . "\n";
echo "   Best practices: " . count($result->bestPractices) . "\n\n";

// Test 6: Different indicator
echo "6. Testing meta_description indicator...\n";
$result2 = $service->getSuggestions('meta_description', ['meta_description' => 'This is a test meta description for SEO purposes.', 'keyword' => 'test']);
echo "   Provider: " . $result2->provider . "\n";
echo "   Score: " . $result2->score . "\n";
echo "   Match: " . ($result2->provider === 'static_fallback' ? 'YES' : 'NO') . "\n\n";

echo "=== Integration Test Complete ===\n";
