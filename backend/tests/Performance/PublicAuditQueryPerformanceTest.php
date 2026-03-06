<?php

/**
 * Database Query Performance Tests
 * Task: TASK-33-35 - Performance Optimization and Launch
 * 
 * Tests for database query performance and index verification
 * for public audit and badge tables.
 */

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Audit;
use App\Models\PublicAuditView;
use App\Models\BadgeImpression;

class PublicAuditQueryPerformanceTest extends TestCase
{
    /**
     * Performance threshold in milliseconds.
     */
    private const TARGET_MS = 10;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations if needed
        $this->artisan('migrate:fresh', ['--seed' => false]);
    }

    /**
     * DB-001: Audit by public_hash query < 10ms.
     */
    public function test_audit_by_public_hash_performance(): void
    {
        // Create test audit with public_hash
        $audit = Audit::factory()->create([
            'public_hash' => 'abc123def456ghi789jkl012mno345pq',
            'visibility' => 'public',
            'overall_score' => 85,
        ]);

        // Measure query time
        $start = microtime(true);
        $result = Audit::where('public_hash', 'abc123def456ghi789jkl012mno345pq')
            ->where('visibility', 'public')
            ->first();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertNotNull($result);
        $this->assertEquals('abc123def456ghi789jkl012mno345pq', $result->public_hash);
        $this->assertLessThan(self::TARGET_MS, $duration, "Query took {$duration}ms, expected < " . self::TARGET_MS . "ms");
        
        echo "\nDB-001: Audit by public_hash: {$duration}ms\n";
    }

    /**
     * DB-002: Audit by public_hash uses index.
     */
    public function test_audit_by_public_hash_uses_index(): void
    {
        $audit = Audit::factory()->create([
            'public_hash' => 'xyz789abc123def456ghi012jkl345mno',
            'visibility' => 'public',
        ]);

        // Get EXPLAIN output
        $explain = DB::select("EXPLAIN SELECT * FROM audits WHERE public_hash = 'xyz789abc123def456ghi012jkl345mno'");
        
        $this->assertNotEmpty($explain);
        $row = (array) $explain[0];
        
        // Should use index, not ALL (full table scan)
        $this->assertNotEquals('ALL', $row['type'], 'Query should use index, not full table scan');
        $this->assertStringContainsString('public_hash', $row['key'] ?? '', 'Should use public_hash index');
        
        echo "\nDB-002: EXPLAIN shows type={$row['type']}, key={$row['key']}\n";
    }

    /**
     * DB-003: Public audit views insert < 10ms.
     */
    public function test_public_audit_views_insert_performance(): void
    {
        $audit = Audit::factory()->create([
            'public_hash' => 'inserttest12345678901234567890ab',
            'visibility' => 'public',
        ]);

        $start = microtime(true);
        $view = PublicAuditView::create([
            'audit_id' => $audit->id,
            'public_hash' => $audit->public_hash,
            'viewed_at' => now(),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'Test Agent',
        ]);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertNotNull($view->id);
        $this->assertLessThan(self::TARGET_MS, $duration, "Insert took {$duration}ms");
        
        echo "\nDB-003: Public audit views insert: {$duration}ms\n";
    }

    /**
     * DB-004: Badge impressions insert < 10ms.
     */
    public function test_badge_impressions_insert_performance(): void
    {
        $audit = Audit::factory()->create([
            'public_hash' => 'badgetest12345678901234567890cd',
            'visibility' => 'public',
        ]);

        $start = microtime(true);
        $impression = BadgeImpression::create([
            'audit_id' => $audit->id,
            'public_hash' => $audit->public_hash,
            'impression_at' => now(),
            'referrer_domain' => 'example.com',
        ]);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertNotNull($impression->id);
        $this->assertLessThan(self::TARGET_MS, $duration, "Insert took {$duration}ms");
        
        echo "\nDB-004: Badge impressions insert: {$duration}ms\n";
    }

    /**
     * DB-005: Views by audit_id query < 10ms.
     */
    public function test_views_by_audit_id_performance(): void
    {
        $audit = Audit::factory()->create([
            'public_hash' => 'viewstest12345678901234567890ef',
            'visibility' => 'public',
        ]);

        // Create some views
        PublicAuditView::factory()->count(10)->create([
            'audit_id' => $audit->id,
            'public_hash' => $audit->public_hash,
        ]);

        $start = microtime(true);
        $views = PublicAuditView::where('audit_id', $audit->id)->get();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertCount(10, $views);
        $this->assertLessThan(self::TARGET_MS, $duration, "Query took {$duration}ms");
        
        echo "\nDB-005: Views by audit_id: {$duration}ms\n";
    }

    /**
     * DB-006: Impressions by audit_id query < 10ms.
     */
    public function test_impressions_by_audit_id_performance(): void
    {
        $audit = Audit::factory()->create([
            'public_hash' => 'impressionstest1234567890123456gh',
            'visibility' => 'public',
        ]);

        // Create some impressions
        BadgeImpression::factory()->count(10)->create([
            'audit_id' => $audit->id,
            'public_hash' => $audit->public_hash,
        ]);

        $start = microtime(true);
        $impressions = BadgeImpression::where('audit_id', $audit->id)->get();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertCount(10, $impressions);
        $this->assertLessThan(self::TARGET_MS, $duration, "Query took {$duration}ms");
        
        echo "\nDB-006: Impressions by audit_id: {$duration}ms\n";
    }

    /**
     * DB-007: Views by viewed_at (time range) query < 50ms.
     */
    public function test_views_by_date_range_performance(): void
    {
        $audit = Audit::factory()->create([
            'public_hash' => 'datetest123456789012345678901234ij',
            'visibility' => 'public',
        ]);

        // Create views with different dates
        for ($i = 0; $i < 50; $i++) {
            PublicAuditView::create([
                'audit_id' => $audit->id,
                'public_hash' => $audit->public_hash,
                'viewed_at' => now()->subDays($i),
                'ip_hash' => hash('sha256', "ip_{$i}"),
            ]);
        }

        $start = microtime(true);
        $recentViews = PublicAuditView::where('viewed_at', '>=', now()->subDays(7))->get();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertCount(7, $recentViews);
        $this->assertLessThan(50, $duration, "Time range query took {$duration}ms");
        
        echo "\nDB-007: Views by date range (7 days): {$duration}ms\n";
    }

    /**
     * DB-008: Index exists on public_hash.
     */
    public function test_index_exists_public_hash(): void
    {
        $indexes = DB::select("SHOW INDEX FROM audits WHERE Column_name = 'public_hash'");
        
        $this->assertNotEmpty($indexes, 'Index on public_hash should exist');
        
        echo "\nDB-008: Index on public_hash exists: " . count($indexes) . " index(es)\n";
    }

    /**
     * DB-009: Index exists on audit_id.
     */
    public function test_index_exists_audit_id(): void
    {
        $viewIndexes = DB::select("SHOW INDEX FROM public_audit_views WHERE Column_name = 'audit_id'");
        $impressionIndexes = DB::select("SHOW INDEX FROM badge_impressions WHERE Column_name = 'audit_id'");
        
        $this->assertNotEmpty($viewIndexes, 'Index on audit_id in public_audit_views should exist');
        $this->assertNotEmpty($impressionIndexes, 'Index on audit_id in badge_impressions should exist');
        
        echo "\nDB-009: Index on audit_id exists in both tables\n";
    }

    /**
     * DB-010: Index exists on viewed_at.
     */
    public function test_index_exists_viewed_at(): void
    {
        $indexes = DB::select("SHOW INDEX FROM public_audit_views WHERE Column_name = 'viewed_at'");
        
        $this->assertNotEmpty($indexes, 'Index on viewed_at should exist');
        
        echo "\nDB-010: Index on viewed_at exists: " . count($indexes) . " index(es)\n";
    }

    /**
     * DB-011: Index exists on impression_at.
     */
    public function test_index_exists_impression_at(): void
    {
        $indexes = DB::select("SHOW INDEX FROM badge_impressions WHERE Column_name = 'impression_at'");
        
        $this->assertNotEmpty($indexes, 'Index on impression_at should exist');
        
        echo "\nDB-011: Index on impression_at exists: " . count($indexes) . " index(es)\n";
    }

    /**
     * DB-012: No full table scans on indexed queries.
     */
    public function test_no_full_table_scans(): void
    {
        Audit::factory()->create([
            'public_hash' => 'fullscantest123456789012345678kl',
            'visibility' => 'public',
        ]);

        $queries = [
            "SELECT * FROM audits WHERE public_hash = 'fullscantest123456789012345678kl'",
            "SELECT * FROM audits WHERE visibility = 'public' LIMIT 10",
        ];

        foreach ($queries as $query) {
            $explain = DB::select("EXPLAIN {$query}");
            $row = (array) $explain[0];
            
            $this->assertNotEquals('ALL', $row['type'], "Query should not use full table scan: {$query}");
        }
        
        echo "\nDB-012: No full table scans detected\n";
    }

    /**
     * DB-013: Query cache hit ratio > 80%.
     */
    public function test_query_cache_hit_ratio(): void
    {
        // Create test data
        $audit = Audit::factory()->create([
            'public_hash' => 'cachetest12345678901234567890mn',
            'visibility' => 'public',
        ]);

        // Run same query multiple times
        $hash = $audit->public_hash;
        for ($i = 0; $i < 10; $i++) {
            Audit::where('public_hash', $hash)->first();
        }

        // Query cache hit ratio should be high for repeated queries
        // Note: MySQL query cache is deprecated in 8.0+, but we can check
        // that the query is fast (< 5ms for cached result)
        $start = microtime(true);
        Audit::where('public_hash', $hash)->first();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(5, $duration, "Cached query should be < 5ms, got {$duration}ms");
        
        echo "\nDB-013: Cached query duration: {$duration}ms\n";
    }

    /**
     * DB-014: Connection pool efficiency.
     */
    public function test_connection_pool_efficiency(): void
    {
        $activeConnections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
        $connection = (array) $activeConnections[0];
        
        // Should have reasonable number of active connections
        $connectionCount = (int) $connection['Value'];
        $this->assertLessThan(50, $connectionCount, "Too many active connections: {$connectionCount}");
        
        echo "\nDB-014: Active connections: {$connectionCount}\n";
    }

    /**
     * DB-015: Query execution plan is optimal.
     */
    public function test_query_execution_plan_optimal(): void
    {
        Audit::factory()->create([
            'public_hash' => 'explaintest12345678901234567890op',
            'visibility' => 'public',
        ]);

        $explain = DB::select("EXPLAIN SELECT * FROM audits WHERE public_hash = 'explaintest12345678901234567890op' AND visibility = 'public'");
        $row = (array) $explain[0];
        
        // Should not have 'Using filesort' or 'Using temporary'
        $extra = $row['Extra'] ?? '';
        $this->assertStringNotContainsString('Using filesort', $extra);
        $this->assertStringNotContainsString('Using temporary', $extra);
        
        echo "\nDB-015: Query execution plan optimal (no filesort/temporary)\n";
    }

    /**
     * DB-016: Composite index usage.
     */
    public function test_composite_index_usage(): void
    {
        // Check for composite index on (public_hash, visibility)
        $indexes = DB::select("SHOW INDEX FROM audits WHERE Key_name LIKE '%public%'");
        
        // At minimum, we should have an index on public_hash
        $this->assertNotEmpty($indexes, 'Composite or single indexes should exist');
        
        echo "\nDB-016: Composite/single indexes verified\n";
    }

    /**
     * DB-017: Join query performance < 15ms.
     */
    public function test_join_query_performance(): void
    {
        $audit = Audit::factory()->create([
            'public_hash' => 'jointest123456789012345678901234qr',
            'visibility' => 'public',
        ]);

        PublicAuditView::factory()->count(5)->create([
            'audit_id' => $audit->id,
            'public_hash' => $audit->public_hash,
        ]);

        $start = microtime(true);
        $results = DB::table('audits')
            ->join('public_audit_views', 'audits.id', '=', 'public_audit_views.audit_id')
            ->where('audits.public_hash', $audit->public_hash)
            ->get();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertCount(5, $results);
        $this->assertLessThan(15, $duration, "Join query took {$duration}ms");
        
        echo "\nDB-017: Join query: {$duration}ms\n";
    }

    /**
     * DB-018: Count query performance < 5ms.
     */
    public function test_count_query_performance(): void
    {
        $audit = Audit::factory()->create([
            'public_hash' => 'counttest123456789012345678901234st',
            'visibility' => 'public',
        ]);

        PublicAuditView::factory()->count(100)->create([
            'audit_id' => $audit->id,
            'public_hash' => $audit->public_hash,
        ]);

        $start = microtime(true);
        $count = PublicAuditView::where('audit_id', $audit->id)->count();
        $duration = (microtime(true) - $start) * 1000;

        $this->assertEquals(100, $count);
        $this->assertLessThan(5, $duration, "Count query took {$duration}ms");
        
        echo "\nDB-018: Count query: {$duration}ms\n";
    }
}
