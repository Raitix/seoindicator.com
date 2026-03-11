<?php

namespace Tests\Unit\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\ContentDecaySignal;
use App\Models\Site;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayScoringServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use App\Services\ContentDecay\Contracts\ContentRefreshPlannerServiceInterface;
use App\Services\ContentDecay\ContentDecayDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unit tests for ContentDecayDetectionService.
 *
 * This service is the main orchestration layer that:
 * - Gathers signals from multiple sources (GSC, rankings, audit, annotations, AI visibility)
 * - Compares windows (current vs previous)
 * - Creates/updates decay candidates
 *
 * @see TEST-36-6.md for test documentation
 */
class ContentDecayDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // SI: Service Instantiation Tests
    // =========================================================================

    /** @test */
    public function si_001_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(ContentDecayDetectionService::class));
    }

    /** @test */
    public function si_002_class_can_be_instantiated(): void
    {
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $service = new ContentDecayDetectionService($scoringService, $reasonService, $plannerService);

        $this->assertInstanceOf(ContentDecayDetectionService::class, $service);
    }

    /** @test */
    public function si_003_container_binding_works(): void
    {
        $this->app->bind(ContentDecayScoringServiceInterface::class, fn() => Mockery::mock(ContentDecayScoringServiceInterface::class));
        $this->app->bind(ContentDecayReasonServiceInterface::class, fn() => Mockery::mock(ContentDecayReasonServiceInterface::class));
        $this->app->bind(ContentRefreshPlannerServiceInterface::class, fn() => Mockery::mock(ContentRefreshPlannerServiceInterface::class));

        $service = app(ContentDecayDetectionService::class);

        $this->assertInstanceOf(ContentDecayDetectionService::class, $service);
    }

    /** @test */
    public function si_004_required_dependencies_injected(): void
    {
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $service = new ContentDecayDetectionService($scoringService, $reasonService, $plannerService);

        $this->assertInstanceOf(ContentDecayScoringServiceInterface::class, $service->getScoringService());
        $this->assertInstanceOf(ContentDecayReasonServiceInterface::class, $service->getReasonService());
        $this->assertInstanceOf(ContentRefreshPlannerServiceInterface::class, $service->getPlannerService());
    }

    /** @test */
    public function si_005_detect_for_site_method_exists(): void
    {
        $service = $this->createMockService();

        $this->assertTrue(method_exists($service, 'detectForSite'));
    }

    /** @test */
    public function si_006_detect_for_project_method_exists(): void
    {
        $service = $this->createMockService();

        $this->assertTrue(method_exists($service, 'detectForProject'));
    }

    /** @test */
    public function si_007_gather_signals_method_exists(): void
    {
        $service = $this->createMockService();

        $this->assertTrue(method_exists($service, 'gatherSignals'));
    }

    /** @test */
    public function si_008_compare_windows_method_exists(): void
    {
        $service = $this->createMockService();

        $this->assertTrue(method_exists($service, 'compareWindows'));
    }

    /** @test */
    public function si_009_upsert_candidate_method_exists(): void
    {
        $service = $this->createMockService();

        $this->assertTrue(method_exists($service, 'upsertCandidate'));
    }

    /** @test */
    public function si_010_default_configuration_loaded(): void
    {
        $service = $this->createMockService();

        $config = $service->getConfiguration();

        $this->assertEquals(28, $config['window_days']);
        $this->assertEquals(100, $config['min_impressions_threshold']);
        $this->assertEquals(10, $config['min_clicks_threshold']);
        $this->assertEquals(14, $config['refresh_cooldown_days']);
    }

    // =========================================================================
    // GS: gatherSignals() Tests
    // =========================================================================

    /** @test */
    public function gs_001_returns_signals_array_structure(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockService();

        $signals = $service->gatherSignals('https://example.com/page1', $project);

        $this->assertIsArray($signals);
        $this->assertArrayHasKey('gsc', $signals);
        $this->assertArrayHasKey('rankings', $signals);
        $this->assertArrayHasKey('audit', $signals);
        $this->assertArrayHasKey('annotations', $signals);
        $this->assertArrayHasKey('ai_visibility', $signals);
    }

    /** @test */
    public function gs_007_rankings_optional_graceful_skip(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockService();

        $signals = $service->gatherSignals('https://example.com/page1', $project);

        $this->assertNull($signals['rankings']);
    }

    /** @test */
    public function gs_019_signals_timestamp_included(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockService();

        $signals = $service->gatherSignals('https://example.com/page1', $project);

        $this->assertArrayHasKey('gathered_at', $signals);
    }

    /** @test */
    public function gs_020_page_url_included_in_signals(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockService();

        $signals = $service->gatherSignals('https://example.com/page1', $project);

        $this->assertArrayHasKey('page_url', $signals);
        $this->assertEquals('https://example.com/page1', $signals['page_url']);
    }

    /** @test */
    public function gs_040_signals_serializable(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockService();

        $signals = $service->gatherSignals('https://example.com/page1', $project);

        $json = json_encode($signals);
        $this->assertIsString($json);
        $this->assertNotFalse($json);
    }

    // =========================================================================
    // CW: compareWindows() Tests
    // =========================================================================

    /** @test */
    public function cw_001_returns_comparison_array(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 800, 'impressions' => 18000, 'ctr' => 0.044, 'position' => 6.5],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('deltas', $comparison);
        $this->assertArrayHasKey('percentages', $comparison);
        $this->assertArrayHasKey('direction', $comparison);
    }

    /** @test */
    public function cw_002_click_delta_calculated(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 800, 'impressions' => 18000, 'ctr' => 0.044, 'position' => 6.5],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertEquals(-200, $comparison['deltas']['clicks']);
    }

    /** @test */
    public function cw_003_impression_delta_calculated(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 800, 'impressions' => 18000, 'ctr' => 0.044, 'position' => 6.5],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertEquals(-2000, $comparison['deltas']['impressions']);
    }

    /** @test */
    public function cw_004_ctr_delta_calculated(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 800, 'impressions' => 18000, 'ctr' => 0.044, 'position' => 6.5],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertEquals(-0.006, round($comparison['deltas']['ctr'], 6));
    }

    /** @test */
    public function cw_005_position_delta_calculated(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 800, 'impressions' => 18000, 'ctr' => 0.044, 'position' => 6.5],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertEquals(1.5, $comparison['deltas']['position']);
    }

    /** @test */
    public function cw_006_click_percentage_calculated(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 800, 'impressions' => 18000, 'ctr' => 0.044, 'position' => 6.5],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertEquals(-20.0, $comparison['percentages']['clicks']);
    }

    /** @test */
    public function cw_010_direction_determined_decline(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 800, 'impressions' => 18000, 'ctr' => 0.044, 'position' => 6.5],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertEquals('down', $comparison['direction']['clicks']);
    }

    /** @test */
    public function cw_011_direction_determined_increase(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 800, 'impressions' => 18000, 'ctr' => 0.044, 'position' => 6.5],
                'current' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertEquals('up', $comparison['direction']['clicks']);
    }

    /** @test */
    public function cw_012_direction_determined_stable(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertEquals('stable', $comparison['direction']['clicks']);
    }

    /** @test */
    public function cw_016_division_by_zero_handled(): void
    {
        $service = $this->createMockService();

        $signals = [
            'gsc' => [
                'previous' => ['clicks' => 0, 'impressions' => 20000, 'ctr' => 0.0, 'position' => 5.0],
                'current' => ['clicks' => 100, 'impressions' => 18000, 'ctr' => 0.005, 'position' => 6.5],
            ],
        ];

        $comparison = $service->compareWindows($signals);

        $this->assertIsNumeric($comparison['percentages']['clicks']);
    }

    // =========================================================================
    // ELIG: Eligibility Filtering Tests (no DB needed)
    // =========================================================================

    /** @test */
    public function elig_001_gsc_data_required_both_windows(): void
    {
        $service = $this->createMockService();

        $gscData = [
            'current' => ['clicks' => 100, 'impressions' => 1000],
        ];

        $eligible = $service->isPageEligible($gscData);

        $this->assertFalse($eligible);
    }

    /** @test */
    public function elig_003_previous_impressions_threshold(): void
    {
        $service = $this->createMockService();

        $gscData = [
            'previous' => ['clicks' => 5, 'impressions' => 150],
            'current' => ['clicks' => 4, 'impressions' => 140],
        ];

        $eligible = $service->isPageEligible($gscData);

        $this->assertTrue($eligible);
    }

    /** @test */
    public function elig_004_previous_impressions_below_threshold(): void
    {
        $service = $this->createMockService();

        $gscData = [
            'previous' => ['clicks' => 5, 'impressions' => 50],
            'current' => ['clicks' => 4, 'impressions' => 45],
        ];

        $eligible = $service->isPageEligible($gscData);

        $this->assertFalse($eligible);
    }

    /** @test */
    public function elig_005_previous_clicks_threshold_alternative(): void
    {
        $service = $this->createMockService();

        $gscData = [
            'previous' => ['clicks' => 15, 'impressions' => 50],
            'current' => ['clicks' => 12, 'impressions' => 45],
        ];

        $eligible = $service->isPageEligible($gscData);

        $this->assertTrue($eligible);
    }

    /** @test */
    public function elig_006_either_threshold_passes(): void
    {
        $service = $this->createMockService();

        $gscData = [
            'previous' => ['clicks' => 15, 'impressions' => 50],
            'current' => ['clicks' => 12, 'impressions' => 45],
        ];

        $eligible = $service->isPageEligible($gscData);

        $this->assertTrue($eligible);
    }

    /** @test */
    public function elig_007_neither_threshold_passes(): void
    {
        $service = $this->createMockService();

        $gscData = [
            'previous' => ['clicks' => 5, 'impressions' => 50],
            'current' => ['clicks' => 4, 'impressions' => 45],
        ];

        $eligible = $service->isPageEligible($gscData);

        $this->assertFalse($eligible);
    }

    /** @test */
    public function elig_019_edge_case_exact_threshold(): void
    {
        $service = $this->createMockService();

        $gscData = [
            'previous' => ['clicks' => 5, 'impressions' => 100],
            'current' => ['clicks' => 4, 'impressions' => 95],
        ];

        $eligible = $service->isPageEligible($gscData);

        $this->assertTrue($eligible);
    }

    /** @test */
    public function elig_020_edge_case_exact_clicks(): void
    {
        $service = $this->createMockService();

        $gscData = [
            'previous' => ['clicks' => 10, 'impressions' => 50],
            'current' => ['clicks' => 8, 'impressions' => 45],
        ];

        $eligible = $service->isPageEligible($gscData);

        $this->assertTrue($eligible);
    }

    // =========================================================================
    // COOL: Cooldown/Suppression Tests (no DB writes)
    // =========================================================================

    /** @test */
    public function cool_008_low_signal_threshold(): void
    {
        $service = $this->createMockService();

        $comparison = [
            'deltas' => ['clicks' => -2, 'impressions' => -50],
            'percentages' => ['clicks' => -1, 'impressions' => -2],
        ];

        $belowThreshold = $service->isBelowLowSignalThreshold($comparison);

        $this->assertTrue($belowThreshold);
    }

    /** @test */
    public function cool_009_low_signal_threshold_passed(): void
    {
        $service = $this->createMockService();

        $comparison = [
            'deltas' => ['clicks' => -200, 'impressions' => -5000],
            'percentages' => ['clicks' => -20, 'impressions' => -25],
        ];

        $belowThreshold = $service->isBelowLowSignalThreshold($comparison);

        $this->assertFalse($belowThreshold);
    }

    // =========================================================================
    // Database Tests - Using proper transaction handling
    // =========================================================================

    /** @test */
    public function uc_001_creates_new_candidate(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $service = $this->createMockService();

        $detectionData = [
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'decay_score' => 45.5,
            'severity' => 'high',
            'detection_confidence' => 0.93,
            'reason_codes' => ['clicks_down', 'position_down'],
            'signal_sources' => ['gsc', 'rankings'],
            'evidence_json' => ['clicks' => ['previous' => 1000, 'current' => 700]],
        ];

        $candidate = $service->upsertCandidate($detectionData);

        $this->assertInstanceOf(ContentDecayCandidate::class, $candidate);
        $this->assertDatabaseHas('content_decay_candidates', [
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
        ]);
    }

    /** @test */
    public function uc_002_updates_existing_candidate(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $existing = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'decay_score' => 30,
            'status' => 'backlog',
        ]);

        $service = $this->createMockService();

        $detectionData = [
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'decay_score' => 55.0,
            'severity' => 'high',
            'detection_confidence' => 0.95,
            'reason_codes' => ['clicks_down', 'position_down', 'ctr_down'],
            'signal_sources' => ['gsc', 'rankings'],
            'evidence_json' => ['clicks' => ['previous' => 1000, 'current' => 500]],
        ];

        $candidate = $service->upsertCandidate($detectionData);

        $this->assertEquals($existing->id, $candidate->id);
        $this->assertEquals(55.0, $candidate->decay_score);
    }

    /** @test */
    public function uc_004_sets_decay_score(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $service = $this->createMockService();

        $detectionData = [
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'decay_score' => 67.8,
            'severity' => 'high',
        ];

        $candidate = $service->upsertCandidate($detectionData);

        $this->assertEquals(67.8, $candidate->decay_score);
    }

    /** @test */
    public function uc_009_sets_status_to_backlog(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $service = $this->createMockService();

        $detectionData = [
            'project_id' => $project->id,
            'page_url' => 'https://example.com/new-page',
            'decay_score' => 45,
            'severity' => 'high',
        ];

        $candidate = $service->upsertCandidate($detectionData);

        $this->assertEquals('backlog', $candidate->status);
    }

    /** @test */
    public function uc_010_preserves_status_if_exists(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $existing = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'status' => 'planned',
        ]);

        $service = $this->createMockService();

        $detectionData = [
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'decay_score' => 55,
            'severity' => 'high',
        ];

        $candidate = $service->upsertCandidate($detectionData);

        $this->assertEquals('planned', $candidate->status);
    }

    /** @test */
    public function cool_001_fourteen_day_refresh_cooldown(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'status' => 'refreshed',
            'refreshed_at' => now()->subDays(7),
        ]);

        $service = $this->createMockService();

        $suppressed = $service->isCooldownActive($candidate);

        $this->assertTrue($suppressed);
    }

    /** @test */
    public function cool_002_fourteen_day_refresh_cooldown_passed(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $candidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'status' => 'refreshed',
            'refreshed_at' => now()->subDays(20),
        ]);

        $service = $this->createMockService();

        $suppressed = $service->isCooldownActive($candidate);

        $this->assertFalse($suppressed);
    }

    /** @test */
    public function sec_001_sql_injection_in_page_url(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $service = $this->createMockService();

        $detectionData = [
            'project_id' => $project->id,
            'page_url' => "https://example.com/page'; DROP TABLE candidates; --",
            'decay_score' => 45,
            'severity' => 'high',
        ];

        $candidate = $service->upsertCandidate($detectionData);

        $this->assertInstanceOf(ContentDecayCandidate::class, $candidate);
    }

    /** @test */
    public function sec_002_xss_in_evidence(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $service = $this->createMockService();

        $detectionData = [
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'decay_score' => 45,
            'severity' => 'high',
            'evidence_json' => [
                'clicks' => ['previous' => 1000, 'current' => 700],
                'malicious' => '<script>alert("xss")</script>',
            ],
        ];

        $candidate = $service->upsertCandidate($detectionData);

        $evidence = $candidate->evidence_json;
        $this->assertStringNotContainsString('<script>', $evidence['malicious'] ?? '');
    }

    /** @test */
    public function edge_006_unicode_in_page_urls(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $service = $this->createMockService();

        $detectionData = [
            'project_id' => $project->id,
            'page_url' => 'https://example.com/文章/标题',
            'decay_score' => 45,
            'severity' => 'high',
        ];

        $candidate = $service->upsertCandidate($detectionData);

        $this->assertEquals('https://example.com/文章/标题', $candidate->page_url);
    }

    // =========================================================================
    // Integration-style Tests (full flow)
    // =========================================================================

    /** @test */
    public function dfs_010_handles_empty_gsc_data(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithEmptyGsc();

        $result = $service->detectForSite($site);

        $this->assertEquals(0, $result['processed_pages']);
        $this->assertEquals(0, $result['candidates_created']);
    }

    /** @test */
    public function dfs_009_respects_project_detection_enabled(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => false,
        ]);

        $service = $this->createMockService();

        $result = $service->detectForSite($site);

        $this->assertEquals(0, $result['processed_pages']);
    }

    /** @test */
    public function dfs_019_returns_timing_metrics(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithEmptyGsc();

        $result = $service->detectForSite($site);

        $this->assertArrayHasKey('duration_ms', $result);
        $this->assertIsInt($result['duration_ms']);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createMockService(): ContentDecayDetectionService
    {
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        return new ContentDecayDetectionService($scoringService, $reasonService, $plannerService);
    }

    private function createMockServiceWithEmptyGsc(): ContentDecayDetectionService
    {
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $service = new class($scoringService, $reasonService, $plannerService) extends ContentDecayDetectionService {
            protected function fetchGscData($site): array
            {
                return [];
            }
        };

        return $service;
    }

    private function createEligibleGscData(): array
    {
        return [
            'previous' => [
                'clicks' => 1000,
                'impressions' => 20000,
                'ctr' => 0.05,
                'position' => 5.0,
            ],
            'current' => [
                'clicks' => 800,
                'impressions' => 18000,
                'ctr' => 0.044,
                'position' => 6.5,
            ],
        ];
    }

    private function createIneligibleGscData(): array
    {
        return [
            'previous' => [
                'clicks' => 5,
                'impressions' => 50,
                'ctr' => 0.10,
                'position' => 10.0,
            ],
            'current' => [
                'clicks' => 4,
                'impressions' => 45,
                'ctr' => 0.089,
                'position' => 12.0,
            ],
        ];
    }
}
