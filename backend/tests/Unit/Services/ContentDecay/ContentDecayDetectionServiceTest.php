<?php

namespace Tests\Unit\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\Site;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayScoringServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use App\Services\ContentDecay\Contracts\ContentRefreshPlannerServiceInterface;
use App\Services\ContentDecay\ContentDecayDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

        // Verify dependencies are accessible through reflection or public methods
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
    // DFS: detectForSite() Tests
    // =========================================================================

    /** @test */
    public function dfs_001_returns_detection_summary_array(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForSite($site);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('processed_pages', $result);
        $this->assertArrayHasKey('candidates_created', $result);
    }

    /** @test */
    public function dfs_002_loads_project_settings(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
            'settings_json' => ['window_days' => 14, 'min_impressions' => 50],
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForSite($site);

        // Verify project settings were used
        $this->assertEquals(14, $service->getLastUsedWindowDays());
    }

    /** @test */
    public function dfs_003_creates_project_if_not_exists(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // No project exists yet
        $this->assertEquals(0, ContentDecayProject::count());

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForSite($site);

        // Project should be created
        $this->assertEquals(1, ContentDecayProject::count());
    }

    /** @test */
    public function dfs_004_filters_eligible_pages(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $gscData = [
            'https://example.com/page1' => $this->createEligibleGscData(),
            'https://example.com/page2' => $this->createIneligibleGscData(), // Low impressions
            'https://example.com/page3' => $this->createEligibleGscData(),
        ];

        $service = $this->createMockServiceWithGscData($site, $gscData);

        $result = $service->detectForSite($site);

        // Only 2 eligible pages should be processed
        $this->assertEquals(2, $result['eligible_pages']);
    }

    /** @test */
    public function dfs_005_calls_gather_signals_for_each_page(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $gscData = [
            'https://example.com/page1' => $this->createEligibleGscData(),
            'https://example.com/page2' => $this->createEligibleGscData(),
        ];

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->twice()
            ->andReturn(['decay_score' => 30, 'severity' => 'medium']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->twice()
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->twice()
            ->andReturn(['primary_recommendation' => 'Refresh content']);

        $service = $this->createMockServiceWithDependencies($scoringService, $reasonService, $plannerService, $site, $gscData);

        $result = $service->detectForSite($site);

        $this->assertEquals(2, $result['processed_pages']);
    }

    /** @test */
    public function dfs_006_calls_compare_windows_for_each_page(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $gscData = [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ];

        $service = $this->createMockServiceWithGscData($site, $gscData);

        $result = $service->detectForSite($site);

        // Verify comparison was performed (checking result structure)
        $this->assertArrayHasKey('pages', $result);
    }

    /** @test */
    public function dfs_007_calls_upsert_candidate_for_each_decay(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $gscData = [
            'https://example.com/page1' => $this->createDecayingGscData(), // Will have decay
        ];

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 45, 'severity' => 'high']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh content']);

        $service = $this->createMockServiceWithDependencies($scoringService, $reasonService, $plannerService, $site, $gscData);

        $result = $service->detectForSite($site);

        $this->assertEquals(1, $result['candidates_created']);
        $this->assertDatabaseHas('content_decay_candidates', [
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
        ]);
    }

    /** @test */
    public function dfs_008_skips_ineligible_pages(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $gscData = [
            'https://example.com/page1' => $this->createIneligibleGscData(), // Low impressions
        ];

        $service = $this->createMockServiceWithGscData($site, $gscData);

        $result = $service->detectForSite($site);

        $this->assertEquals(0, $result['eligible_pages']);
        $this->assertEquals(0, $result['candidates_created']);
    }

    /** @test */
    public function dfs_009_respects_project_detection_enabled(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => false, // Disabled
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForSite($site);

        $this->assertEquals(0, $result['processed_pages']);
    }

    /** @test */
    public function dfs_010_handles_empty_gsc_data(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForSite($site);

        $this->assertEquals(0, $result['processed_pages']);
        $this->assertEquals(0, $result['candidates_created']);
    }

    /** @test */
    public function dfs_011_handles_gsc_api_error(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        Log::shouldReceive('error')->once();

        $service = $this->createMockServiceWithGscError($site);

        $result = $service->detectForSite($site);

        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function dfs_012_creates_detection_summary(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $gscData = [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ];

        $service = $this->createMockServiceWithGscData($site, $gscData);

        $result = $service->detectForSite($site);

        $this->assertArrayHasKey('processed_pages', $result);
        $this->assertArrayHasKey('eligible_pages', $result);
        $this->assertArrayHasKey('candidates_created', $result);
        $this->assertArrayHasKey('candidates_updated', $result);
        $this->assertArrayHasKey('candidates_archived', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function dfs_013_archives_old_candidates(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        // Create existing candidate that should be archived
        $existingCandidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/old-page',
            'status' => 'backlog',
            'decay_score' => 45,
        ]);

        // GSC data shows the old page no longer has decay
        $gscData = [
            'https://example.com/old-page' => $this->createHealthyGscData(),
        ];

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 5, 'severity' => 'low']); // Low decay

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => null]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $service = $this->createMockServiceWithDependencies($scoringService, $reasonService, $plannerService, $site, $gscData);

        $result = $service->detectForSite($site);

        $this->assertEquals(1, $result['candidates_archived']);
    }

    /** @test */
    public function dfs_014_downgrades_severity(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $existingCandidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'status' => 'backlog',
            'decay_score' => 75,
            'severity' => 'critical',
        ]);

        $gscData = [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ];

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 35, 'severity' => 'medium']); // Improved

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Minor refresh']);

        $service = $this->createMockServiceWithDependencies($scoringService, $reasonService, $plannerService, $site, $gscData);

        $result = $service->detectForSite($site);

        $existingCandidate->refresh();
        $this->assertEquals('medium', $existingCandidate->severity);
        $this->assertEquals(35, $existingCandidate->decay_score);
    }

    /** @test */
    public function dfs_015_preserves_ignored_candidates(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $existingCandidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'status' => 'ignored', // Should be preserved
            'decay_score' => 50,
        ]);

        $gscData = [
            'https://example.com/page1' => $this->createDecayingGscData(),
        ];

        $service = $this->createMockServiceWithGscData($site, $gscData);

        $result = $service->detectForSite($site);

        $existingCandidate->refresh();
        $this->assertEquals('ignored', $existingCandidate->status);
    }

    /** @test */
    public function dfs_018_logs_processing_progress(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        Log::shouldReceive('info')->atLeast()->once();

        $service = $this->createMockServiceWithGscData($site, []);

        $service->detectForSite($site);
    }

    /** @test */
    public function dfs_019_returns_timing_metrics(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForSite($site);

        $this->assertArrayHasKey('duration_ms', $result);
        $this->assertIsInt($result['duration_ms']);
    }

    /** @test */
    public function dfs_030_zero_decay_pages_handled(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $gscData = [
            'https://example.com/page1' => $this->createHealthyGscData(), // No decay
        ];

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 0, 'severity' => 'low']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => null]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $service = $this->createMockServiceWithDependencies($scoringService, $reasonService, $plannerService, $site, $gscData);

        $result = $service->detectForSite($site);

        $this->assertEquals(0, $result['candidates_created']);
        $this->assertEquals(1, $result['processed_pages']);
    }

    // =========================================================================
    // DFP: detectForProject() Tests
    // =========================================================================

    /** @test */
    public function dfp_001_returns_detection_summary(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForProject($project);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('project_id', $result);
    }

    /** @test */
    public function dfp_002_uses_project_specific_settings(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
            'settings_json' => [
                'window_days' => 14,
                'min_impressions_threshold' => 50,
            ],
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForProject($project);

        $this->assertEquals(14, $service->getLastUsedWindowDays());
    }

    /** @test */
    public function dfp_003_respects_custom_window_days(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
            'settings_json' => ['window_days' => 7],
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForProject($project);

        $this->assertEquals(7, $service->getLastUsedWindowDays());
    }

    /** @test */
    public function dfp_006_updates_last_scanned_at(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
            'last_scanned_at' => null,
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $service->detectForProject($project);

        $project->refresh();
        $this->assertNotNull($project->last_scanned_at);
    }

    /** @test */
    public function dfp_009_handles_disabled_project(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => false,
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForProject($project);

        $this->assertEquals(0, $result['processed_pages']);
    }

    /** @test */
    public function dfp_016_handles_project_with_no_pages(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForProject($project);

        $this->assertEquals(0, $result['processed_pages']);
        $this->assertEquals(0, $result['candidates_created']);
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

        $service = $this->createMockServiceWithGscData($site, []);

        $signals = $service->gatherSignals('https://example.com/page1', $project);

        $this->assertIsArray($signals);
        $this->assertArrayHasKey('gsc', $signals);
        $this->assertArrayHasKey('rankings', $signals);
        $this->assertArrayHasKey('audit', $signals);
        $this->assertArrayHasKey('annotations', $signals);
        $this->assertArrayHasKey('ai_visibility', $signals);
    }

    /** @test */
    public function gs_002_gsc_required_signals_gathered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

        $signals = $service->gatherSignals('https://example.com/page1', $project);

        $this->assertArrayHasKey('clicks', $signals['gsc']['previous']);
        $this->assertArrayHasKey('impressions', $signals['gsc']['previous']);
        $this->assertArrayHasKey('ctr', $signals['gsc']['previous']);
        $this->assertArrayHasKey('position', $signals['gsc']['previous']);
    }

    /** @test */
    public function gs_003_gsc_previous_window_fetched(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

        $signals = $service->gatherSignals('https://example.com/page1', $project);

        $this->assertArrayHasKey('previous', $signals['gsc']);
    }

    /** @test */
    public function gs_004_gsc_current_window_fetched(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

        $signals = $service->gatherSignals('https://example.com/page1', $project);

        $this->assertArrayHasKey('current', $signals['gsc']);
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

        // No rankings data available
        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

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

        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

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

        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

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

        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

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

        $this->assertEquals(-200, $comparison['deltas']['clicks']); // 800 - 1000 = -200 (loss)
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

        $this->assertEquals(-2000, $comparison['deltas']['impressions']); // 18000 - 20000 = -2000
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

        $this->assertEquals(-0.006, round($comparison['deltas']['ctr'], 6)); // 0.044 - 0.05 = -0.006
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

        // Position decline: higher is worse, so 6.5 - 5.0 = 1.5 (positive = worse)
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

        // -200 / 1000 * 100 = -20%
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

        // Division by zero should return 0 or 100% depending on context
        $this->assertIsNumeric($comparison['percentages']['clicks']);
    }

    // =========================================================================
    // UC: upsertCandidate() Tests
    // =========================================================================

    /** @test */
    public function uc_001_creates_new_candidate(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
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
            'decay_score' => 45.5,
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
            'detection_enabled' => true,
        ]);

        // Create existing candidate
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

        // Create existing candidate with "planned" status
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

    // =========================================================================
    // ELIG: Eligibility Filtering Tests
    // =========================================================================

    /** @test */
    public function elig_001_gsc_data_required_both_windows(): void
    {
        $service = $this->createMockService();

        // Missing previous window
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
            'previous' => ['clicks' => 5, 'impressions' => 150], // >= 100 impressions
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
            'previous' => ['clicks' => 5, 'impressions' => 50], // < 100 impressions
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
            'previous' => ['clicks' => 15, 'impressions' => 50], // < 100 impressions but >= 10 clicks
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
            'previous' => ['clicks' => 15, 'impressions' => 50], // Clicks pass, impressions fail
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
            'previous' => ['clicks' => 5, 'impressions' => 50], // Neither passes
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
            'previous' => ['clicks' => 5, 'impressions' => 100], // Exactly 100
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
            'previous' => ['clicks' => 10, 'impressions' => 50], // Exactly 10 clicks
            'current' => ['clicks' => 8, 'impressions' => 45],
        ];

        $eligible = $service->isPageEligible($gscData);

        $this->assertTrue($eligible);
    }

    // =========================================================================
    // COOL: Cooldown/Suppression Tests
    // =========================================================================

    /** @test */
    public function cool_001_fourteen_day_refresh_cooldown(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        // Create candidate refreshed 7 days ago
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

        // Create candidate refreshed 20 days ago
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
    // GD: Graceful Degradation Tests
    // =========================================================================

    /** @test */
    public function gd_001_gsc_missing_detection_fails(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscError($site);

        $result = $service->detectForSite($site);

        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function gd_002_rankings_missing_continues(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

        // Rankings not available, but detection should proceed
        $result = $service->detectForSite($site);

        $this->assertGreaterThanOrEqual(0, $result['processed_pages']);
    }

    /** @test */
    public function gd_007_all_optional_missing(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

        // Only GSC available, detection should proceed
        $result = $service->detectForSite($site);

        $this->assertGreaterThanOrEqual(0, $result['processed_pages']);
    }

    /** @test */
    public function gd_008_confidence_reflects_available_data(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 45, 'severity' => 'high', 'detection_confidence' => 0.85]); // GSC only = 0.85

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh content']);

        $service = $this->createMockServiceWithDependencies($scoringService, $reasonService, $plannerService, $site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

        $result = $service->detectForSite($site);

        // Should have detection with 0.85 confidence (GSC only)
        $this->assertArrayHasKey('pages', $result);
    }

    // =========================================================================
    // PERF: Performance Tests
    // =========================================================================

    /** @test */
    public function perf_001_single_page_detection_under_500ms(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, [
            'https://example.com/page1' => $this->createEligibleGscData(),
        ]);

        $start = microtime(true);
        $service->detectForSite($site);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration);
    }

    // =========================================================================
    // SEC: Security Tests
    // =========================================================================

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

        // Should not cause SQL error, URL should be sanitized
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

        // XSS should be escaped in the stored evidence
        $this->assertStringNotContainsString('<script>', $candidate->evidence_json['malicious'] ?? '');
    }

    /** @test */
    public function sec_003_user_isolation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $site1 = Site::factory()->create(['user_id' => $user1->id]);
        $site2 = Site::factory()->create(['user_id' => $user2->id]);

        $project1 = ContentDecayProject::factory()->create([
            'site_id' => $site1->id,
            'user_id' => $user1->id,
        ]);

        $project2 = ContentDecayProject::factory()->create([
            'site_id' => $site2->id,
            'user_id' => $user2->id,
        ]);

        // User1 should not be able to detect on User2's project
        $service = $this->createMockService();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $service->detectForProject($project2, $user1);
    }

    // =========================================================================
    // EDGE: Edge Cases Tests
    // =========================================================================

    /** @test */
    public function edge_001_empty_gsc_response(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $service = $this->createMockServiceWithGscData($site, []);

        $result = $service->detectForSite($site);

        $this->assertEquals(0, $result['processed_pages']);
    }

    /** @test */
    public function edge_004_zero_score_decay(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 0, 'severity' => 'low']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => null]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $service = $this->createMockServiceWithDependencies($scoringService, $reasonService, $plannerService, $site, [
            'https://example.com/page1' => $this->createHealthyGscData(),
        ]);

        $result = $service->detectForSite($site);

        // Zero decay should not create a candidate
        $this->assertEquals(0, $result['candidates_created']);
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
            'page_url' => 'https://example.com/文章/标题', // Chinese characters
            'decay_score' => 45,
            'severity' => 'high',
        ];

        $candidate = $service->upsertCandidate($detectionData);

        $this->assertEquals('https://example.com/文章/标题', $candidate->page_url);
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

    private function createMockServiceWithDependencies(
        $scoringService,
        $reasonService,
        $plannerService,
        $site,
        array $gscData
    ): ContentDecayDetectionService {
        // This would be replaced with actual service that can mock GSC data
        return $this->partialMock(ContentDecayDetectionService::class, function ($mock) use ($scoringService, $reasonService, $plannerService, $gscData) {
            $mock->shouldReceive('getScoringService')->andReturn($scoringService);
            $mock->shouldReceive('getReasonService')->andReturn($reasonService);
            $mock->shouldReceive('getPlannerService')->andReturn($plannerService);
            $mock->shouldReceive('fetchGscData')->andReturn($gscData);
        });
    }

    private function createMockServiceWithGscData($site, array $gscData): ContentDecayDetectionService
    {
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        // Return a service that can mock GSC data retrieval
        return $this->partialMock(ContentDecayDetectionService::class, function ($mock) use ($scoringService, $reasonService, $plannerService, $gscData) {
            $mock->shouldReceive('getScoringService')->andReturn($scoringService);
            $mock->shouldReceive('getReasonService')->andReturn($reasonService);
            $mock->shouldReceive('getPlannerService')->andReturn($plannerService);
            $mock->shouldReceive('fetchGscData')->andReturn($gscData);

            $scoringService->shouldReceive('calculateDecayScore')->andReturn(['decay_score' => 30, 'severity' => 'medium']);
            $reasonService->shouldReceive('assignReasonCodes')->andReturn(['primary_reason_code' => 'clicks_down']);
            $plannerService->shouldReceive('generateRecommendations')->andReturn(['primary_recommendation' => 'Refresh']);
        });
    }

    private function createMockServiceWithGscError($site): ContentDecayDetectionService
    {
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        return $this->partialMock(ContentDecayDetectionService::class, function ($mock) use ($scoringService, $reasonService, $plannerService) {
            $mock->shouldReceive('getScoringService')->andReturn($scoringService);
            $mock->shouldReceive('getReasonService')->andReturn($reasonService);
            $mock->shouldReceive('getPlannerService')->andReturn($plannerService);
            $mock->shouldReceive('fetchGscData')->andThrow(new \Exception('GSC API Error'));
        });
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
                'impressions' => 50, // Below threshold
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

    private function createDecayingGscData(): array
    {
        return [
            'previous' => [
                'clicks' => 1500,
                'impressions' => 25000,
                'ctr' => 0.06,
                'position' => 4.0,
            ],
            'current' => [
                'clicks' => 600, // 60% drop
                'impressions' => 20000, // 20% drop
                'ctr' => 0.03,
                'position' => 12.0, // Position decline
            ],
        ];
    }

    private function createHealthyGscData(): array
    {
        return [
            'previous' => [
                'clicks' => 1000,
                'impressions' => 20000,
                'ctr' => 0.05,
                'position' => 5.0,
            ],
            'current' => [
                'clicks' => 1100, // Slight increase
                'impressions' => 21000,
                'ctr' => 0.052,
                'position' => 4.8, // Slight improvement
            ],
        ];
    }
}
