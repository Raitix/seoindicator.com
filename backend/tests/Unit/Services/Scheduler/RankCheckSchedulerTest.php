<?php

namespace Tests\Unit\Services\Scheduler;

use App\Models\Keyword;
use App\Services\Queue\JobQueue;
use App\Services\Scheduler\RankCheckScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Psr\Log\NullLogger;
use Tests\TestCase;

class RankCheckSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbPath = tempnam(sys_get_temp_dir(), 'queue_') . '.sqlite';
        Cache::flush();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
        parent::tearDown();
    }

    public function test_scheduler_queues_eligible_keywords_with_priorities(): void
    {
        config([
            'seoindicator.rank_checks.interval_hours' => 24,
            'seoindicator.rank_checks.spread_minutes' => 0,
            'seoindicator.rank_checks.priority_high_hours' => 24,
            'seoindicator.rank_checks.priority_normal_days' => 7,
            'seoindicator.rank_checks.cache_key' => 'rank_checks.scheduler.last_run',
        ]);

        Keyword::factory()->create([
            'last_checked_at' => null,
            'last_viewed_at' => now()->subHours(2),
        ]);

        Keyword::factory()->create([
            'last_checked_at' => now()->subDays(2),
            'last_viewed_at' => now()->subDays(3),
        ]);

        Keyword::factory()->create([
            'last_checked_at' => now(),
            'last_viewed_at' => now(),
        ]);

        $queue = new JobQueue($this->dbPath);
        $scheduler = new RankCheckScheduler($queue, new NullLogger());

        $result = $scheduler->run(true);

        $this->assertSame(2, $result['queued']);

        $pending = $queue->getPending(10);

        $this->assertCount(2, $pending);
        $this->assertSame('high', $pending[0]->priority);
        $this->assertSame('normal', $pending[1]->priority);
    }

    public function test_scheduler_skips_when_interval_not_reached(): void
    {
        config([
            'seoindicator.rank_checks.interval_hours' => 24,
            'seoindicator.rank_checks.cache_key' => 'rank_checks.scheduler.last_run',
        ]);

        Cache::put('rank_checks.scheduler.last_run', now()->toISOString());

        $queue = new JobQueue($this->dbPath);
        $scheduler = new RankCheckScheduler($queue, new NullLogger());

        $result = $scheduler->run(false);

        $this->assertSame(0, $result['queued']);
        $this->assertSame('interval_not_reached', $result['reason']);
    }
}
