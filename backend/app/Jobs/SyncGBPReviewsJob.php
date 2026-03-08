<?php

namespace App\Jobs;

use App\Services\GBP\Contracts\GBPReviewsServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for syncing GBP reviews in the background.
 *
 * @see TASK-35-10
 * @see SPEC-035
 */
class SyncGBPReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public int $timeout = 300;

    /**
     * The location ID to sync reviews for.
     *
     * @var int
     */
    protected int $locationId;

    /**
     * Create a new job instance.
     *
     * @param int $locationId
     */
    public function __construct(int $locationId)
    {
        $this->locationId = $locationId;
    }

    /**
     * Execute the job.
     *
     * @param GBPReviewsServiceInterface $reviewsService
     * @return void
     */
    public function handle(GBPReviewsServiceInterface $reviewsService): void
    {
        // Get the location model (using DB query to avoid model dependency)
        $location = \Illuminate\Support\Facades\DB::table('business_locations')
            ->where('id', $this->locationId)
            ->first();

        if (!$location) {
            Log::warning('SyncGBPReviewsJob: Location not found', [
                'location_id' => $this->locationId,
            ]);
            return;
        }

        // Create a simple object that mimics the expected interface
        $locationObject = new class($location) {
            public int $id;
            public ?string $google_location_id;
            public ?string $google_access_token;
            public ?string $name;

            public function __construct($location)
            {
                $this->id = $location->id;
                $this->google_location_id = $location->google_location_id ?? null;
                $this->google_access_token = $location->google_access_token ?? null;
                $this->name = $location->name ?? null;
            }
        };

        Log::info('SyncGBPReviewsJob: Starting review sync', [
            'location_id' => $this->locationId,
        ]);

        try {
            $reviewsService->syncReviews($locationObject);

            Log::info('SyncGBPReviewsJob: Review sync completed', [
                'location_id' => $this->locationId,
            ]);
        } catch (\Exception $e) {
            Log::error('SyncGBPReviewsJob: Review sync failed', [
                'location_id' => $this->locationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncGBPReviewsJob: Job failed', [
            'location_id' => $this->locationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
