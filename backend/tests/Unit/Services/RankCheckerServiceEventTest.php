<?php

namespace Tests\Unit\Services;

use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use App\Models\User;
use App\Models\Webhook;
use App\Services\RankCheckerService;
use App\Services\SERPFetcherInterface;
use App\Services\SERP\SERPResultData;
use App\Services\SERP\SERPEntryData;
use App\Services\SERP\SERPResult;
use App\Services\WebhookDispatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for RankCheckerService webhook event emission.
 *
 * @see TASK-6-16
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RankCheckerServiceEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_emits_ranking_changed_event_on_position_improvement(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id, 'url' => 'https://example.com']);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // Create previous ranking
        Ranking::create([
            'keyword_id' => $keyword->id,
            'position' => 7,
            'checked_at' => now()->subDay(),
        ]);

        // Create webhook for user
        Webhook::create([
            'user_id' => $user->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'test_secret',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        // Create the mock SERP result
        $serpResult = $this->createSerpResult('seo tools', 5, 'https://example.com/seo');

        // Mock SERP fetcher using instance binding
        $fetcherMock = \Mockery::mock(SERPFetcherInterface::class);
        $fetcherMock->shouldReceive('fetch')
            ->once()
            ->andReturn($serpResult);
        $this->app->instance(SERPFetcherInterface::class, $fetcherMock);

        // Mock webhook dispatcher using instance binding
        $dispatcherMock = \Mockery::mock(WebhookDispatcherService::class);
        $dispatcherMock->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) use ($user) {
                return $userId === $user->id
                    && $eventType === 'ranking.changed'
                    && $data['old_position'] === 7
                    && $data['new_position'] === 5
                    && $data['change'] === 2; // improvement
            })
            ->andReturn(1);
        $this->app->instance(WebhookDispatcherService::class, $dispatcherMock);

        $service = $this->app->make(RankCheckerService::class);
        $result = $service->checkAndStore($keyword->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_emits_ranking_changed_event_on_position_decline(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id, 'url' => 'https://example.com']);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // Create previous ranking
        Ranking::create([
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now()->subDay(),
        ]);

        // Create webhook for user
        Webhook::create([
            'user_id' => $user->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'test_secret',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        // Create the mock SERP result
        $serpResult = $this->createSerpResult('seo tools', 10, 'https://example.com/seo');

        // Mock SERP fetcher using instance binding
        $fetcherMock = \Mockery::mock(SERPFetcherInterface::class);
        $fetcherMock->shouldReceive('fetch')
            ->once()
            ->andReturn($serpResult);
        $this->app->instance(SERPFetcherInterface::class, $fetcherMock);

        // Mock webhook dispatcher using instance binding
        $dispatcherMock = \Mockery::mock(WebhookDispatcherService::class);
        $dispatcherMock->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) {
                return $data['old_position'] === 5
                    && $data['new_position'] === 10
                    && $data['change'] === -5; // decline
            })
            ->andReturn(1);
        $this->app->instance(WebhookDispatcherService::class, $dispatcherMock);

        $service = $this->app->make(RankCheckerService::class);
        $service->checkAndStore($keyword->id);
    }

    /** @test */
    public function it_emits_ranking_changed_event_on_first_ranking(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id, 'url' => 'https://example.com']);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // No previous ranking

        // Create webhook for user
        Webhook::create([
            'user_id' => $user->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'test_secret',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        // Create the mock SERP result
        $serpResult = $this->createSerpResult('seo tools', 15, 'https://example.com/seo');

        // Mock SERP fetcher using instance binding
        $fetcherMock = \Mockery::mock(SERPFetcherInterface::class);
        $fetcherMock->shouldReceive('fetch')
            ->once()
            ->andReturn($serpResult);
        $this->app->instance(SERPFetcherInterface::class, $fetcherMock);

        // Mock webhook dispatcher using instance binding
        $dispatcherMock = \Mockery::mock(WebhookDispatcherService::class);
        $dispatcherMock->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) {
                return $data['old_position'] === null
                    && $data['new_position'] === 15
                    && $data['change'] === null; // first ranking
            })
            ->andReturn(1);
        $this->app->instance(WebhookDispatcherService::class, $dispatcherMock);

        $service = $this->app->make(RankCheckerService::class);
        $service->checkAndStore($keyword->id);
    }

    /** @test */
    public function it_emits_ranking_changed_event_on_drop_from_top_100(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id, 'url' => 'https://example.com']);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // Create previous ranking
        Ranking::create([
            'keyword_id' => $keyword->id,
            'position' => 50,
            'checked_at' => now()->subDay(),
        ]);

        // Create webhook for user
        Webhook::create([
            'user_id' => $user->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'test_secret',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        // Create the mock SERP result - not found in top 100
        $serpResult = $this->createSerpResult('seo tools', null, null);

        // Mock SERP fetcher using instance binding
        $fetcherMock = \Mockery::mock(SERPFetcherInterface::class);
        $fetcherMock->shouldReceive('fetch')
            ->once()
            ->andReturn($serpResult);
        $this->app->instance(SERPFetcherInterface::class, $fetcherMock);

        // Mock webhook dispatcher using instance binding
        $dispatcherMock = \Mockery::mock(WebhookDispatcherService::class);
        $dispatcherMock->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) {
                return $data['old_position'] === 50
                    && $data['new_position'] === null
                    && $data['change'] === null; // dropped out
            })
            ->andReturn(1);
        $this->app->instance(WebhookDispatcherService::class, $dispatcherMock);

        $service = $this->app->make(RankCheckerService::class);
        $service->checkAndStore($keyword->id);
    }

    /** @test */
    public function it_does_not_emit_event_on_same_position(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id, 'url' => 'https://example.com']);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // Create previous ranking
        Ranking::create([
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now()->subDay(),
        ]);

        // Create the mock SERP result
        $serpResult = $this->createSerpResult('seo tools', 5, 'https://example.com/seo');

        // Mock SERP fetcher using instance binding
        $fetcherMock = \Mockery::mock(SERPFetcherInterface::class);
        $fetcherMock->shouldReceive('fetch')
            ->once()
            ->andReturn($serpResult);
        $this->app->instance(SERPFetcherInterface::class, $fetcherMock);

        // Mock webhook dispatcher - should NOT be called
        $dispatcherMock = \Mockery::mock(WebhookDispatcherService::class);
        $dispatcherMock->shouldNotReceive('dispatchToUserWebhooks');
        $this->app->instance(WebhookDispatcherService::class, $dispatcherMock);

        $service = $this->app->make(RankCheckerService::class);
        $service->checkAndStore($keyword->id);
    }

    /** @test */
    public function it_includes_serp_features_in_event(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id, 'url' => 'https://example.com']);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        Ranking::create([
            'keyword_id' => $keyword->id,
            'position' => 7,
            'checked_at' => now()->subDay(),
        ]);

        // Create webhook for user
        Webhook::create([
            'user_id' => $user->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'test_secret',
            'events' => ['ranking.changed'],
            'enabled' => true,
        ]);

        // Create the mock SERP result with features
        $serpResult = $this->createSerpResultWithFeatures(
            'seo tools',
            5,
            'https://example.com/seo',
            ['featured_snippet' => true, 'local_pack' => false]
        );

        // Mock SERP fetcher using instance binding
        $fetcherMock = \Mockery::mock(SERPFetcherInterface::class);
        $fetcherMock->shouldReceive('fetch')
            ->once()
            ->andReturn($serpResult);
        $this->app->instance(SERPFetcherInterface::class, $fetcherMock);

        // Mock webhook dispatcher using instance binding
        $dispatcherMock = \Mockery::mock(WebhookDispatcherService::class);
        $dispatcherMock->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) {
                return isset($data['serp_features'])
                    && $data['serp_features']['featured_snippet'] === true;
            })
            ->andReturn(1);
        $this->app->instance(WebhookDispatcherService::class, $dispatcherMock);

        $service = $this->app->make(RankCheckerService::class);
        $service->checkAndStore($keyword->id);
    }

    /**
     * Create a mock SERP result.
     */
    protected function createSerpResult(string $keyword, ?int $position, ?string $url): SERPResult
    {
        $entries = [];
        if ($position && $url) {
            for ($i = 1; $i <= 100; $i++) {
                if ($i === $position) {
                    $entries[] = new SERPEntryData($i, $url, "Title {$i}", "Description {$i}", true);
                } else {
                    $entries[] = new SERPEntryData($i, "https://other{$i}.com", "Title {$i}", "Description {$i}", false);
                }
            }
        } else {
            for ($i = 1; $i <= 100; $i++) {
                $entries[] = new SERPEntryData($i, "https://other{$i}.com", "Title {$i}", "Description {$i}", false);
            }
        }

        return new SERPResultData(
            keyword: $keyword,
            searchEngine: 'google',
            location: 'us',
            device: 'desktop',
            results: $entries,
            checkedAt: now()->toIso8601String(),
            error: null,
            durationMs: 100
        );
    }

    /**
     * Create a mock SERP result with SERP features.
     */
    protected function createSerpResultWithFeatures(string $keyword, ?int $position, ?string $url, array $features): SERPResult
    {
        $entries = [];
        if ($position && $url) {
            for ($i = 1; $i <= 100; $i++) {
                if ($i === $position) {
                    $entries[] = new SERPEntryData($i, $url, "Title {$i}", "Description {$i}", true);
                } else {
                    $entries[] = new SERPEntryData($i, "https://other{$i}.com", "Title {$i}", "Description {$i}", false);
                }
            }
        } else {
            for ($i = 1; $i <= 100; $i++) {
                $entries[] = new SERPEntryData($i, "https://other{$i}.com", "Title {$i}", "Description {$i}", false);
            }
        }

        return new SERPResultData(
            keyword: $keyword,
            searchEngine: 'google',
            location: 'us',
            device: 'desktop',
            results: $entries,
            checkedAt: now()->toIso8601String(),
            error: null,
            durationMs: 100,
            serpFeatures: $features
        );
    }
}

