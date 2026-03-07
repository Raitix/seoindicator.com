<?php

// Debug script for MentionParser
require __DIR__ . '/vendor/autoload.php';

use App\Services\AIVisibility\MentionParser;

$parser = new MentionParser();

$text = 'SEOIndicator is in the top 10 list. I also recommend SEOIndicator vs Ahrefs comparison.';
$brands = ['SEOIndicator'];

echo "Text length: " . strlen($text) . "\n";
echo "Brand length: " . strlen('SEOIndicator') . "\n";

// Find positions manually
$pos1 = strpos($text, 'SEOIndicator');
$pos2 = strpos($text, 'SEOIndicator', $pos1 + 1);
echo "First occurrence at: $pos1\n";
echo "Second occurrence at: $pos2\n";

// Test the parser
$result = $parser->extractMentions($text, $brands);
echo "Parser found: " . count($result) . " mentions\n";
foreach ($result as $i => $mention) {
    echo "  [$i] Brand: {$mention['brand']}, Position: {$mention['position']}, Type: {$mention['type']}\n";
}
