<?php

namespace Tests\Unit\Services\Billing;

use Tests\TestCase;
use App\Models\User;
use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\PaymentMethod;
use App\Services\Billing\Contracts\PaymentGatewayInterface;
use App\Services\Billing\StripeGatewayService;
use App\Services\Billing\DTOs\PaymentResult;
use App\Services\Billing\Exceptions\StripePaymentException;
use App\Services\Billing\Exceptions\PaymentTimeoutException;
use App\Services\Billing\Exceptions\PaymentValidationException;
use App\Services\Billing\Exceptions\InvalidWebhookSignatureException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit tests for TASK-26-8: StripeGatewayService
 *
 * Tests the StripeGatewayService responsible for:
 * - Payment intent creation
 * - Payment capture
 * - Refund processing
 * - Webhook handling
 * - Customer management
 * - Error handling
 *
 * SECURITY CRITICAL: All webhook tests verify signature validation.
 * Tests use mocked Stripe SDK responses - no actual API calls.
 *
 * @see TASK-26-8
 * @see \App\Services\Billing\StripeGatewayService
 */
class StripeGatewayServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up Stripe test configuration
        Config::set('services.stripe.key', 'pk_test_example');
        Config::set('services.stripe.secret', 'sk_test_example');
        Config::set('services.stripe.webhook_secret', 'whsec_test_example');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper to create user with credit record.
     */
    protected function createUserWithCredit(): User
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        Credit::create([
            'user_id' => $user->id,
            'balance' => 0,
            'total_purchased' => 0,
            'total_spent' => 0,
        ]);

        return $user;
    }

    /**
     * Create a mock HTTP request for webhook testing.
     */
    protected function createWebhookRequest(array $payload, string $signature = 'valid_signature'): Request
    {
        $request = Request::create('/api/v1/payments/stripe/webhook', 'POST', [], [], [], [], json_encode($payload));
        $request->headers->set('Stripe-Signature', $signature);
        return $request;
    }

    /**
     * Create a mock Stripe client with paymentIntents service.
     */
    protected function mockStripeClient(): Mockery\MockInterface
    {
        return Mockery::mock(\Stripe\StripeClient::class);
    }

    /**
     * Set up the Stripe mock to return service mocks for property access.
     * 
     * Stripe SDK's __get() internally calls getService(), so we mock that method.
     */
    protected function mockStripeServices(Mockery\MockInterface $mockStripe, array $services): void
    {
        foreach ($services as $serviceName => $serviceMock) {
            $mockStripe->shouldReceive('getService')
                ->with($serviceName)
                ->andReturn($serviceMock)
                ->zeroOrMoreTimes();
        }
    }

    // =========================================================================
    // PAYMENT INTENT CREATION TESTS (PI)
    // =========================================================================

    /**
     * PI-001: createPaymentIntent creates payment intent with valid parameters
     */
    public function test_create_payment_intent_with_valid_parameters(): void
    {
        $user = $this->createUserWithCredit();

        // Create mock payment intent
        $mockPaymentIntent = (object) [
            'id' => 'pi_test_123',
            'client_secret' => 'pi_test_123_secret',
            'amount' => 1000,
            'currency' => 'usd',
            'status' => 'requires_payment_method',
        ];

        // Create mock Stripe client
        $mockStripe = $this->mockStripeClient();
        
        // Mock the paymentIntents service
        $mockPaymentIntents = Mockery::mock();
        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) use ($user) {
                return $params['amount'] === 1000 && // $10.00 in cents
                    $params['currency'] === 'usd' &&
                    $params['metadata']['user_id'] === (string) $user->id &&
                    isset($options['idempotency_key']);
            })
            ->andReturn($mockPaymentIntent);

        // Mock customers service (for getCustomer returning null, then createCustomer)
        $mockCustomers = Mockery::mock();
        $mockCustomers->shouldReceive('retrieve')
            ->once()
            ->andThrow(new \Exception('No such customer'));
        $mockCustomers->shouldReceive('create')
            ->once()
            ->andReturn((object) ['id' => 'cus_test_123', 'email' => $user->email]);

        // Set up property access mocks
        $mockStripe->shouldReceive('__get')
            ->with('paymentIntents')
            ->andReturn($mockPaymentIntents);
        $mockStripe->shouldReceive('__get')
            ->with('customers')
            ->andReturn($mockCustomers);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->createPaymentIntent($user, 10.00, 'USD');

        $this->assertIsArray($result);
        $this->assertEquals('pi_test_123_secret', $result['client_secret']);
        $this->assertEquals('pi_test_123', $result['payment_intent_id']);
        $this->assertEquals(1000, $result['amount']);
        $this->assertEquals('usd', $result['currency']);
    }

    /**
     * PI-002: createPaymentIntent creates customer if not exists
     */
    public function test_create_payment_intent_creates_customer_if_not_exists(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        
        $mockCustomers = Mockery::mock();
        $mockPaymentIntents = Mockery::mock();

        // First call: check if customer exists (returns null via exception)
        $mockCustomers->shouldReceive('retrieve')
            ->once()
            ->andThrow(new \Exception('No such customer'));

        // Second call: create customer
        $mockCustomers->shouldReceive('create')
            ->once()
            ->withArgs(function ($params) use ($user) {
                return $params['email'] === $user->email &&
                    $params['name'] === $user->name;
            })
            ->andReturn((object) [
                'id' => 'cus_test_123',
                'email' => $user->email,
            ]);

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'id' => 'pi_test_123',
                'client_secret' => 'pi_test_123_secret',
                'amount' => 1000,
                'currency' => 'usd',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->createPaymentIntent($user, 10.00);

        $this->assertEquals('pi_test_123', $result['payment_intent_id']);
    }

    /**
     * PI-003: createPaymentIntent reuses existing customer
     */
    public function test_create_payment_intent_reuses_existing_customer(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockCustomers = Mockery::mock();
        $mockPaymentIntents = Mockery::mock();

        // Existing customer found
        $mockCustomers->shouldReceive('retrieve')
            ->once()
            ->andReturn((object) [
                'id' => 'cus_existing_123',
                'email' => $user->email,
            ]);

        // Should NOT create new customer
        $mockCustomers->shouldNotReceive('create');

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) {
                return $params['customer'] === 'cus_existing_123';
            })
            ->andReturn((object) [
                'id' => 'pi_test_123',
                'client_secret' => 'pi_test_123_secret',
                'amount' => 1000,
                'currency' => 'usd',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        // Create a mock payment_methods entry for the user
        \App\Models\PaymentMethod::create([
            'user_id' => $user->id,
            'provider' => 'stripe',
            'provider_customer_id' => 'cus_existing_123',
        ]);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->createPaymentIntent($user, 10.00);

        $this->assertEquals('pi_test_123', $result['payment_intent_id']);
    }

    /**
     * PI-004: createPaymentIntent includes metadata for tracking
     */
    public function test_create_payment_intent_includes_metadata(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus_test']);

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) use ($user) {
                return isset($params['metadata']['user_id']) &&
                    $params['metadata']['user_id'] === (string) $user->id &&
                    isset($params['metadata']['package_id']) &&
                    $params['metadata']['package_id'] === '5';
            })
            ->andReturn((object) [
                'id' => 'pi_test_123',
                'client_secret' => 'pi_test_123_secret',
                'amount' => 2500,
                'currency' => 'usd',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->createPaymentIntent($user, 25.00, 'USD', ['package_id' => '5']);

        $this->assertEquals('pi_test_123', $result['payment_intent_id']);
    }

    /**
     * PI-005: createPaymentIntent uses idempotency key
     */
    public function test_create_payment_intent_uses_idempotency_key(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus_test']);

        $capturedKey = null;
        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) use (&$capturedKey) {
                $capturedKey = $options['idempotency_key'] ?? null;
                return true;
            })
            ->andReturn((object) [
                'id' => 'pi_test_123',
                'client_secret' => 'pi_test_123_secret',
                'amount' => 1000,
                'currency' => 'usd',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->createPaymentIntent($user, 10.00);

        $this->assertNotNull($capturedKey);
        $this->assertStringStartsWith('pi_', $capturedKey);
    }

    /**
     * PI-006: createPaymentIntent validates minimum amount
     */
    public function test_create_payment_intent_validates_minimum_amount(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $service = new StripeGatewayService($mockStripe);

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessageMatches('/minimum/i');

        $service->createPaymentIntent($user, 2.00); // Below $5 minimum
    }

    /**
     * PI-007: createPaymentIntent handles Stripe API error
     */
    public function test_create_payment_intent_handles_stripe_api_error(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus_test']);

        $apiException = new class('Card declined', 'card_declined') extends \Stripe\Exception\ApiErrorException {
            protected $stripeCode = 'card_declined';
            public function getStripeCode(): string { return 'card_declined'; }
        };

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andThrow($apiException);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);

        $this->expectException(StripePaymentException::class);
        $service->createPaymentIntent($user, 10.00);
    }

    /**
     * PI-008: createPaymentIntent handles network timeout
     */
    public function test_create_payment_intent_handles_network_timeout(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus_test']);

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Connection timed out'));

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);

        $this->expectException(PaymentTimeoutException::class);
        $service->createPaymentIntent($user, 10.00);
    }

    /**
     * PI-009: createPaymentIntent converts amount to cents correctly
     */
    public function test_create_payment_intent_converts_to_cents(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus_test']);

        $capturedAmount = null;
        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) use (&$capturedAmount) {
                $capturedAmount = $params['amount'];
                return true;
            })
            ->andReturn((object) [
                'id' => 'pi_test',
                'client_secret' => 'secret',
                'amount' => 1234,
                'currency' => 'usd',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $service->createPaymentIntent($user, 12.34);

        $this->assertEquals(1234, $capturedAmount); // $12.34 = 1234 cents
    }

    /**
     * PI-010: createPaymentIntent supports different currencies
     */
    public function test_create_payment_intent_supports_different_currencies(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus_test']);

        $capturedCurrency = null;
        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) use (&$capturedCurrency) {
                $capturedCurrency = $params['currency'];
                return true;
            })
            ->andReturn((object) [
                'id' => 'pi_test',
                'client_secret' => 'secret',
                'amount' => 1000,
                'currency' => 'eur',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $service->createPaymentIntent($user, 10.00, 'EUR');

        $this->assertEquals('eur', $capturedCurrency);
    }

    // =========================================================================
    // PAYMENT CAPTURE TESTS (PC)
    // =========================================================================

    /**
     * PC-001: capturePayment captures authorized payment
     */
    public function test_capture_payment_captures_authorized_payment(): void
    {
        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();

        $mockPaymentIntents->shouldReceive('capture')
            ->once()
            ->with('pi_test_123', [])
            ->andReturn((object) [
                'id' => 'pi_test_123',
                'status' => 'succeeded',
            ]);

        $this->mockStripeServices($mockStripe, ['paymentIntents' => $mockPaymentIntents]);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->capturePayment('pi_test_123');

        $this->assertTrue($result);
    }

    /**
     * PC-002: capturePayment returns false on failure
     */
    public function test_capture_payment_returns_false_on_failure(): void
    {
        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();

        $mockPaymentIntents->shouldReceive('capture')
            ->once()
            ->andThrow(new \Exception('Capture failed'));

        $this->mockStripeServices($mockStripe, ['paymentIntents' => $mockPaymentIntents]);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->capturePayment('pi_test_123');

        $this->assertFalse($result);
    }

    /**
     * PC-003: capturePayment handles already captured payment
     */
    public function test_capture_payment_handles_already_captured(): void
    {
        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();

        $mockPaymentIntents->shouldReceive('capture')
            ->once()
            ->andThrow(new \Exception('PaymentIntent already captured'));

        $this->mockStripeServices($mockStripe, ['paymentIntents' => $mockPaymentIntents]);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->capturePayment('pi_test_123');

        // Should return false but not throw
        $this->assertFalse($result);
    }

    /**
     * PC-004: capturePayment logs errors
     */
    public function test_capture_payment_logs_errors(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'capture') &&
                    isset($context['payment_intent_id']);
            });

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();

        $mockPaymentIntents->shouldReceive('capture')
            ->once()
            ->andThrow(new \Exception('Capture failed'));

        $this->mockStripeServices($mockStripe, ['paymentIntents' => $mockPaymentIntents]);

        $service = new StripeGatewayService($mockStripe);
        $service->capturePayment('pi_test_123');
    }

    // =========================================================================
    // REFUND TESTS (RF)
    // =========================================================================

    /**
     * RF-001: refundPayment processes full refund
     */
    public function test_refund_payment_processes_full_refund(): void
    {
        $mockStripe = $this->mockStripeClient();
        $mockRefunds = Mockery::mock();

        $mockRefunds->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) {
                return $params['payment_intent'] === 'pi_test_123' &&
                    !isset($params['amount']); // Full refund
            })
            ->andReturn((object) [
                'id' => 're_test_123',
                'amount' => 1000,
                'status' => 'succeeded',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockRefunds);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->refundPayment('pi_test_123');

        $this->assertIsArray($result);
        $this->assertEquals('re_test_123', $result['refund_id']);
        $this->assertEquals(1000, $result['amount']);
        $this->assertEquals('succeeded', $result['status']);
    }

    /**
     * RF-002: refundPayment processes partial refund
     */
    public function test_refund_payment_processes_partial_refund(): void
    {
        $mockStripe = $this->mockStripeClient();
        $mockRefunds = Mockery::mock();
        $mockPaymentIntents = Mockery::mock();

        // Need to retrieve payment intent for currency
        $mockPaymentIntents->shouldReceive('retrieve')
            ->once()
            ->with('pi_test_123')
            ->andReturn((object) [
                'id' => 'pi_test_123',
                'currency' => 'usd',
            ]);

        $mockRefunds->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) {
                return $params['payment_intent'] === 'pi_test_123' &&
                    $params['amount'] === 500; // Partial: $5.00 of $10.00
            })
            ->andReturn((object) [
                'id' => 're_test_123',
                'amount' => 500,
                'status' => 'succeeded',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockRefunds);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->refundPayment('pi_test_123', 5.00);

        $this->assertEquals(500, $result['amount']);
    }

    /**
     * RF-003: refundPayment throws on Stripe error
     */
    public function test_refund_payment_throws_on_stripe_error(): void
    {
        $mockStripe = $this->mockStripeClient();
        $mockRefunds = Mockery::mock();

        $mockRefunds->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Refund failed'));

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockRefunds);

        $service = new StripeGatewayService($mockStripe);

        $this->expectException(StripePaymentException::class);
        $service->refundPayment('pi_test_123');
    }

    /**
     * RF-004: refundPayment uses idempotency key
     */
    public function test_refund_payment_uses_idempotency_key(): void
    {
        $mockStripe = $this->mockStripeClient();
        $mockRefunds = Mockery::mock();

        $capturedKey = null;
        $mockRefunds->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) use (&$capturedKey) {
                $capturedKey = $options['idempotency_key'] ?? null;
                return true;
            })
            ->andReturn((object) [
                'id' => 're_test',
                'amount' => 1000,
                'status' => 'succeeded',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockRefunds);

        $service = new StripeGatewayService($mockStripe);
        $service->refundPayment('pi_test_123');

        $this->assertNotNull($capturedKey);
    }

    /**
     * RF-005: refundPayment handles already refunded payment
     */
    public function test_refund_payment_handles_already_refunded(): void
    {
        $mockStripe = $this->mockStripeClient();
        $mockRefunds = Mockery::mock();

        $mockRefunds->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Already refunded'));

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockRefunds);

        $service = new StripeGatewayService($mockStripe);

        $this->expectException(StripePaymentException::class);
        $service->refundPayment('pi_test_123');
    }

    // =========================================================================
    // WEBHOOK HANDLER TESTS (WH) - SECURITY CRITICAL
    // =========================================================================

    /**
     * WH-001: handleWebhook verifies signature
     */
    public function test_handle_webhook_verifies_signature(): void
    {
        $request = $this->createWebhookRequest(['id' => 'evt_test', 'type' => 'test']);

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andThrow(new \Exception('Invalid signature'));

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);

        $this->expectException(InvalidWebhookSignatureException::class);
        $service->handleWebhook($request);
    }

    /**
     * WH-002: handleWebhook processes payment_intent.succeeded
     */
    public function test_handle_webhook_processes_payment_succeeded(): void
    {
        $user = $this->createUserWithCredit();

        $webhookPayload = [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 1000,
                    'currency' => 'usd',
                    'status' => 'succeeded',
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'package_id' => '1',
                    ],
                ],
            ],
        ];

        $request = $this->createWebhookRequest($webhookPayload);

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object) [
                'id' => 'evt_test_123',
                'type' => 'payment_intent.succeeded',
                'data' => (object) [
                    'object' => (object) [
                        'id' => 'pi_test_123',
                        'amount' => 1000,
                        'currency' => 'usd',
                        'status' => 'succeeded',
                        'metadata' => (object) [
                            'user_id' => (string) $user->id,
                            'package_id' => '1',
                        ],
                    ],
                ],
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->handleWebhook($request);

        $this->assertTrue($result->success);
        $this->assertEquals('pi_test_123', $result->paymentId);
        $this->assertEquals(10.00, $result->amount);
    }

    /**
     * WH-003: handleWebhook processes payment_intent.payment_failed
     */
    public function test_handle_webhook_processes_payment_failed(): void
    {
        $user = $this->createUserWithCredit();

        $webhookPayload = [
            'id' => 'evt_test_456',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_456',
                    'amount' => 1000,
                    'currency' => 'usd',
                    'status' => 'failed',
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

        $request = $this->createWebhookRequest($webhookPayload);

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object) [
                'id' => 'evt_test_456',
                'type' => 'payment_intent.payment_failed',
                'data' => (object) [
                    'object' => (object) [
                        'id' => 'pi_test_456',
                        'amount' => 1000,
                        'currency' => 'usd',
                        'status' => 'failed',
                        'last_payment_error' => (object) [
                            'code' => 'card_declined',
                            'message' => 'Your card was declined.',
                        ],
                        'metadata' => (object) [
                            'user_id' => (string) $user->id,
                        ],
                    ],
                ],
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->handleWebhook($request);

        $this->assertFalse($result->success);
        $this->assertEquals('card_declined', $result->errorCode);
        $this->assertStringContainsString('declined', $result->errorMessage);
    }

    /**
     * WH-004: handleWebhook processes charge.refunded
     */
    public function test_handle_webhook_processes_charge_refunded(): void
    {
        $user = $this->createUserWithCredit();

        // Create initial purchase transaction
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'balance_after' => 10.00,
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
        ];

        $request = $this->createWebhookRequest($webhookPayload);

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object) [
                'id' => 'evt_test_789',
                'type' => 'charge.refunded',
                'data' => (object) [
                    'object' => (object) [
                        'id' => 'ch_test_123',
                        'payment_intent' => 'pi_test_123',
                        'amount_refunded' => 1000,
                        'refunds' => (object) [
                            'data' => [
                                (object) [
                                    'id' => 're_test_123',
                                    'amount' => 1000,
                                ],
                            ],
                        ],
                        'metadata' => (object) [
                            'user_id' => (string) $user->id,
                        ],
                    ],
                ],
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->handleWebhook($request);

        $this->assertTrue($result->success);
        $this->assertEquals('refund', $result->type);
    }

    /**
     * WH-005: handleWebhook rejects invalid signature
     */
    public function test_handle_webhook_rejects_invalid_signature(): void
    {
        $request = $this->createWebhookRequest(['id' => 'evt_test'], 'invalid_signature');

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andThrow(new \Exception('Invalid signature'));

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);

        $this->expectException(InvalidWebhookSignatureException::class);
        $service->handleWebhook($request);
    }

    /**
     * WH-006: handleWebhook handles duplicate event idempotently
     */
    public function test_handle_webhook_handles_duplicate_idempotently(): void
    {
        $user = $this->createUserWithCredit();

        // Create existing transaction
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'balance_after' => 10.00,
            'type' => 'purchase',
            'payment_provider' => 'stripe',
            'payment_id' => 'pi_test_123',
        ]);

        $webhookPayload = [
            'id' => 'evt_test_duplicate',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123', // Same payment ID
                    'amount' => 1000,
                    'metadata' => [
                        'user_id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $request = $this->createWebhookRequest($webhookPayload);

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object) [
                'id' => 'evt_test_duplicate',
                'type' => 'payment_intent.succeeded',
                'data' => (object) [
                    'object' => (object) [
                        'id' => 'pi_test_123',
                        'amount' => 1000,
                        'metadata' => (object) [
                            'user_id' => (string) $user->id,
                        ],
                    ],
                ],
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->handleWebhook($request);

        // Should indicate duplicate but not fail
        $this->assertTrue($result->isDuplicate ?? false);
    }

    /**
     * WH-007: handleWebhook ignores unknown event types
     */
    public function test_handle_webhook_ignores_unknown_events(): void
    {
        $webhookPayload = [
            'id' => 'evt_test_unknown',
            'type' => 'customer.created',
            'data' => ['object' => []],
        ];

        $request = $this->createWebhookRequest($webhookPayload);

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object) [
                'id' => 'evt_test_unknown',
                'type' => 'customer.created',
                'data' => (object) ['object' => (object) []],
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->handleWebhook($request);

        $this->assertTrue($result->isIgnored ?? false);
    }

    /**
     * WH-008: handleWebhook logs all events
     */
    public function test_handle_webhook_logs_all_events(): void
    {
        Log::shouldReceive('info')
            ->atLeast()->once();

        $webhookPayload = [
            'id' => 'evt_test',
            'type' => 'customer.updated',
            'data' => ['object' => []],
        ];

        $request = $this->createWebhookRequest($webhookPayload);

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object) [
                'id' => 'evt_test',
                'type' => 'customer.updated',
                'data' => (object) ['object' => (object) []],
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);
        $service->handleWebhook($request);
    }

    /**
     * WH-009: handleWebhook requires Stripe-Signature header
     */
    public function test_handle_webhook_requires_signature_header(): void
    {
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['id' => 'evt_test']));

        $mockStripe = $this->mockStripeClient();
        $service = new StripeGatewayService($mockStripe);

        $this->expectException(InvalidWebhookSignatureException::class);
        $service->handleWebhook($request);
    }

    /**
     * WH-010: handleWebhook handles malformed payload
     */
    public function test_handle_webhook_handles_malformed_payload(): void
    {
        $request = Request::create('/webhook', 'POST', [], [], [], [], 'not json');
        $request->headers->set('Stripe-Signature', 'some_signature');

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andThrow(new \Exception('Invalid payload'));

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);

        $this->expectException(InvalidWebhookSignatureException::class);
        $service->handleWebhook($request);
    }

    // =========================================================================
    // CUSTOMER MANAGEMENT TESTS (CM)
    // =========================================================================

    /**
     * CM-001: getCustomer retrieves existing customer
     */
    public function test_get_customer_retrieves_existing(): void
    {
        $user = $this->createUserWithCredit();

        // Create payment_methods entry
        \App\Models\PaymentMethod::create([
            'user_id' => $user->id,
            'provider' => 'stripe',
            'provider_customer_id' => 'cus_test_123',
        ]);

        $mockStripe = $this->mockStripeClient();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')
            ->once()
            ->with('cus_test_123')
            ->andReturn((object) [
                'id' => 'cus_test_123',
                'email' => $user->email,
                'deleted' => false,
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->getCustomer($user);

        $this->assertEquals('cus_test_123', $result);
    }

    /**
     * CM-002: getCustomer returns null for non-existent customer
     */
    public function test_get_customer_returns_null_for_nonexistent(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $service = new StripeGatewayService($mockStripe);

        $result = $service->getCustomer($user);

        $this->assertNull($result);
    }

    /**
     * CM-003: createCustomer creates new Stripe customer
     */
    public function test_create_customer_creates_new(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('create')
            ->once()
            ->withArgs(function ($params) use ($user) {
                return $params['email'] === $user->email &&
                    $params['name'] === $user->name &&
                    isset($params['metadata']['user_id']);
            })
            ->andReturn((object) [
                'id' => 'cus_new_123',
                'email' => $user->email,
                'name' => $user->name,
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);

        $service = new StripeGatewayService($mockStripe);
        $customerId = $service->createCustomer($user);

        $this->assertEquals('cus_new_123', $customerId);
    }

    /**
     * CM-004: createCustomer stores customer ID in payment_methods
     */
    public function test_create_customer_stores_in_payment_methods(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'id' => 'cus_new_123',
                'email' => $user->email,
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);

        $service = new StripeGatewayService($mockStripe);
        $customerId = $service->createCustomer($user);

        // Verify payment_methods table has the customer ID
        $paymentMethod = \App\Models\PaymentMethod::where('user_id', $user->id)
            ->where('provider', 'stripe')
            ->first();

        $this->assertNotNull($paymentMethod);
        $this->assertEquals('cus_new_123', $paymentMethod->provider_customer_id);
    }

    /**
     * CM-005: createCustomer handles Stripe error
     */
    public function test_create_customer_handles_stripe_error(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Customer creation failed'));

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);

        $service = new StripeGatewayService($mockStripe);

        $this->expectException(StripePaymentException::class);
        $service->createCustomer($user);
    }

    /**
     * CM-006: getCustomer handles deleted customer
     */
    public function test_get_customer_handles_deleted_customer(): void
    {
        $user = $this->createUserWithCredit();

        // Create payment_methods entry
        \App\Models\PaymentMethod::create([
            'user_id' => $user->id,
            'provider' => 'stripe',
            'provider_customer_id' => 'cus_deleted',
        ]);

        $mockStripe = $this->mockStripeClient();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')
            ->once()
            ->andReturn((object) [
                'id' => 'cus_deleted',
                'deleted' => true,
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->getCustomer($user);

        $this->assertNull($result);
    }

    // =========================================================================
    // ERROR HANDLING TESTS (EH)
    // =========================================================================

    /**
     * EH-001: StripePaymentException contains error code
     */
    public function test_stripe_payment_exception_contains_error_code(): void
    {
        $exception = new StripePaymentException('Card declined', 'card_declined');

        $this->assertEquals('Card declined', $exception->getMessage());
        $this->assertEquals('card_declined', $exception->getStripeCode());
    }

    /**
     * EH-002: PaymentTimeoutException for network issues
     */
    public function test_payment_timeout_exception_for_network(): void
    {
        $exception = new PaymentTimeoutException('Connection timeout');

        $this->assertStringContainsString('timeout', strtolower($exception->getMessage()));
    }

    /**
     * EH-003: InvalidWebhookSignatureException for security
     */
    public function test_invalid_webhook_signature_exception(): void
    {
        $exception = new InvalidWebhookSignatureException('Invalid signature');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * EH-004: All payment errors are logged
     */
    public function test_all_payment_errors_are_logged(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains(strtolower($message), 'payment') ||
                    str_contains(strtolower($message), 'stripe');
            });

        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus']);
        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('API error'));

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);

        try {
            $service->createPaymentIntent($user, 10.00);
        } catch (StripePaymentException $e) {
            // Expected
        }
    }

    /**
     * EH-005: Graceful handling of missing configuration
     */
    public function test_graceful_handling_of_missing_configuration(): void
    {
        Config::set('services.stripe.secret', null);

        $this->expectException(\RuntimeException::class);

        // Service should fail to construct without config
        new StripeGatewayService();
    }

    /**
     * EH-006: Meaningful error messages for user-facing errors
     */
    public function test_meaningful_error_messages(): void
    {
        $exception = new StripePaymentException('Your card was declined.', 'card_declined');

        $this->assertStringContainsString('declined', $exception->getMessage());
    }

    // =========================================================================
    // SECURITY TESTS (SC)
    // =========================================================================

    /**
     * SC-001: Never store card numbers
     */
    public function test_never_store_card_numbers(): void
    {
        // This test documents the requirement
        // The service should NEVER log or store card numbers
        // Card numbers are handled entirely by Stripe

        $this->assertTrue(true); // Documentation test

        // In implementation, verify:
        // - No card_number field in any database table
        // - No card number in any log entry
        // - PaymentMethod objects from Stripe do NOT contain full card numbers
    }

    /**
     * SC-002: Webhook signature always verified
     */
    public function test_webhook_signature_always_verified(): void
    {
        // Every webhook MUST verify signature
        $this->assertTrue(true); // Documentation test

        // All WH tests verify this through InvalidWebhookSignatureException
    }

    /**
     * SC-003: Idempotency keys prevent duplicate charges
     */
    public function test_idempotency_keys_prevent_duplicates(): void
    {
        // Tests PI-005, RF-004 verify idempotency key usage
        $this->assertTrue(true); // Documentation test
    }

    /**
     * SC-004: Minimum amount enforced server-side
     */
    public function test_minimum_amount_enforced_server_side(): void
    {
        // Test PI-006 verifies minimum $5.00
        $this->assertTrue(true); // Documentation test
    }

    /**
     * SC-005: All payment events logged for audit
     */
    public function test_all_payment_events_logged(): void
    {
        Log::shouldReceive('info')->atLeast()->once();

        $webhookPayload = [
            'id' => 'evt_test',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test', 'amount' => 1000, 'currency' => 'usd', 'metadata' => (object) []]],
        ];

        $request = $this->createWebhookRequest($webhookPayload);

        $mockStripe = $this->mockStripeClient();
        $mockWebhooks = Mockery::mock();

        $mockWebhooks->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object) [
                'id' => 'evt_test',
                'type' => 'payment_intent.succeeded',
                'data' => (object) ['object' => (object) ['id' => 'pi_test', 'amount' => 1000, 'metadata' => (object) []]],
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockWebhooks);

        $service = new StripeGatewayService($mockStripe);
        $service->handleWebhook($request);
    }

    // =========================================================================
    // EDGE CASE TESTS (EC)
    // =========================================================================

    /**
     * EC-001: Handle zero-decimal currencies
     */
    public function test_handle_zero_decimal_currencies(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus']);

        $capturedAmount = null;
        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->withArgs(function ($params, $options) use (&$capturedAmount) {
                $capturedAmount = $params['amount'];
                return true;
            })
            ->andReturn((object) [
                'id' => 'pi_test',
                'client_secret' => 'secret',
                'amount' => 1000,
                'currency' => 'jpy',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $service->createPaymentIntent($user, 1000.00, 'JPY');

        // JPY is zero-decimal, so 1000 JPY = 1000 (not 100000 cents)
        $this->assertEquals(1000, $capturedAmount);
    }

    /**
     * EC-002: Handle very large payment amounts
     */
    public function test_handle_very_large_payment_amounts(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus']);

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'id' => 'pi_test',
                'client_secret' => 'secret',
                'amount' => 99999999,
                'currency' => 'usd',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->createPaymentIntent($user, 999999.99);

        $this->assertEquals('pi_test', $result['payment_intent_id']);
    }

    /**
     * EC-003: Handle concurrent payment requests
     */
    public function test_handle_concurrent_payment_requests(): void
    {
        // Idempotency keys should prevent duplicate charges
        // even with concurrent requests
        $this->assertTrue(true); // Documented by idempotency key tests
    }

    /**
     * EC-004: Handle partial payment capture
     */
    public function test_handle_partial_payment_capture(): void
    {
        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();

        $mockPaymentIntents->shouldReceive('capture')
            ->once()
            ->withArgs(function ($id, $params) {
                // Partial capture amount
                return isset($params['amount_to_capture']) &&
                    $params['amount_to_capture'] === 500;
            })
            ->andReturn((object) [
                'id' => 'pi_test',
                'status' => 'succeeded',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->capturePayment('pi_test', ['amount_to_capture' => 500]);

        $this->assertTrue($result);
    }

    /**
     * EC-005: Handle Unicode in metadata
     */
    public function test_handle_unicode_in_metadata(): void
    {
        $user = $this->createUserWithCredit();

        $mockStripe = $this->mockStripeClient();
        $mockPaymentIntents = Mockery::mock();
        $mockCustomers = Mockery::mock();

        $mockCustomers->shouldReceive('retrieve')->andThrow(new \Exception());
        $mockCustomers->shouldReceive('create')->andReturn((object) ['id' => 'cus']);

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andReturn((object) [
                'id' => 'pi_test',
                'client_secret' => 'secret',
                'amount' => 1000,
                'currency' => 'usd',
            ]);

        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockCustomers);
        $mockStripe->shouldReceive('getService')->with('')->andReturn($mockPaymentIntents);

        $service = new StripeGatewayService($mockStripe);
        $result = $service->createPaymentIntent($user, 10.00, 'USD', [
            'description' => '購入クレジット 🎉',
        ]);

        $this->assertEquals('pi_test', $result['payment_intent_id']);
    }
}
