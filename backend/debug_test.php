<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\AI\Providers\StaticFallbackProvider;

$provider = new StaticFallbackProvider();

$context = json_encode([
    'indicator' => 'meta_description',
    'description' => 'Premium widgets starting at $9.99 - Shop now!'
]);

$response = $provider->generate($context);
$result = json_decode($response->content, true);

echo "Score: " . $result['score'] . "\n";
echo "Issues: " . json_encode($result['issues'], JSON_PRETTY_PRINT) . "\n";
echo "CTA count check with word boundaries:\n";

$description = 'Premium widgets starting at $9.99 - Shop now!';
$ctaPatterns = ['buy', 'shop', 'order', 'get', 'find', 'discover', 'learn', 'try', 'start', 'call', 'visit', 'download', 'sign up', 'contact', 'book', 'save', 'free'];
$ctaCount = 0;
foreach ($ctaPatterns as $cta) {
    if (preg_match('/\b' . preg_quote($cta, '/') . '\b/i', $description)) {
        echo "  - Found CTA: '$cta'\n";
        $ctaCount++;
    }
}
echo "Total CTA count: $ctaCount\n";

