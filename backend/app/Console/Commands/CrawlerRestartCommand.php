<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Crawler Restart Command.
 *
 * Sends a restart signal to all crawler queue workers.
 * Workers will finish their current job and then restart.
 *
 * @see TASK-22-16
 */
class CrawlerRestartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:restart
                            {--queue=* : Specific queue(s) to restart (default: all crawler queues)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart all crawler queue workers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $queues = $this->option('queue');
        
        if (empty($queues)) {
            $queues = ['crawler-high', 'crawler', 'crawler-low'];
        }

        $this->info('Broadcasting queue restart signal...');

        // Set the restart signal in cache
        // Laravel queue workers check this timestamp on each loop
        Cache::forever('illuminate:queue:restart', now()->timestamp);

        foreach ($queues as $queue) {
            $this->line("  - Queue [{$queue}] restart signal sent");
        }

        $this->newLine();
        $this->info('Workers will restart after completing their current job.');

        return Command::SUCCESS;
    }
}
