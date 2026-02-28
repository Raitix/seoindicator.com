<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\AlertSettings;
use App\Models\KeywordAlertSettings;
use Illuminate\Support\Facades\Log;

/**
 * AlertEvaluator Service
 *
 * Evaluates keyword ranking changes and determines if alerts should be triggered.
 * Compares old vs new positions against user-defined thresholds.
 */
class AlertEvaluator
{
    /**
     * Event types that can be triggered.
     */
    public const EVENT_POSITION_DROP = 'position_drop';
    public const EVENT_POSITION_GAIN = 'position_gain';
    public const EVENT_ENTERED_TOP_10 = 'entered_top_10';
    public const EVENT_LEFT_TOP_10 = 'left_top_10';
    public const EVENT_ENTERED_TOP_3 = 'entered_top_3';
    public const EVENT_LEFT_TOP_3 = 'left_top_3';
    public const EVENT_SERP_FEATURE_GAINED = 'serp_feature_gained';
    public const EVENT_SERP_FEATURE_LOST = 'serp_feature_lost';

    /**
     * All available event types.
     */
    public const ALL_EVENTS = [
        self::EVENT_POSITION_DROP,
        self::EVENT_POSITION_GAIN,
        self::EVENT_ENTERED_TOP_10,
        self::EVENT_LEFT_TOP_10,
        self::EVENT_ENTERED_TOP_3,
        self::EVENT_LEFT_TOP_3,
        self::EVENT_SERP_FEATURE_GAINED,
        self::EVENT_SERP_FEATURE_LOST,
    ];

    /**
     * MVP event types (Phase 1).
     */
    public const MVP_EVENTS = [
        self::EVENT_POSITION_DROP,
        self::EVENT_POSITION_GAIN,
        self::EVENT_ENTERED_TOP_10,
        self::EVENT_LEFT_TOP_10,
    ];

    /**
     * Evaluate a keyword's position change and return triggered events.
     *
     * @param Keyword $keyword The keyword that changed
     * @param int|null $oldPosition Previous position (null if first check)
     * @param int|null $newPosition New position (null if not found)
     * @param array $options Additional options (serp_features, etc.)
     * @return array Array of triggered events with metadata
     */
    public function evaluate(Keyword $keyword, ?int $oldPosition, ?int $newPosition, array $options = []): array
    {
        // Skip if no previous position (first check)
        if ($oldPosition === null) {
            Log::debug('AlertEvaluator: Skipping - no previous position', [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
            ]);
            return [];
        }

        // Skip if new position is null (not found in SERP)
        if ($newPosition === null) {
            Log::debug('AlertEvaluator: Skipping - keyword not found in SERP', [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
            ]);
            return [];
        }

        // Get user's alert settings
        $alertSettings = $this->getUserAlertSettings($keyword);
        if (!$alertSettings || !$alertSettings->global_enabled) {
            Log::debug('AlertEvaluator: Skipping - alerts disabled globally', [
                'keyword_id' => $keyword->id,
                'user_id' => $keyword->user_id,
            ]);
            return [];
        }

        // Get keyword-specific alert settings
        $keywordSettings = $this->getKeywordAlertSettings($keyword);
        if ($keywordSettings && !$keywordSettings->enabled) {
            Log::debug('AlertEvaluator: Skipping - alerts disabled for keyword', [
                'keyword_id' => $keyword->id,
            ]);
            return [];
        }

        $events = [];

        // Evaluate position change events
        $positionEvents = $this->evaluatePositionChange(
            $oldPosition,
            $newPosition,
            $alertSettings,
            $keywordSettings
        );
        $events = array_merge($events, $positionEvents);

        // Evaluate SERP feature changes if provided
        if (isset($options['serp_features'])) {
            $serpEvents = $this->evaluateSerpFeatureChanges(
                $options['serp_features'],
                $alertSettings,
                $keywordSettings
            );
            $events = array_merge($events, $serpEvents);
        }

        // Filter events based on keyword settings
        $events = $this->filterEnabledEvents($events, $keywordSettings);

        Log::info('AlertEvaluator: Evaluation complete', [
            'keyword_id' => $keyword->id,
            'keyword' => $keyword->keyword,
            'old_position' => $oldPosition,
            'new_position' => $newPosition,
            'events_triggered' => count($events),
        ]);

        return $events;
    }

