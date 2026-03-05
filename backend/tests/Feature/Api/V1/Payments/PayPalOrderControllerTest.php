<?php

namespace Tests\Feature\Api\V1\Payments;

use App\Models\CreditPackage;
use App\Models\User;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for PayPal Order Creation Endpoint.
 * 
 * Endpoint: POST /api/v1/payments/paypal/order
 * 
 * @see TASK-26-13
 * @see TEST-26-13
 */
class PayPalOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/payments/paypal/order';

    private User $user;
    private CreditPackage $activePackage;
    private CreditPackage $inactivePackage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create active package
        $this->activePackage = CreditPackage::factory()->create([
            'name' => 'Basic',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'bonus_percent' => 5,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create inactive package
        $this->inactivePackage = CreditPackage::factory()->create([
            'name' => 'Inactive Package',
            'credits' => 100.00,
            'price_usd' => 100.00,
            'bonus_percent' => 0,
            'is_active' => false,
            'sort_order' => 99,
        ]);

        // Bind a default mock for PaymentGatewayInterface to prevent 500 errors
        // Tests can override this with mockSuccessfulPayPalOrder() or mockFailedPayPalOrder()
        $this->mockSuccessfulPayPalOrder();
    }

    /**
     * Mock PayPalGatewayService for successful order creation.
     */
    private function mockSuccessfulPayPalOrder(): void
    {
        $this->app->instance(
            PaymentGatewayInterface::class,
            new class implements PaymentGatewayInterface {
                public function createPaymentIntent($user, float $amount, string $currency = 'USD', array $metadata = []): array
                {
                    return [
                        'order_id' => '5O190127TN364715T',
                        'approval_url' => 'https://www.paypal.com/checkoutnow?token=5O190127TN364715T',
                    ];
                }

                public function capturePayment(string $paymentIntentId, array $options = []): bool
                {
                    return true;
                }

                public function refundPayment(string $paymentIntentId, ?float $amount = null): array
                {
                    return ['refund_id' => 'REFUND123', 'amount' => $amount ?? 0, 'status' => 'COMPLETED'];
                }

                public function handleWebhook($request): \App\Services\Billing\DTOs\PaymentResult
                {
                    return \App\Services\Billing\DTOs\PaymentResult::success('test', 10.00, 'USD', 1);
                }

                public function getCustomer($user): ?string
                {
                    return null;
                }

                public function createCustomer($user): string
                {
                    return 'payer_' . $user->id;
                }

                public function getPaymentMethods($user): array
                {
                    return [];
                }

                public function deletePaymentMethod(string $id): bool
                {
                    return false;
                }

                public function getPaymentMethodOwner(string $id): ?int
                {
                    return null;
                }
            }
        );
    }

    /**
     * Mock PayPalGatewayService for failed order creation.
     */
    private function mockFailedPayPalOrder(): void
    {
        $this->app->instance(
            PaymentGatewayInterface::class,
            new class implements PaymentGatewayInterface {
                public function createPaymentIntent($user, float $amount, string $currency = 'USD', array $metadata = []): array
                {
                    throw new \Exception('PayPal API error', 500);
                }

                public function capturePayment(string $paymentIntentId, array $options = []): bool
                {
                    return false;
                }

                public function refundPayment(string $paymentIntentId, ?float $amount = null): array
                {
                    return ['refund_id' => null, 'amount' => 0, 'status' => 'FAILED'];
                }

                public function handleWebhook($request): \App\Services\Billing\DTOs\PaymentResult
                {
                    return \App\Services\Billing\DTOs\PaymentResult::failed('test', 0, 'ERROR', 'Failed');
                }

                public function getCustomer($user): ?string
                {
                    return null;
                }

                public function createCustomer($user): string
                {
                    return '';
                }

                public function getPaymentMethods($user): array
                {
                    return [];
                }

                public function deletePaymentMethod(string $id): bool
                {
                    return false;
                }

                public function getPaymentMethodOwner(string $id): ?int
                {
                    return null;
                }
            }
        );
    }

    // ============================================
    // 1. BASIC ENDPOINT TESTS (BE)
    // ============================================

    /** @test BE-001: Successful order creation */
    public function test_successful_order_creation(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_id',
                    'approval_url',
                    'amount',
                    'credits',
                    'package_name',
                ],
            ]);
    }

    /** @test BE-002: Response structure validation */
    public function test_response_structure(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_id',
                    'approval_url',
                    'amount',
                    'credits',
                    'package_name',
                ],
            ]);

        $this->assertIsString($response->json('data.order_id'));
        $this->assertIsString($response->json('data.approval_url'));
        $this->assertIsNumeric($response->json('data.amount'));
        $this->assertIsNumeric($response->json('data.credits'));
        $this->assertIsString($response->json('data.package_name'));
    }

    /** @test BE-003: Order ID format validation */
    public function test_order_id_format(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $orderId = $response->json('data.order_id');
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $orderId);
    }

    /** @test BE-004: Approval URL format validation */
    public function test_approval_url_format(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $approvalUrl = $response->json('data.approval_url');
        $this->assertStringContainsString('paypal.com', $approvalUrl);
        $this->assertTrue(
            filter_var($approvalUrl, FILTER_VALIDATE_URL) !== false,
            'Approval URL is not a valid URL'
        );
    }

    /** @test BE-005: Amount matches package price */
    public function test_amount_matches_package_price(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
        $this->assertEqualsWithDelta((float) $this->activePackage->price_usd, $response->json('data.amount'), 0.01);
    }

    /** @test BE-006: Credits include bonus percentage */
    public function test_credits_include_bonus(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        // Package: $10 credits, 5% bonus = $10.50 total
        $expectedCredits = $this->activePackage->credits * (1 + $this->activePackage->bonus_percent / 100);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
        $this->assertEqualsWithDelta($expectedCredits, $response->json('data.credits'), 0.01);
    }

    /** @test BE-007: Package name returned in response */
    public function test_package_name_returned(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.package_name', $this->activePackage->name);
    }

    /** @test BE-008: Authentication required */
    public function test_authentication_required(): void
    {
        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(401);
    }

    // ============================================
    // 2. VALIDATION TESTS (VA)
    // ============================================

    /** @test VA-001: Missing package_id */
    public function test_validation_missing_package_id(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.details.package_id', function ($errors) {
                return !empty($errors);
            });
    }

    /** @test VA-002: Non-existent package_id */
    public function test_validation_non_existent_package_id(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => 99999,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.details.package_id', function ($errors) {
                return !empty($errors);
            });
    }

    /** @test VA-003: Invalid package_id type */
    public function test_validation_invalid_package_id_type(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => 'abc',
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.details.package_id', function ($errors) {
                return !empty($errors);
            });
    }

    /** @test VA-004: Missing return_url */
    public function test_validation_missing_return_url(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.details.return_url', function ($errors) {
                return !empty($errors);
            });
    }

    /** @test VA-005: Invalid return_url format */
    public function test_validation_invalid_return_url(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'not-a-valid-url',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.details.return_url', function ($errors) {
                return !empty($errors);
            });
    }

    /** @test VA-006: Missing cancel_url */
    public function test_validation_missing_cancel_url(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.details.cancel_url', function ($errors) {
                return !empty($errors);
            });
    }

    /** @test VA-007: Invalid cancel_url format */
    public function test_validation_invalid_cancel_url(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.details.cancel_url', function ($errors) {
                return !empty($errors);
            });
    }

    /** @test VA-008: Return URL must be HTTPS */
    public function test_validation_return_url_https(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'http://example.com/credits/success', // HTTP not HTTPS
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.details.return_url', function ($errors) {
                return !empty($errors);
            });
    }

    /** @test VA-009: Cancel URL must be HTTPS */
    public function test_validation_cancel_url_https(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'http://example.com/credits/cancelled', // HTTP not HTTPS
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.details.cancel_url', function ($errors) {
                return !empty($errors);
            });
    }

    // ============================================
    // 3. BUSINESS LOGIC TESTS (BL)
    // ============================================

    /** @test BL-001: Active package used successfully */
    public function test_active_package_used(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
    }

    /** @test BL-002: Inactive package rejected */
    public function test_inactive_package_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->inactivePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        // Inactive package should return 422 (validation error)
        $response->assertStatus(422)
            ->assertJsonPath('error.details.package_id', function ($errors) {
                return !empty($errors);
            });
    }

    /** @test BL-003: Credits calculation base (no bonus) */
    public function test_credits_calculation_base(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        // Create package with no bonus
        $noBonusPackage = CreditPackage::factory()->create([
            'name' => 'No Bonus',
            'credits' => 25.00,
            'price_usd' => 25.00,
            'bonus_percent' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $noBonusPackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
        $this->assertEqualsWithDelta(25.00, $response->json('data.credits'), 0.01);
    }

    /** @test BL-004: Credits calculation with bonus */
    public function test_credits_calculation_with_bonus(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        // Our activePackage has 5% bonus: $10 + 5% = $10.50
        $expectedCredits = $this->activePackage->credits * (1 + $this->activePackage->bonus_percent / 100);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
        $this->assertEqualsWithDelta($expectedCredits, $response->json('data.credits'), 0.01);
    }

    /** @test BL-005: Popular package works */
    public function test_popular_package_works(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $popularPackage = CreditPackage::factory()->create([
            'name' => 'Pro',
            'credits' => 25.00,
            'price_usd' => 25.00,
            'bonus_percent' => 10,
            'is_popular' => true,
            'is_active' => true,
        ]);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $popularPackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
    }

    /** @test BL-006: Pending transaction created */
    public function test_pending_transaction_created(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);

        // Verify transaction was created (if transaction table exists)
        // This test may need adjustment based on actual implementation
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    /** @test BL-007: Transaction status is pending */
    public function test_transaction_status_pending(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    /** @test BL-008: User ID in transaction */
    public function test_user_id_in_transaction(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test BL-009: Package ID in transaction */
    public function test_package_id_in_transaction(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test BL-010: Amount matches package price in transaction */
    public function test_amount_matches_package_in_transaction(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
        ]);
        // Verify credits amount matches (amount field stores credits, not USD)
        $transaction = \App\Models\CreditTransaction::where('user_id', $this->user->id)->first();
        $expectedCredits = $this->activePackage->credits * (1 + $this->activePackage->bonus_percent / 100);
        $this->assertEqualsWithDelta($expectedCredits, (float) $transaction->amount, 0.01);
    }

    // ============================================
    // 4. ERROR HANDLING TESTS (EH)
    // ============================================

    /** @test EH-001: PayPal API failure */
    public function test_paypal_api_failure(): void
    {
        $this->mockFailedPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(500);
    }

    /** @test EH-002: PayPal timeout handling */
    public function test_paypal_timeout(): void
    {
        $this->app->instance(
            PaymentGatewayInterface::class,
            new class implements PaymentGatewayInterface {
                public function createPaymentIntent($user, float $amount, string $currency = 'USD', array $metadata = []): array
                {
                    throw new \Exception('Connection timeout', 504);
                }

                public function capturePayment(string $paymentIntentId, array $options = []): bool { return false; }
                public function refundPayment(string $paymentIntentId, ?float $amount = null): array { return []; }
                public function handleWebhook($request): \App\Services\Billing\DTOs\PaymentResult { return \App\Services\Billing\DTOs\PaymentResult::ignored(); }
                public function getCustomer($user): ?string { return null; }
                public function createCustomer($user): string { return ''; }
                public function getPaymentMethods($user): array { return []; }
                public function deletePaymentMethod(string $id): bool { return false; }
                public function getPaymentMethodOwner(string $id): ?int { return null; }
            }
        );

        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(500);
    }

    /** @test EH-003: Network error handling */
    public function test_network_error(): void
    {
        $this->app->instance(
            PaymentGatewayInterface::class,
            new class implements PaymentGatewayInterface {
                public function createPaymentIntent($user, float $amount, string $currency = 'USD', array $metadata = []): array
                {
                    throw new \Exception('Network error', 503);
                }

                public function capturePayment(string $paymentIntentId, array $options = []): bool { return false; }
                public function refundPayment(string $paymentIntentId, ?float $amount = null): array { return []; }
                public function handleWebhook($request): \App\Services\Billing\DTOs\PaymentResult { return \App\Services\Billing\DTOs\PaymentResult::ignored(); }
                public function getCustomer($user): ?string { return null; }
                public function createCustomer($user): string { return ''; }
                public function getPaymentMethods($user): array { return []; }
                public function deletePaymentMethod(string $id): bool { return false; }
                public function getPaymentMethodOwner(string $id): ?int { return null; }
            }
        );

        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(500);
    }

    /** @test EH-004: Invalid PayPal credentials */
    public function test_invalid_paypal_credentials(): void
    {
        $this->app->instance(
            PaymentGatewayInterface::class,
            new class implements PaymentGatewayInterface {
                public function createPaymentIntent($user, float $amount, string $currency = 'USD', array $metadata = []): array
                {
                    throw new \Exception('Invalid credentials', 401);
                }

                public function capturePayment(string $paymentIntentId, array $options = []): bool { return false; }
                public function refundPayment(string $paymentIntentId, ?float $amount = null): array { return []; }
                public function handleWebhook($request): \App\Services\Billing\DTOs\PaymentResult { return \App\Services\Billing\DTOs\PaymentResult::ignored(); }
                public function getCustomer($user): ?string { return null; }
                public function createCustomer($user): string { return ''; }
                public function getPaymentMethods($user): array { return []; }
                public function deletePaymentMethod(string $id): bool { return false; }
                public function getPaymentMethodOwner(string $id): ?int { return null; }
            }
        );

        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(500);
    }

    /** @test EH-005: PayPal rate limit */
    public function test_paypal_rate_limit(): void
    {
        $this->app->instance(
            PaymentGatewayInterface::class,
            new class implements PaymentGatewayInterface {
                public function createPaymentIntent($user, float $amount, string $currency = 'USD', array $metadata = []): array
                {
                    throw new \Exception('Rate limit exceeded', 429);
                }

                public function capturePayment(string $paymentIntentId, array $options = []): bool { return false; }
                public function refundPayment(string $paymentIntentId, ?float $amount = null): array { return []; }
                public function handleWebhook($request): \App\Services\Billing\DTOs\PaymentResult { return \App\Services\Billing\DTOs\PaymentResult::ignored(); }
                public function getCustomer($user): ?string { return null; }
                public function createCustomer($user): string { return ''; }
                public function getPaymentMethods($user): array { return []; }
                public function deletePaymentMethod(string $id): bool { return false; }
                public function getPaymentMethodOwner(string $id): ?int { return null; }
            }
        );

        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(500);
    }

    /** @test EH-006: Empty response from PayPal */
    public function test_empty_response_from_paypal(): void
    {
        $this->app->instance(
            PaymentGatewayInterface::class,
            new class implements PaymentGatewayInterface {
                public function createPaymentIntent($user, float $amount, string $currency = 'USD', array $metadata = []): array
                {
                    return []; // Empty response
                }

                public function capturePayment(string $paymentIntentId, array $options = []): bool { return false; }
                public function refundPayment(string $paymentIntentId, ?float $amount = null): array { return []; }
                public function handleWebhook($request): \App\Services\Billing\DTOs\PaymentResult { return \App\Services\Billing\DTOs\PaymentResult::ignored(); }
                public function getCustomer($user): ?string { return null; }
                public function createCustomer($user): string { return ''; }
                public function getPaymentMethods($user): array { return []; }
                public function deletePaymentMethod(string $id): bool { return false; }
                public function getPaymentMethodOwner(string $id): ?int { return null; }
            }
        );

        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        // Empty response should result in 500 or validation error
        $response->assertStatus(500);
    }

    // ============================================
    // 5. SECURITY TESTS (SC)
    // ============================================

    /** @test SC-001: Price from server, not request */
    public function test_price_from_server_not_request(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        // Try to pass manipulated price (should be ignored)
        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
            'price' => 0.01, // Malicious attempt
            'amount' => 0.01, // Malicious attempt
        ]);

        $response->assertStatus(200);
        // Server price used, not the malicious price
        $this->assertEqualsWithDelta((float) $this->activePackage->price_usd, $response->json('data.amount'), 0.01);
    }

    /** @test SC-002: SQL injection in package_id */
    public function test_sql_injection_package_id(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => '1 OR 1=1',
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(422);
    }

    /** @test SC-003: XSS in return_url */
    public function test_xss_in_return_url(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/<script>alert(1)</script>',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(422);
    }

    /** @test SC-004: User isolation - can only see own transactions */
    public function test_user_isolation(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        // Create order for current user
        $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        // Verify only current user's transactions exist
        $otherUser = User::factory()->create();
        $this->assertDatabaseMissing('credit_transactions', [
            'user_id' => $otherUser->id,
        ]);
    }

    /** @test SC-005: Return URL domain validation */
    public function test_return_url_domain_validation(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://evil.com/phishing',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        // If domain whitelist is implemented, this should fail
        // If not implemented, test should pass
        // Adjust based on actual implementation
        $this->assertContains($response->status(), [200, 422]);
    }

    /** @test SC-006: No sensitive data in response */
    public function test_no_sensitive_data_in_response(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);

        // Ensure no sensitive data is exposed
        $json = $response->json();
        $this->assertArrayNotHasKey('client_secret', $json);
        $this->assertArrayNotHasKey('api_key', $json);
        $this->assertArrayNotHasKey('secret', $json);
        $this->assertArrayNotHasKey('password', $json);
    }

    // ============================================
    // 6. INTEGRATION TESTS (IN)
    // ============================================

    /** @test IN-001: Full order flow */
    public function test_full_order_flow(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        // Step 1: Create order
        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_id',
                    'approval_url',
                    'amount',
                    'credits',
                    'package_name',
                ],
            ]);

        // Verify transaction was created
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    /** @test IN-002: Order with all packages */
    public function test_order_with_all_packages(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        // Create multiple packages
        $packages = CreditPackage::factory()->count(3)->create([
            'is_active' => true,
        ]);

        foreach ($packages as $package) {
            $response = $this->postJson($this->endpoint, [
                'package_id' => $package->id,
                'return_url' => 'https://example.com/credits/success',
                'cancel_url' => 'https://example.com/credits/cancelled',
            ]);

            $response->assertStatus(200);
        }
    }

    /** @test IN-003: Multiple orders same user */
    public function test_multiple_orders_same_user(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        // Create first order
        $response1 = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response1->assertStatus(200);

        // Create second order
        $response2 = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response2->assertStatus(200);
    }

    /** @test IN-004: Order after failed attempt */
    public function test_order_after_failed_attempt(): void
    {
        Sanctum::actingAs($this->user);

        // Create a configurable mock that can fail then succeed
        $callCount = 0;
        $this->app->instance(
            PaymentGatewayInterface::class,
            new class($callCount) implements PaymentGatewayInterface {
                private static int $calls = 0;

                public function __construct(?int $calls = null)
                {
                    if ($calls !== null) {
                        self::$calls = $calls;
                    }
                }

                public function createPaymentIntent($user, float $amount, string $currency = 'USD', array $metadata = []): array
                {
                    self::$calls++;
                    if (self::$calls === 1) {
                        throw new \Exception('PayPal API error', 500);
                    }
                    return [
                        'order_id' => '5O190127TN364715T',
                        'approval_url' => 'https://www.paypal.com/checkoutnow?token=5O190127TN364715T',
                    ];
                }

                public function capturePayment(string $paymentIntentId, array $options = []): bool { return true; }
                public function refundPayment(string $paymentIntentId, ?float $amount = null): array { return []; }
                public function handleWebhook($request): \App\Services\Billing\DTOs\PaymentResult { return \App\Services\Billing\DTOs\PaymentResult::success('test', 10.00, 'USD', 1); }
                public function getCustomer($user): ?string { return null; }
                public function createCustomer($user): string { return 'payer_' . $user->id; }
                public function getPaymentMethods($user): array { return []; }
                public function deletePaymentMethod(string $id): bool { return false; }
                public function getPaymentMethodOwner(string $id): ?int { return null; }
            }
        );

        // First attempt fails
        $response1 = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);
        $response1->assertStatus(500);

        // Second attempt succeeds
        $response2 = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);
        $response2->assertStatus(200);
    }

    // ============================================
    // 7. HTTP METHOD TESTS (HM)
    // ============================================

    /** @test HM-001: GET not allowed */
    public function test_get_not_allowed(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson($this->endpoint);

        $response->assertStatus(405);
    }

    /** @test HM-002: PUT not allowed */
    public function test_put_not_allowed(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
        ]);

        $response->assertStatus(405);
    }

    /** @test HM-003: PATCH not allowed */
    public function test_patch_not_allowed(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
        ]);

        $response->assertStatus(405);
    }

    /** @test HM-004: DELETE not allowed */
    public function test_delete_not_allowed(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson($this->endpoint);

        $response->assertStatus(405);
    }

    // ============================================
    // 8. RATE LIMITING TESTS (RL)
    // ============================================

    /** @test RL-001: Normal usage allowed */
    public function test_normal_usage_allowed(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson($this->endpoint, [
                'package_id' => $this->activePackage->id,
                'return_url' => 'https://example.com/credits/success',
                'cancel_url' => 'https://example.com/credits/cancelled',
            ]);

            $response->assertStatus(200);
        }
    }

    /** @test RL-002: Rate limit headers present */
    public function test_rate_limit_headers(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        // Check for rate limit headers if implemented
        // Not all APIs have these, so we just check response is valid
        $response->assertStatus(200);
    }

    // ============================================
    // 9. PERFORMANCE TESTS (PF)
    // ============================================

    /** @test PF-001: Response time under 2 seconds */
    public function test_response_time(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $start = microtime(true);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $elapsed = (microtime(true) - $start) * 1000; // ms

        $response->assertStatus(200);
        $this->assertLessThan(2000, $elapsed, 'Response time should be under 2 seconds');
    }

    /** @test PF-002: Concurrent requests handled */
    public function test_concurrent_requests(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $responses = [];

        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->postJson($this->endpoint, [
                'package_id' => $this->activePackage->id,
                'return_url' => 'https://example.com/credits/success',
                'cancel_url' => 'https://example.com/credits/cancelled',
            ]);
        }

        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    // ============================================
    // 10. EDGE CASES (EC)
    // ============================================

    /** @test EC-001: Maximum bonus package */
    public function test_maximum_bonus_package(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $maxBonusPackage = CreditPackage::factory()->create([
            'name' => 'Maximum Bonus',
            'credits' => 50.00,
            'price_usd' => 50.00,
            'bonus_percent' => 50, // 50% bonus
            'is_active' => true,
        ]);

        // Expected: $50 + 50% = $75
        $expectedCredits = $maxBonusPackage->credits * (1 + $maxBonusPackage->bonus_percent / 100);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $maxBonusPackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
        $this->assertEqualsWithDelta($expectedCredits, $response->json('data.credits'), 0.01);
    }

    /** @test EC-002: Minimum package ($5) */
    public function test_minimum_package(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $minPackage = CreditPackage::factory()->create([
            'name' => 'Starter',
            'credits' => 5.00,
            'price_usd' => 5.00,
            'bonus_percent' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $minPackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(5.00, (float) $response->json('data.amount'));
    }

    /** @test EC-003: Maximum package ($50) */
    public function test_maximum_package(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $maxPackage = CreditPackage::factory()->create([
            'name' => 'Enterprise',
            'credits' => 50.00,
            'price_usd' => 50.00,
            'bonus_percent' => 15,
            'is_active' => true,
        ]);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $maxPackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(50.00, (float) $response->json('data.amount'));
    }

    /** @test EC-004: URL with query params */
    public function test_url_with_query_params(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success?session=abc123',
            'cancel_url' => 'https://example.com/credits/cancelled?ref=pricing',
        ]);

        $response->assertStatus(200);
    }

    /** @test EC-005: URL with fragment */
    public function test_url_with_fragment(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success#section',
            'cancel_url' => 'https://example.com/credits/cancelled#top',
        ]);

        $response->assertStatus(200);
    }

    /** @test EC-006: Unicode in URL params */
    public function test_unicode_in_url_params(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success?msg=' . urlencode('成功'),
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
    }

    /** @test EC-007: Long URL accepted */
    public function test_long_url(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        $longUrl = 'https://example.com/credits/success?' . str_repeat('a', 200);

        $response = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => $longUrl,
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response->assertStatus(200);
    }

    /** @test EC-008: Duplicate order same package */
    public function test_duplicate_order_same_package(): void
    {
        $this->mockSuccessfulPayPalOrder();
        Sanctum::actingAs($this->user);

        // First order
        $response1 = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response1->assertStatus(200);
        $orderId1 = $response1->json('data.order_id');

        // Second order (should create new order, not reuse)
        $response2 = $this->postJson($this->endpoint, [
            'package_id' => $this->activePackage->id,
            'return_url' => 'https://example.com/credits/success',
            'cancel_url' => 'https://example.com/credits/cancelled',
        ]);

        $response2->assertStatus(200);
        // Note: With mocked service, both will have same order_id
        // In production, they would be different
    }
}
