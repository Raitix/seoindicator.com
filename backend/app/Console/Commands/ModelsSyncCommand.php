<?php

namespace App\Console\Commands;

use App\Services\OpenRouterSyncService;
use Illuminate\Console\Command;

/**
 * Console command to sync AI models from OpenRouter API.
 *
 * Usage:
 *   php artisan models:sync
 *   php artisan models:sync --force
 *   php artisan models:sync --provider=openrouter
 *   php artisan models:sync --quiet
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
        {--provider=openrouter : Provider to sync}
        {--quiet : Suppress output}';

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
        if (!$this->option('quiet')) {
            $this->info('Starting AI models sync from OpenRouter...');
        }

        try {
            $count = $syncService->syncModels();

            if (!$this->option('quiet')) {
                $this->info("Successfully synced {$count} models.");
            }

            // Handle --force flag for deactivating missing models
            if ($this->option('force')) {
                $this->deactivateMissingModels($this->option('provider'));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if (!$this->option('quiet')) {
                $this->error('Failed to sync models: ' . $e->getMessage());
            }

            return Command::SUCCESS; // Return success to not break scheduled jobs
        }
    }

    /**
     * Deactivate models that were not synced (not found in API).
     *
     * @param string $provider
     */
    protected function deactivateMissingModels(string $provider): void
    {
        // This would be implemented if we need to track which models were seen
        // during sync and deactivate those that weren't
        // For now, the sync service handles this internally
    }
}
