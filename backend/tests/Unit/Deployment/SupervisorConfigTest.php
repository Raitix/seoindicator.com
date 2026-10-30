<?php

namespace Tests\Unit\Deployment;

use Tests\TestCase;

/**
 * Unit tests for TASK-22-16: Supervisor Configuration
 *
 * Tests the supervisor configuration file for crawler queue workers.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class SupervisorConfigTest extends TestCase
{
    protected string $configPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configPath = base_path('deployment/supervisor-crawler.conf');
    }

    // =========================================================================
    // SUPERVISOR CONFIGURATION TESTS (SC-*)
    // =========================================================================

    /**
     * SC-01: Supervisor config file exists
     */
    public function test_supervisor_config_file_exists(): void
    {
        $this->assertFileExists($this->configPath);
    }

    /**
     * SC-02: Supervisor config has process_name
     */
    public function test_supervisor_config_has_process_name(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('process_name=', $content);
        $this->assertStringContainsString('%(program_name)s', $content);
    }

    /**
     * SC-03: Supervisor config has command
     */
    public function test_supervisor_config_has_command(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('command=', $content);
        $this->assertStringContainsString('artisan queue:work', $content);
    }

    /**
     * SC-04: Supervisor config uses crawler queues
     */
    public function test_supervisor_config_uses_crawler_queue(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('--queue=', $content);
        $this->assertStringContainsString('crawler', $content);
    }

    /**
     * SC-05: Supervisor config has autostart
     */
    public function test_supervisor_config_has_autostart(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('autostart=true', $content);
    }

    /**
     * SC-06: Supervisor config has numprocs
     */
    public function test_supervisor_config_has_numprocs(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('numprocs=', $content);
    }

    // =========================================================================
    // CONFIGURATION CONTENT TESTS
    // =========================================================================

    /**
     * Test config has autorestart
     */
    public function test_config_has_autorestart(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('autorestart=true', $content);
    }

    /**
     * Test config has user setting
     */
    public function test_config_has_user_setting(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('user=', $content);
    }

    /**
     * Test config has log file setting
     */
    public function test_config_has_log_file(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('stdout_logfile=', $content);
    }

    /**
     * Test config has stop signal
     */
    public function test_config_has_stop_signal(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('stopsignal=', $content);
    }

    /**
     * Test config has timeout setting
     */
    public function test_config_has_timeout(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('--timeout=', $content);
    }

    /**
     * Test config has memory limit
     */
    public function test_config_has_memory_limit(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('--memory=', $content);
    }

    // =========================================================================
    // PRIORITY QUEUE CONFIGURATION TESTS
    // =========================================================================

    /**
     * Test config includes high priority queue
     */
    public function test_config_includes_high_priority_queue(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('crawler-high', $content);
    }

    /**
     * Test config includes low priority queue
     */
    public function test_config_includes_low_priority_queue(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('crawler-low', $content);
    }

    /**
     * Test config has documentation comments
     */
    public function test_config_has_documentation(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('#', $content);
        $this->assertStringContainsString('Installation:', $content);
    }

    /**
     * Test config has program name
     */
    public function test_config_has_program_name(): void
    {
        $content = file_get_contents($this->configPath);
        $this->assertStringContainsString('[program:crawler-worker]', $content);
    }
}
