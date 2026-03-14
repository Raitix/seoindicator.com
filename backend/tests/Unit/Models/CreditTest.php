<?php

namespace Tests\Unit\Models;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * @Task TASK-26-2
 * @Description Credit Model Unit Tests
 * @Tests 65+
 */
class CreditTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Instantiation Tests (CM-001 to CM-010)
    // ==========================================

    /** @test CM-001 */
    public function create_with_required_fields(): void
    {
        $user = User::factory()->create();

        $credit = Credit::create([
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('credits', [
            'id' => $credit->id,
            'user_id' => $user->id,
        ]);
        $this->assertEquals(0.0, $credit->balance);
        $this->assertEquals(0.0, $credit->total_purchased);
        $this->assertEquals(0.0, $credit->total_spent);
    }

    /** @test CM-002 */
    public function create_with_all_fields(): void
    {
        $user = User::factory()->create();

        $credit = Credit::create([
            'user_id' => $user->id,
            'balance' => 150.50,
            'total_purchased' => 200.00,
            'total_spent' => 49.50,
        ]);

        $this->assertEquals(150.50, $credit->balance);
        $this->assertEquals(200.00, $credit->total_purchased);
        $this->assertEquals(49.50, $credit->total_spent);
    }

    /** @test CM-003 */
    public function mass_assignment_protects_id(): void
    {
        $credit = Credit::factory()->create();
        $originalId = $credit->id;

        $credit->fill(['id' => 999999]);

        $this->assertEquals($originalId, $credit->id);
    }

    /** @test CM-004 */
    public function factory_creates_valid_model(): void
    {
        $credit = Credit::factory()->create();

        $this->assertNotNull($credit->id);
        $this->assertNotNull($credit->user_id);
    }

    /** @test CM-005 */
    public function factory_default_state(): void
    {
        $credit = Credit::factory()->create();

        $this->assertEquals(0.0, $credit->balance);
        $this->assertEquals(0.0, $credit->total_purchased);
        $this->assertEquals(0.0, $credit->total_spent);
    }

    /** @test CM-006 */
    public function factory_with_balance(): void
    {
        $credit = Credit::factory()->withBalance(100.50)->create();

        $this->assertEquals(100.50, $credit->balance);
    }

    /** @test CM-007 */
    public function factory_with_purchases(): void
    {
        $credit = Credit::factory()->withPurchased(500.00)->create();

        $this->assertEquals(500.00, $credit->total_purchased);
    }

    /** @test CM-008 */
    public function factory_with_spending(): void
    {
        $credit = Credit::factory()->withSpent(200.00)->create();

        $this->assertEquals(200.00, $credit->total_spent);
    }

    /** @test CM-009 */
    public function unique_user_constraint(): void
    {
        $user = User::factory()->create();
        Credit::factory()->forUser($user)->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Credit::create(['user_id' => $user->id]);
    }

    /** @test CM-010 */
    public function cascade_delete_on_user_delete(): void
    {
        $credit = Credit::factory()->create();
        $userId = $credit->user_id;

        $credit->user->delete();

        $this->assertDatabaseMissing('credits', ['user_id' => $userId]);
    }

    // ==========================================
    // Relationship Tests (CR-001 to CR-006)
    // ==========================================

    /** @test CR-001 */
    public function belongs_to_user(): void
    {
        $credit = Credit::factory()->create();

        $this->assertInstanceOf(User::class, $credit->user);
        $this->assertEquals($credit->user_id, $credit->user->id);
    }

    /** @test CR-002 */
    public function has_many_transactions(): void
    {
        $credit = Credit::factory()->create();
        CreditTransaction::factory()->count(3)->forUser($credit->user)->create();

        $this->assertCount(3, $credit->transactions);
        $this->assertInstanceOf(CreditTransaction::class, $credit->transactions->first());
    }

    /** @test CR-003 */
    public function user_relationship_loaded(): void
    {
        $credit = Credit::factory()->create();
        $credit = Credit::with('user')->find($credit->id);

        $this->assertTrue($credit->relationLoaded('user'));
    }

    /** @test CR-004 */
    public function transactions_relationship_loaded(): void
    {
        $credit = Credit::factory()->create();
        CreditTransaction::factory()->count(2)->forUser($credit->user)->create();

        $credit = Credit::with('transactions')->find($credit->id);

        $this->assertTrue($credit->relationLoaded('transactions'));
    }

    /** @test CR-005 */
    public function create_transaction_via_relationship(): void
    {
        $credit = Credit::factory()->create();

        $transaction = $credit->transactions()->create([
            'user_id' => $credit->user_id,
            'amount' => 50.00,
            'balance_after' => $credit->balance + 50.00,
            'type' => 'purchase',
            'description' => 'Test transaction',
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'id' => $transaction->id,
            'user_id' => $credit->user_id,
        ]);
    }

    /** @test CR-006 */
    public function delete_credit_does_not_cascade_to_transactions(): void
    {
        $credit = Credit::factory()->create();
        $transaction = CreditTransaction::factory()->forUser($credit->user)->create();
        $transactionId = $transaction->id;

        $credit->delete();

        // Transactions should remain independent (no cascade)
        $this->assertDatabaseHas('credit_transactions', ['id' => $transactionId]);
    }

    // ==========================================
    // Casts Tests (CC-001 to CC-008)
    // ==========================================

    /** @test CC-001 */
    public function balance_cast_to_decimal(): void
    {
        $credit = Credit::factory()->withBalance(100.50)->create();

        // Laravel's decimal cast returns string for precision
        $this->assertEquals('100.50', $credit->balance);
        // When used in calculations, it's cast to float
        $this->assertEquals(100.50, (float) $credit->balance);
    }

    /** @test CC-002 */
    public function total_purchased_cast_to_decimal(): void
    {
        $credit = Credit::factory()->withPurchased(250.00)->create();

        // Laravel's decimal cast returns string for precision
        $this->assertEquals('250.00', $credit->total_purchased);
        $this->assertEquals(250.00, (float) $credit->total_purchased);
    }

    /** @test CC-003 */
    public function total_spent_cast_to_decimal(): void
    {
        $credit = Credit::factory()->withSpent(75.25)->create();

        // Laravel's decimal cast returns string for precision
        $this->assertEquals('75.25', $credit->total_spent);
        $this->assertEquals(75.25, (float) $credit->total_spent);
    }

    /** @test CC-004 */
    public function balance_precision(): void
    {
        $credit = Credit::create([
            'user_id' => User::factory()->create()->id,
            'balance' => 123.456,
        ]);

        // DECIMAL(10,2) rounds to 2 decimal places
        $this->assertEqualsWithDelta(123.46, $credit->fresh()->balance, 0.001);
    }

    /** @test CC-005 */
    public function zero_balance(): void
    {
        $credit = Credit::factory()->withBalance(0.00)->create();

        $this->assertEquals(0.0, $credit->balance);
    }

    /** @test CC-006 */
    public function large_balance(): void
    {
        $credit = Credit::factory()->withBalance(99999999.99)->create();

        $this->assertEquals(99999999.99, $credit->balance);
    }

    /** @test CC-007 */
    public function negative_balance(): void
    {
        $credit = Credit::create([
            'user_id' => User::factory()->create()->id,
            'balance' => -50.00,
        ]);

        $this->assertEquals(-50.00, $credit->balance);
    }

    /** @test CC-008 */
    public function created_at_cast_to_datetime(): void
    {
        $credit = Credit::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $credit->created_at);
    }

    // ==========================================
    // Accessor Tests (CA-001 to CA-008)
    // ==========================================

    /** @test CA-001 */
    public function formatted_balance_accessor(): void
    {
        $credit = Credit::factory()->withBalance(1234.50)->create();

        $this->assertEquals('$1,234.50', $credit->formatted_balance);
    }

    /** @test CA-002 */
    public function formatted_balance_zero(): void
    {
        $credit = Credit::factory()->withBalance(0.00)->create();

        $this->assertEquals('$0.00', $credit->formatted_balance);
    }

    /** @test CA-003 */
    public function formatted_balance_decimal(): void
    {
        $credit = Credit::factory()->withBalance(0.50)->create();

        $this->assertEquals('$0.50', $credit->formatted_balance);
    }

    /** @test CA-004 */
    public function formatted_balance_large(): void
    {
        $credit = Credit::factory()->withBalance(1000000.00)->create();

        $this->assertEquals('$1,000,000.00', $credit->formatted_balance);
    }

    /** @test CA-005 */
    public function formatted_total_purchased_accessor(): void
    {
        $credit = Credit::factory()->withPurchased(500.00)->create();

        $this->assertEquals('$500.00', $credit->formatted_total_purchased);
    }

    /** @test CA-006 */
    public function formatted_total_spent_accessor(): void
    {
        $credit = Credit::factory()->withSpent(250.00)->create();

        $this->assertEquals('$250.00', $credit->formatted_total_spent);
    }

    /** @test CA-007 */
    public function accessor_exists(): void
    {
        $credit = Credit::factory()->create();

        $this->assertIsString($credit->formatted_balance);
    }

    /** @test CA-008 */
    public function accessor_with_negative_balance(): void
    {
        $credit = Credit::create([
            'user_id' => User::factory()->create()->id,
            'balance' => -10.00,
        ]);

        $this->assertEquals('-$10.00', $credit->formatted_balance);
    }

    // ==========================================
    // Balance Check Method Tests (CB-001 to CB-010)
    // ==========================================

    /** @test CB-001 */
    public function has_sufficient_balance(): void
    {
        $credit = Credit::factory()->withBalance(100.00)->create();

        $this->assertTrue($credit->hasSufficientBalance(50.00));
    }

    /** @test CB-002 */
    public function has_exact_balance(): void
    {
        $credit = Credit::factory()->withBalance(100.00)->create();

        $this->assertTrue($credit->hasSufficientBalance(100.00));
    }

    /** @test CB-003 */
    public function insufficient_balance(): void
    {
        $credit = Credit::factory()->withBalance(50.00)->create();

        $this->assertFalse($credit->hasSufficientBalance(100.00));
    }

    /** @test CB-004 */
    public function zero_balance_check_zero(): void
    {
        $credit = Credit::factory()->withBalance(0.00)->create();

        $this->assertTrue($credit->hasSufficientBalance(0.00));
    }

    /** @test CB-005 */
    public function zero_balance_check_positive(): void
    {
        $credit = Credit::factory()->withBalance(0.00)->create();

        $this->assertFalse($credit->hasSufficientBalance(1.00));
    }

    /** @test CB-006 */
    public function negative_balance_check(): void
    {
        $credit = Credit::create([
            'user_id' => User::factory()->create()->id,
            'balance' => -10.00,
        ]);

        $this->assertFalse($credit->hasSufficientBalance(5.00));
    }

    /** @test CB-007 */
    public function check_negative_amount(): void
    {
        $credit = Credit::factory()->withBalance(100.00)->create();

        // Negative check means refund/credit - always allowed
        $this->assertTrue($credit->hasSufficientBalance(-10.00));
    }

    /** @test CB-008 */
    public function check_decimal_amount(): void
    {
        $credit = Credit::factory()->withBalance(100.50)->create();

        $this->assertTrue($credit->hasSufficientBalance(50.25));
    }

    /** @test CB-009 */
    public function check_with_decimal_precision(): void
    {
        $credit = Credit::factory()->withBalance(100.00)->create();

        $this->assertTrue($credit->hasSufficientBalance(99.99));
    }

    /** @test CB-010 */
    public function check_amount_zero(): void
    {
        $credit = Credit::factory()->withBalance(100.00)->create();

        $this->assertTrue($credit->hasSufficientBalance(0.00));
    }

    // ==========================================
    // Factory State Tests (CF-001 to CF-006)
    // ==========================================

    /** @test CF-001 */
    public function factory_default_state_values(): void
    {
        $credit = Credit::factory()->create();

        $this->assertEquals(0.0, $credit->balance);
        $this->assertEquals(0.0, $credit->total_purchased);
        $this->assertEquals(0.0, $credit->total_spent);
    }

    /** @test CF-002 */
    public function factory_with_balance_state(): void
    {
        $credit = Credit::factory()->withBalance(100.00)->create();

        $this->assertEquals(100.00, $credit->balance);
    }

    /** @test CF-003 */
    public function factory_with_purchased_state(): void
    {
        $credit = Credit::factory()->withPurchased(500.00)->create();

        $this->assertEquals(500.00, $credit->total_purchased);
    }

    /** @test CF-004 */
    public function factory_with_spent_state(): void
    {
        $credit = Credit::factory()->withSpent(200.00)->create();

        $this->assertEquals(200.00, $credit->total_spent);
    }

    /** @test CF-005 */
    public function factory_for_user_state(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $credit->user_id);
    }

    /** @test CF-006 */
    public function factory_combined_states(): void
    {
        $credit = Credit::factory()
            ->withBalance(100.00)
            ->withPurchased(200.00)
            ->create();

        $this->assertEquals(100.00, $credit->balance);
        $this->assertEquals(200.00, $credit->total_purchased);
    }

    // ==========================================
    // Edge Cases (CE-001 to CE-008)
    // ==========================================

    /** @test CE-001 */
    public function very_large_balance(): void
    {
        $credit = Credit::factory()->withBalance(99999999.99)->create();
        $credit->refresh();

        $this->assertEquals(99999999.99, $credit->balance);
    }

    /** @test CE-002 */
    public function negative_balance_stored(): void
    {
        $credit = Credit::create([
            'user_id' => User::factory()->create()->id,
            'balance' => -1000.00,
        ]);

        $this->assertEquals(-1000.00, $credit->balance);
    }

    /** @test CE-003 */
    public function balance_update_changes_updated_at(): void
    {
        $credit = Credit::factory()->create();
        $originalUpdatedAt = $credit->updated_at;

        sleep(1); // Ensure time difference
        $credit->update(['balance' => 100.00]);

        $this->assertNotEquals($originalUpdatedAt, $credit->fresh()->updated_at);
    }

    /** @test CE-004 */
    public function balance_string_to_float(): void
    {
        $credit = Credit::create([
            'user_id' => User::factory()->create()->id,
            'balance' => "100.50",
        ]);

        // Laravel's decimal cast returns string for precision
        $this->assertIsString($credit->balance);
        $this->assertEquals('100.50', $credit->balance);
        // When cast to float for calculations
        $this->assertEquals(100.50, (float) $credit->balance);
    }

    /** @test CE-005 */
    public function null_user_id_throws_exception(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Credit::create(['user_id' => null]);
    }

    /** @test CE-006 */
    public function multiple_transactions_linked_correctly(): void
    {
        $credit = Credit::factory()->create();
        
        for ($i = 0; $i < 10; $i++) {
            CreditTransaction::factory()
                ->forUser($credit->user)
                ->create();
        }

        $this->assertCount(10, $credit->transactions);
    }

    /** @test CE-007 */
    public function no_transactions_returns_empty_collection(): void
    {
        $credit = Credit::factory()->create();

        $this->assertCount(0, $credit->transactions);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $credit->transactions);
    }

    /** @test CE-008 */
    public function refresh_model(): void
    {
        $credit = Credit::factory()->create();
        
        // Direct DB update
        \DB::table('credits')->where('id', $credit->id)->update(['balance' => 500.00]);

        $credit->refresh();

        $this->assertEquals(500.00, $credit->balance);
    }

    // ==========================================
    // Security Tests (CS-001 to CS-005)
    // ==========================================

    /** @test CS-001 */
    public function mass_assignment_balance_allowed(): void
    {
        $credit = Credit::factory()->create();
        $originalBalance = $credit->balance;

        $credit->fill(['balance' => 1000.00]);

        $this->assertEquals(1000.00, $credit->balance);
    }

    /** @test CS-002 */
    public function mass_assignment_id_not_modifiable(): void
    {
        $credit = Credit::factory()->create();
        $originalId = $credit->id;

        // Attempting to change id via fill should not work
        $credit->fill(['id' => 999999]);

        $this->assertEquals($originalId, $credit->id);
    }

    /** @test CS-004 */
    public function balance_overflow(): void
    {
        // DECIMAL(10,2) max is 99999999.99
        // SQLite doesn't enforce DECIMAL constraints, so skip on SQLite
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('SQLite does not enforce DECIMAL constraints');
        }

        $this->expectException(\Illuminate\Database\QueryException::class);

        Credit::create([
            'user_id' => User::factory()->create()->id,
            'balance' => 999999999.99, // Exceeds max
        ]);
    }

    /** @test CS-005 */
    public function concurrent_balance_updates(): void
    {
        $credit = Credit::factory()->withBalance(100.00)->create();
        $creditId = $credit->id;

        // Simulate two concurrent updates (last write wins)
        \DB::table('credits')->where('id', $creditId)->update(['balance' => 150.00]);
        \DB::table('credits')->where('id', $creditId)->update(['balance' => 200.00]);

        $credit->refresh();

        $this->assertEquals(200.00, $credit->balance);
    }

    // ==========================================
    // Schema Tests
    // ==========================================

    /** @test */
    public function table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('credits', 'id'));
        $this->assertTrue(Schema::hasColumn('credits', 'user_id'));
        $this->assertTrue(Schema::hasColumn('credits', 'balance'));
        $this->assertTrue(Schema::hasColumn('credits', 'total_purchased'));
        $this->assertTrue(Schema::hasColumn('credits', 'total_spent'));
        $this->assertTrue(Schema::hasColumn('credits', 'created_at'));
        $this->assertTrue(Schema::hasColumn('credits', 'updated_at'));
    }

    /** @test */
    public function user_id_has_unique_index(): void
    {
        // SQLite doesn't support SHOW INDEX, use alternative approach
        if (config('database.default') === 'sqlite') {
            // Verify uniqueness by attempting to create duplicate
            $user = User::factory()->create();
            Credit::factory()->forUser($user)->create();

            $this->expectException(\Illuminate\Database\QueryException::class);
            Credit::create(['user_id' => $user->id]);
        } else {
            // MySQL: use SHOW INDEX
            $indexes = \DB::select("SHOW INDEX FROM credits WHERE Column_name = 'user_id'");
            $hasUnique = collect($indexes)->contains('Non_unique', 0);
            
            $this->assertTrue($hasUnique, 'user_id should have a unique index');
        }
    }
}
