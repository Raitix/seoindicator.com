<?php

namespace Tests\Feature\Payments;

use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\Exceptions\PayPalPaymentException;
use App\Services\Billing\Exceptions\PaymentTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Integration tests for PayPal payment flow.
 *
 * TASK-26-26: Write Integration Tests for Payments
 * @see TESTS/TASK-26-26.md
 *
 * These tests use mocked PayPal responses to test the full payment flow
 * without requiring actual PayPal API calls.
 */
class PayPalPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Order Creation Tests
    // ============================================

    /** @test */
    public function it_creates_paypal_order_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->with($user, 10.00, 'USD')
            ->andReturn([
                'order_id' => '5O190127TN364715C',
                'status' => 'CREATED',
                'links' => [
                    [
                        'rel' => 'approve',
                        'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=5O190127TN364715C',
                    ],
                ],
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/order', [
                'amount' => 10.00,
                'package_id' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'order_id',
                'status',
                'links',
            ])
            ->assertJson([
                'status' => 'CREATED',
            ]);
    }

    /** @test */
    public function it_includes_return_and_cancel_urls_in_order(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg, $amount, $currency, $metadata = []) use ($user) {
                return $userArg->id === $user->id
                    && $amount === 25.00
                    && isset($metadata['return_url'])
                    && isset($metadata['cancel_url']);
            })
            ->andReturn([
                'order_id' => '5O190127TN364715C',
                'status' => 'CREATED',
                'links' => [
                    [
                        'rel' => 'approve',
                        'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=5O190127TN364715C',
                    ],
                ],
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/order', [
                'amount' => 25.00,
                'package_id' => 1,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_includes_custom_id_for_tracking(): void
    {
        $user = User::factory()->create();
        $packageId = 2;

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg, $amount, $currency, $metadata = []) use ($user, $packageId) {
                return isset($metadata['custom_id'])
                    && str_contains($metadata['custom_id'], (string) $user->id)
                    && str_contains($metadata['custom_id'], (string) $packageId);
            })
            ->andReturn([
                'order_id' => '5O190127TN364715C',
                'status' => 'CREATED',
                'links' => [],
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/order', [
                'amount' => 25.00,
                'package_id' => $packageId,
            ]);

        $response->assertStatus(200);
    }

    // ============================================
    // Order Capture Tests
    // ============================================

    /** @test */
    public function it_captures_approved_order(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->with('5O190127TN364715C')
            ->andReturn([
                'capture_id' => '8XN93152WY218160S',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
                'payer_id' => 'QPWXRMG3YH5RN',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => '5O190127TN364715C',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'COMPLETED',
            ]);
    }

    /** @test */
    public function it_updates_credits_after_successful_capture(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
            'total_purchased' => 0.00,
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->andReturn([
                'capture_id' => '8XN93152WY218160S',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
                'payer_id' => 'QPWXRMG3YH5RN',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => '5O190127TN364715C',
            ]);

        $response->assertStatus(200);

        // Verify credit was updated
        $credit->refresh();
        $this->assertEquals(10.00, $credit->balance);
        $this->assertEquals(10.00, $credit->total_purchased);

        // Verify transaction was created
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'paypal',
        ]);
    }

    /** @test */
    public function it_verifies_amount_matches_original_order(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->andReturn([
                'capture_id' => '8XN93152WY218160S',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '5.00', // Different from expected $10.00
                    'currency_code' => 'USD',
                ],
                'payer_id' => 'QPWXRMG3YH5RN',
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        // Assume original order was for $10.00
        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => '5O190127TN364715C',
                'expected_amount' => 10.00,
            ]);

        // Should flag amount mismatch
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'amount_mismatch',
            ]);
    }

    /** @test */
    public function it_handles_denied_capture(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->andReturn([
                'capture_id' => '8XN93152WY218160S',
                'status' => 'DENIED',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => '5O190127TN364715C',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'status' => 'DENIED',
            ]);

        // Verify no credits were added
        $this->assertEquals(0, $user->credit->balance ?? 0);
    }

    // ============================================
    // Validation Tests
    // ============================================

    /** @test */
    public function it_validates_minimum_purchase_amount_for_paypal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/order', [
                'amount' => 2.00, // Below minimum of $5
                'package_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_validates_order_id_required_for_capture(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /** @test */
    public function it_validates_order_id_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => 'invalid-format',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    // ============================================
    // Authentication Tests
    // ============================================

    /** @test */
    public function it_requires_authentication_for_paypal_order(): void
    {
        $response = $this->postJson('/api/v1/payments/paypal/order', [
            'amount' => 10.00,
            'package_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_paypal_capture(): void
    {
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => '5O190127TN364715C',
        ]);

        $response->assertStatus(401);
    }

    // ============================================
    // Error Handling Tests
    // ============================================

    /** @test */
    public function it_handles_paypal_api_error(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new PayPalPaymentException(
                'PAYMENT_DENIED',
                'Payment was denied by PayPal'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/order', [
                'amount' => 10.00,
                'package_id' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'payment_failed',
                'message' => 'Payment was denied by PayPal',
            ]);
    }

    /** @test */
    public function it_handles_paypal_timeout(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createPaymentIntent')
            ->once()
            ->andThrow(new PaymentTimeoutException(
                'PayPal service unavailable. Please try again.'
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
    public function it_handles_order_not_found(): void
    {
        $user = User::factory()->create();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->andThrow(new PayPalPaymentException(
                'ORDER_NOT_FOUND',
                'Order not found or expired'
            ));

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => 'NONEXISTENT_ORDER',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'order_not_found',
            ]);
    }

    // ============================================
    // Refund Tests
    // ============================================

    /** @test */
    public function it_processes_paypal_refund(): void
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

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('refundPayment')
            ->once()
            ->with('8XN93152WY218160S', 10.00)
            ->andReturn([
                'refund_id' => '1J114081VH731483M',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/refund', [
                'capture_id' => '8XN93152WY218160S',
                'amount' => 10.00,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify credit balance adjusted
        $credit->refresh();
        $this->assertEquals(0.00, $credit->balance);
    }

    /** @test */
    public function it_processes_partial_paypal_refund(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 20.00,
        ]);

        CreditTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 20.00,
            'type' => 'purchase',
            'payment_provider' => 'paypal',
            'payment_id' => '8XN93152WY218160S',
        ]);

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('refundPayment')
            ->once()
            ->with('8XN93152WY218160S', 10.00)
            ->andReturn([
                'refund_id' => '1J114081VH731483M',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/refund', [
                'capture_id' => '8XN93152WY218160S',
                'amount' => 10.00,
            ]);

        $response->assertStatus(200);

        $credit->refresh();
        $this->assertEquals(10.00, $credit->balance);
    }

    // ============================================
    // Idempotency Tests
    // ============================================

    /** @test */
    public function it_handles_duplicate_capture_request(): void
    {
        $user = User::factory()->create();
        $idempotencyKey = 'idemp_' . uniqid();

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->andReturn([
                'capture_id' => '8XN93152WY218160S',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        // First request
        $response1 = $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => '5O190127TN364715C',
            ]);

        $response1->assertStatus(200);

        // Second request with same idempotency key
        $response2 = $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => '5O190127TN364715C',
            ]);

        // Should return same result without double capture
        $response2->assertStatus(200)
            ->assertJson([
                'capture_id' => '8XN93152WY218160S',
            ]);

        // Verify only one transaction created
        $this->assertEquals(1, CreditTransaction::where('user_id', $user->id)->count());
    }

    // ============================================
    // Payer Information Tests
    // ============================================

    /** @test */
    public function it_stores_payer_id_from_paypal(): void
    {
        $user = User::factory()->create();
        $credit = Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 0.00,
        ]);

        $payerId = 'QPWXRMG3YH5RN';

        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('capturePayment')
            ->once()
            ->andReturn([
                'capture_id' => '8XN93152WY218160S',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '10.00',
                    'currency_code' => 'USD',
                ],
                'payer_id' => $payerId,
            ]);

        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => '5O190127TN364715C',
            ]);

        $response->assertStatus(200);

        // Verify transaction has payer_id
        $transaction = CreditTransaction::where('user_id', $user->id)
            ->where('type', 'purchase')
            ->first();

        $this->assertNotNull($transaction);
        $metadata = $transaction->metadata;
        $this->assertEquals($payerId, $metadata['payer_id'] ?? null);
    }
}
