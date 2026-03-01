<?php

/**
 * Cost Tracking Configuration
 *
 * Defines cost rates for analysis tools and default settings.
 * For MVP, all tools are FREE - costs are estimated/notional for transparency
 * and future monetization preparation.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Rate
    |--------------------------------------------------------------------------
    |
    | The default rate applied when a tool is not found in the rates array.
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
    | Cost rates per tool/operation. Each tool can have:
    | - base: Base cost for using the tool
    | - per_unit: Cost per variable unit (e.g., per page, per 1000 words)
    | - type: Tool type (free, paid)
    | - multiplier: Optional multiplier applied to total cost
    | - currency: Optional override currency
    | - exchange_rate: Optional exchange rate for currency conversion
    |
    | MVP: All tools are FREE (base: 0.00, per_unit: 0.00)
    |
    */
    'rates' => [
        'meta_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'heading_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'serp_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'google_cse' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'daily_quota' => 100,
            'type' => 'free',
        ],
        'backlink_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'pwa_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'keyword_density' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
        'meta_tag_analyzer' => [
            'base' => 0.00,
            'per_unit' => 0.00,
            'type' => 'free',
        ],
    ],
];
