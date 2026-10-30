<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Crawler Status Command.
 *
 * Shows the current status of crawler queues including:
 * - Pending jobs count
 * - Failed jobs count
 * - Active workers (estimated)
 * - Queue statistics
 *
 * @see TASK-22-16
 */
class CrawlerStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:status
                            {--queue=* : Specific queue(s) to check (default: all crawler queues)}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show crawler queue status and statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $queues = $this->option('queue');
        $asJson = $this->option('json');
        
        if (empty($queues)) {
            $queues = ['crawler-high', 'crawler', 'crawler-low'];
        }

        $stats = $this->gatherStats($queues);

        if ($asJson) {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $this->displayTable($stats);

        return Command::SUCCESS;
    }

    /**
     * Gather statistics for all queues.
     *
     * @param array $queues
     * @return array
     */
    protected function gatherStats(array $queues): array
    {
        $stats = [
            'queues' => [],
            'totals' => [
                'pending' => 0,
                'failed' => 0,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        foreach ($queues as $queue) {
            $pending = $this->getPendingJobsCount($queue);
            $failed = $this->getFailedJobsCount($queue);

            $stats['queues'][$queue] = [
                'pending' => $pending,
                'failed' => $failed,
            ];

            $stats['totals']['pending'] += $pending;
            $stats['totals']['failed'] += $failed;
        }

        // Add worker information
        $stats['workers'] = $this->getWorkerInfo();

        return $stats;
    }

    /**
     * Get the count of pending jobs in a queue.
     *
     * @param string $queue
     * @return int
     */
    protected function getPendingJobsCount(string $queue): int
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
     * Get the count of failed jobs for a queue.
     *
     * @param string $queue
     * @return int
     */
    protected function getFailedJobsCount(string $queue): int
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
     * Get worker information.
     *
     * @return array
     */
    protected function getWorkerInfo(): array
    {
        // Check for active worker processes
        // This is a best-effort estimate based on cache
        $lastWorkerPing = Cache::get('crawler:worker:last_ping');
        
        return [
            'status' => $lastWorkerPing ? 'active' : 'unknown',
            'last_ping' => $lastWorkerPing,
            'restart_signal' => Cache::get('illuminate:queue:restart'),
        ];
    }

    /**
     * Display the statistics as a table.
     *
     * @param array $stats
     */
    protected function displayTable(array $stats): void
    {
        $this->info('Crawler Queue Status');
        $this->line(str_repeat('-', 50));
        $this->newLine();

        // Queue table
        $rows = [];
        foreach ($stats['queues'] as $queue => $data) {
            $rows[] = [
                $queue,
                $data['pending'],
                $data['failed'],
            ];
        }

        $this->table(
            ['Queue', 'Pending', 'Failed'],
            $rows
        );

        // Totals
        $this->newLine();
        $this->line(sprintf(
            '<info>Totals:</info> %d pending, %d failed',
            $stats['totals']['pending'],
            $stats['totals']['failed']
        ));

        // Worker info
        $this->newLine();
        $this->line('<info>Workers:</info>');
        $this->line(sprintf(
            '  Status: %s',
            $stats['workers']['status']
        ));
        
        if ($stats['workers']['last_ping']) {
            $this->line(sprintf(
                '  Last ping: %s',
                $stats['workers']['last_ping']
            ));
        }

        // Restart signal
        if ($restartTs = $stats['workers']['restart_signal']) {
            $this->line(sprintf(
                '  Restart signal: %s',
                date('Y-m-d H:i:s', $restartTs)
            ));
        }

        $this->newLine();
        $this->line(sprintf('Timestamp: %s', $stats['timestamp']));
    }
}
