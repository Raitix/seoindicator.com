<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\AIVisibility\RunAIVisibilityQueriesJob;
use App\Jobs\AIVisibility\AggregateAIVisibilityAnalyticsJob;
use App\Jobs\AIVisibility\CheckAIVisibilityAlertsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rank-checks:schedule {--force}', function (\App\Services\Scheduler\RankCheckScheduler $scheduler) {
    $result = $scheduler->run((bool) $this->option('force'));

    $this->info("Queued {$result['queued']} rank check jobs.");

    if (!empty($result['skipped'])) {
        $this->line("Skipped {$result['skipped']} recently checked keywords.");
    }

    if (!empty($result['reason'])) {
        $this->line("Reason: {$result['reason']}");
    }
})->purpose('Queue rank check jobs for active keywords');

// AI Visibility scheduled jobs
Schedule::job(new RunAIVisibilityQueriesJob)
    ->weeklyOn(0, '02:00')
    ->name('ai-visibility:run-queries')
    ->withoutOverlapping(3600);

Schedule::job(new AggregateAIVisibilityAnalyticsJob)
    ->dailyAt('03:00')
    ->name('ai-visibility:aggregate-analytics')
    ->withoutOverlapping(3600);

Schedule::job(new CheckAIVisibilityAlertsJob)
    ->cron('0 */6 * * *')
    ->name('ai-visibility:check-alerts')
    ->withoutOverlapping(1800);
