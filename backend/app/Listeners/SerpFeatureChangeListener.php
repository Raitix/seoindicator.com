<?php

namespace App\Listeners;

use App\Services\SerpFeatureAlertService;
use Illuminate\Support\Facades\Log;

/**
 * Event listener for SERP feature changes.
 *
 * Listens for 'serp-feature.gained' and 'serp-feature.lost' events
 * and triggers alert generation via SerpFeatureAlertService.
 */
class SerpFeatureChangeListener
{
    protected SerpFeatureAlertService $alertService;

    /**
     * Create the event listener.
     */
    public function __construct(SerpFeatureAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Handle SERP feature gained events.
     *
     * @param array $event
     */
    public function handleGained(array $event): void
    {
        Log::info('SerpFeatureChangeListener: Processing gained event', $event);

        $this->alertService->processChangeEvent(array_merge($event, [
            'action' => 'gained',
        ]));
    }

    /**
     * Handle SERP feature lost events.
     *
     * @param array $event
     */
    public function handleLost(array $event): void
    {
        Log::info('SerpFeatureChangeListener: Processing lost event', $event);

        $this->alertService->processChangeEvent(array_merge($event, [
            'action' => 'lost',
        ]));
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events): array
    {
        return [
            'serp-feature.gained' => 'handleGained',
            'serp-feature.lost' => 'handleLost',
        ];
    }
}
