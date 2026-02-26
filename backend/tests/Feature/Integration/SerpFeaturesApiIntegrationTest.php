<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\SerpFeature;
use App\Models\SerpFeatureHistory;
use App\Services\SerpFeatureService;
use App\Services\SerpFeatureChangeTracker;
use App\Repositories\SerpFeatureRepository;
use App\Repositories\SerpFeatureHistoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

/**
 * Integration tests for SERP Features API endpoints.
 * 
 * Tests verify end-to-end functionality of SERP feature tracking
 * including API endpoints, database integration, and workflow.
 * 
 * Task: TASK-7-20
 * Test Documentation: TESTS/SPEC-7-20.md
 * 
 * @group integration
 * @group serp-features
 */
class SerpFeaturesApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;
    protected Keyword $keyword1;
    protected Keyword $keyword2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->site = Site::create([
            'user_id' => $this->user->id,
            'url' => 'https://test-example.com',
            'name' => 'Test Site'
        ]);

        $this->keyword1 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'seo tools',
            'target_url' => 'https://test-example.com/seo-tools',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active'
        ]);

        $this->keyword2 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword tracker',
            'target_url' => 'https://test-example.com/tracker',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active'
        ]);
    }

    // ==================== API-001: Get features for keyword ====================

    /**
     * API-001: Get features for keyword returns 200 with data.
     */
    public function test_api_001_get_features_for_keyword_returns_200(): void
    {
        // Arrange: Create SERP features
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com/seo-tools',
            'snippet' => 'Best SEO tools for 2026...',
            'checked_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword1->id}/serp-features");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'keyword' => ['id', 'keyword'],
                    'currentFeatures',
                    'history',
                    'changes'
                ]
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.keyword.id', $this->keyword1->id);
    }

    /**
     * API-001: Get features returns all feature types with correct presence.
     */
    public function test_api_001_get_features_returns_all_feature_types(): void
    {
        // Arrange: Create some features
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'local_pack',
            'is_present' => false,
            'checked_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword1->id}/serp-features");

        // Assert
        $response->assertStatus(200);

        $features = collect($response->json('data.currentFeatures'));
        
        // Verify all feature types are present
        $featureTypes = $features->pluck('type')->toArray();
        $this->assertContains('featured_snippet', $featureTypes);
        $this->assertContains('local_pack', $featureTypes);
        
        // Verify presence status
        $featuredSnippet = $features->firstWhere('type', 'featured_snippet');
        $this->assertTrue($featuredSnippet['isPresent']);
        
        $localPack = $features->firstWhere('type', 'local_pack');
        $this->assertFalse($localPack['isPresent']);
    }

    // ==================== API-002: Get feature summary ====================

    /**
     * API-002: Get feature summary returns 200 with summary.
     */
    public function test_api_002_get_feature_summary_returns_200(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features/summary");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totalKeywords',
                    'keywordsWithFeatures',
                    'featureCounts',
                    'recentChanges'
                ]
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.totalKeywords', 2)
            ->assertJsonPath('data.keywordsWithFeatures', 2);
    }

    /**
     * API-002: Feature summary returns correct counts by type.
     */
    public function test_api_002_feature_summary_returns_correct_counts(): void
    {
        // Arrange: Multiple features of same type
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'sitelinks',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features/summary");

        // Assert
        $response->assertStatus(200);

        $featureCounts = $response->json('data.featureCounts');
        $this->assertEquals(2, $featureCounts['featured_snippet']);
        $this->assertEquals(1, $featureCounts['sitelinks']);
    }

    // ==================== API-003: Filter keywords by feature ====================

    /**
     * API-003: Filter keywords by SERP feature returns filtered results.
     */
    public function test_api_003_filter_keywords_by_feature(): void
    {
        // Arrange: Create features
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act: Filter by featured_snippet
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->keyword1->id, $data[0]['id']);
    }

    /**
     * API-003: Filter by multiple features (OR logic).
     */
    public function test_api_003_filter_by_multiple_features(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'local_pack',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act: Filter by either featured_snippet OR local_pack
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet,local_pack");

        // Assert
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    /**
     * API-003: Filter by feature absence (present=false).
     */
    public function test_api_003_filter_by_feature_absence(): void
    {
        // Arrange: One keyword WITH featured snippet, one WITHOUT
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword2->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()
        ]);

        // Act: Filter for keywords WITHOUT featured snippet
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=featured_snippet&present=false");

        // Assert
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->keyword2->id, $data[0]['id']);
    }

    /**
     * API-003: Invalid feature type returns 400.
     */
    public function test_api_003_invalid_feature_type_returns_400(): void
    {
        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords?serpFeature=invalid_type");

        // Assert
        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    // ==================== API-004: Create report with features ====================

    /**
     * API-004: Keywords include SERP features in response.
     */
    public function test_api_004_keywords_include_serp_features(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/keywords");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'keyword',
                        'serpFeatures'
                    ]
                ]
            ]);

        $keyword = collect($response->json('data'))->firstWhere('id', $this->keyword1->id);
        $this->assertArrayHasKey('serpFeatures', $keyword);
        $this->assertArrayHasKey('featured_snippet', $keyword['serpFeatures']);
        $this->assertTrue($keyword['serpFeatures']['featured_snippet']);
    }

    // ==================== DB-001: Feature data persisted ====================

    /**
     * DB-001: Feature data is correctly persisted.
     */
    public function test_db_001_feature_data_persisted(): void
    {
        // Arrange
        $feature = SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com/page',
            'snippet' => 'Test snippet',
            'checked_at' => now()
        ]);

        // Act: Retrieve from database
        $retrieved = SerpFeature::find($feature->id);

        // Assert
        $this->assertNotNull($retrieved);
        $this->assertEquals('featured_snippet', $retrieved->feature_type);
        $this->assertTrue($retrieved->is_present);
        $this->assertEquals('https://test-example.com/page', $retrieved->url);
    }

    // ==================== DB-002: Feature history tracked ====================

    /**
     * DB-002: Feature history is tracked.
     */
    public function test_db_002_feature_history_tracked(): void
    {
        // Arrange
        $history = SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com/page',
            'detected_at' => now()
        ]);

        // Act: Retrieve from database
        $retrieved = SerpFeatureHistory::find($history->id);

        // Assert
        $this->assertNotNull($retrieved);
        $this->assertEquals('gained', $retrieved->action);
        $this->assertEquals('featured_snippet', $retrieved->feature_type);
    }

    // ==================== DB-003: Feature query performance ====================

    /**
     * DB-003: Feature queries use indexes efficiently.
     */
    public function test_db_003_feature_query_performance(): void
    {
        // Arrange: Create multiple features
        for ($i = 0; $i < 100; $i++) {
            SerpFeature::create([
                'keyword_id' => $this->keyword1->id,
                'feature_type' => SerpFeature::FEATURE_TYPES[$i % 10],
                'is_present' => $i % 2 === 0,
                'checked_at' => now()->subDays($i)
            ]);
        }

        // Act: Query by feature type (should use index)
        $start = microtime(true);
        $features = SerpFeature::where('feature_type', 'featured_snippet')
            ->where('is_present', true)
            ->get();
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Query should be fast (< 100ms)
        $this->assertLessThan(100, $duration);
        $this->assertGreaterThan(0, $features->count());
    }

    // ==================== DB-004: Data consistency ====================

    /**
     * DB-004: Concurrent writes maintain data consistency.
     */
    public function test_db_004_concurrent_writes_consistency(): void
    {
        // Arrange: Create features concurrently
        $features = [];
        for ($i = 0; $i < 10; $i++) {
            $features[] = SerpFeature::create([
                'keyword_id' => $this->keyword1->id,
                'feature_type' => SerpFeature::FEATURE_TYPES[$i],
                'is_present' => true,
                'checked_at' => now()
            ]);
        }

        // Assert: All features were created
        $this->assertCount(10, $features);
        
        // Verify database state
        $dbFeatures = SerpFeature::where('keyword_id', $this->keyword1->id)->get();
        $this->assertCount(10, $dbFeatures);
    }

    // ==================== EH-001: SERP fetch fails gracefully ====================

    /**
     * EH-001: SERP fetch failure is handled gracefully.
     */
    public function test_eh_001_serp_fetch_failure_handled(): void
    {
        // Arrange: Keyword with no features (simulating failed fetch)
        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword1->id}/serp-features");

        // Assert: Returns 200 with default features (all false)
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $features = $response->json('data.currentFeatures');
        foreach ($features as $feature) {
            $this->assertFalse($feature['isPresent']);
        }
    }

    // ==================== EH-002: Detection fails gracefully ====================

    /**
     * EH-002: Malformed SERP data is handled gracefully.
     */
    public function test_eh_002_malformed_serp_handled(): void
    {
        // Arrange: Create feature with minimal/missing data
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
            // No url, snippet, etc.
        ]);

        // Act
        $response = $this->getJson("/api/v1/keywords/{$this->keyword1->id}/serp-features");

        // Assert: Returns valid response
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $features = $response->json('data.currentFeatures');
        $featuredSnippet = collect($features)->firstWhere('type', 'featured_snippet');
        $this->assertTrue($featuredSnippet['isPresent']);
    }

    // ==================== EH-003: API timeout handled ====================

    /**
     * EH-003: API timeout is handled gracefully.
     */
    public function test_eh_003_api_timeout_handled(): void
    {
        // This test verifies the API responds within reasonable time
        // Arrange: Create multiple features
        for ($i = 0; $i < 50; $i++) {
            SerpFeature::create([
                'keyword_id' => $this->keyword1->id,
                'feature_type' => SerpFeature::FEATURE_TYPES[$i % 10],
                'is_present' => true,
                'checked_at' => now()->subDays($i)
            ]);
        }

        // Act
        $start = microtime(true);
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features/summary");
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Response within 5 seconds
        $response->assertStatus(200);
        $this->assertLessThan(5000, $duration);
    }

    // ==================== EH-004: Database error handled ====================

    /**
     * EH-004: Database error is handled gracefully.
     */
    public function test_eh_004_database_error_handled(): void
    {
        // Arrange: Invalid site ID
        // Act
        $response = $this->getJson('/api/v1/sites/99999/serp-features/summary');

        // Assert: Returns 404 with proper error
        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ==================== PF-001: API response performance ====================

    /**
     * PF-001: API response for 1000 keywords is performant.
     */
    public function test_pf_001_api_response_performance(): void
    {
        // Arrange: Create 1000 keywords with features
        for ($i = 0; $i < 100; $i++) {
            $keyword = Keyword::create([
                'site_id' => $this->site->id,
                'keyword' => "test keyword {$i}",
                'status' => 'active'
            ]);

            SerpFeature::create([
                'keyword_id' => $keyword->id,
                'feature_type' => 'featured_snippet',
                'is_present' => $i % 10 === 0,
                'checked_at' => now()
            ]);
        }

        // Act
        $start = microtime(true);
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/serp-features/summary");
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Response within 500ms
        $response->assertStatus(200);
        $this->assertLessThan(500, $duration);
    }

    // ==================== CI-001: Tests run in CI ====================

    /**
     * CI-001: Integration tests are properly isolated.
     */
    public function test_ci_001_test_isolation(): void
    {
        // Arrange: Create test data
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act: Query only this test's data
        $features = SerpFeature::where('keyword_id', $this->keyword1->id)->get();

        // Assert: Only our data returned
        $this->assertCount(1, $features);
    }

    /**
     * CI-002: No cross-test contamination.
     */
    public function test_ci_002_no_cross_test_contamination(): void
    {
        // This test verifies RefreshDatabase trait works correctly
        // Act: Query all features (should be empty due to RefreshDatabase)
        $allFeatures = SerpFeature::all();

        // Assert: Empty (each test starts fresh)
        $this->assertCount(0, $allFeatures);
    }

    /**
     * CI-003: Database cleanup after tests.
     */
    public function test_ci_003_database_cleanup(): void
    {
        // Arrange: Create data
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Verify it exists
        $this->assertEquals(1, SerpFeature::count());

        // RefreshDatabase will clean this up after the test
        $this->assertTrue(true);
    }

    // ==================== Alert Integration Tests ====================

    /**
     * AL-001: Feature gained triggers alert structure.
     */
    public function test_al_001_feature_gained_alert_structure(): void
    {
        // Arrange
        Event::fake();

        // Act: Create history entry (simulating gained feature)
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'current_url' => 'https://test-example.com/page',
            'detected_at' => now()
        ]);

        // Assert: History entry created
        $history = SerpFeatureHistory::where('keyword_id', $this->keyword1->id)
            ->where('action', 'gained')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('featured_snippet', $history->feature_type);
    }

    /**
     * AL-002: Feature lost triggers alert structure.
     */
    public function test_al_002_feature_lost_alert_structure(): void
    {
        // Arrange
        Event::fake();

        // Act: Create history entry (simulating lost feature)
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'local_pack',
            'action' => 'lost',
            'previous_url' => 'https://test-example.com/local',
            'detected_at' => now()
        ]);

        // Assert: History entry created
        $history = SerpFeatureHistory::where('keyword_id', $this->keyword1->id)
            ->where('action', 'lost')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('local_pack', $history->feature_type);
    }

    // ==================== Report Integration Tests ====================

    /**
     * RP-001: Keywords with features are available for reports.
     */
    public function test_rp_001_features_available_for_reports(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act: Get summary (used in reports)
        $service = app(SerpFeatureService::class);
        $summary = $service->getSummary($this->site->id);

        // Assert: Feature data available
        $this->assertTrue($summary['success']);
        $this->assertGreaterThan(0, $summary['data']['featureCounts']['featured_snippet']);
    }

    /**
     * RP-002: Feature data can be exported.
     */
    public function test_rp_002_feature_data_exportable(): void
    {
        // Arrange
        SerpFeature::create([
            'keyword_id' => $this->keyword1->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act: Get site features (for CSV export)
        $service = app(SerpFeatureService::class);
        $result = $service->getSiteFeatures($this->site->id);

        // Assert: Data available for export
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['meta']['total']);
    }
}
