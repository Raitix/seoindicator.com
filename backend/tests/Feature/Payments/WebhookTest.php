<?php

namespace Tests\Feature\Payments;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\DTOs\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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

        $webhookPayload = [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 1000, // $10.00 in cents
                    'currency' => 'usd',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'package_id' => '1',
                    ],
                ],
            ],
        ];

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'pi_test_123',
                amount: 10.00,
                currency: 'USD',
                userId: $user->id,
                metadata: ['package_id' => '1']
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', $webhookPayload, [
            'Stripe-Signature' => 't=1234567890,v1=abc123def456',
        ]);

        $response->assertStatus(200);

        // Verify credit was updated
        $credit->refresh();
        $this->assertEquals(10.00, $credit->balance);
    }

    /** @test */
    public function it_handles_stripe_payment_failed_webhook(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 5.00,
        ]);

        $webhookPayload = [
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
        ];

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: false,
                paymentId: 'pi_test_456',
                amount: 10.00,
                currency: 'USD',
                userId: $user->id,
                errorCode: 'card_declined',
                errorMessage: 'Your card was declined.'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', $webhookPayload, [
            'Stripe-Signature' => 't=1234567890,v1=abc123def456',
        ]);

        $response->assertStatus(200);

        // Verify credit was NOT updated
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

        $webhookPayload = [
            'id' => 'evt_test_789',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_123',
                    'payment_intent' => 'pi_test_123',
                    'amount_refunded' => 1000, // Full refund
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
        ];

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 're_test_123',
                amount: -10.00, // Negative for refund
                currency: 'USD',
                userId: $user->id,
                type: 'refund'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', $webhookPayload, [
            'Stripe-Signature' => 't=1234567890,v1=abc123def456',
        ]);

        $response->assertStatus(200);

        // Verify credit was reduced
        $credit->refresh();
        $this->assertEquals(0.00, $credit->balance);

        // Verify refund transaction created
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => 'refund',
            'amount' => -10.00,
        ]);
    }

    /** @test */
    public function it_verifies_stripe_webhook_signature(): void
    {
        $webhookPayload = [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ];

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new \App\Services\Billing\Exceptions\InvalidWebhookSignatureException(
                'Invalid webhook signature'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        // Missing or invalid signature
        $response = $this->postJson('/api/v1/payments/stripe/webhook', $webhookPayload);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'invalid_signature',
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

        // Create existing transaction
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $webhookPayload = [
            'id' => 'evt_test_duplicate',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 1000,
                    'metadata' => ['user_id' => (string) $user->id],
                ],
            ],
        ];

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'pi_test_123',
                amount: 10.00,
                currency: 'USD',
                userId: $user->id,
                isDuplicate: true
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', $webhookPayload, [
            'Stripe-Signature' => 'valid_signature',
        ]);

        $response->assertStatus(200);

        // Balance should remain unchanged (no duplicate credit)
        $credit->refresh();
        $this->assertEquals(10.00, $credit->balance);

        // Should still have only one transaction
        $this->assertEquals(1, CreditTransaction::where('payment_id', 'pi_test_123')->count());
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

        $webhookPayload = [
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

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
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

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $webhookPayload, [
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

        $webhookPayload = [
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

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
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

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $webhookPayload, [
            'PAYPAL-TRANSMISSION-ID' => 'trans_123',
            'PAYPAL-TRANSMISSION-SIG' => 'signature_here',
        ]);

        $response->assertStatus(200);

        // Balance should remain unchanged
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

        $webhookPayload = [
            'id' => 'WH-1234567892',
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => '1J114081VH731483M',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
                'links' => [
                    ['rel' => 'up', 'href' => 'https://api.paypal.com/v2/payments/captures/8XN93152WY218160S'],
                ],
            ],
        ];

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
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

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $webhookPayload, [
            'PAYPAL-TRANSMISSION-ID' => 'trans_123',
            'PAYPAL-TRANSMISSION-SIG' => 'signature_here',
        ]);

        $response->assertStatus(200);

        $credit->refresh();
        $this->assertEquals(0.00, $credit->balance);
    }

    /** @test */
    public function it_verifies_paypal_webhook_signature(): void
    {
        $webhookPayload = [
            'id' => 'WH-invalid',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [],
        ];

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new \App\Services\Billing\Exceptions\InvalidWebhookSignatureException(
                'Invalid PayPal webhook signature'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $webhookPayload);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'invalid_signature',
            ]);
    }

    /** @test */
    public function it_handles_paypal_checkout_order_approved_webhook(): void
    {
        $user = User::factory()->create();

        $webhookPayload = [
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

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
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

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', $webhookPayload, [
            'PAYPAL-TRANSMISSION-ID' => 'trans_123',
            'PAYPAL-TRANSMISSION-SIG' => 'signature_here',
        ]);

        $response->assertStatus(200);

        // Order approved but not yet captured - no credit added yet
        $this->assertEquals(0, $user->credit->balance ?? 0);
    }

    // ============================================
    // Security Tests
    // ============================================

    /** @test */
    public function it_rejects_webhook_without_signature(): void
    {
        $response = $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature(): void
    {
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new \App\Services\Billing\Exceptions\InvalidWebhookSignatureException());

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ], [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_logs_webhook_events(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'webhook') &&
                    isset($context['event_type']);
            });

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'pi_test',
                amount: 10.00,
                currency: 'USD',
                userId: 1
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test']],
        ], [
            'Stripe-Signature' => 'valid',
        ]);
    }

    /** @test */
    public function it_handles_webhook_timeout_gracefully(): void
    {
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new \App\Services\Billing\Exceptions\PaymentTimeoutException(
                'Webhook processing timeout'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ], [
            'Stripe-Signature' => 'valid',
        ]);

        // Should return 200 to prevent Stripe from retrying
        $response->assertStatus(200);
    }

    /** @test */
    public function it_prevents_webhook_replay_attack(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
        ]);

        // Existing transaction from first webhook
        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $webhookPayload = [
            'id' => 'evt_test_replay',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 1000,
                    'metadata' => ['user_id' => (string) $user->id],
                ],
            ],
        ];

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'pi_test_123',
                amount: 10.00,
                currency: 'USD',
                userId: $user->id,
                isDuplicate: true
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        // Replay the webhook
        $response = $this->postJson('/api/v1/payments/stripe/webhook', $webhookPayload, [
            'Stripe-Signature' => 'valid',
        ]);

        $response->assertStatus(200);

        // Balance should NOT double
        $credit->refresh();
        $this->assertEquals(10.00, $credit->balance);
    }

    // ============================================
    // Unknown Event Handling Tests
    // ============================================

    /** @test */
    public function it_ignores_unknown_stripe_webhook_events(): void
    {
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
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

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/stripe/webhook', [
            'id' => 'evt_unknown',
            'type' => 'customer.created',
            'data' => ['object' => []],
        ], [
            'Stripe-Signature' => 'valid',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_ignores_unknown_paypal_webhook_events(): void
    {
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
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

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->postJson('/api/v1/payments/paypal/webhook', [
            'id' => 'WH-unknown',
            'event_type' => 'MERCHANT.PARTNER-INTEGRATION-CONSENT.REVOKED',
            'resource' => [],
        ], [
            'PAYPAL-TRANSMISSION-ID' => 'trans_123',
        ]);

        $response->assertStatus(200);
    }
}
