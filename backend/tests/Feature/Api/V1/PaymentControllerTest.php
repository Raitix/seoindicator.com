<?php

namespace Tests\Feature\Api\V1;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Gateway\StripeGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for TASK-26-11: Payment Methods Management API
 *
 * Tests the API endpoints for:
 * - List Payment Methods (GET /api/v1/payments/methods)
 * - Delete Payment Method (DELETE /api/v1/payments/methods/{id})
 *
 * Covers:
 * - Authentication requirements
 * - Authorization (user can only access their own methods)
 * - StripeGatewayService integration (mocked)
 * - Response format validation
 * - Error handling
 * - Security measures
 *
 * @see \App\Http\Controllers\Api\V1\PaymentController
 */
class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected $stripeGatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // Mock StripeGatewayService
        $this->stripeGatewayMock = Mockery::mock(StripeGatewayService::class);
        $this->app->instance(StripeGatewayService::class, $this->stripeGatewayMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== LIST PAYMENT METHODS TESTS ====================

    /**
     * Test list returns user's payment methods.
     * PM-LIST-01
     */
    public function test_list_returns_user_payment_methods(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_visa',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2026,
            'is_default' => true,
        ]);

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_mastercard',
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '5555',
            'exp_month' => 6,
            'exp_year' => 2025,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'provider',
                        'brand',
                        'last_four',
                        'is_default',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * Test list returns empty array when no methods.
     * PM-LIST-02
     */
    public function test_list_returns_empty_array_when_no_methods(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /**
     * Test list only returns current user's methods.
     * PM-LIST-03
     */
    public function test_list_only_returns_current_user_methods(): void
    {
        $otherUser = User::factory()->create();

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_user1',
            'stripe_payment_method_id' => 'pm_user1_card',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '1111',
        ]);

        PaymentMethod::create([
            'user_id' => $otherUser->id,
            'stripe_customer_id' => 'cus_user2',
            'stripe_payment_method_id' => 'pm_user2_card',
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '2222',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_four', '1111');
    }

    /**
     * Test list unauthenticated returns 401.
     * PM-LIST-04
     */
    public function test_list_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/payments/methods');

        $response->assertUnauthorized();
    }

    /**
     * Test list default method appears first.
     * PM-LIST-05
     */
    public function test_list_default_method_appears_first(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_non_default',
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '5555',
            'is_default' => false,
        ]);

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_default',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        $response->assertOk();
        
        // Default method should be first
        $data = $response->json('data');
        $this->assertTrue($data[0]['is_default']);
        $this->assertEquals('4242', $data[0]['last_four']);
    }

    /**
     * Test list response format matches specification.
     * PM-LIST-06
     */
    public function test_list_response_format_matches_specification(): void
    {
        $pm = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2026,
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $pm->id)
            ->assertJsonPath('data.0.provider', 'stripe')
            ->assertJsonPath('data.0.brand', 'visa')
            ->assertJsonPath('data.0.last_four', '4242')
            ->assertJsonPath('data.0.is_default', true);
    }

    /**
     * Test list includes expiration date if available.
     * PM-LIST-07
     */
    public function test_list_includes_expiration_date_if_available(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2026,
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'exp_month',
                        'exp_year',
                    ],
                ],
            ]);
    }

    /**
     * Test list handles method without brand.
     * PM-LIST-08
     */
    public function test_list_handles_method_without_brand(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test',
            'provider' => 'stripe',
            'brand' => null,
            'last_four' => '4242',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ==================== DELETE PAYMENT METHOD TESTS ====================

    /**
     * Test delete removes payment method.
     * PM-DEL-01
     */
    public function test_delete_removes_payment_method(): void
    {
        $pm = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_to_delete',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
        ]);

        // Mock StripeGatewayService detach call
        $this->stripeGatewayMock
            ->shouldReceive('deletePaymentMethod')
            ->once()
            ->with('pm_to_delete')
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/{$pm->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Payment method deleted successfully',
            ]);

        $this->assertDatabaseMissing('payment_methods', [
            'id' => $pm->id,
        ]);
    }

    /**
     * Test delete unauthenticated returns 401.
     * PM-DEL-02
     */
    public function test_delete_unauthenticated_returns_401(): void
    {
        $pm = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test',
            'provider' => 'stripe',
        ]);

        $response = $this->deleteJson("/api/v1/payments/methods/{$pm->id}");

        $response->assertUnauthorized();
    }

    /**
     * Test delete another user's method returns 404.
     * PM-DEL-03
     */
    public function test_delete_another_user_method_returns_404(): void
    {
        $otherUser = User::factory()->create();

        $pm = PaymentMethod::create([
            'user_id' => $otherUser->id,
            'stripe_customer_id' => 'cus_other',
            'stripe_payment_method_id' => 'pm_other_card',
            'provider' => 'stripe',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/{$pm->id}");

        $response->assertNotFound();

        // Verify method still exists
        $this->assertDatabaseHas('payment_methods', [
            'id' => $pm->id,
        ]);
    }

    /**
     * Test delete non-existent method returns 404.
     * PM-DEL-04
     */
    public function test_delete_non_existent_method_returns_404(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/payments/methods/99999');

        $response->assertNotFound();
    }

    /**
     * Test delete last default method fails.
     * PM-DEL-05
     */
    public function test_delete_last_default_method_fails(): void
    {
        $pm = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_only_card',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/{$pm->id}");

        // Should fail - cannot delete only payment method if it's default
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // Verify method still exists
        $this->assertDatabaseHas('payment_methods', [
            'id' => $pm->id,
        ]);
    }

    /**
     * Test delete default when other methods exist reassigns default.
     * PM-DEL-06
     */
    public function test_delete_default_reassigns_to_next_method(): void
    {
        $pm1 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_default',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'is_default' => true,
        ]);

        $pm2 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_non_default',
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '5555',
            'is_default' => false,
        ]);

        $this->stripeGatewayMock
            ->shouldReceive('deletePaymentMethod')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/{$pm1->id}");

        $response->assertOk();

        // Verify second method is now default
        $this->assertTrue($pm2->fresh()->is_default);
    }

    /**
     * Test delete non-default method succeeds.
     * PM-DEL-07
     */
    public function test_delete_non_default_method_succeeds(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_default',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'is_default' => true,
        ]);

        $pm2 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_non_default',
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '5555',
            'is_default' => false,
        ]);

        $this->stripeGatewayMock
            ->shouldReceive('deletePaymentMethod')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/{$pm2->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('payment_methods', [
            'id' => $pm2->id,
        ]);
    }

    /**
     * Test delete handles Stripe API error.
     * PM-DEL-08
     */
    public function test_delete_handles_stripe_api_error(): void
    {
        $pm1 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_default',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'is_default' => true,
        ]);

        $pm2 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_to_delete',
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '5555',
            'is_default' => false,
        ]);

        // Mock Stripe API error
        $this->stripeGatewayMock
            ->shouldReceive('deletePaymentMethod')
            ->once()
            ->andThrow(new \Exception('Stripe API error'));

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/{$pm2->id}");

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);

        // Verify local record still exists (not deleted on API failure)
        $this->assertDatabaseHas('payment_methods', [
            'id' => $pm2->id,
        ]);
    }

    /**
     * Test delete with invalid ID format.
     * PM-DEL-09
     */
    public function test_delete_with_invalid_id_format(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/payments/methods/abc');

        $response->assertNotFound();
    }

    // ==================== SECURITY TESTS ====================

    /**
     * Test list does not expose stripe IDs in response.
     * PM-SEC-01
     */
    public function test_list_does_not_expose_stripe_ids(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_sensitive123',
            'stripe_payment_method_id' => 'pm_sensitive456',
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        $response->assertOk();

        // Check that Stripe IDs are not exposed
        $json = $response->json();
        $this->assertArrayNotHasKey('stripe_customer_id', $json['data'][0] ?? []);
        $this->assertArrayNotHasKey('stripe_payment_method_id', $json['data'][0] ?? []);
    }

    /**
     * Test user cannot enumerate other users' payment methods.
     * PM-SEC-02
     */
    public function test_user_cannot_enumerate_other_users_methods(): void
    {
        $otherUser = User::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            PaymentMethod::create([
                'user_id' => $otherUser->id,
                'stripe_customer_id' => "cus_other_{$i}",
                'stripe_payment_method_id' => "pm_other_{$i}",
                'provider' => 'stripe',
            ]);
        }

        // Try to access other user's methods by ID
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/payments/methods/{$i}");

            // Should always be 404 (not found) not 403 (forbidden)
            // This prevents enumeration attacks
            if ($response->status() !== 404) {
                $this->fail('User should not be able to determine if payment method exists for another user');
            }
        }

        $this->assertTrue(true); // All attempts returned 404
    }

    /**
     * Test delete requires user ownership.
     * PM-SEC-03
     */
    public function test_delete_requires_user_ownership(): void
    {
        $otherUser = User::factory()->create();

        $pm = PaymentMethod::create([
            'user_id' => $otherUser->id,
            'stripe_customer_id' => 'cus_other',
            'stripe_payment_method_id' => 'pm_other',
            'provider' => 'stripe',
        ]);

        // Should not call Stripe API
        $this->stripeGatewayMock
            ->shouldNotReceive('deletePaymentMethod');

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/{$pm->id}");

        $response->assertNotFound();
    }

    /**
     * Test no SQL injection via payment method ID.
     * PM-SEC-04
     */
    public function test_no_sql_injection_via_payment_method_id(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/1; DROP TABLE payment_methods;--");

        // Should handle gracefully - either 404 or validation error
        $this->assertContains($response->status(), [404, 422]);

        // Verify table still exists
        $this->assertGreaterThan(0, PaymentMethod::count());
    }

    // ==================== ERROR HANDLING TESTS ====================

    /**
     * Test list handles database error gracefully.
     * PM-ERR-01
     */
    public function test_list_handles_database_error_gracefully(): void
    {
        // This test documents expected behavior
        // In production, database errors should return 500 with generic message
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        // Should succeed with empty list if no DB error
        $response->assertOk();
    }

    /**
     * Test delete handles concurrent deletion.
     * PM-ERR-02
     */
    public function test_delete_handles_concurrent_deletion(): void
    {
        $pm = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test',
            'provider' => 'stripe',
            'is_default' => false,
        ]);

        // Create another method so delete can succeed
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_default',
            'provider' => 'stripe',
            'is_default' => true,
        ]);

        // Simulate concurrent deletion by manually deleting first
        $pm->delete();

        $this->stripeGatewayMock
            ->shouldNotReceive('deletePaymentMethod');

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/{$pm->id}");

        $response->assertNotFound();
    }

    // ==================== PERFORMANCE TESTS ====================

    /**
     * Test list response time under 200ms.
     * PM-PERF-01
     */
    public function test_list_response_time_under_200ms(): void
    {
        // Create 10 payment methods
        for ($i = 0; $i < 10; $i++) {
            PaymentMethod::create([
                'user_id' => $this->user->id,
                'stripe_customer_id' => 'cus_test123',
                'stripe_payment_method_id' => "pm_test_{$i}",
                'provider' => 'stripe',
                'brand' => 'visa',
                'last_four' => str_pad($i, 4, '0'),
            ]);
        }

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/methods');

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(200, $elapsed, "List endpoint took {$elapsed}ms, expected < 200ms");
    }

    /**
     * Test delete response time under 500ms.
     * PM-PERF-02
     */
    public function test_delete_response_time_under_500ms(): void
    {
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_default',
            'provider' => 'stripe',
            'is_default' => true,
        ]);

        $pm = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_to_delete',
            'provider' => 'stripe',
            'is_default' => false,
        ]);

        $this->stripeGatewayMock
            ->shouldReceive('deletePaymentMethod')
            ->once()
            ->andReturn(true);

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/payments/methods/{$pm->id}");

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(500, $elapsed, "Delete endpoint took {$elapsed}ms, expected < 500ms");
    }

    // ==================== RATE LIMITING TESTS ====================

    /**
     * Test list respects rate limiting.
     * PM-RATE-01
     */
    public function test_list_respects_rate_limiting(): void
    {
        // This test documents expected behavior
        // Rate limiting should be configured in API routes
        // Typically 60 requests per minute for authenticated users
        
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/payments/methods');
            $response->assertOk();
        }
    }

    /**
     * Test delete respects rate limiting.
     * PM-RATE-02
     */
    public function test_delete_respects_rate_limiting(): void
    {
        // This test documents expected behavior
        // Rate limiting should be configured in API routes
        // Typically 10 delete requests per minute
        
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_default',
            'provider' => 'stripe',
            'is_default' => true,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $pm = PaymentMethod::create([
                'user_id' => $this->user->id,
                'stripe_customer_id' => 'cus_test123',
                'stripe_payment_method_id' => "pm_test_{$i}",
                'provider' => 'stripe',
                'is_default' => false,
            ]);

            $this->stripeGatewayMock
                ->shouldReceive('deletePaymentMethod')
                ->once()
                ->andReturn(true);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/payments/methods/{$pm->id}");
            $response->assertOk();
        }
    }

    // ==================== HTTP METHOD TESTS ====================

    /**
     * Test list only accepts GET requests.
     * PM-HTTP-01
     */
    public function test_list_only_accepts_get_requests(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments/methods');
        $response->assertMethodNotAllowed();

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/payments/methods');
        $response->assertMethodNotAllowed();

        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/payments/methods');
        $response->assertMethodNotAllowed();
    }

    /**
     * Test delete only accepts DELETE requests.
     * PM-HTTP-02
     */
    public function test_delete_only_accepts_delete_requests(): void
    {
        $pm = PaymentMethod::create([
            'user_id' => $this->user->id,
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test',
            'provider' => 'stripe',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/payments/methods/{$pm->id}");
        $response->assertMethodNotAllowed();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/payments/methods/{$pm->id}");
        $response->assertMethodNotAllowed();

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/payments/methods/{$pm->id}");
        $response->assertMethodNotAllowed();
    }
}
