<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\AI\Providers\StaticFallbackProvider;

$provider = new StaticFallbackProvider();

// Test case: str_repeat('a', 50)
$context = json_encode(['indicator' => 'title', 'title' => str_repeat('a', 50)]);
$response = $provider->generate($context);
$result = json_decode($response->content, true);
echo "50 chars: score=" . $result['score'] . "\n";
print_r($result['issues']);

echo "\n";

// Test case: str_repeat('a', 55)
$context = json_encode(['indicator' => 'title', 'title' => str_repeat('a', 55)]);
$response = $provider->generate($context);
$result = json_decode($response->content, true);
echo "55 chars: score=" . $result['score'] . "\n";
print_r($result['issues']);

echo "\n";

// Test case: str_repeat('a', 70)
$context = json_encode(['indicator' => 'title', 'title' => str_repeat('a', 70)]);
$response = $provider->generate($context);
$result = json_decode($response->content, true);
echo "70 chars: score=" . $result['score'] . "\n";
print_r($result['issues']);

echo "\n";

// Test case: str_repeat('a', 100)
$context = json_encode(['indicator' => 'title', 'title' => str_repeat('a', 100)]);
$response = $provider->generate($context);
$result = json_decode($response->content, true);
echo "100 chars: score=" . $result['score'] . "\n";
print_r($result['issues']);
