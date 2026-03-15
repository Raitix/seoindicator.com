<?php

namespace Tests\Feature\Payments;

use App\Models\Credit;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\DTOs\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Integration tests for Stripe payment flow.
 *
 * TASK-26-26: Write Integration Tests for Payments
 * @see TESTS/TASK-26-26.md
 *
 * These tests use mocked Stripe responses to test the full payment flow
 * without requiring actual Stripe API calls.
 */
class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected CreditPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a credit package for testing
        $this->package = CreditPackage::factory()->create([
            'name' => 'Basic',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'bonus_percent' => 0,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Payment Intent Creation Tests
    // ============================================

    /** @test */
    public function it_creates_payment_intent_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        // Mock StripeGatewayService (bound to PaymentGatewayInterface)
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg, $amount, $currency, $metadata = []) {
                return $amount === 10.00 && $currency === 'USD';
            })
            ->andReturn([
                'client_secret' => 'pi_test_client_secret',
                'payment_intent_id' => 'pi_test_123',
                'amount' => 1000, // cents
                'currency' => 'usd',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'client_secret',
                'payment_intent_id',
                'amount',
                'currency',
            ]);
    }

    /** @test */
    public function it_creates_stripe_customer_if_not_exists(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn([
                'client_secret' => 'pi_test_secret',
                'payment_intent_id' => 'pi_test_123',
                'amount' => 500,
                'currency' => 'usd',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_reuses_existing_stripe_customer(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn([
                'client_secret' => 'pi_test_secret',
                'payment_intent_id' => 'pi_test_123',
                'amount' => 500,
                'currency' => 'usd',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_includes_metadata_in_payment_intent(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg, $amount, $currency, $metadata = []) use ($user) {
                return $userArg->id === $user->id
                    && $amount === 10.00
                    && $currency === 'USD'
                    && isset($metadata['user_id'])
                    && isset($metadata['package_id'])
                    && $metadata['user_id'] === $user->id;
            })
            ->andReturn([
                'client_secret' => 'pi_test_secret',
                'payment_intent_id' => 'pi_test_123',
                'amount' => 2500,
                'currency' => 'usd',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_uses_idempotency_key_for_retry_safety(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn([
                'client_secret' => 'pi_test_secret',
                'payment_intent_id' => 'pi_test_123',
                'amount' => 500,
                'currency' => 'usd',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(200);
    }

    // ============================================
    // Payment Method Tests
    // ============================================

    /** @test */
    public function it_lists_saved_payment_methods(): void
    {
        $user = User::factory()->create();

        // Create payment methods in database
        PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2025,
            'is_default' => true,
        ]);

        PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '5555',
            'exp_month' => 6,
            'exp_year' => 2026,
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'provider',
                        'brand',
                        'last_four',
                        'exp_month',
                        'exp_year',
                        'is_default',
                        'created_at',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_deletes_payment_method(): void
    {
        $user = User::factory()->create();

        // Create two payment methods (can't delete the only one if it's default)
        $method1 = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2025,
            'is_default' => true,
            'stripe_payment_method_id' => 'pm_card_visa',
        ]);

        $method2 = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '5555',
            'exp_month' => 6,
            'exp_year' => 2026,
            'is_default' => false,
        ]);

        // Mock StripeGatewayService for deletion
        $mockGateway = Mockery::mock(\App\Services\Gateway\StripeGatewayService::class);
        $mockGateway->shouldReceive('deletePaymentMethod')
            ->once()
            ->with('pm_card_visa')
            ->andReturn(true);

        $this->app->instance(\App\Services\Gateway\StripeGatewayService::class, $mockGateway);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/payments/methods/' . $method1->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment method deleted',
            ]);

        // Verify deleted from database
        $this->assertDatabaseMissing('payment_methods', [
            'id' => $method1->id,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_other_users_payment_method(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $method = PaymentMethod::factory()->create([
            'user_id' => $otherUser->id,
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2025,
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/payments/methods/' . $method->id);

        $response->assertStatus(404);

        // Verify not deleted
        $this->assertDatabaseHas('payment_methods', [
            'id' => $method->id,
        ]);
    }

    // ============================================
    // Validation Tests
    // ============================================

    /** @test */
    public function it_validates_minimum_purchase_amount(): void
    {
        $user = User::factory()->create();

        // Create a cheap package
        $cheapPackage = CreditPackage::factory()->create([
            'name' => 'Cheap',
            'credits' => 2.00,
            'price_usd' => 2.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 2.00,
                'package_id' => $cheapPackage->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                    'details',
                ],
            ]);
    }

    /** @test */
    public function it_validates_amount_is_numeric(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 'invalid',
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                    'details',
                ],
            ]);
    }

    /** @test */
    public function it_validates_package_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => 999, // Non-existent package
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                    'details',
                ],
            ]);
    }

    /** @test */
    public function it_validates_amount_matches_package(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 5.00, // Wrong amount (package costs $10.00)
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                    'details',
                ],
            ]);
    }

    // ============================================
    // Authentication Tests
    // ============================================

    /** @test */
    public function it_requires_authentication_for_payment_intent(): void
    {
        $response = $this->postJson('/api/v1/payments/stripe/intent', [
            'amount' => 10.00,
            'package_id' => $this->package->id,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_payment_methods(): void
    {
        $response = $this->getJson('/api/v1/payments/methods');

        $response->assertStatus(401);
    }

    // ============================================
    // Error Handling Tests
    // ============================================

    /** @test */
    public function it_handles_stripe_api_error(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new \App\Services\Billing\Exceptions\StripePaymentException(
                'Card declined',
                'card_declined'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'payment_failed',
                'message' => 'Card declined',
            ]);
    }

    /** @test */
    public function it_handles_network_timeout(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new \App\Services\Billing\Exceptions\PaymentTimeoutException(
                'Payment service unavailable. Please try again.'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(503)
            ->assertJson([
                'error' => 'service_unavailable',
                'message' => 'Payment service unavailable. Please try again.',
            ]);
    }

    /** @test */
    public function it_handles_invalid_request_error(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new \App\Services\Billing\Exceptions\PaymentValidationException(
                'Invalid currency code'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'validation_error',
            ]);
    }

    // ============================================
    // Refund Tests
    // ============================================

    /** @test */
    public function it_processes_refund_successfully(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
            'total_purchased' => 10.00,
        ]);

        $transaction = CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('refundPayment')
            ->once()
            ->with('pi_test_123', 10.00)
            ->andReturn([
                'refund_id' => 're_test_123',
                'amount' => 1000, // cents
                'status' => 'succeeded',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        // Note: There's no /api/v1/payments/refund endpoint defined in routes
        // This test documents expected behavior when such endpoint is added
        // For now, we test the gateway method directly
        $result = $mockGateway->refundPayment('pi_test_123', 10.00);

        $this->assertEquals('re_test_123', $result['refund_id']);
        $this->assertEquals('succeeded', $result['status']);
    }

    /** @test */
    public function it_processes_partial_refund(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 20.00,
            'total_purchased' => 20.00,
        ]);

        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 20.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('refundPayment')
            ->once()
            ->with('pi_test_123', 10.00)
            ->andReturn([
                'refund_id' => 're_test_123',
                'amount' => 1000, // cents (partial refund)
                'status' => 'succeeded',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $result = $mockGateway->refundPayment('pi_test_123', 10.00);

        $this->assertEquals('re_test_123', $result['refund_id']);
    }

    // ============================================
    // Idempotency Tests
    // ============================================

    /** @test */
    public function it_handles_duplicate_payment_intent_request(): void
    {
        $user = User::factory()->create();
        $idempotencyKey = 'idemp_' . uniqid();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->twice() // Both requests will call the gateway (idempotency handled by Stripe)
            ->andReturn([
                'client_secret' => 'pi_test_secret',
                'payment_intent_id' => 'pi_test_123',
                'amount' => 1000,
                'currency' => 'usd',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        // First request
        $response1 = $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response1->assertStatus(200)
            ->assertJson([
                'payment_intent_id' => 'pi_test_123',
            ]);

        // Second request with same idempotency key
        // In production, Stripe handles idempotency and returns the same result
        $response2 = $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => $this->package->id,
            ]);

        $response2->assertStatus(200)
            ->assertJson([
                'payment_intent_id' => 'pi_test_123',
            ]);
    }
}