    /**
     * Evaluate position change events.
     *
     * @param int $oldPosition
     * @param int $newPosition
     * @param AlertSettings $alertSettings
     * @param KeywordAlertSettings|null $keywordSettings
     * @return array
     */
    protected function evaluatePositionChange(
        int $oldPosition,
        int $newPosition,
        AlertSettings $alertSettings,
        ?KeywordAlertSettings $keywordSettings
    ): array {
        $events = [];
        $change = $newPosition - $oldPosition; // Positive = dropped, Negative = improved
        $absChange = abs($change);

        // Get threshold (keyword-specific override or global default)
        $threshold = $this->getThreshold($alertSettings, $keywordSettings);

        // Position drop (ranking got worse)
        if ($change > 0 && $absChange >= $threshold) {
            $events[] = [
                'event' => self::EVENT_POSITION_DROP,
                'old_position' => $oldPosition,
                'new_position' => $newPosition,
                'change' => $change,
                'threshold' => $threshold,
            ];
        }

        // Position gain (ranking improved)
        if ($change < 0 && $absChange >= $threshold) {
            $events[] = [
                'event' => self::EVENT_POSITION_GAIN,
                'old_position' => $oldPosition,
                'new_position' => $newPosition,
                'change' => $absChange, // Positive number for display
                'threshold' => $threshold,
            ];
        }

        // Entered top 10
        if ($oldPosition > 10 && $newPosition <= 10) {
            $events[] = [
                'event' => self::EVENT_ENTERED_TOP_10,
                'old_position' => $oldPosition,
                'new_position' => $newPosition,
            ];
        }

        // Left top 10
        if ($oldPosition <= 10 && $newPosition > 10) {
            $events[] = [
                'event' => self::EVENT_LEFT_TOP_10,
                'old_position' => $oldPosition,
                'new_position' => $newPosition,
            ];
        }

        // Entered top 3
        if ($oldPosition > 3 && $newPosition <= 3) {
            $events[] = [
                'event' => self::EVENT_ENTERED_TOP_3,
                'old_position' => $oldPosition,
                'new_position' => $newPosition,
            ];
        }

        // Left top 3
        if ($oldPosition <= 3 && $newPosition > 3) {
            $events[] = [
                'event' => self::EVENT_LEFT_TOP_3,
                'old_position' => $oldPosition,
                'new_position' => $newPosition,
            ];
        }

        return $events;
    }

    /**
     * Evaluate SERP feature changes.
     *
     * @param array $serpFeatures ['gained' => [...], 'lost' => [...]]
     * @param AlertSettings $alertSettings
     * @param KeywordAlertSettings|null $keywordSettings
     * @return array
     */
    protected function evaluateSerpFeatureChanges(
        array $serpFeatures,
        AlertSettings $alertSettings,
        ?KeywordAlertSettings $keywordSettings
    ): array {
        $events = [];

        // Check if SERP feature alerts are enabled
        if (!$alertSettings->serp_feature_alerts_enabled) {
            return $events;
        }

        // Gained SERP features
        if (!empty($serpFeatures['gained'])) {
            foreach ($serpFeatures['gained'] as $feature) {
                if ($alertSettings->isSerpFeatureAlertEnabled($feature)) {
                    $events[] = [
                        'event' => self::EVENT_SERP_FEATURE_GAINED,
                        'feature_type' => $feature,
                    ];
                }
            }
        }

        // Lost SERP features
        if (!empty($serpFeatures['lost'])) {
            foreach ($serpFeatures['lost'] as $feature) {
                if ($alertSettings->isSerpFeatureAlertEnabled($feature)) {
                    $events[] = [
                        'event' => self::EVENT_SERP_FEATURE_LOST,
                        'feature_type' => $feature,
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Get user's alert settings.
     *
     * @param Keyword $keyword
     * @return AlertSettings|null
     */
    protected function getUserAlertSettings(Keyword $keyword): ?AlertSettings
    {
        if (!$keyword->user_id) {
            return null;
        }

        return AlertSettings::firstOrCreate(
            ['user_id' => $keyword->user_id],
            AlertSettings::getDefaults()
        );
    }

    /**
     * Get keyword-specific alert settings.
     *
     * @param Keyword $keyword
     * @return KeywordAlertSettings|null
     */
    protected function getKeywordAlertSettings(Keyword $keyword): ?KeywordAlertSettings
    {
        return KeywordAlertSettings::where('keyword_id', $keyword->id)->first();
    }

    /**
     * Get the threshold to use (keyword-specific or global).
     *
     * @param AlertSettings $alertSettings
     * @param KeywordAlertSettings|null $keywordSettings
     * @return int
     */
    protected function getThreshold(AlertSettings $alertSettings, ?KeywordAlertSettings $keywordSettings): int
    {
        if ($keywordSettings && $keywordSettings->custom_threshold !== null) {
            return $keywordSettings->custom_threshold;
        }

        return $alertSettings->default_threshold;
    }

    /**
     * Filter events based on keyword settings.
     *
     * @param array $events
     * @param KeywordAlertSettings|null $keywordSettings
     * @return array
     */
    protected function filterEnabledEvents(array $events, ?KeywordAlertSettings $keywordSettings): array
    {
        if (!$keywordSettings || empty($keywordSettings->alert_events)) {
            // No filtering - all events enabled
            return $events;
        }

        $enabledEvents = $keywordSettings->alert_events;

        return array_filter($events, function ($event) use ($enabledEvents) {
            return in_array($event['event'], $enabledEvents);
        });
    }
}
