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

// Test 1: Generate PDF Report
echo "Test 1: Generate PDF Report\n";
$reportData = [
    'name' => 'Smoke Test PDF Report',
    'type' => 'performance',
    'format' => 'pdf',
    'dateRange' => [
        'start' => now()->subDays(7)->format('Y-m-d'),
        'end' => now()->format('Y-m-d'),
    ],
];

try {
    $controller = new \App\Http\Controllers\Api\V1\ReportController();
    $request = new \Illuminate\Http\Request();
    $request->setMethod('POST');
    $request->replace($reportData);
    
    // Mock authenticated user
    \Illuminate\Support\Facades\Auth::login($user);
    
    $response = $controller->generate($request, $site->id);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 202 && isset($responseData['reportId'])) {
        echo "  ✓ PASS: PDF report created (ID: {$responseData['reportId']})\n";
        echo "    Status: {$responseData['status']}\n";
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
    'type' => 'rankings',
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
    
    $response = $controller->generate($request, $site->id);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 202 && isset($responseData['reportId'])) {
        echo "  ✓ PASS: CSV report created (ID: {$responseData['reportId']})\n";
        echo "    Status: {$responseData['status']}\n";
    } else {
        echo "  ✗ FAIL: Unexpected response (HTTP {$response->getStatusCode()})\n";
    }
} catch (\Exception $e) {
    echo "  ✗ FAIL: Exception - " . $e->getMessage() . "\n";
}

// Test 3: Validation - Missing required fields
echo "\nTest 3: Validation - Missing Required Fields\n";
try {
    $request = new \Illuminate\Http\Request();
    $request->setMethod('POST');
    $request->replace(['name' => 'Incomplete Report']); // Missing type, format, dateRange
    
    $response = $controller->generate($request, $site->id);
    
    if ($response->getStatusCode() === 422) {
        echo "  ✓ PASS: Validation error returned (422)\n";
    } else {
        echo "  ✗ FAIL: Expected 422, got {$response->getStatusCode()}\n";
    }
} catch (\Exception $e) {
    echo "  ✗ FAIL: Exception - " . $e->getMessage() . "\n";
}

// Test 4: Verify reports in database
echo "\nTest 4: Verify Reports in Database\n";
$reports = \App\Models\Report::where('site_id', $site->id)->count();
echo "  Reports found: {$reports}\n";
if ($reports >= 2) {
    echo "  ✓ PASS: Reports created successfully\n";
} else {
    echo "  ✗ FAIL: Expected at least 2 reports\n";
}

// Test 5: Report file exists
echo "\nTest 5: Verify Report Files Exist\n";
$reportsWithFiles = \App\Models\Report::where('site_id', $site->id)
    ->whereNotNull('file_path')
    ->where('status', 'completed')
    ->count();
echo "  Reports with files: {$reportsWithFiles}\n";
if ($reportsWithFiles >= 1) {
    echo "  ✓ PASS: Report files generated\n";
} else {
    echo "  ⚠ WARNING: No completed reports with files (may still be processing)\n";
}

echo "\n=== Smoke Test Complete ===\n";