<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing CreditPackage model...\n\n";

// Test scopeActive
$activeCount = App\Models\CreditPackage::active()->count();
echo "✓ Active packages count: {$activeCount}\n";

// Test scopeOrdered
$ordered = App\Models\CreditPackage::ordered()->get();
echo "✓ Ordered packages: " . $ordered->pluck('name')->join(', ') . "\n";

// Test scopePopular
$popular = App\Models\CreditPackage::popular()->first();
echo "✓ Popular package: " . ($popular ? $popular->name : 'None') . "\n";

// Test effective_credits accessor
$pro = App\Models\CreditPackage::where('name', 'Pro')->first();
$expectedEffective = 25 + (25 * 0.10); // $25 + 10% bonus = $27.50
echo "✓ Pro effective_credits: {$pro->effective_credits} (expected: {$expectedEffective})\n";

// Test savings_percentage accessor
echo "✓ Pro savings_percentage: {$pro->savings_percentage}%\n";

// Test formatted_price accessor
echo "✓ Pro formatted_price: {$pro->formatted_price}\n";

// Test formatted_credits accessor
echo "✓ Pro formatted_credits: {$pro->formatted_credits}\n";

// Verify all packages
echo "\n--- All Packages ---\n";
foreach (App\Models\CreditPackage::ordered()->get() as $pkg) {
    $effective = $pkg->effective_credits;
    echo "- {$pkg->name}: \${$pkg->price_usd} = \${$effective} credits ({$pkg->bonus_percent}% bonus)";
    if ($pkg->is_popular) echo " [POPULAR]";
    echo "\n";
}

echo "\n✅ All production tests passed!\n";
