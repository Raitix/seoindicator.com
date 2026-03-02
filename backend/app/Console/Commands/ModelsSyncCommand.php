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
     * The sync service instance.
     */
    private OpenRouterSyncService $syncService;

    /**
     * Create a new command instance.
     */
    public function __construct(OpenRouterSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $quiet = $this->option('quiet');
        $force = $this->option('force');
        $provider = $this->option('provider');

        if (!$quiet) {
            $this->info("Starting model sync from {$provider}...");
        }

        try {
            // Sync models from API (don't deactivate by default)
            $count = $this->syncService->syncModels(deactivateMissing: false);

            if (!$quiet) {
                $this->info("Successfully synced {$count} models.");
            }

            // Deactivate missing models only if --force flag is set
            if ($force && $provider === 'openrouter') {
                // Get models that were just synced
                $syncedIds = \App\Models\AIModel::where('provider', 'openrouter')
                    ->whereNotNull('last_synced_at')
                    ->where('last_synced_at', '>=', now()->subMinutes(5))
                    ->pluck('model_id')
                    ->toArray();

                $deactivated = $this->syncService->deactivateMissingModels($syncedIds);

                if (!$quiet && $deactivated > 0) {
                    $this->info("Deactivated {$deactivated} missing models.");
                }
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            if (!$quiet) {
                $this->error("Sync failed: {$e->getMessage()}");
            }

            // Log the error for debugging
            \Illuminate\Support\Facades\Log::error('Model sync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return success anyway to not break scheduler
            // The error is logged and handled gracefully
            return self::SUCCESS;
        }
    }
}
