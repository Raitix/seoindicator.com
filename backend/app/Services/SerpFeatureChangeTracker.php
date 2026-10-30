<?php

namespace App\Services;

use App\Models\SerpFeature;
use App\Repositories\SerpFeatureHistoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * SerpFeatureChangeTracker service for TASK-7-8.
 *
 * Detects when SERP features are gained or lost by comparing current
 * feature state with previous state and records changes in history.
 */
class SerpFeatureChangeTracker
{
    protected SerpFeatureHistoryRepository $historyRepo;

    /**
     * Create a new SerpFeatureChangeTracker instance.
     */
    public function __construct(SerpFeatureHistoryRepository $historyRepo)
    {
        $this->historyRepo = $historyRepo;
    }

    /**
     * Track feature changes between current and previous states.
     *
     * @param int $keywordId The keyword ID being tracked
     * @param array $currentFeatures Array of current SERP feature data
     * @param array $previousFeatures Array of previous SERP feature data (empty for first check)
     * @return array Array of SERP feature change records
     * @throws \InvalidArgumentException If keyword ID is invalid
     */
    public function trackChanges(int $keywordId, array $currentFeatures, array $previousFeatures): array
    {
        // Validate keyword ID
        if ($keywordId <= 0) {
            throw new \InvalidArgumentException('Invalid keyword ID: must be a positive integer');
        }

        $changes = [];
        $detectedAt = now();

        // Get all feature types we know about
        $allFeatureTypes = SerpFeature::FEATURE_TYPES;

        // Build lookup maps for current and previous features
        $currentMap = $this->buildFeatureMap($currentFeatures);
        $previousMap = $this->buildFeatureMap($previousFeatures);

        // Determine if this is a first check (no previous features)
        $isFirstCheck = empty($previousFeatures) || empty($previousMap);

        // If first check, we can't determine "gained" - return empty
        // The caller should still record current features, but no changes
        if ($isFirstCheck) {
            return [];
        }

        // Process each feature type
        foreach ($allFeatureTypes as $featureType) {
            $change = $this->detectFeatureChange(
                $keywordId,
                $featureType,
                $currentMap[$featureType] ?? null,
                $previousMap[$featureType] ?? null,
                $detectedAt
            );

            if ($change !== null) {
                $changes[] = $change;
                $this->persistAndEmit($change);
            }
        }

        return $changes;
    }

    /**
     * Build a lookup map from feature array.
     *
     * @param array $features
     * @return array
     */
    protected function buildFeatureMap(array $features): array
    {
        $map = [];

        foreach ($features as $feature) {
            $type = $feature['type'] ?? null;

            // Skip invalid feature types
            if ($type === null || !in_array($type, SerpFeature::FEATURE_TYPES)) {
                continue;
            }

            $map[$type] = [
                'is_present' => $feature['is_present'] ?? false,
                'url' => $feature['url'] ?? null,
            ];
        }

        return $map;
    }

    /**
     * Detect change for a single feature type.
     *
     * @param int $keywordId
     * @param string $featureType
     * @param array|null $current Current feature data or null
     * @param array|null $previous Previous feature data or null
     * @param \Illuminate\Support\Carbon $detectedAt
     * @return array|null Change record or null if no change
     */
    protected function detectFeatureChange(
        int $keywordId,
        string $featureType,
        ?array $current,
        ?array $previous,
        $detectedAt
    ): ?array {
        $currentPresent = $current['is_present'] ?? false;
        $previousPresent = $previous['is_present'] ?? false;

        // Handle missing feature in current but present in previous = lost
        if ($current === null && $previous !== null && $previousPresent) {
            return [
                'type' => $featureType,
                'action' => 'lost',
                'keyword_id' => $keywordId,
                'previous_url' => $previous['url'] ?? null,
                'current_url' => null,
                'detected_at' => $detectedAt,
            ];
        }

        // Handle missing feature in previous but present in current
        // This could be first occurrence - don't trigger gained
        if ($previous === null && $current !== null && $currentPresent) {
            return null; // Can't know if it was present before
        }

        // No change
        if ($currentPresent === $previousPresent) {
            return null;
        }

        // Gained: wasn't present before, is present now
        if ($currentPresent && !$previousPresent) {
            return [
                'type' => $featureType,
                'action' => 'gained',
                'keyword_id' => $keywordId,
                'previous_url' => null,
                'current_url' => $current['url'] ?? null,
                'detected_at' => $detectedAt,
            ];
        }

        // Lost: was present before, not present now
        if (!$currentPresent && $previousPresent) {
            return [
                'type' => $featureType,
                'action' => 'lost',
                'keyword_id' => $keywordId,
                'previous_url' => $previous['url'] ?? null,
                'current_url' => null,
                'detected_at' => $detectedAt,
            ];
        }

        return null;
    }

    /**
     * Persist change to database and emit event.
     *
     * @param array $change
     * @return void
     */
    protected function persistAndEmit(array $change): void
    {
        try {
            // Persist to history
            $this->historyRepo->insert([
                'keyword_id' => $change['keyword_id'],
                'feature_type' => $change['type'],
                'action' => $change['action'],
                'previous_url' => $change['previous_url'],
                'current_url' => $change['current_url'],
                'detected_at' => $change['detected_at'],
            ]);
        } catch (\Exception $e) {
            // Log error but continue processing
            Log::error('Failed to persist SERP feature change', [
                'change' => $change,
                'error' => $e->getMessage(),
            ]);
        }

        // Emit event for alert system integration
        // Note: Wrap in array so Laravel passes it as a single array argument
        $eventName = 'serp-feature.' . $change['action'];
        Event::dispatch($eventName, [[
            'keyword_id' => $change['keyword_id'],
            'feature_type' => $change['type'],
            'action' => $change['action'],
            'previous_url' => $change['previous_url'],
            'current_url' => $change['current_url'],
            'detected_at' => $change['detected_at'],
        ]]);
    }

    /**
     * Get all feature changes for a keyword within a date range.
     *
     * @param int $keywordId
     * @param \DateTimeInterface|null $since
     * @return Collection
     */
    public function getChangesForKeyword(int $keywordId, ?\DateTimeInterface $since = null): Collection
    {
        $query = $this->historyRepo->getHistoryByKeyword($keywordId, 100);

        if ($since) {
            $query = $query->filter(function ($item) use ($since) {
                return $item->detected_at >= $since;
            });
        }

        return $query;
    }
}
