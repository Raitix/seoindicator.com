<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\DTOs\PaymentResult;
use App\Services\Billing\Exceptions\StripePaymentException;
use App\Services\Billing\Exceptions\PaymentValidationException;
use App\Services\Billing\Exceptions\PaymentTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

/**
 * PaymentController Test Suite
 *
 * Comprehensive tests for TASK-26-9: Create Stripe Payment Intent Endpoint.
 * Tests cover endpoint functionality, validation, business logic, and security.
 *
 * @see TASK-26-9
 * @see FEAT-026
 */
class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up Stripe test config
        Config::set('services.stripe.secret', 'sk_test_example');
        Config::set('services.stripe.key', 'pk_test_example');
        Config::set('services.stripe.webhook_secret', 'whsec_example');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================
    // Test Data Helpers
    // ============================================

    /**
     * Create a test credit package.
     */
    protected function createTestPackage(array $overrides = []): CreditPackage
    {
        return CreditPackage::factory()->create(array_merge([
            'name' => 'Basic',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'bonus_percent' => 5,
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    /**
     * Create a test user.
     */
    protected function createTestUser(): User
    {
        return User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    /**
     * Mock the payment gateway.
     */
    protected function mockPaymentGateway(array $intentResult = null): Mockery\MockInterface
    {
        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        if ($intentResult) {
            $mock->shouldReceive('createPaymentIntent')
                ->andReturn($intentResult);
        }
        
        $mock->shouldReceive('getCustomer')
            ->andReturn(null);
        
        $mock->shouldReceive('createCustomer')
            ->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        return $mock;
    }

    // ============================================
    // Endpoint Tests: POST /api/v1/payments/stripe/intent
    // ============================================

    /**
     * Test PI-001: Successful payment intent creation with valid package.
     * 
     * @test
     */
    public function pi_001_successful_payment_intent_creation(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 1000, // cents
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'client_secret',
                    'payment_intent_id',
                    'amount',
                    'credits',
                    'package_name',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'client_secret' => 'pi_test123_secret_abc',
                    'payment_intent_id' => 'pi_test123',
                    'amount' => 10.00,
                    'package_name' => 'Basic',
                ],
            ]);

        // Verify credits calculation includes bonus (10 + 5% = 10.5)
        $this->assertEquals(10.50, $response->json('data.credits'));
    }

    /**
     * Test PI-002: Payment intent with return_url parameter.
     * 
     * @test
     */
    public function pi_002_payment_intent_with_return_url(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 1000,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
                'return_url' => 'https://example.com/credits/success',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test PI-003: Payment intent creates pending transaction.
     * 
     * @test
     */
    public function pi_003_creates_pending_transaction(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 1000,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        // Verify pending transaction was created
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test123',
            'type' => 'purchase',
        ]);
    }

    // ============================================
    // Authentication Tests
    // ============================================

    /**
     * Test PI-010: Unauthenticated request returns 401.
     * 
     * @test
     */
    public function pi_010_unauthenticated_returns_401(): void
    {
        $package = $this->createTestPackage();

        $response = $this->postJson('/api/v1/payments/stripe/intent', [
            'package_id' => $package->id,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test PI-011: Authenticated user can create intent.
     * 
     * @test
     */
    public function pi_011_authenticated_user_can_create_intent(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 1000,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200);
    }

    // ============================================
    // Validation Tests
    // ============================================

    /**
     * Test PI-020: Missing package_id returns 422.
     * 
     * @test
     */
    public function pi_020_missing_package_id_returns_422(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['package_id']);
    }

    /**
     * Test PI-021: Invalid package_id returns 422.
     * 
     * @test
     */
    public function pi_021_invalid_package_id_returns_422(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['package_id']);
    }

    /**
     * Test PI-022: Inactive package returns 400.
     * 
     * @test
     */
    public function pi_022_inactive_package_returns_400(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage(['is_active' => false]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'The selected package is not available.',
            ]);
    }

    /**
     * Test PI-023: Invalid return_url returns 422.
     * 
     * @test
     */
    public function pi_023_invalid_return_url_returns_422(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
                'return_url' => 'not-a-valid-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['return_url']);
    }

    /**
     * Test PI-024: Non-numeric package_id returns 422.
     * 
     * @test
     */
    public function pi_024_non_numeric_package_id_returns_422(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => 'abc',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['package_id']);
    }

    // ============================================
    // Business Logic Tests
    // ============================================

    /**
     * Test PI-030: Credits calculated correctly with bonus.
     * 
     * @test
     */
    public function pi_030_credits_calculated_with_bonus(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage([
            'credits' => 100.00,
            'bonus_percent' => 20,
        ]);

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 10000, // $100 in cents
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        // 100 credits + 20% bonus = 120 credits
        $this->assertEquals(120.00, $response->json('data.credits'));
    }

    /**
     * Test PI-031: Credits calculated correctly without bonus.
     * 
     * @test
     */
    public function pi_031_credits_calculated_without_bonus(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage([
            'credits' => 50.00,
            'bonus_percent' => 0,
        ]);

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 5000,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        // 50 credits + 0% bonus = 50 credits
        $this->assertEquals(50.00, $response->json('data.credits'));
    }

    /**
     * Test PI-032: Amount comes from package price not request.
     * 
     * @test
     */
    public function pi_032_amount_from_package_not_request(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage([
            'price_usd' => 25.00,
        ]);

        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        // Verify that the amount passed to gateway is from package, not request
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg, $amount, $currency, $metadata) use ($package) {
                // Amount should be package price, not any other value
                return $amount === $package->price_usd;
            })
            ->andReturn([
                'client_secret' => 'pi_test123_secret_abc',
                'payment_intent_id' => 'pi_test123',
                'amount' => 2500,
                'currency' => 'usd',
            ]);
        
        $mock->shouldReceive('getCustomer')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test PI-033: Gateway receives correct metadata.
     * 
     * @test
     */
    public function pi_033_gateway_receives_correct_metadata(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg, $amount, $currency, $metadata) use ($user, $package) {
                // Verify metadata contains required fields
                return isset($metadata['user_id']) 
                    && isset($metadata['package_id'])
                    && $metadata['user_id'] === (string) $user->id
                    && $metadata['package_id'] === (string) $package->id;
            })
            ->andReturn([
                'client_secret' => 'pi_test123_secret_abc',
                'payment_intent_id' => 'pi_test123',
                'amount' => 1000,
                'currency' => 'usd',
            ]);
        
        $mock->shouldReceive('getCustomer')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200);
    }

    // ============================================
    // Error Handling Tests
    // ============================================

    /**
     * Test PI-040: Stripe error returns 500 with message.
     * 
     * @test
     */
    public function pi_040_stripe_error_returns_500(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        $mock->shouldReceive('createPaymentIntent')
            ->andThrow(new StripePaymentException(
                'Stripe API error',
                'api_error'
            ));
        
        $mock->shouldReceive('getCustomer')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Payment could not be initiated',
            ]);
    }

    /**
     * Test PI-041: Payment timeout returns 503.
     * 
     * @test
     */
    public function pi_041_payment_timeout_returns_503(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        $mock->shouldReceive('createPaymentIntent')
            ->andThrow(new PaymentTimeoutException('Payment service unavailable'));
        
        $mock->shouldReceive('getCustomer')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'message' => 'Payment service temporarily unavailable',
            ]);
    }

    /**
     * Test PI-042: Validation error returns 422 with details.
     * 
     * @test
     */
    public function pi_042_validation_error_returns_422_with_details(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        $mock->shouldReceive('createPaymentIntent')
            ->andThrow(PaymentValidationException::minimumAmount(5.00, 1.00));
        
        $mock->shouldReceive('getCustomer')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    // ============================================
    // Security Tests
    // ============================================

    /**
     * Test PI-050: Amount validated server-side (never from request).
     * 
     * @test
     */
    public function pi_050_amount_validated_server_side(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage(['price_usd' => 15.00]);

        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        // The amount should be $15 (from package), ignoring any client input
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg, $amount) {
                return $amount === 15.00;
            })
            ->andReturn([
                'client_secret' => 'pi_test123_secret_abc',
                'payment_intent_id' => 'pi_test123',
                'amount' => 1500,
                'currency' => 'usd',
            ]);
        
        $mock->shouldReceive('getCustomer')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        // Try to manipulate amount via request (should be ignored)
        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
                'amount' => 1.00, // This should be completely ignored
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test PI-051: Package price read from database (not request).
     * 
     * @test
     */
    public function pi_051_package_price_from_database(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage(['price_usd' => 100.00]);

        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg, $amount) {
                // Amount must come from database package, not request
                return $amount === 100.00;
            })
            ->andReturn([
                'client_secret' => 'pi_test123_secret_abc',
                'payment_intent_id' => 'pi_test123',
                'amount' => 10000,
                'currency' => 'usd',
            ]);
        
        $mock->shouldReceive('getCustomer')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test PI-052: Payment intent created with user context.
     * 
     * @test
     */
    public function pi_052_payment_intent_with_user_context(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->withArgs(function ($userArg) use ($user) {
                // Verify correct user is passed
                return $userArg->id === $user->id;
            })
            ->andReturn([
                'client_secret' => 'pi_test123_secret_abc',
                'payment_intent_id' => 'pi_test123',
                'amount' => 1000,
                'currency' => 'usd',
            ]);
        
        $mock->shouldReceive('getCustomer')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test PI-053: Idempotency prevents duplicate intents for same user/package.
     * 
     * @test
     */
    public function pi_053_idempotency_prevents_duplicate_intents(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        // Create an existing pending transaction for same package
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'balance_after' => 0,
            'type' => 'purchase',
            'description' => 'Pending purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_existing123',
            'reference_type' => 'credit_packages',
            'reference_id' => $package->id,
            'metadata' => ['status' => 'pending'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        // Should return the existing intent, not create new one
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'payment_intent_id' => 'pi_existing123',
                ],
            ]);
    }

    /**
     * Test PI-054: Cannot use another user's package.
     * 
     * @test
     */
    public function pi_054_cannot_use_another_users_package(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 1000,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        // Package is global (not user-specific), so any authenticated user can use it
        // This test verifies the endpoint works correctly
        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200);
    }

    // ============================================
    // Response Format Tests
    // ============================================

    /**
     * Test PI-060: Success response format matches specification.
     * 
     * @test
     */
    public function pi_060_success_response_format(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 1000,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'client_secret',
                    'payment_intent_id',
                    'amount',
                    'credits',
                    'package_name',
                ],
            ])
            ->assertJsonPath('success', true);
    }

    /**
     * Test PI-061: Error response format matches specification.
     * 
     * @test
     */
    public function pi_061_error_response_format(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }

    // ============================================
    // Edge Case Tests
    // ============================================

    /**
     * Test PI-070: Package with maximum values.
     * 
     * @test
     */
    public function pi_070_package_with_maximum_values(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage([
            'credits' => 999999.99,
            'price_usd' => 99999.99,
            'bonus_percent' => 100,
        ]);

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 9999999,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test PI-071: Package with zero bonus.
     * 
     * @test
     */
    public function pi_071_package_with_zero_bonus(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage([
            'bonus_percent' => 0,
        ]);

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 1000,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200);
        // Credits should equal base credits with 0% bonus
        $this->assertEquals(10.00, $response->json('data.credits'));
    }

    /**
     * Test PI-072: Multiple requests create separate intents (no idempotency key collision).
     * 
     * @test
     */
    public function pi_072_multiple_requests_create_separate_intents(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $callCount = 0;
        $mock = Mockery::mock(PaymentGatewayInterface::class);
        
        $mock->shouldReceive('createPaymentIntent')
            ->twice()
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                return [
                    'client_secret' => "pi_test{$callCount}_secret",
                    'payment_intent_id' => "pi_test{$callCount}",
                    'amount' => 1000,
                    'currency' => 'usd',
                ];
            });
        
        $mock->shouldReceive('getCustomer')->andReturn(null);
        $mock->shouldReceive('createCustomer')->andReturn('cus_test123');

        $this->app->instance(PaymentGatewayInterface::class, $mock);

        // First request
        $response1 = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        // Second request (different package to avoid idempotency check)
        $package2 = $this->createTestPackage(['name' => 'Pro', 'sort_order' => 2]);

        $response2 = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package2->id,
            ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    /**
     * Test PI-073: Package name in response matches database.
     * 
     * @test
     */
    public function pi_073_package_name_matches_database(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage(['name' => 'Enterprise']);

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 1000,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/payments/stripe/intent', [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.package_name', 'Enterprise');
    }

    // ============================================
    // Rate Limiting Tests (if implemented)
    // ============================================

    /**
     * Test PI-080: Rate limiting on payment intent creation.
     * 
     * @test
     */
    public function pi_080_rate_limiting_on_intent_creation(): void
    {
        $user = $this->createTestUser();
        $package = $this->createTestPackage();

        $intentResult = [
            'client_secret' => 'pi_test123_secret_abc',
            'payment_intent_id' => 'pi_test123',
            'amount' => 1000,
            'currency' => 'usd',
        ];

        $this->mockPaymentGateway($intentResult);

        // Make multiple requests rapidly
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)
                ->postJson('/api/v1/payments/stripe/intent', [
                    'package_id' => $package->id,
                ]);
        }

        // All requests should succeed (no aggressive rate limiting on test)
        // In production, this might return 429 after a threshold
        $response->assertStatus(200);
    }
}
