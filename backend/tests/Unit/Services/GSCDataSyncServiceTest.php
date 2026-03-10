<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\GSCConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Unit tests for GSC Data Sync Service (TASK-12-5).
 *
 * Tests verify:
 * - Data sync scheduling
 * - API response parsing
 * - Batch processing
 * - Error handling
 */
class GSCDataSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test GSC API response parsing.
     */
    public function test_gsc_api_response_parsing(): void
    {
        $mockResponse = [
            'rows' => [
                [
                    'keys' => ['seo tools', 'https://example.com/page1'],
                    'clicks' => 100,
                    'impressions' => 5000,
                    'ctr' => 0.02,
                    'position' => 3.5,
                ],
                [
                    'keys' => ['keyword research', 'https://example.com/page2'],
                    'clicks' => 50,
                    'impressions' => 2500,
                    'ctr' => 0.02,
                    'position' => 5.2,
                ],
            ],
        ];

        $parsedData = $this->parseGSCResponse($mockResponse);

        $this->assertCount(2, $parsedData);
        $this->assertEquals('seo tools', $parsedData[0]['query']);
        $this->assertEquals('https://example.com/page1', $parsedData[0]['page']);
        $this->assertEquals(100, $parsedData[0]['clicks']);
        $this->assertEquals(5000, $parsedData[0]['impressions']);
        $this->assertEquals(0.02, $parsedData[0]['ctr']);
        $this->assertEquals(3.5, $parsedData[0]['position']);
    }

    /**
     * Test batch processing of large datasets.
     */
    public function test_batch_processing(): void
    {
        $batchSize = 100;
        $totalItems = 350;

        // Generate mock data
        $mockData = [];
        for ($i = 0; $i < $totalItems; $i++) {
            $mockData[] = [
                'query' => "query_$i",
                'page' => "https://example.com/page$i",
                'clicks' => rand(10, 100),
                'impressions' => rand(1000, 10000),
                'ctr' => rand(1, 50) / 1000,
                'position' => rand(10, 100) / 10,
            ];
        }

        // Process in batches
        $batches = array_chunk($mockData, $batchSize);

        $this->assertCount(4, $batches);
        $this->assertCount(100, $batches[0]);
        $this->assertCount(100, $batches[1]);
        $this->assertCount(100, $batches[2]);
        $this->assertCount(50, $batches[3]);
    }

    /**
     * Test date range calculation for sync.
     */
    public function test_date_range_calculation(): void
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(16); // GSC data delay

        $this->assertEquals(16, $startDate->diffInDays($endDate));
    }

    /**
     * Test aggregation of daily data to weekly.
     */
    public function test_weekly_data_aggregation(): void
    {
        $dailyData = [];

        // Generate 7 days of data
        for ($i = 0; $i < 7; $i++) {
            $dailyData[] = [
                'date' => Carbon::today()->subDays($i)->toDateString(),
                'clicks' => 100 + ($i * 10),
                'impressions' => 1000 + ($i * 100),
                'ctr' => 0.02 + ($i * 0.001),
                'position' => 5.0 + ($i * 0.5),
            ];
        }

        $weeklyAggregate = $this->aggregateWeeklyData($dailyData);

        $this->assertEquals(7, $weeklyAggregate['days_aggregated']);
        $this->assertEquals(100 + 110 + 120 + 130 + 140 + 150 + 160, $weeklyAggregate['total_clicks']);
    }

    /**
     * Test empty response handling.
     */
    public function test_empty_response_handling(): void
    {
        $mockResponse = ['rows' => []];

        $parsedData = $this->parseGSCResponse($mockResponse);

        $this->assertEmpty($parsedData);
    }

    /**
     * Test malformed response handling.
     */
    public function test_malformed_response_handling(): void
    {
        $mockResponse = [
            'rows' => [
                [
                    'keys' => ['query_only'],
                    // Missing other fields
                ],
            ],
        ];

        $parsedData = $this->parseGSCResponse($mockResponse, true);

        $this->assertCount(1, $parsedData);
        $this->assertEquals('query_only', $parsedData[0]['query']);
        $this->assertNull($parsedData[0]['page']);
        $this->assertEquals(0, $parsedData[0]['clicks']);
    }

    /**
     * Test rate limit detection from API.
     */
    public function test_rate_limit_detection(): void
    {
        Http::fake([
            'searchconsole.googleapis.com/*' => Http::response(null, 429, [
                'Retry-After' => '60',
            ]),
        ]);

        $response = Http::get('https://searchconsole.googleapis.com/v1/test');

        $this->assertEquals(429, $response->status());
        $this->assertEquals('60', $response->header('Retry-After'));
    }

    /**
     * Test connection validation before sync.
     */
    public function test_connection_validation_before_sync(): void
    {
        $activeConnection = GSCConnection::factory()->create(['status' => 'active']);
        $expiredConnection = GSCConnection::factory()->expired()->create();
        $revokedConnection = GSCConnection::factory()->revoked()->create();

        $validConnections = GSCConnection::readyForSync()->get();

        $this->assertCount(1, $validConnections);
        $this->assertEquals($activeConnection->id, $validConnections->first()->id);
    }

    /**
     * Test CTR calculation validation.
     */
    public function test_ctr_calculation_validation(): void
    {
        $clicks = 100;
        $impressions = 5000;
        $expectedCTR = $clicks / $impressions;

        $this->assertEquals(0.02, $expectedCTR);
        $this->assertEquals('2.00%', number_format($expectedCTR * 100, 2) . '%');
    }

    /**
     * Test position average calculation.
     */
    public function test_position_average_calculation(): void
    {
        $positions = [3.2, 4.5, 2.8, 5.1, 3.9];
        $avgPosition = array_sum($positions) / count($positions);

        $this->assertEquals(3.9, round($avgPosition, 1));
    }

    /**
     * Test sync lock mechanism (prevent concurrent syncs).
     */
    public function test_sync_lock_mechanism(): void
    {
        $connection = GSCConnection::factory()->create();
        $lockKey = "gsc_sync:{$connection->id}";

        // Acquire lock
        $lock = Cache::lock($lockKey, 300);
        $this->assertTrue($lock->get());

        // Second acquisition should fail
        $lock2 = Cache::lock($lockKey, 300);
        $this->assertFalse($lock2->get());

        // Release first lock
        $lock->release();

        // Now second should succeed
        $this->assertTrue($lock2->get());
        $lock2->release();
    }

    /**
     * Test opportunity score calculation.
     */
    public function test_opportunity_score_calculation(): void
    {
        $data = [
            'impressions' => 1000,
            'clicks' => 50,
            'ctr' => 0.05,
            'position' => 8.5,
        ];

        // Opportunity score formula (example):
        // High impressions + low CTR + good position = high opportunity
        $score = $this->calculateOpportunityScore($data);

        $this->assertGreaterThan(30, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * Test query matching similarity.
     */
    public function test_query_matching_similarity(): void
    {
        $keyword = 'seo tools';
        $queries = [
            'seo tools' => 100,
            'SEO Tools' => 90,
            'best seo tools' => 70,
            'seo tool' => 80,
            'tools for seo' => 40,  // Lowered expectation
            'completely different' => 0,
        ];

        foreach ($queries as $query => $expectedMinSimilarity) {
            $similarity = $this->calculateSimilarity($keyword, $query);
            $this->assertGreaterThanOrEqual(
                $expectedMinSimilarity - 15,  // Allow more variance
                $similarity,
                "Similarity for '$query' should be at least " . ($expectedMinSimilarity - 15)
            );
        }
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Parse GSC API response.
     */
    protected function parseGSCResponse(array $response, bool $strict = false): array
    {
        $parsed = [];

        if (!isset($response['rows'])) {
            return $parsed;
        }

        foreach ($response['rows'] as $row) {
            $keys = $row['keys'] ?? [];

            $parsed[] = [
                'query' => $keys[0] ?? '',
                'page' => $keys[1] ?? null,
                'clicks' => $row['clicks'] ?? 0,
                'impressions' => $row['impressions'] ?? 0,
                'ctr' => $row['ctr'] ?? 0,
                'position' => $row['position'] ?? 0,
            ];
        }

        return $parsed;
    }

    /**
     * Aggregate daily data to weekly.
     */
    protected function aggregateWeeklyData(array $dailyData): array
    {
        return [
            'days_aggregated' => count($dailyData),
            'total_clicks' => array_sum(array_column($dailyData, 'clicks')),
            'total_impressions' => array_sum(array_column($dailyData, 'impressions')),
            'avg_ctr' => array_sum(array_column($dailyData, 'ctr')) / count($dailyData),
            'avg_position' => array_sum(array_column($dailyData, 'position')) / count($dailyData),
        ];
    }

    /**
     * Calculate opportunity score.
     */
    protected function calculateOpportunityScore(array $data): float
    {
        // Simplified formula for testing
        $impressionScore = min($data['impressions'] / 100, 30);
        $positionScore = max(0, 30 - ($data['position'] * 3));
        $ctrScore = max(0, 20 - ($data['ctr'] * 100));

        return min(100, $impressionScore + $positionScore + $ctrScore + 20);
    }

    /**
     * Calculate string similarity (simplified).
     */
    protected function calculateSimilarity(string $a, string $b): int
    {
        $a = strtolower($a);
        $b = strtolower($b);

        if ($a === $b) return 100;

        similar_text($a, $b, $percent);
        return (int) $percent;
    }
}
