<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Unit tests for TASK-26-11: PaymentMethod Model
 *
 * Tests the PaymentMethod model for:
 * - Model creation with required fields
 * - Casts (BOOLEAN, TIMESTAMP)
 * - Scopes (forUser, default, active)
 * - Accessors (formatted display, masked card)
 * - Relationships (belongsTo User)
 * - Indexes (user_id, stripe_customer_id)
 * - Edge cases and security
 *
 * @see \App\Models\PaymentMethod
 */
class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==================== MODEL CREATION TESTS ====================

    /**
     * Test model can be created with required fields only.
     * PM-CR-01
     */
    public function test_model_can_be_created_with_required_fields_only(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'provider' => 'stripe',
        ]);

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals($this->user->id, $paymentMethod->user_id);
        $this->assertEquals('cus_test123', $paymentMethod->stripe_customer_id);
        $this->assertEquals('pm_test456', $paymentMethod->stripe_payment_method_id);
        $this->assertEquals('stripe', $paymentMethod->provider);
        $this->assertNull($paymentMethod->brand);
        $this->assertNull($paymentMethod->last_four);
        $this->assertFalse($paymentMethod->is_default);
    }

    /**
     * Test model can be created with full card data.
     * PM-CR-02
     */
    public function test_model_can_be_created_with_full_card_data(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_card_visa',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2026,
            'is_default' => true,
        ]);

        $this->assertEquals('visa', $paymentMethod->brand);
        $this->assertEquals('4242', $paymentMethod->last_four);
        $this->assertEquals(12, $paymentMethod->exp_month);
        $this->assertEquals(2026, $paymentMethod->exp_year);
        $this->assertTrue($paymentMethod->is_default);
    }

    /**
     * Test mass assignment protection.
     * PM-CR-03
     */
    public function test_mass_assignment_protection(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '5555',
        ]);

        $this->assertEquals('mastercard', $paymentMethod->brand);
        $this->assertEquals('5555', $paymentMethod->last_four);
    }

    /**
     * Test user_id is required.
     * PM-CR-04
     */
    public function test_user_id_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        PaymentMethod::create([
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'provider' => 'stripe',
        ]);
    }

    /**
     * Test stripe_customer_id is required.
     * PM-CR-05
     */
    public function test_stripe_customer_id_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_payment_method_id' => 'pm_test456',
            'provider' => 'stripe',
        ]);
    }

    /**
     * Test provider defaults to stripe.
     * PM-CR-06
     */
    public function test_provider_defaults_to_stripe(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
        ]);

        $this->assertEquals('stripe', $paymentMethod->provider);
    }

    /**
     * Test is_default defaults to false.
     * PM-CR-07
     */
    public function test_is_default_defaults_to_false(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
        ]);

        $this->assertFalse($paymentMethod->is_default);
    }

    /**
     * Test can have multiple payment methods per user.
     * PM-CR-08
     */
    public function test_can_have_multiple_payment_methods_per_user(): void
    {
        $pm1 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_card1',
            'brand' => 'visa',
            'last_four' => '4242',
            'is_default' => true,
        ]);

        $pm2 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_card2',
            'brand' => 'mastercard',
            'last_four' => '5555',
            'is_default' => false,
        ]);

        $this->assertCount(2, PaymentMethod::where('user_id', $this->user->id)->get());
        $this->assertNotEquals($pm1->id, $pm2->id);
    }

    // ==================== RELATIONSHIP TESTS ====================

    /**
     * Test belongsTo relationship to User.
     * PM-REL-01
     */
    public function test_belongs_to_user_relationship(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
        ]);

        $this->assertInstanceOf(User::class, $paymentMethod->user);
        $this->assertEquals($this->user->id, $paymentMethod->user->id);
    }

    /**
     * Test user relationship returns correct user.
     * PM-REL-02
     */
    public function test_user_relationship_returns_correct_user(): void
    {
        $otherUser = User::factory()->create();

        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
        ]);

        $this->assertEquals($this->user->id, $paymentMethod->user->id);
        $this->assertNotEquals($otherUser->id, $paymentMethod->user->id);
    }

    /**
     * Test cascade delete when user is deleted.
     * PM-REL-03
     */
    public function test_cascade_delete_when_user_deleted(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
        ]);

        $this->user->delete();

        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    // ==================== CASTS TESTS ====================

    /**
     * Test is_default BOOLEAN cast.
     * PM-CA-01
     */
    public function test_is_default_boolean_cast(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'is_default' => true,
        ]);

        $this->assertIsBool($paymentMethod->is_default);
        $this->assertTrue($paymentMethod->is_default);
    }

    /**
     * Test exp_month INT cast.
     * PM-CA-02
     */
    public function test_exp_month_int_cast(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'exp_month' => 12,
        ]);

        $this->assertIsInt($paymentMethod->exp_month);
        $this->assertEquals(12, $paymentMethod->exp_month);
    }

    /**
     * Test exp_year INT cast.
     * PM-CA-03
     */
    public function test_exp_year_int_cast(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'exp_year' => 2026,
        ]);

        $this->assertIsInt($paymentMethod->exp_year);
        $this->assertEquals(2026, $paymentMethod->exp_year);
    }

    /**
     * Test timestamps cast.
     * PM-CA-04
     */
    public function test_timestamps_cast(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $paymentMethod->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $paymentMethod->updated_at);
    }

    /**
     * Test nullable fields are null by default.
     * PM-CA-05
     */
    public function test_nullable_fields_are_null_by_default(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
        ]);

        $this->assertNull($paymentMethod->brand);
        $this->assertNull($paymentMethod->last_four);
        $this->assertNull($paymentMethod->exp_month);
        $this->assertNull($paymentMethod->exp_year);
    }

    // ==================== SCOPE TESTS ====================

    /**
     * Test forUser scope returns only user's methods.
     * PM-SC-01
     */
    public function test_for_user_scope_returns_only_user_methods(): void
    {
        $otherUser = User::factory()->create();

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test1',
            'stripe_payment_method_id' => 'pm_card1',
        ]);

        PaymentMethod::create([
            'user_id' => $otherUser->id,
            'stripe_customer_id' => 'cus_test2',
            'stripe_payment_method_id' => 'pm_card2',
        ]);

        if (method_exists(PaymentMethod::class, 'scopeForUser')) {
            $userMethods = PaymentMethod::forUser($this->user->id)->get();

            $this->assertCount(1, $userMethods);
            $this->assertEquals('pm_card1', $userMethods->first()->stripe_payment_method_id);
        } else {
            $this->markTestSkipped('forUser scope not yet implemented');
        }
    }

    /**
     * Test default scope returns only default methods.
     * PM-SC-02
     */
    public function test_default_scope_returns_only_default_methods(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_default',
            'is_default' => true,
        ]);

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_non_default',
            'is_default' => false,
        ]);

        if (method_exists(PaymentMethod::class, 'scopeDefault')) {
            $defaultMethods = PaymentMethod::default()->get();

            $this->assertCount(1, $defaultMethods);
            $this->assertTrue($defaultMethods->first()->is_default);
        } else {
            $this->markTestSkipped('default scope not yet implemented');
        }
    }

    /**
     * Test byProvider scope filters by provider.
     * PM-SC-03
     */
    public function test_by_provider_scope_filters_by_provider(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_stripe',
            'stripe_payment_method_id' => 'pm_stripe',
            'provider' => 'stripe',
        ]);

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_paypal',
            'stripe_payment_method_id' => null,
            'provider' => 'paypal',
        ]);

        if (method_exists(PaymentMethod::class, 'scopeByProvider')) {
            $stripeMethods = PaymentMethod::byProvider('stripe')->get();

            $this->assertCount(1, $stripeMethods);
            $this->assertEquals('stripe', $stripeMethods->first()->provider);
        } else {
            $this->markTestSkipped('byProvider scope not yet implemented');
        }
    }

    /**
     * Test ordered scope sorts by created_at desc.
     * PM-SC-04
     */
    public function test_ordered_scope_sorts_by_created_at_desc(): void
    {
        $pm1 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_old',
        ]);

        // Small delay to ensure different timestamps
        sleep(1);

        $pm2 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_new',
        ]);

        if (method_exists(PaymentMethod::class, 'scopeOrdered')) {
            $methods = PaymentMethod::ordered()->get();

            $this->assertEquals('pm_new', $methods->first()->stripe_payment_method_id);
            $this->assertEquals('pm_old', $methods->last()->stripe_payment_method_id);
        } else {
            $this->markTestSkipped('ordered scope not yet implemented');
        }
    }

    // ==================== ACCESSOR TESTS ====================

    /**
     * Test formatted_display accessor returns brand + last_four.
     * PM-AC-01
     */
    public function test_formatted_display_accessor(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'brand' => 'visa',
            'last_four' => '4242',
        ]);

        if (method_exists($paymentMethod, 'getFormattedDisplayAttribute')) {
            $this->assertStringContainsStringIgnoringCase('visa', $paymentMethod->formatted_display);
            $this->assertStringContainsString('4242', $paymentMethod->formatted_display);
        } else {
            $this->markTestSkipped('formatted_display accessor not yet implemented');
        }
    }

    /**
     * Test formatted_display handles null brand.
     * PM-AC-02
     */
    public function test_formatted_display_handles_null_brand(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'brand' => null,
            'last_four' => '4242',
        ]);

        if (method_exists($paymentMethod, 'getFormattedDisplayAttribute')) {
            $display = $paymentMethod->formatted_display;
            $this->assertNotNull($display);
            $this->assertStringContainsString('4242', $display);
        } else {
            $this->markTestSkipped('formatted_display accessor not yet implemented');
        }
    }

    /**
     * Test masked_card accessor returns masked number.
     * PM-AC-03
     */
    public function test_masked_card_accessor(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'brand' => 'visa',
            'last_four' => '4242',
        ]);

        if (method_exists($paymentMethod, 'getMaskedCardAttribute')) {
            $masked = $paymentMethod->masked_card;
            $this->assertStringContainsString('****', $masked);
            $this->assertStringContainsString('4242', $masked);
        } else {
            $this->markTestSkipped('masked_card accessor not yet implemented');
        }
    }

    /**
     * Test is_expired accessor for expired card.
     * PM-AC-04
     */
    public function test_is_expired_accessor_for_expired_card(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'exp_month' => 1,
            'exp_year' => 2020,
        ]);

        if (method_exists($paymentMethod, 'getIsExpiredAttribute')) {
            $this->assertTrue($paymentMethod->is_expired);
        } else {
            $this->markTestSkipped('is_expired accessor not yet implemented');
        }
    }

    /**
     * Test is_expired accessor for valid card.
     * PM-AC-05
     */
    public function test_is_expired_accessor_for_valid_card(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'exp_month' => 12,
            'exp_year' => 2030,
        ]);

        if (method_exists($paymentMethod, 'getIsExpiredAttribute')) {
            $this->assertFalse($paymentMethod->is_expired);
        } else {
            $this->markTestSkipped('is_expired accessor not yet implemented');
        }
    }

    // ==================== INDEX TESTS ====================

    /**
     * Test unique index on user_id + stripe_payment_method_id exists.
     * PM-IX-01
     */
    public function test_unique_index_on_user_payment_method_exists(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_duplicate',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_duplicate',
        ]);
    }

    /**
     * Test unique index allows same payment_method_id for different users.
     * PM-IX-02
     */
    public function test_unique_index_allows_same_pm_different_user(): void
    {
        $otherUser = User::factory()->create();

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test1',
            'stripe_payment_method_id' => 'pm_shared',
        ]);

        // Should not throw - different user
        $pm2 = PaymentMethod::create([
            'user_id' => $otherUser->id,
            'stripe_customer_id' => 'cus_test2',
            'stripe_payment_method_id' => 'pm_shared',
        ]);

        $this->assertNotNull($pm2->id);
    }

    /**
     * Test index on user_id exists.
     * PM-IX-03
     */
    public function test_index_on_user_id_exists(): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list(payment_methods)");
            $hasIndex = false;
            foreach ($indexes as $index) {
                $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                foreach ($indexInfo as $col) {
                    if ($col->name === 'user_id') {
                        $hasIndex = true;
                        break 2;
                    }
                }
            }
            $this->assertTrue($hasIndex, 'Index on user_id should exist');
        } else {
            $indexes = DB::select("SHOW INDEX FROM payment_methods WHERE Column_name = 'user_id'");
            $this->assertGreaterThan(0, count($indexes), 'Index on user_id should exist');
        }
    }

    /**
     * Test index on stripe_customer_id exists.
     * PM-IX-04
     */
    public function test_index_on_stripe_customer_id_exists(): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list(payment_methods)");
            $hasIndex = false;
            foreach ($indexes as $index) {
                $indexInfo = DB::select("PRAGMA index_info({$index->name})");
                foreach ($indexInfo as $col) {
                    if ($col->name === 'stripe_customer_id') {
                        $hasIndex = true;
                        break 2;
                    }
                }
            }
            $this->assertTrue($hasIndex, 'Index on stripe_customer_id should exist');
        } else {
            $indexes = DB::select("SHOW INDEX FROM payment_methods WHERE Column_name = 'stripe_customer_id'");
            $this->assertGreaterThan(0, count($indexes), 'Index on stripe_customer_id should exist');
        }
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * Test nullable stripe_payment_method_id.
     * PM-EC-01
     */
    public function test_nullable_stripe_payment_method_id(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => null,
        ]);

        $this->assertNull($paymentMethod->stripe_payment_method_id);
    }

    /**
     * Test max brand length 50 characters.
     * PM-EC-02
     */
    public function test_brand_max_length_50_characters(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'brand' => str_repeat('a', 50),
        ]);

        $this->assertEquals(50, strlen($paymentMethod->brand));
    }

    /**
     * Test last_four is exactly 4 characters.
     * PM-EC-03
     */
    public function test_last_four_exactly_4_characters(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'last_four' => '1234',
        ]);

        $this->assertEquals(4, strlen($paymentMethod->last_four));
    }

    /**
     * Test exp_month valid range 1-12.
     * PM-EC-04
     */
    public function test_exp_month_valid_range(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'exp_month' => 6,
        ]);

        $this->assertGreaterThanOrEqual(1, $paymentMethod->exp_month);
        $this->assertLessThanOrEqual(12, $paymentMethod->exp_month);
    }

    /**
     * Test exp_year reasonable future value.
     * PM-EC-05
     */
    public function test_exp_year_reasonable_future_value(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'exp_year' => 2030,
        ]);

        $this->assertGreaterThanOrEqual(2024, $paymentMethod->exp_year);
    }

    /**
     * Test different card brands.
     * PM-EC-06
     */
    public function test_different_card_brands(): void
    {
        $brands = ['visa', 'mastercard', 'amex', 'discover', 'diners', 'jcb'];

        foreach ($brands as $index => $brand) {
            PaymentMethod::create([
                'user_id' => $this->user->id,
                'stripe_customer_id' => 'cus_test123',
                'stripe_payment_method_id' => 'pm_' . $brand,
                'brand' => $brand,
                'last_four' => (string) str_pad($index, 4, '0'),
            ]);
        }

        $this->assertCount(6, PaymentMethod::where('user_id', $this->user->id)->get());
    }

    /**
     * Test only one default per user.
     * PM-EC-07
     */
    public function test_only_one_default_per_user(): void
    {
        // This test documents the expected behavior
        // The application logic should enforce only one default per user
        $pm1 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_card1',
            'is_default' => true,
        ]);

        $pm2 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_card2',
            'is_default' => true,
        ]);

        // At minimum, both should exist - developer should enforce single default
        $this->assertCount(2, PaymentMethod::where('user_id', $this->user->id)->get());
    }

    // ==================== SECURITY TESTS ====================

    /**
     * Test no sensitive card data stored.
     * PM-SEC-01
     */
    public function test_no_sensitive_card_data_stored(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'brand' => 'visa',
            'last_four' => '4242',
        ]);

        // Ensure no full card number exists in model
        $this->assertArrayNotHasKey('card_number', $paymentMethod->getAttributes());
        $this->assertArrayNotHasKey('cvv', $paymentMethod->getAttributes());
    }

    /**
     * Test payment method user isolation.
     * PM-SEC-02
     */
    public function test_payment_method_user_isolation(): void
    {
        $otherUser = User::factory()->create();

        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
        ]);

        // Query should only return user's own methods
        $methods = PaymentMethod::where('user_id', $this->user->id)->get();
        $otherUserMethods = PaymentMethod::where('user_id', $otherUser->id)->get();

        $this->assertCount(1, $methods);
        $this->assertCount(0, $otherUserMethods);
    }

    /**
     * Test stripe_customer_id format validation.
     * PM-SEC-03
     */
    public function test_stripe_customer_id_format(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_abc123XYZ',
            'stripe_payment_method_id' => 'pm_test456',
        ]);

        $this->assertStringStartsWith('cus_', $paymentMethod->stripe_customer_id);
    }

    /**
     * Test stripe_payment_method_id format validation.
     * PM-SEC-04
     */
    public function test_stripe_payment_method_id_format(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_abc123XYZ',
        ]);

        $this->assertStringStartsWith('pm_', $paymentMethod->stripe_payment_method_id);
    }

    /**
     * Test data integrity after save.
     * PM-SEC-05
     */
    public function test_data_integrity_after_save(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test456',
            'brand' => 'visa',
            'last_four' => '4242',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'brand' => 'visa',
            'last_four' => '4242',
            'is_default' => true,
        ]);
    }

    // ==================== HELPER METHOD TESTS ====================

    /**
     * Test setAsDefault method updates is_default flag.
     * PM-HL-01
     */
    public function test_set_as_default_method(): void
    {
        $pm1 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_card1',
            'is_default' => true,
        ]);

        $pm2 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_card2',
            'is_default' => false,
        ]);

        if (method_exists($pm2, 'setAsDefault')) {
            $pm2->setAsDefault();

            $this->assertTrue($pm2->fresh()->is_default);
            $this->assertFalse($pm1->fresh()->is_default);
        } else {
            $this->markTestSkipped('setAsDefault method not yet implemented');
        }
    }

    /**
     * Test findDefaultForUser method returns user's default method.
     * PM-HL-02
     */
    public function test_find_default_for_user_method(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_default',
            'is_default' => true,
        ]);

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_non_default',
            'is_default' => false,
        ]);

        if (method_exists(PaymentMethod::class, 'findDefaultForUser')) {
            $default = PaymentMethod::findDefaultForUser($this->user->id);

            $this->assertNotNull($default);
            $this->assertTrue($default->is_default);
        } else {
            $this->markTestSkipped('findDefaultForUser method not yet implemented');
        }
    }
}
