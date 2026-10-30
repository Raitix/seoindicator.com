<?php

namespace App\Console\Commands;

use App\Services\AI\AISuggestionCache;
use Illuminate\Console\Command;

/**
 * Artisan command to clear AI suggestion cache.
 *
 * Provides CLI access to manage the AI suggestion cache,
 * including clearing by indicator type and viewing statistics.
 *
 * @task TASK-21-6
 */
class AICacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:cache:clear
                            {--indicator= : Clear cache for a specific indicator type}
                            {--force : Skip confirmation prompt}
                            {--stats : Show cache statistics before clearing}
                            {--dry-run : Show what would be cleared without actually clearing}
                            {--reset-stats : Reset statistics counters after clearing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear AI suggestion cache entries';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected AISuggestionCache $cacheService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $indicator = $this->option('indicator');
        $force = $this->option('force');
        $showStats = $this->option('stats');
        $dryRun = $this->option('dry-run');
        $resetStats = $this->option('reset-stats');

        // Show stats if requested
        if ($showStats) {
            $this->displayStats();
            $this->newLine();
        }

        // Validate indicator if provided
        if ($indicator && !$this->cacheService->isValidIndicator($indicator)) {
            $this->error("Invalid indicator: {$indicator}");
            $this->line('Valid indicators: ' . implode(', ', $this->cacheService->getValidIndicators()));
            return self::FAILURE;
        }

        // Dry run mode
        if ($dryRun) {
            return $this->dryRun($indicator);
        }

        // Confirm unless forced
        if (!$force && !$this->confirmClear($indicator)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        // Clear cache
        $count = $indicator
            ? $this->cacheService->invalidateByIndicator($indicator)
            : $this->cacheService->clear();

        // Reset stats if requested
        if ($resetStats) {
            $this->cacheService->resetStats();
        }

        // Output result
        $message = $indicator
            ? "Cleared {$count} cache entries for indicator: {$indicator}"
            : "Cleared {$count} AI suggestion cache entries";

        $this->info($message);

        return self::SUCCESS;
    }

    /**
     * Display cache statistics.
     */
    protected function displayStats(): void
    {
        $stats = $this->cacheService->getStats();

        $this->info('Cache Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Entries', $stats['total_entries']],
                ['Hits', $stats['hits']],
                ['Misses', $stats['misses']],
                ['Hit Ratio', number_format($stats['hit_ratio'] * 100, 2) . '%'],
                ['Evictions', $stats['evictions']],
            ]
        );

        if (!empty($stats['by_indicator'])) {
            $this->newLine();
            $this->info('By Indicator:');
            $rows = [];
            foreach ($stats['by_indicator'] as $ind => $count) {
                $rows[] [$ind] = $count;
            }
            $this->table(['Indicator', 'Count'], $rows);
        }
    }

    /**
     * Run in dry-run mode.
     */
    protected function dryRun(?string $indicator): int
    {
        $stats = $this->cacheService->getStats();

        if ($indicator) {
            $byIndicator = $stats['by_indicator'] ?? [];
            $count = $byIndicator[$indicator] ?? 0;
            $this->info("Would clear {$count} cache entries for indicator: {$indicator}");
        } else {
            $this->info("Would clear {$stats['total_entries']} AI suggestion cache entries");
        }

        return self::SUCCESS;
    }

    /**
     * Confirm cache clear operation.
     */
    protected function confirmClear(?string $indicator): bool
    {
        $stats = $this->cacheService->getStats();
        $count = $indicator
            ? ($stats['by_indicator'][$indicator] ?? 0)
            : $stats['total_entries'];

        $target = $indicator
            ? "cache entries for indicator '{$indicator}'"
            : 'all AI suggestion cache entries';

        return $this->confirm(
            "Are you sure you want to clear {$count} {$target}?",
            false
        );
    }
}
