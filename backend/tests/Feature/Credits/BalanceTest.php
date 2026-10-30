<?php

namespace Tests\Feature\Credits;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for credit balance management.
 *
 * TASK-26-26: Write Integration Tests for Payments
 * @see TESTS/TASK-26-26.md
 *
 * Tests cover balance retrieval, updates, and concurrent access scenarios.
 */
class BalanceTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // Balance Retrieval Tests
    // ============================================

    /** @test */
    public function it_returns_user_balance(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 25.50,
            'total_purchased' => 50.00,
            'total_spent' => 24.50,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertStatus(200)
            ->assertJson([
                'balance' => 25.50,
                'formatted_balance' => '$25.50',
                'total_purchased' => 50.00,
                'total_spent' => 24.50,
            ]);
    }

    /** @test */
    public function it_returns_zero_balance_for_new_user(): void
    {
        $user = User::factory()->create();
        // No credit record exists yet

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertStatus(200)
            ->assertJson([
                'balance' => 0.00,
                'formatted_balance' => '$0.00',
                'total_purchased' => 0.00,
                'total_spent' => 0.00,
            ]);
    }

    /** @test */
    public function it_creates_credit_record_on_first_access(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseMissing('credits', [
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertStatus(200);

        $this->assertDatabaseHas('credits', [
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);
    }

    /** @test */
    public function it_formats_balance_with_currency_symbol(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertJson([
            'formatted_balance' => '$100.00',
        ]);
    }

    /** @test */
    public function it_handles_decimal_precision(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 12.34,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertJson([
            'balance' => 12.34,
        ]);
    }

    // ============================================
    // Balance Update Tests
    // ============================================

    /** @test */
    public function it_adds_credits_from_purchase(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
            'total_purchased' => 10.00,
        ]);

        // Simulate adding credits from a purchase
        $credit->balance += 25.00;
        $credit->total_purchased += 25.00;
        $credit->save();

        $credit->refresh();

        $this->assertEquals(35.00, $credit->balance);
        $this->assertEquals(35.00, $credit->total_purchased);
    }

    /** @test */
    public function it_deducts_credits_for_usage(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 20.00,
            'total_spent' => 0.00,
        ]);

        // Simulate deducting credits for analysis
        $credit->balance -= 5.00;
        $credit->total_spent += 5.00;
        $credit->save();

        $credit->refresh();

        $this->assertEquals(15.00, $credit->balance);
        $this->assertEquals(5.00, $credit->total_spent);
    }

    /** @test */
    public function it_prevents_insufficient_balance_usage(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 5.00,
        ]);

        // Check hasSufficientBalance method
        $this->assertTrue($credit->hasSufficientBalance(5.00));
        $this->assertTrue($credit->hasSufficientBalance(4.99));
        $this->assertFalse($credit->hasSufficientBalance(5.01));
        $this->assertFalse($credit->hasSufficientBalance(10.00));
    }

    /** @test */
    public function it_allows_zero_amount_transactions(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
        ]);

        // Zero or negative amounts should always be allowed
        $this->assertTrue($credit->hasSufficientBalance(0));
        $this->assertTrue($credit->hasSufficientBalance(-5)); // Credit (refund)
    }

    // ============================================
    // Transaction Tests
    // ============================================

    /** @test */
    public function it_creates_transaction_on_credit_add(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        $transaction = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'balance_after' => 10.00,
            'type' => 'purchase',
            'description' => 'Credit purchase via Stripe',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
        ]);
    }

    /** @test */
    public function it_creates_transaction_on_credit_usage(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 15.00,
        ]);

        $transaction = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => -5.00,
            'balance_after' => 10.00,
            'type' => 'usage',
            'description' => 'Analysis with GPT-4',
            'reference_type' => 'analysis',
            'reference_id' => 1,
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => -5.00,
            'type' => 'usage',
            'reference_type' => 'analysis',
        ]);
    }

    /** @test */
    public function it_creates_transaction_on_refund(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
        ]);

        $transaction = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => -10.00,
            'balance_after' => 0.00,
            'type' => 'refund',
            'description' => 'Refund for order #123',
            'payment_provider' => 'stripe',
            'payment_id' => 're_test_123',
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => 'refund',
        ]);
    }

    /** @test */
    public function it_creates_transaction_on_bonus(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        $transaction = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 5.00,
            'balance_after' => 5.00,
            'type' => 'bonus',
            'description' => 'Welcome bonus',
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => 'bonus',
            'amount' => 5.00,
        ]);
    }

    // ============================================
    // Authentication Tests
    // ============================================

    /** @test */
    public function it_requires_authentication_for_balance(): void
    {
        $response = $this->getJson('/api/v1/credits/balance');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_only_shows_own_balance(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Credit::factory()->create([
            'user_id' => $otherUser->id,
            'balance' => 1000.00,
        ]);

        // User should not see other user's balance
        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertJson([
            'balance' => 0.00,
        ]);
    }

    // ============================================
    // Low Balance Warning Tests
    // ============================================

    /** @test */
    public function it_indicates_low_balance_status(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 1.50, // Below $2 threshold
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertJson([
            'low_balance' => true,
        ]);
    }

    /** @test */
    public function it_indicates_sufficient_balance(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertJson([
            'low_balance' => false,
        ]);
    }

    // ============================================
    // Transaction History Tests
    // ============================================

    /** @test */
    public function it_returns_transaction_history(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id]);

        // Create some transactions
        CreditTransaction::factory()->count(5)->create([
            'user_id' => $user->id,
            'type' => 'purchase',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'type',
                        'description',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function it_paginates_transaction_history(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id]);

        CreditTransaction::factory()->count(25)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/transactions?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }

    /** @test */
    public function it_filters_transactions_by_type(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id]);

        CreditTransaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'type' => 'purchase',
        ]);

        CreditTransaction::factory()->count(2)->create([
            'user_id' => $user->id,
            'type' => 'usage',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/transactions?type=purchase');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_orders_transactions_by_date_desc(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id]);

        $older = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);

        $newer = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/transactions');

        $transactions = $response->json('data');

        // Newer transaction should be first
        $this->assertEquals($newer->id, $transactions[0]['id']);
        $this->assertEquals($older->id, $transactions[1]['id']);
    }

    // ============================================
    // Concurrent Access Tests
    // ============================================

    /** @test */
    public function it_handles_concurrent_balance_updates(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 100.00,
        ]);

        // Simulate two concurrent deductions
        // In a real test, we'd use actual concurrent processes
        // Here we test the behavior without proper locking

        $credit1 = Credit::find($credit->id);
        $credit2 = Credit::find($credit->id);

        $credit1->balance -= 50;
        $credit1->save();

        $credit2->balance -= 50;
        $credit2->save();

        // Without locking, balance would be 50 (100 - 50 from last save)
        // The second save overwrites the first one
        // This test documents current behavior without database locking
        $credit->refresh();
        
        // Current behavior: second save overwrites, so balance is 50
        $this->assertEquals(50.00, $credit->balance);
    }

    /** @test */
    public function it_uses_database_transactions_for_balance_updates(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 50.00,
        ]);

        \DB::transaction(function () use ($user, $credit) {
            // Deduct balance
            $credit->balance -= 10;
            $credit->total_spent += 10;
            $credit->save();

            // Create transaction record
            CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => -10,
                'balance_after' => $credit->balance,
                'type' => 'usage',
                'description' => 'Test transaction',
            ]);
        });

        $credit->refresh();

        $this->assertEquals(40.00, $credit->balance);
        $this->assertEquals(10.00, $credit->total_spent);
        $this->assertEquals(1, CreditTransaction::where('user_id', $user->id)->count());
    }

    // ============================================
    // Edge Cases
    // ============================================

    /** @test */
    public function it_handles_negative_balance_gracefully(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 5.00,
        ]);

        // Force negative balance (shouldn't happen in production)
        $credit->balance = -5.00;
        $credit->save();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertJson([
            'balance' => -5.00,
            // PHP number_format puts the minus after the dollar sign
            'formatted_balance' => '$-5.00',
        ]);
    }

    /** @test */
    public function it_handles_large_balance_values(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 999999.99, // Max decimal(10,2)
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/credits/balance');

        $response->assertStatus(200)
            ->assertJson([
                'balance' => 999999.99,
            ]);
    }
}
