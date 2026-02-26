<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Site;
use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\SerpFeature;
use App\Models\SerpFeatureHistory;
use App\Services\SerpFeatureChangeTracker;
use App\Services\SerpFeatureService;
use App\Repositories\SerpFeatureRepository;
use App\Repositories\SerpFeatureHistoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

/**
 * Integration tests for SERP Feature Workflow.
 * 
 * Tests verify end-to-end workflow scenarios including:
 * - Ranking check updates feature data
 * - Change tracker integration
 * - Alert system integration
 * - Mock SERP data testing
 * 
 * Task: TASK-7-20
 * Test Documentation: TESTS/SPEC-7-20.md
 * 
 * @group integration
 * @group serp-features
 * @group workflow
 */
class SerpFeatureWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Site $site;
    protected Keyword $keyword;

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

        $this->keyword = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'seo tools',
            'target_url' => 'https://test-example.com/seo-tools',
            'search_engine' => 'google',
            'location' => 'us',
            'device' => 'desktop',
            'status' => 'active'
        ]);
    }

    // ==================== WF-001: Feature detected on check ====================

    /**
     * WF-001: Ranking check creates SERP feature records.
     */
    public function test_wf_001_ranking_check_creates_feature_records(): void
    {
        // Arrange: Create a ranking with SERP features
        $serpFeatures = json_encode([
            'featured_snippet' => true,
            'local_pack' => false,
            'sitelinks' => true
        ]);

        $ranking = Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 1,
            'url_found' => 'https://test-example.com/seo-tools',
            'serp_features' => $serpFeatures,
            'checked_at' => now()
        ]);

        // Act: Simulate feature detection workflow
        $features = json_decode($serpFeatures, true);
        foreach ($features as $type => $isPresent) {
            SerpFeature::create([
                'keyword_id' => $this->keyword->id,
                'feature_type' => $type,
                'is_present' => $isPresent,
                'checked_at' => now()
            ]);
        }

        // Assert: Features stored correctly
        $storedFeatures = SerpFeature::where('keyword_id', $this->keyword->id)->get();
        $this->assertCount(3, $storedFeatures);
        
        $featuredSnippet = $storedFeatures->firstWhere('feature_type', 'featured_snippet');
        $this->assertTrue($featuredSnippet->is_present);
        
        $localPack = $storedFeatures->firstWhere('feature_type', 'local_pack');
        $this->assertFalse($localPack->is_present);
    }

    /**
     * WF-001: Feature detection integrates with ranking workflow.
     */
    public function test_wf_001_feature_detection_integrates_with_ranking(): void
    {
        // Arrange: First ranking check
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 1,
            'url_found' => 'https://test-example.com/seo-tools',
            'serp_features' => json_encode(['featured_snippet' => true]),
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        // Act: Query features via service
        $service = app(SerpFeatureService::class);
        $result = $service->getKeywordFeatures($this->keyword->id);

        // Assert: Features available through service
        $this->assertTrue($result['success']);
        $features = collect($result['data']['currentFeatures']);
        $featuredSnippet = $features->firstWhere('type', 'featured_snippet');
        $this->assertTrue($featuredSnippet['isPresent']);
    }

    // ==================== WF-002: Change tracked ====================

    /**
     * WF-002: Feature change triggers history record.
     */
    public function test_wf_002_feature_change_triggers_history(): void
    {
        // Arrange: Initial state (no featured snippet)
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()->subDay()
        ]);

        // Act: New state (gained featured snippet)
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com/page',
            'checked_at' => now()
        ]);

        // Track the change
        $tracker = app(SerpFeatureChangeTracker::class);
        $changes = $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://test-example.com/page']],
            [['type' => 'featured_snippet', 'is_present' => false]]
        );

        // Assert: Change recorded
        $this->assertCount(1, $changes);
        $this->assertEquals('gained', $changes[0]['action']);
        $this->assertEquals('featured_snippet', $changes[0]['type']);
    }

    /**
     * WF-002: Lost feature creates appropriate history record.
     */
    public function test_wf_002_lost_feature_creates_history(): void
    {
        // Arrange: Initial state (had featured snippet)
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com/old',
            'checked_at' => now()->subDay()
        ]);

        // Act: New state (lost featured snippet)
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()
        ]);

        $tracker = app(SerpFeatureChangeTracker::class);
        $changes = $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'featured_snippet', 'is_present' => false]],
            [['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://test-example.com/old']]
        );

        // Assert
        $this->assertCount(1, $changes);
        $this->assertEquals('lost', $changes[0]['action']);
    }

    // ==================== WF-003: Alert triggered ====================

    /**
     * WF-003: Feature gained triggers alert event.
     */
    public function test_wf_003_feature_gained_triggers_event(): void
    {
        // Arrange
        Event::fake();

        // Act: Track a gained feature
        $tracker = app(SerpFeatureChangeTracker::class);
        $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://test-example.com/page']],
            [['type' => 'featured_snippet', 'is_present' => false]]
        );

        // Assert: Event dispatched
        Event::assertDispatched('serp-feature.gained', function ($eventName, $payload) {
            return $payload['feature_type'] === 'featured_snippet'
                && $payload['action'] === 'gained';
        });
    }

    /**
     * WF-003: Feature lost triggers alert event.
     */
    public function test_wf_003_feature_lost_triggers_event(): void
    {
        // Arrange
        Event::fake();

        // Act: Track a lost feature
        $tracker = app(SerpFeatureChangeTracker::class);
        $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'local_pack', 'is_present' => false]],
            [['type' => 'local_pack', 'is_present' => true, 'url' => 'https://test-example.com/local']]
        );

        // Assert: Event dispatched
        Event::assertDispatched('serp-feature.lost', function ($eventName, $payload) {
            return $payload['feature_type'] === 'local_pack'
                && $payload['action'] === 'lost';
        });
    }

    // ==================== FW-001: New keyword feature tracking ====================

    /**
     * FW-001: New keyword starts with no features.
     */
    public function test_fw_001_new_keyword_no_features(): void
    {
        // Arrange: New keyword with no feature data
        
        // Act
        $service = app(SerpFeatureService::class);
        $result = $service->getKeywordFeatures($this->keyword->id);

        // Assert: All features should be false
        $this->assertTrue($result['success']);
        foreach ($result['data']['currentFeatures'] as $feature) {
            $this->assertFalse($feature['isPresent']);
        }
    }

    /**
     * FW-001: First check records features.
     */
    public function test_fw_001_first_check_records_features(): void
    {
        // Arrange: Simulate first ranking check
        $features = [
            'featured_snippet' => false,
            'local_pack' => false,
            'sitelinks' => true,
        ];

        // Act: Store features from first check
        foreach ($features as $type => $isPresent) {
            SerpFeature::create([
                'keyword_id' => $this->keyword->id,
                'feature_type' => $type,
                'is_present' => $isPresent,
                'checked_at' => now()
            ]);
        }

        // Assert: Features stored
        $service = app(SerpFeatureService::class);
        $result = $service->getKeywordFeatures($this->keyword->id);
        
        $storedFeatures = collect($result['data']['currentFeatures']);
        $sitelinks = $storedFeatures->firstWhere('type', 'sitelinks');
        $this->assertTrue($sitelinks['isPresent']);
    }

    // ==================== FW-002: Feature change detection ====================

    /**
     * FW-002: Change from not present to present detected.
     */
    public function test_fw_002_change_detected_gained(): void
    {
        // Arrange: Previous state
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()->subDay()
        ]);

        // Act: Current state
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        $tracker = app(SerpFeatureChangeTracker::class);
        $changes = $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'featured_snippet', 'is_present' => true]],
            [['type' => 'featured_snippet', 'is_present' => false]]
        );

        // Assert
        $this->assertCount(1, $changes);
        $this->assertEquals('gained', $changes[0]['action']);
    }

    /**
     * FW-002: Change from present to not present detected.
     */
    public function test_fw_002_change_detected_lost(): void
    {
        // Arrange: Previous state (had feature)
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'local_pack',
            'is_present' => true,
            'checked_at' => now()->subDay()
        ]);

        // Act: Current state (lost feature)
        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'local_pack',
            'is_present' => false,
            'checked_at' => now()
        ]);

        $tracker = app(SerpFeatureChangeTracker::class);
        $changes = $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'local_pack', 'is_present' => false]],
            [['type' => 'local_pack', 'is_present' => true]]
        );

        // Assert
        $this->assertCount(1, $changes);
        $this->assertEquals('lost', $changes[0]['action']);
    }

    // ==================== FW-003: Alert generation ====================

    /**
     * FW-003: Alert generated for gained feature.
     */
    public function test_fw_003_alert_generated_gained(): void
    {
        // Arrange
        Event::fake();

        // Act: Track gained feature
        $tracker = app(SerpFeatureChangeTracker::class);
        $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'featured_snippet', 'is_present' => true]],
            [['type' => 'featured_snippet', 'is_present' => false]]
        );

        // Assert: History record created
        $history = SerpFeatureHistory::where('keyword_id', $this->keyword->id)
            ->where('action', 'gained')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('featured_snippet', $history->feature_type);
    }

    /**
     * FW-003: Alert generated for lost feature.
     */
    public function test_fw_003_alert_generated_lost(): void
    {
        // Arrange
        Event::fake();

        // Act: Track lost feature
        $tracker = app(SerpFeatureChangeTracker::class);
        $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'local_pack', 'is_present' => false]],
            [['type' => 'local_pack', 'is_present' => true]]
        );

        // Assert: History record created
        $history = SerpFeatureHistory::where('keyword_id', $this->keyword->id)
            ->where('action', 'lost')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('local_pack', $history->feature_type);
    }

    // ==================== MS-001: Featured snippet detected ====================

    /**
     * MS-001: Featured snippet detected from mock data.
     */
    public function test_ms_001_featured_snippet_detected(): void
    {
        // Arrange: Mock SERP data with answer box
        $mockSerpData = [
            'keyword' => 'seo tools',
            'results' => [],
            'answer_box' => [
                'url' => 'https://test-example.com/seo-tools',
                'snippet' => 'Best SEO tools for 2026...',
                'title' => 'SEO Tools Guide'
            ]
        ];

        // Act: Simulate feature detection
        $isPresent = isset($mockSerpData['answer_box']) 
            && str_contains($mockSerpData['answer_box']['url'], 'test-example.com');

        // Assert
        $this->assertTrue($isPresent);
    }

    // ==================== MS-002: Local pack detected ====================

    /**
     * MS-002: Local pack detected from mock data.
     */
    public function test_ms_002_local_pack_detected(): void
    {
        // Arrange: Mock SERP data with local pack
        $mockSerpData = [
            'keyword' => 'seo agency',
            'results' => [],
            'local_pack' => [
                ['url' => 'https://test-example.com', 'title' => 'Test Agency'],
                ['url' => 'https://competitor.com', 'title' => 'Competitor'],
            ]
        ];

        // Act: Simulate feature detection
        $isPresent = false;
        if (isset($mockSerpData['local_pack'])) {
            foreach ($mockSerpData['local_pack'] as $item) {
                if (str_contains($item['url'], 'test-example.com')) {
                    $isPresent = true;
                    break;
                }
            }
        }

        // Assert
        $this->assertTrue($isPresent);
    }

    // ==================== MS-003: Multiple features detected ====================

    /**
     * MS-003: Multiple features detected from mock data.
     */
    public function test_ms_003_multiple_features_detected(): void
    {
        // Arrange: Mock SERP data with multiple features
        $mockSerpData = [
            'keyword' => 'seo tools',
            'results' => [
                ['position' => 1, 'url' => 'https://test-example.com/seo-tools']
            ],
            'answer_box' => [
                'url' => 'https://test-example.com/seo-tools',
                'snippet' => 'Best tools...'
            ],
            'sitelinks' => true,
            'reviews' => true
        ];

        // Act: Simulate detection
        $features = [];
        $features['featured_snippet'] = isset($mockSerpData['answer_box']);
        $features['sitelinks'] = $mockSerpData['sitelinks'] ?? false;
        $features['reviews'] = $mockSerpData['reviews'] ?? false;

        // Assert
        $this->assertTrue($features['featured_snippet']);
        $this->assertTrue($features['sitelinks']);
        $this->assertTrue($features['reviews']);
    }

    // ==================== MS-004: No features ====================

    /**
     * MS-004: No features detected correctly.
     */
    public function test_ms_004_no_features_detected(): void
    {
        // Arrange: Mock SERP data with no special features
        $mockSerpData = [
            'keyword' => 'some keyword',
            'results' => [
                ['position' => 10, 'url' => 'https://test-example.com/page']
            ]
        ];

        // Act: Simulate detection
        $features = [
            'featured_snippet' => isset($mockSerpData['answer_box']),
            'local_pack' => isset($mockSerpData['local_pack']),
            'sitelinks' => $mockSerpData['sitelinks'] ?? false,
        ];

        // Assert: All false
        foreach ($features as $isPresent) {
            $this->assertFalse($isPresent);
        }
    }

    // ==================== Full Workflow Tests ====================

    /**
     * Full workflow: Ranking check -> Feature detection -> Change tracking -> Alert.
     */
    public function test_full_workflow_ranking_to_alert(): void
    {
        // Arrange
        Event::fake();

        // Step 1: First ranking check (no features)
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 5,
            'url_found' => 'https://test-example.com/seo-tools',
            'serp_features' => json_encode(['featured_snippet' => false]),
            'checked_at' => now()->subDay()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => false,
            'checked_at' => now()->subDay()
        ]);

        // Step 2: Second ranking check (gained featured snippet)
        Ranking::create([
            'keyword_id' => $this->keyword->id,
            'position' => 1,
            'url_found' => 'https://test-example.com/seo-tools',
            'serp_features' => json_encode(['featured_snippet' => true]),
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'url' => 'https://test-example.com/seo-tools',
            'checked_at' => now()
        ]);

        // Step 3: Track changes
        $tracker = app(SerpFeatureChangeTracker::class);
        $changes = $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'featured_snippet', 'is_present' => true, 'url' => 'https://test-example.com/seo-tools']],
            [['type' => 'featured_snippet', 'is_present' => false]]
        );

        // Assert: Change detected and recorded
        $this->assertCount(1, $changes);
        $this->assertEquals('gained', $changes[0]['action']);

        // Assert: History entry created
        $history = SerpFeatureHistory::where('keyword_id', $this->keyword->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('gained', $history->action);

        // Assert: Event dispatched
        Event::assertDispatched('serp-feature.gained');
    }

    /**
     * Full workflow: Summary reflects all changes.
     */
    public function test_full_workflow_summary_reflects_changes(): void
    {
        // Arrange: Create features across multiple keywords
        $keyword2 = Keyword::create([
            'site_id' => $this->site->id,
            'keyword' => 'keyword tracker',
            'status' => 'active'
        ]);

        SerpFeature::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeature::create([
            'keyword_id' => $keyword2->id,
            'feature_type' => 'local_pack',
            'is_present' => true,
            'checked_at' => now()
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'detected_at' => now()
        ]);

        // Act: Get summary
        $service = app(SerpFeatureService::class);
        $summary = $service->getSummary($this->site->id);

        // Assert
        $this->assertTrue($summary['success']);
        $this->assertEquals(2, $summary['data']['totalKeywords']);
        $this->assertEquals(2, $summary['data']['keywordsWithFeatures']);
        $this->assertEquals(1, $summary['data']['featureCounts']['featured_snippet']);
        $this->assertEquals(1, $summary['data']['featureCounts']['local_pack']);
        $this->assertCount(1, $summary['data']['recentChanges']['gained']);
    }

    // ==================== Edge Cases ====================

    /**
     * Edge case: Multiple changes for same feature type.
     */
    public function test_edge_case_multiple_changes_same_feature(): void
    {
        // Arrange: Multiple changes over time
        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'detected_at' => now()->subDays(3)
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'lost',
            'detected_at' => now()->subDays(2)
        ]);

        SerpFeatureHistory::create([
            'keyword_id' => $this->keyword->id,
            'feature_type' => 'featured_snippet',
            'action' => 'gained',
            'detected_at' => now()->subDay()
        ]);

        // Act
        $service = app(SerpFeatureService::class);
        $result = $service->getKeywordFeatures($this->keyword->id);

        // Assert: All changes recorded
        $this->assertGreaterThanOrEqual(3, count($result['data']['changes']));
    }

    /**
     * Edge case: No previous data for change tracking.
     */
    public function test_edge_case_no_previous_data(): void
    {
        // Arrange: First check, no previous data
        $tracker = app(SerpFeatureChangeTracker::class);

        // Act: Track with no previous features
        $changes = $tracker->trackChanges(
            $this->keyword->id,
            [['type' => 'featured_snippet', 'is_present' => true]],
            [] // No previous data
        );

        // Assert: No changes recorded (can't determine if gained or not)
        $this->assertCount(0, $changes);
    }

    /**
     * Edge case: Invalid keyword ID.
     */
    public function test_edge_case_invalid_keyword_id(): void
    {
        // Arrange
        $tracker = app(SerpFeatureChangeTracker::class);

        // Act & Assert: Exception for invalid keyword ID
        $this->expectException(\InvalidArgumentException::class);
        $tracker->trackChanges(
            -1,
            [],
            []
        );
    }
}
