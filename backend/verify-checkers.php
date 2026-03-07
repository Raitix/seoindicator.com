<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Audit\Checkers\TitleTagChecker;
use App\Services\Audit\Checkers\H1Checker;
use App\Services\Audit\Checkers\MetaDescriptionChecker;
use App\Services\Audit\Checkers\ViewportChecker;
use App\Services\Audit\Checkers\ImageAltChecker;
use App\Services\Audit\Checkers\PerformanceChecker;
use App\Services\Audit\Contracts\CheckerInterface;
use App\Services\Audit\DTOs\CrawlResult;

echo "=== COMPREHENSIVE CHECKER VERIFICATION ===" . PHP_EOL . PHP_EOL;

// Verify all checkers implement the interface
$checkers = [
    'TitleTagChecker' => new TitleTagChecker(),
    'H1Checker' => new H1Checker(),
    'MetaDescriptionChecker' => new MetaDescriptionChecker(),
    'ViewportChecker' => new ViewportChecker(),
    'ImageAltChecker' => new ImageAltChecker(),
    'PerformanceChecker' => new PerformanceChecker(),
];

echo "--- Interface Implementation Check ---" . PHP_EOL;
foreach ($checkers as $name => $checker) {
    $implements = $checker instanceof CheckerInterface;
    echo "$name implements CheckerInterface: " . ($implements ? 'YES' : 'NO') . PHP_EOL;
}

// Create test result using proper DTO
function createResult(string $html, float $responseTimeMs = 500, int $statusCode = 200): CrawlResult {
    return CrawlResult::success(
        url: 'https://example.com/test',
        statusCode: $statusCode,
        responseTimeMs: $responseTimeMs,
        html: $html,
        links: [],
        metadata: [],
        headers: []
    );
}

echo PHP_EOL . "--- Functional Tests ---" . PHP_EOL;

// Test case 1: Perfect page
$perfectHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A perfectly optimized page with all the right meta tags for SEO.">
    <title>Perfect Page Title for SEO</title>
</head>
<body>
    <h1>Main Heading</h1>
    <p>Content here.</p>
    <img src="image.jpg" alt="Descriptive alt text">
</body>
</html>';

$result = createResult($perfectHtml, 500.0);
$issues = [];

foreach ($checkers as $name => $checker) {
    $finding = $checker->check($result);
    if ($finding) {
        $issues[$name] = $finding->severity . ' - ' . $finding->description;
    }
}

echo "Perfect page issues: " . (empty($issues) ? 'NONE (PASS)' : PHP_EOL . implode(PHP_EOL, array_map(fn($k, $v) => "  $k: $v", array_keys($issues), array_values($issues)))) . PHP_EOL;

// Test case 2: Page with multiple issues
$problematicHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <h1></h1>
    <h1>Second H1</h1>
    <img src="no-alt.jpg">
    <img src="empty-alt.jpg" alt="">
</body>
</html>';

$result = createResult($problematicHtml, 5500.0);
$issues = [];

foreach ($checkers as $name => $checker) {
    $finding = $checker->check($result);
    if ($finding) {
        $issues[$name] = $finding->severity;
    }
}

echo "Problematic page issues:" . PHP_EOL;
foreach ($issues as $name => $severity) {
    echo "  - $name: $severity" . PHP_EOL;
}

// Test case 3: Empty page
$emptyHtml = '';
$result = createResult($emptyHtml, 100.0);
$issues = [];

foreach ($checkers as $name => $checker) {
    $finding = $checker->check($result);
    if ($finding) {
        $issues[$name] = $finding->severity;
    }
}

echo PHP_EOL . "Empty page issues:" . PHP_EOL;
foreach ($issues as $name => $severity) {
    echo "  - $name: $severity" . PHP_EOL;
}

// Test case 4: Large page performance
$largeHtml = '<html><head><title>Test</title></head><body>';
for ($i = 0; $i < 5000; $i++) {
    $largeHtml .= '<p>Paragraph content</p>';
}
$largeHtml .= '</body></html>';
$largeHtml = str_repeat($largeHtml, 10); // Make it very large

$result = createResult($largeHtml, 3000.0);
$finding = $checkers['PerformanceChecker']->check($result);
echo PHP_EOL . "Large page (size check): " . ($finding ? 'Issue detected (' . $finding->issueType . ')' : 'No issue') . PHP_EOL;

// Test checker priorities
echo PHP_EOL . "--- Checker Priorities ---" . PHP_EOL;
$priorities = [];
foreach ($checkers as $name => $checker) {
    $priorities[$name] = $checker->getPriority();
}
arsort($priorities);
foreach ($priorities as $name => $priority) {
    echo "  $name: priority $priority" . PHP_EOL;
}

// Test issue types
echo PHP_EOL . "--- Issue Types ---" . PHP_EOL;
foreach ($checkers as $name => $checker) {
    echo "  $name: " . $checker->getIssueType() . PHP_EOL;
}

echo PHP_EOL . "=== ALL VERIFICATION TESTS COMPLETED ===" . PHP_EOL;
