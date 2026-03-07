<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== AIQueryEngine Live API Test ===\n\n";

try {
    $engine = app(\App\Services\AIVisibility\AIQueryEngine::class);
    
    // Test a real query with a simple SEO-related question
    echo "Testing real API call to OpenRouter...\n";
    
    $result = $engine->query('chatgpt', 'What are the best SEO tools for small businesses? List 3-5 tools.');
    
    echo "✓ Query completed\n";
    echo "  - Success: " . ($result->success ? 'true' : 'false') . "\n";
    echo "  - Platform: " . $result->platform . "\n";
    echo "  - Model: " . $result->model . "\n";
    echo "  - Tokens Used: " . $result->tokensUsed . "\n";
    echo "  - Latency: " . $result->latencyMs . "ms\n";
    
    if ($result->success) {
        echo "  - Response Preview: " . substr($result->responseText, 0, 200) . "...\n";
        
        // Test parseMentions with the response
        $brands = ['Ahrefs', 'SEMrush', 'Moz', 'Google', 'Yoast'];
        $mentions = $engine->parseMentions($result->responseText, $brands);
        echo "\n✓ Brand mentions found: " . count($mentions) . "\n";
        foreach (array_slice($mentions, 0, 5) as $mention) {
            echo "  - {$mention['brand']} at position {$mention['position']}\n";
        }
        
        // Test sentiment analysis on the response
        $sentiment = $engine->analyzeSentiment($result->responseText);
        echo "\n✓ Overall sentiment: $sentiment\n";
    } else {
        echo "  - Error: " . $result->errorMessage . "\n";
    }
    
    // Test cache by calling the same query again
    echo "\nTesting cache (second call should be cached)...\n";
    $startTime = microtime(true);
    $cachedResult = $engine->query('chatgpt', 'What are the best SEO tools for small businesses? List 3-5 tools.');
    $cacheTime = (microtime(true) - $startTime) * 1000;
    
    echo "✓ Second query completed in " . round($cacheTime, 2) . "ms (should be < 100ms if cached)\n";
    
    if ($cacheTime < 100) {
        echo "✓ Cache appears to be working (fast response time)\n";
    } else {
        echo "! Warning: Response time suggests cache may not be working\n";
    }
    
    echo "\n=== Live API Test Complete ===\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
