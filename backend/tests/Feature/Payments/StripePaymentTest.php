<?php

namespace Tests\Feature\Payments;

use App\Models\Credit;
use App\Models\CreditTransaction;
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

        // Mock StripeGatewayService
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->with($user, 10.00, 'USD')
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
                'package_id' => 1,
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
        $mockGateway->shouldReceive('getCustomer')
            ->once()
            ->with($user)
            ->andReturn(null);

        $mockGateway->shouldReceive('createCustomer')
            ->once()
            ->with($user)
            ->andReturn('cus_test_123');

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
                'amount' => 5.00,
                'package_id' => 1,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_reuses_existing_stripe_customer(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('getCustomer')
            ->once()
            ->with($user)
            ->andReturn('cus_existing_123');

        $mockGateway->shouldReceive('createCustomer')
            ->never();

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
                'amount' => 5.00,
                'package_id' => 1,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_includes_metadata_in_payment_intent(): void
    {
        $user = User::factory()->create();
        $packageId = 2;

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg, $amount, $currency, $metadata = []) use ($user, $packageId) {
                return $userArg->id === $user->id
                    && $amount === 25.00
                    && $currency === 'USD'
                    && isset($metadata['user_id'])
                    && isset($metadata['package_id'])
                    && $metadata['user_id'] === $user->id
                    && $metadata['package_id'] === $packageId;
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
                'amount' => 25.00,
                'package_id' => $packageId,
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
            ->withArgs(function ($userArg, $amount, $currency, $metadata = [], $idempotencyKey = null) {
                return !empty($idempotencyKey) && is_string($idempotencyKey);
            })
            ->andReturn([
                'client_secret' => 'pi_test_secret',
                'payment_intent_id' => 'pi_test_123',
                'amount' => 500,
                'currency' => 'usd',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 5.00,
                'package_id' => 1,
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

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('getPaymentMethods')
            ->once()
            ->with($user)
            ->andReturn([
                [
                    'id' => 'pm_card_visa',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                        'exp_month' => 12,
                        'exp_year' => 2025,
                    ],
                ],
                [
                    'id' => 'pm_card_mastercard',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'mastercard',
                        'last4' => '5555',
                        'exp_month' => 6,
                        'exp_year' => 2026,
                    ],
                ],
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/payments/methods');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'card' => [
                            'brand',
                            'last4',
                            'exp_month',
                            'exp_year',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_deletes_payment_method(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('deletePaymentMethod')
            ->once()
            ->with('pm_card_visa')
            ->andReturn(true);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/payments/methods/pm_card_visa');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment method deleted',
            ]);
    }

    /** @test */
    public function it_prevents_deleting_other_users_payment_method(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('getPaymentMethodOwner')
            ->once()
            ->with('pm_card_visa')
            ->andReturn($otherUser->id);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/payments/methods/pm_card_visa');

        $response->assertStatus(403);
    }

    // ============================================
    // Validation Tests
    // ============================================

    /** @test */
    public function it_validates_minimum_purchase_amount(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 2.00, // Below minimum of $5
                'package_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_validates_amount_is_numeric(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 'invalid',
                'package_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
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
            ->assertJsonValidationErrors(['package_id']);
    }

    /** @test */
    public function it_validates_amount_matches_package(): void
    {
        $user = User::factory()->create();
        // Assume package 1 costs $10.00

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 5.00, // Wrong amount
                'package_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    // ============================================
    // Authentication Tests
    // ============================================

    /** @test */
    public function it_requires_authentication_for_payment_intent(): void
    {
        $response = $this->postJson('/api/v1/payments/stripe/intent', [
            'amount' => 10.00,
            'package_id' => 1,
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
                'package_id' => 1,
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
                'package_id' => 1,
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
                'package_id' => 1,
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

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/refund', [
                'payment_intent_id' => 'pi_test_123',
                'amount' => 10.00,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'refund_id' => 're_test_123',
            ]);

        // Verify credit balance was adjusted
        $credit->refresh();
        $this->assertEquals(0.00, $credit->balance);
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

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/refund', [
                'payment_intent_id' => 'pi_test_123',
                'amount' => 10.00, // Partial refund
            ]);

        $response->assertStatus(200);

        $credit->refresh();
        $this->assertEquals(10.00, $credit->balance);
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
            ->once()
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
                'package_id' => 1,
            ]);

        $response1->assertStatus(200);

        // Second request with same idempotency key
        $response2 = $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => 1,
            ]);

        // Should return same result without creating new payment
        $response2->assertStatus(200)
            ->assertJson([
                'payment_intent_id' => 'pi_test_123',
            ]);
    }
}
