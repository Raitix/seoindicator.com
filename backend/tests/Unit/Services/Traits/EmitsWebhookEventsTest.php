<?php

namespace Tests\Unit\Services\Traits;

use App\Models\User;
use App\Models\Webhook;
use App\Services\Traits\EmitsWebhookEvents;
use App\Services\WebhookDispatcherService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for EmitsWebhookEvents trait.
 *
 * @see TASK-6-16
 */
class EmitsWebhookEventsTest extends TestCase
{
    /**
     * Test class using the trait.
     */
    protected function getTestClass(): object
    {
        return new class {
            use EmitsWebhookEvents;
        };
    }

    /** @test */
    public function it_emits_ranking_changed_event_with_correct_payload(): void
    {
        $userId = 1;
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userIdArg, $eventType, $data) use ($userId) {
                return $userIdArg === $userId
                    && $eventType === 'ranking.changed'
                    && $data['keyword_id'] === 1
                    && $data['keyword'] === 'seo tools'
                    && $data['site_id'] === 1
                    && $data['old_position'] === 7
                    && $data['new_position'] === 5
                    && $data['change'] === 2; // improvement
            })
            ->andReturn(1);

        $testClass = $this->getTestClass();
        $testClass->setWebhookDispatcher($mockDispatcher);

        $result = $testClass->emitRankingChangedEvent(
            userId: $userId,
            keywordId: 1,
            keyword: 'seo tools',
            siteId: 1,
            oldPosition: 7,
            newPosition: 5
        );

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_calculates_position_change_correctly(): void
    {
        $testClass = $this->getTestClass();

        // Improvement (moved up)
        $this->assertEquals(2, $testClass->calculatePositionChange(7, 5));

        // Decline (moved down)
        $this->assertEquals(-3, $testClass->calculatePositionChange(5, 8));

        // No previous (first ranking)
        $this->assertNull($testClass->calculatePositionChange(null, 5));

        // Dropped out
        $this->assertNull($testClass->calculatePositionChange(10, null));
    }

    /** @test */
    public function it_detects_position_changes_correctly(): void
    {
        $testClass = $this->getTestClass();

        // Position changed
        $this->assertTrue($testClass->hasPositionChanged(7, 5));

        // Position unchanged
        $this->assertFalse($testClass->hasPositionChanged(5, 5));

        // First ranking
        $this->assertTrue($testClass->hasPositionChanged(null, 5));

        // Dropped out
        $this->assertTrue($testClass->hasPositionChanged(10, null));

        // Both null (no ranking)
        $this->assertFalse($testClass->hasPositionChanged(null, null));
    }

    /** @test */
    public function it_emits_keyword_added_event_with_correct_payload(): void
    {
        $userId = 1;
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userIdArg, $eventType, $data) use ($userId) {
                return $userIdArg === $userId
                    && $eventType === 'keyword.added'
                    && $data['keyword_id'] === 1
                    && $data['keyword'] === 'seo tools'
                    && $data['site_id'] === 1
                    && $data['target_url'] === 'https://example.com/seo'
                    && $data['search_engine'] === 'google'
                    && $data['location'] === 'us'
                    && $data['device'] === 'desktop';
            })
            ->andReturn(1);

        $testClass = $this->getTestClass();
        $testClass->setWebhookDispatcher($mockDispatcher);

        $result = $testClass->emitKeywordAddedEvent(
            userId: $userId,
            keywordId: 1,
            keyword: 'seo tools',
            siteId: 1,
            targetUrl: 'https://example.com/seo'
        );

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_emits_keyword_removed_event_with_correct_payload(): void
    {
        $userId = 1;
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userIdArg, $eventType, $data) use ($userId) {
                return $userIdArg === $userId
                    && $eventType === 'keyword.removed'
                    && $data['keyword_id'] === 1
                    && $data['keyword'] === 'seo tools'
                    && $data['site_id'] === 1
                    && isset($data['removed_at']);
            })
            ->andReturn(1);

        $testClass = $this->getTestClass();
        $testClass->setWebhookDispatcher($mockDispatcher);

        $result = $testClass->emitKeywordRemovedEvent(
            userId: $userId,
            keywordId: 1,
            keyword: 'seo tools',
            siteId: 1
        );

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_emits_report_completed_event_with_correct_payload(): void
    {
        $userId = 1;
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userIdArg, $eventType, $data) use ($userId) {
                return $userIdArg === $userId
                    && $eventType === 'report.completed'
                    && $data['report_id'] === 1
                    && $data['site_id'] === 1
                    && $data['type'] === 'weekly'
                    && $data['format'] === 'pdf'
                    && $data['download_url'] === '/api/v1/reports/1/download'
                    && $data['file_size'] === 1024
                    && $data['scheduled_report_id'] === 5
                    && $data['date_range']['start'] === '2026-02-20'
                    && $data['date_range']['end'] === '2026-02-27'
                    && isset($data['generated_at']);
            })
            ->andReturn(1);

        $testClass = $this->getTestClass();
        $testClass->setWebhookDispatcher($mockDispatcher);

        $result = $testClass->emitReportCompletedEvent(
            userId: $userId,
            reportId: 1,
            siteId: 1,
            type: 'weekly',
            format: 'pdf',
            downloadUrl: '/api/v1/reports/1/download',
            fileSize: 1024,
            scheduledReportId: 5,
            dateRange: ['start' => '2026-02-20', 'end' => '2026-02-27']
        );

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_emits_alert_triggered_event_with_correct_payload(): void
    {
        $userId = 1;
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userIdArg, $eventType, $data) use ($userId) {
                return $userIdArg === $userId
                    && $eventType === 'alert.triggered'
                    && $data['alert_id'] === 1
                    && $data['alert_name'] === 'Position Drop Alert'
                    && $data['keyword_id'] === 1
                    && $data['keyword'] === 'seo tools'
                    && $data['site_id'] === 1
                    && $data['condition'] === 'position_drop'
                    && $data['threshold'] === 5
                    && $data['previous_value'] === 5
                    && $data['current_value'] === 15
                    && isset($data['triggered_at']);
            })
            ->andReturn(1);

        $testClass = $this->getTestClass();
        $testClass->setWebhookDispatcher($mockDispatcher);

        $result = $testClass->emitAlertTriggeredEvent(
            userId: $userId,
            alertId: 1,
            alertName: 'Position Drop Alert',
            keywordId: 1,
            keyword: 'seo tools',
            siteId: 1,
            condition: 'position_drop',
            threshold: 5,
            previousValue: 5,
            currentValue: 15
        );

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_handles_dispatcher_exceptions_gracefully(): void
    {
        $userId = 1;

        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->andThrow(new \Exception('Connection error'));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Webhook event emission failed');
            });

        $testClass = $this->getTestClass();
        $testClass->setWebhookDispatcher($mockDispatcher);

        // Should not throw, should return 0
        $result = $testClass->emitRankingChangedEvent(
            userId: $userId,
            keywordId: 1,
            keyword: 'seo tools',
            siteId: 1,
            oldPosition: 7,
            newPosition: 5
        );

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_includes_serp_features_in_ranking_event(): void
    {
        $userId = 1;
        $mockDispatcher = Mockery::mock(WebhookDispatcherService::class);
        $mockDispatcher->shouldReceive('dispatchToUserWebhooks')
            ->once()
            ->withArgs(function ($userIdArg, $eventType, $data) {
                return isset($data['serp_features'])
                    && $data['serp_features']['featured_snippet'] === true
                    && $data['serp_features']['local_pack'] === false;
            })
            ->andReturn(1);

        $testClass = $this->getTestClass();
        $testClass->setWebhookDispatcher($mockDispatcher);

        $testClass->emitRankingChangedEvent(
            userId: $userId,
            keywordId: 1,
            keyword: 'seo tools',
            siteId: 1,
            oldPosition: 7,
            newPosition: 5,
            serpFeatures: ['featured_snippet' => true, 'local_pack' => false]
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
