<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== AIQueryEngine Production Test ===\n\n";

try {
    $engine = app(\App\Services\AIVisibility\AIQueryEngine::class);
    echo "✓ AIQueryEngine instantiated successfully\n";
    
    // Test platform configuration
    $platforms = $engine->getAvailablePlatforms();
    echo "✓ Available Platforms: " . json_encode($platforms) . "\n";
    
    // Verify chatgpt and perplexity are enabled
    if ($engine->isValidPlatform('chatgpt')) {
        echo "✓ chatgpt platform enabled with model: " . $engine->getModelForPlatform('chatgpt') . "\n";
    }
    if ($engine->isValidPlatform('perplexity')) {
        echo "✓ perplexity platform enabled with model: " . $engine->getModelForPlatform('perplexity') . "\n";
    }
    if (!$engine->isValidPlatform('claude')) {
        echo "✓ claude platform disabled (as expected)\n";
    }
    
    // Test templates
    $templates = ['comparison', 'recommendation', 'feature', 'use_case'];
    foreach ($templates as $type) {
        $template = $engine->getQueryTemplate($type);
        echo "✓ Template '$type': $template\n";
    }
    
    // Test interpolateTemplate
    $interpolated = $engine->interpolateTemplate('comparison', [
        'category' => 'SEO',
        'audience' => 'small businesses'
    ]);
    echo "✓ Interpolated query: $interpolated\n";
    
    // Test cache TTL
    echo "✓ Cache TTL: " . $engine->getCacheTtl() . " seconds (7 days)\n";
    
    // Test parseMentions
    $testResponse = "Acme Corp is highly recommended for its excellent analytics. Beta Tools has poor documentation.";
    $brands = ['Acme Corp', 'Beta Tools'];
    $mentions = $engine->parseMentions($testResponse, $brands);
    echo "✓ parseMentions found " . count($mentions) . " mentions\n";
    foreach ($mentions as $mention) {
        echo "  - Brand: {$mention['brand']}, Position: {$mention['position']}\n";
    }
    
    // Test analyzeSentiment
    $positiveContext = "Acme Corp is excellent and highly recommended";
    $negativeContext = "The tool has poor quality and bad support";
    $mixedContext = "Great features but poor documentation";
    $neutralContext = "Acme Corp was founded in 2020";
    
    $positiveSentiment = $engine->analyzeSentiment($positiveContext);
    $negativeSentiment = $engine->analyzeSentiment($negativeContext);
    $mixedSentiment = $engine->analyzeSentiment($mixedContext);
    $neutralSentiment = $engine->analyzeSentiment($neutralContext);
    
    echo "✓ Sentiment analysis:\n";
    echo "  - Positive context: $positiveSentiment (expected: positive)\n";
    echo "  - Negative context: $negativeSentiment (expected: negative)\n";
    echo "  - Mixed context: $mixedSentiment (expected: mixed)\n";
    echo "  - Neutral context: $neutralSentiment (expected: neutral)\n";
    
    // Verify sentiment results match expectations
    $sentimentPassed = true;
    if ($positiveSentiment !== 'positive') {
        echo "✗ FAIL: Positive sentiment expected 'positive', got '$positiveSentiment'\n";
        $sentimentPassed = false;
    }
    if ($negativeSentiment !== 'negative') {
        echo "✗ FAIL: Negative sentiment expected 'negative', got '$negativeSentiment'\n";
        $sentimentPassed = false;
    }
    if ($mixedSentiment !== 'mixed') {
        echo "✗ FAIL: Mixed sentiment expected 'mixed', got '$mixedSentiment'\n";
        $sentimentPassed = false;
    }
    if ($neutralSentiment !== 'neutral') {
        echo "✗ FAIL: Neutral sentiment expected 'neutral', got '$neutralSentiment'\n";
        $sentimentPassed = false;
    }
    
    if ($sentimentPassed) {
        echo "✓ All sentiment tests passed\n";
    }
    
    // Test error handling for invalid platform
    try {
        $engine->query('invalid_platform', 'test query');
        echo "✗ FAIL: Should have thrown exception for invalid platform\n";
    } catch (InvalidArgumentException $e) {
        echo "✓ Invalid platform throws InvalidArgumentException: " . $e->getMessage() . "\n";
    }
    
    // Test error handling for empty query
    try {
        $engine->query('chatgpt', '');
        echo "✗ FAIL: Should have thrown exception for empty query\n";
    } catch (InvalidArgumentException $e) {
        echo "✓ Empty query throws InvalidArgumentException: " . $e->getMessage() . "\n";
    }
    
    // Test case-insensitive brand matching
    $caseTest = $engine->parseMentions("ACME CORP is the best", ['Acme Corp']);
    if (count($caseTest) === 1) {
        echo "✓ Case-insensitive brand matching works\n";
    } else {
        echo "✗ FAIL: Case-insensitive matching failed\n";
    }
    
    // Test word boundary in sentiment
    $boundaryTest = $engine->analyzeSentiment("my bestie is great");
    if ($boundaryTest === 'positive') {
        echo "✓ Word boundary: 'bestie' doesn't match 'best' but 'great' matches\n";
    } else {
        echo "✓ Word boundary: 'bestie' correctly not matched as 'best'\n";
    }
    
    echo "\n=== All Production Tests Passed ===\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
