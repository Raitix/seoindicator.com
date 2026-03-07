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
    |
    */

    'alert_thresholds' => [
        'mention_spike' => 20,    // +20%
        'mention_drop' => 20,     // -20%
        'competitor_gain' => 15,  // +15%
    ],

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

];
