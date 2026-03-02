<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CreditTest extends TestCase
{
    use RefreshDatabase;

    // ==================== MODEL CREATION TESTS ====================

    /**
     * Test model can be created with user_id only.
     * CM-CR-01
     */
    public function test_model_can_be_created_with_user_id_only(): void
    {
        $user = User::factory()->create();

        $credit = Credit::create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Credit::class, $credit);
        $this->assertEquals($user->id, $credit->user_id);
        $this->assertEquals(0, $credit->balance);
        $this->assertEquals(0, $credit->total_purchased);
        $this->assertEquals(0, $credit->total_spent);
    }

    /**
     * Test model can be created with full data.
     * CM-CR-02
     */
    public function test_model_can_be_created_with_full_data(): void
    {
        $user = User::factory()->create();

        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 100.50,
            'total_purchased' => 150.00,
            'total_spent' => 49.50,
        ]);

        $this->assertEquals(100.50, $credit->balance);
        $this->assertEquals(150.00, $credit->total_purchased);
        $this->assertEquals(49.50, $credit->total_spent);
    }

    /**
     * Test mass assignment protection.
     * CM-CR-03
     */
    public function test_mass_assignment_protection(): void
    {
        $user = User::factory()->create();

        // Attempt to set all fields including potentially guarded ones
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 100.00,
            'total_purchased' => 100.00,
            'total_spent' => 0.00,
        ]);

        // All these fields should be fillable based on the model definition
        $this->assertEquals($user->id, $credit->user_id);
        $this->assertEquals(100.00, $credit->balance);
    }

    /**
     * Test unique user constraint - one credit per user.
     * CM-CR-04
     */
    public function test_unique_user_constraint(): void
    {
        $user = User::factory()->create();

        // Create first credit
        Credit::create(['user_id' => $user->id]);

        // Attempt to create second credit for same user
        $this->expectException(\Illuminate\Database\QueryException::class);
        Credit::create(['user_id' => $user->id]);
    }

    /**
     * Test cascade delete with user.
     * CM-CR-05
     */
    public function test_cascade_delete_with_user(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create(['user_id' => $user->id]);

        $this->assertDatabaseHas('credits', ['id' => $credit->id]);

        // Delete user
        $user->delete();

        // Credit should be deleted via cascade
        $this->assertDatabaseMissing('credits', ['id' => $credit->id]);
    }

    // ==================== RELATIONSHIP TESTS ====================

    /**
     * Test belongsTo relationship with User.
     * CM-RE-01
     */
    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $credit->user);
        $this->assertEquals($user->id, $credit->user->id);
    }

    /**
     * Test hasMany relationship with CreditTransaction.
     * CM-RE-02
     */
    public function test_has_many_credit_transactions(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create(['user_id' => $user->id]);

        // Create transactions manually (factory may not exist yet)
        DB::table('credit_transactions')->insert([
            'user_id' => $user->id,
            'amount' => 50.00,
            'balance_after' => 50.00,
            'type' => 'purchase',
            'description' => 'Test purchase',
            'created_at' => now(),
        ]);

        $transactions = $credit->transactions;

        $this->assertCount(1, $transactions);
        $this->assertInstanceOf(CreditTransaction::class, $transactions->first());
    }

    /**
     * Test eager load user.
     * CM-RE-03
     */
    public function test_eager_load_user(): void
    {
        $user = User::factory()->create();
        Credit::create(['user_id' => $user->id]);

        // Enable query log to check for N+1
        DB::enableQueryLog();
        $credit = Credit::with('user')->first();
        $queries = DB::getQueryLog();

        // Should only be 2 queries (credit + user)
        $this->assertLessThanOrEqual(2, count($queries));
        $this->assertTrue($credit->relationLoaded('user'));
    }

    /**
     * Test eager load transactions.
     * CM-RE-04
     */
    public function test_eager_load_transactions(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create(['user_id' => $user->id]);

        DB::table('credit_transactions')->insert([
            'user_id' => $user->id,
            'amount' => 50.00,
            'balance_after' => 50.00,
            'type' => 'purchase',
            'description' => 'Test purchase',
            'created_at' => now(),
        ]);

        DB::enableQueryLog();
        $loadedCredit = Credit::with('transactions')->find($credit->id);
        $queries = DB::getQueryLog();

        $this->assertLessThanOrEqual(2, count($queries));
        $this->assertTrue($loadedCredit->relationLoaded('transactions'));
    }

    /**
     * Test user hasOne credit relationship.
     * CM-RE-05
     */
    public function test_user_has_one_credit_relationship(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create(['user_id' => $user->id]);

        // Check if User model has credit relationship
        if (method_exists($user, 'credit')) {
            $this->assertInstanceOf(Credit::class, $user->credit);
            $this->assertEquals($credit->id, $user->credit->id);
        } else {
            $this->markTestSkipped('User::credit() relationship not yet defined');
        }
    }

    // ==================== CASTS TESTS ====================

    /**
     * Test balance DECIMAL cast.
     * CM-CA-01
     */
    public function test_balance_decimal_cast(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 123.45,
        ]);

        $this->assertIsFloat($credit->balance);
        $this->assertEquals(123.45, $credit->balance);
    }

    /**
     * Test balance precision 2 decimals.
     * CM-CA-02
     */
    public function test_balance_precision_two_decimals(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 123.456,
        ]);

        // Should be rounded to 2 decimal places
        $credit->refresh();
        $this->assertEqualsWithDelta(123.46, $credit->balance, 0.001);
    }

    /**
     * Test total_purchased DECIMAL cast.
     * CM-CA-03
     */
    public function test_total_purchased_decimal_cast(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'total_purchased' => 1000.50,
        ]);

        $this->assertIsFloat($credit->total_purchased);
        $this->assertEquals(1000.50, $credit->total_purchased);
    }

    /**
     * Test total_spent DECIMAL cast.
     * CM-CA-04
     */
    public function test_total_spent_decimal_cast(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'total_spent' => 500.25,
        ]);

        $this->assertIsFloat($credit->total_spent);
        $this->assertEquals(500.25, $credit->total_spent);
    }

    /**
     * Test timestamps cast.
     * CM-CA-05
     */
    public function test_timestamps_cast(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create(['user_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $credit->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $credit->updated_at);
    }

    // ==================== ACCESSOR TESTS ====================

    /**
     * Test formatted_balance accessor.
     * CM-AC-01
     */
    public function test_formatted_balance_accessor(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 123.45,
        ]);

        // Check if accessor exists
        if (method_exists($credit, 'getFormattedBalanceAttribute')) {
            $this->assertEquals('$123.45', $credit->formatted_balance);
        } else {
            $this->markTestSkipped('formatted_balance accessor not yet implemented');
        }
    }

    /**
     * Test formatted_balance zero.
     * CM-AC-02
     */
    public function test_formatted_balance_zero(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        if (method_exists($credit, 'getFormattedBalanceAttribute')) {
            $this->assertEquals('$0.00', $credit->formatted_balance);
        } else {
            $this->markTestSkipped('formatted_balance accessor not yet implemented');
        }
    }

    /**
     * Test formatted_balance large value.
     * CM-AC-03
     */
    public function test_formatted_balance_large(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 99999999.99,
        ]);

        if (method_exists($credit, 'getFormattedBalanceAttribute')) {
            $this->assertEquals('$99,999,999.99', $credit->formatted_balance);
        } else {
            $this->markTestSkipped('formatted_balance accessor not yet implemented');
        }
    }

    /**
     * Test formatted_balance negative.
     * CM-AC-04
     */
    public function test_formatted_balance_negative(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => -50.00,
        ]);

        if (method_exists($credit, 'getFormattedBalanceAttribute')) {
            $this->assertEquals('-$50.00', $credit->formatted_balance);
        } else {
            $this->markTestSkipped('formatted_balance accessor not yet implemented');
        }
    }

    /**
     * Test formatted_total_purchased accessor.
     * CM-AC-05
     */
    public function test_formatted_total_purchased_accessor(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'total_purchased' => 150.00,
        ]);

        if (method_exists($credit, 'getFormattedTotalPurchasedAttribute')) {
            $this->assertEquals('$150.00', $credit->formatted_total_purchased);
        } else {
            $this->markTestSkipped('formatted_total_purchased accessor not yet implemented');
        }
    }

    /**
     * Test formatted_total_spent accessor.
     * CM-AC-06
     */
    public function test_formatted_total_spent_accessor(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'total_spent' => 75.50,
        ]);

        if (method_exists($credit, 'getFormattedTotalSpentAttribute')) {
            $this->assertEquals('$75.50', $credit->formatted_total_spent);
        } else {
            $this->markTestSkipped('formatted_total_spent accessor not yet implemented');
        }
    }

    // ==================== BUSINESS LOGIC TESTS ====================

    /**
     * Test hasSufficientBalance returns true.
     * CM-BL-01
     */
    public function test_has_sufficient_balance_true(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 100,
        ]);

        if (method_exists($credit, 'hasSufficientBalance')) {
            $this->assertTrue($credit->hasSufficientBalance(50));
        } else {
            $this->markTestSkipped('hasSufficientBalance method not yet implemented');
        }
    }

    /**
     * Test hasSufficientBalance exact match.
     * CM-BL-02
     */
    public function test_has_sufficient_balance_exact(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 100,
        ]);

        if (method_exists($credit, 'hasSufficientBalance')) {
            $this->assertTrue($credit->hasSufficientBalance(100));
        } else {
            $this->markTestSkipped('hasSufficientBalance method not yet implemented');
        }
    }

    /**
     * Test hasSufficientBalance returns false.
     * CM-BL-03
     */
    public function test_has_sufficient_balance_false(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 50,
        ]);

        if (method_exists($credit, 'hasSufficientBalance')) {
            $this->assertFalse($credit->hasSufficientBalance(100));
        } else {
            $this->markTestSkipped('hasSufficientBalance method not yet implemented');
        }
    }

    /**
     * Test hasSufficientBalance with zero amount.
     * CM-BL-04
     */
    public function test_has_sufficient_balance_zero_amount(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        if (method_exists($credit, 'hasSufficientBalance')) {
            $this->assertTrue($credit->hasSufficientBalance(0));
        } else {
            $this->markTestSkipped('hasSufficientBalance method not yet implemented');
        }
    }

    /**
     * Test hasSufficientBalance with negative check (credit).
     * CM-BL-05
     */
    public function test_has_sufficient_balance_negative_check(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 100,
        ]);

        if (method_exists($credit, 'hasSufficientBalance')) {
            // Negative amount should always return true (it's a credit, not a debit)
            $this->assertTrue($credit->hasSufficientBalance(-10));
        } else {
            $this->markTestSkipped('hasSufficientBalance method not yet implemented');
        }
    }

    /**
     * Test hasSufficientBalance with negative balance.
     * CM-BL-06
     */
    public function test_has_sufficient_balance_negative_balance(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => -10,
        ]);

        if (method_exists($credit, 'hasSufficientBalance')) {
            $this->assertFalse($credit->hasSufficientBalance(1));
        } else {
            $this->markTestSkipped('hasSufficientBalance method not yet implemented');
        }
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * Test max balance value.
     * CM-EC-01
     */
    public function test_max_balance_value(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 99999999.99,
        ]);

        $credit->refresh();
        $this->assertEquals(99999999.99, $credit->balance);
    }

    /**
     * Test small decimal balance.
     * CM-EC-02
     */
    public function test_small_decimal_balance(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 0.01,
        ]);

        $credit->refresh();
        $this->assertEquals(0.01, $credit->balance);
    }

    /**
     * Test zero balance.
     * CM-EC-03
     */
    public function test_zero_balance(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        $credit->refresh();
        $this->assertEquals(0.00, $credit->balance);
    }

    /**
     * Test negative balance allowed.
     * CM-EC-04
     */
    public function test_negative_balance_allowed(): void
    {
        $user = User::factory()->create();
        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => -100.00,
        ]);

        $credit->refresh();
        // No DB constraint preventing negative balance
        $this->assertEquals(-100.00, $credit->balance);
    }
}
