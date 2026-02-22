<?php

return [
    'rank_checks' => [
        'interval_hours' => env('RANK_CHECK_INTERVAL_HOURS', 24),
        'spread_minutes' => env('RANK_CHECK_SPREAD_MINUTES', 120),
        'priority_high_hours' => env('RANK_CHECK_PRIORITY_HIGH_HOURS', 24),
        'priority_normal_days' => env('RANK_CHECK_PRIORITY_NORMAL_DAYS', 7),
        'cache_key' => env('RANK_CHECK_CACHE_KEY', 'rank_checks.scheduler.last_run'),
    ],
];
