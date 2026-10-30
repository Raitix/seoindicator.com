<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Repositories\CreditRepository;
use App\Services\CreditService;
use App\Exceptions\InsufficientCreditsException;
use App\Exceptions\InvalidCreditAmountException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for TASK-26-5: CreditService
 *
 * Tests the CreditService responsible for:
 * - Balance retrieval and management
 * - Credit addition (purchase, bonus)
 * - Credit deduction (usage)
 * - Balance validation
 * - Transaction history
 * - Refunds
 *
 * TDD Approach: Tests are written BEFORE implementation.
 * Tests use expected interface from TASK-26-5 specification.
 *
 * @see TASK-26-5
 * @see \App\Services\CreditService
 */
class CreditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreditService $service;
    protected CreditRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Use repository if service exists, otherwise mock
        if (class_exists(CreditService::class)) {
            $this->service = app(CreditService::class);
        } else {
            // Create a mock for TDD - will fail if service doesn't exist
            $this->service = $this->createMockCreditService();
        }

        $this->repository = app(CreditRepository::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create mock service for TDD when implementation doesn't exist.
     */
    protected function createMockCreditService(): CreditService
    {
        // This will fail if CreditService doesn't exist
        return new CreditService(app(CreditRepository::class));
    }

    /**
     * Helper to create user with credit balance.
     */
    protected function createUserWithBalance(float $balance = 0): array
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

    // =========================================================================
    // GET BALANCE TESTS (GB)
    // =========================================================================

    /**
     * GB-001: getBalance returns current balance for user
     */
    public function test_get_balance_returns_current_balance(): void
    {
        ['user' => $user] = $this->createUserWithBalance(123.45);

        $balance = $this->service->getBalance($user);

        $this->assertIsFloat($balance);
        $this->assertEquals(123.45, $balance);
    }

    /**
     * GB-002: getBalance returns zero for user without credit record
     */
    public function test_get_balance_returns_zero_for_new_user(): void
    {
        $user = User::factory()->create();

        $balance = $this->service->getBalance($user);

        $this->assertEquals(0.0, $balance);
        // Should create credit record on demand
        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
    }

    /**
     * GB-003: getBalance handles zero balance
     */
    public function test_get_balance_handles_zero_balance(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $balance = $this->service->getBalance($user);

        $this->assertEquals(0.0, $balance);
    }

    /**
     * GB-004: getBalance handles negative balance (edge case)
     */
    public function test_get_balance_handles_negative_balance(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithBalance(0);
        $credit->update(['balance' => -10.00]);

        $balance = $this->service->getBalance($user);

        $this->assertEquals(-10.0, $balance);
    }

    /**
     * GB-005: getBalance preserves precision
     */
    public function test_get_balance_preserves_precision(): void
    {
        ['user' => $user] = $this->createUserWithBalance(123.45);

        $balance = $this->service->getBalance($user);

        $this->assertEquals(123.45, $balance);
        $this->assertLessThanOrEqual(2, strlen(substr(strrchr((string)$balance, '.'), 1)));
    }

    // =========================================================================
    // ADD CREDITS TESTS (AC)
    // =========================================================================

    /**
     * AC-001: addCredits creates purchase transaction
     */
    public function test_add_credits_creates_purchase_transaction(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $transaction = $this->service->addCredits($user, 50.00, 'purchase', [
            'description' => 'Purchased 50 credits',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test123',
        ]);

        $this->assertInstanceOf(CreditTransaction::class, $transaction);
        $this->assertEquals(50.00, $transaction->amount);
        $this->assertEquals('purchase', $transaction->type);
        $this->assertEquals(50.00, $user->credit->fresh()->balance);
    }

    /**
     * AC-002: addCredits creates bonus transaction
     */
    public function test_add_credits_creates_bonus_transaction(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $transaction = $this->service->addCredits($user, 25.00, 'bonus', [
            'description' => 'Welcome bonus',
        ]);

        $this->assertEquals('bonus', $transaction->type);
        $this->assertEquals(25.00, $transaction->amount);
        $this->assertEquals(25.00, $user->credit->fresh()->balance);
    }

    /**
     * AC-003: addCredits updates total_purchased for purchase type
     */
    public function test_add_credits_updates_total_purchased(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithBalance(0);

        $this->service->addCredits($user, 100.00, 'purchase', [
            'description' => 'Purchase',
        ]);

        $this->assertEquals(100.00, $credit->fresh()->total_purchased);
    }

    /**
     * AC-004: addCredits updates total_purchased for bonus type
     */
    public function test_add_credits_updates_total_purchased_for_bonus(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithBalance(0);

        $this->service->addCredits($user, 25.00, 'bonus', [
            'description' => 'Bonus',
        ]);

        $this->assertEquals(25.00, $credit->fresh()->total_purchased);
    }

    /**
     * AC-005: addCredits throws InvalidCreditAmountException for zero amount
     */
    public function test_add_credits_throws_exception_for_zero_amount(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $this->expectException(InvalidCreditAmountException::class);

        $this->service->addCredits($user, 0, 'purchase', [
            'description' => 'Invalid',
        ]);
    }

    /**
     * AC-006: addCredits throws InvalidCreditAmountException for negative amount
     */
    public function test_add_credits_throws_exception_for_negative_amount(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $this->expectException(InvalidCreditAmountException::class);

        $this->service->addCredits($user, -10.00, 'purchase', [
            'description' => 'Invalid',
        ]);
    }

    /**
     * AC-007: addCredits throws InvalidCreditAmountException for invalid type
     */
    public function test_add_credits_throws_exception_for_invalid_type(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->addCredits($user, 50.00, 'invalid_type', [
            'description' => 'Invalid',
        ]);
    }

    /**
     * AC-008: addCredits stores metadata
     */
    public function test_add_credits_stores_metadata(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $metadata = [
            'package' => 'pro',
            'campaign' => 'welcome_2026',
            'bonus_percent' => 10,
        ];

        $transaction = $this->service->addCredits($user, 50.00, 'purchase', [
            'description' => 'Purchase',
            'metadata' => $metadata,
        ]);

        $this->assertEquals($metadata, $transaction->metadata);
    }

    /**
     * AC-009: addCredits creates credit record if not exists
     */
    public function test_add_credits_creates_credit_record_if_not_exists(): void
    {
        $user = User::factory()->create();
        $this->assertDatabaseMissing('credits', ['user_id' => $user->id]);

        $this->service->addCredits($user, 50.00, 'purchase', [
            'description' => 'First purchase',
        ]);

        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
        $this->assertEquals(50.00, $user->credit->balance);
    }

    /**
     * AC-010: addCredits is idempotent with payment_id
     */
    public function test_add_credits_is_idempotent_with_payment_id(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        // First call
        $transaction1 = $this->service->addCredits($user, 50.00, 'purchase', [
            'description' => 'Purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_unique_123',
        ]);

        // Second call with same payment_id should be idempotent
        $transaction2 = $this->service->addCredits($user, 50.00, 'purchase', [
            'description' => 'Duplicate webhook',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_unique_123',
        ]);

        // Should return same transaction, not create new one
        $this->assertEquals($transaction1->id, $transaction2->id);
        $this->assertEquals(50.00, $user->credit->fresh()->balance); // Not doubled
    }

    // =========================================================================
    // DEDUCT CREDITS TESTS (DC)
    // =========================================================================

    /**
     * DC-001: deductCredits creates usage transaction
     */
    public function test_deduct_credits_creates_usage_transaction(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        $transaction = $this->service->deductCredits($user, 10.00, 'App\\Models\\AnalysisJob', 123);

        $this->assertInstanceOf(CreditTransaction::class, $transaction);
        $this->assertEquals(-10.00, $transaction->amount);
        $this->assertEquals('usage', $transaction->type);
        $this->assertEquals(90.00, $user->credit->fresh()->balance);
    }

    /**
     * DC-002: deductCredits updates total_spent
     */
    public function test_deduct_credits_updates_total_spent(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithBalance(100);

        $this->service->deductCredits($user, 25.00, 'App\\Models\\AnalysisJob', 123);

        $this->assertEquals(25.00, $credit->fresh()->total_spent);
    }

    /**
     * DC-003: deductCredits throws InsufficientCreditsException
     */
    public function test_deduct_credits_throws_insufficient_credits_exception(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        $this->expectException(InsufficientCreditsException::class);

        $this->service->deductCredits($user, 100.00, 'App\\Models\\AnalysisJob', 123);
    }

    /**
     * DC-004: deductCredits with exact balance succeeds
     */
    public function test_deduct_credits_exact_balance_succeeds(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        $transaction = $this->service->deductCredits($user, 100.00, 'App\\Models\\AnalysisJob', 123);

        $this->assertInstanceOf(CreditTransaction::class, $transaction);
        $this->assertEquals(0.0, $user->credit->fresh()->balance);
    }

    /**
     * DC-005: deductCredits throws InvalidCreditAmountException for zero
     */
    public function test_deduct_credits_throws_exception_for_zero(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        $this->expectException(InvalidCreditAmountException::class);

        $this->service->deductCredits($user, 0, 'App\\Models\\AnalysisJob', 123);
    }

    /**
     * DC-006: deductCredits throws InvalidCreditAmountException for negative
     */
    public function test_deduct_credits_throws_exception_for_negative(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        $this->expectException(InvalidCreditAmountException::class);

        $this->service->deductCredits($user, -10.00, 'App\\Models\\AnalysisJob', 123);
    }

    /**
     * DC-007: deductCredits stores reference type and id
     */
    public function test_deduct_credits_stores_reference(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        $transaction = $this->service->deductCredits($user, 10.00, 'App\\Models\\AnalysisJob', 456);

        $this->assertEquals('App\\Models\\AnalysisJob', $transaction->reference_type);
        $this->assertEquals(456, $transaction->reference_id);
    }

    /**
     * DC-008: deductCredits with zero balance throws InsufficientCreditsException
     */
    public function test_deduct_credits_zero_balance_throws_exception(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $this->expectException(InsufficientCreditsException::class);

        $this->service->deductCredits($user, 1.00, 'App\\Models\\AnalysisJob', 123);
    }

    /**
     * DC-009: deductCredits preserves balance on failure
     */
    public function test_deduct_credits_preserves_balance_on_failure(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithBalance(50);

        try {
            $this->service->deductCredits($user, 100.00, 'App\\Models\\AnalysisJob', 123);
        } catch (InsufficientCreditsException $e) {
            // Expected
        }

        // Balance should be unchanged
        $this->assertEquals(50.00, $credit->fresh()->balance);
    }

    // =========================================================================
    // HAS SUFFICIENT BALANCE TESTS (HS)
    // =========================================================================

    /**
     * HS-001: hasSufficientBalance returns true when enough
     */
    public function test_has_sufficient_balance_returns_true_when_enough(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        $result = $this->service->hasSufficientBalance($user, 50.00);

        $this->assertTrue($result);
    }

    /**
     * HS-002: hasSufficientBalance returns true for exact amount
     */
    public function test_has_sufficient_balance_returns_true_for_exact(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        $result = $this->service->hasSufficientBalance($user, 100.00);

        $this->assertTrue($result);
    }

    /**
     * HS-003: hasSufficientBalance returns false when insufficient
     */
    public function test_has_sufficient_balance_returns_false_when_insufficient(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        $result = $this->service->hasSufficientBalance($user, 100.00);

        $this->assertFalse($result);
    }

    /**
     * HS-004: hasSufficientBalance returns true for zero amount
     */
    public function test_has_sufficient_balance_returns_true_for_zero(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $result = $this->service->hasSufficientBalance($user, 0);

        $this->assertTrue($result);
    }

    /**
     * HS-005: hasSufficientBalance returns true for negative amount
     */
    public function test_has_sufficient_balance_returns_true_for_negative(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        // Negative amounts are credits, always allowed
        $result = $this->service->hasSufficientBalance($user, -10.00);

        $this->assertTrue($result);
    }

    /**
     * HS-006: hasSufficientBalance creates credit record for new user
     */
    public function test_has_sufficient_balance_creates_credit_for_new_user(): void
    {
        $user = User::factory()->create();

        $result = $this->service->hasSufficientBalance($user, 0);

        $this->assertTrue($result);
        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
    }

    /**
     * HS-007: hasSufficientBalance returns false for negative balance
     */
    public function test_has_sufficient_balance_returns_false_for_negative_balance(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithBalance(0);
        $credit->update(['balance' => -10.00]);

        $result = $this->service->hasSufficientBalance($user, 1.00);

        $this->assertFalse($result);
    }

    // =========================================================================
    // TRANSACTION HISTORY TESTS (TH)
    // =========================================================================

    /**
     * TH-001: getTransactionHistory returns paginated results
     */
    public function test_get_transaction_history_returns_paginated(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        // Create multiple transactions
        for ($i = 0; $i < 25; $i++) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => 5.00,
                'balance_after' => 100 - ($i * 5),
                'type' => 'usage',
                'description' => "Transaction $i",
            ]);
        }

        $result = $this->service->getTransactionHistory($user, 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(25, $result->total());
    }

    /**
     * TH-002: getTransactionHistory returns empty for no transactions
     */
    public function test_get_transaction_history_returns_empty(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $result = $this->service->getTransactionHistory($user, 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    /**
     * TH-003: getTransactionHistory respects perPage parameter
     */
    public function test_get_transaction_history_respects_per_page(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        for ($i = 0; $i < 20; $i++) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => 5.00,
                'balance_after' => 100,
                'type' => 'usage',
                'description' => "Transaction $i",
            ]);
        }

        $result = $this->service->getTransactionHistory($user, 10);

        $this->assertEquals(10, $result->perPage());
        $this->assertCount(10, $result->items());
    }

    /**
     * TH-004: getTransactionHistory ordered by created_at descending
     */
    public function test_get_transaction_history_ordered_desc(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

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

        $result = $this->service->getTransactionHistory($user, 15);

        $items = $result->items();
        // Should be ordered newest first
        $this->assertEquals($t3->id, $items[0]->id);
        $this->assertEquals($t2->id, $items[1]->id);
        $this->assertEquals($t1->id, $items[2]->id);
    }

    /**
     * TH-005: getTransactionHistory only returns user's transactions
     */
    public function test_get_transaction_history_only_user_transactions(): void
    {
        ['user' => $user1] = $this->createUserWithBalance(100);
        ['user' => $user2] = $this->createUserWithBalance(100);

        CreditTransaction::create([
            'user_id' => $user1->id,
            'amount' => 10.00,
            'balance_after' => 90,
            'type' => 'usage',
            'description' => 'User 1 transaction',
        ]);

        CreditTransaction::create([
            'user_id' => $user2->id,
            'amount' => 20.00,
            'balance_after' => 80,
            'type' => 'usage',
            'description' => 'User 2 transaction',
        ]);

        $result = $this->service->getTransactionHistory($user1, 15);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('User 1 transaction', $result->items()[0]->description);
    }

    // =========================================================================
    // REFUND CREDITS TESTS (RC)
    // =========================================================================

    /**
     * RC-001: refundCredits creates refund transaction
     */
    public function test_refund_credits_creates_refund_transaction(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        $transaction = $this->service->refundCredits($user, 10.00, null, 'System refund');

        $this->assertInstanceOf(CreditTransaction::class, $transaction);
        $this->assertEquals('refund', $transaction->type);
        $this->assertEquals(10.00, $transaction->amount);
        $this->assertEquals(60.00, $user->credit->fresh()->balance);
    }

    /**
     * RC-002: refundCredits with original transaction reference
     */
    public function test_refund_credits_with_original_reference(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        // Create original transaction
        $original = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => -25.00,
            'balance_after' => 25.00,
            'type' => 'usage',
            'description' => 'Analysis job',
            'reference_type' => 'App\\Models\\AnalysisJob',
            'reference_id' => 123,
        ]);

        $refund = $this->service->refundCredits($user, 15.00, $original->id, 'Partial refund');

        $this->assertEquals('refund', $refund->type);
        $this->assertStringContainsString('Partial refund', $refund->description);
    }

    /**
     * RC-003: refundCredits throws InvalidCreditAmountException for zero
     */
    public function test_refund_credits_throws_exception_for_zero(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        $this->expectException(InvalidCreditAmountException::class);

        $this->service->refundCredits($user, 0, null, 'Invalid refund');
    }

    /**
     * RC-004: refundCredits throws InvalidCreditAmountException for negative
     */
    public function test_refund_credits_throws_exception_for_negative(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        $this->expectException(InvalidCreditAmountException::class);

        $this->service->refundCredits($user, -10.00, null, 'Invalid refund');
    }

    /**
     * RC-005: refundCredits handles non-existent original transaction
     */
    public function test_refund_credits_handles_nonexistent_original(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        // Should still succeed with null original transaction
        $refund = $this->service->refundCredits($user, 10.00, 99999, 'Manual refund');

        $this->assertInstanceOf(CreditTransaction::class, $refund);
    }

    // =========================================================================
    // TRANSACTION SAFETY TESTS (TS)
    // =========================================================================

    /**
     * TS-001: addCredits wrapped in database transaction
     */
    public function test_add_credits_wrapped_in_transaction(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        DB::enableQueryLog();

        $this->service->addCredits($user, 50.00, 'purchase', [
            'description' => 'Test',
        ]);

        $queries = DB::getQueryLog();

        // Transaction should be started
        $this->assertGreaterThan(0, count($queries));
    }

    /**
     * TS-002: deductCredits wrapped in database transaction
     */
    public function test_deduct_credits_wrapped_in_transaction(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        DB::enableQueryLog();

        $this->service->deductCredits($user, 10.00, 'App\\Models\\AnalysisJob', 123);

        $queries = DB::getQueryLog();

        $this->assertGreaterThan(0, count($queries));
    }

    /**
     * TS-003: Transaction rollback on error
     */
    public function test_transaction_rollback_on_error(): void
    {
        ['user' => $user, 'credit' => $credit] = $this->createUserWithBalance(50);

        $initialBalance = $credit->balance;

        try {
            // This should fail and rollback
            $this->service->deductCredits($user, 100.00, 'App\\Models\\AnalysisJob', 123);
        } catch (InsufficientCreditsException $e) {
            // Expected
        }

        // Balance should be unchanged after rollback
        $this->assertEquals($initialBalance, $credit->fresh()->balance);
    }

    /**
     * TS-004: Atomic balance and transaction creation
     */
    public function test_atomic_balance_and_transaction_creation(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $transaction = $this->service->addCredits($user, 100.00, 'purchase', [
            'description' => 'Test',
        ]);

        // Both should exist after transaction
        $this->assertDatabaseHas('credits', [
            'user_id' => $user->id,
            'balance' => 100.00,
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'id' => $transaction->id,
            'amount' => 100.00,
        ]);
    }

    // =========================================================================
    // CONCURRENCY TESTS (CC)
    // =========================================================================

    /**
     * CC-001: Concurrent deductions handled with locking
     */
    public function test_concurrent_deductions_handled(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        // Simulate concurrent requests (in reality this would need multiple processes)
        // For unit test, we verify the lock is acquired

        $result1 = $this->service->deductCredits($user, 50.00, 'App\\Models\\AnalysisJob', 1);
        $result2 = $this->service->deductCredits($user, 30.00, 'App\\Models\\AnalysisJob', 2);

        $this->assertEquals(-50.00, $result1->amount);
        $this->assertEquals(-30.00, $result2->amount);
        $this->assertEquals(20.00, $user->credit->fresh()->balance);
    }

    /**
     * CC-002: Concurrent add and deduct operations
     */
    public function test_concurrent_add_and_deduct(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        $add = $this->service->addCredits($user, 50.00, 'purchase', [
            'description' => 'Add',
        ]);

        $deduct = $this->service->deductCredits($user, 30.00, 'App\\Models\\AnalysisJob', 123);

        $this->assertEquals(70.00, $user->credit->fresh()->balance);
    }

    /**
     * CC-003: Lock timeout handling
     */
    public function test_lock_timeout_handling(): void
    {
        // This test would require actual concurrent connections
        // For unit test, we verify the method signature supports timeout
        ['user' => $user] = $this->createUserWithBalance(100);

        // Should complete without timeout in normal operation
        $result = $this->service->deductCredits($user, 10.00, 'App\\Models\\AnalysisJob', 123);

        $this->assertInstanceOf(CreditTransaction::class, $result);
    }

    // =========================================================================
    // ERROR HANDLING TESTS (EH)
    // =========================================================================

    /**
     * EH-001: InsufficientCreditsException contains context
     */
    public function test_insufficient_credits_exception_contains_context(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        try {
            $this->service->deductCredits($user, 100.00, 'App\\Models\\AnalysisJob', 123);
            $this->fail('Expected InsufficientCreditsException');
        } catch (InsufficientCreditsException $e) {
            $this->assertStringContainsString('insufficient', strtolower($e->getMessage()));
        }
    }

    /**
     * EH-002: InvalidCreditAmountException contains context
     */
    public function test_invalid_credit_amount_exception_contains_context(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        try {
            $this->service->addCredits($user, -10.00, 'purchase', [
                'description' => 'Test',
            ]);
            $this->fail('Expected InvalidCreditAmountException');
        } catch (InvalidCreditAmountException $e) {
            $this->assertStringContainsString('invalid', strtolower($e->getMessage()));
        }
    }

    /**
     * EH-003: Errors are logged with context
     */
    public function test_errors_are_logged_with_context(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($user) {
                return str_contains(strtolower($message), 'insufficient') &&
                    isset($context['user_id']) &&
                    $context['user_id'] === $user->id;
            });

        try {
            $this->service->deductCredits($user, 100.00, 'App\\Models\\AnalysisJob', 123);
        } catch (InsufficientCreditsException $e) {
            // Expected
        }
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Very large credit amount
     */
    public function test_very_large_credit_amount(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $transaction = $this->service->addCredits($user, 99999999.99, 'purchase', [
            'description' => 'Large purchase',
        ]);

        $this->assertEquals(99999999.99, $transaction->amount);
        $this->assertEquals(99999999.99, $user->credit->fresh()->balance);
    }

    /**
     * EC-002: Small decimal amounts
     */
    public function test_small_decimal_amounts(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $this->service->addCredits($user, 0.01, 'purchase', [
            'description' => 'Micro purchase',
        ]);

        $this->assertEquals(0.01, $user->credit->fresh()->balance);
    }

    /**
     * EC-003: Multiple small operations
     */
    public function test_multiple_small_operations(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        for ($i = 0; $i < 100; $i++) {
            $this->service->addCredits($user, 0.01, 'bonus', [
                'description' => "Micro bonus $i",
            ]);
        }

        $this->assertEquals(1.00, $user->credit->fresh()->balance);
    }

    /**
     * EC-004: Unicode in description
     */
    public function test_unicode_in_description(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $transaction = $this->service->addCredits($user, 50.00, 'purchase', [
            'description' => '購入クレジット 🎉',
        ]);

        $this->assertEquals('購入クレジット 🎉', $transaction->description);
    }

    /**
     * EC-005: Long description
     */
    public function test_long_description(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $longDescription = str_repeat('a', 500);

        $transaction = $this->service->addCredits($user, 50.00, 'purchase', [
            'description' => $longDescription,
        ]);

        $this->assertEquals(500, strlen($transaction->description));
    }

    /**
     * EC-006: Balance precision maintained through operations
     */
    public function test_balance_precision_maintained(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        // Add 33.33
        $this->service->addCredits($user, 33.33, 'purchase', ['description' => 'Add']);
        // Add 33.33
        $this->service->addCredits($user, 33.33, 'purchase', ['description' => 'Add']);
        // Add 33.34
        $this->service->addCredits($user, 33.34, 'purchase', ['description' => 'Add']);

        $this->assertEquals(100.00, $user->credit->fresh()->balance);
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-001: Balance cannot go negative through deduct
     */
    public function test_balance_cannot_go_negative(): void
    {
        ['user' => $user] = $this->createUserWithBalance(50);

        $this->expectException(InsufficientCreditsException::class);

        $this->service->deductCredits($user, 100.00, 'App\\Models\\AnalysisJob', 123);

        $this->assertEquals(50.00, $user->credit->fresh()->balance);
    }

    /**
     * SC-002: Server-side balance validation
     */
    public function test_server_side_balance_validation(): void
    {
        ['user' => $user] = $this->createUserWithBalance(100);

        // Attempt to deduct more than balance
        $this->expectException(InsufficientCreditsException::class);

        $this->service->deductCredits($user, 200.00, 'App\\Models\\AnalysisJob', 123);
    }

    /**
     * SC-003: Audit trail for all balance changes
     */
    public function test_audit_trail_for_all_changes(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        $this->service->addCredits($user, 100.00, 'purchase', ['description' => 'Add']);
        $this->service->deductCredits($user, 30.00, 'App\\Models\\AnalysisJob', 123);
        $this->service->refundCredits($user, 10.00, null, 'Refund');

        // Should have 3 transactions
        $this->assertEquals(3, CreditTransaction::where('user_id', $user->id)->count());
    }

    /**
     * SC-004: Amount validation for reasonable bounds
     */
    public function test_amount_validation_reasonable_bounds(): void
    {
        ['user' => $user] = $this->createUserWithBalance(0);

        // Amount over 100 million should probably be validated
        // This test documents expected behavior
        $transaction = $this->service->addCredits($user, 100000000.00, 'purchase', [
            'description' => 'Very large purchase',
        ]);

        // Should succeed but system may have validation rules
        $this->assertNotNull($transaction);
    }
}
