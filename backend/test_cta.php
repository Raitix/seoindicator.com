<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\AI\Providers\StaticFallbackProvider;

$provider = new StaticFallbackProvider();
$reflection = new ReflectionClass($provider);
$method = $reflection->getMethod('countCTAs');
$method->setAccessible(true);

$text = 'Buy now! Shop today! Order here! Get it now!';
echo "Text: $text\n";
echo "CTA count: " . $method->invoke($provider, $text) . "\n";

$text2 = 'Best widgets at great prices. Shop our selection today!';
echo "\nText: $text2\n";
echo "CTA count: " . $method->invoke($provider, $text2) . "\n";
