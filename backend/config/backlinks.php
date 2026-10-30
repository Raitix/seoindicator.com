<?php

/**
 * Backlinks configuration for TASK-23-5.
 * 
 * Controls scheduled daily backlink sync from Google Search Console.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Enable Scheduled Backlink Sync
    |--------------------------------------------------------------------------
    |
    | When enabled, the system will automatically sync backlink data from
    | Google Search Console on a daily schedule for all projects with
    | active GSC connections.
    |
    */
    'sync_enabled' => env('BACKLINKS_SYNC_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Scheduled Sync Time
    |--------------------------------------------------------------------------
    |
    | The time of day to run the scheduled backlink sync. Format is 24-hour
    | time (HH:MM). Default is 03:00 (3 AM) to run during low-traffic hours.
    |
    */
    'sync_time' => env('BACKLINKS_SYNC_TIME', '03:00'),

    /*
    |--------------------------------------------------------------------------
    | Job Spread Duration (minutes)
    |--------------------------------------------------------------------------
    |
    | When dispatching multiple sync jobs, spread them over this duration
    | to avoid hitting GSC API rate limits. Default is 30 minutes.
    |
    */
    'spread_duration' => env('BACKLINKS_SPREAD_DURATION', 30),
];
