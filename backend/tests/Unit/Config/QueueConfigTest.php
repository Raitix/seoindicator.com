<?php

namespace Tests\Unit\Config;

use Tests\TestCase;

/**
 * Unit tests for TASK-22-16: Queue Configuration
 *
 * Tests the crawler queue configuration in config/queue.php.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class QueueConfigTest extends TestCase
{
    // =========================================================================
    // QUEUE CONFIGURATION TESTS (QC-*)
    // =========================================================================

    /**
     * QC-01: Crawler queue connection exists
     */
    public function test_crawler_queue_connection_exists(): void
    {
        $connections = config('queue.connections');
        $this->assertArrayHasKey('crawler', $connections);
    }

    /**
     * QC-02: Crawler-high queue connection exists
     */
    public function test_crawler_high_queue_connection_exists(): void
    {
        $connections = config('queue.connections');
        $this->assertArrayHasKey('crawler-high', $connections);
    }

    /**
     * QC-03: Crawler-low queue connection exists
     */
    public function test_crawler_low_queue_connection_exists(): void
    {
        $connections = config('queue.connections');
        $this->assertArrayHasKey('crawler-low', $connections);
    }

    /**
     * QC-04: Crawler queue uses database driver
     */
    public function test_crawler_queue_uses_database_driver(): void
    {
        $this->assertEquals('database', config('queue.connections.crawler.driver'));
        $this->assertEquals('database', config('queue.connections.crawler-high.driver'));
        $this->assertEquals('database', config('queue.connections.crawler-low.driver'));
    }

    /**
     * QC-05: Crawler queue retry_after is 300 seconds
     */
    public function test_crawler_queue_retry_after_300(): void
    {
        $this->assertEquals(300, config('queue.connections.crawler.retry_after'));
        $this->assertEquals(300, config('queue.connections.crawler-high.retry_after'));
        $this->assertEquals(300, config('queue.connections.crawler-low.retry_after'));
    }

    /**
     * QC-06: Crawler queue after_commit is true
     */
    public function test_crawler_queue_after_commit_true(): void
    {
        $this->assertTrue(config('queue.connections.crawler.after_commit'));
        $this->assertTrue(config('queue.connections.crawler-high.after_commit'));
        $this->assertTrue(config('queue.connections.crawler-low.after_commit'));
    }

    /**
     * QC-07: Crawler queue table is jobs
     */
    public function test_crawler_queue_table_is_jobs(): void
    {
        $this->assertEquals('jobs', config('queue.connections.crawler.table'));
    }

    /**
     * QC-08: Monitoring thresholds configured
     */
    public function test_monitoring_thresholds_configured(): void
    {
        $thresholds = config('queue.monitoring.thresholds');
        
        $this->assertIsArray($thresholds);
        $this->assertArrayHasKey('pending_warning', $thresholds);
        $this->assertArrayHasKey('pending_critical', $thresholds);
        $this->assertArrayHasKey('failed_warning', $thresholds);
        $this->assertArrayHasKey('failed_critical', $thresholds);
    }

    /**
     * QC-09: Failed jobs configured
     */
    public function test_failed_jobs_configured(): void
    {
        $failed = config('queue.failed');
        
        $this->assertIsArray($failed);
        $this->assertEquals('database-uuids', $failed['driver']);
        $this->assertEquals('failed_jobs', $failed['table']);
    }

    /**
     * QC-10: Batching configured
     */
    public function test_batching_configured(): void
    {
        $batching = config('queue.batching');
        
        $this->assertIsArray($batching);
        $this->assertArrayHasKey('database', $batching);
        $this->assertArrayHasKey('table', $batching);
        $this->assertEquals('job_batches', $batching['table']);
    }

    /**
     * QC-11: Queue priorities are separate
     */
    public function test_queue_priorities_defined(): void
    {
        // Each priority level has its own queue
        $this->assertEquals('crawler-high', config('queue.connections.crawler-high.queue'));
        $this->assertEquals('crawler', config('queue.connections.crawler.queue'));
        $this->assertEquals('crawler-low', config('queue.connections.crawler-low.queue'));
    }

    /**
     * QC-12: Crawler queue name matches connection
     */
    public function test_crawler_queue_name_matches_connection(): void
    {
        // The queue name should match the connection purpose
        $this->assertEquals('crawler', config('queue.connections.crawler.queue'));
    }

    // =========================================================================
    // DEFAULT QUEUE CONFIGURATION TESTS
    // =========================================================================

    /**
     * Test default queue connection is configured
     */
    public function test_default_queue_connection(): void
    {
        // Default queue connection should be defined (actual value depends on env)
        $this->assertNotNull(config('queue.default'));
        $this->assertIsString(config('queue.default'));
    }

    /**
     * Test database connection for crawler uses same DB
     */
    public function test_crawler_uses_same_database_connection(): void
    {
        // Crawler uses the same DB connection as default queue
        $crawlerConnection = config('queue.connections.crawler.connection');
        $defaultConnection = config('queue.connections.database.connection');
        
        // Both should be null (use default) or the same
        $this->assertTrue(
            $crawlerConnection === $defaultConnection || 
            $crawlerConnection === null
        );
    }

    // =========================================================================
    // ENVIRONMENT OVERRIDES TESTS
    // =========================================================================

    /**
     * Test pending warning threshold default
     */
    public function test_pending_warning_threshold_default(): void
    {
        $this->assertEquals(100, config('queue.monitoring.thresholds.pending_warning'));
    }

    /**
     * Test pending critical threshold default
     */
    public function test_pending_critical_threshold_default(): void
    {
        $this->assertEquals(500, config('queue.monitoring.thresholds.pending_critical'));
    }

    /**
     * Test failed warning threshold default
     */
    public function test_failed_warning_threshold_default(): void
    {
        $this->assertEquals(10, config('queue.monitoring.thresholds.failed_warning'));
    }

    /**
     * Test failed critical threshold default
     */
    public function test_failed_critical_threshold_default(): void
    {
        $this->assertEquals(50, config('queue.monitoring.thresholds.failed_critical'));
    }

    // =========================================================================
    // CONFIGURATION FILE STRUCTURE TESTS
    // =========================================================================

    /**
     * Test config file exists
     */
    public function test_config_file_exists(): void
    {
        $this->assertFileExists(config_path('queue.php'));
    }

    /**
     * Test config file is valid PHP
     */
    public function test_config_file_is_valid_php(): void
    {
        // If config loads without error, it's valid PHP
        $config = config('queue');
        $this->assertIsArray($config);
    }

    /**
     * Test all required config keys exist
     */
    public function test_all_required_config_keys_exist(): void
    {
        $config = config('queue');
        
        $requiredKeys = ['default', 'connections', 'batching', 'failed'];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $config, "Missing required config key: {$key}");
        }
    }
}
