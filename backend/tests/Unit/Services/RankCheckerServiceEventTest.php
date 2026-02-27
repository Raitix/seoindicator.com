<?php

namespace Tests\Unit\Services;

use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\Site;
use App\Models\User;
use App\Models\Webhook;
use App\Services\RankCheckerService;
use App\Services\SERPFetcherInterface;
use App\Services\SERP\SERPResult;
use App\Services\SERP\SERPEntry;
use App\Services\SERP\SERPOptionsData;
use App\Services\WebhookDispatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for RankCheckerService webhook event emission.
 *
 * @see TASK-6-16
 */
class RankCheckerServiceEventTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_emits_ranking_changed_event_on_position_improvement(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
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

        // Mock SERP fetcher
        $mockFetcher = Mockery::mock(SERPFetcherInterface::class);
        $mockFetcher->shouldReceive('fetch')
            ->once()
            ->andReturn($this->createSerpResult('seo tools', 5, 'https://example.com/seo'));

        // Mock webhook dispatcher
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) use ($user) {
                return $userId === $user->id
                    && $eventType === 'ranking.changed'
                    && $data['old_position'] === 7
                    && $data['new_position'] === 5
                    && $data['change'] === 2; // improvement
            })
            ->andReturn(1);

        $service = new RankCheckerService($mockFetcher);
        $service->setWebhookDispatcher($mockDispatcher);

        $result = $service->checkAndStore($keyword->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_emits_ranking_changed_event_on_position_decline(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
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

        // Mock SERP fetcher
        $mockFetcher = Mockery::mock(SERPFetcherInterface::class);
        $mockFetcher->shouldReceive('fetch')
            ->once()
            ->andReturn($this->createSerpResult('seo tools', 10, 'https://example.com/seo'));

        // Mock webhook dispatcher
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) {
                return $data['old_position'] === 5
                    && $data['new_position'] === 10
                    && $data['change'] === -5; // decline
            })
            ->andReturn(1);

        $service = new RankCheckerService($mockFetcher);
        $service->setWebhookDispatcher($mockDispatcher);

        $service->checkAndStore($keyword->id);
    }

    /** @test */
    public function it_emits_ranking_changed_event_on_first_ranking(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // No previous ranking

        // Mock SERP fetcher
        $mockFetcher = Mockery::mock(SERPFetcherInterface::class);
        $mockFetcher->shouldReceive('fetch')
            ->once()
            ->andReturn($this->createSerpResult('seo tools', 15, 'https://example.com/seo'));

        // Mock webhook dispatcher
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) {
                return $data['old_position'] === null
                    && $data['new_position'] === 15
                    && $data['change'] === null; // first ranking
            })
            ->andReturn(1);

        $service = new RankCheckerService($mockFetcher);
        $service->setWebhookDispatcher($mockDispatcher);

        $service->checkAndStore($keyword->id);
    }

    /** @test */
    public function it_emits_ranking_changed_event_on_drop_from_top_100(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
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

        // Mock SERP fetcher - not found in top 100
        $mockFetcher = Mockery::mock(SERPFetcherInterface::class);
        $mockFetcher->shouldReceive('fetch')
            ->once()
            ->andReturn($this->createSerpResult('seo tools', null, null));

        // Mock webhook dispatcher
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) {
                return $data['old_position'] === 50
                    && $data['new_position'] === null
                    && $data['change'] === null; // dropped out
            })
            ->andReturn(1);

        $service = new RankCheckerService($mockFetcher);
        $service->setWebhookDispatcher($mockDispatcher);

        $service->checkAndStore($keyword->id);
    }

    /** @test */
    public function it_does_not_emit_event_on_same_position(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
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

        // Mock SERP fetcher
        $mockFetcher = Mockery::mock(SERPFetcherInterface::class);
        $mockFetcher->shouldReceive('fetch')
            ->once()
            ->andReturn($this->createSerpResult('seo tools', 5, 'https://example.com/seo'));

        // Mock webhook dispatcher - should NOT be called
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldNotReceive('dispatchToUserWebhooks');

        $service = new RankCheckerService($mockFetcher);
        $service->setWebhookDispatcher($mockDispatcher);

        $service->checkAndStore($keyword->id);
    }

    /** @test */
    public function it_includes_serp_features_in_event(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
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

        // Mock SERP fetcher with features
        $mockFetcher = Mockery::mock(SERPFetcherInterface::class);
        $mockFetcher->shouldReceive('fetch')
            ->once()
            ->andReturn($this->createSerpResultWithFeatures(
                'seo tools',
                5,
                'https://example.com/seo',
                ['featured_snippet' => true, 'local_pack' => false]
            ));

        // Mock webhook dispatcher
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) {
                return isset($data['serp_features'])
                    && $data['serp_features']['featured_snippet'] === true;
            })
            ->andReturn(1);

        $service = new RankCheckerService($mockFetcher);
        $service->setWebhookDispatcher($mockDispatcher);

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
                    $entries[] = new SERPEntry($i, $url, "Title {$i}", "Description {$i}");
                } else {
                    $entries[] = new SERPEntry($i, "https://other{$i}.com", "Title {$i}", "Description {$i}");
                }
            }
        } else {
            for ($i = 1; $i <= 100; $i++) {
                $entries[] = new SERPEntry($i, "https://other{$i}.com", "Title {$i}", "Description {$i}");
            }
        }

        return new SERPResult($keyword, 'google', 'us', 'desktop', $entries, 100);
    }

    /**
     * Create a mock SERP result with SERP features.
     */
    protected function createSerpResultWithFeatures(string $keyword, ?int $position, ?string $url, array $features): SERPResult
    {
        $result = $this->createSerpResult($keyword, $position, $url);
        // Add SERP features if the class supports it
        if (method_exists($result, 'setSerpFeatures')) {
            $result->setSerpFeatures($features);
        }
        return $result;
    }
}
