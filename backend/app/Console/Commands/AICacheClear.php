<?php

namespace App\Console\Commands;

use App\Services\AI\AISuggestionCache;
use Illuminate\Console\Command;

/**
 * Clear AI Suggestion Cache Command.
 *
 * Artisan command for clearing AI suggestion cache entries.
 * Supports clearing all entries or by specific indicator.
 *
 * @task TASK-21-6
 */
class AICacheClear extends Command
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
                            {--reset-stats : Reset statistics after clearing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear AI suggestion cache entries';

    /**
     * Execute the console command.
     */
    public function handle(AISuggestionCache $cacheService): int
    {
        $indicator = $this->option('indicator');
        $force = $this->option('force');
        $showStats = $this->option('stats');
        $dryRun = $this->option('dry-run');
        $resetStats = $this->option('reset-stats');

        // Validate indicator if provided
        if ($indicator && !$cacheService->isValidIndicator($indicator)) {
            $this->error("Invalid indicator: {$indicator}");
            $this->info('Valid indicators: ' . implode(', ', $cacheService->getValidIndicators()));
            return self::FAILURE;
        }

        // Show statistics if requested
        if ($showStats) {
            $this->displayStats($cacheService);
        }

        // Dry run mode
        if ($dryRun) {
            return $this->dryRun($cacheService, $indicator);
        }

        // Confirm before clearing (unless --force or running in non-interactive mode)
        if (!$force && $this->input->isInteractive() && !$this->confirmClearing($indicator)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        // Clear cache
        if ($indicator) {
            $count = $cacheService->invalidateByIndicator($indicator);
            $this->info("Cleared {$count} cache entries for indicator: {$indicator}");
        } else {
            $count = $cacheService->clear();
            $this->info("Cleared {$count} AI suggestion cache entries.");
        }

        // Reset stats if requested
        if ($resetStats) {
            $cacheService->resetStats();
            $this->info('Statistics reset.');
        }

        return self::SUCCESS;
    }

    /**
     * Display cache statistics.
     */
    private function displayStats(AISuggestionCache $cacheService): void
    {
        $stats = $cacheService->getStats();

        $this->newLine();
        $this->info('=== AI Suggestion Cache Statistics ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Entries', $stats['total_entries']],
                ['Cache Hits', $stats['hits']],
                ['Cache Misses', $stats['misses']],
                ['Hit Ratio', $stats['hit_ratio'] * 100 . '%'],
                ['Evictions', $stats['evictions']],
                ['Eviction Rate', $stats['eviction_rate'] * 100 . '%'],
            ]
        );

        if (!empty($stats['by_indicator'])) {
            $this->newLine();
            $this->info('=== Entries by Indicator ===');
            $rows = [];
            foreach ($stats['by_indicator'] as $ind => $count) {
                $rows[] = [$ind, $count];
            }
            $this->table(['Indicator', 'Count'], $rows);
        }

        $this->newLine();
    }

    /**
     * Run in dry-run mode (show what would be cleared).
     */
    private function dryRun(AISuggestionCache $cacheService, ?string $indicator): int
    {
        $stats = $cacheService->getStats();

        if ($indicator) {
            $count = $stats['by_indicator'][$indicator] ?? 0;
            $this->info("Would clear {$count} cache entries for indicator: {$indicator}");
        } else {
            $this->info("Would clear {$stats['total_entries']} AI suggestion cache entries.");
        }

        $this->warn('Dry run mode - no changes were made.');

        return self::SUCCESS;
    }

    /**
     * Confirm before clearing cache.
     */
    private function confirmClearing(?string $indicator): bool
    {
        if ($indicator) {
            return $this->confirm("Are you sure you want to clear cache for indicator '{$indicator}'?");
        }

        return $this->confirm('Are you sure you want to clear ALL AI suggestion cache?');
    }
}
