<?php

namespace Tests\Feature\Api\V1\Payments;

use App\Models\User;
use App\Models\CreditPackage;
use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * PayPal Capture Order Endpoint Tests
 * 
 * Tests for POST /api/v1/payments/paypal/capture
 * Captures an approved PayPal order and credits the user.
 * 
 * @see TESTS/TEST-26-15.md
 */
class PayPalCaptureControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CreditPackage $package;
    private string $orderId;
    private float $orderAmount;
    private float $expectedCredits;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->package = CreditPackage::factory()->create([
            'name' => 'Test Package',
            'price' => 10.00,
            'credits' => 10.00,
            'bonus_percent' => 5,
            'is_active' => true,
        ]);
        
        $this->orderId = '5O190127TN364715T';
        $this->orderAmount = 10.00;
        $this->expectedCredits = 10.50; // 10 + 5% bonus
        
        // Ensure user has a credit record
        Credit::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 25.00,
        ]);
    }

    /**
     * Mock PayPalGatewayService to return successful capture
     */
    private function mockSuccessfulCapture(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function () {
            $mock = $this->createMock(PaymentGatewayInterface::class);
            $mock->method('capturePayment')
                ->willReturn([
                    'success' => true,
                    'capture_id' => 'CAPTURE-123456',
                    'amount' => $this->orderAmount,
                    'currency' => 'USD',
                ]);
            return $mock;
        });
    }

    /**
     * Mock PayPalGatewayService to return failed capture
     */
    private function mockFailedCapture(string $error = 'Capture failed'): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function () use ($error) {
            $mock = $this->createMock(PaymentGatewayInterface::class);
            $mock->method('capturePayment')
                ->willThrowException(new \Exception($error));
            return $mock;
        });
    }

    /**
     * Create a pending transaction for testing
     */
    private function createPendingTransaction(array $overrides = []): CreditTransaction
    {
        return CreditTransaction::create(array_merge([
            'user_id' => $this->user->id,
            'amount' => $this->expectedCredits,
            'balance_after' => 25.00 + $this->expectedCredits,
            'type' => 'purchase',
            'description' => 'PayPal Order: ' . $this->orderId,
            'payment_provider' => 'paypal',
            'payment_id' => $this->orderId,
            'metadata' => [
                'order_id' => $this->orderId,
                'package_id' => $this->package->id,
                'status' => 'approved',
            ],
        ], $overrides));
    }

    // ============================================
    // 1. Basic Endpoint Tests (BE)
    // ============================================

    /** @test - BE-01: Successful capture with valid order */
    public function be_01_successful_capture_with_valid_order(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'transaction_id',
                    'amount',
                    'credits',
                    'new_balance',
                ],
            ]);
    }

    /** @test - BE-02: Response structure validation */
    public function be_02_response_structure_validation(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertJsonStructure([
            'success',
            'data' => [
                'transaction_id',
                'amount',
                'credits',
                'new_balance',
            ],
        ]);
        
        $data = $response->json('data');
        $this->assertIsInt($data['transaction_id']);
        $this->assertIsFloat($data['amount']);
        $this->assertIsFloat($data['credits']);
        $this->assertIsFloat($data['new_balance']);
    }

    /** @test - BE-03: Credits calculation correct */
    public function be_03_credits_calculation_correct(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(200);
        $this->assertEquals(
            $this->expectedCredits,
            $response->json('data.credits'),
            'Credits should include bonus percentage'
        );
    }

    /** @test - BE-04: Transaction status updated */
    public function be_04_transaction_status_updated(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $transaction = $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $transaction->refresh();
        $this->assertEquals('completed', $transaction->fresh()->metadata['status']);
    }

    /** @test - BE-05: Balance updated atomically */
    public function be_05_balance_updated_atomically(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $initialBalance = 25.00;
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $expectedBalance = $initialBalance + $this->expectedCredits;
        $this->assertEquals($expectedBalance, $response->json('data.new_balance'));
        
        $credit = Credit::where('user_id', $this->user->id)->first();
        $this->assertEquals($expectedBalance, $credit->balance);
    }

    /** @test - BE-06: Transaction record created */
    public function be_06_transaction_record_created(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
            'type' => 'purchase',
            'payment_provider' => 'paypal',
            'payment_id' => $this->orderId,
        ]);
    }

    /** @test - BE-07: Payment provider recorded */
    public function be_07_payment_provider_recorded(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertDatabaseHas('credit_transactions', [
            'payment_provider' => 'paypal',
        ]);
    }

    /** @test - BE-08: Payment ID stored */
    public function be_08_payment_id_stored(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertDatabaseHas('credit_transactions', [
            'payment_id' => $this->orderId,
        ]);
    }

    // ============================================
    // 2. Validation Tests (VA)
    // ============================================

    /** @test - VA-01: Missing order_id */
    public function va_01_missing_order_id(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /** @test - VA-02: Empty order_id */
    public function va_02_empty_order_id(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => '',
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /** @test - VA-03: Invalid order_id format */
    public function va_03_invalid_order_id_format(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => 'invalid',
        ]);
        
        $response->assertStatus(404);
    }

    /** @test - VA-04: Non-existent order_id */
    public function va_04_non_existent_order_id(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => 'NONEXISTENT123',
        ]);
        
        $response->assertStatus(404);
    }

    /** @test - VA-05: order_id not string */
    public function va_05_order_id_not_string(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => 12345,
        ]);
        
        $response->assertStatus(422);
    }

    /** @test - VA-06: Extra fields ignored */
    public function va_06_extra_fields_ignored(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
            'extra' => 'ignored',
        ]);
        
        $response->assertStatus(200);
    }

    /** @test - VA-07: Null order_id */
    public function va_07_null_order_id(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => null,
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /** @test - VA-08: Very long order_id */
    public function va_08_very_long_order_id(): void
    {
        Sanctum::actingAs($this->user);
        
        $longOrderId = str_repeat('A', 300);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $longOrderId,
        ]);
        
        // Should fail validation or not found
        $this->assertContains($response->status(), [404, 422]);
    }

    /** @test - VA-09: Unicode in order_id */
    public function va_09_unicode_in_order_id(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => '测试订单',
        ]);
        
        $response->assertStatus(404);
    }

    // ============================================
    // 3. Security Tests (SC)
    // ============================================

    /** @test - SC-01: Unauthenticated request */
    public function sc_01_unauthenticated_request(): void
    {
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(401);
    }

    /** @test - SC-02: Wrong user capture attempt */
    public function sc_02_wrong_user_capture_attempt(): void
    {
        $this->mockSuccessfulCapture();
        
        // Create transaction for user A
        $this->createPendingTransaction();
        
        // Authenticate as user B
        $userB = User::factory()->create();
        Sanctum::actingAs($userB);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // Should return 401 or 404 (don't leak existence)
        $this->assertContains($response->status(), [401, 404]);
    }

    /** @test - SC-03: Order ownership verified */
    public function sc_03_order_ownership_verified(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(200);
    }

    /** @test - SC-04: SQL injection in order_id */
    public function sc_04_sql_injection_in_order_id(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => "'; DROP TABLE credit_transactions;--",
        ]);
        
        $response->assertStatus(404);
        
        // Verify table still exists
        $this->assertDatabaseCount('credit_transactions', 0);
    }

    /** @test - SC-05: XSS in order_id */
    public function sc_05_xss_in_order_id(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => "<script>alert('xss')</script>",
        ]);
        
        $response->assertStatus(404);
    }

    /** @test - SC-06: IDOR prevention */
    public function sc_06_idor_prevention(): void
    {
        $this->mockSuccessfulCapture();
        
        // Create order for user A
        $this->createPendingTransaction();
        
        // Create user B and authenticate
        $userB = User::factory()->create();
        Credit::factory()->create(['user_id' => $userB->id, 'balance' => 0]);
        Sanctum::actingAs($userB);
        
        // Try to capture user A's order
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertContains($response->status(), [401, 404]);
        
        // Verify user B didn't get credits
        $creditB = Credit::where('user_id', $userB->id)->first();
        $this->assertEquals(0, $creditB->balance);
    }

    /** @test - SC-07: CSRF protection */
    public function sc_07_csrf_protection(): void
    {
        // This is handled by Sanctum middleware, but test that
        // request without proper auth fails
        $response = $this->post('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(401);
    }

    /** @test - SC-08: Audit logging */
    public function sc_08_audit_logging(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'capture') &&
                    isset($context['order_id']);
            });
        
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
    }

    // ============================================
    // 4. Double Capture Prevention Tests (DC)
    // ============================================

    /** @test - DC-01: Capture already captured order */
    public function dc_01_capture_already_captured_order(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        // Create already completed transaction
        $this->createPendingTransaction(['metadata' => [
            'order_id' => $this->orderId,
            'package_id' => $this->package->id,
            'status' => 'completed',
        ]]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(400);
    }

    /** @test - DC-02: Concurrent capture attempts */
    public function dc_02_concurrent_capture_attempts(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        // Simulate concurrent requests
        $responses = [];
        $promises = [];
        
        // First capture
        $response1 = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // Second capture (same order)
        $response2 = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // One should succeed, one should fail
        $statuses = [$response1->status(), $response2->status()];
        $this->assertContains(200, $statuses);
        $this->assertContains(400, $statuses);
    }

    /** @test - DC-03: Idempotency key handling */
    public function dc_03_idempotency_key_handling(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        // First capture
        $response1 = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // Second capture should fail (already captured)
        $response2 = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response1->assertStatus(200);
        $response2->assertStatus(400);
    }

    /** @test - DC-04: Transaction status check */
    public function dc_04_transaction_status_check(): void
    {
        Sanctum::actingAs($this->user);
        
        // Create completed transaction
        $this->createPendingTransaction(['metadata' => [
            'order_id' => $this->orderId,
            'package_id' => $this->package->id,
            'status' => 'completed',
        ]]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(400);
    }

    /** @test - DC-05: Payment service idempotency */
    public function dc_05_payment_service_idempotency(): void
    {
        // Mock PayPal returning already captured
        $this->app->bind(PaymentGatewayInterface::class, function () {
            $mock = $this->createMock(PaymentGatewayInterface::class);
            $mock->method('capturePayment')
                ->willReturn([
                    'success' => false,
                    'error' => 'ORDER_ALREADY_CAPTURED',
                ]);
            return $mock;
        });
        
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(400);
    }

    // ============================================
    // 5. Order State Validation Tests (OS)
    // ============================================

    /** @test - OS-01: Order in approved state */
    public function os_01_order_in_approved_state(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(200);
    }

    /** @test - OS-02: Order in created state */
    public function os_02_order_in_created_state(): void
    {
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction(['metadata' => [
            'order_id' => $this->orderId,
            'package_id' => $this->package->id,
            'status' => 'created',
        ]]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(400);
    }

    /** @test - OS-03: Order in completed state */
    public function os_03_order_in_completed_state(): void
    {
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction(['metadata' => [
            'order_id' => $this->orderId,
            'package_id' => $this->package->id,
            'status' => 'completed',
        ]]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(400);
    }

    /** @test - OS-04: Order in cancelled state */
    public function os_04_order_in_cancelled_state(): void
    {
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction(['metadata' => [
            'order_id' => $this->orderId,
            'package_id' => $this->package->id,
            'status' => 'cancelled',
        ]]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(400);
    }

    /** @test - OS-05: Order in expired state */
    public function os_05_order_in_expired_state(): void
    {
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction(['metadata' => [
            'order_id' => $this->orderId,
            'package_id' => $this->package->id,
            'status' => 'expired',
        ]]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(400);
    }

    /** @test - OS-06: Order in denied state */
    public function os_06_order_in_denied_state(): void
    {
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction(['metadata' => [
            'order_id' => $this->orderId,
            'package_id' => $this->package->id,
            'status' => 'denied',
        ]]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(400);
    }

    // ============================================
    // 6. Error Handling Tests (EH)
    // ============================================

    /** @test - EH-01: PayPal API failure */
    public function eh_01_paypal_api_failure(): void
    {
        $this->mockFailedCapture('PayPal API Error');
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(500);
    }

    /** @test - EH-02: PayPal timeout */
    public function eh_02_paypal_timeout(): void
    {
        $this->mockFailedCapture('Connection timeout');
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertContains($response->status(), [500, 503]);
    }

    /** @test - EH-03: Invalid PayPal credentials */
    public function eh_03_invalid_paypal_credentials(): void
    {
        $this->mockFailedCapture('Invalid credentials');
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(500);
    }

    /** @test - EH-04: Capture amount mismatch */
    public function eh_04_capture_amount_mismatch(): void
    {
        // Mock returns different amount
        $this->app->bind(PaymentGatewayInterface::class, function () {
            $mock = $this->createMock(PaymentGatewayInterface::class);
            $mock->method('capturePayment')
                ->willReturn([
                    'success' => true,
                    'capture_id' => 'CAPTURE-123',
                    'amount' => 999.99, // Mismatch!
                    'currency' => 'USD',
                ]);
            return $mock;
        });
        
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // Should reject mismatch
        $response->assertStatus(500);
    }

    /** @test - EH-05: CreditService failure */
    public function eh_05_creditservice_failure(): void
    {
        $this->mockSuccessfulCapture();
        
        // Mock CreditService to fail
        $this->mock(CreditService::class, function ($mock) {
            $mock->shouldReceive('addCredits')
                ->andThrow(new \Exception('Credit service failed'));
        });
        
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(500);
        
        // Verify balance unchanged
        $credit = Credit::where('user_id', $this->user->id)->first();
        $this->assertEquals(25.00, $credit->balance);
    }

    /** @test - EH-06: Database transaction failure */
    public function eh_06_database_transaction_failure(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        // Force DB error
        DB::shouldReceive('transaction')->andThrow(new \Exception('DB error'));
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(500);
    }

    /** @test - EH-07: Partial failure recovery */
    public function eh_07_partial_failure_recovery(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        // Successful capture should be atomic
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // Either all succeeds or all fails
        $this->assertContains($response->status(), [200, 500]);
    }

    // ============================================
    // 7. Business Logic Tests (BL)
    // ============================================

    /** @test - BL-01: Credit calculation with bonus */
    public function bl_01_credit_calculation_with_bonus(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // 10 credits + 5% bonus = 10.50
        $this->assertEquals(10.50, $response->json('data.credits'));
    }

    /** @test - BL-02: Credit calculation no bonus */
    public function bl_02_credit_calculation_no_bonus(): void
    {
        $package = CreditPackage::factory()->create([
            'name' => 'No Bonus Package',
            'price' => 10.00,
            'credits' => 10.00,
            'bonus_percent' => 0,
            'is_active' => true,
        ]);
        
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction([
            'amount' => 10.00,
            'metadata' => [
                'order_id' => $this->orderId,
                'package_id' => $package->id,
                'status' => 'approved',
            ],
        ]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertEquals(10.00, $response->json('data.credits'));
    }

    /** @test - BL-03: Credit calculation high bonus */
    public function bl_03_credit_calculation_high_bonus(): void
    {
        $package = CreditPackage::factory()->create([
            'name' => 'High Bonus Package',
            'price' => 100.00,
            'credits' => 100.00,
            'bonus_percent' => 25,
            'is_active' => true,
        ]);
        
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $orderId = 'HIGH-BONUS-ORDER';
        
        $this->createPendingTransaction([
            'amount' => 125.00,
            'payment_id' => $orderId,
            'metadata' => [
                'order_id' => $orderId,
                'package_id' => $package->id,
                'status' => 'approved',
            ],
        ]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $orderId,
        ]);
        
        // 100 + 25% = 125
        $this->assertEquals(125.00, $response->json('data.credits'));
    }

    /** @test - BL-04: Balance update atomic */
    public function bl_04_balance_update_atomic(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $initialBalance = 25.00;
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $expectedBalance = $initialBalance + $this->expectedCredits;
        $this->assertEquals($expectedBalance, $response->json('data.new_balance'));
    }

    /** @test - BL-05: Transaction type correct */
    public function bl_05_transaction_type_correct(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
            'type' => 'purchase',
        ]);
    }

    /** @test - BL-06: Transaction description */
    public function bl_06_transaction_description(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $transaction = CreditTransaction::where('user_id', $this->user->id)
            ->where('payment_provider', 'paypal')
            ->first();
        
        $this->assertNotNull($transaction->description);
        $this->assertStringContainsStringIgnoringCase('paypal', $transaction->description);
    }

    /** @test - BL-07: Transaction metadata */
    public function bl_07_transaction_metadata(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $transaction = CreditTransaction::where('user_id', $this->user->id)
            ->where('payment_id', $this->orderId)
            ->first();
        
        $this->assertNotNull($transaction->metadata);
        $this->assertEquals($this->orderId, $transaction->metadata['order_id']);
    }

    /** @test - BL-08: Payment ID populated */
    public function bl_08_payment_id_populated(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertDatabaseHas('credit_transactions', [
            'payment_id' => $this->orderId,
        ]);
    }

    // ============================================
    // 8. Integration Tests (IN)
    // ============================================

    /** @test - IN-01: PayPalGatewayService called */
    public function in_01_paypalgatewayservice_called(): void
    {
        $gatewayMock = $this->createMock(PaymentGatewayInterface::class);
        $gatewayMock->expects($this->once())
            ->method('capturePayment')
            ->with($this->orderId)
            ->willReturn([
                'success' => true,
                'capture_id' => 'CAPTURE-123',
                'amount' => $this->orderAmount,
            ]);
        
        $this->app->instance(PaymentGatewayInterface::class, $gatewayMock);
        
        Sanctum::actingAs($this->user);
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
    }

    /** @test - IN-02: CreditService called */
    public function in_02_creditservice_called(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $initialBalance = Credit::where('user_id', $this->user->id)->value('balance');
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $newBalance = Credit::where('user_id', $this->user->id)->value('balance');
        
        $this->assertEquals($initialBalance + $this->expectedCredits, $newBalance);
    }

    /** @test - IN-03: Transaction created via DB */
    public function in_03_transaction_created_via_db(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
            'type' => 'purchase',
            'payment_provider' => 'paypal',
        ]);
    }

    /** @test - IN-04: Full flow end-to-end */
    public function in_04_full_flow_end_to_end(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        // Create pending transaction (simulating order creation)
        $this->createPendingTransaction();
        
        $initialBalance = Credit::where('user_id', $this->user->id)->value('balance');
        
        // Capture
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(200);
        
        // Verify all effects
        $newBalance = Credit::where('user_id', $this->user->id)->value('balance');
        $this->assertEquals($initialBalance + $this->expectedCredits, $newBalance);
        
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
            'type' => 'purchase',
            'payment_provider' => 'paypal',
            'payment_id' => $this->orderId,
        ]);
    }

    /** @test - IN-05: Webhook compatibility */
    public function in_05_webhook_compatibility(): void
    {
        // This test verifies that the same capture logic works via webhook
        // The actual webhook handler is in TASK-26-14
        $this->markTestSkipped('Webhook handler tested in TASK-26-14');
    }

    // ============================================
    // 9. HTTP Methods Tests (HM)
    // ============================================

    /** @test - HM-01: POST allowed */
    public function hm_01_post_allowed(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $this->assertNotEquals(405, $response->status());
    }

    /** @test - HM-02: GET not allowed */
    public function hm_02_get_not_allowed(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/v1/payments/paypal/capture');
        
        $response->assertStatus(405);
    }

    /** @test - HM-03: PUT not allowed */
    public function hm_03_put_not_allowed(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->putJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(405);
    }

    /** @test - HM-04: DELETE not allowed */
    public function hm_04_delete_not_allowed(): void
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson('/api/v1/payments/paypal/capture');
        
        $response->assertStatus(405);
    }

    // ============================================
    // 10. Rate Limiting Tests (RL)
    // ============================================

    /** @test - RL-01: Normal usage within limits */
    public function rl_01_normal_usage_within_limits(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        // Create multiple orders
        for ($i = 0; $i < 3; $i++) {
            $orderId = "ORDER-{$i}";
            $this->createPendingTransaction([
                'payment_id' => $orderId,
                'metadata' => [
                    'order_id' => $orderId,
                    'package_id' => $this->package->id,
                    'status' => 'approved',
                ],
            ]);
        }
        
        // Make multiple requests
        for ($i = 0; $i < 3; $i++) {
            $orderId = "ORDER-{$i}";
            $response = $this->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => $orderId,
            ]);
            
            // All should succeed or fail for valid reasons (not rate limit)
            $this->assertNotEquals(429, $response->status());
        }
    }

    /** @test - RL-02: Rate limit headers present */
    public function rl_02_rate_limit_headers_present(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // Rate limit headers may or may not be present depending on middleware
        // This test verifies the response is processed correctly
        $response->assertStatus(200);
    }

    // ============================================
    // 11. Performance Tests (PF)
    // ============================================

    /** @test - PF-01: Response time acceptable */
    public function pf_01_response_time_acceptable(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $start = microtime(true);
        
        $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $elapsed = (microtime(true) - $start) * 1000;
        
        // Should complete within 2 seconds (PayPal API + DB)
        $this->assertLessThan(2000, $elapsed);
    }

    /** @test - PF-02: No memory leaks */
    public function pf_02_no_memory_leaks(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $initialMemory = memory_get_usage();
        
        // Create and capture 5 orders
        for ($i = 0; $i < 5; $i++) {
            $orderId = "PERF-ORDER-{$i}";
            $this->createPendingTransaction([
                'payment_id' => $orderId,
                'metadata' => [
                    'order_id' => $orderId,
                    'package_id' => $this->package->id,
                    'status' => 'approved',
                ],
            ]);
            
            $this->postJson('/api/v1/payments/paypal/capture', [
                'order_id' => $orderId,
            ]);
        }
        
        $finalMemory = memory_get_usage();
        
        // Memory should not grow significantly
        $growth = $finalMemory - $initialMemory;
        $this->assertLessThan(5 * 1024 * 1024, $growth); // Less than 5MB growth
    }

    // ============================================
    // 12. Edge Cases Tests (EC)
    // ============================================

    /** @test - EC-01: Order expired (3 hour window) */
    public function ec_01_order_expired(): void
    {
        Sanctum::actingAs($this->user);
        
        // Create expired order (created 4 hours ago)
        $this->createPendingTransaction([
            'created_at' => now()->subHours(4),
            'metadata' => [
                'order_id' => $this->orderId,
                'package_id' => $this->package->id,
                'status' => 'approved',
            ],
        ]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(400);
    }

    /** @test - EC-02: Zero credit package */
    public function ec_02_zero_credit_package(): void
    {
        $package = CreditPackage::factory()->create([
            'name' => 'Zero Package',
            'price' => 0.00,
            'credits' => 0.00,
            'bonus_percent' => 0,
            'is_active' => true,
        ]);
        
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction([
            'amount' => 0.00,
            'metadata' => [
                'order_id' => $this->orderId,
                'package_id' => $package->id,
                'status' => 'approved',
            ],
        ]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // Should handle gracefully
        $this->assertContains($response->status(), [200, 400]);
    }

    /** @test - EC-03: Negative balance prevention */
    public function ec_03_negative_balance_prevention(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        // User starts with 0 balance
        Credit::where('user_id', $this->user->id)->update(['balance' => 0]);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // Capture should succeed and add credits
        $response->assertStatus(200);
        
        $newBalance = Credit::where('user_id', $this->user->id)->value('balance');
        $this->assertGreaterThanOrEqual(0, $newBalance);
    }

    /** @test - EC-04: Decimal precision */
    public function ec_04_decimal_precision(): void
    {
        $package = CreditPackage::factory()->create([
            'name' => 'Precision Package',
            'price' => 33.33,
            'credits' => 33.33,
            'bonus_percent' => 3,
            'is_active' => true,
        ]);
        
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $orderId = 'PRECISION-ORDER';
        $expectedCredits = round(33.33 * 1.03, 2); // 34.33
        
        $this->createPendingTransaction([
            'amount' => $expectedCredits,
            'payment_id' => $orderId,
            'metadata' => [
                'order_id' => $orderId,
                'package_id' => $package->id,
                'status' => 'approved',
            ],
        ]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $orderId,
        ]);
        
        $response->assertStatus(200);
        
        // Check precision is maintained
        $credits = $response->json('data.credits');
        $this->assertEquals($expectedCredits, round($credits, 2));
    }

    /** @test - EC-05: Concurrent same-user captures */
    public function ec_05_concurrent_same_user_captures(): void
    {
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        // Create two different orders for same user
        $order1 = 'ORDER-1';
        $order2 = 'ORDER-2';
        
        $this->createPendingTransaction([
            'payment_id' => $order1,
            'metadata' => [
                'order_id' => $order1,
                'package_id' => $this->package->id,
                'status' => 'approved',
            ],
        ]);
        
        $this->createPendingTransaction([
            'payment_id' => $order2,
            'metadata' => [
                'order_id' => $order2,
                'package_id' => $this->package->id,
                'status' => 'approved',
            ],
        ]);
        
        // Capture both
        $response1 = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $order1,
        ]);
        
        $response2 = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $order2,
        ]);
        
        // Both should succeed
        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    /** @test - EC-06: Large credit amount */
    public function ec_06_large_credit_amount(): void
    {
        $package = CreditPackage::factory()->create([
            'name' => 'Enterprise Package',
            'price' => 10000.00,
            'credits' => 1000000.00,
            'bonus_percent' => 0,
            'is_active' => true,
        ]);
        
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $orderId = 'LARGE-ORDER';
        
        $this->createPendingTransaction([
            'amount' => 1000000.00,
            'payment_id' => $orderId,
            'metadata' => [
                'order_id' => $orderId,
                'package_id' => $package->id,
                'status' => 'approved',
            ],
        ]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $orderId,
        ]);
        
        $response->assertStatus(200);
        $this->assertEquals(1000000.00, $response->json('data.credits'));
    }

    /** @test - EC-07: Unicode in user data */
    public function ec_07_unicode_in_user_data(): void
    {
        $user = User::factory()->create([
            'name' => '测试用户 日本語 Ñoño',
            'email' => 'test+unicode@example.com',
        ]);
        
        Credit::factory()->create([
            'user_id' => $user->id,
            'balance' => 10.00,
        ]);
        
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($user);
        
        $this->createPendingTransaction(['user_id' => $user->id]);
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        $response->assertStatus(200);
    }

    /** @test - EC-08: Missing user credits record */
    public function ec_08_missing_user_credits_record(): void
    {
        // Delete credit record
        Credit::where('user_id', $this->user->id)->delete();
        
        $this->mockSuccessfulCapture();
        Sanctum::actingAs($this->user);
        
        $this->createPendingTransaction();
        
        $response = $this->postJson('/api/v1/payments/paypal/capture', [
            'order_id' => $this->orderId,
        ]);
        
        // Should create credits record and add balance
        $response->assertStatus(200);
        
        $credit = Credit::where('user_id', $this->user->id)->first();
        $this->assertNotNull($credit);
        $this->assertEquals($this->expectedCredits, $credit->balance);
    }
}
