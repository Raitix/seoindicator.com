<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Crawler Clear Command.
 *
 * Clears pending jobs from crawler queues.
 * Failed jobs are preserved unless explicitly cleared.
 *
 * @see TASK-22-16
 */
class CrawlerClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:clear
                            {--queue=* : Specific queue(s) to clear (default: all crawler queues)}
                            {--force : Force clear without confirmation}
                            {--include-failed : Also clear failed jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear pending jobs from crawler queues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $queues = $this->option('queue');
        $force = $this->option('force');
        $includeFailed = $this->option('include-failed');
        
        if (empty($queues)) {
            $queues = ['crawler-high', 'crawler', 'crawler-low'];
        }

        // Count jobs before clearing
        $totalPending = 0;
        $totalFailed = 0;
        
        foreach ($queues as $queue) {
            $totalPending += $this->countPendingJobs($queue);
            if ($includeFailed) {
                $totalFailed += $this->countFailedJobs($queue);
            }
        }

        if ($totalPending === 0 && (!$includeFailed || $totalFailed === 0)) {
            $this->info('No jobs to clear.');
            return Command::SUCCESS;
        }

        // Confirm before clearing (unless --force)
        if (!$force) {
            $message = sprintf(
                'This will delete %d pending job(s)%s. Continue?',
                $totalPending,
                $includeFailed ? " and {$totalFailed} failed job(s)" : ''
            );

            if (!$this->confirm($message)) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Clear pending jobs
        $clearedPending = 0;
        foreach ($queues as $queue) {
            $clearedPending += $this->clearPendingJobs($queue);
        }

        // Clear failed jobs if requested
        $clearedFailed = 0;
        if ($includeFailed) {
            foreach ($queues as $queue) {
                $clearedFailed += $this->clearFailedJobs($queue);
            }
        }

        // Output results
        $this->info(sprintf('Deleted %d pending job(s).', $clearedPending));
        
        if ($includeFailed) {
            $this->info(sprintf('Deleted %d failed job(s).', $clearedFailed));
        }

        return Command::SUCCESS;
    }

    /**
     * Count pending jobs in a queue.
     *
     * @param string $queue
     * @return int
     */
    protected function countPendingJobs(string $queue): int
    {
        try {
            return DB::table('jobs')
                ->where('queue', $queue)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Count failed jobs for a queue.
     *
     * @param string $queue
     * @return int
     */
    protected function countFailedJobs(string $queue): int
    {
        try {
            return DB::table('failed_jobs')
                ->where('queue', $queue)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Clear pending jobs from a queue.
     *
     * @param string $queue
     * @return int Number of jobs deleted
     */
    protected function clearPendingJobs(string $queue): int
    {
        try {
            return DB::table('jobs')
                ->where('queue', $queue)
                ->delete();
        } catch (\Throwable $e) {
            $this->error("Failed to clear queue {$queue}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear failed jobs for a queue.
     *
     * @param string $queue
     * @return int Number of jobs deleted
     */
    protected function clearFailedJobs(string $queue): int
    {
        try {
            return DB::table('failed_jobs')
                ->where('queue', $queue)
                ->delete();
        } catch (\Throwable $e) {
            $this->error("Failed to clear failed jobs for {$queue}: " . $e->getMessage());
            return 0;
        }
    }
}
