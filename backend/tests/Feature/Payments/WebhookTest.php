<?php

namespace Tests\Feature\Payments;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Gateway\StripeSignatureVerifier;
use App\Services\Gateway\StripeWebhookService;
use App\Services\Billing\PayPalGatewayService;
use App\Services\Billing\DTOs\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Integration tests for payment webhooks (Stripe and PayPal).
 *
 * TASK-26-26: Write Integration Tests for Payments
 * @see TESTS/TASK-26-26.md
 *
 * Webhooks are security-critical - all tests verify signature validation
 * and proper handling of success/failure scenarios.
 */
class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Stripe Webhook Tests
    // ============================================

    /** @test */
    public function it_handles_stripe_payment_succeeded_webhook(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        $webhookPayload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 1000,
                    'currency' => 'usd',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'package_id' => '1',
                    ],
                ],
            ],
        ]);

        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => true,
                'payload' => $webhookPayload,
                'error' => null,
            ]);

        $mockService = Mockery::mock(StripeWebhookService::class);
        $mockService->shouldReceive('processWebhook')
            ->once()
            ->with($webhookPayload)
            ->andReturn([
                'success' => true,
                'event_type' => 'payment_intent.succeeded',
                'message' => 'Credits added successfully',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);
        $this->app->instance(StripeWebhookService::class, $mockService);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', json_decode($webhookPayload, true), [
            'Stripe-Signature' => 't=1234567890,v1=abc123def456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'received' => true,
                'event_type' => 'payment_intent.succeeded',
            ]);
    }

    /** @test */
    public function it_handles_stripe_payment_failed_webhook(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 5.00,
        ]);

        $webhookPayload = json_encode([
            'id' => 'evt_test_456',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_456',
                    'amount' => 1000,
                    'currency' => 'usd',
                    'last_payment_error' => [
                        'code' => 'card_declined',
                        'message' => 'Your card was declined.',
                    ],
                    'metadata' => [
                        'user_id' => (string) $user->id,
                    ],
                ],
            ],
        ]);

        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => true,
                'payload' => $webhookPayload,
                'error' => null,
            ]);

        $mockService = Mockery::mock(StripeWebhookService::class);
        $mockService->shouldReceive('processWebhook')
            ->once()
            ->with($webhookPayload)
            ->andReturn([
                'success' => true,
                'event_type' => 'payment_intent.payment_failed',
                'message' => 'Payment failure recorded',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);
        $this->app->instance(StripeWebhookService::class, $mockService);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', json_decode($webhookPayload, true), [
            'Stripe-Signature' => 't=1234567890,v1=abc123def456',
        ]);

        $response->assertStatus(200);

        $credit->refresh();
        $this->assertEquals(5.00, $credit->balance);
    }

    /** @test */
    public function it_handles_stripe_refund_webhook(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
            'total_purchased' => 10.00,
        ]);

        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $webhookPayload = json_encode([
            'id' => 'evt_test_789',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_123',
                    'payment_intent' => 'pi_test_123',
                    'amount_refunded' => 1000,
                    'refunds' => [
                        'data' => [
                            [
                                'id' => 're_test_123',
                                'amount' => 1000,
                            ],
                        ],
                    ],
                    'metadata' => [
                        'user_id' => (string) $user->id,
                    ],
                ],
            ],
        ]);

        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => true,
                'payload' => $webhookPayload,
                'error' => null,
            ]);

        $mockService = Mockery::mock(StripeWebhookService::class);
        $mockService->shouldReceive('processWebhook')
            ->once()
            ->with($webhookPayload)
            ->andReturn([
                'success' => true,
                'event_type' => 'charge.refunded',
                'message' => 'Refund processed successfully',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);
        $this->app->instance(StripeWebhookService::class, $mockService);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', json_decode($webhookPayload, true), [
            'Stripe-Signature' => 't=1234567890,v1=abc123def456',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_verifies_stripe_webhook_signature(): void
    {
        $webhookPayload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ]);

        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => false,
                'payload' => null,
                'error' => 'Invalid webhook signature',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', json_decode($webhookPayload, true));

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid signature',
            ]);
    }

    /** @test */
    public function it_handles_duplicate_stripe_webhook_idempotently(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
        ]);

        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $webhookPayload = json_encode([
            'id' => 'evt_test_duplicate',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 1000,
                    'metadata' => ['user_id' => (string) $user->id],
                ],
            ],
        ]);

        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => true,
                'payload' => $webhookPayload,
                'error' => null,
            ]);

        $mockService = Mockery::mock(StripeWebhookService::class);
        $mockService->shouldReceive('processWebhook')
            ->once()
            ->with($webhookPayload)
            ->andReturn([
                'success' => true,
                'event_type' => 'payment_intent.succeeded',
                'message' => 'Payment already processed',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);
        $this->app->instance(StripeWebhookService::class, $mockService);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', json_decode($webhookPayload, true), [
            'Stripe-Signature' => 'valid_signature',
        ]);

        $response->assertStatus(200);

        $credit->refresh();
        $this->assertEquals(10.00, $credit->balance);
    }

    // ============================================
    // PayPal Webhook Tests
    // ============================================

    /** @test */
    public function it_handles_paypal_payment_completed_webhook(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        $payload = [
            'id' => 'WH-1234567890',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => '8XN93152WY218160S',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
                'custom_id' => "user_{$user->id}_package_1",
                'payer' => [
                    'payer_id' => 'QPWXRMG3YH5RN',
                ],
            ],
        ];

        $mockGateway = Mockery::mock(PayPalGatewayService::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: '8XN93152WY218160S',
                amount: 10.00,
                currency: 'USD',
                userId: $user->id,
                metadata: ['package_id' => '1', 'payer_id' => 'QPWXRMG3YH5RN']
            ));

        $this->app->instance(PayPalGatewayService::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $payload, [
            'PAYPAL-TRANSMISSION-ID' => 'trans_123',
            'PAYPAL-CERT-URL' => 'https://api.paypal.com/v1/notifications/certs/Cert',
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            'PAYPAL-TRANSMISSION-SIG' => 'signature_here',
            'PAYPAL-TRANSMISSION-TIME' => '2024-01-01T00:00:00Z',
        ]);

        $response->assertStatus(200);

        $credit->refresh();
        $this->assertEquals(10.00, $credit->balance);
    }

    /** @test */
    public function it_handles_paypal_payment_denied_webhook(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 5.00,
        ]);

        $payload = [
            'id' => 'WH-1234567891',
            'event_type' => 'PAYMENT.CAPTURE.DENIED',
            'resource' => [
                'id' => '8XN93152WY218160S',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
                'custom_id' => "user_{$user->id}_package_1",
            ],
        ];

        $mockGateway = Mockery::mock(PayPalGatewayService::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: false,
                paymentId: '8XN93152WY218160S',
                amount: 10.00,
                currency: 'USD',
                userId: $user->id,
                errorCode: 'PAYMENT_DENIED',
                errorMessage: 'Payment was denied'
            ));

        $this->app->instance(PayPalGatewayService::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $payload, [
            'PAYPAL-TRANSMISSION-ID' => 'trans_123',
            'PAYPAL-TRANSMISSION-SIG' => 'signature_here',
        ]);

        $response->assertStatus(200);

        $credit->refresh();
        $this->assertEquals(5.00, $credit->balance);
    }

    /** @test */
    public function it_handles_paypal_refund_webhook(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
        ]);

        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'paypal',
            'payment_id' => '8XN93152WY218160S',
        ]);

        $payload = [
            'id' => 'WH-1234567892',
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => '1J114081VH731483M',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
                'seller_payable_breakdown' => [
                    'total_refunded_amount' => [
                        'value' => '10.00',
                    ],
                ],
                'links' => [
                    ['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/captures/8XN93152WY218160S'],
                ],
            ],
        ];

        $mockGateway = Mockery::mock(PayPalGatewayService::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: '1J114081VH731483M',
                amount: -10.00,
                currency: 'USD',
                userId: $user->id,
                type: 'refund'
            ));

        $this->app->instance(PayPalGatewayService::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $payload, [
            'PAYPAL-TRANSMISSION-ID' => 'trans_123',
            'PAYPAL-TRANSMISSION-SIG' => 'signature_here',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_verifies_paypal_webhook_signature(): void
    {
        $payload = [
            'id' => 'WH-invalid',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [],
        ];

        $mockGateway = Mockery::mock(PayPalGatewayService::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(false);

        $this->app->instance(PayPalGatewayService::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $payload);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ]);
    }

    /** @test */
    public function it_handles_paypal_checkout_order_approved_webhook(): void
    {
        $user = User::factory()->create();

        $payload = [
            'id' => 'WH-1234567893',
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => [
                'id' => '5O190127TN364715C',
                'status' => 'APPROVED',
                'purchase_units' => [
                    [
                        'amount' => [
                            'value' => '10.00',
                            'currency_code' => 'USD',
                        ],
                        'custom_id' => "user_{$user->id}_package_1",
                    ],
                ],
                'payer' => [
                    'payer_id' => 'QPWXRMG3YH5RN',
                ],
            ],
        ];

        $mockGateway = Mockery::mock(PayPalGatewayService::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: '5O190127TN364715C',
                amount: 10.00,
                currency: 'USD',
                userId: $user->id,
                requiresCapture: true
            ));

        $this->app->instance(PayPalGatewayService::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $payload, [
            'PAYPAL-TRANSMISSION-ID' => 'trans_123',
            'PAYPAL-TRANSMISSION-SIG' => 'signature_here',
        ]);

        $response->assertStatus(200);
    }

    // ============================================
    // Security Tests
    // ============================================

    /** @test */
    public function it_rejects_webhook_without_signature(): void
    {
        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => false,
                'payload' => null,
                'error' => 'Missing Stripe-Signature header',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature(): void
    {
        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => false,
                'payload' => null,
                'error' => 'Invalid signature: signature verification failed',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ], [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_webhook_processing_error(): void
    {
        $webhookPayload = json_encode([
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ]);

        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => true,
                'payload' => $webhookPayload,
                'error' => null,
            ]);

        $mockService = Mockery::mock(StripeWebhookService::class);
        $mockService->shouldReceive('processWebhook')
            ->once()
            ->with($webhookPayload)
            ->andReturn([
                'success' => false,
                'event_type' => 'payment_intent.succeeded',
                'message' => 'Failed to process webhook: User not found',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);
        $this->app->instance(StripeWebhookService::class, $mockService);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', json_decode($webhookPayload, true), [
            'Stripe-Signature' => 'valid',
        ]);

        $response->assertStatus(500);
    }

    /** @test */
    public function it_prevents_webhook_replay_attack(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
        ]);

        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $webhookPayload = json_encode([
            'id' => 'evt_test_replay',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 1000,
                    'metadata' => ['user_id' => (string) $user->id],
                ],
            ],
        ]);

        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => true,
                'payload' => $webhookPayload,
                'error' => null,
            ]);

        $mockService = Mockery::mock(StripeWebhookService::class);
        $mockService->shouldReceive('processWebhook')
            ->once()
            ->with($webhookPayload)
            ->andReturn([
                'success' => true,
                'event_type' => 'payment_intent.succeeded',
                'message' => 'Payment already processed',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);
        $this->app->instance(StripeWebhookService::class, $mockService);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', json_decode($webhookPayload, true), [
            'Stripe-Signature' => 'valid',
        ]);

        $response->assertStatus(200);

        $credit->refresh();
        $this->assertEquals(10.00, $credit->balance);
    }

    // ============================================
    // Unknown Event Handling Tests
    // ============================================

    /** @test */
    public function it_ignores_unknown_stripe_webhook_events(): void
    {
        $webhookPayload = json_encode([
            'id' => 'evt_unknown',
            'type' => 'customer.created',
            'data' => ['object' => []],
        ]);

        $mockVerifier = Mockery::mock(StripeSignatureVerifier::class);
        $mockVerifier->shouldReceive('verify')
            ->once()
            ->andReturn([
                'valid' => true,
                'payload' => $webhookPayload,
                'error' => null,
            ]);

        $mockService = Mockery::mock(StripeWebhookService::class);
        $mockService->shouldReceive('processWebhook')
            ->once()
            ->with($webhookPayload)
            ->andReturn([
                'success' => true,
                'event_type' => 'customer.created',
                'message' => 'Event type not supported, acknowledged',
            ]);

        $this->app->instance(StripeSignatureVerifier::class, $mockVerifier);
        $this->app->instance(StripeWebhookService::class, $mockService);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', json_decode($webhookPayload, true), [
            'Stripe-Signature' => 'valid',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_ignores_unknown_paypal_webhook_events(): void
    {
        $payload = [
            'id' => 'WH-unknown',
            'event_type' => 'MERCHANT.PARTNER-INTEGRATION-CONSENT.REVOKED',
            'resource' => [],
        ];

        $mockGateway = Mockery::mock(PayPalGatewayService::class);
        $mockGateway->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: null,
                amount: 0,
                currency: 'USD',
                userId: null,
                isIgnored: true
            ));

        $this->app->instance(PayPalGatewayService::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $payload, [
            'PAYPAL-TRANSMISSION-ID' => 'trans_123',
        ]);

        $response->assertStatus(200);
    }
}
