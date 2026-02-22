<?php

namespace Tests\Unit\Services\Queue;

use App\Services\Queue\JobQueue;
use Tests\TestCase;

class JobQueueTest extends TestCase
{
    private string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dbPath = tempnam(sys_get_temp_dir(), 'queue_') . '.sqlite';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
        parent::tearDown();
    }

    public function test_add_and_get_job(): void
    {
        $queue = new JobQueue($this->dbPath);

        $jobId = $queue->addJob([
            'type' => 'single',
            'keywordId' => 5,
            'siteId' => 2,
        ]);

        $job = $queue->getJob($jobId);

        $this->assertNotNull($job);
        $this->assertSame($jobId, $job->id);
        $this->assertSame('single', $job->type);
        $this->assertSame('pending', $job->status);
        $this->assertSame(0, $job->attempts);
        $this->assertSame(3, $job->maxAttempts);
        $this->assertSame(5, $job->payload['keywordId']);
        $this->assertSame(2, $job->payload['siteId']);
    }

    public function test_pending_jobs_are_sorted_by_priority(): void
    {
        $queue = new JobQueue($this->dbPath);

        $lowId = $queue->addJob(['type' => 'low'], 'low');
        $highId = $queue->addJob(['type' => 'high'], 'high');
        $normalId = $queue->addJob(['type' => 'normal'], 'normal');

        $pending = $queue->getPending(10);

        $this->assertCount(3, $pending);
        $this->assertSame($highId, $pending[0]->id);
        $this->assertSame($normalId, $pending[1]->id);
        $this->assertSame($lowId, $pending[2]->id);
    }

    public function test_failed_jobs_retry_until_max_attempts(): void
    {
        $queue = new JobQueue($this->dbPath);

        $jobId = $queue->addJob(['type' => 'retry'], 'normal', 2);

        $queue->updateJob($jobId, ['status' => 'running']);
        $job = $queue->updateJob($jobId, ['status' => 'failed', 'error' => 'first fail']);

        $this->assertNotNull($job);
        $this->assertSame('pending', $job->status);
        $this->assertSame(1, $job->attempts);

        $queue->updateJob($jobId, ['status' => 'running']);
        $job = $queue->updateJob($jobId, ['status' => 'failed', 'error' => 'second fail']);

        $this->assertNotNull($job);
        $this->assertSame('failed', $job->status);
        $this->assertSame(2, $job->attempts);
        $this->assertNotNull($job->failedAt);
    }

    public function test_completed_jobs_are_cleaned_up_after_seven_days(): void
    {
        $queue = new JobQueue($this->dbPath);

        $jobId = $queue->addJob(['type' => 'cleanup']);
        $job = $queue->updateJob($jobId, ['status' => 'completed']);

        $this->assertNotNull($job);
        $this->assertSame('completed', $job->status);

        // Force completed_at to be old
        $pdo = new \PDO('sqlite:' . $this->dbPath);
        $pdo->exec("UPDATE queue_jobs SET completed_at = datetime('now', '-8 days') WHERE id = '{$jobId}'");

        $pending = $queue->getPending();

        $this->assertCount(0, $pending);
        $this->assertNull($queue->getJob($jobId));
    }

    public function test_pending_jobs_skip_scheduled_future_jobs(): void
    {
        $queue = new JobQueue($this->dbPath);

        $queue->addJob([
            'type' => 'future',
            'scheduledFor' => now()->addDay()->toISOString(),
        ]);

        $queue->addJob(['type' => 'now']);

        $pending = $queue->getPending();

        $this->assertCount(1, $pending);
        $this->assertSame('now', $pending[0]->type);
    }
}
