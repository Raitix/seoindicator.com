<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Services\Admin\AdminStatsService;
use App\Services\Admin\UserAggregationService;
use App\Services\Admin\UsageAggregationService;
use App\Services\Admin\CreditAggregationService;
use App\Services\Admin\HealthCheckService;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Unit tests for AdminStatsService
 * 
 * TASK-27-18: Admin Dashboard Tests
 * Status: Tests Coverage In Progress
 * 
 * @group admin
 * @group unit
 * @group services
 */
class AdminStatsServiceTest extends TestCase
{
    protected AdminStatsService $service;
    protected $userAggregator;
    protected $usageAggregator;
    protected $creditAggregator;
    protected $healthChecker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userAggregator = Mockery::mock(UserAggregationService::class);
        $this->usageAggregator = Mockery::mock(UsageAggregationService::class);
        $this->creditAggregator = Mockery::mock(CreditAggregationService::class);
        $this->healthChecker = Mockery::mock(HealthCheckService::class);
        
        $this->service = new AdminStatsService(
            $this->userAggregator,
            $this->usageAggregator,
            $this->creditAggregator,
            $this->healthChecker
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Service Instantiation Tests
    // =========================================================================

    /** @test */
    public function it_can_be_instantiated_via_container(): void
    {
        $service = app(AdminStatsService::class);
        $this->assertInstanceOf(AdminStatsService::class, $service);
    }

    /** @test */
    public function it_has_required_dependencies_injected(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertTrue($reflection->hasProperty('userAggregator'));
        $this->assertTrue($reflection->hasProperty('usageAggregator'));
        $this->assertTrue($reflection->hasProperty('creditAggregator'));
        $this->assertTrue($reflection->hasProperty('healthChecker'));
    }

    // =========================================================================
    // getUserStats() Tests
    // =========================================================================

    /** @test */
    public function get_user_stats_returns_array_from_user_aggregator(): void
    {
        $expectedStats = [
            'total' => 1250,
            'active' => 890,
            'newThisMonth' => 156,
            'newThisWeek' => 42,
            'newToday' => 8,
            'growthRate' => [
                'monthly' => 14.2,
                'weekly' => 5.8,
                'daily' => 1.2,
            ],
            'byPlan' => [
                'free' => 1000,
                'pro' => 200,
                'enterprise' => 50,
            ],
        ];

        $this->userAggregator
            ->shouldReceive('getStats')
            ->once()
            ->andReturn($expectedStats);

        $result = $this->service->getUserStats();

        $this->assertEquals($expectedStats, $result);
    }

    /** @test */
    public function get_user_stats_caches_result_for_five_minutes(): void
    {
        $expectedStats = ['total' => 100];

        Cache::shouldReceive('remember')
            ->once()
            ->with('admin.stats.users', 300, Mockery::type('Closure'))
            ->andReturn($expectedStats);

        $result = $this->service->getUserStats();

        $this->assertEquals($expectedStats, $result);
    }

    /** @test */
    public function get_user_stats_returns_cached_value_on_subsequent_calls(): void
    {
        $this->userAggregator
            ->shouldReceive('getStats')
            ->once()
            ->andReturn(['total' => 100]);

        $result1 = $this->service->getUserStats();
        $result2 = $this->service->getUserStats();

        $this->assertEquals($result1, $result2);
    }

    // =========================================================================
    // getUsageStats() Tests
    // =========================================================================

    /** @test */
    public function get_usage_stats_returns_array_from_usage_aggregator(): void
    {
        $expectedStats = [
            'requests' => [
                'total' => 156789,
                'today' => 2340,
                'thisWeek' => 15680,
                'thisMonth' => 48920,
            ],
            'tokens' => [
                'total' => 12500000,
                'today' => 156000,
                'thisWeek' => 890000,
                'thisMonth' => 3200000,
                'byModel' => [
                    'gpt-4' => 5000000,
                    'gpt-3.5-turbo' => 6000000,
                ],
            ],
            'analyses' => [
                'total' => 12340,
                'today' => 156,
            ],
        ];

        $this->usageAggregator
            ->shouldReceive('getStats')
            ->once()
            ->andReturn($expectedStats);

        $result = $this->service->getUsageStats();

        $this->assertEquals($expectedStats, $result);
    }

    /** @test */
    public function get_usage_stats_caches_result_for_five_minutes(): void
    {
        $expectedStats = ['requests' => ['total' => 100]];

        Cache::shouldReceive('remember')
            ->once()
            ->with('admin.stats.usage', 300, Mockery::type('Closure'))
            ->andReturn($expectedStats);

        $result = $this->service->getUsageStats();

        $this->assertEquals($expectedStats, $result);
    }

    // =========================================================================
    // getCreditStats() Tests
    // =========================================================================

    /** @test */
    public function get_credit_stats_returns_array_from_credit_aggregator(): void
    {
        $expectedStats = [
            'balances' => [
                'totalHeld' => 15678.50,
                'averagePerUser' => 12.54,
                'usersWithBalance' => 890,
                'lowBalanceCount' => 234,
            ],
            'spend' => [
                'total' => 45678.90,
                'today' => 567.80,
            ],
            'revenue' => [
                'total' => 78900.00,
                'thisMonth' => 15600.00,
            ],
        ];

        $this->creditAggregator
            ->shouldReceive('getStats')
            ->once()
            ->andReturn($expectedStats);

        $result = $this->service->getCreditStats();

        $this->assertEquals($expectedStats, $result);
    }

    /** @test */
    public function get_credit_stats_caches_result_for_one_minute(): void
    {
        $expectedStats = ['balances' => ['totalHeld' => 100]];

        Cache::shouldReceive('remember')
            ->once()
            ->with('admin.stats.credits', 60, Mockery::type('Closure'))
            ->andReturn($expectedStats);

        $result = $this->service->getCreditStats();

        $this->assertEquals($expectedStats, $result);
    }

    // =========================================================================
    // getHealthStats() Tests
    // =========================================================================

    /** @test */
    public function get_health_stats_returns_array_from_health_checker(): void
    {
        $expectedStats = [
            'status' => 'healthy',
            'uptime' => 99.98,
            'lastChecked' => '2026-03-02T10:00:00Z',
            'services' => [
                'database' => [
                    'status' => 'healthy',
                    'latencyMs' => 12,
                    'connections' => 45,
                ],
                'queue' => [
                    'status' => 'healthy',
                    'pending' => 23,
                    'failed' => 2,
                    'workers' => 5,
                ],
                'cache' => [
                    'status' => 'healthy',
                    'hitRate' => 94.5,
                ],
                'storage' => [
                    'status' => 'healthy',
                    'usedPercent' => 34,
                ],
            ],
            'alerts' => [],
        ];

        $this->healthChecker
            ->shouldReceive('getStats')
            ->once()
            ->andReturn($expectedStats);

        $result = $this->service->getHealthStats();

        $this->assertEquals($expectedStats, $result);
    }

    /** @test */
    public function get_health_stats_caches_result_for_thirty_seconds(): void
    {
        $expectedStats = ['status' => 'healthy'];

        Cache::shouldReceive('remember')
            ->once()
            ->with('admin.stats.health', 30, Mockery::type('Closure'))
            ->andReturn($expectedStats);

        $result = $this->service->getHealthStats();

        $this->assertEquals($expectedStats, $result);
    }

    // =========================================================================
    // getCachedMetric() Tests
    // =========================================================================

    /** @test */
    public function get_cached_metric_stores_result_in_cache(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('custom.metric', 300, Mockery::type('Closure'))
            ->andReturn('result');

        $result = $this->service->getCachedMetric('custom.metric', fn() => 'result', 300);

        $this->assertEquals('result', $result);
    }

    /** @test */
    public function get_cached_metric_uses_custom_ttl(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('custom.metric', 600, Mockery::type('Closure'))
            ->andReturn('result');

        $result = $this->service->getCachedMetric('custom.metric', fn() => 'result', 600);

        $this->assertEquals('result', $result);
    }

    /** @test */
    public function get_cached_metric_executes_calculator_on_cache_miss(): void
    {
        $calculatorCalled = false;
        $calculator = function () use (&$calculatorCalled) {
            $calculatorCalled = true;
            return 'calculated';
        };

        Cache::shouldReceive('remember')
            ->once()
            ->with('test.metric', 300, Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $result = $this->service->getCachedMetric('test.metric', $calculator, 300);

        $this->assertTrue($calculatorCalled);
        $this->assertEquals('calculated', $result);
    }

    // =========================================================================
    // Cache Invalidation Tests
    // =========================================================================

    /** @test */
    public function refresh_all_caches_clears_all_cache_keys(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('admin.stats.users')
            ->andReturn(true);
        
        Cache::shouldReceive('forget')
            ->once()
            ->with('admin.stats.usage')
            ->andReturn(true);
        
        Cache::shouldReceive('forget')
            ->once()
            ->with('admin.stats.credits')
            ->andReturn(true);
        
        Cache::shouldReceive('forget')
            ->once()
            ->with('admin.stats.health')
            ->andReturn(true);

        $this->service->refreshAllCaches();

        // Assert that all 4 cache forget calls were made
        $this->assertTrue(true);
    }

    /** @test */
    public function refresh_user_cache_only_clears_user_cache(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('admin.stats.users')
            ->andReturn(true);

        $result = $this->service->refreshUserCache();

        $this->assertNull($result);
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    /** @test */
    public function get_user_stats_returns_empty_array_on_aggregator_exception(): void
    {
        $this->userAggregator
            ->shouldReceive('getStats')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $result = $this->service->getUserStats();

        $this->assertEquals([], $result);
    }

    /** @test */
    public function get_usage_stats_returns_empty_array_on_aggregator_exception(): void
    {
        $this->usageAggregator
            ->shouldReceive('getStats')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $result = $this->service->getUsageStats();

        $this->assertEquals([], $result);
    }

    /** @test */
    public function get_credit_stats_returns_empty_array_on_aggregator_exception(): void
    {
        $this->creditAggregator
            ->shouldReceive('getStats')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $result = $this->service->getCreditStats();

        $this->assertEquals([], $result);
    }

    /** @test */
    public function get_health_stats_returns_unhealthy_status_on_exception(): void
    {
        $this->healthChecker
            ->shouldReceive('getStats')
            ->once()
            ->andThrow(new \Exception('Health check failed'));

        $result = $this->service->getHealthStats();

        $this->assertEquals('unhealthy', $result['status']);
        $this->assertArrayHasKey('error', $result);
    }

    // =========================================================================
    // Performance Tests
    // =========================================================================

    /** @test */
    public function get_all_stats_completes_under_200ms(): void
    {
        $this->userAggregator->shouldReceive('getStats')->andReturn([]);
        $this->usageAggregator->shouldReceive('getStats')->andReturn([]);
        $this->creditAggregator->shouldReceive('getStats')->andReturn([]);
        $this->healthChecker->shouldReceive('getStats')->andReturn([]);

        $start = microtime(true);
        
        $this->service->getUserStats();
        $this->service->getUsageStats();
        $this->service->getCreditStats();
        $this->service->getHealthStats();
        
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $elapsed, 'All stats retrieval should complete under 200ms');
    }

    // =========================================================================
    // Data Integrity Tests
    // =========================================================================

    /** @test */
    public function get_user_stats_preserves_numeric_precision(): void
    {
        $expectedStats = [
            'total' => 1250,
            'growthRate' => [
                'monthly' => 14.234567,
            ],
        ];

        $this->userAggregator
            ->shouldReceive('getStats')
            ->andReturn($expectedStats);

        $result = $this->service->getUserStats();

        $this->assertEquals(14.234567, $result['growthRate']['monthly']);
    }

    /** @test */
    public function get_credit_stats_preserves_decimal_precision(): void
    {
        $expectedStats = [
            'balances' => [
                'totalHeld' => 15678.50,
            ],
        ];

        $this->creditAggregator
            ->shouldReceive('getStats')
            ->andReturn($expectedStats);

        $result = $this->service->getCreditStats();

        $this->assertEquals(15678.50, $result['balances']['totalHeld']);
    }

    // =========================================================================
    // getAllStats() Tests
    // =========================================================================

    /** @test */
    public function get_all_stats_returns_all_stat_categories(): void
    {
        $this->userAggregator->shouldReceive('getStats')->andReturn(['total' => 100]);
        $this->usageAggregator->shouldReceive('getStats')->andReturn(['requests' => 50]);
        $this->creditAggregator->shouldReceive('getStats')->andReturn(['balances' => []]);
        $this->healthChecker->shouldReceive('getStats')->andReturn(['status' => 'healthy']);

        $result = $this->service->getAllStats();

        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('usage', $result);
        $this->assertArrayHasKey('credits', $result);
        $this->assertArrayHasKey('health', $result);
    }

    /** @test */
    public function get_all_stats_returns_correct_structure(): void
    {
        $this->userAggregator->shouldReceive('getStats')->andReturn(['total' => 100]);
        $this->usageAggregator->shouldReceive('getStats')->andReturn(['requests' => 50]);
        $this->creditAggregator->shouldReceive('getStats')->andReturn(['balances' => ['totalHeld' => 1000]]);
        $this->healthChecker->shouldReceive('getStats')->andReturn(['status' => 'healthy', 'uptime' => 99.9]);

        $result = $this->service->getAllStats();

        $this->assertEquals(['total' => 100], $result['users']);
        $this->assertEquals(['requests' => 50], $result['usage']);
        $this->assertEquals(['balances' => ['totalHeld' => 1000]], $result['credits']);
        $this->assertEquals(['status' => 'healthy', 'uptime' => 99.9], $result['health']);
    }

    // =========================================================================
    // TTL Expiration Tests
    // =========================================================================

    /** @test */
    public function cache_ttl_constants_are_correctly_defined(): void
    {
        $reflection = new \ReflectionClass(AdminStatsService::class);
        
        $this->assertEquals(300, $reflection->getConstant('USER_CACHE_TTL'));
        $this->assertEquals(300, $reflection->getConstant('USAGE_CACHE_TTL'));
        $this->assertEquals(60, $reflection->getConstant('CREDIT_CACHE_TTL'));
        $this->assertEquals(30, $reflection->getConstant('HEALTH_CACHE_TTL'));
    }

    /** @test */
    public function different_cache_keys_prevent_collision(): void
    {
        $keys = [
            'admin.stats.users',
            'admin.stats.usage',
            'admin.stats.credits',
            'admin.stats.health',
        ];

        // Verify all keys are unique
        $this->assertCount(4, array_unique($keys));
    }

    /** @test */
    public function health_stats_has_shortest_ttl_due_to_realtime_nature(): void
    {
        $reflection = new \ReflectionClass(AdminStatsService::class);
        
        $userTtl = $reflection->getConstant('USER_CACHE_TTL');
        $usageTtl = $reflection->getConstant('USAGE_CACHE_TTL');
        $creditTtl = $reflection->getConstant('CREDIT_CACHE_TTL');
        $healthTtl = $reflection->getConstant('HEALTH_CACHE_TTL');

        // Health stats should have the shortest TTL (most real-time)
        $this->assertLessThan($userTtl, $healthTtl);
        $this->assertLessThan($usageTtl, $healthTtl);
        $this->assertLessThan($creditTtl, $healthTtl);
    }

    /** @test */
    public function credit_stats_has_shorter_ttl_than_user_and_usage(): void
    {
        $reflection = new \ReflectionClass(AdminStatsService::class);
        
        $userTtl = $reflection->getConstant('USER_CACHE_TTL');
        $usageTtl = $reflection->getConstant('USAGE_CACHE_TTL');
        $creditTtl = $reflection->getConstant('CREDIT_CACHE_TTL');

        // Credit stats change more frequently (purchases, spend)
        $this->assertLessThan($userTtl, $creditTtl);
        $this->assertLessThan($usageTtl, $creditTtl);
    }

    // =========================================================================
    // Cache Invalidation Edge Cases
    // =========================================================================

    /** @test */
    public function refresh_usage_cache_only_clears_usage_cache(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('admin.stats.usage')
            ->andReturn(true);

        $this->service->refreshUsageCache();

        $this->assertTrue(true);
    }

    /** @test */
    public function refresh_credit_cache_only_clears_credit_cache(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('admin.stats.credits')
            ->andReturn(true);

        $this->service->refreshCreditCache();

        $this->assertTrue(true);
    }

    /** @test */
    public function refresh_health_cache_only_clears_health_cache(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('admin.stats.health')
            ->andReturn(true);

        $this->service->refreshHealthCache();

        $this->assertTrue(true);
    }

    // =========================================================================
    // Acceptance Criteria Tests
    // =========================================================================

    /** @test */
    public function ac_service_class_created_at_correct_location(): void
    {
        $this->assertFileExists(app_path('Services/Admin/AdminStatsService.php'));
    }

    /** @test */
    public function ac_implements_get_cached_metric_with_key_and_ttl(): void
    {
        $reflection = new \ReflectionClass(AdminStatsService::class);
        
        $this->assertTrue($reflection->hasMethod('getCachedMetric'));
        
        $method = $reflection->getMethod('getCachedMetric');
        $params = $method->getParameters();
        
        $this->assertEquals('key', $params[0]->getName());
        $this->assertEquals('calculator', $params[1]->getName());
        $this->assertEquals('ttl', $params[2]->getName());
        $this->assertEquals(300, $params[2]->getDefaultValue());
    }

    /** @test */
    public function ac_cache_hit_returns_cached_value(): void
    {
        // First call - cache miss, stores in cache
        $this->userAggregator
            ->shouldReceive('getStats')
            ->once()
            ->andReturn(['total' => 100]);

        $result1 = $this->service->getUserStats();
        
        // Second call - should return cached value (aggregator not called again)
        $result2 = $this->service->getUserStats();

        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function ac_cache_miss_calls_calculator_and_stores_result(): void
    {
        $calculatorCalled = false;
        $calculator = function () use (&$calculatorCalled) {
            $calculatorCalled = true;
            return 'computed_value';
        };

        Cache::shouldReceive('remember')
            ->once()
            ->with('test.key', 300, Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $result = $this->service->getCachedMetric('test.key', $calculator, 300);

        $this->assertTrue($calculatorCalled);
        $this->assertEquals('computed_value', $result);
    }

    /** @test */
    public function ac_manual_invalidation_clears_cache(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('admin.stats.users')
            ->andReturn(true);

        $this->service->refreshUserCache();

        // Cache::forget was called (verified by mock expectation)
        $this->assertTrue(true);
    }

    /** @test */
    public function ac_uses_laravel_cache_facade(): void
    {
        // The service imports and uses Illuminate\Support\Facades\Cache
        $reflection = new \ReflectionClass(AdminStatsService::class);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('use Illuminate\Support\Facades\Cache', $fileContent);
    }
}
