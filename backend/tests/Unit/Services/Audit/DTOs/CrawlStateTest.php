<?php

namespace Tests\Unit\Services\Audit\DTOs;

use App\Services\Audit\DTOs\CrawlState;
use Tests\TestCase;

/**
 * Unit tests for CrawlState DTO.
 *
 * @see TASK-14-5
 * @see TEST-14-5
 */
class CrawlStateTest extends TestCase
{
    // =========================================================================
    // Factory Methods
    // =========================================================================

    /** @test Creates pending state */
    public function test_creates_pending_state(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/', ['max_depth' => 3]);

        $this->assertEquals(1, $state->auditId);
        $this->assertEquals('https://example.com/', $state->baseUrl);
        $this->assertEquals(CrawlState::STATUS_PENDING, $state->status);
        $this->assertEquals(['max_depth' => 3], $state->config);
    }

    /** @test Creates with defaults */
    public function test_creates_with_defaults(): void
    {
        $state = new CrawlState(1, 'https://example.com/');

        $this->assertEquals(1, $state->auditId);
        $this->assertEquals('https://example.com/', $state->baseUrl);
        $this->assertEquals(0, $state->totalPages);
        $this->assertEquals(0, $state->pagesCrawled);
        $this->assertEquals(0, $state->pagesFailed);
    }

    // =========================================================================
    // Status Transitions
    // =========================================================================

    /** @test markAsRunning() changes status */
    public function test_mark_as_running(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $state->markAsRunning();

        $this->assertEquals(CrawlState::STATUS_RUNNING, $state->status);
    }

    /** @test markAsCompleted() changes status and sets completedAt */
    public function test_mark_as_completed(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $state->markAsCompleted();

        $this->assertEquals(CrawlState::STATUS_COMPLETED, $state->status);
        $this->assertNotNull($state->completedAt);
    }

    /** @test markAsFailed() changes status and logs error */
    public function test_mark_as_failed(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $state->markAsFailed('Fatal error');

        $this->assertEquals(CrawlState::STATUS_FAILED, $state->status);
        $this->assertNotNull($state->completedAt);
        $this->assertCount(1, $state->errors);
        $this->assertEquals('Fatal error', $state->errors[0]['message']);
    }

    /** @test markAsCancelled() changes status */
    public function test_mark_as_cancelled(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $state->markAsCancelled();

        $this->assertEquals(CrawlState::STATUS_CANCELLED, $state->status);
        $this->assertNotNull($state->completedAt);
    }

    // =========================================================================
    // Counter Methods
    // =========================================================================

    /** @test incrementPagesCrawled() increments counter */
    public function test_increment_pages_crawled(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $state->incrementPagesCrawled();
        $state->incrementPagesCrawled();
        $state->incrementPagesCrawled();

        $this->assertEquals(3, $state->pagesCrawled);
    }

    /** @test incrementPagesFailed() increments and logs error */
    public function test_increment_pages_failed(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $state->incrementPagesFailed('Timeout');
        $state->incrementPagesFailed();

        $this->assertEquals(2, $state->pagesFailed);
        $this->assertCount(1, $state->errors);
    }

    /** @test setTotalPages() sets total */
    public function test_set_total_pages(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $state->setTotalPages(100);

        $this->assertEquals(100, $state->totalPages);
    }

    // =========================================================================
    // Status Checks
    // =========================================================================

    /** @test isPending() returns correct status */
    public function test_is_pending(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $this->assertTrue($state->isPending());

        $state->markAsRunning();
        $this->assertFalse($state->isPending());
    }

    /** @test isRunning() returns correct status */
    public function test_is_running(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');
        $state->markAsRunning();

        $this->assertTrue($state->isRunning());
    }

    /** @test isCompleted() returns correct status */
    public function test_is_completed(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');
        $state->markAsCompleted();

        $this->assertTrue($state->isCompleted());
    }

    /** @test isFailed() returns correct status */
    public function test_is_failed(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');
        $state->markAsFailed();

        $this->assertTrue($state->isFailed());
    }

    /** @test isCancelled() returns correct status */
    public function test_is_cancelled(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');
        $state->markAsCancelled();

        $this->assertTrue($state->isCancelled());
    }

    /** @test isFinished() checks all terminal states */
    public function test_is_finished(): void
    {
        $pending = CrawlState::pending(1, 'https://example.com/');
        $completed = CrawlState::pending(1, 'https://example.com/');
        $failed = CrawlState::pending(1, 'https://example.com/');
        $cancelled = CrawlState::pending(1, 'https://example.com/');

        $completed->markAsCompleted();
        $failed->markAsFailed();
        $cancelled->markAsCancelled();

        $this->assertFalse($pending->isFinished());
        $this->assertTrue($completed->isFinished());
        $this->assertTrue($failed->isFinished());
        $this->assertTrue($cancelled->isFinished());
    }

