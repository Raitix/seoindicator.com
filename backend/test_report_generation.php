<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Production Smoke Test: Report Generation Endpoint ===\n\n";

// Get test data
$user = \App\Models\User::first();
$site = \App\Models\Site::first();

if (!$user || !$site) {
    echo "ERROR: Missing test data (user or site)\n";
    exit(1);
}

echo "Testing with Site ID: {$site->id}, User ID: {$user->id}\n\n";

// Mock authenticated user
\Illuminate\Support\Facades\Auth::login($user);

// Resolve controller from service container
$controller = app(\App\Http\Controllers\Api\V1\ReportController::class);

// Test 1: Generate PDF Report
echo "Test 1: Generate PDF Report\n";
$reportData = [
    'name' => 'Smoke Test PDF Report',
    'type' => 'daily',
    'format' => 'pdf',
    'dateRange' => [
        'start' => now()->subDays(7)->format('Y-m-d'),
        'end' => now()->format('Y-m-d'),
    ],
];

try {
    $request = new \Illuminate\Http\Request();
    $request->setMethod('POST');
    $request->replace($reportData);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    $response = $controller->generate($request, $site->id);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 201 && isset($responseData['data']['reportId'])) {
        echo "  ✓ PASS: PDF report created (ID: {$responseData['data']['reportId']})\n";
        echo "    Status: {$responseData['data']['status']}\n";
    } else {
        echo "  ✗ FAIL: Unexpected response (HTTP {$response->getStatusCode()})\n";
        echo "    " . json_encode($responseData) . "\n";
    }
} catch (\Exception $e) {
    echo "  ✗ FAIL: Exception - " . $e->getMessage() . "\n";
}

// Test 2: Generate CSV Report
echo "\nTest 2: Generate CSV Report\n";
$csvData = [
    'name' => 'Smoke Test CSV Report',
    'type' => 'weekly',
    'format' => 'csv',
    'dateRange' => [
        'start' => now()->subDays(30)->format('Y-m-d'),
        'end' => now()->format('Y-m-d'),
    ],
];

try {
    $request = new \Illuminate\Http\Request();
    $request->setMethod('POST');
    $request->replace($csvData);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    $response = $controller->generate($request, $site->id);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 201 && isset($responseData['data']['reportId'])) {
        echo "  ✓ PASS: CSV report created (ID: {$responseData['data']['reportId']})\n";
        echo "    Status: {$responseData['data']['status']}\n";
    } else {
        echo "  ✗ FAIL: Unexpected response (HTTP {$response->getStatusCode()})\n";
    }
} catch (\Exception $e) {
    echo "  ✗ FAIL: Exception - " . $e->getMessage() . "\n";
}

// Note: Additional tests omitted for brevity.

echo "\n=== Smoke Test Complete ===\n";