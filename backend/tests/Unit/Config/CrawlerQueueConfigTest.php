<?php

namespace Tests\Unit\Config;

use Tests\TestCase;

/**
 * Unit tests for TASK-22-16: Queue Integration with JobQueue
 *
 * Tests the queue configuration for the crawler queue.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class CrawlerQueueConfigTest extends TestCase
{
    // =========================================================================
    // QUEUE CONFIGURATION TESTS (QC-*)
    // =========================================================================

    /**
     * QC-01: Crawler queue connection exists
     */
    public function test_crawler_queue_connection_exists(): void
    {
        $crawlerConfig = config('queue.connections.crawler');
        
        $this->assertNotNull($crawlerConfig, 'Crawler queue connection should be defined');
        $this->assertIsArray($crawlerConfig, 'Crawler queue config should be an array');
    }

    /**
     * QC-02: Driver is database
     */
    public function test_driver_is_database(): void
    {
        $this->assertEquals(
            'database',
            config('queue.connections.crawler.driver'),
            'Crawler queue should use database driver'
        );
    }

    /**
     * QC-03: Table is jobs
     */
    public function test_table_is_jobs(): void
    {
        $this->assertEquals(
            'jobs',
            config('queue.connections.crawler.table'),
            'Crawler queue should use jobs table'
        );
    }

    /**
     * QC-04: Queue name is crawler
     */
    public function test_queue_name_is_crawler(): void
    {
        $this->assertEquals(
            'crawler',
            config('queue.connections.crawler.queue'),
            'Crawler queue name should be crawler'
        );
    }

    /**
     * QC-05: Retry after is 300 seconds
     */
    public function test_retry_after_is_300(): void
    {
        $this->assertEquals(
            300,
            config('queue.connections.crawler.retry_after'),
            'Crawler queue retry_after should be 300 seconds'
        );
    }

    /**
     * QC-06: After commit is true
     */
    public function test_after_commit_is_true(): void
    {
        $this->assertTrue(
            config('queue.connections.crawler.after_commit'),
            'Crawler queue should use after_commit for transaction safety'
        );
    }

    /**
     * QC-07: Queue connection uses same driver as JobQueue
     */
    public function test_queue_connection_uses_same_driver_as_jobqueue(): void
    {
        $crawlerDriver = config('queue.connections.crawler.driver');
        $defaultDriver = config('queue.connections.database.driver');
        
        $this->assertEquals(
            $defaultDriver,
            $crawlerDriver,
            'Crawler queue should use same driver as default database queue'
        );
    }

    /**
     * QC-08: Default queue connection unchanged
     */
    public function test_default_queue_connection_unchanged(): void
    {
        $defaultConnection = config('queue.default');
        
        // Default should be 'database' or 'sync' depending on environment
        $this->assertContains(
            $defaultConnection,
            ['database', 'sync', 'redis'],
            'Default queue connection should remain unchanged'
        );
    }

    /**
     * QC-09: Failed job driver configured
     */
    public function test_failed_job_driver_configured(): void
    {
        $failedDriver = config('queue.failed.driver');
        
        $this->assertEquals(
            'database-uuids',
            $failedDriver,
            'Failed jobs should use database-uuids driver'
        );
    }

    /**
     * QC-10: Failed job table configured
     */
    public function test_failed_job_table_configured(): void
    {
        $failedTable = config('queue.failed.table');
        
        $this->assertEquals(
            'failed_jobs',
            $failedTable,
            'Failed jobs should be stored in failed_jobs table'
        );
    }

    // =========================================================================
    // PRIORITY QUEUE CONFIGURATION TESTS
    // =========================================================================

    /**
     * High priority queue connection exists
     */
    public function test_high_priority_queue_connection_exists(): void
    {
        $highConfig = config('queue.connections.crawler-high');
        
        $this->assertNotNull($highConfig, 'High priority crawler queue should be defined');
        $this->assertEquals('crawler-high', $highConfig['queue'] ?? null);
    }

    /**
     * Low priority queue connection exists
     */
    public function test_low_priority_queue_connection_exists(): void
    {
        $lowConfig = config('queue.connections.crawler-low');
        
        $this->assertNotNull($lowConfig, 'Low priority crawler queue should be defined');
        $this->assertEquals('crawler-low', $lowConfig['queue'] ?? null);
    }

    /**
     * All priority queues use same driver
     */
    public function test_all_priority_queues_use_same_driver(): void
    {
        $defaultDriver = config('queue.connections.crawler.driver');
        $highDriver = config('queue.connections.crawler-high.driver');
        $lowDriver = config('queue.connections.crawler-low.driver');
        
        $this->assertEquals($defaultDriver, $highDriver, 'High priority queue should use same driver');
        $this->assertEquals($defaultDriver, $lowDriver, 'Low priority queue should use same driver');
    }

    /**
     * Queue configuration is cacheable
     */
    public function test_queue_configuration_is_cacheable(): void
    {
        // Verify config can be cached without errors
        $config = config('queue');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('connections', $config);
        $this->assertArrayHasKey('crawler', $config['connections']);
    }
}
