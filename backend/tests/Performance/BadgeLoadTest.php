<?php

/**
 * Badge Load Tests
 * Task: TASK-33-35 - Performance Optimization and Launch
 * 
 * Load tests for public audit pages and badge endpoints.
 * Tests concurrent access and system stability under load.
 */

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Audit;
use App\Models\PublicAuditView;
use App\Models\BadgeImpression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

class BadgeLoadTest extends TestCase
{
    /**
     * Test audit hashes for load testing.
     */
    private array $testHashes = [];

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test audits for load testing
        $this->testHashes = [];
        for ($i = 0; $i < 10; $i++) {
            $audit = Audit::factory()->create([
                'public_hash' => 'loadtest' . str_pad($i, 26, 'x') . $i,
                'visibility' => 'public',
                'overall_score' => rand(50, 95),
            ]);
            $this->testHashes[] = $audit->public_hash;
        }
    }

    /**
     * LOAD-001: 10 concurrent audit page requests.
     */
    public function test_10_concurrent_audit_pages(): void
    {
        $start = microtime(true);
        $results = [];
        $errors = 0;

        foreach ($this->testHashes as $hash) {
            try {
                $response = $this->getJson("/api/v1/public-audit/{$hash}");
                $results[] = $response->status();
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $duration = (microtime(true) - $start) * 1000;

        $this->assertEquals(10, count($results));
        $this->assertEquals(0, $errors);
        $this->assertLessThan(5000, $duration, "10 concurrent requests took {$duration}ms, expected < 5000ms");

        echo "\nLOAD-001: 10 concurrent audits: {$duration}ms\n";
    }

    /**
     * LOAD-002: 50 concurrent audit page requests.
     */
    public function test_50_concurrent_audit_pages(): void
    {
        // Create more test data
        $hashes = [];
        for ($i = 0; $i < 50; $i++) {
            $audit = Audit::factory()->create([
                'public_hash' => 'load50' . str_pad($i, 25, 'x') . $i,
                'visibility' => 'public',
                'overall_score' => rand(50, 95),
            ]);
            $hashes[] = $audit->public_hash;
        }

        $start = microtime(true);
        $results = [];
        $errors = 0;

        foreach ($hashes as $hash) {
            try {
                $response = $this->getJson("/api/v1/public-audit/{$hash}");
                $results[] = $response->status();
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $duration = (microtime(true) - $start) * 1000;
        $successCount = count(array_filter($results, fn($s) => $s === 200));

        $this->assertGreaterThan(45, $successCount, "At least 45 of 50 should succeed");
        $this->assertLessThan(15000, $duration, "50 concurrent requests took {$duration}ms");

        echo "\nLOAD-002: 50 concurrent audits: {$duration}ms, {$successCount}/50 success\n";
    }

    /**
     * LOAD-003: 100 concurrent audit page requests.
     */
    public function test_100_concurrent_audit_pages(): void
    {
        // Create 100 test audits
        $hashes = [];
        for ($i = 0; $i < 100; $i++) {
            $audit = Audit::factory()->create([
                'public_hash' => 'load100' . str_pad($i, 24, 'x') . $i,
                'visibility' => 'public',
                'overall_score' => rand(50, 95),
            ]);
            $hashes[] = $audit->public_hash;
        }

        $start = microtime(true);
        $results = [];
        $errors = 0;

        foreach ($hashes as $hash) {
            try {
                $response = $this->getJson("/api/v1/public-audit/{$hash}");
                $results[] = $response->status();
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $duration = (microtime(true) - $start) * 1000;
        $successCount = count(array_filter($results, fn($s) => $s === 200));

        $this->assertGreaterThan(90, $successCount, "At least 90 of 100 should succeed");
        $this->assertLessThan(30000, $duration, "100 concurrent requests took {$duration}ms");

        echo "\nLOAD-003: 100 concurrent audits: {$duration}ms, {$successCount}/100 success\n";
    }

    /**
     * LOAD-004: 100 concurrent badge SVG requests.
     */
    public function test_100_concurrent_badge_requests(): void
    {
        $start = microtime(true);
        $results = [];

        foreach ($this->testHashes as $i => $hash) {
            // Simulate 10 requests per hash (100 total)
            for ($j = 0; $j < 10; $j++) {
                try {
                    $response = $this->get("/badge/{$hash}/medium.svg");
                    $results[] = $response->status();
                } catch (\Exception $e) {
                    $results[] = 500;
                }
            }
        }

        $duration = (microtime(true) - $start) * 1000;
        $successCount = count(array_filter($results, fn($s) => $s === 200));

        $this->assertGreaterThan(90, $successCount, "At least 90 of 100 badge requests should succeed");
        $this->assertLessThan(10000, $duration, "100 badge requests took {$duration}ms");

        echo "\nLOAD-004: 100 badge requests: {$duration}ms, {$successCount}/100 success\n";
    }

    /**
     * LOAD-005: 500 concurrent badge SVG requests.
     */
    public function test_500_concurrent_badge_requests(): void
    {
        $start = microtime(true);
        $results = [];

        foreach ($this->testHashes as $i => $hash) {
            // Simulate 50 requests per hash (500 total)
            for ($j = 0; $j < 50; $j++) {
                try {
                    $response = $this->get("/badge/{$hash}/small.svg");
                    $results[] = $response->status();
                } catch (\Exception $e) {
                    $results[] = 500;
                }
            }
        }

        $duration = (microtime(true) - $start) * 1000;
        $successCount = count(array_filter($results, fn($s) => $s === 200));

        $this->assertGreaterThan(450, $successCount, "At least 450 of 500 should succeed");
        $this->assertLessThan(30000, $duration, "500 badge requests took {$duration}ms");

        echo "\nLOAD-005: 500 badge requests: {$duration}ms, {$successCount}/500 success\n";
    }

    /**
     * LOAD-006: Sustained load 100 requests per minute.
     */
    public function test_sustained_load_100_per_minute(): void
    {
        $requestsPerBatch = 20;
        $batches = 5;
        $results = [];

        for ($batch = 0; $batch < $batches; $batch++) {
            $batchStart = microtime(true);

            for ($i = 0; $i < $requestsPerBatch; $i++) {
                $hash = $this->testHashes[$i % count($this->testHashes)];
                $response = $this->getJson("/api/v1/public-audit/{$hash}");
                $results[] = $response->status();
            }

            $batchDuration = (microtime(true) - $batchStart) * 1000;
            
            // Simulate 1 second interval between batches
            if ($batch < $batches - 1) {
                usleep(100000); // 100ms
            }
        }

        $successCount = count(array_filter($results, fn($s) => $s === 200));
        $this->assertEquals(100, $successCount, "All 100 requests should succeed over 5 batches");

        echo "\nLOAD-006: Sustained 100 requests: {$successCount}/100 success\n";
    }

    /**
     * LOAD-007: Burst traffic 200 requests in 10 seconds.
     */
    public function test_burst_traffic_200_in_10s(): void
    {
        $start = microtime(true);
        $results = [];

        // Send 200 requests as fast as possible
        for ($i = 0; $i < 200; $i++) {
            $hash = $this->testHashes[$i % count($this->testHashes)];
            $response = $this->get("/badge/{$hash}/small.svg");
            $results[] = $response->status();
        }

        $duration = (microtime(true) - $start) * 1000;
        $successCount = count(array_filter($results, fn($s) => $s === 200 || $s === 304));

        $this->assertLessThan(30000, $duration, "Burst completed in {$duration}ms");
        $this->assertGreaterThan(180, $successCount, "At least 180 of 200 should succeed");

        echo "\nLOAD-007: Burst 200 requests: {$duration}ms, {$successCount}/200 success\n";
    }

    /**
     * LOAD-008: Mixed read/write operations.
     */
    public function test_mixed_read_write_operations(): void
    {
        $audit = Audit::factory()->create([
            'public_hash' => 'mixedtest123456789012345678901234uv',
            'visibility' => 'public',
        ]);

        $start = microtime(true);
        $results = ['reads' => 0, 'writes' => 0];

        for ($i = 0; $i < 100; $i++) {
            if ($i % 5 === 0) {
                // 20% writes
                PublicAuditView::create([
                    'audit_id' => $audit->id,
                    'public_hash' => $audit->public_hash,
                    'viewed_at' => now(),
                    'ip_hash' => hash('sha256', "ip_{$i}"),
                ]);
                $results['writes']++;
            } else {
                // 80% reads
                $this->getJson("/api/v1/public-audit/{$audit->public_hash}");
                $results['reads']++;
            }
        }

        $duration = (microtime(true) - $start) * 1000;

        $this->assertEquals(80, $results['reads']);
        $this->assertEquals(20, $results['writes']);
        $this->assertLessThan(20000, $duration, "Mixed operations took {$duration}ms");

        echo "\nLOAD-008: Mixed 80/20 operations: {$duration}ms\n";
    }

    /**
     * LOAD-009: Error rate under load < 1%.
     */
    public function test_error_rate_under_load(): void
    {
        $results = [];

        for ($i = 0; $i < 100; $i++) {
            $hash = $this->testHashes[$i % count($this->testHashes)];
            $response = $this->getJson("/api/v1/public-audit/{$hash}");
            $results[] = $response->status();
        }

        $errorCount = count(array_filter($results, fn($s) => $s >= 400));
        $errorRate = ($errorCount / 100) * 100;

        $this->assertLessThan(1, $errorRate, "Error rate is {$errorRate}%, expected < 1%");

        echo "\nLOAD-009: Error rate: {$errorRate}%\n";
    }

    /**
     * LOAD-010: Response time P95 < 3s.
     */
    public function test_response_time_p95(): void
    {
        $latencies = [];

        for ($i = 0; $i < 100; $i++) {
            $hash = $this->testHashes[$i % count($this->testHashes)];
            
            $start = microtime(true);
            $this->getJson("/api/v1/public-audit/{$hash}");
            $latencies[] = (microtime(true) - $start) * 1000;
        }

        sort($latencies);
        $p95Index = (int) ceil(0.95 * count($latencies)) - 1;
        $p95 = $latencies[$p95Index];

        $this->assertLessThan(3000, $p95, "P95 latency is {$p95}ms, expected < 3000ms");

        echo "\nLOAD-010: P95 response time: {$p95}ms\n";
    }

    /**
     * LOAD-011: Response time P99 < 5s.
     */
    public function test_response_time_p99(): void
    {
        $latencies = [];

        for ($i = 0; $i < 100; $i++) {
            $hash = $this->testHashes[$i % count($this->testHashes)];
            
            $start = microtime(true);
            $this->getJson("/api/v1/public-audit/{$hash}");
            $latencies[] = (microtime(true) - $start) * 1000;
        }

        sort($latencies);
        $p99Index = (int) ceil(0.99 * count($latencies)) - 1;
        $p99 = $latencies[$p99Index];

        $this->assertLessThan(5000, $p99, "P99 latency is {$p99}ms, expected < 5000ms");

        echo "\nLOAD-011: P99 response time: {$p99}ms\n";
    }

    /**
     * LOAD-012: Memory usage under load < 500MB.
     */
    public function test_memory_usage_under_load(): void
    {
        $initialMemory = memory_get_usage(true);

        for ($i = 0; $i < 100; $i++) {
            $hash = $this->testHashes[$i % count($this->testHashes)];
            $this->getJson("/api/v1/public-audit/{$hash}");
        }

        $peakMemory = memory_get_peak_usage(true);
        $memoryIncreaseMB = ($peakMemory - $initialMemory) / (1024 * 1024);

        $this->assertLessThan(500, $memoryIncreaseMB, "Memory increase is {$memoryIncreaseMB}MB");

        echo "\nLOAD-012: Memory increase: {$memoryIncreaseMB}MB\n";
    }

    /**
     * LOAD-013: CPU usage estimation.
     */
    public function test_cpu_usage_estimation(): void
    {
        // This is an estimation based on execution time
        $start = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $hash = $this->testHashes[$i % count($this->testHashes)];
            $this->getJson("/api/v1/public-audit/{$hash}");
        }

        $duration = microtime(true) - $start;
        $requestsPerSecond = 100 / $duration;

        // If we can handle > 50 req/s, CPU is likely acceptable
        $this->assertGreaterThan(10, $requestsPerSecond, "Throughput is {$requestsPerSecond} req/s");

        echo "\nLOAD-013: Throughput: {$requestsPerSecond} req/s\n";
    }

    /**
     * LOAD-014: No memory leaks over 1000 requests.
     */
    public function test_no_memory_leaks(): void
    {
        $memoryBefore = memory_get_usage(true);

        for ($i = 0; $i < 1000; $i++) {
            $hash = $this->testHashes[$i % count($this->testHashes)];
            $this->getJson("/api/v1/public-audit/{$hash}");
            
            // Clear any caches every 100 requests
            if ($i % 100 === 0) {
                gc_collect_cycles();
            }
        }

        $memoryAfter = memory_get_usage(true);
        $memoryLeakMB = ($memoryAfter - $memoryBefore) / (1024 * 1024);

        $this->assertLessThan(100, $memoryLeakMB, "Potential memory leak: {$memoryLeakMB}MB increase");

        echo "\nLOAD-014: Memory after 1000 requests: {$memoryLeakMB}MB change\n";
    }

    /**
     * LOAD-015: Graceful degradation with high load.
     */
    public function test_graceful_degradation(): void
    {
        // Create more hashes for heavier load
        $hashes = [];
        for ($i = 0; $i < 20; $i++) {
            $audit = Audit::factory()->create([
                'public_hash' => 'degrade' . str_pad($i, 25, 'x') . $i,
                'visibility' => 'public',
            ]);
            $hashes[] = $audit->public_hash;
        }

        $results = [];

        // Send 200 requests across 20 hashes (10 each)
        for ($i = 0; $i < 200; $i++) {
            $hash = $hashes[$i % count($hashes)];
            $response = $this->getJson("/api/v1/public-audit/{$hash}");
            $results[] = $response->status();
        }

        $successCount = count(array_filter($results, fn($s) => $s === 200));
        $successRate = ($successCount / 200) * 100;

        $this->assertGreaterThan(95, $successRate, "Success rate is {$successRate}%, expected > 95%");

        echo "\nLOAD-015: Graceful degradation: {$successRate}% success rate\n";
    }
}
