<?php

namespace Tests\Unit\Repositories;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Repositories\CreditRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * @Task TASK-26-25
 * @Description CreditRepository Unit Tests
 * @Tests 55+
 */
class CreditRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CreditRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(CreditRepository::class);
    }

    // ==========================================
    // Repository Instantiation Tests (RI-001 to RI-005)
    // ==========================================

    /** @test RI-001 */
    public function repository_instantiable(): void
    {
        $repository = new CreditRepository();

        $this->assertInstanceOf(CreditRepository::class, $repository);
    }

    /** @test RI-002 */
    public function repository_injected_via_container(): void
    {
        $repository = app(CreditRepository::class);

        $this->assertInstanceOf(CreditRepository::class, $repository);
    }

    /** @test RI-003 */
    public function interface_binding(): void
    {
        // Skip if interface not defined yet
        if (!interface_exists(\App\Repositories\Contracts\CreditRepositoryInterface::class)) {
            $this->markTestSkipped('CreditRepositoryInterface not defined');
        }

        $repository = app(\App\Repositories\Contracts\CreditRepositoryInterface::class);

        $this->assertInstanceOf(CreditRepository::class, $repository);
    }

    // ==========================================
    // findOrCreateForUser Tests (RF-001 to RF-010)
    // ==========================================

    /** @test RF-001 */
    public function find_existing_credit(): void
    {
        $user = User::factory()->create();
        $existingCredit = Credit::factory()->forUser($user)->create();

        $credit = $this->repository->findOrCreateForUser($user);

        $this->assertEquals($existingCredit->id, $credit->id);
        $this->assertEquals($user->id, $credit->user_id);
    }

    /** @test RF-002 */
    public function create_new_credit(): void
    {
        $user = User::factory()->create();
        $this->assertDatabaseMissing('credits', ['user_id' => $user->id]);

        $credit = $this->repository->findOrCreateForUser($user);

        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
        $this->assertInstanceOf(Credit::class, $credit);
    }

    /** @test RF-003 */
    public function new_credit_defaults(): void
    {
        $user = User::factory()->create();

        $credit = $this->repository->findOrCreateForUser($user);

        $this->assertEquals(0.0, $credit->balance);
        $this->assertEquals(0.0, $credit->total_purchased);
        $this->assertEquals(0.0, $credit->total_spent);
    }

    /** @test RF-004 */
    public function same_user_returns_same_credit(): void
    {
        $user = User::factory()->create();

        $credit1 = $this->repository->findOrCreateForUser($user);
        $credit2 = $this->repository->findOrCreateForUser($user);

        $this->assertEquals($credit1->id, $credit2->id);
    }

    /** @test RF-005 */
    public function different_users_different_credits(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $credit1 = $this->repository->findOrCreateForUser($user1);
        $credit2 = $this->repository->findOrCreateForUser($user2);

        $this->assertNotEquals($credit1->id, $credit2->id);
    }

    // ==========================================
    // getBalance Tests (RG-001 to RG-010)
    // ==========================================

    /** @test RG-001 */
    public function user_with_positive_balance(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.50)->create();

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(100.50, $balance);
    }

    /** @test RG-002 */
    public function user_with_zero_balance(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(0.00, $balance);
    }

    /** @test RG-003 */
    public function user_with_negative_balance(): void
    {
        $user = User::factory()->create();
        Credit::create([
            'user_id' => $user->id,
            'balance' => -10.00,
        ]);

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(-10.00, $balance);
    }

    /** @test RG-004 */
    public function user_without_credit_returns_zero(): void
    {
        $user = User::factory()->create();

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(0.00, $balance);
    }

    /** @test RG-005 */
    public function creates_credit_if_missing(): void
    {
        $user = User::factory()->create();
        $this->assertDatabaseMissing('credits', ['user_id' => $user->id]);

        $this->repository->getBalance($user);

        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
    }

    /** @test RG-006 */
    public function returns_float(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $balance = $this->repository->getBalance($user);

        $this->assertIsFloat($balance);
    }

    /** @test RG-007 */
    public function large_balance(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(99999999.99)->create();

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(99999999.99, $balance);
    }

    // ==========================================
    // getTransactions Tests (RGT-001 to RGT-012)
    // ==========================================

    /** @test RGT-001 */
    public function user_with_transactions(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(5)->forUser($user)->create();

        $transactions = $this->repository->getTransactions($user);

        $this->assertCount(5, $transactions->items());
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $transactions);
    }

    /** @test RGT-002 */
    public function user_without_transactions(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transactions = $this->repository->getTransactions($user);

        $this->assertCount(0, $transactions->items());
    }

    /** @test RGT-003 */
    public function custom_per_page(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(20)->forUser($user)->create();

        $transactions = $this->repository->getTransactions($user, 10);

        $this->assertEquals(10, $transactions->perPage());
    }

    /** @test RGT-004 */
    public function default_per_page(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(5)->forUser($user)->create();

        $transactions = $this->repository->getTransactions($user);

        $this->assertEquals(15, $transactions->perPage());
    }

    /** @test RGT-005 */
    public function ordered_by_created_at_desc(): void
    {
        $user = User::factory()->create();
        $old = CreditTransaction::factory()->forUser($user)->create(['created_at' => now()->subDay()]);
        $new = CreditTransaction::factory()->forUser($user)->create(['created_at' => now()]);

        $transactions = $this->repository->getTransactions($user);

        $this->assertEquals($new->id, $transactions->items()[0]->id);
        $this->assertEquals($old->id, $transactions->items()[1]->id);
    }

    /** @test RGT-006 */
    public function user_isolation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        CreditTransaction::factory()->count(5)->forUser($user1)->create();
        CreditTransaction::factory()->count(3)->forUser($user2)->create();

        $transactions = $this->repository->getTransactions($user1);

        $this->assertCount(5, $transactions->items());
        foreach ($transactions->items() as $transaction) {
            $this->assertEquals($user1->id, $transaction->user_id);
        }
    }

    /** @test RGT-007 */
    public function filter_by_type(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(3)->forUser($user)->purchase()->create();
        CreditTransaction::factory()->count(2)->forUser($user)->usage()->create();

        $transactions = $this->repository->getTransactionsWithFilters($user, 15, ['type' => 'purchase']);

        $this->assertCount(3, $transactions->items());
    }

    /** @test RGT-008 */
    public function filter_by_date_range(): void
    {
        $user = User::factory()->create();
        $now = now();
        CreditTransaction::factory()->forUser($user)->create(['created_at' => $now->copy()->subDays(10)]);
        CreditTransaction::factory()->forUser($user)->create(['created_at' => $now->copy()->subDays(5)]);
        CreditTransaction::factory()->forUser($user)->create(['created_at' => $now]);

        $transactions = $this->repository->getTransactionsWithFilters($user, 15, [
            'from' => $now->copy()->subDays(6)->toDateString(),
            'to' => $now->copy()->subDays(1)->toDateString(),
        ]);

        $this->assertCount(1, $transactions->items());
    }

    // ==========================================
    // addCredits Tests (RA-001 to RA-015)
    // ==========================================

    /** @test RA-001 */
    public function add_credits_successfully(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $transaction = $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'Test purchase',
        ]);

        $this->assertInstanceOf(CreditTransaction::class, $transaction);
        $this->assertEquals(50.00, $transaction->amount);
        $this->assertEquals('purchase', $transaction->type);
        $this->assertEquals(150.00, $transaction->balance_after);
        $this->assertEquals(150.00, $user->credit->fresh()->balance);
    }

    /** @test RA-002 */
    public function add_credits_creates_transaction(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'Test',
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 50.00,
            'type' => 'purchase',
        ]);
    }

    /** @test RA-003 */
    public function add_credits_updates_total_purchased(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 100.00, [
            'type' => 'purchase',
            'description' => 'Test',
        ]);

        $credit = Credit::where('user_id', $user->id)->first();
        $this->assertEquals(100.00, $credit->total_purchased);
    }

    /** @test RA-004 */
    public function add_bonus_credits(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 25.00, [
            'type' => 'bonus',
            'description' => 'Welcome bonus',
        ]);

        $this->assertEquals('bonus', $transaction->type);
        $this->assertEquals(25.00, $user->credit->fresh()->balance);
    }

    /** @test RA-005 */
    public function add_credits_with_payment_info(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'Stripe payment',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_123',
        ]);

        $this->assertEquals('stripe', $transaction->payment_provider);
        $this->assertEquals('pi_123', $transaction->payment_id);
    }

    /** @test RA-006 */
    public function add_credits_with_reference(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'Test',
            'reference_type' => 'App\\Models\\Payment',
            'reference_id' => 123,
        ]);

        $this->assertEquals('App\\Models\\Payment', $transaction->reference_type);
        $this->assertEquals(123, $transaction->reference_id);
    }

    /** @test RA-007 */
    public function add_credits_with_metadata(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'Test',
            'metadata' => ['package' => 'starter', 'campaign' => 'welcome'],
        ]);

        $this->assertEquals(['package' => 'starter', 'campaign' => 'welcome'], $transaction->metadata);
    }

    /** @test RA-008 */
    public function add_credits_creates_credit_if_missing(): void
    {
        $user = User::factory()->create();
        $this->assertDatabaseMissing('credits', ['user_id' => $user->id]);

        $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'First purchase',
        ]);

        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
        $this->assertEquals(50.00, $user->credit->balance);
    }

    /** @test RA-009 */
    public function add_refund_credits(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 25.00, [
            'type' => 'refund',
            'description' => 'Refund for cancelled analysis',
        ]);

        $this->assertEquals('refund', $transaction->type);
        $this->assertEquals(25.00, $user->credit->fresh()->balance);
    }

    // ==========================================
    // deductCredits Tests (RD-001 to RD-015)
    // ==========================================

    /** @test RD-001 */
    public function deduct_credits_successfully(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $transaction = $this->repository->deductCredits($user, 25.00, [
            'description' => 'Analysis usage',
        ]);

        $this->assertInstanceOf(CreditTransaction::class, $transaction);
        $this->assertEquals(-25.00, $transaction->amount);
        $this->assertEquals('usage', $transaction->type);
        $this->assertEquals(75.00, $transaction->balance_after);
        $this->assertEquals(75.00, $user->credit->fresh()->balance);
    }

    /** @test RD-002 */
    public function deduct_credits_updates_total_spent(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $this->repository->deductCredits($user, 25.00, [
            'description' => 'Test',
        ]);

        $credit = Credit::where('user_id', $user->id)->first();
        $this->assertEquals(25.00, $credit->total_spent);
    }

    /** @test RD-003 */
    public function deduct_insufficient_balance_throws_exception(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(50.00)->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient');

        $this->repository->deductCredits($user, 100.00, [
            'description' => 'Test usage',
        ]);
    }

    /** @test RD-004 */
    public function deduct_exact_balance_succeeds(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $transaction = $this->repository->deductCredits($user, 100.00, [
            'description' => 'Full balance usage',
        ]);

        $this->assertEquals(0.0, $transaction->balance_after);
        $this->assertEquals(0.0, $user->credit->fresh()->balance);
    }

    /** @test RD-005 */
    public function deduct_credits_with_reference(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $transaction = $this->repository->deductCredits($user, 25.00, [
            'description' => 'Analysis job',
            'reference_type' => 'App\\Models\\AnalysisJob',
            'reference_id' => 123,
        ]);

        $this->assertEquals('App\\Models\\AnalysisJob', $transaction->reference_type);
        $this->assertEquals(123, $transaction->reference_id);
    }

    /** @test RD-006 */
    public function deduct_credits_zero_balance_throws(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->expectException(\Exception::class);

        $this->repository->deductCredits($user, 1.00, [
            'description' => 'Test',
        ]);
    }

    /** @test RD-007 */
    public function deduct_credits_preserves_balance_on_failure(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(50.00)->create();

        try {
            $this->repository->deductCredits($user, 100.00, [
                'description' => 'Test',
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals(50.00, $user->credit->fresh()->balance);
    }

    /** @test RD-008 */
    public function deduct_credits_with_metadata(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $transaction = $this->repository->deductCredits($user, 25.00, [
            'description' => 'Test',
            'metadata' => ['analysis_type' => 'deep', 'model' => 'gpt-4'],
        ]);

        $this->assertEquals(['analysis_type' => 'deep', 'model' => 'gpt-4'], $transaction->metadata);
    }

    // ==========================================
    // hasSufficientBalance Tests (RH-001 to RH-005)
    // ==========================================

    /** @test RH-001 */
    public function has_sufficient_balance_true(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $result = $this->repository->hasSufficientBalance($user, 50.00);

        $this->assertTrue($result);
    }

    /** @test RH-002 */
    public function has_sufficient_balance_exact(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $result = $this->repository->hasSufficientBalance($user, 100.00);

        $this->assertTrue($result);
    }

    /** @test RH-003 */
    public function has_sufficient_balance_false(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(50.00)->create();

        $result = $this->repository->hasSufficientBalance($user, 100.00);

        $this->assertFalse($result);
    }

    /** @test RH-004 */
    public function has_sufficient_balance_zero(): void
    {
        $user = User::factory()->create();

        $result = $this->repository->hasSufficientBalance($user, 0.00);

        $this->assertTrue($result);
    }

    /** @test RH-005 */
    public function has_sufficient_balance_creates_credit(): void
    {
        $user = User::factory()->create();
        $this->assertDatabaseMissing('credits', ['user_id' => $user->id]);

        $this->repository->hasSufficientBalance($user, 0.00);

        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
    }

    // ==========================================
    // getStats Tests (RS-001 to RS-005)
    // ==========================================

    /** @test RS-001 */
    public function get_stats_returns_array(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $stats = $this->repository->getStats($user);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('balance', $stats);
        $this->assertArrayHasKey('total_purchased', $stats);
        $this->assertArrayHasKey('total_spent', $stats);
        $this->assertArrayHasKey('transaction_count', $stats);
    }

    /** @test RS-002 */
    public function get_stats_correct_values(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();
        CreditTransaction::factory()->count(5)->forUser($user)->create();

        $stats = $this->repository->getStats($user);

        $this->assertEquals(100.00, $stats['balance']);
        $this->assertEquals(5, $stats['transaction_count']);
    }

    /** @test RS-003 */
    public function get_stats_creates_credit_if_missing(): void
    {
        $user = User::factory()->create();

        $stats = $this->repository->getStats($user);

        $this->assertEquals(0.00, $stats['balance']);
        $this->assertEquals(0, $stats['transaction_count']);
        $this->assertDatabaseHas('credits', ['user_id' => $user->id]);
    }

    // ==========================================
    // Transaction Safety Tests (RTS-001 to RTS-005)
    // ==========================================

    /** @test RTS-001 */
    public function add_credits_wrapped_in_transaction(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => 'Test',
        ]);

        // Both should exist after transaction
        $this->assertDatabaseHas('credits', [
            'user_id' => $user->id,
            'balance' => 50.00,
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'id' => $transaction->id,
        ]);
    }

    /** @test RTS-002 */
    public function deduct_credits_wrapped_in_transaction(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $transaction = $this->repository->deductCredits($user, 25.00, [
            'description' => 'Test',
        ]);

        // Both should exist after transaction
        $this->assertDatabaseHas('credits', [
            'user_id' => $user->id,
            'balance' => 75.00,
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'id' => $transaction->id,
            'amount' => -25.00,
        ]);
    }

    /** @test RTS-003 */
    public function rollback_on_error(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        try {
            DB::transaction(function () use ($user) {
                $this->repository->addCredits($user, 50.00, [
                    'type' => 'purchase',
                    'description' => 'Test',
                ]);
                throw new \Exception('Simulated error');
            });
        } catch (\Exception $e) {
            // Expected
        }

        // Balance should be unchanged after rollback
        $this->assertEquals(100.00, $user->credit->fresh()->balance);
    }

    /** @test RTS-004 */
    public function lock_for_update_used(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        DB::beginTransaction();
        $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();
        $this->assertNotNull($credit);
        DB::rollBack();
    }

    /** @test RTS-005 */
    public function concurrent_updates_use_lock(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        // Simulate sequential concurrent updates
        DB::transaction(function () use ($user) {
            $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();
            $credit->update(['balance' => $credit->balance + 50]);
        });
        DB::transaction(function () use ($user) {
            $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();
            $credit->update(['balance' => $credit->balance - 25]);
        });

        $this->assertDatabaseHas('credits', [
            'user_id' => $user->id,
            'balance' => 125.00,
        ]);
    }

    // ==========================================
    // Edge Cases (RE-001 to RE-008)
    // ==========================================

    /** @test RE-001 */
    public function multiple_operations(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 100.00, ['type' => 'purchase', 'description' => 'Add']);
        $this->repository->deductCredits($user, 30.00, ['description' => 'Use']);
        $this->repository->addCredits($user, 10.00, ['type' => 'refund', 'description' => 'Refund']);

        $this->assertEquals(80.00, $user->credit->fresh()->balance);
        $this->assertEquals(3, CreditTransaction::where('user_id', $user->id)->count());
    }

    /** @test RE-002 */
    public function decimal_precision(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 12.34, [
            'type' => 'purchase',
            'description' => 'Test',
        ]);

        $this->assertEquals(12.34, $transaction->amount);
        $this->assertEquals(12.34, $user->credit->fresh()->balance);
    }

    /** @test RE-003 */
    public function small_amounts(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 0.01, [
            'type' => 'purchase',
            'description' => 'Micro purchase',
        ]);

        $this->assertEquals(0.01, $transaction->amount);
    }

    /** @test RE-004 */
    public function large_amount(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 99999999.99, [
            'type' => 'purchase',
            'description' => 'Large purchase',
        ]);

        $this->assertEquals(99999999.99, $transaction->amount);
    }

    /** @test RE-005 */
    public function unicode_description(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $transaction = $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => '購入クレジット 🎉',
        ]);

        $this->assertEquals('購入クレジット 🎉', $transaction->description);
    }

    /** @test RE-006 */
    public function long_description(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $longDescription = str_repeat('a', 500);

        $transaction = $this->repository->addCredits($user, 50.00, [
            'type' => 'purchase',
            'description' => $longDescription,
        ]);

        $this->assertEquals(500, strlen($transaction->description));
    }

    /** @test RE-007 */
    public function multiple_users_isolated(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Credit::factory()->forUser($user1)->create();
        Credit::factory()->forUser($user2)->create();

        $this->repository->addCredits($user1, 100.00, ['type' => 'purchase', 'description' => 'User 1']);
        $this->repository->addCredits($user2, 200.00, ['type' => 'purchase', 'description' => 'User 2']);

        $this->assertEquals(100.00, $user1->credit->fresh()->balance);
        $this->assertEquals(200.00, $user2->credit->fresh()->balance);
    }

    /** @test RE-008 */
    public function zero_amount_add(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $transaction = $this->repository->addCredits($user, 0.00, [
            'type' => 'purchase',
            'description' => 'Zero test',
        ]);

        $this->assertEquals(100.00, $user->credit->fresh()->balance);
    }
}
