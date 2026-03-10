<?php

namespace Tests\Feature\Services\GBP;

use App\Models\BusinessLocation;
use App\Services\GBP\Contracts\GBPApiServiceInterface;
use App\Services\GBP\Contracts\GBPInsightsServiceInterface;
use App\Services\GBP\GBPInsightsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Feature tests for GBPInsightsService.
 *
 * These tests intentionally define the business-location centric domain
 * contract for TASK-35-9. The existing LocalSEO adapter can keep its legacy
 * controller-facing shape and delegate into this service during the Developer
 * pass.
 *
 * @see TASK-35-9
 * @see SPEC-035
 */
class GBPInsightsServiceTest extends TestCase
{
    private GBPInsightsService $service;
    private MockInterface $mockApiService;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createTables();

        $this->mockApiService = Mockery::mock(GBPApiServiceInterface::class);
        $this->service = new GBPInsightsService($this->mockApiService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createTables(): void
    {
        Schema::dropIfExists('gbp_insights');
        Schema::dropIfExists('business_locations');

        Schema::create('business_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('gbp_place_id')->nullable();
            $table->text('gbp_access_token')->nullable();
            $table->text('gbp_refresh_token')->nullable();
            $table->timestamp('gbp_token_expires_at')->nullable();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('gbp_connected')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('settings')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('gbp_insights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('searches')->default(0);
            $table->unsignedInteger('calls')->default(0);
            $table->unsignedInteger('directions')->default(0);
            $table->unsignedInteger('website_clicks')->default(0);
            $table->unsignedInteger('photos_views')->default(0);
            $table->longText('raw_response')->nullable();
            $table->date('insights_date');
            $table->timestamps();

            $table->unique(['location_id', 'insights_date']);
            $table->index(['location_id', 'insights_date']);
        });
    }

    private function createLocation(array $attributes = []): BusinessLocation
    {
        return BusinessLocation::query()->create(array_merge([
            'name' => 'SEOIndicator Riga',
            'gbp_place_id' => 'accounts/123/locations/456',
            'gbp_access_token' => 'test-access-token',
            'gbp_connected' => true,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function seedInsights(int $locationId, array $rows): void
    {
        foreach ($rows as $row) {
            DB::table('gbp_insights')->insert(array_merge([
                'location_id' => $locationId,
                'views' => 0,
                'searches' => 0,
                'calls' => 0,
                'directions' => 0,
                'website_clicks' => 0,
                'photos_views' => 0,
                'raw_response' => json_encode($row, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ], $row));
        }
    }

    // ================================================================
    // Class & Container Contract
    // ================================================================

    #[Test] // GBP-INS-CLS-001
    public function service_class_exists(): void
    {
        $this->assertTrue(class_exists(GBPInsightsService::class));
    }

    #[Test] // GBP-INS-CLS-002
    public function service_implements_interface(): void
    {
        $this->assertInstanceOf(GBPInsightsServiceInterface::class, $this->service);
    }

    #[Test] // GBP-INS-CLS-003
    public function container_resolves_contract_binding(): void
    {
        $resolved = $this->app->make(GBPInsightsServiceInterface::class);

        $this->assertInstanceOf(GBPInsightsService::class, $resolved);
    }

    #[Test] // GBP-INS-CLS-004
    public function interface_exposes_all_required_methods(): void
    {
        foreach (['syncInsights', 'getInsights', 'getInsightsTrend', 'getTotalMetrics', 'getTopPerformingDay'] as $method) {
            $this->assertTrue(method_exists(GBPInsightsServiceInterface::class, $method), "Missing interface method: {$method}");
        }
    }

    // ================================================================
    // Sync contract
    // ================================================================

    #[Test] // GBP-INS-SYNC-001
    public function sync_insights_fetches_gbp_metrics_and_persists_daily_snapshots(): void
    {
        $location = $this->createLocation();

        $this->mockApiService
            ->shouldReceive('getInsights')
            ->once()
            ->with($location->gbp_place_id, $location->gbp_access_token, [
                'start' => '2026-03-01',
                'end' => '2026-03-02',
            ])
            ->andReturn([
                'daily_metrics' => [
                    [
                        'date' => '2026-03-01',
                        'views' => 120,
                        'searches' => 40,
                        'calls' => 3,
                        'directions' => 2,
                        'website_clicks' => 4,
                        'photos_views' => 10,
                    ],
                    [
                        'date' => '2026-03-02',
                        'views' => 160,
                        'searches' => 55,
                        'calls' => 5,
                        'directions' => 4,
                        'website_clicks' => 6,
                        'photos_views' => 18,
                    ],
                ],
            ]);

        $this->service->syncInsights($location, [
            'start' => '2026-03-01',
            'end' => '2026-03-02',
        ]);

        $this->assertDatabaseHas('gbp_insights', [
            'location_id' => $location->id,
            'insights_date' => '2026-03-01',
            'views' => 120,
            'searches' => 40,
            'calls' => 3,
            'directions' => 2,
            'website_clicks' => 4,
            'photos_views' => 10,
        ]);
    }

    #[Test] // GBP-INS-SYNC-002
    public function sync_insights_upserts_duplicate_dates_instead_of_inserting_duplicates(): void
    {
        $location = $this->createLocation();

        $this->seedInsights($location->id, [
            [
                'insights_date' => '2026-03-01',
                'views' => 99,
                'searches' => 30,
                'calls' => 1,
                'directions' => 1,
                'website_clicks' => 1,
                'photos_views' => 8,
            ],
        ]);

        $this->mockApiService
            ->shouldReceive('getInsights')
            ->once()
            ->andReturn([
                'daily_metrics' => [
                    [
                        'date' => '2026-03-01',
                        'views' => 150,
                        'searches' => 60,
                        'calls' => 5,
                        'directions' => 4,
                        'website_clicks' => 3,
                        'photos_views' => 12,
                    ],
                ],
            ]);

        $this->service->syncInsights($location, [
            'start' => '2026-03-01',
            'end' => '2026-03-01',
        ]);

        $this->assertSame(1, DB::table('gbp_insights')->where('location_id', $location->id)->count());
        $this->assertDatabaseHas('gbp_insights', [
            'location_id' => $location->id,
            'insights_date' => '2026-03-01',
            'views' => 150,
            'searches' => 60,
        ]);
    }

    // ================================================================
    // Read / aggregate contract
    // ================================================================

    #[Test] // GBP-INS-GET-001
    public function get_insights_returns_collection_ordered_by_date_with_derived_queries_and_actions(): void
    {
        $location = $this->createLocation();

        $this->seedInsights($location->id, [
            [
                'insights_date' => '2026-03-02',
                'views' => 160,
                'searches' => 55,
                'calls' => 5,
                'directions' => 4,
                'website_clicks' => 6,
                'photos_views' => 18,
            ],
            [
                'insights_date' => '2026-03-01',
                'views' => 120,
                'searches' => 40,
                'calls' => 3,
                'directions' => 2,
                'website_clicks' => 4,
                'photos_views' => 10,
            ],
        ]);

        $insights = $this->service->getInsights($location, [
            'start' => '2026-03-01',
            'end' => '2026-03-02',
        ]);

        $this->assertInstanceOf(Collection::class, $insights);
        $this->assertSame('2026-03-01', $insights->first()['date'] ?? null);
        $this->assertSame(40, $insights->first()['queries'] ?? null);
        $this->assertSame(9, $insights->first()['actions'] ?? null);
    }

    #[Test] // GBP-INS-AGG-001
    public function get_total_metrics_aggregates_queries_views_actions_and_photo_views(): void
    {
        $location = $this->createLocation();

        $this->seedInsights($location->id, [
            [
                'insights_date' => '2026-03-01',
                'views' => 120,
                'searches' => 40,
                'calls' => 3,
                'directions' => 2,
                'website_clicks' => 4,
                'photos_views' => 10,
            ],
            [
                'insights_date' => '2026-03-02',
                'views' => 160,
                'searches' => 55,
                'calls' => 5,
                'directions' => 4,
                'website_clicks' => 6,
                'photos_views' => 18,
            ],
        ]);

        $totals = $this->service->getTotalMetrics($location, [
            'start' => '2026-03-01',
            'end' => '2026-03-02',
        ]);

        $this->assertSame(95, $totals['queries'] ?? null);
        $this->assertSame(280, $totals['views'] ?? null);
        $this->assertSame(24, $totals['actions'] ?? null);
        $this->assertSame(28, $totals['photos_views'] ?? null);
    }

    #[Test] // GBP-INS-TRD-001
    public function get_insights_trend_returns_daily_breakdown_totals_and_wow_mom_changes(): void
    {
        $location = $this->createLocation();

        $rows = [];
        for ($day = 1; $day <= 60; $day++) {
            $rows[] = [
                'insights_date' => sprintf('2026-01-%02d', $day <= 31 ? $day : 31),
                'views' => 50 + $day,
                'searches' => 20 + $day,
                'calls' => 1 + ($day % 3),
                'directions' => 2 + ($day % 4),
                'website_clicks' => 3 + ($day % 5),
                'photos_views' => 5 + $day,
            ];
        }

        // Replace invalid overflowed dates with sequential February dates.
        foreach ($rows as $index => $row) {
            if ($index >= 31) {
                $rows[$index]['insights_date'] = sprintf('2026-02-%02d', $index - 30);
            }
        }

        $this->seedInsights($location->id, $rows);

        $trend = $this->service->getInsightsTrend($location, 30);

        $this->assertIsArray($trend);
        $this->assertArrayHasKey('daily_breakdown', $trend);
        $this->assertArrayHasKey('totals', $trend);
        $this->assertArrayHasKey('wow', $trend);
        $this->assertArrayHasKey('mom', $trend);
        $this->assertArrayHasKey('queries', $trend['wow']);
    }

    #[Test] // GBP-INS-TOP-001
    public function get_top_performing_day_returns_best_day_by_total_actions(): void
    {
        $location = $this->createLocation();

        $this->seedInsights($location->id, [
            [
                'insights_date' => '2026-03-01',
                'views' => 100,
                'searches' => 20,
                'calls' => 1,
                'directions' => 1,
                'website_clicks' => 2,
                'photos_views' => 5,
            ],
            [
                'insights_date' => '2026-03-02',
                'views' => 140,
                'searches' => 30,
                'calls' => 6,
                'directions' => 5,
                'website_clicks' => 7,
                'photos_views' => 12,
            ],
            [
                'insights_date' => '2026-03-03',
                'views' => 110,
                'searches' => 22,
                'calls' => 2,
                'directions' => 2,
                'website_clicks' => 3,
                'photos_views' => 6,
            ],
        ]);

        $day = $this->service->getTopPerformingDay($location, 30);

        $this->assertSame('2026-03-02', $day['date'] ?? null);
        $this->assertSame(18, $day['actions'] ?? null);
    }

    #[Test] // GBP-INS-CACHE-001
    public function get_insights_uses_one_hour_cache_for_identical_location_and_date_range(): void
    {
        $location = $this->createLocation();

        $this->seedInsights($location->id, [
            [
                'insights_date' => '2026-03-01',
                'views' => 120,
                'searches' => 40,
                'calls' => 3,
                'directions' => 2,
                'website_clicks' => 4,
                'photos_views' => 10,
            ],
        ]);

        $first = $this->service->getInsights($location, [
            'start' => '2026-03-01',
            'end' => '2026-03-01',
        ]);

        DB::table('gbp_insights')
            ->where('location_id', $location->id)
            ->where('insights_date', '2026-03-01')
            ->update([
                'views' => 999,
                'searches' => 999,
                'calls' => 9,
                'directions' => 9,
                'website_clicks' => 9,
                'photos_views' => 999,
                'raw_response' => json_encode(['date' => '2026-03-01'], JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        $second = $this->service->getInsights($location, [
            'start' => '2026-03-01',
            'end' => '2026-03-01',
        ]);

        $this->assertSame($first->toArray(), $second->toArray());
    }

    #[Test] // GBP-INS-ERR-001
    public function sync_insights_logs_and_rethrows_api_failures(): void
    {
        $location = $this->createLocation();

        Log::spy();

        $this->mockApiService
            ->shouldReceive('getInsights')
            ->once()
            ->andThrow(new \RuntimeException('GBP API unavailable'));

        try {
            $this->service->syncInsights($location, [
                'start' => '2026-03-01',
                'end' => '2026-03-02',
            ]);

            $this->fail('Expected syncInsights() to rethrow the API exception.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('GBP API unavailable', $exception->getMessage());
        }

        Log::shouldHaveReceived('error')->once();
    }
}

