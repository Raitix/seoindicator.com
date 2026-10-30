<?php

namespace App\Jobs;

use App\Models\BusinessLocation;
use App\Services\LocalSEO\Contracts\LocalSEOAlertServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckLocalSEOAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * The location ID(s) to check.
     */
    protected int|array $locationId;

    /**
     * Create a new job instance.
     */
    public function __construct(int|array $locationId)
    {
        $this->locationId = $locationId;
        $this->onQueue('alerts');
    }

    /**
     * Get the location ID.
     */
    public function getLocationId(): int|array
    {
        return $this->locationId;
    }

    /**
     * Execute the job.
     */
    public function handle(LocalSEOAlertServiceInterface $alertService): void
    {
        $locationIds = is_array($this->locationId) ? $this->locationId : [$this->locationId];

        foreach ($locationIds as $id) {
            $location = BusinessLocation::with('user')->find($id);

            if (!$location) {
                Log::warning('CheckLocalSEOAlertsJob: Location not found', ['location_id' => $id]);
                continue;
            }

            if (!$location->is_active) {
                Log::debug('CheckLocalSEOAlertsJob: Location is inactive, skipping', ['location_id' => $id]);
                continue;
            }

            $alertService->checkForAlerts($location);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckLocalSEOAlertsJob failed', [
            'location_id' => $this->locationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
