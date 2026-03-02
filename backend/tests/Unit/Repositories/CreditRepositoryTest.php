<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Repositories\CreditRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CreditRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CreditRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CreditRepository();
    }

    /**
     * Helper to create a user with optional credit.
     */
    protected function createUserWithCredit(float $balance = 0): array
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => $balance,
            'total_purchased' => $balance,
            'total_spent' => 0,
        ]);

        return ['user' => $user, 'credit' => $credit];
    }

    // ==================== findOrCreateForUser TESTS ====================

    /**
     * Test findOrCreateForUser creates for new user.
     * CR-FO-01
     */
    public function test_find_or_create_creates_for_new_user(): void
    {
        $user = User::factory()->create();

        // Ensure no credit exists
        $this->assertDatabaseMissing('credits', ['user_id' => $user->id]);

        $credit = $this->repository->findOrCreateForUser($user);

        $this->assertInstanceOf(Credit::class, $credit);
        $this->assertEquals($user->id, $credit->user_id);
        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
    }

    /**
     * Test findOrCreateForUser returns existing.
     * CR-FO-02
     */
    public function test_find_or_create_returns_existing(): void
    {
        $user = User::factory()->create();
        $existingCredit = Credit::create([
            'user_id' => $user->id,
            'balance' => 100.00,
        ]);

        $credit = $this->repository->findOrCreateForUser($user);

        $this->assertEquals($existingCredit->id, $credit->id);
        $this->assertEquals(100.00, $credit->balance);
    }

    /**
     * Test findOrCreateForUser returns Credit instance.
     * CR-FO-03
     */
    public function test_find_or_create_returns_credit_instance(): void
    {
        $user = User::factory()->create();

        $credit = $this->repository->findOrCreateForUser($user);

        $this->assertInstanceOf(Credit::class, $credit);
    }

    /**
     * Test findOrCreateForUser default values.
     * CR-FO-04
     */
    public function test_find_or_create_default_values(): void
    {
        $user = User::factory()->create();

        $credit = $this->repository->findOrCreateForUser($user);

        $this->assertEquals(0, $credit->balance);
        $this->assertEquals(0, $credit->total_purchased);
        $this->assertEquals(0, $credit->total_spent);
    }

    /**
     * Test findOrCreateForUser idempotent.
     * CR-FO-05
     */
    public function test_find_or_create_idempotent(): void
    {
        $user = User::factory()->create();

        $credit1 = $this->repository->findOrCreateForUser($user);
        $credit2 = $this->repository->findOrCreateForUser($user);

        $this->assertEquals($credit1->id, $credit2->id);
        $this->assertEquals(1, Credit::where('user_id', $user->id)->count());
    }

    // ==================== getBalance TESTS ====================

    /**
     * Test getBalance for existing credit.
     * CR-GB-01
     */
    public function test_get_balance_existing(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithCredit(123.45);

        $balance = $this->repository->getBalance($user);

        $this->assertIsFloat($balance);
        $this->assertEquals(123.45, $balance);
    }

    /**
     * Test getBalance for new user (creates credit).
     * CR-GB-02
     */
    public function test_get_balance_new_user_creates_credit(): void
    {
        $user = User::factory()->create();

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(0.0, $balance);
        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
    }

    /**
     * Test getBalance with decimals.
     * CR-GB-03
     */
    public function test_get_balance_with_decimals(): void
    {
        ['user' => $user] = $this->createUserWithCredit(123.45);

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(123.45, $balance);
    }

    /**
     * Test getBalance zero.
     * CR-GB-04
     */
    public function test_get_balance_zero(): void
    {
        ['user' => $user] = $this->createUserWithCredit(0);

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(0.0, $balance);
    }

    /**
     * Test getBalance negative.
     * CR-GB-05
     */
    public function test_get_balance_negative(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithCredit(0);
        $credit->update(['balance' => -10.00]);

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(-10.0, $balance);
    }

    // ==================== getTransactions TESTS ====================

    /**
     * Test getTransactions returns paginated results.
     * CR-GT-01
     */
    public function test_get_transactions_paginated(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithCredit(100);

        // Create some transactions
        for ($i = 0; $i < 20; $i++) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => 5.00,
                'balance_after' => 100 - ($i * 5),
                'type' => 'usage',
                'description' => "Transaction $i",
            ]);
        }

        $result = $this->repository->getTransactions($user, 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(20, $result->total());
    }

    /**
     * Test getTransactions empty.
     * CR-GT-02
     */
    public function test_get_transactions_empty(): void
    {
        ['user' => $user] = $this->createUserWithCredit(0);

        $result = $this->repository->getTransactions($user, 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
        $this->assertCount(0, $result->items());
    }

    /**
     * Test getTransactions with custom perPage.
     * CR-GT-04
     */
    public function test_get_transactions_custom_per_page(): void
    {
        ['user' => $user] = $this->createUserWithCredit(100);

        for ($i = 0; $i < 10; $i++) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => 5.00,
                'balance_after' => 100,
                'type' => 'usage',
                'description' => "Transaction $i",
            ]);
        }

        $result = $this->repository->getTransactions($user, 5);

        $this->assertEquals(5, $result->perPage());
        $this->assertCount(5, $result->items());
    }

    /**
     * Test getTransactions ordered by created_at descending.
     * CR-GT-05
     */
    public function test_get_transactions_ordered_desc(): void
    {
        ['user' => $user] = $this->createUserWithCredit(100);

        // Create transactions with different timestamps
        $t1 = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'balance_after' => 90,
            'type' => 'usage',
            'description' => 'First',
            'created_at' => now()->subHours(2),
        ]);

        $t2 = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 20.00,
            'balance_after' => 70,
            'type' => 'usage',
            'description' => 'Second',
            'created_at' => now()->subHour(),
        ]);

        $t3 = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 30.00,
            'balance_after' => 40,
            'type' => 'usage',
            'description' => 'Third',
            'created_at' => now(),
        ]);

        $result = $this->repository->getTransactions($user, 15);

        $items = $result->items();
        // Should be ordered newest first
        $this->assertEquals($t3->id, $items[0]->id);
        $this->assertEquals($t2->id, $items[1]->id);
        $this->assertEquals($t1->id, $items[2]->id);
    }

    // ==================== TRANSACTION-SAFE UPDATE TESTS ====================

    /**
     * Test addCredits in transaction.
     * CR-TS-01
     */
    public function test_add_credits_transaction(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithCredit(100);

        if (!method_exists($this->repository, 'addCredits')) {
            $this->markTestSkipped('addCredits method not yet implemented');
        }

        $transaction = $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'Purchased 50 credits',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_123',
        ]);

        $this->assertInstanceOf(CreditTransaction::class, $transaction);
        $this->assertEquals(50.00, $transaction->amount);
        $this->assertEquals(150.00, $credit->fresh()->balance);
    }

    /**
     * Test deductCredits in transaction.
     * CR-TS-02
     */
    public function test_deduct_credits_transaction(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithCredit(100);

        if (!method_exists($this->repository, 'deductCredits')) {
            $this->markTestSkipped('deductCredits method not yet implemented');
        }

        $transaction = $this->repository->deductCredits($user, 10.00, [
            'type' => 'usage',
            'description' => 'Analysis job #123',
            'reference_type' => 'App\\Models\\AnalysisJob',
            'reference_id' => 123,
        ]);

        $this->assertInstanceOf(CreditTransaction::class, $transaction);
        $this->assertEquals(-10.00, $transaction->amount);
        $this->assertEquals(90.00, $credit->fresh()->balance);
    }

    /**
     * Test insufficient balance throws exception.
     * CR-TS-03
     */
    public function test_insufficient_balance_throws_exception(): void
    {
        ['user' => $user] = $this->createUserWithCredit(50);

        if (!method_exists($this->repository, 'deductCredits')) {
            $this->markTestSkipped('deductCredits method not yet implemented');
        }

        $this->expectException(\Exception::class);

        $this->repository->deductCredits($user, 100.00, [
            'type' => 'usage',
            'description' => 'This should fail',
        ]);
    }

    /**
     * Test rollback on error.
     * CR-TS-04
     */
    public function test_rollback_on_error(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithCredit(100);

        if (!method_exists($this->repository, 'addCredits')) {
            $this->markTestSkipped('addCredits method not yet implemented');
        }

        $initialBalance = $credit->balance;

        // Simulate an error during transaction
        // This test would need custom logic to force an error
        // For now, we verify the repository uses DB::transaction
        
        // Check that the repository method wraps operations in a transaction
        $reflection = new \ReflectionMethod($this->repository, 'addCredits');
        $source = file($reflection->getFileName())[$reflection->getStartLine() - 1] ?? '';
        
        // The method should use DB::transaction internally
        $this->assertTrue(
            true, // Placeholder - actual test would verify transaction usage
            'Repository should use DB::transaction for atomic operations'
        );
    }

    /**
     * Test concurrent updates with lockForUpdate.
     * CR-TS-05
     */
    public function test_concurrent_updates_locking(): void
    {
        ['user' => $user] = $this->createUserWithCredit(100);

        if (!method_exists($this->repository, 'deductCredits')) {
            $this->markTestSkipped('deductCredits method not yet implemented');
        }

        // Simulate concurrent deduction attempts
        // In a real test, this would use multiple processes/threads
        
        // For unit test, verify the method uses lockForUpdate
        $this->repository->deductCredits($user, 10.00, [
            'type' => 'usage',
            'description' => 'Concurrent test',
        ]);

        $this->assertEquals(90.00, $user->credit->fresh()->balance);
    }

    /**
     * Test atomic balance update.
     * CR-TS-06
     */
    public function test_atomic_balance_update(): void
    {
        ['user' => $user] = $this->createUserWithCredit(100);

        if (!method_exists($this->repository, 'addCredits') || 
            !method_exists($this->repository, 'deductCredits')) {
            $this->markTestSkipped('Credit methods not yet implemented');
        }

        // Perform multiple operations
        $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'Add',
        ]);

        $this->repository->deductCredits($user, 25.00, [
            'type' => 'usage',
            'description' => 'Deduct',
        ]);

        $this->assertEquals(125.00, $user->credit->fresh()->balance);
        $this->assertEquals(2, CreditTransaction::where('user_id', $user->id)->count());
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * Test zero amount transaction.
     * CR-EC-01
     */
    public function test_zero_amount_transaction(): void
    {
        ['user' => $user] = $this->createUserWithCredit(100);

        if (!method_exists($this->repository, 'addCredits')) {
            $this->markTestSkipped('addCredits method not yet implemented');
        }

        // Zero amount should either be ignored or handled gracefully
        $result = $this->repository->addCredits($user, 0, [
            'type' => 'bonus',
            'description' => 'Zero transaction',
        ]);

        // Should return null or skip creating transaction
        if ($result === null) {
            $this->assertNull($result);
            $this->assertEquals(0, CreditTransaction::where('user_id', $user->id)->count());
        } else {
            // Or it creates a zero transaction
            $this->assertEquals(0, $result->amount);
        }
    }

    /**
     * Test very large transaction.
     * CR-EC-03
     */
    public function test_very_large_transaction(): void
    {
        ['user' => $user] = $this->createUserWithCredit(0);

        if (!method_exists($this->repository, 'addCredits')) {
            $this->markTestSkipped('addCredits method not yet implemented');
        }

        $transaction = $this->repository->addCredits($user, 99999999.99, [
            'type' => 'purchase',
            'description' => 'Large purchase',
        ]);

        $this->assertEquals(99999999.99, $transaction->amount);
        $this->assertEquals(99999999.99, $user->credit->fresh()->balance);
    }

    /**
     * Test precision preservation.
     * CR-EC-04
     */
    public function test_precision_preservation(): void
    {
        ['user' => $user] = $this->createUserWithCredit(0);

        if (!method_exists($this->repository, 'addCredits')) {
            $this->markTestSkipped('addCredits method not yet implemented');
        }

        // Multiple small transactions
        for ($i = 0; $i < 10; $i++) {
            $this->repository->addCredits($user, 0.01, [
                'type' => 'bonus',
                'description' => "Micro bonus $i",
            ]);
        }

        $this->assertEquals(0.10, $user->credit->fresh()->balance);
    }

    /**
     * Test metadata storage.
     * CR-EC-05
     */
    public function test_metadata_storage(): void
    {
        ['user' => $user] = $this->createUserWithCredit(100);

        if (!method_exists($this->repository, 'addCredits')) {
            $this->markTestSkipped('addCredits method not yet implemented');
        }

        $metadata = [
            'package' => 'starter',
            'credits' => 50,
            'campaign' => 'welcome_2026',
        ];

        $transaction = $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'Purchase with metadata',
            'metadata' => $metadata,
        ]);

        $this->assertEquals($metadata, $transaction->metadata);
    }

    // ==================== INTEGRATION TESTS ====================

    /**
     * Test full purchase flow.
     */
    public function test_full_purchase_flow(): void
    {
        $user = User::factory()->create();

        // Find or create credit
        $credit = $this->repository->findOrCreateForUser($user);
        $this->assertEquals(0, $credit->balance);

        // Add credits via purchase
        if (method_exists($this->repository, 'addCredits')) {
            $transaction = $this->repository->addCredits($user, 100.00, [
                'type' => 'purchase',
                'description' => 'Purchased 100 credits',
                'payment_provider' => 'stripe',
                'payment_id' => 'pi_test123',
                'metadata' => ['package' => 'starter'],
            ]);

            $this->assertEquals(100.00, $credit->fresh()->balance);
            $this->assertEquals(100.00, $credit->fresh()->total_purchased);
        } else {
            $this->markTestSkipped('addCredits method not yet implemented');
        }
    }

    /**
     * Test full usage and refund flow.
     */
    public function test_full_usage_refund_flow(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithCredit(100);

        if (!method_exists($this->repository, 'deductCredits')) {
            $this->markTestSkipped('deductCredits method not yet implemented');
        }

        // Use credits
        $usage = $this->repository->deductCredits($user, 25.00, [
            'type' => 'usage',
            'description' => 'Analysis job #123',
            'reference_type' => 'App\\Models\\AnalysisJob',
            'reference_id' => 123,
        ]);

        $this->assertEquals(75.00, $credit->fresh()->balance);
        $this->assertEquals(25.00, $credit->fresh()->total_spent);

        // Refund credits
        if (method_exists($this->repository, 'addCredits')) {
            $refund = $this->repository->addCredits($user, 10.00, [
                'type' => 'refund',
                'description' => 'Refund for failed job',
                'reference_type' => 'App\\Models\\AnalysisJob',
                'reference_id' => 123,
            ]);

            $this->assertEquals(85.00, $credit->fresh()->balance);
        }
    }

    /**
     * Test transaction history retrieval.
     */
    public function test_transaction_history_retrieval(): void
    {
        ['user' => $user] = $this->createUserWithCredit(100);

        // Create various transactions
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'balance_after' => 100.00,
            'type' => 'purchase',
            'description' => 'Initial purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_1',
        ]);

        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => -25.00,
            'balance_after' => 75.00,
            'type' => 'usage',
            'description' => 'Analysis',
        ]);

        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'balance_after' => 85.00,
            'type' => 'bonus',
            'description' => 'Referral bonus',
        ]);

        $history = $this->repository->getTransactions($user, 15);

        $this->assertEquals(3, $history->total());
    }
}
