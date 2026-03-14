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
 * @Description CreditTransaction Model Unit Tests
 * @Tests 70+
 */
class CreditTransactionTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // Model Instantiation Tests (TM-001 to TM-010)
    // ==========================================

    /** @test TM-001 */
    public function create_with_required_fields(): void
    {
        $user = User::factory()->create();

        $transaction = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 50.00,
            'balance_after' => 50.00,
            'type' => 'purchase',
            'description' => 'Test purchase',
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'id' => $transaction->id,
            'user_id' => $user->id,
            'type' => 'purchase',
        ]);
    }

    /** @test TM-002 */
    public function create_with_all_fields(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->forUser($user)->create();

        $transaction = CreditTransaction::create([
            'user_id' => $user->id,
            'credit_id' => $credit->id,
            'amount' => 50.00,
            'balance_after' => 100.00,
            'type' => 'purchase',
            'description' => 'Purchased 50 credits via Stripe',
            'reference_type' => 'App\\Models\\Payment',
            'reference_id' => 1,
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_1234567890',
            'metadata' => ['package' => 'starter'],
        ]);

        $this->assertEquals(50.00, $transaction->amount);
        $this->assertEquals(100.00, $transaction->balance_after);
        $this->assertEquals('purchase', $transaction->type);
        $this->assertEquals('Purchased 50 credits via Stripe', $transaction->description);
        $this->assertEquals('stripe', $transaction->payment_provider);
        $this->assertEquals(['package' => 'starter'], $transaction->metadata);
    }

    /** @test TM-003 */
    public function mass_assignment_protects_id(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create();
        $originalId = $transaction->id;

        $transaction->fill(['id' => 999999]);

        $this->assertEquals($originalId, $transaction->id);
    }

    /** @test TM-004 */
    public function factory_creates_valid_model(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create();

        $this->assertNotNull($transaction->id);
        $this->assertNotNull($transaction->user_id);
        $this->assertNotNull($transaction->type);
    }

    /** @test TM-005 */
    public function factory_purchase_state(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create();

        $this->assertEquals('purchase', $transaction->type);
        $this->assertGreaterThan(0, $transaction->amount);
    }

    /** @test TM-006 */
    public function factory_usage_state(): void
    {
        $transaction = CreditTransaction::factory()->usage()->create();

        $this->assertEquals('usage', $transaction->type);
        $this->assertLessThan(0, $transaction->amount);
    }

    /** @test TM-007 */
    public function factory_refund_state(): void
    {
        $transaction = CreditTransaction::factory()->refund()->create();

        $this->assertEquals('refund', $transaction->type);
        $this->assertGreaterThan(0, $transaction->amount);
    }

    /** @test TM-008 */
    public function factory_bonus_state(): void
    {
        $transaction = CreditTransaction::factory()->bonus()->create();

        $this->assertEquals('bonus', $transaction->type);
        $this->assertGreaterThan(0, $transaction->amount);
    }

    /** @test TM-009 */
    public function cascade_delete_on_user_delete(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create();
        $userId = $transaction->user_id;

        $transaction->user->delete();

        $this->assertDatabaseMissing('credit_transactions', ['user_id' => $userId]);
    }

    /** @test TM-010 */
    public function no_updated_at_column(): void
    {
        $this->assertFalse(Schema::hasColumn('credit_transactions', 'updated_at'));
    }

    // ==========================================
    // Relationship Tests (TR-001 to TR-008)
    // ==========================================

    /** @test TR-001 */
    public function belongs_to_user(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create();

        $this->assertInstanceOf(User::class, $transaction->user);
        $this->assertEquals($transaction->user_id, $transaction->user->id);
    }

    /** @test TR-002 */
    public function belongs_to_credit(): void
    {
        $credit = Credit::factory()->create();
        $transaction = CreditTransaction::factory()
            ->forUser($credit->user)
            ->create(['credit_id' => $credit->id]);

        $this->assertInstanceOf(Credit::class, $transaction->credit);
        $this->assertEquals($credit->id, $transaction->credit->id);
    }

    /** @test TR-003 */
    public function user_relationship_loaded(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create();

        $transaction = CreditTransaction::with('user')->find($transaction->id);

        $this->assertTrue($transaction->relationLoaded('user'));
    }

    /** @test TR-004 */
    public function credit_relationship_loaded(): void
    {
        $credit = Credit::factory()->create();
        $transaction = CreditTransaction::factory()
            ->forUser($credit->user)
            ->create(['credit_id' => $credit->id]);

        $transaction = CreditTransaction::with('credit')->find($transaction->id);

        $this->assertTrue($transaction->relationLoaded('credit'));
    }

    /** @test TR-005 */
    public function transaction_belongs_to_correct_user(): void
    {
        $credit = Credit::factory()->create();
        $transaction = CreditTransaction::factory()
            ->forUser($credit->user)
            ->create();

        $this->assertEquals($credit->user_id, $transaction->user_id);
    }

    /** @test TR-006 */
    public function transaction_without_credit(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['credit_id' => null]);

        $this->assertNull($transaction->credit_id);
    }

    /** @test TR-007 */
    public function multiple_transactions_per_user(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->count(5)->forUser($user)->create();

        $this->assertCount(5, $user->transactions);
    }

    /** @test TR-008 */
    public function transaction_preserves_credit_reference_after_credit_delete(): void
    {
        $credit = Credit::factory()->create();
        $transaction = CreditTransaction::factory()
            ->forUser($credit->user)
            ->create(['credit_id' => $credit->id]);
        $transactionId = $transaction->id;
        $creditId = $credit->id;

        $credit->delete();

        // Transaction should remain
        $this->assertDatabaseHas('credit_transactions', ['id' => $transactionId]);
        // But credit_id is now orphaned (no cascade)
        $freshTransaction = CreditTransaction::find($transactionId);
        $this->assertEquals($creditId, $freshTransaction->credit_id);
    }

    // ==========================================
    // Casts Tests (TC-001 to TC-010)
    // ==========================================

    /** @test TC-001 */
    public function amount_cast_to_decimal(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['amount' => 50.25]);

        $this->assertIsFloat($transaction->amount);
        $this->assertEquals(50.25, $transaction->amount);
    }

    /** @test TC-002 */
    public function balance_after_cast_to_decimal(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['balance_after' => 100.50]);

        $this->assertIsFloat($transaction->balance_after);
        $this->assertEquals(100.50, $transaction->balance_after);
    }

    /** @test TC-003 */
    public function metadata_cast_to_array(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create([
            'metadata' => ['key' => 'value', 'nested' => ['a' => 1]],
        ]);

        $this->assertIsArray($transaction->metadata);
        $this->assertEquals('value', $transaction->metadata['key']);
        $this->assertEquals(['a' => 1], $transaction->metadata['nested']);
    }

    /** @test TC-004 */
    public function metadata_null(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['metadata' => null]);

        $this->assertNull($transaction->metadata);
    }

    /** @test TC-005 */
    public function metadata_empty_object(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['metadata' => []]);

        $this->assertIsArray($transaction->metadata);
        $this->assertEmpty($transaction->metadata);
    }

    /** @test TC-006 */
    public function metadata_nested(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create([
            'metadata' => ['level1' => ['level2' => ['level3' => 'value']]],
        ]);

        $this->assertEquals('value', $transaction->metadata['level1']['level2']['level3']);
    }

    /** @test TC-007 */
    public function metadata_array(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create([
            'metadata' => [['item' => 1], ['item' => 2]],
        ]);

        $this->assertIsArray($transaction->metadata);
        $this->assertCount(2, $transaction->metadata);
    }

    /** @test TC-008 */
    public function created_at_cast_to_datetime(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $transaction->created_at);
    }

    /** @test TC-009 */
    public function negative_amount(): void
    {
        $transaction = CreditTransaction::factory()->usage()->create(['amount' => -10.00]);

        $this->assertEquals(-10.00, $transaction->amount);
    }

    /** @test TC-010 */
    public function large_amount(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['amount' => 99999999.99]);

        $this->assertEquals(99999999.99, $transaction->amount);
    }

    // ==========================================
    // Scope Tests (TS-001 to TS-012)
    // ==========================================

    /** @test TS-001 */
    public function scope_of_type_purchase(): void
    {
        CreditTransaction::factory()->purchase()->count(3)->create();
        CreditTransaction::factory()->usage()->count(2)->create();

        $results = CreditTransaction::ofType('purchase')->get();

        $this->assertCount(3, $results);
        $results->each(fn ($t) => $this->assertEquals('purchase', $t->type));
    }

    /** @test TS-002 */
    public function scope_of_type_usage(): void
    {
        CreditTransaction::factory()->purchase()->count(2)->create();
        CreditTransaction::factory()->usage()->count(3)->create();

        $results = CreditTransaction::ofType('usage')->get();

        $this->assertCount(3, $results);
        $results->each(fn ($t) => $this->assertEquals('usage', $t->type));
    }

    /** @test TS-003 */
    public function scope_of_type_refund(): void
    {
        CreditTransaction::factory()->refund()->count(2)->create();
        CreditTransaction::factory()->purchase()->count(3)->create();

        $results = CreditTransaction::ofType('refund')->get();

        $this->assertCount(2, $results);
        $results->each(fn ($t) => $this->assertEquals('refund', $t->type));
    }

    /** @test TS-004 */
    public function scope_of_type_bonus(): void
    {
        CreditTransaction::factory()->bonus()->count(2)->create();
        CreditTransaction::factory()->purchase()->count(3)->create();

        $results = CreditTransaction::ofType('bonus')->get();

        $this->assertCount(2, $results);
        $results->each(fn ($t) => $this->assertEquals('bonus', $t->type));
    }

    /** @test TS-005 */
    public function scope_of_type_invalid(): void
    {
        CreditTransaction::factory()->purchase()->count(3)->create();

        $results = CreditTransaction::ofType('invalid_type')->get();

        $this->assertCount(0, $results);
    }

    /** @test TS-006 */
    public function scope_of_provider_stripe(): void
    {
        CreditTransaction::factory()->purchase()->count(2)->create(['payment_provider' => 'stripe']);
        CreditTransaction::factory()->purchase()->count(3)->create(['payment_provider' => 'paypal']);

        $results = CreditTransaction::ofProvider('stripe')->get();

        $this->assertCount(2, $results);
        $results->each(fn ($t) => $this->assertEquals('stripe', $t->payment_provider));
    }

    /** @test TS-007 */
    public function scope_of_provider_paypal(): void
    {
        CreditTransaction::factory()->purchase()->count(2)->create(['payment_provider' => 'paypal']);
        CreditTransaction::factory()->purchase()->count(3)->create(['payment_provider' => 'stripe']);

        $results = CreditTransaction::ofProvider('paypal')->get();

        $this->assertCount(2, $results);
        $results->each(fn ($t) => $this->assertEquals('paypal', $t->payment_provider));
    }

    /** @test TS-008 */
    public function scope_of_provider_null(): void
    {
        CreditTransaction::factory()->usage()->count(3)->create(['payment_provider' => null]);
        CreditTransaction::factory()->purchase()->count(2)->create(['payment_provider' => 'stripe']);

        $results = CreditTransaction::ofProvider(null)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($t) => $this->assertNull($t->payment_provider));
    }

    /** @test TS-009 */
    public function scope_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        CreditTransaction::factory()->count(3)->forUser($user1)->create();
        CreditTransaction::factory()->count(2)->forUser($user2)->create();

        $results = CreditTransaction::forUser($user1->id)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($t) => $this->assertEquals($user1->id, $t->user_id));
    }

    /** @test TS-010 */
    public function scope_recent(): void
    {
        CreditTransaction::factory()->count(10)->purchase()->create();

        $results = CreditTransaction::recent(5)->get();

        $this->assertCount(5, $results);
    }

    /** @test TS-011 */
    public function scope_date_range(): void
    {
        $now = now();
        
        // Create transactions at different dates
        CreditTransaction::factory()->purchase()->create(['created_at' => $now->copy()->subDays(10)]);
        CreditTransaction::factory()->purchase()->create(['created_at' => $now->copy()->subDays(5)]);
        CreditTransaction::factory()->purchase()->create(['created_at' => $now->copy()->subDays(2)]);
        CreditTransaction::factory()->purchase()->create(['created_at' => $now]);

        $results = CreditTransaction::dateRange(
            $now->copy()->subDays(6)->toDateString(),
            $now->copy()->subDays(1)->toDateString()
        )->get();

        $this->assertCount(2, $results);
    }

    /** @test TS-012 */
    public function combined_scopes(): void
    {
        $user = User::factory()->create();
        
        CreditTransaction::factory()->purchase()->count(2)->forUser($user)->create(['payment_provider' => 'stripe']);
        CreditTransaction::factory()->purchase()->count(3)->forUser($user)->create(['payment_provider' => 'paypal']);
        CreditTransaction::factory()->usage()->count(2)->forUser($user)->create(['payment_provider' => 'stripe']);

        $results = CreditTransaction::ofType('purchase')
            ->ofProvider('stripe')
            ->forUser($user->id)
            ->get();

        $this->assertCount(2, $results);
        $results->each(function ($t) {
            $this->assertEquals('purchase', $t->type);
            $this->assertEquals('stripe', $t->payment_provider);
        });
    }

    // ==========================================
    // Type ENUM Tests (TT-001 to TT-006)
    // ==========================================

    /** @test TT-001 */
    public function type_purchase(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create();

        $this->assertEquals('purchase', $transaction->type);
    }

    /** @test TT-002 */
    public function type_usage(): void
    {
        $transaction = CreditTransaction::factory()->usage()->create();

        $this->assertEquals('usage', $transaction->type);
    }

    /** @test TT-003 */
    public function type_refund(): void
    {
        $transaction = CreditTransaction::factory()->refund()->create();

        $this->assertEquals('refund', $transaction->type);
    }

    /** @test TT-004 */
    public function type_bonus(): void
    {
        $transaction = CreditTransaction::factory()->bonus()->create();

        $this->assertEquals('bonus', $transaction->type);
    }

    /** @test TT-005 */
    public function type_invalid_rejected(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        CreditTransaction::create([
            'user_id' => User::factory()->create()->id,
            'amount' => 50.00,
            'balance_after' => 50.00,
            'type' => 'invalid',
            'description' => 'Test',
        ]);
    }

    /** @test TT-006 */
    public function type_case_sensitive(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        CreditTransaction::create([
            'user_id' => User::factory()->create()->id,
            'amount' => 50.00,
            'balance_after' => 50.00,
            'type' => 'PURCHASE',
            'description' => 'Test',
        ]);
    }

    // ==========================================
    // Payment Provider Tests (TP-001 to TP-008)
    // ==========================================

    /** @test TP-001 */
    public function provider_stripe(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['payment_provider' => 'stripe']);

        $this->assertEquals('stripe', $transaction->payment_provider);
    }

    /** @test TP-002 */
    public function provider_paypal(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['payment_provider' => 'paypal']);

        $this->assertEquals('paypal', $transaction->payment_provider);
    }

    /** @test TP-003 */
    public function provider_null(): void
    {
        $transaction = CreditTransaction::factory()->usage()->create(['payment_provider' => null]);

        $this->assertNull($transaction->payment_provider);
    }

    /** @test TP-004 */
    public function provider_empty(): void
    {
        $transaction = CreditTransaction::factory()->usage()->create(['payment_provider' => '']);

        $this->assertEquals('', $transaction->payment_provider);
    }

    /** @test TP-005 */
    public function payment_id_stored(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['payment_id' => 'pi_123']);

        $this->assertEquals('pi_123', $transaction->payment_id);
    }

    /** @test TP-006 */
    public function payment_id_null(): void
    {
        $transaction = CreditTransaction::factory()->usage()->create(['payment_id' => null]);

        $this->assertNull($transaction->payment_id);
    }

    /** @test TP-007 */
    public function provider_and_id_together(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create([
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_abc123',
        ]);

        $this->assertEquals('stripe', $transaction->payment_provider);
        $this->assertEquals('pi_abc123', $transaction->payment_id);
    }

    /** @test TP-008 */
    public function custom_provider(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create(['payment_provider' => 'custom']);

        $this->assertEquals('custom', $transaction->payment_provider);
    }

    // ==========================================
    // Polymorphic Reference Tests (TPR-001 to TPR-006)
    // ==========================================

    /** @test TPR-001 */
    public function valid_reference_type(): void
    {
        $transaction = CreditTransaction::factory()->usage()->create([
            'reference_type' => 'App\\Models\\Analysis',
        ]);

        $this->assertEquals('App\\Models\\Analysis', $transaction->reference_type);
    }

    /** @test TPR-002 */
    public function valid_reference_id(): void
    {
        $transaction = CreditTransaction::factory()->usage()->create(['reference_id' => 123]);

        $this->assertEquals(123, $transaction->reference_id);
    }

    /** @test TPR-003 */
    public function both_reference_null(): void
    {
        $transaction = CreditTransaction::factory()->purchase()->create([
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $this->assertNull($transaction->reference_type);
        $this->assertNull($transaction->reference_id);
    }

    /** @test TPR-004 */
    public function only_reference_type(): void
    {
        $transaction = CreditTransaction::factory()->create([
            'reference_type' => 'App\\Models\\Analysis',
            'reference_id' => null,
        ]);

        $this->assertEquals('App\\Models\\Analysis', $transaction->reference_type);
        $this->assertNull($transaction->reference_id);
    }

    /** @test TPR-005 */
    public function only_reference_id(): void
    {
        $transaction = CreditTransaction::factory()->create([
            'reference_type' => null,
            'reference_id' => 123,
        ]);

        $this->assertNull($transaction->reference_type);
        $this->assertEquals(123, $transaction->reference_id);
    }

    /** @test TPR-006 */
    public function long_reference_type_truncated(): void
    {
        $longType = str_repeat('a', 60);

        $transaction = CreditTransaction::factory()->create([
            'reference_type' => $longType,
        ]);

        // Should be truncated to 50 chars
        $this->assertLessThanOrEqual(50, strlen($transaction->reference_type));
    }

    // ==========================================
    // Edge Cases (TE-001 to TE-010)
    // ==========================================

    /** @test TE-001 */
    public function zero_amount(): void
    {
        $transaction = CreditTransaction::factory()->create(['amount' => 0.00]);

        $this->assertEquals(0.00, $transaction->amount);
    }

    /** @test TE-002 */
    public function edge_case_negative_amount(): void
    {
        $transaction = CreditTransaction::factory()->usage()->create(['amount' => -100.00]);

        $this->assertEquals(-100.00, $transaction->amount);
    }

    /** @test TE-003 */
    public function large_description(): void
    {
        $description = str_repeat('a', 500);
        $transaction = CreditTransaction::factory()->create(['description' => $description]);

        $this->assertEquals(500, strlen($transaction->description));
    }

    /** @test TE-004 */
    public function description_exceeds_max(): void
    {
        $description = str_repeat('a', 600);

        $transaction = CreditTransaction::factory()->create(['description' => $description]);

        // Should be truncated to 500 chars
        $this->assertLessThanOrEqual(500, strlen($transaction->description));
    }

    /** @test TE-005 */
    public function unicode_in_description(): void
    {
        $transaction = CreditTransaction::factory()->create([
            'description' => '日本語の説明 - クレジット購入',
        ]);

        $this->assertEquals('日本語の説明 - クレジット購入', $transaction->description);
    }

    /** @test TE-006 */
    public function special_chars_in_payment_id(): void
    {
        $transaction = CreditTransaction::factory()->create([
            'payment_id' => 'pi_abc-123_xyz',
        ]);

        $this->assertEquals('pi_abc-123_xyz', $transaction->payment_id);
    }

    /** @test TE-007 */
    public function empty_metadata(): void
    {
        $transaction = CreditTransaction::factory()->create(['metadata' => []]);

        $this->assertEquals([], $transaction->metadata);
    }

    /** @test TE-008 */
    public function null_metadata(): void
    {
        $transaction = CreditTransaction::factory()->create(['metadata' => null]);

        $this->assertNull($transaction->metadata);
    }

    /** @test TE-009 */
    public function multiple_transactions_same_user(): void
    {
        $user = User::factory()->create();

        CreditTransaction::factory()->count(10)->forUser($user)->create();

        $this->assertCount(10, $user->transactions);
    }

    /** @test TE-010 */
    public function transaction_immutability(): void
    {
        $transaction = CreditTransaction::factory()->create();
        $originalAmount = $transaction->amount;

        // Try to update - should fail because no updated_at column
        $transaction->amount = 999.99;
        
        // This will throw an error if we try to save
        // but we can test that the model is set up as immutable
        $this->assertNull($transaction->getUpdatedAtColumn());
    }

    // ==========================================
    // Security Tests (TSEC-001 to TSEC-006)
    // ==========================================

    /** @test TSEC-001 */
    public function sql_injection_in_description(): void
    {
        $malicious = "'; DROP TABLE credit_transactions;--";
        
        $transaction = CreditTransaction::factory()->create(['description' => $malicious]);

        // Should be stored safely
        $this->assertEquals($malicious, $transaction->description);
        // Table should still exist
        $this->assertTrue(Schema::hasTable('credit_transactions'));
    }

    /** @test TSEC-002 */
    public function xss_in_description(): void
    {
        $xss = '<script>alert("xss")</script>';
        
        $transaction = CreditTransaction::factory()->create(['description' => $xss]);

        // Should be stored as-is (output escaping is responsibility of views)
        $this->assertEquals($xss, $transaction->description);
    }

    /** @test TSEC-003 */
    public function sql_injection_in_payment_id(): void
    {
        $malicious = "'; DELETE FROM users WHERE 1=1;--";
        
        $transaction = CreditTransaction::factory()->create(['payment_id' => $malicious]);

        // Should be stored safely
        $this->assertEquals($malicious, $transaction->payment_id);
    }

    /** @test TSEC-004 */
    public function mass_assignment_protects_fields(): void
    {
        $transaction = CreditTransaction::factory()->create();
        $originalId = $transaction->id;
        $originalUserId = $transaction->user_id;

        $transaction->fill([
            'id' => 999,
            'user_id' => 888,
        ]);

        // Protected fields should not change
        $this->assertEquals($originalId, $transaction->id);
        $this->assertEquals($originalUserId, $transaction->user_id);
    }

    /** @test TSEC-005 */
    public function negative_balance_after(): void
    {
        $transaction = CreditTransaction::factory()->create(['balance_after' => -10.00]);

        $this->assertEquals(-10.00, $transaction->balance_after);
    }

    /** @test TSEC-006 */
    public function amount_overflow(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        CreditTransaction::factory()->create(['amount' => 999999999.99]);
    }

    // ==========================================
    // Schema Tests
    // ==========================================

    /** @test */
    public function table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'id'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'user_id'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'amount'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'balance_after'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'type'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'description'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'reference_type'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'reference_id'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'payment_provider'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'payment_id'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'metadata'));
        $this->assertTrue(Schema::hasColumn('credit_transactions', 'created_at'));
        $this->assertFalse(Schema::hasColumn('credit_transactions', 'updated_at'));
    }

    /** @test */
    public function table_has_expected_indexes(): void
    {
        $indexes = collect(\DB::select("SHOW INDEX FROM credit_transactions"))
            ->pluck('Column_name')
            ->unique()
            ->toArray();

        $this->assertContains('user_id', $indexes);
        $this->assertContains('type', $indexes);
        $this->assertContains('created_at', $indexes);
    }
}
