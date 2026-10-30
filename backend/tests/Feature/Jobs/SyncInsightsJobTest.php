<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SyncInsightsJob;
use App\Models\BusinessLocation;
use App\Services\GBP\Contracts\GBPInsightsServiceInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for the background GBP insights sync job.
 *
 * @see TASK-35-9
 * @see SPEC-035
 */
class SyncInsightsJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('business_locations');
        Schema::create('business_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('gbp_place_id')->nullable();
            $table->text('gbp_access_token')->nullable();
            $table->boolean('gbp_connected')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

    #[Test] // JOB-INS-001
    public function job_dispatches_for_a_location_and_preserves_optional_date_range(): void
    {
        Queue::fake();

        $location = $this->createLocation();
        $dateRange = ['start' => '2026-03-01', 'end' => '2026-03-07'];

        SyncInsightsJob::dispatch($location->id, $dateRange);

        Queue::assertPushed(SyncInsightsJob::class, function (SyncInsightsJob $job) use ($location, $dateRange) {
            return $job->getLocationId() === $location->id
                && $job->getDateRange() === $dateRange;
        });
    }

    #[Test] // JOB-INS-002
    public function job_handle_resolves_location_and_calls_domain_service_with_model_and_date_range(): void
    {
        $location = $this->createLocation();
        $dateRange = ['start' => '2026-03-01', 'end' => '2026-03-07'];

        $mockService = Mockery::mock(GBPInsightsServiceInterface::class);
        $mockService->shouldReceive('syncInsights')
            ->once()
            ->with(Mockery::type(BusinessLocation::class), $dateRange);

        $job = new SyncInsightsJob($location->id, $dateRange);
        $job->handle($mockService);

        $this->assertTrue(true);
    }

    #[Test] // JOB-INS-003
    public function missing_location_is_logged_and_job_exits_gracefully(): void
    {
        Log::spy();
        $mockService = Mockery::mock(GBPInsightsServiceInterface::class);
        $mockService->shouldNotReceive('syncInsights');

        $job = new SyncInsightsJob(999999, ['start' => '2026-03-01', 'end' => '2026-03-07']);
        $job->handle($mockService);

        Log::shouldHaveReceived('warning')->once();
        $this->assertTrue(true);
    }

    #[Test] // JOB-INS-004
    public function job_uses_expected_retry_and_timeout_defaults(): void
    {
        $job = new SyncInsightsJob(1);

        $this->assertSame(3, $job->tries);
        $this->assertSame(300, $job->timeout);
    }

    #[Test] // JOB-INS-005
    public function job_failure_handler_logs_context(): void
    {
        Log::spy();

        $job = new SyncInsightsJob(42, ['start' => '2026-03-01', 'end' => '2026-03-07']);
        $job->failed(new \RuntimeException('boom'));

        Log::shouldHaveReceived('error')->once();
        $this->assertTrue(true);
    }
}

