<?php

namespace Tests\Unit\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\CrawlerStatusCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;

/**
 * Unit tests for TASK-22-16: CrawlerStatusCommand
 *
 * Tests the crawler:status artisan command that shows crawler queue statistics.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class CrawlerStatusCommandTest extends TestCase
{
    // =========================================================================
    // COMMAND STRUCTURE TESTS (CS-*)
    // =========================================================================

    /**
     * CS-01: Command file exists
     */
    public function test_command_exists(): void
    {
        $this->assertFileExists(app_path('Console/Commands/CrawlerStatusCommand.php'));
    }

    /**
     * CS-02: Command signature is correct
     */
    public function test_command_signature_correct(): void
    {
        $command = new CrawlerStatusCommand();
        $this->assertStringContainsString('crawler:status', $command->getName());
    }

    /**
     * CS-03: Command description is set
     */
    public function test_command_description_set(): void
    {
        $command = new CrawlerStatusCommand();
        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('status', strtolower($command->getDescription()));
    }

    // =========================================================================
    // COMMAND FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * CS-04: Command gathers queue stats
     */
    public function test_command_gathers_queue_stats(): void
    {
        $this->artisan('crawler:status')
            ->expectsOutputToContain('Crawler Queue Status')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CS-05: Command outputs table format
     */
    public function test_command_outputs_table_format(): void
    {
        $this->artisan('crawler:status')
            ->expectsOutputToContain('Queue')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CS-08: Command --json option outputs JSON
     */
    public function test_command_json_option(): void
    {
        $this->artisan('crawler:status', ['--json' => true])
            ->expectsOutputToContain('queues')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CS-09: Command handles specific queue option
     */
    public function test_command_handles_specific_queue(): void
    {
        $this->artisan('crawler:status', ['--queue' => ['crawler-high']])
            ->expectsOutputToContain('crawler-high')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CS-10: Command defaults to all crawler queues
     */
    public function test_command_defaults_to_all_queues(): void
    {
        $this->artisan('crawler:status')
            ->expectsOutputToContain('crawler')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CS-11: Command shows totals
     */
    public function test_command_shows_totals(): void
    {
        $this->artisan('crawler:status')
            ->expectsOutputToContain('Totals:')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CS-12: Command shows timestamp
     */
    public function test_command_shows_timestamp(): void
    {
        $this->artisan('crawler:status')
            ->expectsOutputToContain('Timestamp:')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CS-13: Command returns success code
     */
    public function test_command_returns_success_code(): void
    {
        $this->artisan('crawler:status')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CS-14: Command shows worker info
     */
    public function test_command_shows_worker_info(): void
    {
        $this->artisan('crawler:status')
            ->expectsOutputToContain('Workers:')
            ->assertExitCode(Command::SUCCESS);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Test command shows restart signal when present
     */
    public function test_command_shows_restart_signal(): void
    {
        // Set a restart signal
        Cache::forever('illuminate:queue:restart', time());

        $this->artisan('crawler:status')
            ->expectsOutputToContain('Restart signal')
            ->assertExitCode(Command::SUCCESS);

        // Cleanup
        Cache::forget('illuminate:queue:restart');
    }

    /**
     * Test worker status when ping exists
     */
    public function test_worker_status_when_ping_exists(): void
    {
        Cache::put('crawler:worker:last_ping', now()->toIso8601String(), 300);

        $this->artisan('crawler:status')
            ->expectsOutputToContain('active')
            ->assertExitCode(Command::SUCCESS);

        Cache::forget('crawler:worker:last_ping');
    }
}
