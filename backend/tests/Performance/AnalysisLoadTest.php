<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Services\AI\AIAnalysisServiceInterface;
use App\Services\AI\DTOs\AnalysisResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;

/**
 * Load tests for unified AI analysis system.
 *
 * Tests concurrent request handling, rate limiting, and system stability under load.
 *
 * @see TASK-31-17
 * @see TEST-31-17
 */
class AnalysisLoadTest extends TestCase
{
    use RefreshDatabase;

    protected string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturesPath = base_path('tests/fixtures');
        Config::set('ai.unified_prompt_enabled', true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock AnalysisResult for load testing.
     */
    protected function createMockResult(array $overrides = []): AnalysisResult
    {
        $defaults = [
            'overallScore' => 75,
            'overallSummary' => 'Load test result.',
            'categoryScores' => [
                'technical' => 80,
                'content' => 70,
                'accessibility' => 75,
                'performance' => 65,
            ],
            'indicators' => $this->getDefaultIndicators(),
            'model' => 'google/gemini-2.0-flash-exp:free',
            'promptTokens' => 15000,
            'responseTokens' => 2500,
            'latencyMs' => 3000,
            'truncationStrategies' => [],
            'topPriorities' => [],
        ];

        $data = array_merge($defaults, $overrides);

        return new AnalysisResult(
            overallScore: $data['overallScore'],
            overallSummary: $data['overallSummary'],
            categoryScores: $data['categoryScores'],
            indicators: $data['indicators'],
            model: $data['model'],
            promptTokens: $data['promptTokens'],
            responseTokens: $data['responseTokens'],
            latencyMs: $data['latencyMs'],
            truncationStrategies: $data['truncationStrategies'],
            topPriorities: $data['topPriorities'],
        );
    }

    /**
     * Get default indicator set.
     */
    protected function getDefaultIndicators(): array
    {
        $indicators = [];
        $names = ['url', 'title_tag', 'meta_description', 'h1_headings', 'robots_txt',
                  'sitemap_xml', 'image_alt', 'links', 'canonical', 'viewport',
                  'semantic_html', 'https', 'language', 'charset', 'social_meta',
                  'structured_data', 'internal_links'];

        foreach ($names as $name) {
            $indicators[] = [
                'id' => $name,
                'name' => ucwords(str_replace('_', ' ', $name)),
                'score' => 2,
                'passed' => true,
                'details' => [],
                'suggestions' => [],
            ];
        }

        return $indicators;
    }

    /**
     * Load fixture file content.
     */
    protected function loadFixture(string $filename): string
    {
        $path = $this->fixturesPath . '/' . $filename;
        return file_exists($path) ? file_get_contents($path) : '';
    }

    /**
     * Set up mock AI service for load testing.
     */
    protected function setupMockAIService(): void
    {
        $mock = Mockery::mock(AIAnalysisServiceInterface::class);
        $mock->shouldReceive('analyze')->andReturn($this->createMockResult());
        $this->app->instance(AIAnalysisServiceInterface::class, $mock);
    }

    // =========================================================================
    // LOAD TESTS
    // =========================================================================

    /**
     * LOAD-001: 10 concurrent analyses.
     */
    public function test_concurrent_analyses_10_users(): void
    {
        $concurrency = 10;
        $user = User::factory()->create();

        $this->setupMockAIService();

        $html = $this->loadFixture('small_page.html');
        $results = [];
        $errors = [];

        $startTime = microtime(true);

        // Simulate concurrent requests
        for ($i = 0; $i < $concurrency; $i++) {
            $service = app(\App\Services\AnalysisRunnerService::class);

            try {
                $result = $service->run("https://load-test-{$i}.com", $html);
                $results[] = $result->status;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $elapsed = microtime(true) - $startTime;

        // All requests should succeed
        $this->assertCount($concurrency, $results, 'Not all requests completed');
        $this->assertEmpty($errors, 'Errors occurred: ' . implode(', ', $errors));

        foreach ($results as $status) {
            $this->assertEquals('completed', $status);
        }

        echo "\n[LOAD-001] 10 concurrent analyses: {$elapsed}s\n";

        // Should complete within reasonable time (30 seconds for 10 requests)
        $this->assertLessThan(30, $elapsed, 'Load test took too long');
    }

    /**
     * LOAD-002: Rate limiting enforced.
     */
    public function test_rate_limiting_enforced(): void
    {
        $user = User::factory()->create();

        // Mock rate limit response
        $mock = Mockery::mock(AIAnalysisServiceInterface::class);
        $callCount = 0;

        $mock->shouldReceive('analyze')
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                if ($callCount > 5) {
                    throw new \App\Exceptions\AIAnalysisException('Rate limit exceeded');
                }
                return $this->createMockResult();
            });

        $this->app->instance(AIAnalysisServiceInterface::class, $mock);

        $html = $this->loadFixture('small_page.html');
        $successCount = 0;
        $rateLimitedCount = 0;

        // Attempt 10 analyses
        for ($i = 0; $i < 10; $i++) {
            $service = app(\App\Services\AnalysisRunnerService::class);

            try {
                $result = $service->run("https://rate-test-{$i}.com", $html);
                if ($result->status === 'completed') {
                    $successCount++;
                }
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'Rate limit')) {
                    $rateLimitedCount++;
                }
            }
        }

