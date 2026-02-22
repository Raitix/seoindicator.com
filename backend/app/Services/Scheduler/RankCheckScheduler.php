<?php

namespace App\Services\Scheduler;

use App\Models\Keyword;
use App\Services\Queue\JobQueueInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

class RankCheckScheduler
{
    public function __construct(
        protected JobQueueInterface $queue,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(bool $force = false): array
    {
        $intervalHours = (int) config('seoindicator.rank_checks.interval_hours', 24);
        $spreadMinutes = (int) config('seoindicator.rank_checks.spread_minutes', 120);
        $cacheKey = (string) config('seoindicator.rank_checks.cache_key', 'rank_checks.scheduler.last_run');

        $now = now();
        $lastRun = Cache::get($cacheKey);
        $lastRunAt = $lastRun ? Carbon::parse($lastRun) : null;

        if (!$force && $lastRunAt && $lastRunAt->greaterThan($now->copy()->subHours($intervalHours))) {
            $this->logger->info('Rank check scheduler skipped (interval not reached).', [
                'lastRunAt' => $lastRunAt->toISOString(),
                'intervalHours' => $intervalHours,
            ]);

            return [
                'queued' => 0,
                'skipped' => 0,
                'reason' => 'interval_not_reached',
                'lastRunAt' => $lastRunAt->toISOString(),
            ];
        }

        $threshold = $now->copy()->subHours($intervalHours);
        $eligibleQuery = Keyword::query()
            ->active()
            ->where(function ($query) use ($threshold) {
                $query->whereNull('last_checked_at')
                    ->orWhere('last_checked_at', '<=', $threshold);
            });

        $totalActive = Keyword::query()->active()->count();
        $eligibleKeywords = $eligibleQuery->get();
        $queued = 0;

        foreach ($eligibleKeywords as $keyword) {
            $priority = $this->priorityForKeyword($keyword, $now);
            $scheduledFor = $this->randomScheduleTime($now, $spreadMinutes);

            $this->queue->addJob([
                'type' => 'single',
                'keywordId' => $keyword->id,
                'scheduledFor' => $scheduledFor->toISOString(),
            ], $priority);

            $queued++;
        }

        $skipped = max(0, $totalActive - $queued);

        Cache::put($cacheKey, $now->toISOString());

        $this->logger->info('Rank check scheduler queued jobs.', [
            'queued' => $queued,
            'skipped' => $skipped,
            'intervalHours' => $intervalHours,
            'spreadMinutes' => $spreadMinutes,
        ]);

        return [
            'queued' => $queued,
            'skipped' => $skipped,
            'intervalHours' => $intervalHours,
            'spreadMinutes' => $spreadMinutes,
            'lastRunAt' => $now->toISOString(),
        ];
    }

    protected function priorityForKeyword(Keyword $keyword, Carbon $now): string
    {
        $highWindowHours = (int) config('seoindicator.rank_checks.priority_high_hours', 24);
        $normalWindowDays = (int) config('seoindicator.rank_checks.priority_normal_days', 7);

        $lastViewed = $keyword->last_viewed_at;
        if ($lastViewed instanceof Carbon) {
            if ($lastViewed->greaterThanOrEqualTo($now->copy()->subHours($highWindowHours))) {
                return 'high';
            }

            if ($lastViewed->greaterThanOrEqualTo($now->copy()->subDays($normalWindowDays))) {
                return 'normal';
            }
        }

        return 'low';
    }

    protected function randomScheduleTime(Carbon $now, int $spreadMinutes): Carbon
    {
        if ($spreadMinutes <= 0) {
            return $now->copy();
        }

        $offset = random_int(0, $spreadMinutes);

        return $now->copy()->addMinutes($offset);
    }
}
