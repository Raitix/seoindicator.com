<?php

namespace App\Console\Commands;

use App\Models\AIModel;
use App\Services\OpenRouterSyncService;
use Illuminate\Console\Command;

/**
 * Console command to sync AI models from OpenRouter API.
 *
 * Usage:
 *   php artisan models:sync
 *   php artisan models:sync --force
 *   php artisan models:sync --provider=openrouter
 *   php artisan models:sync -q (quiet mode - built-in Laravel option)
 */
class ModelsSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'models:sync
        {--force : Deactivate models not found in API}
        {--provider=openrouter : Provider to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync AI models from OpenRouter API';

    /**
     * Execute the console command.
     */
    public function handle(OpenRouterSyncService $syncService): int
    {
        $this->info('Starting AI models sync from OpenRouter...');

        try {
            // By default, do NOT deactivate missing models
            // Only deactivate when --force is explicitly passed
            $deactivateMissing = (bool) $this->option('force');
            
            // Sync models
            $count = $syncService->syncModels(deactivateMissing: $deactivateMissing);

            $this->info("Successfully synced {$count} models.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to sync models: ' . $e->getMessage());

            return Command::SUCCESS; // Return success to not break scheduled jobs
        }
    }
}
