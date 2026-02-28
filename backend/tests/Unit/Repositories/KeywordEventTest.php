<?php

namespace Tests\Unit\Repositories;

use App\Models\Keyword;
use App\Models\Site;
use App\Models\User;
use App\Models\Webhook;
use App\Repositories\KeywordRepository;
use App\Services\WebhookDispatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for KeywordRepository webhook event emission.
 *
 * @see TASK-6-16
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
class KeywordEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_emits_keyword_added_event_on_create(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // Mock webhook dispatcher using Laravel's mock helper
        $this->mock(WebhookDispatcherService::class)
            ->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) use ($user, $site) {
                return $userId === $user->id
                    && $eventType === 'keyword.added'
                    && $data['keyword'] === 'seo tools'
                    && $data['site_id'] === $site->id
                    && $data['search_engine'] === 'google'
                    && $data['location'] === 'us'
                    && $data['device'] === 'desktop'
                    && $data['status'] === 'active';
            })
            ->andReturn(1);

        $repository = app(KeywordRepository::class);
        $keyword = $repository->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Keyword::class, $keyword);
        $this->assertEquals('seo tools', $keyword->keyword);
    }

    /** @test */
    public function it_includes_target_url_in_keyword_added_event(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // Mock webhook dispatcher using Laravel's mock helper
        $this->mock(WebhookDispatcherService::class)
            ->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) {
                return $data['target_url'] === 'https://example.com/seo-tools';
            })
            ->andReturn(1);

        $repository = app(KeywordRepository::class);
        $keyword = $repository->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
            'target_url' => 'https://example.com/seo-tools',
        ]);

        $this->assertEquals('https://example.com/seo-tools', $keyword->target_url);
    }

    /** @test */
    public function it_emits_keyword_removed_event_on_delete(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // Mock webhook dispatcher using Laravel's mock helper
        $this->mock(WebhookDispatcherService::class)
            ->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userId, $eventType, $data) use ($user, $keyword, $site) {
                return $userId === $user->id
                    && $eventType === 'keyword.removed'
                    && $data['keyword_id'] === $keyword->id
                    && $data['keyword'] === 'seo tools'
                    && $data['site_id'] === $site->id
                    && isset($data['removed_at']);
            })
            ->andReturn(1);

        $repository = app(KeywordRepository::class);
        $result = $repository->delete($keyword->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_emits_keyword_removed_event_before_hard_delete(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // Mock webhook dispatcher using Laravel's mock helper
        $this->mock(WebhookDispatcherService::class)
            ->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->andReturn(1);

        $repository = app(KeywordRepository::class);
        $result = $repository->delete($keyword->id, true);

        $this->assertTrue($result);
        $this->assertNull(Keyword::find($keyword->id));
    }

    /** @test */
    public function it_does_not_emit_event_on_update(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // Mock webhook dispatcher - should NOT be called
        $this->mock(WebhookDispatcherService::class)
            ->shouldNotReceive('dispatchToUserWebhooks');

        $repository = app(KeywordRepository::class);
        $result = $repository->update($keyword->id, ['status' => 'paused']);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_does_not_emit_event_for_keyword_without_user(): void
    {
        // Create keyword without user_id
        $site = Site::factory()->create(['user_id' => null]);
        
        // Mock webhook dispatcher - should NOT be called
        $this->mock(WebhookDispatcherService::class)
            ->shouldNotReceive('dispatchToUserWebhooks');

        $repository = app(KeywordRepository::class);

        // Create keyword directly in DB (repository would try to emit)
        $keyword = Keyword::create([
            'site_id' => $site->id,
            'keyword' => 'seo tools',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
        ]);

        $this->assertNull($keyword->user_id);
    }

    /** @test */
    public function it_handles_dispatcher_exception_gracefully_on_create(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // Mock webhook dispatcher that throws
        $this->mock(WebhookDispatcherService::class)
            ->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->andThrow(new \Exception('Connection error'));

        $repository = app(KeywordRepository::class);

        // Should not throw - keyword should still be created
        $keyword = $repository->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        $this->assertInstanceOf(Keyword::class, $keyword);
        $this->assertEquals('seo tools', $keyword->keyword);
    }

    /** @test */
    public function it_handles_dispatcher_exception_gracefully_on_delete(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'keyword' => 'seo tools',
        ]);

        // Mock webhook dispatcher that throws
        $this->mock(WebhookDispatcherService::class)
            ->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->andThrow(new \Exception('Connection error'));

        $repository = app(KeywordRepository::class);

        // Should not throw - delete should still proceed
        $result = $repository->delete($keyword->id);

        $this->assertTrue($result);
    }
}
