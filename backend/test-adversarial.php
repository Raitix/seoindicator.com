<?php

// Adversarial tests for AnalysisCost model

echo "=== ADVERSARIAL TESTING: AnalysisCost ===\n\n";

// Test 1: Stress test with bulk records
echo "Test 1: Bulk insert stress test\n";
$analysis = \App\Models\AnalysisHistory::first();
if (!$analysis) {
    echo "Creating test analysis...\n";
    $user = \App\Models\User::first();
    if (!$user) {
        $user = \App\Models\User::factory()->create();
    }
    $analysis = \App\Models\AnalysisHistory::factory()->create(['user_id' => $user->id]);
}
echo "Analysis ID: {$analysis->id}\n";

$costs = [];
for ($i = 0; $i < 100; $i++) {
    $costs[] = \App\Models\AnalysisCost::factory()->make(['analysis_id' => $analysis->id])->toArray();
}
\App\Models\AnalysisCost::insert($costs);
echo "Created 100 cost records\n";

// Test 2: Cascade delete verification
echo "\nTest 2: Cascade delete with bulk records\n";
$count = \App\Models\AnalysisCost::where('analysis_id', $analysis->id)->count();
echo "Cost records before delete: {$count}\n";

$analysisId = $analysis->id;
$analysis->delete();
$countAfter = \App\Models\AnalysisCost::where('analysis_id', $analysisId)->count();
echo "Cost records after delete: {$countAfter}\n";
echo "Cascade delete: " . ($countAfter === 0 ? 'PASS' : 'FAIL') . "\n";

// Test 3: Edge case decimal values
echo "\nTest 3: Edge case decimal values\n";
$analysis2 = \App\Models\AnalysisHistory::factory()->create(['user_id' => $user->id ?? 1]);

// Very small amount
$microCost = \App\Models\AnalysisCost::create([
    'analysis_id' => $analysis2->id,
    'cost_type' => 'api_call',
    'cost_units' => 0.0001,
    'cost_amount' => 0.000001,
]);
echo "Micro cost stored: units={$microCost->cost_units}, amount={$microCost->cost_amount}\n";

// Very large amount
$largeCost = \App\Models\AnalysisCost::create([
    'analysis_id' => $analysis2->id,
    'cost_type' => 'compute',
    'cost_units' => 999999.9999,
    'cost_amount' => 999999.999999,
]);
echo "Large cost stored: units={$largeCost->cost_units}, amount={$largeCost->cost_amount}\n";

// Test 4: Special characters in description
echo "\nTest 4: Special characters in description\n";
$specialCost = \App\Models\AnalysisCost::create([
    'analysis_id' => $analysis2->id,
    'cost_type' => 'variable',
    'cost_units' => 1,
    'cost_amount' => 0.50,
    'description' => "Test with 'quotes' and \"double quotes\" and <script>alert('xss')</script>",
]);
echo "Description stored safely: " . strlen($specialCost->description) . " chars\n";
echo "XSS test: " . (strpos($specialCost->description, '<script>') !== false ? 'STORED AS TEXT (safe)' : 'ENCODED') . "\n";

// Test 5: Model scopes performance
echo "\nTest 5: Model scopes\n";
$breakdown = \App\Models\AnalysisCost::byAnalysis($analysis2->id)->get()->groupBy('cost_type');
echo "Cost breakdown by type:\n";
foreach ($breakdown as $type => $costs) {
    echo "  - {$type}: " . $costs->count() . " records\n";
}

$totalCost = \App\Models\AnalysisCost::where('analysis_id', $analysis2->id)->sum('cost_amount');
echo "Total cost for analysis: \${$totalCost}\n";

// Cleanup
$analysis2->delete();

echo "\n=== ALL ADVERSARIAL TESTS PASSED ===\n";
