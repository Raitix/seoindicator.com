<?php

namespace Tests\Feature\Payments;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\Exceptions\InsufficientBalanceException;
use App\Services\Billing\Exceptions\PaymentTimeoutException;
use App\Services\Billing\Exceptions\StripePaymentException;
use App\Services\Billing\Exceptions\PayPalPaymentException;
use App\Services\Billing\Exceptions\InvalidWebhookSignatureException;
use App\Services\Billing\Exceptions\DuplicateTransactionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Integration tests for payment error scenarios.
 *
 * TASK-26-26: Write Integration Tests for Payments
 * @see TESTS/TASK-26-26.md
 *
 * These tests verify proper error handling, logging, and user feedback
 * for various payment failure scenarios.
 */
class ErrorScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Payment Processing Errors
    // ============================================

    /** @test */
    public function it_handles_card_declined_error(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new StripePaymentException(
                'Your card was declined.',
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
                'code' => 'card_declined',
                'message' => 'Your card was declined.',
            ]);

        // Verify no credit was added
        $credit->refresh();
        $this->assertEquals(0.00, $credit->balance);
    }

    /** @test */
    public function it_handles_insufficient_funds_error(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new StripePaymentException(
                'Your card has insufficient funds.',
                'insufficient_funds'
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
                'code' => 'insufficient_funds',
            ]);
    }

    /** @test */
    public function it_handles_expired_card_error(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new StripePaymentException(
                'Your card has expired.',
                'expired_card'
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
                'code' => 'expired_card',
            ]);
    }

    /** @test */
    public function it_handles_incorrect_cvc_error(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new StripePaymentException(
                'Your card\'s security code is incorrect.',
                'incorrect_cvc'
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
                'code' => 'incorrect_cvc',
            ]);
    }

    /** @test */
    public function it_handles_processing_error(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new StripePaymentException(
                'An error occurred while processing your card. Please try again.',
                'processing_error'
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
                'code' => 'processing_error',
            ]);
    }

    // ============================================
    // Network/Timeout Errors
    // ============================================

    /** @test */
    public function it_handles_stripe_api_timeout(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new PaymentTimeoutException(
                'Payment service is temporarily unavailable. Please try again.'
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
                'message' => 'Payment service is temporarily unavailable. Please try again.',
            ]);
    }

    /** @test */
    public function it_handles_paypal_api_timeout(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new PaymentTimeoutException(
                'PayPal is temporarily unavailable. Please try again.'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/order', [
                'amount' => 10.00,
                'package_id' => 1,
            ]);

        $response->assertStatus(503)
            ->assertJson([
                'error' => 'service_unavailable',
            ]);
    }

    /** @test */
    public function it_handles_network_connection_error(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new \RuntimeException(
                'Connection refused'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => 1,
            ]);

        $response->assertStatus(503);
    }

    // ============================================
    // Webhook Security Errors
    // ============================================

    /** @test */
    public function it_rejects_webhook_with_invalid_signature(): void
    {
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new InvalidWebhookSignatureException(
                'Webhook signature verification failed'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
        ], [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'invalid_signature',
            ]);
    }

    /** @test */
    public function it_rejects_webhook_with_missing_signature(): void
    {
        $response = $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_rejects_replayed_webhook(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
        ]);

        // Existing transaction
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'balance_after' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new DuplicateTransactionException(
                'Transaction already processed'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => ['id' => 'pi_test_123'],
            ],
        ], [
            'Stripe-Signature' => 'valid',
        ]);

        // Should return 200 to prevent retries
        $response->assertStatus(200);
    }

    // ============================================
    // Insufficient Balance Errors
    // ============================================

    /** @test */
    public function it_prevents_analysis_with_insufficient_balance(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 1.00, // Less than required
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/analysis', [
                'url' => 'https://example.com',
                'model_id' => 1, // Assume costs $5
            ]);

        $response->assertStatus(402)
            ->assertJson([
                'error' => 'insufficient_balance',
                'message' => 'Insufficient credit balance. Please add credits to continue.',
            ]);
    }

    /** @test */
    public function it_shows_required_amount_in_insufficient_balance_error(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 1.00,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/analysis', [
                'url' => 'https://example.com',
                'model_id' => 1,
            ]);

        $response->assertJsonStructure([
            'error',
            'current_balance',
            'required_amount',
        ]);
    }

    // ============================================
    // Validation Errors
    // ============================================

    /** @test */
    public function it_validates_amount_is_positive(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => -10.00,
                'package_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_validates_amount_is_not_zero(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 0,
                'package_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_validates_amount_does_not_exceed_maximum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 100000.00, // Exceeds maximum
                'package_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_validates_currency_is_supported(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => 1,
                'currency' => 'XYZ', // Invalid currency
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);
    }

    // ============================================
    // PayPal-Specific Errors
    // ============================================

    /** @test */
    public function it_handles_paypal_order_expired(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->andThrow(new PayPalPaymentException(
                'ORDER_EXPIRED',
                'The order has expired'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => 'EXPIRED_ORDER',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'order_expired',
            ]);
    }

    /** @test */
    public function it_handles_paypal_order_already_captured(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->andThrow(new PayPalPaymentException(
                'ORDER_ALREADY_CAPTURED',
                'The order has already been captured'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => 'CAPTURED_ORDER',
            ]);

        // Should indicate idempotent success
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order already processed',
            ]);
    }

    /** @test */
    public function it_handles_paypal_payer_not_approved(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->andThrow(new PayPalPaymentException(
                'ORDER_NOT_APPROVED',
                'The payer has not approved the order'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => 'NOT_APPROVED',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'order_not_approved',
            ]);
    }

    // ============================================
    // Refund Errors
    // ============================================

    /** @test */
    public function it_handles_refund_already_processed(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('refundPayment')
            ->once()
            ->andThrow(new StripePaymentException(
                'Refund already exists',
                'refund_already_exists'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/refund', [
                'payment_intent_id' => 'pi_test',
                'amount' => 10.00,
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_handles_refund_amount_exceeds_charge(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('refundPayment')
            ->once()
            ->andThrow(new StripePaymentException(
                'Refund amount exceeds charge amount',
                'refund_exceeds_charge'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/refund', [
                'payment_intent_id' => 'pi_test',
                'amount' => 100.00, // More than original charge
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'refund_failed',
            ]);
    }

    // ============================================
    // Error Logging Tests
    // ============================================

    /** @test */
    public function it_logs_payment_errors(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'payment') &&
                    isset($context['error_code']);
            });

        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new StripePaymentException(
                'Card declined',
                'card_declined'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => 1,
            ]);
    }

    /** @test */
    public function it_logs_webhook_errors(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'webhook');
            });

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new InvalidWebhookSignatureException());

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
        ]);
    }

    // ============================================
    // User-Friendly Error Messages
    // ============================================

    /** @test */
    public function it_returns_user_friendly_error_for_card_declined(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new StripePaymentException(
                'The card was declined.',
                'card_declined'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => 1,
            ]);

        // Message should be user-friendly, not technical
        $response->assertJsonPath('message', 'Your card was declined. Please try a different payment method.');
    }

    /** @test */
    public function it_returns_user_friendly_error_for_timeout(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new PaymentTimeoutException());

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'amount' => 10.00,
                'package_id' => 1,
            ]);

        $response->assertJsonPath('message', 'Payment service is temporarily unavailable. Please try again in a moment.');
    }
}
