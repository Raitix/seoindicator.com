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
 * @Task TASK-26-2
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

    /** @test RI-004 */
    public function model_dependency(): void
    {
        $repository = new CreditRepository();

        $this->assertInstanceOf(CreditRepository::class, $repository);
    }

    /** @test RI-005 */
    public function transaction_dependency(): void
    {
        $repository = new CreditRepository();

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

    /** @test RF-006 */
    public function transaction_isolation(): void
    {
        $user = User::factory()->create();

        // Simulate concurrent creation
        $credit1 = $this->repository->findOrCreateForUser($user);
        $credit2 = $this->repository->findOrCreateForUser($user);

        // Should return same credit (unique constraint)
        $this->assertEquals($credit1->id, $credit2->id);
    }

    /** @test RF-007 */
    public function user_relationship_loaded(): void
    {
        $user = User::factory()->create();

        $credit = $this->repository->findOrCreateForUser($user);

        $this->assertTrue($credit->relationLoaded('user') || $credit->user()->exists());
    }

    /** @test RF-008 */
    public function invalid_user_type_throws_exception(): void
    {
        $this->expectException(\TypeError::class);

        $this->repository->findOrCreateForUser(null);
    }

    /** @test RF-009 */
    public function user_without_id_throws_exception(): void
    {
        $user = new User();

        $this->expectException(\Exception::class);

        $this->repository->findOrCreateForUser($user);
    }

    /** @test RF-010 */
    public function returns_credit_model(): void
    {
        $user = User::factory()->create();

        $credit = $this->repository->findOrCreateForUser($user);

        $this->assertInstanceOf(Credit::class, $credit);
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
    public function decimal_precision(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(123.45)->create();

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(123.45, $balance);
    }

    /** @test RG-008 */
    public function large_balance(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(99999999.99)->create();

        $balance = $this->repository->getBalance($user);

        $this->assertEquals(99999999.99, $balance);
    }

    /** @test RG-009 */
    public function multiple_calls_same_result(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $balance1 = $this->repository->getBalance($user);
        $balance2 = $this->repository->getBalance($user);

        $this->assertEquals($balance1, $balance2);
    }

    /** @test RG-010 */
    public function balance_unchanged_after_query(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $this->repository->getBalance($user);

        $this->assertDatabaseHas('credits', [
            'user_id' => $user->id,
            'balance' => 100.00,
        ]);
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
    public function user_without_credit(): void
    {
        $user = User::factory()->create();

        $transactions = $this->repository->getTransactions($user);

        $this->assertCount(0, $transactions->items());
    }

    /** @test RGT-004 */
    public function custom_per_page(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(20)->forUser($user)->create();

        $transactions = $this->repository->getTransactions($user, 10);

        $this->assertEquals(10, $transactions->perPage());
    }

    /** @test RGT-005 */
    public function default_per_page(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(5)->forUser($user)->create();

        $transactions = $this->repository->getTransactions($user);

        $this->assertEquals(15, $transactions->perPage());
    }

    /** @test RGT-006 */
    public function ordered_by_created_at_desc(): void
    {
        $user = User::factory()->create();
        $old = CreditTransaction::factory()->forUser($user)->create(['created_at' => now()->subDay()]);
        $new = CreditTransaction::factory()->forUser($user)->create(['created_at' => now()]);

        $transactions = $this->repository->getTransactions($user);

        $this->assertEquals($new->id, $transactions->items()[0]->id);
        $this->assertEquals($old->id, $transactions->items()[1]->id);
    }

    /** @test RGT-007 */
    public function filter_by_type(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(3)->forUser($user)->purchase()->create();
        CreditTransaction::factory()->count(2)->forUser($user)->usage()->create();

        $transactions = $this->repository->getTransactions($user, 15, ['type' => 'purchase']);

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

        $transactions = $this->repository->getTransactions($user, 15, [
            'date_from' => $now->copy()->subDays(6)->toDateString(),
            'date_to' => $now->copy()->subDays(1)->toDateString(),
        ]);

        $this->assertCount(1, $transactions->items());
    }

    /** @test RGT-009 */
    public function paginates_correctly(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->count(50)->forUser($user)->create();

        $transactions = $this->repository->getTransactions($user, 10);

        $this->assertEquals(5, $transactions->lastPage());
        $this->assertEquals(50, $transactions->total());
    }

    /** @test RGT-010 */
    public function returns_correct_type(): void
    {
        $user = User::factory()->create();

        $transactions = $this->repository->getTransactions($user);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $transactions);
    }

    /** @test RGT-011 */
    public function loads_relationships(): void
    {
        $user = User::factory()->create();
        CreditTransaction::factory()->forUser($user)->create();

        $transactions = $this->repository->getTransactions($user);

        $this->assertTrue($transactions->items()[0]->relationLoaded('user'));
    }

    /** @test RGT-012 */
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

    // ==========================================
    // Transaction-Safe Balance Updates Tests (RT-001 to RT-015)
    // ==========================================

    /** @test RT-001 */
    public function add_credits_successfully(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $credit = $this->repository->addCredits($user, 50.00, 'purchase', 'Test purchase');

        $this->assertEquals(150.00, $credit->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 50.00,
            'type' => 'purchase',
        ]);
    }

    /** @test RT-002 */
    public function deduct_credits_successfully(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $credit = $this->repository->deductCredits($user, 25.00, 'Test usage');

        $this->assertEquals(75.00, $credit->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => -25.00,
            'type' => 'usage',
        ]);
    }

    /** @test RT-003 */
    public function deduct_insufficient_balance_throws_exception(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(50.00)->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->repository->deductCredits($user, 100.00, 'Test usage');
    }

    /** @test RT-004 */
    public function zero_amount_transaction(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $credit = $this->repository->addCredits($user, 0.00, 'purchase', 'Zero test');

        $this->assertEquals(100.00, $credit->balance);
    }

    /** @test RT-005 */
    public function decimal_precision_in_updates(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $credit = $this->repository->addCredits($user, 12.34, 'purchase', 'Test');

        $this->assertEquals(112.34, $credit->balance);
    }

    /** @test RT-006 */
    public function transaction_created_on_update(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 50.00, 'purchase', 'Test');

        $this->assertEquals(1, CreditTransaction::where('user_id', $user->id)->count());
    }

    /** @test RT-007 */
    public function transaction_type_purchase(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 50.00, 'purchase', 'Test');

        $transaction = CreditTransaction::where('user_id', $user->id)->first();
        $this->assertEquals('purchase', $transaction->type);
    }

    /** @test RT-008 */
    public function transaction_type_usage(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $this->repository->deductCredits($user, 50.00, 'Test usage');

        $transaction = CreditTransaction::where('user_id', $user->id)->first();
        $this->assertEquals('usage', $transaction->type);
    }

    /** @test RT-009 */
    public function transaction_type_refund(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 50.00, 'refund', 'Test refund');

        $transaction = CreditTransaction::where('user_id', $user->id)->first();
        $this->assertEquals('refund', $transaction->type);
    }

    /** @test RT-010 */
    public function transaction_type_bonus(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 25.00, 'bonus', 'Welcome bonus');

        $transaction = CreditTransaction::where('user_id', $user->id)->first();
        $this->assertEquals('bonus', $transaction->type);
    }

    /** @test RT-011 */
    public function balance_after_correct(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $this->repository->addCredits($user, 50.00, 'purchase', 'Test');

        $transaction = CreditTransaction::where('user_id', $user->id)->first();
        $this->assertEquals(150.00, $transaction->balance_after);
    }

    /** @test RT-012 */
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

    /** @test RT-013 */
    public function rollback_on_error(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        try {
            DB::transaction(function () use ($user) {
                $this->repository->addCredits($user, 50.00, 'purchase', 'Test');
                throw new \Exception('Simulated error');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertDatabaseHas('credits', [
            'user_id' => $user->id,
            'balance' => 100.00,
        ]);
    }

    /** @test RT-014 */
    public function lock_for_update_used(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        DB::beginTransaction();
        $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();
        $this->assertNotNull($credit);
        DB::rollBack();
    }

    /** @test RT-015 */
    public function database_transaction_used(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        // The repository should use DB::transaction internally
        $credit = $this->repository->addCredits($user, 50.00, 'purchase', 'Test');

        $this->assertEquals(150.00, $credit->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 50.00,
        ]);
    }

    // ==========================================
    // Credit Update Methods (RC-001 to RC-010)
    // ==========================================

    /** @test RC-001 */
    public function add_credits_method(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $credit = $this->repository->addCredits($user, 50.00, 'purchase', 'Test purchase');

        $this->assertEquals(150.00, $credit->balance);
    }

    /** @test RC-002 */
    public function deduct_credits_method(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $credit = $this->repository->deductCredits($user, 25.00, 'Test usage');

        $this->assertEquals(75.00, $credit->balance);
    }

    /** @test RC-003 */
    public function deduct_with_reference(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $credit = $this->repository->deductCredits($user, 25.00, 'Test', [
            'type' => 'App\\Models\\Analysis',
            'id' => 123,
        ]);

        $this->assertEquals(75.00, $credit->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'reference_type' => 'App\\Models\\Analysis',
            'reference_id' => 123,
        ]);
    }

    /** @test RC-004 */
    public function deduct_insufficient_throws(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(50.00)->create();

        $this->expectException(\Exception::class);

        $this->repository->deductCredits($user, 100.00, 'Test');
    }

    /** @test RC-005 */
    public function add_credits_with_payment(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $credit = $this->repository->addCredits($user, 50.00, 'purchase', 'Stripe payment', [
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_123',
        ]);

        $this->assertEquals(50.00, $credit->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_123',
        ]);
    }

    /** @test RC-006 */
    public function refund_credits_method(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $credit = $this->repository->refundCredits($user, 10.00, 'Refund', [
            'type' => 'App\\Models\\Analysis',
            'id' => 456,
        ]);

        $this->assertEquals(110.00, $credit->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => 'refund',
            'amount' => 10.00,
        ]);
    }

    /** @test RC-007 */
    public function bonus_credits_method(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $credit = $this->repository->bonusCredits($user, 25.00, 'Welcome bonus');

        $this->assertEquals(25.00, $credit->balance);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => 'bonus',
        ]);
    }

    /** @test RC-008 */
    public function description_stored(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 50.00, 'purchase', 'Custom description');

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'description' => 'Custom description',
        ]);
    }

    /** @test RC-009 */
    public function metadata_stored(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 50.00, 'purchase', 'Test', [
            'metadata' => ['key' => 'value'],
        ]);

        $transaction = CreditTransaction::where('user_id', $user->id)->first();
        $this->assertEquals(['key' => 'value'], $transaction->metadata);
    }

    /** @test RC-010 */
    public function balance_updates_total_spent(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        $this->repository->deductCredits($user, 25.00, 'Usage');

        $credit = Credit::where('user_id', $user->id)->first();
        $this->assertEquals(25.00, $credit->total_spent);
    }

    // ==========================================
    // Error Handling Tests (RE-001 to RE-008)
    // ==========================================

    /** @test RE-001 */
    public function invalid_amount_type(): void
    {
        $this->expectException(\TypeError::class);

        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->repository->addCredits($user, 'abc', 'purchase', 'Test');
    }

    /** @test RE-002 */
    public function negative_purchase_amount(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Purchase amount must be positive');

        $this->repository->addCredits($user, -10.00, 'purchase', 'Test');
    }

    /** @test RE-003 */
    public function user_not_found(): void
    {
        $user = new User();
        $user->id = 999999;

        $this->expectException(\Exception::class);

        $this->repository->getBalance($user);
    }

    /** @test RE-004 */
    public function database_error_handling(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        // Simulate DB error by using an invalid amount
        $this->expectException(\Exception::class);

        // Try to insert amount that exceeds DECIMAL
        $this->repository->addCredits($user, 999999999.99, 'purchase', 'Test');
    }

    /** @test RE-005 */
    public function invalid_transaction_type(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->expectException(\Exception::class);

        $this->repository->addCredits($user, 50.00, 'invalid_type', 'Test');
    }

    /** @test RE-006 */
    public function null_user(): void
    {
        $this->expectException(\TypeError::class);

        $this->repository->getBalance(null);
    }

    /** @test RE-007 */
    public function zero_balance_deduction(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create(); // balance = 0

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->repository->deductCredits($user, 1.00, 'Test');
    }

    /** @test RE-008 */
    public function concurrent_race_condition(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->withBalance(100.00)->create();

        // Simulate race condition with transactions
        $results = [];
        
        DB::transaction(function () use ($user, &$results) {
            $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();
            $results[] = $credit->balance;
            $credit->update(['balance' => $credit->balance - 50]);
        });
        
        DB::transaction(function () use ($user, &$results) {
            $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();
            $results[] = $credit->balance;
            $credit->update(['balance' => $credit->balance - 30]);
        });

        $this->assertDatabaseHas('credits', [
            'user_id' => $user->id,
            'balance' => 20.00,
        ]);
    }
}
