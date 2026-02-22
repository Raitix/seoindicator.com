<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
