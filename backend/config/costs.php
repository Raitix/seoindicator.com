<?php

/**
 * Cost Tracking Configuration
 *
 * Defines cost rates for analysis tools and operations.
 * For MVP, all tools are FREE (base: 0.00, per_unit: 0.00).
 * Architecture supports future paid tiers.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Rate
    |--------------------------------------------------------------------------
    |
    | The default cost rate to use when a tool is not explicitly configured.
    | This ensures graceful fallback for unknown tools.
    |
    */
    'default_rate' => 0.01,

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for cost calculations.
    |
    */
    'currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Tool Cost Rates
    |--------------------------------------------------------------------------
    |
    | Configure cost rates per tool. Each tool can have:
    | - base: Fixed base cost per analysis
    | - per_unit: Variable cost per unit (e.g., per 1000 words, per API call)
    | - type: 'free', 'paid', or 'metered'
    | - currency: Optional override currency
    | - multiplier: Optional cost multiplier
    | - exchange_rate: Optional exchange rate for currency conversion
    |
    | MVP: All tools are FREE (transparency only, no actual billing).
    |
    */
    'rates' => [
        // Meta Tag Analyzer - FREE
        'meta-tag-analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'meta_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],

        // Heading Analyzer - FREE
        'heading-analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'heading_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],

        // Keyword Density - FREE
        'keyword-density' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],

        // Image Alt Checker - FREE
        'image-alt-checker' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],

        // Link Analyzer - FREE
        'link-analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],

        // SERP Analyzer - FREE
        'serp-analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'serp_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],

        // Backlink Analyzer - FREE
        'backlink-analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'backlink_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],

        // PWA Analyzer - FREE
        'pwa-analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'pwa_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],

        // Google Custom Search API - FREE tier (100 queries/day)
        'google_cse' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'daily_quota' => 100,
            'type' => 'free',
        ],

        // Example paid tool configuration (for future reference)
        // 'paid_tool' => [
        //     'base' => 0.05,
        //     'per_unit' => 0.02,
        //     'type' => 'paid',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Exchange Rates
    |--------------------------------------------------------------------------
    |
    | Exchange rates for converting costs to the default currency.
    | Rates are relative to USD.
    |
    */
    'exchange_rates' => [
        'USD' => 1.0,
        'EUR' => 1.1,
        'GBP' => 1.27,
        'JPY' => 0.0067,
    ],
];
