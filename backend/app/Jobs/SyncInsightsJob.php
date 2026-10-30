<?php

namespace App\Jobs;

use App\Models\BusinessLocation;
use App\Services\GBP\Contracts\GBPInsightsServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for syncing GBP insights in the background.
 *
 * @see TASK-35-9
 * @see SPEC-035
 */
class SyncInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job may run.
     */
    public int $timeout = 300;

    /**
     * @param int $locationId
     * @param array{start?: string, end?: string}|null $dateRange
     */
    public function __construct(
        protected int $locationId,
        protected ?array $dateRange = null,
    ) {
    }

    public function handle(GBPInsightsServiceInterface $insightsService): void
    {
        $location = BusinessLocation::query()->find($this->locationId);

        if (!$location) {
            Log::warning('SyncInsightsJob: Location not found', [
                'location_id' => $this->locationId,
            ]);

            return;
        }

        Log::info('SyncInsightsJob: Starting insights sync', [
            'location_id' => $this->locationId,
            'date_range' => $this->dateRange,
        ]);

        $insightsService->syncInsights($location, $this->dateRange);

        Log::info('SyncInsightsJob: Insights sync completed', [
            'location_id' => $this->locationId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncInsightsJob: Job failed', [
            'location_id' => $this->locationId,
            'date_range' => $this->dateRange,
            'error' => $exception->getMessage(),
        ]);
    }

    public function getLocationId(): int
    {
        return $this->locationId;
    }

    /**
     * @return array{start?: string, end?: string}|null
     */
    public function getDateRange(): ?array
    {
        return $this->dateRange;
    }
}
