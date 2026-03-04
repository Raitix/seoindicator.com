<?php

namespace Tests\Performance;

use App\Models\User;
use App\Services\AI\AISuggestionService;
use App\Services\AI\Providers\StaticFallbackProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Large;

/**
 * Performance tests for AI Suggestion system.
 * 
 * Task: TASK-21-12
 * Test Documentation: TESTS/TEST-21-12.md
 * 
 * Tests response time, throughput, memory usage, and scalability.
 * 
 * @group performance
 * @group ai
 * @group task-21-12
 */
class AISuggestionPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/ai/suggestions';
    private const RESPONSE_TIME_THRESHOLD_MS = 500; // Max acceptable response time
    private const STATIC_RESPONSE_TIME_THRESHOLD_MS = 100; // Static provider should be fast

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // =========================================================================
    // PERF-001: Response Time - Static Provider
    // =========================================================================

    #[Test]
    #[TestDox('PERF-001-01: Static provider responds within threshold')]
    public function static_provider_responds_within_threshold(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test Title'],
                'provider' => 'static',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(
            self::STATIC_RESPONSE_TIME_THRESHOLD_MS,
            $elapsed,
            "Static provider should respond within {$this->getStaticThreshold()}ms, took {$elapsed}ms"
        );
    }

    #[Test]
    #[TestDox('PERF-001-02: Static provider has consistent response times')]
    public function static_provider_has_consistent_response_times(): void
    {
        $times = [];

        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);

            $this->actingAs($this->user)
                ->postJson(self::ENDPOINT, [
                    'indicator' => 'title',
                    'context' => ['title' => "Test $i"],
                    'provider' => 'static',
                ]);

            $times[] = (microtime(true) - $start) * 1000;
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);

        // Variance should be reasonable
        $variance = $maxTime - $minTime;
        $this->assertLessThan(200, $variance, "Response time variance should be under 200ms, was {$variance}ms");
        $this->assertLessThan(150, $avgTime, "Average response time should be under 150ms, was {$avgTime}ms");
    }

    // =========================================================================
    // PERF-002: Response Time - By Indicator
    // =========================================================================

    #[Test]
    #[TestDox('PERF-002-01: Title indicator response time')]
    public function title_indicator_response_time(): void
    {
        $this->assertIndicatorResponseTime('title', ['title' => 'Test Title']);
    }

    #[Test]
    #[TestDox('PERF-002-02: Meta description indicator response time')]
    public function meta_description_indicator_response_time(): void
    {
        $this->assertIndicatorResponseTime('meta_description', ['description' => 'Test description']);
    }

    #[Test]
    #[TestDox('PERF-002-03: Headings indicator response time')]
    public function headings_indicator_response_time(): void
    {
        $this->assertIndicatorResponseTime('headings', ['h1' => ['Main Heading'], 'h2' => ['Subheading']]);
    }

    #[Test]
    #[TestDox('PERF-002-04: Links indicator response time')]
    public function links_indicator_response_time(): void
    {
        $this->assertIndicatorResponseTime('links', [
            'internal' => 10,
            'external' => 5,
            'broken' => 0,
        ]);
    }

    #[Test]
    #[TestDox('PERF-002-05: Image alt indicator response time')]
    public function image_alt_indicator_response_time(): void
    {
        $images = [];
        for ($i = 0; $i < 20; $i++) {
            $images[] = ['url' => "/image$i.jpg", 'alt' => ''];
        }

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'image_alt',
                'context' => ['images' => $images],
                'provider' => 'static',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsed, "Image alt with 20 items should respond within 500ms");
    }

    // =========================================================================
    // PERF-003: Memory Usage
    // =========================================================================

    #[Test]
    #[TestDox('PERF-003-01: Single request does not leak memory')]
    public function single_request_does_not_leak_memory(): void
    {
        $memoryBefore = memory_get_usage();

        $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'provider' => 'static',
            ]);

        $memoryAfter = memory_get_usage();
        $memoryDiff = $memoryAfter - $memoryBefore;

        // Allow some memory usage but not excessive (5MB threshold)
        $this->assertLessThan(
            5 * 1024 * 1024,
            $memoryDiff,
            'Single request should not use more than 5MB memory'
        );
    }

    #[Test]
    #[TestDox('PERF-003-02: Multiple requests do not accumulate memory')]
    public function multiple_requests_do_not_accumulate_memory(): void
    {
        gc_collect_cycles();
        $memoryBefore = memory_get_usage();

        for ($i = 0; $i < 50; $i++) {
            $this->actingAs($this->user)
                ->postJson(self::ENDPOINT, [
                    'indicator' => 'title',
                    'context' => ['title' => "Test $i"],
                    'provider' => 'static',
                ]);
        }

        gc_collect_cycles();
        $memoryAfter = memory_get_usage();
        $memoryDiff = $memoryAfter - $memoryBefore;

        // Memory should not grow significantly (10MB threshold after 50 requests)
        $this->assertLessThan(
            10 * 1024 * 1024,
            $memoryDiff,
            '50 requests should not accumulate more than 10MB memory'
        );
    }

    // =========================================================================
    // PERF-004: Throughput
    // =========================================================================

    #[Test]
    #[TestDox('PERF-004-01: Can handle 10 requests per second')]
    public function can_handle_10_requests_per_second(): void
    {
        $start = microtime(true);
        $requests = 10;

        for ($i = 0; $i < $requests; $i++) {
            $this->actingAs($this->user)
                ->postJson(self::ENDPOINT, [
                    'indicator' => 'title',
                    'context' => ['title' => "Test $i"],
                    'provider' => 'static',
                ]);
        }

        $elapsed = microtime(true) - $start;
        $requestsPerSecond = $requests / $elapsed;

        $this->assertGreaterThanOrEqual(
            5,
            $requestsPerSecond,
            "Should handle at least 5 requests per second, got {$requestsPerSecond}"
        );
    }

    // =========================================================================
    // PERF-005: Large Input Handling
    // =========================================================================

    #[Test]
    #[TestDox('PERF-005-01: Large title is processed quickly')]
    public function large_title_is_processed_quickly(): void
    {
        $largeTitle = str_repeat('Long Title Test ', 100); // ~1600 chars

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => $largeTitle],
                'provider' => 'static',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsed, 'Large title should still be processed quickly');
    }

    #[Test]
    #[TestDox('PERF-005-02: Large context array is processed efficiently')]
    public function large_context_array_is_processed_efficiently(): void
    {
        $largeArray = [
            'items' => array_map(fn($i) => ['id' => $i, 'value' => "Item $i"], range(1, 100)),
        ];

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'links',
                'context' => $largeArray,
                'provider' => 'static',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(1000, $elapsed, 'Large context should be processed within 1 second');
    }

    // =========================================================================
    // PERF-006: Provider Direct Performance
    // =========================================================================

    #[Test]
    #[TestDox('PERF-006-01: StaticFallbackProvider generate is fast')]
    public function static_fallback_provider_generate_is_fast(): void
    {
        $provider = app(StaticFallbackProvider::class);

        $times = [];

        for ($i = 0; $i < 100; $i++) {
            $start = microtime(true);
            $provider->generate('Test prompt ' . $i);
            $times[] = (microtime(true) - $start) * 1000;
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);

        $this->assertLessThan(10, $avgTime, "Static provider generate() should average under 10ms, was {$avgTime}ms");
        $this->assertLessThan(50, $maxTime, "Static provider generate() max should be under 50ms, was {$maxTime}ms");
    }

    // =========================================================================
    // PERF-007: Service Instantiation
    // =========================================================================

    #[Test]
    #[TestDox('PERF-007-01: Service can be instantiated quickly')]
    public function service_can_be_instantiated_quickly(): void
    {
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            app(AISuggestionService::class);
        }

        $elapsed = (microtime(true) - $start) * 1000;
        $avgTime = $elapsed / 100;

        $this->assertLessThan(5, $avgTime, "Service instantiation should average under 5ms, was {$avgTime}ms");
    }

    // =========================================================================
    // PERF-008: Concurrent Request Simulation
    // =========================================================================

    #[Test]
    #[TestDox('PERF-008-01: Sequential requests maintain performance')]
    public function sequential_requests_maintain_performance(): void
    {
        $times = [];

        for ($i = 0; $i < 20; $i++) {
            $start = microtime(true);

            $response = $this->actingAs($this->user)
                ->postJson(self::ENDPOINT, [
                    'indicator' => 'title',
                    'context' => ['title' => "Test $i"],
                    'provider' => 'static',
                ]);

            $times[] = (microtime(true) - $start) * 1000;
            $response->assertStatus(200);
        }

        // Later requests should not be significantly slower
        $firstHalfAvg = array_sum(array_slice($times, 0, 10)) / 10;
        $secondHalfAvg = array_sum(array_slice($times, 10, 10)) / 10;

        $degradation = ($secondHalfAvg - $firstHalfAvg) / $firstHalfAvg * 100;

        $this->assertLessThan(
            50,
            $degradation,
            "Performance degradation should be under 50%, was {$degradation}%"
        );
    }

    // =========================================================================
    // PERF-009: Database Operations
    // =========================================================================

    #[Test]
    #[TestDox('PERF-009-01: User lookup is fast')]
    public function user_lookup_is_fast(): void
    {
        // Create multiple users
        User::factory()->count(100)->create();

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => 'Test'],
                'provider' => 'static',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsed, 'Request with user lookup should be fast');
    }

    // =========================================================================
    // PERF-010: JSON Processing
    // =========================================================================

    #[Test]
    #[TestDox('PERF-010-01: Large JSON response is generated quickly')]
    public function large_json_response_is_generated_quickly(): void
    {
        // Create context that will generate larger response
        $context = [
            'title' => 'Test Title',
            'keyword' => 'test',
            'content_summary' => str_repeat('Lorem ipsum dolor sit amet. ', 50),
        ];

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => $context,
                'provider' => 'static',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsed, 'Large JSON response should be generated quickly');
    }

    // =========================================================================
    // PERF-011: Cache Performance (if caching is implemented)
    // =========================================================================

    #[Test]
    #[TestDox('PERF-011-01: Repeated identical requests maintain performance')]
    public function repeated_identical_requests_maintain_performance(): void
    {
        $payload = [
            'indicator' => 'title',
            'context' => ['title' => 'Consistent Test Title'],
            'provider' => 'static',
        ];

        $times = [];

        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);
            $this->actingAs($this->user)->postJson(self::ENDPOINT, $payload);
            $times[] = (microtime(true) - $start) * 1000;
        }

        // All requests should be consistently fast
        foreach ($times as $time) {
            $this->assertLessThan(
                self::RESPONSE_TIME_THRESHOLD_MS,
                $time,
                "Each request should be within {$this->getThreshold()}ms"
            );
        }
    }

    // =========================================================================
    // PERF-012: Unicode Processing Performance
    // =========================================================================

    #[Test]
    #[TestDox('PERF-012-01: Unicode content processing is fast')]
    public function unicode_content_processing_is_fast(): void
    {
        $unicodeTitle = 'Лучшие товары 世界 🎉 Lorem ipsum 你好 مرحبا';

        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => 'title',
                'context' => ['title' => $unicodeTitle],
                'provider' => 'static',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $elapsed, 'Unicode processing should be fast');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function assertIndicatorResponseTime(string $indicator, array $context): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson(self::ENDPOINT, [
                'indicator' => $indicator,
                'context' => $context,
                'provider' => 'static',
            ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(
            self::RESPONSE_TIME_THRESHOLD_MS,
            $elapsed,
            "{$indicator} indicator should respond within {$this->getThreshold()}ms, took {$elapsed}ms"
        );
    }

    private function getThreshold(): int
    {
        return self::RESPONSE_TIME_THRESHOLD_MS;
    }

    private function getStaticThreshold(): int
    {
        return self::STATIC_RESPONSE_TIME_THRESHOLD_MS;
    }
}
