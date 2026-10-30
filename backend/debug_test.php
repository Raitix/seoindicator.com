<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\AI\Providers\StaticFallbackProvider;

$provider = new StaticFallbackProvider();

// Test from failing test
$desc = 'Discover our premium widgets that deliver exceptional quality and value. Shop now and enjoy free shipping on all orders over $50.';
echo "Description: '$desc'\n";
echo "Length: " . mb_strlen($desc) . " chars\n\n";

// Check CTA patterns
$ctaPatterns = ['buy', 'shop', 'order', 'get', 'find', 'discover', 'learn', 'try', 'start', 'call', 'visit', 'download', 'sign up', 'contact', 'book', 'save', 'free'];
echo "CTA patterns found:\n";
foreach ($ctaPatterns as $cta) {
    if (preg_match('/\b' . preg_quote($cta, '/') . '\b/i', $desc)) {
        echo "  - '$cta'\n";
    }
}

$context = json_encode([
    'indicator' => 'meta_description',
    'description' => $desc
]);
$response = $provider->generate($context);
$result = json_decode($response->content, true);
echo "\nScore: " . $result['score'] . "\n";
echo "Issues: " . json_encode($result['issues']) . "\n";




