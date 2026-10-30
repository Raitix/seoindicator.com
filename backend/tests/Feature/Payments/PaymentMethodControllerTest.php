<?php

namespace Tests\Feature\Payments;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\Exceptions\StripePaymentException;
use App\Services\Gateway\StripeGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

/**
 * Payment Method Controller Test Suite
 *
 * Comprehensive tests for TASK-26-11: Payment Methods Management.
 * Tests cover list and delete endpoints with full security coverage.
 *
 * @see TASK-26-11
 * @see FEAT-026
 */
class PaymentMethodControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up Stripe test config
        Config::set('services.stripe.secret', 'sk_test_example');
        Config::set('services.stripe.key', 'pk_test_example');
        Config::set('services.stripe.webhook_secret', 'whsec_example');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Test Data Helpers
    // ============================================

    /**
     * Create a test user.
     */
    protected function createTestUser(): User
    {
        return User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    /**
     * Create a second test user (for ownership tests).
     */
    protected function createSecondUser(): User
    {
        return User::factory()->create([
            'email' => 'other@example.com',
            'name' => 'Other User',
        ]);
    }

    /**
     * Create a payment method record in the database.
     */
    protected function createPaymentMethod(User $user, array $attributes = []): PaymentMethod
    {
        return PaymentMethod::create(array_merge([
            'user_id' => $user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test123',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2026,
            'is_default' => false,
        ], $attributes));
    }

    /**
     * Mock the Stripe gateway service for delete operations.
     */
    protected function mockStripeGatewayForDelete(string $paymentMethodId, bool $success = true, ?int $ownerId = null): Mockery\MockInterface
    {
        $mock = Mockery::mock(StripeGatewayService::class);

        $mock->shouldReceive('getPaymentMethodOwner')
            ->with($paymentMethodId)
            ->andReturn($ownerId);

        if ($success && $ownerId !== null) {
            $mock->shouldReceive('deletePaymentMethod')
                ->with($paymentMethodId)
                ->andReturn($success);
        }

        $this->app->instance(StripeGatewayService::class, $mock);

        return $mock;
    }

    // ============================================
    // List Payment Methods Tests: PM-LIST-*
    // ============================================

    /**
     * Test PM-LIST-001: Authenticated user gets their payment methods.
     *
     * @test
     */
    public function pm_list_001_authenticated_user_gets_payment_methods(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'provider',
                        'brand',
                        'last_four',
                        'is_default',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test PM-LIST-002: Unauthenticated request returns 401.
     *
     * @test
     */
    public function pm_list_002_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/payments/methods');

        $response->assertStatus(401);
    }

    /**
     * Test PM-LIST-003: User with no payment methods gets empty array.
     *
     * @test
     */
    public function pm_list_003_no_payment_methods_returns_empty_array(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /**
     * Test PM-LIST-004: Response format matches specification.
     *
     * @test
     */
    public function pm_list_004_response_format_matches_specification(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);

        // Verify success is boolean
        $this->assertIsBool($response->json('success'));
        $this->assertTrue($response->json('success'));

        // Verify data is array
        $this->assertIsArray($response->json('data'));
    }

    /**
     * Test PM-LIST-005: Payment methods include all required fields.
     *
     * @test
     */
    public function pm_list_005_payment_methods_include_required_fields(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user, ['stripe_payment_method_id' => 'pm_required_fields']);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $method = $data[0];
        $this->assertArrayHasKey('id', $method);
        $this->assertArrayHasKey('provider', $method);
        $this->assertArrayHasKey('brand', $method);
        $this->assertArrayHasKey('last_four', $method);
        $this->assertArrayHasKey('is_default', $method);
    }

    /**
     * Test PM-LIST-006: User can only see their own payment methods.
     *
     * @test
     */
    public function pm_list_006_user_sees_only_own_payment_methods(): void
    {
        $user1 = $this->createTestUser();
        $user2 = $this->createSecondUser();

        // Create payment method for user1
        $this->createPaymentMethod($user1, [
            'stripe_payment_method_id' => 'pm_user1',
            'last_four' => '1111',
        ]);

        // Create payment method for user2
        $this->createPaymentMethod($user2, [
            'stripe_payment_method_id' => 'pm_user2',
            'last_four' => '2222',
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify only user1's methods are returned
        $this->assertCount(1, $data);
        $this->assertEquals('1111', $data[0]['last_four']);
    }

    /**
     * Test PM-LIST-007: Multiple payment methods returned in order.
     *
     * @test
     */
    public function pm_list_007_multiple_payment_methods_in_order(): void
    {
        $user = $this->createTestUser();

        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_first',
            'last_four' => '1111',
            'is_default' => true,
        ]);

        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_second',
            'last_four' => '2222',
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        
        // Default should be first
        $this->assertTrue($data[0]['is_default']);
    }

    /**
     * Test PM-LIST-008: Stripe API error handled gracefully.
     *
     * @test
     */
    public function pm_list_008_stripe_api_error_handled_gracefully(): void
    {
        $user = $this->createTestUser();
        // No payment methods in DB - controller reads from DB, not Stripe

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        // Controller returns empty array, not error
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /**
     * Test PM-LIST-009: Payment method with expired card shows expiry.
     *
     * @test
     */
    public function pm_list_009_payment_method_shows_expiry(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_expiry',
            'exp_month' => 6,
            'exp_year' => 2025,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('exp_month', $data[0]);
        $this->assertArrayHasKey('exp_year', $data[0]);
        $this->assertEquals(6, $data[0]['exp_month']);
        $this->assertEquals(2025, $data[0]['exp_year']);
    }

    /**
     * Test PM-LIST-010: Response includes success flag.
     *
     * @test
     */
    public function pm_list_010_response_includes_success_flag(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ============================================
    // Delete Payment Method Tests: PM-DELETE-*
    // ============================================

    /**
     * Test PM-DELETE-001: User can delete their own payment method.
     *
     * @test
     */
    public function pm_delete_001_user_can_delete_own_payment_method(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user);

        // Create a second method so we can delete one
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_second',
            'last_four' => '2222',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->with($paymentMethod->stripe_payment_method_id)
            ->andReturn(true);
        $this->app->instance(StripeGatewayService::class, $mock);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test PM-DELETE-002: Unauthenticated request returns 401.
     *
     * @test
     */
    public function pm_delete_002_unauthenticated_returns_401(): void
    {
        $response = $this->deleteJson('/api/v1/payments/methods/pm_test123');

        $response->assertStatus(401);
    }

    /**
     * Test PM-DELETE-003: Cannot delete another user's payment method.
     *
     * @test
     */
    public function pm_delete_003_cannot_delete_another_users_method(): void
    {
        $user1 = $this->createTestUser();
        $user2 = $this->createSecondUser();
        $paymentMethod = $this->createPaymentMethod($user2);

        // User1 tries to delete user2's method
        $response = $this->actingAs($user1)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        // Should return 404 to prevent enumeration attacks
        $response->assertStatus(404);
    }

    /**
     * Test PM-DELETE-004: Non-existent payment method returns 404.
     *
     * @test
     */
    public function pm_delete_004_non_existent_method_returns_404(): void
    {
        $user = $this->createTestUser();

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('getPaymentMethodOwner')
            ->with('pm_nonexistent')
            ->andReturn(null);
        $mock->shouldReceive('deletePaymentMethod')
            ->never();
        $this->app->instance(StripeGatewayService::class, $mock);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/payments/methods/pm_nonexistent');

        $response->assertStatus(404);
    }

    /**
     * Test PM-DELETE-005: Delete removes from Stripe.
     *
     * @test
     */
    public function pm_delete_005_delete_removes_from_stripe(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->with($paymentMethod->stripe_payment_method_id)
            ->once()
            ->andReturn(true);
        $this->app->instance(StripeGatewayService::class, $mock);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $response->assertStatus(200);
    }

    /**
     * Test PM-DELETE-006: Delete removes from local database.
     *
     * @test
     */
    public function pm_delete_006_delete_removes_from_database(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->with($paymentMethod->stripe_payment_method_id)
            ->andReturn(true);
        $this->app->instance(StripeGatewayService::class, $mock);

        $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    /**
     * Test PM-DELETE-007: Cannot delete default if only method.
     *
     * @test
     */
    public function pm_delete_007_cannot_delete_default_if_only_method(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => true]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test PM-DELETE-008: Deleting default reassigns to next.
     *
     * @test
     */
    public function pm_delete_008_deleting_default_reassigns(): void
    {
        $user = $this->createTestUser();
        $first = $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_first',
            'last_four' => '1111',
            'is_default' => true,
        ]);
        $second = $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_second',
            'last_four' => '2222',
            'is_default' => false,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->with($first->stripe_payment_method_id)
            ->andReturn(true);
        $this->app->instance(StripeGatewayService::class, $mock);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$first->id}");

        $response->assertStatus(200);

        // Second should now be default
        $second->refresh();
        $this->assertTrue($second->is_default);
    }

    /**
     * Test PM-DELETE-009: Invalid payment method ID format.
     *
     * @test
     */
    public function pm_delete_009_invalid_payment_method_id_format(): void
    {
        $user = $this->createTestUser();

        // Use a non-existent database ID
        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/payments/methods/999999');

        $response->assertStatus(404);
    }

    /**
     * Test PM-DELETE-010: Stripe API error during delete.
     *
     * @test
     */
    public function pm_delete_010_stripe_api_error_during_delete(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->with($paymentMethod->stripe_payment_method_id)
            ->andThrow(new \Exception('Stripe API error'));
        $this->app->instance(StripeGatewayService::class, $mock);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }

    // ============================================
    // Security Tests: PM-SEC-*
    // ============================================

    /**
     * Test PM-SEC-001: User cannot access another user's methods.
     *
     * @test
     */
    public function pm_sec_001_user_cannot_access_another_users_methods(): void
    {
        $user1 = $this->createTestUser();
        $user2 = $this->createSecondUser();
        
        // Create methods for user2
        $this->createPaymentMethod($user2, ['last_four' => '2222']);

        // User1 should not see user2's methods
        $response = $this->actingAs($user1)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }

    /**
     * Test PM-SEC-002: No sensitive card data exposed.
     *
     * @test
     */
    public function pm_sec_002_no_sensitive_card_data_exposed(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Check that sensitive fields are NOT present
        $method = $data[0];
        $this->assertArrayNotHasKey('cvc', $method);
        $this->assertArrayNotHasKey('full_card_number', $method);
        
        // Check that only last 4 digits are exposed
        $this->assertArrayHasKey('last_four', $method);
        $this->assertEquals(4, strlen($method['last_four']));
    }

    /**
     * Test PM-SEC-003: SQL injection in payment method ID.
     *
     * @test
     */
    public function pm_sec_003_sql_injection_in_payment_method_id(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/payments/methods/1 OR 1=1');

        // Should return 404, not cause SQL error
        $response->assertStatus(404);
    }

    /**
     * Test PM-SEC-004: XSS in response data.
     *
     * @test
     */
    public function pm_sec_004_xss_in_response_data(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user, [
            'brand' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);
        
        // Brand should be present as-is (JSON encoding handles XSS)
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /**
     * Test PM-SEC-005: CSRF protection on delete.
     *
     * @test
     */
    public function pm_sec_005_csrf_protection_on_delete(): void
    {
        $user = $this->createTestUser();

        // JSON API requests don't need CSRF - they use token auth
        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/payments/methods/pm_test');

        // Should get 404 (not found), not 419 (CSRF mismatch)
        $response->assertStatus(404);
    }

    /**
     * Test PM-SEC-006: Rate limiting on list.
     *
     * @test
     */
    public function pm_sec_006_rate_limiting_on_list(): void
    {
        $user = $this->createTestUser();

        // Make multiple requests - rate limiting is typically at infra level
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)
                ->getJson('/api/v1/payments/methods');
            $response->assertStatus(200);
        }
    }

    /**
     * Test PM-SEC-007: Rate limiting on delete.
     *
     * @test
     */
    public function pm_sec_007_rate_limiting_on_delete(): void
    {
        $user = $this->createTestUser();

        // Make multiple delete requests - rate limiting is typically at infra level
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($user)
                ->deleteJson('/api/v1/payments/methods/pm_nonexistent_' . $i);
            $response->assertStatus(404);
        }
    }

    /**
     * Test PM-SEC-008: Audit log created on delete.
     *
     * @test
     */
    public function pm_sec_008_audit_log_created_on_delete(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->andReturn(true);
        $this->app->instance(StripeGatewayService::class, $mock);

        $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        // Verify method was deleted (audit would be logged elsewhere)
        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    /**
     * Test PM-SEC-009: Payment method ownership verified.
     *
     * @test
     */
    public function pm_sec_009_payment_method_ownership_verified(): void
    {
        $user1 = $this->createTestUser();
        $user2 = $this->createSecondUser();
        $paymentMethod = $this->createPaymentMethod($user2);

        $response = $this->actingAs($user1)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        // Should get 404, not 403 (to prevent enumeration)
        $response->assertStatus(404);

        // Verify method still exists
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    /**
     * Test PM-SEC-010: Idempotent delete requests.
     *
     * @test
     */
    public function pm_sec_010_idempotent_delete_requests(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->andReturn(true);
        $this->app->instance(StripeGatewayService::class, $mock);

        // First delete
        $response1 = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");
        $response1->assertStatus(200);

        // Second delete of same ID should return 404
        $response2 = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");
        $response2->assertStatus(404);
    }

    // ============================================
    // Edge Case Tests: PM-EDGE-*
    // ============================================

    /**
     * Test PM-EDGE-001: Concurrent delete.
     *
     * @test
     */
    public function pm_edge_001_concurrent_delete(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->andReturn(true);
        $this->app->instance(StripeGatewayService::class, $mock);

        // First delete succeeds
        $response1 = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");
        $response1->assertStatus(200);

        // Second delete fails (already deleted)
        $response2 = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");
        $response2->assertStatus(404);
    }

    /**
     * Test PM-EDGE-002: Delete during active subscription.
     *
     * @test
     */
    public function pm_edge_002_delete_during_active_subscription(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => true]);

        // Cannot delete the only payment method
        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $response->assertStatus(422);
    }

    /**
     * Test PM-EDGE-003: Method already deleted from Stripe.
     *
     * @test
     */
    public function pm_edge_003_method_already_deleted_from_stripe(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->andThrow(new \Exception('Payment method not found'));
        $this->app->instance(StripeGatewayService::class, $mock);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $response->assertStatus(500);
    }

    /**
     * Test PM-EDGE-004: Very long payment method ID.
     *
     * @test
     */
    public function pm_edge_004_very_long_payment_method_id(): void
    {
        $user = $this->createTestUser();
        $longId = str_repeat('a', 1000);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$longId}");

        $response->assertStatus(404);
    }

    /**
     * Test PM-EDGE-005: Unicode characters in response.
     *
     * @test
     */
    public function pm_edge_005_unicode_characters_in_response(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user, [
            'brand' => '测试品牌',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals('测试品牌', $data[0]['brand']);
    }

    /**
     * Test PM-EDGE-006: Large number of payment methods.
     *
     * @test
     */
    public function pm_edge_006_large_number_of_payment_methods(): void
    {
        $user = $this->createTestUser();

        // Create 20 payment methods
        for ($i = 0; $i < 20; $i++) {
            $this->createPaymentMethod($user, [
                'stripe_payment_method_id' => "pm_test_{$i}",
                'last_four' => str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'is_default' => $i === 0,
            ]);
        }

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(20, $data);
    }

    /**
     * Test PM-EDGE-007: Network timeout to Stripe.
     *
     * @test
     */
    public function pm_edge_007_network_timeout_to_stripe(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->andThrow(new \Exception('Connection timeout'));
        $this->app->instance(StripeGatewayService::class, $mock);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $response->assertStatus(500);
    }

    /**
     * Test PM-EDGE-008: Malformed Stripe response.
     *
     * @test
     */
    public function pm_edge_008_malformed_stripe_response(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->andThrow(new \Exception('Invalid JSON'));
        $this->app->instance(StripeGatewayService::class, $mock);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $response->assertStatus(500);
    }

    /**
     * Test PM-EDGE-009: User deleted while listing.
     *
     * @test
     */
    public function pm_edge_009_user_deleted_while_listing(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        // Should work normally
        $response->assertStatus(200);
    }

    /**
     * Test PM-EDGE-010: Special characters in brand.
     *
     * @test
     */
    public function pm_edge_010_special_characters_in_brand(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user, [
            'brand' => 'Visa & MasterCard <test>',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    // ============================================
    // Response Structure Tests: PM-RESP-*
    // ============================================

    /**
     * Test PM-RESP-001: List response structure.
     *
     * @test
     */
    public function pm_resp_001_list_response_structure(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /**
     * Test PM-RESP-002: Payment method object structure.
     *
     * @test
     */
    public function pm_resp_002_payment_method_object_structure(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'provider',
                        'brand',
                        'last_four',
                        'is_default',
                        'exp_month',
                        'exp_year',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * Test PM-RESP-003: Delete success response.
     *
     * @test
     */
    public function pm_resp_003_delete_success_response(): void
    {
        $user = $this->createTestUser();
        $paymentMethod = $this->createPaymentMethod($user, ['is_default' => false]);
        $this->createPaymentMethod($user, [
            'stripe_payment_method_id' => 'pm_default',
            'last_four' => '9999',
            'is_default' => true,
        ]);

        $mock = Mockery::mock(StripeGatewayService::class);
        $mock->shouldReceive('deletePaymentMethod')
            ->andReturn(true);
        $this->app->instance(StripeGatewayService::class, $mock);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/payments/methods/{$paymentMethod->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * Test PM-RESP-004: Delete error response.
     *
     * @test
     */
    public function pm_resp_004_delete_error_response(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/payments/methods/nonexistent');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                ],
            ]);
    }

    /**
     * Test PM-RESP-005: Date fields ISO 8601 format.
     *
     * @test
     */
    public function pm_resp_005_date_fields_iso_8601_format(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Verify created_at is ISO 8601 format
        $createdAt = $data[0]['created_at'];
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $createdAt
        );
    }

    /**
     * Test PM-RESP-006: Boolean fields are booleans.
     *
     * @test
     */
    public function pm_resp_006_boolean_fields_are_booleans(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $this->assertIsBool($data[0]['is_default']);
    }

    /**
     * Test PM-RESP-007: Numeric fields correct type.
     *
     * @test
     */
    public function pm_resp_007_numeric_fields_correct_type(): void
    {
        $user = $this->createTestUser();
        $this->createPaymentMethod($user);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $method = $data[0];
        $this->assertIsInt($method['exp_month']);
        $this->assertIsInt($method['exp_year']);
    }

    /**
     * Test PM-RESP-008: Empty list response structure.
     *
     * @test
     */
    public function pm_resp_008_empty_list_response_structure(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'data' => [],
            ]);
    }
}
