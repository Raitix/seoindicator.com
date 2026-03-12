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
     * @param mixed ...$eventData Event payload (passed as individual arguments by Laravel)
     */
    public function handleGained(...$eventData): void
    {
        $event = $this->extractEventData($eventData);

        Log::info('SerpFeatureChangeListener: Processing gained event', $event);

        $this->alertService->processChangeEvent(array_merge($event, [
            'action' => 'gained',
        ]));
    }

    /**
     * Handle SERP feature lost events.
     *
     * @param mixed ...$eventData Event payload (passed as individual arguments by Laravel)
     */
    public function handleLost(...$eventData): void
    {
        $event = $this->extractEventData($eventData);

        Log::info('SerpFeatureChangeListener: Processing lost event', $event);

        $this->alertService->processChangeEvent(array_merge($event, [
            'action' => 'lost',
        ]));
    }

    /**
     * Extract event data from variadic arguments.
     *
     * When Event::dispatch() is called with an array payload,
     * Laravel passes the array items as individual arguments.
     * If the first argument is an array (associative), use it directly.
     * Otherwise, combine all arguments into an array.
     *
     * @param array $eventData
     * @return array
     */
    protected function extractEventData(array $eventData): array
    {
        // If single argument and it's an array, use it directly
        if (count($eventData) === 1 && is_array($eventData[0])) {
            return $eventData[0];
        }

        // If multiple arguments, they might be named parameters
        // Build an associative array from them
        if (count($eventData) > 1 && is_string(array_keys($eventData)[0] ?? null)) {
            return $eventData;
        }

        // Fallback: return as-is if associative, or wrap if needed
        return is_array($eventData[0] ?? null) ? $eventData[0] : $eventData;
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
