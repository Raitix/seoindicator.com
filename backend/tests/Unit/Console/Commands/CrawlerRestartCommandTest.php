<?php

namespace Tests\Unit\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\CrawlerRestartCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for TASK-22-16: CrawlerRestartCommand
 *
 * Tests the crawler:restart artisan command that sends restart signals
 * to crawler queue workers.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class CrawlerRestartCommandTest extends TestCase
{
    // =========================================================================
    // COMMAND STRUCTURE TESTS (CR-*)
    // =========================================================================

    /**
     * CR-01: Command file exists
     */
    public function test_command_exists(): void
    {
        $this->assertFileExists(app_path('Console/Commands/CrawlerRestartCommand.php'));
    }

    /**
     * CR-02: Command signature is correct
     */
    public function test_command_signature_correct(): void
    {
        $command = new CrawlerRestartCommand();
        $this->assertStringContainsString('crawler:restart', $command->getName());
    }

    /**
     * CR-03: Command description is set
     */
    public function test_command_description_set(): void
    {
        $command = new CrawlerRestartCommand();
        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('restart', strtolower($command->getDescription()));
    }

    // =========================================================================
    // COMMAND FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * CR-04: Command sets restart signal in cache
     */
    public function test_command_sets_restart_signal(): void
    {
        Cache::shouldReceive('forever')
            ->once()
            ->with('illuminate:queue:restart', \Mockery::type('int'));

        $this->artisan('crawler:restart')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CR-05: Command outputs success message
     */
    public function test_command_outputs_success_message(): void
    {
        $this->artisan('crawler:restart')
            ->expectsOutput('Broadcasting queue restart signal...')
            ->expectsOutput('Workers will restart after completing their current job.')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CR-06: Command handles specific queue option
     */
    public function test_command_handles_specific_queue_option(): void
    {
        $this->artisan('crawler:restart', ['--queue' => ['crawler-high']])
            ->expectsOutputToContain('crawler-high')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CR-07: Command defaults to all crawler queues
     */
    public function test_command_defaults_to_all_queues(): void
    {
        $output = '';
        
        $this->artisan('crawler:restart')
            ->expectsOutputToContain('crawler-high')
            ->expectsOutputToContain('Queue [crawler]')
            ->expectsOutputToContain('crawler-low')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CR-08: Restart signal persists in cache
     */
    public function test_restart_signal_persists_in_cache(): void
    {
        // Clear any existing signal
        Cache::forget('illuminate:queue:restart');

        // Run command
        $this->artisan('crawler:restart')
            ->assertExitCode(Command::SUCCESS);

        // Verify signal exists
        $signal = Cache::get('illuminate:queue:restart');
        $this->assertNotNull($signal);
        $this->assertIsInt($signal);
    }

    /**
     * CR-09: Command returns success code
     */
    public function test_command_returns_success_code(): void
    {
        $this->artisan('crawler:restart')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CR-10: Restart signal format is timestamp
     */
    public function test_restart_signal_format(): void
    {
        Cache::forget('illuminate:queue:restart');

        $this->artisan('crawler:restart')
            ->assertExitCode(Command::SUCCESS);

        $signal = Cache::get('illuminate:queue:restart');
        
        // Signal should be a valid Unix timestamp (within last minute)
        $this->assertGreaterThan(time() - 60, $signal);
        $this->assertLessThanOrEqual(time(), $signal);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    /**
     * Test command handles multiple specific queues
     */
    public function test_command_handles_multiple_specific_queues(): void
    {
        $this->artisan('crawler:restart', ['--queue' => ['crawler-high', 'crawler']])
            ->expectsOutputToContain('crawler-high')
            ->expectsOutputToContain('crawler')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * Test restart signal can be overwritten
     */
    public function test_restart_signal_can_be_overwritten(): void
    {
        // First restart
        $this->artisan('crawler:restart')->assertExitCode(Command::SUCCESS);
        $firstSignal = Cache::get('illuminate:queue:restart');

        // Wait a tiny bit (simulate time passing)
        sleep(1);

        // Second restart
        $this->artisan('crawler:restart')->assertExitCode(Command::SUCCESS);
        $secondSignal = Cache::get('illuminate:queue:restart');

        // Signals should be different
        $this->assertNotEquals($firstSignal, $secondSignal);
        $this->assertGreaterThanOrEqual($firstSignal, $secondSignal);
    }
}
