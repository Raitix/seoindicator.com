<?php

namespace Tests\Unit\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\CreditController;
use App\Models\Credit;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for CreditController.
 *
 * Tests the balance() method in isolation with mocked dependencies.
 *
 * @see TESTS/TASK-26-6.md for full test documentation
 */
class CreditControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Controller Instantiation Tests
    // =========================================================================

    /** @test */
    public function controller_can_be_instantiated(): void
    {
        $mockService = Mockery::mock(CreditService::class);
        $controller = new CreditController($mockService);

        $this->assertInstanceOf(CreditController::class, $controller);
    }

    /** @test */
    public function controller_has_balance_method(): void
    {
        $mockService = Mockery::mock(CreditService::class);
        $controller = new CreditController($mockService);

        $this->assertTrue(method_exists($controller, 'balance'));
    }

    // =========================================================================
    // Balance Method Tests
    // =========================================================================

    /** @test */
    public function balance_returns_json_response(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $credit = new Credit([
            'user_id' => 1,
            'balance' => 100.00,
            'total_purchased' => 150.00,
            'total_spent' => 50.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->with($user)
            ->andReturn(100.00);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock the user's credit relationship
        $user->setRelation('credit', $credit);

        $response = $controller->balance($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /** @test */
    public function balance_returns_correct_structure(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 100.00,
            'total_purchased' => 150.00,
            'total_spent' => 50.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(100.00);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['success']);
    }

    /** @test */
    public function balance_includes_all_required_fields(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 100.00,
            'total_purchased' => 150.00,
            'total_spent' => 50.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(100.00);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertArrayHasKey('balance', $data['data']);
        $this->assertArrayHasKey('total_purchased', $data['data']);
        $this->assertArrayHasKey('total_spent', $data['data']);
        $this->assertArrayHasKey('formatted_balance', $data['data']);
        $this->assertArrayHasKey('has_sufficient_balance', $data['data']);
        $this->assertArrayHasKey('low_balance_warning', $data['data']);
    }

    /** @test */
    public function balance_calls_credit_service_get_balance(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 100.00,
            'total_purchased' => 150.00,
            'total_spent' => 50.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->with($user)
            ->andReturn(100.00);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $controller->balance($request);
    }

    /** @test */
    public function balance_formats_balance_with_dollar_sign(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 123.45,
            'total_purchased' => 200.00,
            'total_spent' => 76.55,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(123.45);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertEquals('$123.45', $data['data']['formatted_balance']);
    }

    /** @test */
    public function balance_sets_sufficient_balance_true_when_positive(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 50.00,
            'total_purchased' => 50.00,
            'total_spent' => 0.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(50.00);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertTrue($data['data']['has_sufficient_balance']);
    }

    /** @test */
    public function balance_sets_sufficient_balance_false_when_zero(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 0.00,
            'total_purchased' => 0.00,
            'total_spent' => 0.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(0.00);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertFalse($data['data']['has_sufficient_balance']);
    }

    /** @test */
    public function balance_sets_low_balance_warning_when_below_threshold(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 4.99,
            'total_purchased' => 10.00,
            'total_spent' => 5.01,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(4.99);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertTrue($data['data']['low_balance_warning']);
    }

    /** @test */
    public function balance_no_low_balance_warning_when_at_threshold(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 5.00,
            'total_purchased' => 5.00,
            'total_spent' => 0.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(5.00);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertFalse($data['data']['low_balance_warning']);
    }

    /** @test */
    public function balance_handles_user_without_credit_record(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(0.00);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn(null);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertEquals(0.00, $data['data']['balance']);
        $this->assertEquals(0.00, $data['data']['total_purchased']);
        $this->assertEquals(0.00, $data['data']['total_spent']);
    }

    /** @test */
    public function balance_handles_exception_gracefully(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $controller->balance($request);

        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
    }

    /** @test */
    public function balance_returns_200_status_on_success(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 100.00,
            'total_purchased' => 150.00,
            'total_spent' => 50.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(100.00);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function balance_precision_is_maintained(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 123.45,
            'total_purchased' => 200.00,
            'total_spent' => 76.55,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(123.45);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertEquals(123.45, $data['data']['balance']);
    }

    /** @test */
    public function balance_handles_large_amounts(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 99999999.99,
            'total_purchased' => 99999999.99,
            'total_spent' => 0.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(99999999.99);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertEquals(99999999.99, $data['data']['balance']);
    }

    /** @test */
    public function balance_handles_small_decimals(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $credit = new Credit([
            'user_id' => 1,
            'balance' => 0.01,
            'total_purchased' => 0.01,
            'total_spent' => 0.00,
        ]);

        $mockService = Mockery::mock(CreditService::class);
        $mockService->shouldReceive('getBalance')
            ->once()
            ->andReturn(0.01);

        $controller = new CreditController($mockService);

        $request = Request::create('/api/v1/credits/balance', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $user->shouldReceive('getAttribute')
            ->with('credit')
            ->andReturn($credit);

        $response = $controller->balance($request);
        $data = $response->getData(true);

        $this->assertEquals(0.01, $data['data']['balance']);
        $this->assertEquals('$0.01', $data['data']['formatted_balance']);
    }
}