        echo "\n[LOAD-002] Rate limiting: {$successCount} success, {$rateLimitedCount} rate-limited\n";

        // Some requests should succeed, some should be rate limited
        $this->assertGreaterThan(0, $successCount);
        $this->assertGreaterThan(0, $rateLimitedCount);
    }

    /**
     * LOAD-003: Sustained load (50 requests over time).
     */
    public function test_sustained_load(): void
    {
        $requestCount = 50;
        $user = User::factory()->create();

        $this->setupMockAIService();

        $html = $this->loadFixture('small_page.html');
        $completedCount = 0;
        $failedCount = 0;

        $startTime = microtime(true);

        for ($i = 0; $i < $requestCount; $i++) {
            $service = app(\App\Services\AnalysisRunnerService::class);

            $result = $service->run("https://sustained-{$i}.com", $html);

            if ($result->status === 'completed') {
                $completedCount++;
            } else {
                $failedCount++;
            }
        }

        $elapsed = microtime(true) - $startTime;
        $requestsPerSecond = round($requestCount / $elapsed, 2);

        echo "\n[LOAD-003] Sustained load: {$requestCount} requests in {$elapsed}s ({$requestsPerSecond} req/s)\n";
        echo "  Completed: {$completedCount}, Failed: {$failedCount}\n";

        // Most requests should succeed
        $this->assertGreaterThan($requestCount * 0.9, $completedCount, 'Too many failures');
    }

    /**
     * LOAD-004: Burst traffic handling.
     */
    public function test_burst_traffic_handling(): void
    {
        $burstSize = 20;
        $user = User::factory()->create();

        $this->setupMockAIService();

        $html = $this->loadFixture('small_page.html');
        $results = [];

        $startTime = microtime(true);

        // Simulate burst (all requests at once)
        for ($i = 0; $i < $burstSize; $i++) {
            $service = app(\App\Services\AnalysisRunnerService::class);
            $results[] = $service->run("https://burst-{$i}.com", $html);
        }

        $elapsed = microtime(true) - $startTime;

        $successCount = collect($results)->where('status', 'completed')->count();

        echo "\n[LOAD-004] Burst traffic: {$burstSize} requests in {$elapsed}s\n";
        echo "  Success: {$successCount}/{$burstSize}\n";

        // Should handle burst without complete failure
        $this->assertGreaterThan($burstSize * 0.8, $successCount, 'Too many burst failures');
    }

    /**
     * LOAD-005: Memory usage under load.
     */
    public function test_memory_usage_under_load(): void
    {
        $requestCount = 25;

        $this->setupMockAIService();

        $html = $this->loadFixture('medium_page.html');

        $beforeMemory = memory_get_peak_usage(true);
        $beforeRealMemory = memory_get_usage(true);

        for ($i = 0; $i < $requestCount; $i++) {
            $service = app(\App\Services\AnalysisRunnerService::class);
            $service->run("https://memory-test-{$i}.com", $html);

            // Force garbage collection periodically
            if ($i % 10 === 0) {
                gc_collect_cycles();
            }
        }

        $afterMemory = memory_get_peak_usage(true);
        $afterRealMemory = memory_get_usage(true);

        $peakIncrease = ($afterMemory - $beforeMemory) / 1024 / 1024;
        $realIncrease = ($afterRealMemory - $beforeRealMemory) / 1024 / 1024;

        echo "\n[LOAD-005] Memory under load ({$requestCount} requests):\n";
        echo "  Peak increase: {$peakIncrease}MB\n";
        echo "  Real increase: {$realIncrease}MB\n";

        // Memory should not grow unbounded (indicates memory leak)
        // Allow up to 50MB growth for 25 requests
        $this->assertLessThan(50, $peakIncrease, 'Memory leak detected');
        $this->assertLessThan(30, $realIncrease, 'Real memory leak detected');
    }

    // =========================================================================
    // STRESS TESTS
    // =========================================================================

    /**
     * Test system behavior under extreme load.
     */
    public function test_extreme_load_handling(): void
    {
        $this->markTestSkipped('Extreme load test - run manually for capacity planning');

        $requestCount = 100;

        $this->setupMockAIService();

        $html = $this->loadFixture('small_page.html');
        $completedCount = 0;

        $startTime = microtime(true);

        for ($i = 0; $i < $requestCount; $i++) {
            $service = app(\App\Services\AnalysisRunnerService::class);
            $result = $service->run("https://extreme-{$i}.com", $html);

            if ($result->status === 'completed') {
                $completedCount++;
            }
        }

        $elapsed = microtime(true) - $startTime;

        echo "\n[STRESS] Extreme load: {$requestCount} requests in {$elapsed}s\n";
        echo "  Success rate: " . round($completedCount / $requestCount * 100, 1) . "%\n";

        // System should remain stable (at least 50% success under extreme load)
        $this->assertGreaterThan($requestCount * 0.5, $completedCount);
    }

    /**
     * Test timeout handling under load.
     */
    public function test_timeout_handling_under_load(): void
    {
        $user = User::factory()->create();

        // Mock slow responses
        $mock = Mockery::mock(AIAnalysisServiceInterface::class);
        $mock->shouldReceive('analyze')
            ->andReturnUsing(function () {
                usleep(100000); // 100ms delay
                return $this->createMockResult();
            });

        $this->app->instance(AIAnalysisServiceInterface::class, $mock);

        $html = $this->loadFixture('small_page.html');
        $results = [];

        for ($i = 0; $i < 5; $i++) {
            $service = app(\App\Services\AnalysisRunnerService::class);
            $results[] = $service->run("https://timeout-test-{$i}.com", $html);
        }

        // All should complete despite delays
        foreach ($results as $result) {
            $this->assertEquals('completed', $result->status);
        }
    }

    // =========================================================================
    // API ENDPOINT LOAD TESTS
    // =========================================================================

    /**
     * Test API endpoint under concurrent load.
     */
    public function test_api_endpoint_concurrent_load(): void
    {
        $user = User::factory()->create();
        $this->setupMockAIService();

        $html = $this->loadFixture('small_page.html');
        $concurrency = 5;
        $results = [];

        for ($i = 0; $i < $concurrency; $i++) {
            $response = $this->actingAs($user)
                ->postJson('/api/v1/analyze', [
                    'url' => "https://api-load-{$i}.com",
                    'html' => $html,
                ]);

            $results[] = $response->status();
        }

        // All API requests should return 200
        foreach ($results as $status) {
            $this->assertEquals(200, $status);
        }
    }

    /**
     * Test API response time under load.
     */
    public function test_api_response_time_under_load(): void
    {
        $user = User::factory()->create();
        $this->setupMockAIService();

        $html = $this->loadFixture('small_page.html');
        $responseTimes = [];

        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);

            $this->actingAs($user)
                ->postJson('/api/v1/analyze', [
                    'url' => "https://api-response-{$i}.com",
                    'html' => $html,
                ]);

            $responseTimes[] = (microtime(true) - $start) * 1000;
        }

        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);

        echo "\n[API Load] Response times:\n";
        echo "  Average: " . round($avgResponseTime, 2) . "ms\n";
        echo "  Max: " . round($maxResponseTime, 2) . "ms\n";

        // Average response time should be under 5 seconds
        $this->assertLessThan(5000, $avgResponseTime, 'Average API response time too high');
    }
}
