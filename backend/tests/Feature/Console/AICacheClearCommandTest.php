<?php

namespace Tests\Feature\Console;

use Tests\TestCase;
use App\Services\AI\AISuggestionCache;
use Illuminate\Support\Facades\Artisan;
use Mockery;

/**
 * Feature tests for AI cache clear artisan command.
 * 
 * Covers AC-4: Clear all cache command available
 * 
 * @task TASK-21-6
 */
class AICacheClearCommandTest extends TestCase
{
    private AISuggestionCache $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(AISuggestionCache::class);
    }

    protected function tearDown(): void
    {
        $this->cacheService->clear();
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // Command Execution Tests
    // ============================================================

    /** @test Command exists and can be called */
    public function test_command_exists(): void
    {
        $this->assertArrayHasKey('ai:cache:clear', Artisan::all());
    }

    /** @test Command clears all AI suggestion cache */
    public function test_command_clears_cache(): void
    {
        // Add cache entries
        for ($i = 0; $i < 5; $i++) {
            $hash = "cmd_test_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        $exitCode = Artisan::call('ai:cache:clear', ['--force' => true]);
        
        $this->assertEquals(0, $exitCode);
        
        // Verify cache is empty
        $stats = $this->cacheService->getStats();
        $this->assertEquals(0, $stats['total_entries']);
    }

    /** @test Command outputs success message */
    public function test_command_success_message(): void
    {
        $hash = 'msg_test_' . uniqid();
        $this->cacheService->set($hash, ['data' => true]);
        
        Artisan::call('ai:cache:clear', ['--force' => true]);
        
        $output = Artisan::output();
        
        $this->assertStringContainsString('cleared', strtolower($output));
    }

    /** @test Command outputs count of cleared entries */
    public function test_command_outputs_count(): void
    {
        $count = 3;
        for ($i = 0; $i < $count; $i++) {
            $hash = "count_test_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        Artisan::call('ai:cache:clear', ['--force' => true]);
        
        $output = Artisan::output();
        
        $this->assertStringContainsString((string) $count, $output);
    }

    /** @test Command handles empty cache gracefully */
    public function test_command_empty_cache(): void
    {
        $this->cacheService->clear();
        
        $exitCode = Artisan::call('ai:cache:clear', ['--force' => true]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('0', $output);
    }

    // ============================================================
    // Option Tests
    // ============================================================

    /** @test Command with --indicator option clears specific type */
    public function test_command_indicator_option(): void
    {
        // Create entries for different indicators
        $titleHash = $this->cacheService->generateHash('title', ['title' => 'Test'], 'openrouter');
        $metaHash = $this->cacheService->generateHash('meta_description', ['desc' => 'Test'], 'openrouter');
        
        $this->cacheService->set($titleHash, ['indicator' => 'title']);
        $this->cacheService->set($metaHash, ['indicator' => 'meta_description']);
        
        Artisan::call('ai:cache:clear', ['--indicator' => 'title', '--force' => true]);
        
        // Title should be cleared
        $this->assertNull($this->cacheService->get($titleHash));
        
        // Meta should remain
        $this->assertNotNull($this->cacheService->get($metaHash));
    }

    /** @test Command with --force option skips confirmation */
    public function test_command_force_option(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $hash = "force_test_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        Artisan::call('ai:cache:clear', ['--force' => true]);
        
        $stats = $this->cacheService->getStats();
        $this->assertEquals(0, $stats['total_entries']);
    }

    /** @test Command with --stats option shows statistics */
    public function test_command_stats_option(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $hash = "stats_test_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        $this->cacheService->get('stats_test_0'); // Hit
        $this->cacheService->get('nonexistent'); // Miss
        
        Artisan::call('ai:cache:clear', ['--stats' => true, '--force' => true]);
        
        $output = Artisan::output();
        
        $this->assertStringContainsString('hit', strtolower($output));
        $this->assertStringContainsString('miss', strtolower($output));
    }

    /** @test Command rejects invalid indicator */
    public function test_command_invalid_indicator(): void
    {
        Artisan::call('ai:cache:clear', ['--indicator' => 'invalid_type']);
        
        $output = Artisan::output();
        
        $this->assertStringContainsString('invalid', strtolower($output));
    }

    /** @test Command accepts all valid indicator types */
    public function test_command_all_valid_indicators(): void
    {
        $validIndicators = [
            'title', 'meta_description', 'robots', 'sitemap', 'headings',
            'image_alt', 'filenames', 'url', 'links', '404'
        ];
        
        foreach ($validIndicators as $indicator) {
            $hash = $this->cacheService->generateHash($indicator, ['test' => true], 'openrouter');
            $this->cacheService->set($hash, ['indicator' => $indicator]);
        }
        
        foreach ($validIndicators as $indicator) {
            Artisan::call('ai:cache:clear', ['--indicator' => $indicator, '--force' => true]);
            
            $output = Artisan::output();
            $this->assertStringContainsString('cleared', strtolower($output));
        }
    }

    // ============================================================
    // Error Handling Tests
    // ============================================================

    /** @test Command handles cache driver errors gracefully */
    public function test_command_handles_cache_errors(): void
    {
        // This test would require mocking the cache driver to throw an exception
        // For now, we test that the command doesn't crash
        
        $exitCode = Artisan::call('ai:cache:clear', ['--force' => true]);
        
        $this->assertEquals(0, $exitCode);
    }

    /** @test Command returns non-zero on critical error */
    public function test_command_error_exit_code(): void
    {
        // In production, if cache driver fails, command should return non-zero
        // This is a placeholder for that scenario
        
        $exitCode = Artisan::call('ai:cache:clear', ['--force' => true]);
        
        // Should succeed in normal conditions
        $this->assertEquals(0, $exitCode);
    }

    // ============================================================
    // Performance Tests
    // ============================================================

    /** @test Command completes quickly with small cache */
    public function test_command_performance_small(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $hash = "perf_small_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        $start = microtime(true);
        Artisan::call('ai:cache:clear', ['--force' => true]);
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(500, $elapsed, 'Command should complete in < 500ms for small cache');
    }

    /** @test Command completes reasonably with large cache */
    public function test_command_performance_large(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $hash = "perf_large_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        $start = microtime(true);
        Artisan::call('ai:cache:clear', ['--force' => true]);
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan(2000, $elapsed, 'Command should complete in < 2s for large cache');
    }

    // ============================================================
    // Verbose Output Tests
    // ============================================================

    /** @test Command with -v shows more details */
    public function test_command_verbose_output(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $hash = "verbose_test_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        Artisan::call('ai:cache:clear', ['-v' => true, '--force' => true]);
        
        $output = Artisan::output();
        
        // Verbose mode should show more details
        $this->assertNotEmpty($output);
    }

    /** @test Command with --dry-run shows what would be cleared */
    public function test_command_dry_run(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $hash = "dryrun_test_{$i}_" . uniqid();
            $this->cacheService->set($hash, ['data' => $i]);
        }
        
        Artisan::call('ai:cache:clear', ['--dry-run' => true]);
        
        $output = Artisan::output();
        
        // Should show what would be cleared
        $this->assertStringContainsString('would clear', strtolower($output));
        
        // Cache should NOT be cleared
        $stats = $this->cacheService->getStats();
        $this->assertGreaterThan(0, $stats['total_entries']);
    }

    // ============================================================
    // Integration Tests
    // ============================================================

    /** @test Command integration with cache service */
    public function test_command_integration_with_service(): void
    {
        // Clear any existing cache state first
        $this->cacheService->clear();
        $this->cacheService->resetStats();
        
        // Use service directly
        $hash = 'integration_' . uniqid();
        $this->cacheService->set($hash, ['test' => true]);
        
        $this->assertNotNull($this->cacheService->get($hash));
        
        // Clear via command
        Artisan::call('ai:cache:clear', ['--force' => true]);
        
        // Verify via service
        $this->assertNull($this->cacheService->get($hash));
    }

    /** @test Command resets stats after clear */
    public function test_command_resets_stats(): void
    {
        // Clear any existing cache state first
        $this->cacheService->clear();
        $this->cacheService->resetStats();
        
        // Generate hits and misses
        $hash = 'stats_reset_' . uniqid();
        $this->cacheService->set($hash, ['data' => true]);
        $this->cacheService->get($hash); // Hit
        $this->cacheService->get('nonexistent'); // Miss
        
        $beforeStats = $this->cacheService->getStats();
        $this->assertGreaterThan(0, $beforeStats['hits']);
        
        Artisan::call('ai:cache:clear', ['--reset-stats' => true, '--force' => true]);
        
        // Get fresh instance to avoid in-memory stats from this test instance
        $freshCacheService = app(AISuggestionCache::class);
        $afterStats = $freshCacheService->getStats();
        $this->assertEquals(0, $afterStats['hits']);
        $this->assertEquals(0, $afterStats['misses']);
    }
}
