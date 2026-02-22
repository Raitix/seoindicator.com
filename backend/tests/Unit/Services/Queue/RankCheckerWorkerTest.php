<?php

namespace Tests\Unit\Services\Queue;

use App\Models\Keyword;
use App\Models\Site;
use App\Repositories\KeywordRepository;
use App\Repositories\RankingRepository;
use App\Services\Queue\JobQueue;
use App\Services\Queue\RankCheckerWorker;
use App\Services\SERP\SERPEntryData;
use App\Services\SERP\SERPResultData;
use App\Services\SERPFetcherInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\NullLogger;
use Tests\TestCase;
use Mockery;

class RankCheckerWorkerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_processes_single_job_and_stores_ranking(): void
    {
        $this->artisan('migrate');

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'worker@example.com',
            'password' => bcrypt('password'),
        ]);

        $keyword = Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'seo tools',
            'search_engine' => 'google',
            'location' => 'US',
            'device' => 'desktop',
            'target_url' => 'https://example.com/page',
        ]);

        $queue = new JobQueue($this->tempQueuePath());
        $jobId = $queue->addJob([
            'type' => 'single',
            'keywordId' => $keyword->id,
        ]);

        $mockFetcher = Mockery::mock(SERPFetcherInterface::class);
        $mockFetcher->shouldReceive('fetch')
            ->once()
            ->andReturn(new SERPResultData(
                'seo tools',
                'google',
                'US',
                'desktop',
                [
                    new SERPEntryData(3, 'https://example.com/page', 'Title', 'Desc', true),
                ],
                now()->toAtomString(),
                null,
                120
            ));

        $worker = new RankCheckerWorker(
            $queue,
            new KeywordRepository(),
            new RankingRepository(),
            $mockFetcher,
            new NullLogger(),
            1
        );

        $worker->runOnce();

        $job = $queue->getJob($jobId);
        $this->assertNotNull($job);
        $this->assertSame('completed', $job->status);

        $this->assertDatabaseHas('ranking_history', [
            'keyword_id' => $keyword->id,
            'position' => 3,
            'url_found' => 'https://example.com/page',
        ]);

        $keyword->refresh();
        $this->assertNotNull($keyword->last_checked_at);
    }

    public function test_failed_fetch_requeues_job_and_records_error(): void
    {
        $this->artisan('migrate');

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'worker-fail@example.com',
            'password' => bcrypt('password'),
        ]);

        $keyword = Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'seo tools',
            'search_engine' => 'google',
            'location' => 'US',
            'device' => 'desktop',
            'target_url' => 'https://example.com/page',
        ]);

        $queue = new JobQueue($this->tempQueuePath());
        $jobId = $queue->addJob([
            'type' => 'single',
            'keywordId' => $keyword->id,
        ]);

        $mockFetcher = Mockery::mock(SERPFetcherInterface::class);
        $mockFetcher->shouldReceive('fetch')
            ->once()
            ->andReturn(new SERPResultData(
                'seo tools',
                'google',
                'US',
                'desktop',
                [],
                now()->toAtomString(),
                'SERP API down',
                50
            ));

        $worker = new RankCheckerWorker(
            $queue,
            new KeywordRepository(),
            new RankingRepository(),
            $mockFetcher,
            new NullLogger(),
            1
        );

        $worker->runOnce();

        $job = $queue->getJob($jobId);
        $this->assertNotNull($job);
        $this->assertSame('pending', $job->status);
        $this->assertSame(1, $job->attempts);

        $this->assertDatabaseHas('ranking_history', [
            'keyword_id' => $keyword->id,
            'error' => 'SERP API down',
        ]);
    }

    public function test_site_job_processes_multiple_keywords(): void
    {
        $this->artisan('migrate');

        $user = \App\Models\User::create([
            'name' => 'Site User',
            'email' => 'worker-site@example.com',
            'password' => bcrypt('password'),
        ]);

        $site = Site::create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'name' => 'Example',
        ]);

        $keywords = Keyword::factory()->count(2)->create([
            'site_id' => $site->id,
            'search_engine' => 'google',
            'location' => 'US',
            'device' => 'desktop',
            'target_url' => 'https://example.com/page',
            'last_checked_at' => null,
        ]);

        $queue = new JobQueue($this->tempQueuePath());
        $jobId = $queue->addJob([
            'type' => 'site',
            'siteId' => $site->id,
        ]);

        $mockFetcher = Mockery::mock(SERPFetcherInterface::class);
        $mockFetcher->shouldReceive('fetch')
            ->twice()
            ->andReturn(new SERPResultData(
                'seo tools',
                'google',
                'US',
                'desktop',
                [
                    new SERPEntryData(7, 'https://example.com/page', 'Title', 'Desc', true),
                ],
                now()->toAtomString(),
                null,
                80
            ));

        $worker = new RankCheckerWorker(
            $queue,
            new KeywordRepository(),
            new RankingRepository(),
            $mockFetcher,
            new NullLogger(),
            2
        );

        $worker->runOnce();

        $job = $queue->getJob($jobId);
        $this->assertNotNull($job);
        $this->assertSame('completed', $job->status);

        foreach ($keywords as $keyword) {
            $this->assertDatabaseHas('ranking_history', [
                'keyword_id' => $keyword->id,
                'position' => 7,
            ]);
        }
    }

    protected function tempQueuePath(): string
    {
        return tempnam(sys_get_temp_dir(), 'queue_') . '.sqlite';
    }
}
