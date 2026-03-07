<?php

namespace Tests\Unit\Services\Audit\DTOs;

use App\Services\Audit\DTOs\CrawlQueueItem;
use Tests\TestCase;

/**
 * Unit tests for CrawlQueueItem DTO.
 *
 * @see TASK-14-5
 * @see TEST-14-5
 */
class CrawlQueueItemTest extends TestCase
{
    // =========================================================================
    // Factory Methods
    // =========================================================================

    /** @test Creates pending queue item */
    public function test_creates_pending_queue_item(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/', 2, 50);

        $this->assertEquals('https://example.com/', $item->url);
        $this->assertEquals(2, $item->depth);
        $this->assertEquals(50, $item->priority);
        $this->assertEquals(CrawlQueueItem::STATUS_PENDING, $item->status);
    }

    /** @test Creates with defaults */
    public function test_creates_with_defaults(): void
    {
        $item = new CrawlQueueItem('https://example.com/');

        $this->assertEquals('https://example.com/', $item->url);
        $this->assertEquals(0, $item->depth);
        $this->assertEquals(0, $item->priority);
        $this->assertEquals(CrawlQueueItem::STATUS_PENDING, $item->status);
        $this->assertEquals(0, $item->retryCount);
    }

    // =========================================================================
    // Status Transitions
    // =========================================================================

    /** @test markAsProcessing() changes status */
    public function test_mark_as_processing(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');

        $item->markAsProcessing();

        $this->assertEquals(CrawlQueueItem::STATUS_PROCESSING, $item->status);
    }

    /** @test markAsDone() changes status */
    public function test_mark_as_done(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');

        $item->markAsDone();

        $this->assertEquals(CrawlQueueItem::STATUS_DONE, $item->status);
    }

    /** @test markAsFailed() changes status and sets error */
    public function test_mark_as_failed(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');

        $item->markAsFailed('Connection timeout');

        $this->assertEquals(CrawlQueueItem::STATUS_FAILED, $item->status);
        $this->assertEquals('Connection timeout', $item->errorMessage);
    }

    /** @test incrementRetry() increments count and resets status */
    public function test_increment_retry(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');
        $item->markAsFailed();

        $item->incrementRetry();

        $this->assertEquals(1, $item->retryCount);
        $this->assertEquals(CrawlQueueItem::STATUS_PENDING, $item->status);
    }

    // =========================================================================
    // Status Checks
    // =========================================================================

    /** @test isPending() returns correct status */
    public function test_is_pending(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');

        $this->assertTrue($item->isPending());

        $item->markAsProcessing();
        $this->assertFalse($item->isPending());
    }

    /** @test isProcessing() returns correct status */
    public function test_is_processing(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');
        $item->markAsProcessing();

        $this->assertTrue($item->isProcessing());
    }

    /** @test isDone() returns correct status */
    public function test_is_done(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');
        $item->markAsDone();

        $this->assertTrue($item->isDone());
    }

    /** @test isFailed() returns correct status */
    public function test_is_failed(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');
        $item->markAsFailed();

        $this->assertTrue($item->isFailed());
    }

    /** @test canRetry() respects max retries */
    public function test_can_retry(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');
        $item->markAsFailed();

        $this->assertTrue($item->canRetry(3));

        $item->incrementRetry();
        $item->markAsFailed();
        $this->assertTrue($item->canRetry(3));

        $item->incrementRetry();
        $item->markAsFailed();
        $this->assertTrue($item->canRetry(3));

        $item->incrementRetry();
        $item->markAsFailed();
        $this->assertFalse($item->canRetry(3));
    }

    // =========================================================================
    // Priority
    // =========================================================================

    /** @test getSortPriority() calculates correctly */
    public function test_get_sort_priority(): void
    {
        $homepage = CrawlQueueItem::pending('https://example.com/', 0, 0);
        $depth1 = CrawlQueueItem::pending('https://example.com/page', 1, 0);
        $depth2 = CrawlQueueItem::pending('https://example.com/deep', 2, 0);

        // Lower sort priority = processed first
        $this->assertLessThan($depth1->getSortPriority(), $homepage->getSortPriority());
        $this->assertLessThan($depth2->getSortPriority(), $depth1->getSortPriority());
    }

    /** @test Priority affects sort order */
    public function test_priority_affects_sort_order(): void
    {
        $highPriority = CrawlQueueItem::pending('https://example.com/', 1, 100);
        $lowPriority = CrawlQueueItem::pending('https://example.com/', 1, 0);

        // Higher priority value = lower sort priority (processed first)
        $this->assertLessThan($lowPriority->getSortPriority(), $highPriority->getSortPriority());
    }

    // =========================================================================
    // Serialization
    // =========================================================================

    /** @test toArray() returns complete array */
    public function test_to_array(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/', 2, 50);
        $item->markAsProcessing();

        $array = $item->toArray();

        $this->assertEquals('https://example.com/', $array['url']);
        $this->assertEquals(2, $array['depth']);
        $this->assertEquals(50, $array['priority']);
        $this->assertEquals(CrawlQueueItem::STATUS_PROCESSING, $array['status']);
    }

    /** @test jsonSerialize() returns JSON-compatible array */
    public function test_json_serialize(): void
    {
        $item = CrawlQueueItem::pending('https://example.com/');

        $json = json_encode($item);
        $decoded = json_decode($json, true);

        $this->assertEquals('https://example.com/', $decoded['url']);
    }

    /** @test fromArray() creates instance from array */
    public function test_from_array(): void
    {
        $data = [
            'url' => 'https://example.com/',
            'depth' => 2,
            'priority' => 50,
            'status' => CrawlQueueItem::STATUS_PENDING,
            'retryCount' => 1,
            'errorMessage' => null,
            'addedAt' => '2026-03-07T10:00:00Z',
        ];

        $item = CrawlQueueItem::fromArray($data);

        $this->assertEquals('https://example.com/', $item->url);
        $this->assertEquals(2, $item->depth);
        $this->assertEquals(50, $item->priority);
        $this->assertEquals(1, $item->retryCount);
    }

    /** @test Round-trip serialization */
    public function test_round_trip_serialization(): void
    {
        $original = CrawlQueueItem::pending('https://example.com/', 2, 50);
        $original->markAsFailed('Error');

        $array = $original->toArray();
        $restored = CrawlQueueItem::fromArray($array);

        $this->assertEquals($original->url, $restored->url);
        $this->assertEquals($original->depth, $restored->depth);
        $this->assertEquals($original->priority, $restored->priority);
        $this->assertEquals($original->status, $restored->status);
        $this->assertEquals($original->errorMessage, $restored->errorMessage);
    }

    // =========================================================================
    // Timestamp
    // =========================================================================

    /** @test addedAt is set automatically */
    public function test_added_at_is_set_automatically(): void
    {
        $item = new CrawlQueueItem('https://example.com/');

        $this->assertNotEmpty($item->addedAt);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $item->addedAt);
    }
}
