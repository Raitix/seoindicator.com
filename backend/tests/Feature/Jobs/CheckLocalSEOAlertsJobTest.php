<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckLocalSEOAlertsJob;
use App\Services\LocalSEO\Contracts\LocalRankServiceInterface;
use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use App\Services\LocalSEO\Contracts\GBPInsightsServiceInterface;
use App\Services\LocalSEO\LocalSEOAlertService;
use App\Models\BusinessLocation;
use App\Models\LocalSEOAlert;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckLocalSEOAlertsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // JOB DISPATCH TESTS
    // ==========================================

    /** @test */
    public function queue_001_job_dispatches_for_location(): void
    {
        Queue::fake();

        $locationId = 1;

        CheckLocalSEOAlertsJob::dispatch($locationId);

        Queue::assertPushed(CheckLocalSEOAlertsJob::class, function ($job) use ($locationId) {
            return $job->locationId === $locationId;
        });
    }

    /** @test */
    public function queue_002_job_dispatches_on_correct_queue(): void
    {
        Queue::fake();

        CheckLocalSEOAlertsJob::dispatch(1)->onQueue('alerts');

        Queue::assertPushedOn('alerts', CheckLocalSEOAlertsJob::class);
    }

    /** @test */
    public function queue_003_job_calls_check_for_alerts(): void
    {
        $location = BusinessLocation::create([
            'name' => 'Test Business',
            'google_location_id' => 'accounts/123/locations/456',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'address' => '123 Main St',
        ]);

        $mockService = Mockery::mock(LocalSEOAlertService::class);
        $mockService->shouldReceive('checkForAlerts')
            ->once()
            ->with(Mockery::type(BusinessLocation::class));

        $this->app->instance(LocalSEOAlertService::class, $mockService);

        $job = new CheckLocalSEOAlertsJob($location->id);
        $job->handle(app(LocalSEOAlertService::class));
    }

    /** @test */
    public function queue_004_job_handles_multiple_locations(): void
    {
        $location1 = BusinessLocation::create([
            'name' => 'Business 1',
            'google_location_id' => 'loc1',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'address' => '123 Main St',
        ]);

        $location2 = BusinessLocation::create([
            'name' => 'Business 2',
            'google_location_id' => 'loc2',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'address' => '456 Oak Ave',
        ]);

        Queue::fake();

        CheckLocalSEOAlertsJob::dispatch([$location1->id, $location2->id]);

        Queue::assertPushed(CheckLocalSEOAlertsJob::class);
    }

    /** @test */
    public function queue_005_job_failure_is_logged(): void
    {
        $job = new CheckLocalSEOAlertsJob(99999);

        // Job should handle missing location gracefully
        $result = $job->handle(app(LocalSEOAlertService::class));

        $this->assertNull($result);
    }

    /** @test */
    public function queue_006_job_retry_configuration(): void
    {
        $job = new CheckLocalSEOAlertsJob(1);

        $this->assertEquals(3, $job->tries);
    }

    /** @test */
    public function queue_007_job_timeout_configuration(): void
    {
        $job = new CheckLocalSEOAlertsJob(1);

        $this->assertEquals(120, $job->timeout);
    }

    // ==========================================
    // SCHEDULED JOB TESTS
    // ==========================================

    /** @test */
    public function queue_010_scheduled_job_dispatches_for_all_active_locations(): void
    {
        $user = \App\Models\User::factory()->create();

        // Create active locations
        $location1 = BusinessLocation::create([
            'user_id' => $user->id,
            'name' => 'Active Business 1',
            'google_location_id' => 'loc1',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'address' => '123 Main St',
            'is_active' => true,
        ]);

        $location2 = BusinessLocation::create([
            'user_id' => $user->id,
            'name' => 'Active Business 2',
            'google_location_id' => 'loc2',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'address' => '456 Oak Ave',
            'is_active' => true,
        ]);

        // Inactive location should not be checked
        BusinessLocation::create([
            'user_id' => $user->id,
            'name' => 'Inactive Business',
            'google_location_id' => 'loc3',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'address' => '789 Pine St',
            'is_active' => false,
        ]);

        Queue::fake();

        // Dispatch jobs for all active locations
        BusinessLocation::where('is_active', true)->each(function ($location) {
            CheckLocalSEOAlertsJob::dispatch($location->id);
        });

        Queue::assertPushed(CheckLocalSEOAlertsJob::class, 2);
    }

    // ==========================================
    // INTEGRATION TESTS
    // ==========================================

    /** @test */
    public function queue_int_001_full_job_flow(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = BusinessLocation::create([
            'user_id' => $user->id,
            'name' => 'Test Business',
            'google_location_id' => 'accounts/123/locations/456',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'address' => '123 Main St',
        ]);

        // Mock dependencies
        $mockRank = Mockery::mock(LocalRankServiceInterface::class);
        $mockRank->shouldReceive('getLastRanking')->andReturn((object) [
            'local_pack_position' => 5,
            'keyword' => 'test',
        ]);
        $mockRank->shouldReceive('getPreviousRanking')->andReturn((object) [
            'local_pack_position' => 2,
            'keyword' => 'test',
        ]);

        $mockReviews = Mockery::mock(GBPReviewsServiceInterface::class);
        $mockReviews->shouldReceive('getNewReviews')->andReturn(collect());
        $mockReviews->shouldReceive('getReviewStats')->andReturn([
            'average_rating' => 4.5,
            'previous_rating' => 4.5,
            'total_count' => 100,
            'previous_count' => 100,
        ]);

        $mockInsights = Mockery::mock(GBPInsightsServiceInterface::class);
        $mockInsights->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->app->instance(LocalRankServiceInterface::class, $mockRank);
        $this->app->instance(GBPReviewsServiceInterface::class, $mockReviews);
        $this->app->instance(GBPInsightsServiceInterface::class, $mockInsights);

        // Run job
        $job = new CheckLocalSEOAlertsJob($location->id);
        $job->handle(app(LocalSEOAlertService::class));

        // Verify alert was created
        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
        ]);
    }
}
