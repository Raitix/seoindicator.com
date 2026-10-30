<?php

/**
 * AI Visibility Configuration
 * 
 * Configuration for Generative Engine Optimization (GEO) feature.
 * 
 * @see SPEC-034
 * @see TASK-34-5
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Query Limits by Tier
    |--------------------------------------------------------------------------
    |
    | Maximum number of queries allowed per month for each subscription tier.
    | Set to null for unlimited queries.
    |
    */

    'query_limits' => [
        'free' => 5,
        'pro' => 50,
        'business' => 200,
        'unlimited' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Time-to-live in seconds for cached analytics and competitor comparisons.
    | Default: 3600 seconds (1 hour)
    |
    */

    'cache_ttl' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Percentage thresholds for triggering alerts.
    | mention_spike: Alert when mentions increase by this percentage
    | mention_drop: Alert when mentions decrease by this percentage
    | competitor_gain: Alert when competitor mentions increase by this percentage
    | sentiment_change: Alert when sentiment changes by this amount
    |
    | Environment variables:
    | - AI_VISIBILITY_SPIKE_THRESHOLD
    | - AI_VISIBILITY_DROP_THRESHOLD
    | - AI_VISIBILITY_COMPETITOR_THRESHOLD
    | - AI_VISIBILITY_SENTIMENT_THRESHOLD
    |
    */

    'alert_thresholds' => [
        'mention_spike' => env('AI_VISIBILITY_SPIKE_THRESHOLD', 20),    // +20%
        'mention_drop' => env('AI_VISIBILITY_DROP_THRESHOLD', 20),     // -20%
        'competitor_gain' => env('AI_VISIBILITY_COMPETITOR_THRESHOLD', 15),  // +15%
        'sentiment_change' => env('AI_VISIBILITY_SENTIMENT_THRESHOLD', 0.30),  // ±0.30 score
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Deduplication Window
    |--------------------------------------------------------------------------
    |
    | Time in seconds before a new alert can be created for the same condition.
    | Default: 86400 seconds (24 hours)
    |
    */

    'deduplication_window' => env('AI_VISIBILITY_DEDUP_WINDOW', 86400),

    /*
    |--------------------------------------------------------------------------
    | Delete Strategy
    |--------------------------------------------------------------------------
    |
    | Whether to use soft deletes or hard deletes for projects.
    | Options: 'soft', 'hard'
    |
    */

    'delete_strategy' => 'soft',

    /*
    |--------------------------------------------------------------------------
    | Default Query Templates
    |--------------------------------------------------------------------------
    |
    | Default templates used when project has no custom templates.
    |
    */

    'default_query_templates' => [
        'What are the best tools for {category}?',
        'Can you recommend a good {category} tool?',
        'How does {brand} compare to competitors?',
    ],

    /*
    |--------------------------------------------------------------------------
    | Platforms
    |--------------------------------------------------------------------------
    |
    | AI platforms to query for visibility tracking.
    |
    */

    'platforms' => [
        'chatgpt',
        'perplexity',
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Rate Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of exports per minute per user.
    |
    */

    'export_rate_limit' => env('AI_VISIBILITY_EXPORT_RATE_LIMIT', 10),

];
