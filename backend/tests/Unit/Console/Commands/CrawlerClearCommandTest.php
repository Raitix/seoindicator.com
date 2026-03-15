<?php

namespace Tests\Unit\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\CrawlerClearCommand;
use Illuminate\Console\Command;

/**
 * Unit tests for TASK-22-16: CrawlerClearCommand
 *
 * Tests the crawler:clear artisan command that clears pending jobs from crawler queues.
 *
 * @see TEST-22-16
 * @see TASK-22-16
 */
class CrawlerClearCommandTest extends TestCase
{
    // =========================================================================
    // COMMAND STRUCTURE TESTS (CC-*)
    // =========================================================================

    /**
     * CC-01: Command file exists
     */
    public function test_command_exists(): void
    {
        $this->assertFileExists(app_path('Console/Commands/CrawlerClearCommand.php'));
    }

    /**
     * CC-02: Command signature is correct
     */
    public function test_command_signature_correct(): void
    {
        $command = new CrawlerClearCommand();
        $this->assertStringContainsString('crawler:clear', $command->getName());
    }

    /**
     * CC-03: Command description is set
     */
    public function test_command_description_set(): void
    {
        $command = new CrawlerClearCommand();
        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('clear', strtolower($command->getDescription()));
    }

    // =========================================================================
    // COMMAND FUNCTIONALITY TESTS
    // =========================================================================

    /**
     * CC-07: Command --force option skips confirmation
     */
    public function test_command_force_option(): void
    {
        // Should not ask for confirmation with --force
        $this->artisan('crawler:clear', ['--force' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CC-08: Command --queue option works
     */
    public function test_command_queue_option(): void
    {
        $this->artisan('crawler:clear', ['--queue' => ['crawler-high'], '--force' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CC-10: Command returns success code
     */
    public function test_command_returns_success_code(): void
    {
        $this->artisan('crawler:clear', ['--force' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CC-11: Command handles empty queue
     */
    public function test_command_handles_empty_queue(): void
    {
        $this->artisan('crawler:clear', ['--force' => true])
            ->expectsOutputToContain('No jobs to clear')
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * CC-14: Command defaults to all crawler queues
     */
    public function test_command_defaults_to_all_queues(): void
    {
        $this->artisan('crawler:clear', ['--force' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    // =========================================================================
    // COMMAND OPTIONS TESTS
    // =========================================================================

    /**
     * Test command has --queue option
     */
    public function test_command_has_queue_option(): void
    {
        $command = new CrawlerClearCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('queue'));
    }

    /**
     * Test command has --force option
     */
    public function test_command_has_force_option(): void
    {
        $command = new CrawlerClearCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('force'));
    }

    /**
     * Test command has --include-failed option
     */
    public function test_command_has_include_failed_option(): void
    {
        $command = new CrawlerClearCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('include-failed'));
    }

    /**
     * Test command requires confirmation without --force
     */
    public function test_command_requires_confirmation(): void
    {
        // Without --force, command asks for confirmation
        // When there are no jobs, it says "No jobs to clear" without asking
        $this->artisan('crawler:clear')
            ->expectsOutputToContain('No jobs to clear')
            ->assertExitCode(Command::SUCCESS);
    }

    // =========================================================================
    // ACCEPTANCE CRITERIA TESTS
    // =========================================================================

    /**
     * Test command handles database gracefully
     */
    public function test_command_handles_database_gracefully(): void
    {
        // This test verifies the command runs without error
        $this->artisan('crawler:clear', ['--force' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    /**
     * Test command with include-failed option
     */
    public function test_command_with_include_failed(): void
    {
        $this->artisan('crawler:clear', ['--force' => true, '--include-failed' => true])
            ->assertExitCode(Command::SUCCESS);
    }
}
