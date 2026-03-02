<?php

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\CreditService;

$user = User::first();
if (!$user) {
    echo "No users found\n";
    exit(1);
}

echo "Testing CreditService with user ID: " . $user->id . "\n";

$service = app(CreditService::class);

// Test getBalance
$balance = $service->getBalance($user);
echo "Initial balance: " . $balance . "\n";

// Test addCredits
$transaction = $service->addCredits($user, 10.00, 'bonus', ['reason' => 'prod_test']);
echo "Added 10 credits, transaction ID: " . $transaction->id . "\n";
echo "New balance: " . $service->getBalance($user) . "\n";

// Test hasSufficientBalance
$canAfford = $service->hasSufficientBalance($user, 5.00);
echo "Can afford 5 credits: " . ($canAfford ? 'YES' : 'NO') . "\n";

// Test deductCredits
$service->deductCredits($user, 3.00, 'test', 1);
echo "Deducted 3 credits, new balance: " . $service->getBalance($user) . "\n";

// Test getTransactionHistory
$history = $service->getTransactionHistory($user, 5);
echo "Transaction history count: " . $history->count() . "\n";

// Test refundCredits
$service->refundCredits($user, 2.00, null, 'Production test refund');
echo "Refunded 2 credits, final balance: " . $service->getBalance($user) . "\n";

echo "\n=== ALL CREDITSERVICE TESTS PASSED ===\n";
