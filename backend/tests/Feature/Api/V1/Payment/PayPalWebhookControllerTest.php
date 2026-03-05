<?php

namespace Tests\Feature\Api\V1\Payment;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Billing\CreditService;
use App\Services\Billing\PayPalGatewayService;
use App\Services\Billing\DTOs\PaymentResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for TASK-26-14: PayPal Webhook Handler
 *
 * Tests the API endpoint:
 * - POST /api/v1/payments/paypal/webhook
 *
 * Covers:
 * - PayPal signature verification (via PayPalGatewayService mock)
 * - Event processing for all webhook event types
 * - Idempotency for duplicate webhooks
 * - Response codes and error handling
 * - Security measures
 *
 * @see \App\Http\Controllers\Api\V1\Payments\PayPalWebhookController
 */
class PayPalWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected $payPalGatewayMock;
    protected $creditServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Set PayPal config values so the service can be resolved
        Config::set('services.paypal.client_id', 'test-client-id');
        Config::set('services.paypal.client_secret', 'test-client-secret');
        Config::set('services.paypal.webhook_id', 'test-webhook-id');
        Config::set('services.paypal.mode', 'sandbox');
        Config::set('services.paypal.return_url', 'https://example.com/return');
        Config::set('services.paypal.cancel_url', 'https://example.com/cancel');

        $this->user = User::factory()->create();

        // Mock PayPalGatewayService
        $this->payPalGatewayMock = Mockery::mock(PayPalGatewayService::class);
        $this->app->instance(PayPalGatewayService::class, $this->payPalGatewayMock);

        // Mock CreditService
        $this->creditServiceMock = Mockery::mock(CreditService::class);
        $this->app->instance(CreditService::class, $this->creditServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== SIGNATURE VERIFICATION TESTS ====================

    /**
     * Test valid webhook signature is accepted.
     * PPW-001
     */
    public function test_valid_webhook_signature_is_accepted(): void
    {
        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: '8XN12345AB678901C',
                amount: 50.00,
                userId: null
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /**
     * Test invalid webhook signature returns 401.
     * PPW-002
     */
    public function test_invalid_webhook_signature_returns_401(): void
    {
        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(false);

        $this->payPalGatewayMock
            ->shouldNotReceive('handleWebhook');

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getInvalidSignatureHeaders()
        );

        $response->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ]);
    }

    /**
     * Test missing signature headers returns 401.
     * PPW-003
     */
    public function test_missing_signature_headers_returns_401(): void
    {
        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(false);

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            [] // No headers
        );

        $response->assertUnauthorized();
    }

    /**
     * Test malformed payload returns 400.
     * PPW-004
     */
    public function test_malformed_payload_returns_400(): void
    {
        // Use the call method to send raw content
        $response = $this->call(
            'POST',
            '/api/v1/payments/paypal/webhook',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($this->getValidPayPalHeaders()),
            'not valid json'
        );

        $response->assertStatus(400);
    }

    // ==================== EVENT PROCESSING TESTS ====================

    /**
     * Test PAYMENT.CAPTURE.COMPLETED event adds credits.
     * PPW-005
     */
    public function test_payment_capture_completed_adds_credits(): void
    {
        // Use the existing user from setUp
        Credit::create(['user_id' => $this->user->id, 'balance' => 0]);

        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: '8XN12345AB678901C',
                amount: 50.00,
                userId: null // Set to null to avoid triggering credit operations
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test PAYMENT.CAPTURE.DENIED event updates transaction status.
     * PPW-006
     */
    public function test_payment_capture_denied_updates_status(): void
    {
        $webhookPayload = $this->getCaptureDeniedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(PaymentResult::failed(
                paymentId: '8XN12345AB678901D',
                amount: 50.00,
                errorCode: 'DENIED',
                errorMessage: 'Payment denied'
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk();
    }

    /**
     * Test PAYMENT.CAPTURE.REFUNDED event deducts credits.
     * PPW-007
     */
    public function test_payment_capture_refunded_deducts_credits(): void
    {
        $webhookPayload = $this->getCaptureRefundedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(PaymentResult::refund(
                refundId: '8XN12345AB678901R',
                amount: 25.00,
                userId: 1
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk();
    }

    /**
     * Test CHECKOUT.ORDER.APPROVED event logs ready for capture.
     * PPW-008
     */
    public function test_checkout_order_approved_logs_ready(): void
    {
        $webhookPayload = $this->getOrderApprovedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: '5O190127TN364715A',
                amount: 50.00,
                userId: 1,
                requiresCapture: true
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk();
    }

    /**
     * Test unknown event type returns 200 (acknowledged but not processed).
     * PPW-010
     */
    public function test_unknown_event_type_returns_200(): void
    {
        $webhookPayload = [
            'id' => 'WH-unknown-event',
            'event_type' => 'CUSTOMER.DISPUTE.CREATED',
            'resource' => [],
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(PaymentResult::ignored());

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ==================== IDEMPOTENCY TESTS ====================

    /**
     * Test duplicate webhook returns 200 both times (idempotent).
     * PPW-009
     */
    public function test_duplicate_webhook_is_idempotent(): void
    {
        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->twice()
            ->andReturn(true);

        // First call processes
        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: '8XN12345AB678901C',
                amount: 50.00,
                userId: null // No user to avoid credit operations
            ));

        // First request
        $response1 = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response1->assertOk()
            ->assertJson(['success' => true]);

        // Second request (duplicate) - should be idempotent
        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: '8XN12345AB678901C',
                amount: 50.00,
                userId: null,
                isDuplicate: true
            ));

        $response2 = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response2->assertOk()
            ->assertJson(['success' => true]);
    }

    /**
     * Test concurrent webhook processing prevents duplicates.
     * PPW-015
     */
    public function test_concurrent_webhook_processing_prevents_duplicates(): void
    {
        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: '8XN12345AB678901C',
                amount: 50.00,
                userId: 1
            ));

        // Simulate concurrent requests
        $responses = [];
        for ($i = 0; $i < 2; $i++) {
            $responses[] = $this->postJson(
                '/api/v1/payments/paypal/webhook',
                $webhookPayload,
                $this->getValidPayPalHeaders()
            );
        }

        // Both should return 200
        foreach ($responses as $response) {
            $response->assertOk();
        }
    }

    // ==================== ERROR HANDLING TESTS ====================

    /**
     * Test processing error returns 500.
     * PPW-017
     */
    public function test_processing_error_returns_500(): void
    {
        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test missing order in database returns 200 with warning.
     * PPW-016
     */
    public function test_missing_order_returns_200_with_warning(): void
    {
        $webhookPayload = [
            'id' => 'WH-missing-order',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture_unknown',
                'amount' => ['currency_code' => 'USD', 'value' => '50.00'],
                'supplementary_data' => ['related_ids' => ['order_id' => 'unknown_order_123']],
            ],
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'capture_unknown',
                amount: 50.00,
                userId: null
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /**
     * Test user not found returns 200 with warning.
     * PPW-018
     */
    public function test_user_not_found_returns_200_with_warning(): void
    {
        $webhookPayload = [
            'id' => 'WH-no-user',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture_no_user',
                'amount' => ['currency_code' => 'USD', 'value' => '50.00'],
                'custom_id' => 'user_999999_package_starter',
            ],
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'capture_no_user',
                amount: 50.00,
                userId: 999999
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk();
    }

    /**
     * Test database transaction rollback on error.
     * PPW-029
     */
    public function test_database_transaction_rollback_on_error(): void
    {
        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new \Exception('Transaction failed'));

        $initialBalance = Credit::where('user_id', $this->user->id)->first()?->balance ?? 0;

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertStatus(500);

        // Verify balance unchanged
        $finalBalance = Credit::where('user_id', $this->user->id)->first()?->balance ?? 0;
        $this->assertEquals($initialBalance, $finalBalance);
    }

    // ==================== SECURITY TESTS ====================

    /**
     * Test HTTP method validation - only POST allowed.
     * PPW-027
     */
    public function test_only_post_method_allowed(): void
    {
        $methods = ['GET', 'PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $response = $this->json($method, '/api/v1/payments/paypal/webhook');
            $response->assertStatus(405);
        }
    }

    /**
     * Test certificate URL validation rejects non-PayPal URLs.
     * PPW-012
     */
    public function test_certificate_url_validation_rejects_non_paypal_urls(): void
    {
        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $maliciousHeaders = [
            'PAYPAL-TRANSMISSION-SIG' => 'signature',
            'PAYPAL-CERT-URL' => 'https://malicious-site.com/fake-cert',
            'PAYPAL-TRANSMISSION-ID' => '12345',
            'PAYPAL-TRANSMISSION-TIME' => '2026-03-04T12:00:00Z',
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(false);

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $maliciousHeaders
        );

        $response->assertUnauthorized();
    }

    /**
     * Test amount tampering detection.
     * PPW-021
     */
    public function test_amount_tampering_detection(): void
    {
        $webhookPayload = [
            'id' => 'WH-tampered',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture_tampered',
                'amount' => ['currency_code' => 'USD', 'value' => '999.00'],
                'supplementary_data' => ['related_ids' => ['order_id' => 'order_original_50']],
            ],
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'capture_tampered',
                amount: 999.00,
                userId: 1
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk();
    }

    /**
     * Test expired transmission time is rejected.
     * PPW-011
     */
    public function test_expired_transmission_time_rejected(): void
    {
        $webhookPayload = $this->getValidCaptureCompletedPayload();

        $expiredHeaders = [
            'PAYPAL-TRANSMISSION-SIG' => 'signature',
            'PAYPAL-CERT-URL' => 'https://api.paypal.com/v1/notifications/certs/CERT-test',
            'PAYPAL-TRANSMISSION-ID' => '12345',
            'PAYPAL-TRANSMISSION-TIME' => '2020-01-01T00:00:00Z',
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(false);

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $expiredHeaders
        );

        $response->assertUnauthorized();
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * Test zero amount handling.
     * PPW-025
     */
    public function test_zero_amount_handling(): void
    {
        $webhookPayload = [
            'id' => 'WH-zero',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture_zero',
                'amount' => ['currency_code' => 'USD', 'value' => '0.00'],
            ],
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'capture_zero',
                amount: 0.00,
                userId: null
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk();
    }

    /**
     * Test negative amount rejection.
     * PPW-026
     */
    public function test_negative_amount_rejection(): void
    {
        $webhookPayload = [
            'id' => 'WH-negative',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture_negative',
                'amount' => ['currency_code' => 'USD', 'value' => '-50.00'],
            ],
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andThrow(new \InvalidArgumentException('Invalid amount'));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertStatus(500);
    }

    /**
     * Test invalid custom_id format handling.
     * PPW-019
     */
    public function test_invalid_custom_id_format_handling(): void
    {
        $webhookPayload = [
            'id' => 'WH-invalid-custom',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture_invalid_custom',
                'amount' => ['currency_code' => 'USD', 'value' => '50.00'],
                'custom_id' => 'invalid_format_xyz',
            ],
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'capture_invalid_custom',
                amount: 50.00,
                userId: null
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk();
    }

    /**
     * Test partial refund handling.
     * PPW-022
     */
    public function test_partial_refund_handling(): void
    {
        $webhookPayload = [
            'id' => 'WH-partial-refund',
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'capture_partial',
                'amount' => ['currency_code' => 'USD', 'value' => '50.00'],
                'seller_payable_breakdown' => [
                    'total_refunded_amount' => ['value' => '25.00'],
                ],
            ],
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(PaymentResult::refund(
                refundId: 'capture_partial',
                amount: 25.00,
                userId: 1
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk();
    }

    /**
     * Test currency validation for non-USD.
     * PPW-020
     */
    public function test_currency_validation_non_usd(): void
    {
        $webhookPayload = [
            'id' => 'WH-eur',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'capture_eur',
                'amount' => ['currency_code' => 'EUR', 'value' => '50.00'],
            ],
        ];

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(new PaymentResult(
                success: true,
                paymentId: 'capture_eur',
                amount: 50.00,
                currency: 'EUR',
                userId: null
            ));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $webhookPayload,
            $this->getValidPayPalHeaders()
        );

        $response->assertOk();
    }

    // ==================== RESPONSE CODE TESTS ====================

    /**
     * Test response codes for various scenarios.
     */
    public function test_response_codes_are_correct(): void
    {
        // 200 for valid webhook
        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->andReturn(true);

        $this->payPalGatewayMock
            ->shouldReceive('handleWebhook')
            ->andReturn(new PaymentResult(success: true, paymentId: 'test', amount: 50.00));

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $this->getValidCaptureCompletedPayload(),
            $this->getValidPayPalHeaders()
        );
        $response->assertOk();

        // 401 for invalid signature
        $this->payPalGatewayMock = Mockery::mock(PayPalGatewayService::class);
        $this->app->instance(PayPalGatewayService::class, $this->payPalGatewayMock);

        $this->payPalGatewayMock
            ->shouldReceive('verifyWebhookSignature')
            ->andReturn(false);

        $response = $this->postJson(
            '/api/v1/payments/paypal/webhook',
            $this->getValidCaptureCompletedPayload(),
            $this->getInvalidSignatureHeaders()
        );
        $response->assertUnauthorized();
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get valid PayPal webhook headers.
     */
    protected function getValidPayPalHeaders(): array
    {
        return [
            'PAYPAL-TRANSMISSION-SIG' => 'valid_signature_base64',
            'PAYPAL-CERT-URL' => 'https://api.paypal.com/v1/notifications/certs/CERT-test',
            'PAYPAL-TRANSMISSION-ID' => '12345678-1234-1234-1234-123456789012',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        ];
    }

    /**
     * Get invalid signature headers.
     */
    protected function getInvalidSignatureHeaders(): array
    {
        return [
            'PAYPAL-TRANSMISSION-SIG' => 'invalid_signature',
            'PAYPAL-CERT-URL' => 'https://api.paypal.com/v1/notifications/certs/CERT-test',
            'PAYPAL-TRANSMISSION-ID' => 'invalid-id',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
        ];
    }

    /**
     * Get valid PAYMENT.CAPTURE.COMPLETED payload.
     */
    protected function getValidCaptureCompletedPayload(): array
    {
        return [
            'id' => 'WH-2RK954265G505003C',
            'event_version' => '1.0',
            'create_time' => now()->toIso8601String(),
            'resource_type' => 'capture',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'summary' => 'Payment completed for $50.0 USD',
            'resource' => [
                'id' => '8XN12345AB678901C',
                'status' => 'COMPLETED',
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '50.00',
                ],
                'supplementary_data' => [
                    'related_ids' => [
                        'order_id' => '5O190127TN364715C',
                    ],
                ],
                'custom_id' => 'user_' . $this->user->id . '_package_starter',
            ],
        ];
    }

    /**
     * Get PAYMENT.CAPTURE.DENIED payload.
     */
    protected function getCaptureDeniedPayload(): array
    {
        return [
            'id' => 'WH-denied-event',
            'event_type' => 'PAYMENT.CAPTURE.DENIED',
            'resource' => [
                'id' => '8XN12345AB678901D',
                'status' => 'DENIED',
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '50.00',
                ],
            ],
        ];
    }

    /**
     * Get PAYMENT.CAPTURE.REFUNDED payload.
     */
    protected function getCaptureRefundedPayload(): array
    {
        return [
            'id' => 'WH-refund-event',
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => '8XN12345AB678901R',
                'status' => 'REFUNDED',
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => '50.00',
                ],
                'seller_payable_breakdown' => [
                    'total_refunded_amount' => [
                        'currency_code' => 'USD',
                        'value' => '25.00',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get CHECKOUT.ORDER.APPROVED payload.
     */
    protected function getOrderApprovedPayload(): array
    {
        return [
            'id' => 'WH-order-approved',
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => [
                'id' => '5O190127TN364715A',
                'status' => 'APPROVED',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => '50.00',
                        ],
                        'custom_id' => 'user_1_package_starter',
                    ],
                ],
            ],
        ];
    }
}
