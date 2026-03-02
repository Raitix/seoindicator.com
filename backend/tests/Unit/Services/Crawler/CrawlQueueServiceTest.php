<?php

namespace Tests\Unit\Services\Crawler;

use Tests\TestCase;
use App\Services\Crawler\CrawlQueueService;
use App\Services\Crawler\UrlNormalizer;
use App\Services\Crawler\Data\QueueItem;
use App\Services\Crawler\Data\QueueStats;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for the CrawlQueueService.
 *
 * @see TASK-22-10
 * @see SPEC-022
 */
class CrawlQueueServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Service Instantiation Tests (LQ-001)
    // ==========================================

    /**
     * Test service can be instantiated via container.
     * LQ-001-01: Service instantiation
     */
    public function test_service_can_be_instantiated(): void
    {
        $service = app(CrawlQueueService::class);
        $this->assertInstanceOf(CrawlQueueService::class, $service);
    }

    /**
     * Test service has config injected.
     * LQ-001-02: Config injection
     */
    public function test_service_has_config_injected(): void
    {
        config(['crawler.max_concurrent_pages' => 5]);
        config(['crawler.queue_retry_attempts' => 3]);
        config(['crawler.queue_lock_timeout' => 600]);

        $service = app(CrawlQueueService::class);

        $this->assertEquals(5, $service->getMaxConcurrentPages());
        $this->assertEquals(3, $service->getRetryAttempts());
        $this->assertEquals(600, $service->getLockTimeout());
    }

    /**
     * Test service has logger injected.
     * LQ-001-03: Logger injection
     */
    public function test_service_has_logger_injected(): void
    {
        $service = app(CrawlQueueService::class);
        $this->assertNotNull($service->getLogger());
    }

    /**
     * Test service loads default settings.
     * LQ-001-04: Default settings
     */
    public function test_service_loads_default_settings(): void
    {
        $service = app(CrawlQueueService::class);

        // Default values from config
        $this->assertEquals(3, $service->getMaxConcurrentPages());
        $this->assertEquals(2, $service->getRetryAttempts());
        $this->assertEquals(300, $service->getLockTimeout());
        $this->assertEquals('set', $service->getDedupMethod());
    }

    // ==========================================
    // addToQueue() Method Tests (LQ-002)
    // ==========================================

    /**
     * Test add single link to queue.
     * LQ-002-01: Add single link
     */
    public function test_add_to_queue_single_link(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page', 'text' => 'Page'],
        ];

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertCount(1, $queue);
        $this->assertEquals('https://example.com/page', $queue[0]['url']);
    }

    /**
     * Test add multiple links to queue.
     * LQ-002-02: Add multiple links
     */
    public function test_add_to_queue_multiple_links(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page1', 'text' => 'Page 1'],
            ['url' => 'https://example.com/page2', 'text' => 'Page 2'],
            ['url' => 'https://example.com/page3', 'text' => 'Page 3'],
        ];

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertCount(3, $queue);
    }

    /**
     * Test add empty array to queue.
     * LQ-002-03: Add empty array
     */
    public function test_add_to_queue_empty_array(): void
    {
        $service = app(CrawlQueueService::class);

        $service->addToQueue('job-1', [], 1);

        $queue = $service->getQueue('job-1');

        $this->assertCount(0, $queue);
    }

    /**
     * Test priority set by depth 0.
     * LQ-002-04: Priority high (depth 0)
     */
    public function test_add_to_queue_priority_high(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com']];

        $service->addToQueue('job-1', $links, 0);

        $queue = $service->getQueue('job-1');

        // Depth 0 = high priority (67-100)
        $this->assertGreaterThanOrEqual(67, $queue[0]['priority']);
    }

    /**
     * Test priority set by depth 1.
     * LQ-002-05: Priority medium (depth 1)
     */
    public function test_add_to_queue_priority_medium(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        // Depth 1 = medium priority (34-66)
        $this->assertGreaterThanOrEqual(34, $queue[0]['priority']);
        $this->assertLessThanOrEqual(66, $queue[0]['priority']);
    }

    /**
     * Test priority set by depth 2.
     * LQ-002-06: Priority low (depth 2)
     */
    public function test_add_to_queue_priority_low(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page/sub']];

        $service->addToQueue('job-1', $links, 2);

        $queue = $service->getQueue('job-1');

        // Depth 2 = low priority (0-33)
        $this->assertLessThanOrEqual(33, $queue[0]['priority']);
    }

    /**
     * Test store parent URL.
     * LQ-002-07: Store parent URL
     */
    public function test_add_to_queue_stores_parent_url(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];

        $service->addToQueue('job-1', $links, 1, 'https://example.com');

        $queue = $service->getQueue('job-1');

        $this->assertEquals('https://example.com', $queue[0]['parent_url']);
    }

    /**
     * Test status set to pending.
     * LQ-002-08: Set status pending
     */
    public function test_add_to_queue_sets_status_pending(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertEquals('pending', $queue[0]['status']);
    }

    /**
     * Test skip duplicate URL.
     * LQ-002-09: Skip duplicate
     */
    public function test_add_to_queue_skips_duplicate(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/page'], // duplicate
        ];

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertCount(1, $queue);
    }

    /**
     * Test skip normalized duplicate.
     * LQ-002-10: Skip normalized duplicate
     */
    public function test_add_to_queue_skips_normalized_duplicate(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/page/'], // trailing slash
        ];

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertCount(1, $queue);
    }

    /**
     * Test store timestamp.
     * LQ-002-11: Store timestamp
     */
    public function test_add_to_queue_stores_timestamp(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertNotNull($queue[0]['created_at']);
    }

    /**
     * Test generate unique ID.
     * LQ-002-12: Generate unique ID
     */
    public function test_add_to_queue_generates_unique_id(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2'],
        ];

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertNotEquals($queue[0]['id'], $queue[1]['id']);
    }

    /**
     * Test associate with crawl job.
     * LQ-002-13: Associate with crawl job
     */
    public function test_add_to_queue_associates_with_job(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];

        $service->addToQueue('job-123', $links, 1);

        $queue = $service->getQueue('job-123');

        $this->assertEquals('job-123', $queue[0]['crawl_job_id']);
    }

    // ==========================================
    // getNextItem() Method Tests (LQ-003)
    // ==========================================

    /**
     * Test get next pending item.
     * LQ-003-01: Get next pending
     */
    public function test_get_next_item_returns_pending(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];

        $service->addToQueue('job-1', $links, 1);

        $item = $service->getNextItem('job-1');

        $this->assertNotNull($item);
        $this->assertEquals('pending', $item['status']);
    }

    /**
     * Test order by priority.
     * LQ-003-02: Order by priority
     */
    public function test_get_next_item_orders_by_priority(): void
    {
        $service = app(CrawlQueueService::class);

        // Add low priority first (depth 2)
        $service->addToQueue('job-1', [['url' => 'https://example.com/low']], 2);
        // Add high priority (depth 0)
        $service->addToQueue('job-1', [['url' => 'https://example.com/high']], 0);
        // Add medium priority (depth 1)
        $service->addToQueue('job-1', [['url' => 'https://example.com/medium']], 1);

        $item = $service->getNextItem('job-1');

        // High priority should come first
        $this->assertStringContainsString('high', $item['url']);
    }

    /**
     * Test order by created_at.
     * LQ-003-03: Order by created_at
     */
    public function test_get_next_item_orders_by_created_at(): void
    {
        $service = app(CrawlQueueService::class);

        // Add links at same depth (same priority)
        $service->addToQueue('job-1', [['url' => 'https://example.com/first']], 1);
        usleep(10000); // 10ms delay
        $service->addToQueue('job-1', [['url' => 'https://example.com/second']], 1);

        $item = $service->getNextItem('job-1');

        // Oldest should come first
        $this->assertStringContainsString('first', $item['url']);
    }

    /**
     * Test empty queue returns null.
     * LQ-003-04: Empty queue
     */
    public function test_get_next_item_empty_queue(): void
    {
        $service = app(CrawlQueueService::class);

        $item = $service->getNextItem('job-nonexistent');

        $this->assertNull($item);
    }

    /**
     * Test no pending items.
     * LQ-003-05: No pending items
     */
    public function test_get_next_item_no_pending(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];

        $service->addToQueue('job-1', $links, 1);

        // Mark as completed
        $queue = $service->getQueue('job-1');
        $service->markCompleted($queue[0]['id']);

        $item = $service->getNextItem('job-1');

        $this->assertNull($item);
    }

    /**
     * Test skip locked items.
     * LQ-003-06: Skip locked items
     */
    public function test_get_next_item_skips_locked(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/locked'],
            ['url' => 'https://example.com/unlocked'],
        ];

        $service->addToQueue('job-1', $links, 1);

        // Lock first item
        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id'], 'worker-1');

        // Get next should skip locked item
        $item = $service->getNextItem('job-1');

        $this->assertStringContainsString('unlocked', $item['url']);
    }

    /**
     * Test filter by job ID.
     * LQ-003-07: Filter by job ID
     */
    public function test_get_next_item_filters_by_job(): void
    {
        $service = app(CrawlQueueService::class);

        $service->addToQueue('job-1', [['url' => 'https://example.com/job1']], 1);
        $service->addToQueue('job-2', [['url' => 'https://example.com/job2']], 1);

        $item = $service->getNextItem('job-1');

        $this->assertStringContainsString('job1', $item['url']);
    }

    // ==========================================
    // markProcessing() Method Tests (LQ-004)
    // ==========================================

    /**
     * Test mark item processing.
     * LQ-004-01: Mark processing
     */
    public function test_mark_processing(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals('processing', $updated['status']);
    }

    /**
     * Test set lock timestamp.
     * LQ-004-02: Set lock timestamp
     */
    public function test_mark_processing_sets_lock_timestamp(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);

        $updated = $service->getItem($queue[0]['id']);

        $this->assertNotNull($updated['locked_at']);
    }

    /**
     * Test set lock owner.
     * LQ-004-03: Set lock owner
     */
    public function test_mark_processing_sets_lock_owner(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id'], 'worker-123');

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals('worker-123', $updated['locked_by']);
    }

    /**
     * Test increment attempts.
     * LQ-004-04: Increment attempts
     */
    public function test_mark_processing_increments_attempts(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals(1, $updated['attempts']);
    }

    /**
     * Test update timestamp.
     * LQ-004-05: Update timestamp
     */
    public function test_mark_processing_updates_timestamp(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $originalUpdatedAt = $queue[0]['updated_at'];

        usleep(10000); // 10ms delay
        $service->markProcessing($queue[0]['id']);

        $updated = $service->getItem($queue[0]['id']);

        $this->assertNotEquals($originalUpdatedAt, $updated['updated_at']);
    }

    /**
     * Test item not found throws exception.
     * LQ-004-06: Item not found
     */
    public function test_mark_processing_item_not_found(): void
    {
        $service = app(CrawlQueueService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->markProcessing('nonexistent-id');
    }

    // ==========================================
    // markCompleted() Method Tests (LQ-005)
    // ==========================================

    /**
     * Test mark item completed.
     * LQ-005-01: Mark completed
     */
    public function test_mark_completed(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markCompleted($queue[0]['id']);

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals('completed', $updated['status']);
    }

    /**
     * Test clear lock on complete.
     * LQ-005-02: Clear lock
     */
    public function test_mark_completed_clears_lock(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markCompleted($queue[0]['id']);

        $updated = $service->getItem($queue[0]['id']);

        $this->assertNull($updated['locked_at']);
        $this->assertNull($updated['locked_by']);
    }

    /**
     * Test update timestamp on complete.
     * LQ-005-03: Update timestamp
     */
    public function test_mark_completed_updates_timestamp(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $originalUpdatedAt = $service->getItem($queue[0]['id'])['updated_at'];

        usleep(10000);
        $service->markCompleted($queue[0]['id']);

        $updated = $service->getItem($queue[0]['id']);

        $this->assertNotEquals($originalUpdatedAt, $updated['updated_at']);
    }

    /**
     * Test item not found throws exception.
     * LQ-005-04: Item not found
     */
    public function test_mark_completed_item_not_found(): void
    {
        $service = app(CrawlQueueService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->markCompleted('nonexistent-id');
    }

    // ==========================================
    // markFailed() Method Tests (LQ-006)
    // ==========================================

    /**
     * Test mark item failed.
     * LQ-006-01: Mark failed
     */
    public function test_mark_failed(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markFailed($queue[0]['id'], 'Connection timeout');

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals('failed', $updated['status']);
    }

    /**
     * Test store error message.
     * LQ-006-02: Store error message
     */
    public function test_mark_failed_stores_error(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markFailed($queue[0]['id'], 'Connection timeout');

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals('Connection timeout', $updated['error']);
    }

    /**
     * Test clear lock on fail.
     * LQ-006-03: Clear lock
     */
    public function test_mark_failed_clears_lock(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markFailed($queue[0]['id'], 'Error');

        $updated = $service->getItem($queue[0]['id']);

        $this->assertNull($updated['locked_at']);
    }

    /**
     * Test retry if under max attempts.
     * LQ-006-06: Retry allowed
     */
    public function test_mark_failed_allows_retry(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']); // attempt 1

        // First failure should allow retry
        $service->markFailed($queue[0]['id'], 'Server error 500');

        $updated = $service->getItem($queue[0]['id']);

        // Should be back to pending for retry
        $this->assertEquals('pending', $updated['status']);
    }

    /**
     * Test no retry if over max attempts.
     * LQ-006-07: No retry
     */
    public function test_mark_failed_no_retry_over_max(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        // Exhaust retries (default max = 2)
        $service->markProcessing($queue[0]['id']); // attempt 1
        $service->markFailed($queue[0]['id'], 'Error 1');

        $item = $service->getItem($queue[0]['id']);
        $service->markProcessing($queue[0]['id']); // attempt 2
        $service->markFailed($queue[0]['id'], 'Error 2');

        $updated = $service->getItem($queue[0]['id']);

        // Should stay failed
        $this->assertEquals('failed', $updated['status']);
        $this->assertEquals(2, $updated['attempts']);
    }

    /**
     * Test no retry on 404 error.
     * LQ-006-08: No retry on 404
     */
    public function test_mark_failed_no_retry_on_404(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markFailed($queue[0]['id'], 'HTTP 404 Not Found');

        $updated = $service->getItem($queue[0]['id']);

        // Should stay failed, no retry
        $this->assertEquals('failed', $updated['status']);
    }

    // ==========================================
    // markSkipped() Method Tests (LQ-007)
    // ==========================================

    /**
     * Test mark item skipped.
     * LQ-007-01: Mark skipped
     */
    public function test_mark_skipped(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markSkipped($queue[0]['id'], 'Redirect to external domain');

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals('skipped', $updated['status']);
    }

    /**
     * Test store skip reason.
     * LQ-007-02: Store skip reason
     */
    public function test_mark_skipped_stores_reason(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markSkipped($queue[0]['id'], 'Redirect to external domain');

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals('Redirect to external domain', $updated['error']);
    }

    /**
     * Test clear lock on skip.
     * LQ-007-03: Clear lock
     */
    public function test_mark_skipped_clears_lock(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markSkipped($queue[0]['id'], 'Reason');

        $updated = $service->getItem($queue[0]['id']);

        $this->assertNull($updated['locked_at']);
    }

    // ==========================================
    // getQueueStats() Method Tests (LQ-008)
    // ==========================================

    /**
     * Test get stats empty queue.
     * LQ-008-01: Empty queue stats
     */
    public function test_get_queue_stats_empty(): void
    {
        $service = app(CrawlQueueService::class);

        $stats = $service->getQueueStats('job-nonexistent');

        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['pending']);
        $this->assertEquals(0, $stats['processing']);
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['skipped']);
    }

    /**
     * Test get stats with items.
     * LQ-008-02: Stats with items
     */
    public function test_get_queue_stats_with_items(): void
    {
        $service = app(CrawlQueueService::class);

        // Add items
        $service->addToQueue('job-1', [
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2'],
            ['url' => 'https://example.com/page3'],
        ], 1);

        // Process one
        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);

        $stats = $service->getQueueStats('job-1');

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['pending']);
        $this->assertEquals(1, $stats['processing']);
    }

    /**
     * Test count completed items.
     * LQ-008-05: Count completed
     */
    public function test_get_queue_stats_counts_completed(): void
    {
        $service = app(CrawlQueueService::class);

        $service->addToQueue('job-1', [
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2'],
        ], 1);

        $queue = $service->getQueue('job-1');

        $service->markProcessing($queue[0]['id']);
        $service->markCompleted($queue[0]['id']);

        $stats = $service->getQueueStats('job-1');

        $this->assertEquals(1, $stats['completed']);
    }

    /**
     * Test count failed items.
     * LQ-008-06: Count failed
     */
    public function test_get_queue_stats_counts_failed(): void
    {
        $service = app(CrawlQueueService::class);

        $service->addToQueue('job-1', [
            ['url' => 'https://example.com/page1'],
        ], 1);

        $queue = $service->getQueue('job-1');

        $service->markProcessing($queue[0]['id']);
        $service->markProcessing($queue[0]['id']); // attempt 2 to exhaust retries
        $service->markFailed($queue[0]['id'], 'HTTP 404');

        $stats = $service->getQueueStats('job-1');

        $this->assertEquals(1, $stats['failed']);
    }

    /**
     * Test count skipped items.
     * LQ-008-07: Count skipped
     */
    public function test_get_queue_stats_counts_skipped(): void
    {
        $service = app(CrawlQueueService::class);

        $service->addToQueue('job-1', [
            ['url' => 'https://example.com/page1'],
        ], 1);

        $queue = $service->getQueue('job-1']);

        $service->markSkipped($queue[0]['id'], 'External redirect');

        $stats = $service->getQueueStats('job-1');

        $this->assertEquals(1, $stats['skipped']);
    }

    // ==========================================
    // Duplicate Detection Tests (LQ-009)
    // ==========================================

    /**
     * Test detect exact duplicate.
     * LQ-009-01: Exact duplicate
     */
    public function test_duplicate_detection_exact(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/page'],
        ];

        $service->addToQueue('job-1', $links, 1);

        $this->assertCount(1, $service->getQueue('job-1'));
    }

    /**
     * Test detect normalized duplicate.
     * LQ-009-02: Normalized duplicate
     */
    public function test_duplicate_detection_normalized(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://example.com/page/'],
        ];

        $service->addToQueue('job-1', $links, 1);

        $this->assertCount(1, $service->getQueue('job-1'));
    }

    /**
     * Test detect with www prefix.
     * LQ-009-03: www prefix duplicate
     */
    public function test_duplicate_detection_www_prefix(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page'],
            ['url' => 'https://www.example.com/page'],
        ];

        $service->addToQueue('job-1', $links, 1);

        // Both should be added (different hosts)
        $this->assertCount(2, $service->getQueue('job-1'));
    }

    /**
     * Test detect with query order.
     * LQ-009-05: Query param order
     */
    public function test_duplicate_detection_query_order(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page?a=1&b=2'],
            ['url' => 'https://example.com/page?b=2&a=1'],
        ];

        $service->addToQueue('job-1', $links, 1);

        // After normalization (sorted params), these should be duplicates
        $this->assertCount(1, $service->getQueue('job-1'));
    }

    // ==========================================
    // Retry Logic Tests (LQ-010)
    // ==========================================

    /**
     * Test first attempt.
     * LQ-010-01: First attempt
     */
    public function test_retry_first_attempt(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertEquals(0, $queue[0]['attempts']);
    }

    /**
     * Test second attempt.
     * LQ-010-02: Second attempt
     */
    public function test_retry_second_attempt(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals(1, $updated['attempts']);
    }

    /**
     * Test max attempts reached.
     * LQ-010-03: Max attempts
     */
    public function test_retry_max_attempts(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        // Attempt 1
        $service->markProcessing($queue[0]['id']);
        $service->markFailed($queue[0]['id'], 'Error');

        // Attempt 2
        $service->markProcessing($queue[0]['id']);
        $service->markFailed($queue[0]['id'], 'Error');

        $updated = $service->getItem($queue[0]['id']);

        $this->assertEquals(2, $updated['attempts']);
        $this->assertEquals('failed', $updated['status']);
    }

    /**
     * Test retry on 500 error.
     * LQ-010-11: Retry on 500
     */
    public function test_retry_on_500_error(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markFailed($queue[0]['id'], 'HTTP 500 Internal Server Error');

        $updated = $service->getItem($queue[0]['id']);

        // Should be pending for retry
        $this->assertEquals('pending', $updated['status']);
    }

    /**
     * Test no retry on 400 error.
     * LQ-010-07: No retry on 400
     */
    public function test_no_retry_on_400_error(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markFailed($queue[0]['id'], 'HTTP 400 Bad Request');

        $updated = $service->getItem($queue[0]['id']);

        // Should stay failed
        $this->assertEquals('failed', $updated['status']);
    }

    // ==========================================
    // Concurrent Processing Tests (LQ-012)
    // ==========================================

    /**
     * Test lock item on processing.
     * LQ-012-01: Lock on processing
     */
    public function test_concurrent_lock_on_processing(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $item = $service->getNextItem('job-1');

        // Item should be returned and locked
        $this->assertNotNull($item);
        $this->assertNotNull($item['locked_at']);
    }

    /**
     * Test multiple workers.
     * LQ-012-03: Multiple workers
     */
    public function test_concurrent_multiple_workers(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2'],
            ['url' => 'https://example.com/page3'],
        ];

        $service->addToQueue('job-1', $links, 1);

        // Simulate 3 workers getting items
        $item1 = $service->getNextItem('job-1');
        $item2 = $service->getNextItem('job-1');
        $item3 = $service->getNextItem('job-1');

        // All should be different
        $urls = [$item1['url'], $item2['url'], $item3['url']];
        $this->assertCount(3, array_unique($urls));
    }

    /**
     * Test prevent double processing.
     * LQ-012-05: Prevent double processing
     */
    public function test_concurrent_prevent_double_processing(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        // First worker gets item
        $item1 = $service->getNextItem('job-1');

        // Second worker should get nothing
        $item2 = $service->getNextItem('job-1');

        $this->assertNotNull($item1);
        $this->assertNull($item2);
    }

    /**
     * Test lock release on complete.
     * LQ-012-07: Lock release on complete
     */
    public function test_concurrent_lock_release_on_complete(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [['url' => 'https://example.com/page']];
        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');
        $service->markProcessing($queue[0]['id']);
        $service->markCompleted($queue[0]['id']);

        $updated = $service->getItem($queue[0]['id']);

        $this->assertNull($updated['locked_at']);
        $this->assertNull($updated['locked_by']);
    }

    // ==========================================
    // Queue Cleanup Tests (LQ-011)
    // ==========================================

    /**
     * Test remove completed items.
     * LQ-011-01: Remove completed
     */
    public function test_cleanup_removes_completed(): void
    {
        $service = app(CrawlQueueService::class);

        $service->addToQueue('job-1', [
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2'],
        ], 1);

        $queue = $service->getQueue('job-1');

        $service->markProcessing($queue[0]['id']);
        $service->markCompleted($queue[0]['id']);

        $service->cleanupQueue('job-1');

        $remaining = $service->getQueue('job-1');

        // Only the non-completed item should remain
        $this->assertCount(1, $remaining);
    }

    /**
     * Test clear on cancellation.
     * LQ-011-04: Clear on cancellation
     */
    public function test_cleanup_clears_on_cancel(): void
    {
        $service = app(CrawlQueueService::class);

        $service->addToQueue('job-1', [
            ['url' => 'https://example.com/page1'],
            ['url' => 'https://example.com/page2'],
        ], 1);

        $service->clearQueue('job-1');

        $remaining = $service->getQueue('job-1');

        $this->assertCount(0, $remaining);
    }

    // ==========================================
    // Configuration Tests (LQ-017)
    // ==========================================

    /**
     * Test default max concurrent.
     * LQ-017-01: Default max concurrent
     */
    public function test_config_default_max_concurrent(): void
    {
        $service = app(CrawlQueueService::class);

        $this->assertEquals(3, $service->getMaxConcurrentPages());
    }

    /**
     * Test default retry attempts.
     * LQ-017-02: Default retry attempts
     */
    public function test_config_default_retry_attempts(): void
    {
        $service = app(CrawlQueueService::class);

        $this->assertEquals(2, $service->getRetryAttempts());
    }

    /**
     * Test default lock timeout.
     * LQ-017-03: Default lock timeout
     */
    public function test_config_default_lock_timeout(): void
    {
        $service = app(CrawlQueueService::class);

        $this->assertEquals(300, $service->getLockTimeout());
    }

    /**
     * Test default dedup method.
     * LQ-017-04: Default dedup method
     */
    public function test_config_default_dedup_method(): void
    {
        $service = app(CrawlQueueService::class);

        $this->assertEquals('set', $service->getDedupMethod());
    }

    // ==========================================
    // Edge Cases Tests (LQ-018)
    // ==========================================

    /**
     * Test very long URL.
     * LQ-018-01: Very long URL
     */
    public function test_edge_case_very_long_url(): void
    {
        $service = app(CrawlQueueService::class);

        $longPath = str_repeat('/a', 500);
        $url = 'https://example.com' . $longPath;

        $links = [['url' => $url]];

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertCount(1, $queue);
    }

    /**
     * Test empty HTML.
     * LQ-018-04: Empty HTML
     */
    public function test_edge_case_empty_links(): void
    {
        $service = app(CrawlQueueService::class);

        $service->addToQueue('job-1', [], 1);

        $queue = $service->getQueue('job-1');

        $this->assertCount(0, $queue);
    }

    /**
     * Test large queue.
     * LQ-018-08: Large queue
     */
    public function test_edge_case_large_queue(): void
    {
        $service = app(CrawlQueueService::class);

        $links = [];
        for ($i = 0; $i < 1000; $i++) {
            $links[] = ['url' => "https://example.com/page{$i}"];
        }

        $service->addToQueue('job-1', $links, 1);

        $queue = $service->getQueue('job-1');

        $this->assertCount(1000, $queue);
    }

    // ==========================================
    // Integration Tests (LQ-020)
    // ==========================================

    /**
     * Test full queue cycle.
     * LQ-020-01: Full queue cycle
     */
    public function test_integration_full_queue_cycle(): void
    {
        $service = app(CrawlQueueService::class);

        // Add
        $service->addToQueue('job-1', [
            ['url' => 'https://example.com/page'],
        ], 1);

        // Get
        $item = $service->getNextItem('job-1');
        $this->assertNotNull($item);

        // Process (already locked by getNextItem)
        $service->markCompleted($item['id']);

        // Verify
        $updated = $service->getItem($item['id']);
        $this->assertEquals('completed', $updated['status']);

        // Stats
        $stats = $service->getQueueStats('job-1');
        $this->assertEquals(1, $stats['completed']);
    }
}