    // =========================================================================
    // Progress & Duration
    // =========================================================================

    /** @test getProgress() calculates percentage */
    public function test_get_progress(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');
        $state->setTotalPages(100);

        $state->incrementPagesCrawled();
        $this->assertEquals(1.0, $state->getProgress());

        $state->incrementPagesCrawled();
        $state->incrementPagesCrawled();
        $this->assertEquals(3.0, $state->getProgress());
    }

    /** @test getProgress() handles zero total */
    public function test_get_progress_handles_zero(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $this->assertEquals(0.0, $state->getProgress());
    }

    /** @test getDuration() calculates elapsed time */
    public function test_get_duration(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        // Should be very small since just created
        $this->assertLessThan(2, $state->getDuration());
    }

    // =========================================================================
    // Config Accessors
    // =========================================================================

    /** @test getMaxDepth() returns from config */
    public function test_get_max_depth(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/', ['max_depth' => 5]);

        $this->assertEquals(5, $state->getMaxDepth());
    }

    /** @test getMaxDepth() returns default if not set */
    public function test_get_max_depth_default(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $this->assertEquals(3, $state->getMaxDepth());
    }

    /** @test getMaxPages() returns from config */
    public function test_get_max_pages(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/', ['max_pages' => 200]);

        $this->assertEquals(200, $state->getMaxPages());
    }

    /** @test getMaxPages() returns default if not set */
    public function test_get_max_pages_default(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $this->assertEquals(500, $state->getMaxPages());
    }

    // =========================================================================
    // Serialization
    // =========================================================================

    /** @test toArray() returns complete array */
    public function test_to_array(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/', ['max_depth' => 3]);
        $state->setTotalPages(100);
        $state->incrementPagesCrawled();

        $array = $state->toArray();

        $this->assertEquals(1, $array['auditId']);
        $this->assertEquals('https://example.com/', $array['baseUrl']);
        $this->assertEquals(100, $array['totalPages']);
        $this->assertEquals(1, $array['pagesCrawled']);
        $this->assertArrayHasKey('progress', $array);
        $this->assertArrayHasKey('duration', $array);
    }

    /** @test jsonSerialize() returns JSON-compatible array */
    public function test_json_serialize(): void
    {
        $state = CrawlState::pending(1, 'https://example.com/');

        $json = json_encode($state);
        $decoded = json_decode($json, true);

        $this->assertEquals(1, $decoded['auditId']);
        $this->assertEquals('https://example.com/', $decoded['baseUrl']);
    }

    /** @test fromArray() creates instance from array */
    public function test_from_array(): void
    {
        $data = [
            'auditId' => 1,
            'baseUrl' => 'https://example.com/',
            'totalPages' => 100,
            'pagesCrawled' => 50,
            'pagesFailed' => 2,
            'status' => CrawlState::STATUS_RUNNING,
            'startedAt' => '2026-03-07T10:00:00Z',
            'completedAt' => null,
            'errors' => [],
            'config' => ['max_depth' => 3],
        ];

        $state = CrawlState::fromArray($data);

        $this->assertEquals(1, $state->auditId);
        $this->assertEquals(100, $state->totalPages);
        $this->assertEquals(50, $state->pagesCrawled);
        $this->assertTrue($state->isRunning());
    }

    /** @test Round-trip serialization */
    public function test_round_trip_serialization(): void
    {
        $original = CrawlState::pending(1, 'https://example.com/', ['max_depth' => 5, 'max_pages' => 200]);
        $original->setTotalPages(100);
        $original->incrementPagesCrawled();
        $original->incrementPagesCrawled();
        $original->incrementPagesFailed('Timeout');

        $array = $original->toArray();
        $restored = CrawlState::fromArray($array);

        $this->assertEquals($original->auditId, $restored->auditId);
        $this->assertEquals($original->baseUrl, $restored->baseUrl);
        $this->assertEquals($original->totalPages, $restored->totalPages);
        $this->assertEquals($original->pagesCrawled, $restored->pagesCrawled);
        $this->assertEquals($original->pagesFailed, $restored->pagesFailed);
        $this->assertEquals($original->status, $restored->status);
        $this->assertEquals($original->config, $restored->config);
    }

    // =========================================================================
    // Timestamp
    // =========================================================================

    /** @test startedAt is set automatically */
    public function test_started_at_is_set_automatically(): void
    {
        $state = new CrawlState(1, 'https://example.com/');

        $this->assertNotEmpty($state->startedAt);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $state->startedAt);
    }
}
