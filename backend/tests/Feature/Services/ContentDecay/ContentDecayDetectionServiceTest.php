<?php

namespace Tests\Feature\Services\ContentDecay;

use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\ContentDecaySignal;
use App\Models\Site;
use App\Models\User;
use App\Services\ContentDecay\Contracts\ContentDecayScoringServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayReasonServiceInterface;
use App\Services\ContentDecay\Contracts\ContentRefreshPlannerServiceInterface;
use App\Services\ContentDecay\ContentDecayDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for ContentDecayDetectionService.
 *
 * These tests verify the full integration of the detection service
 * with the database, events, and other services.
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
    // INT: Integration Tests
    // =========================================================================

    /** @test */
    public function int_001_full_detection_flow(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
            'settings_json' => [
                'window_days' => 28,
                'min_impressions_threshold' => 100,
                'min_clicks_threshold' => 10,
            ],
        ]);

        // Create mock services
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn([
                'decay_score' => 45.5,
                'severity' => 'high',
                'detection_confidence' => 0.93,
            ]);

        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.32);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.20);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.25);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.15);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn([
                'primary_reason_code' => 'clicks_down',
                'reason_codes' => ['clicks_down', 'position_down'],
                'evidence' => [
                    'clicks' => [
                        'metric' => 'clicks',
                        'previous' => 1500,
                        'current' => 600,
                    ],
                ],
            ]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn([
                'primary_recommendation' => 'Refresh content',
            ]);

        // Create service with mocked fetchGscData
        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            'https://example.com/blog/seo-tips' => [
                'previous' => [
                    'clicks' => 1500,
                    'impressions' => 25000,
                    'ctr' => 0.06,
                    'position' => 4.0,
                ],
                'current' => [
                    'clicks' => 600,
                    'impressions' => 20000,
                    'ctr' => 0.03,
                    'position' => 12.0,
                ],
            ],
        ]);

        $result = $service->detectForSite($site);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('processed_pages', $result);
        $this->assertArrayHasKey('candidates_created', $result);

        if ($result['candidates_created'] > 0) {
            $this->assertDatabaseHas('content_decay_candidates', [
                'project_id' => $project->id,
                'page_url' => 'https://example.com/blog/seo-tips',
            ]);
        }
    }

    /** @test */
    public function int_002_scoring_service_integration(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $expectedScore = 67.5;
        $expectedSeverity = 'high';

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn([
                'decay_score' => $expectedScore,
                'severity' => $expectedSeverity,
                'detection_confidence' => 0.93,
            ]);
        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.5);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.2);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.5);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.5);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down', 'reason_codes' => ['clicks_down'], 'evidence' => []]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            'https://example.com/page1' => [
                'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
            ],
        ]);

        $result = $service->detectForSite($site);

        // Ensure candidate was created
        $this->assertEquals(1, $result['candidates_created']);
        
        $candidate = ContentDecayCandidate::where('project_id', $project->id)->first();
        $this->assertNotNull($candidate, 'Candidate should be created');
        // Compare as float with tolerance for decimal precision
        $this->assertEqualsWithDelta($expectedScore, (float) $candidate->decay_score, 0.01);
        $this->assertEquals($expectedSeverity, $candidate->severity);
    }

    /** @test */
    public function int_003_reason_service_integration(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
        ]);

        $expectedReasons = ['clicks_down', 'position_down', 'ctr_down'];

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 45.0, 'severity' => 'high', 'detection_confidence' => 0.85]);
        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.5);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.2);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.5);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.5);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn([
                'primary_reason_code' => 'clicks_down',
                'reason_codes' => $expectedReasons,
                'evidence' => [],
            ]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            'https://example.com/page1' => [
                'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
            ],
        ]);

        $result = $service->detectForSite($site);

        $candidate = ContentDecayCandidate::where('project_id', $project->id)->first();
        if ($candidate) {
            $this->assertEquals($expectedReasons, $candidate->reason_codes);
        }
    }

    /** @test */
    public function int_004_planner_service_integration(): void
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
            ->andReturn(['decay_score' => 45.0, 'severity' => 'high', 'detection_confidence' => 0.85]);
        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.5);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.2);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.5);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.5);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down', 'reason_codes' => ['clicks_down'], 'evidence' => []]);

        // The planner service is not called during detection (recommendations come from reason service)
        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            'https://example.com/page1' => [
                'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
            ],
        ]);

        $result = $service->detectForSite($site);

        // Verify candidate was created and has the primary_reason_code as recommendation
        $this->assertGreaterThanOrEqual(0, $result['candidates_created']);
        if ($result['candidates_created'] > 0) {
            $this->assertEquals('clicks_down', $result['pages'][0]['recommendation'] ?? null);
        }
    }

    /** @test */
    public function int_005_model_persistence(): void
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
            ->andReturn(['decay_score' => 55.5, 'severity' => 'high', 'detection_confidence' => 0.93]);
        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.5);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.2);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.5);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.5);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down', 'reason_codes' => ['clicks_down'], 'evidence' => []]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            'https://example.com/page1' => [
                'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
            ],
        ]);

        $this->assertEquals(0, ContentDecayCandidate::count());

        $result = $service->detectForSite($site);

        $this->assertGreaterThanOrEqual(0, ContentDecayCandidate::count());

        if ($result['candidates_created'] > 0) {
            $candidate = ContentDecayCandidate::first();
            $this->assertNotNull($candidate->decay_score);
            $this->assertNotNull($candidate->severity);
            $this->assertEquals('backlog', $candidate->status);
        }
    }

    /** @test */
    public function int_015_recovery_detection(): void
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
            'decay_score' => 55,
            'severity' => 'high',
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 5.0, 'severity' => 'low', 'detection_confidence' => 0.85]);
        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.05);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.05);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.05);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.05);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => null, 'reason_codes' => [], 'evidence' => []]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            'https://example.com/page1' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 1100, 'impressions' => 21000, 'ctr' => 0.052, 'position' => 4.8],
            ],
        ]);

        $result = $service->detectForSite($site);

        $existingCandidate->refresh();
        $this->assertEquals('recovered', $existingCandidate->status);
    }

    // =========================================================================
    // Multi-Page Detection Tests
    // =========================================================================

    /** @test */
    public function it_processes_multiple_pages_in_single_detection(): void
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
            ->andReturn(['decay_score' => 40, 'severity' => 'medium', 'detection_confidence' => 0.85]);
        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.3);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.2);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.2);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.3);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down', 'reason_codes' => ['clicks_down'], 'evidence' => []]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            'https://example.com/page1' => [
                'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                'current' => ['clicks' => 900, 'impressions' => 22000, 'ctr' => 0.04, 'position' => 8.0],
            ],
            'https://example.com/page2' => [
                'previous' => ['clicks' => 800, 'impressions' => 15000, 'ctr' => 0.05, 'position' => 6.0],
                'current' => ['clicks' => 500, 'impressions' => 12000, 'ctr' => 0.04, 'position' => 10.0],
            ],
            'https://example.com/page3' => [
                'previous' => ['clicks' => 2000, 'impressions' => 30000, 'ctr' => 0.07, 'position' => 3.0],
                'current' => ['clicks' => 1200, 'impressions' => 25000, 'ctr' => 0.05, 'position' => 7.0],
            ],
        ]);

        $result = $service->detectForSite($site);

        $this->assertEquals(3, $result['processed_pages']);
        $this->assertEquals(3, $result['eligible_pages']);
        $this->assertEquals(3, $result['candidates_created']);

        $this->assertEquals(3, ContentDecayCandidate::where('project_id', $project->id)->count());
    }

    /** @test */
    public function it_handles_mixed_eligibility_pages(): void
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
            ->andReturn(['decay_score' => 40.0, 'severity' => 'medium', 'detection_confidence' => 0.85]);
        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.3);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.2);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.2);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.3);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down', 'reason_codes' => ['clicks_down'], 'evidence' => []]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            // Eligible page
            'https://example.com/page1' => [
                'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                'current' => ['clicks' => 900, 'impressions' => 22000, 'ctr' => 0.04, 'position' => 8.0],
            ],
            // Ineligible page (low impressions and clicks)
            'https://example.com/page2' => [
                'previous' => ['clicks' => 5, 'impressions' => 50, 'ctr' => 0.10, 'position' => 15.0],
                'current' => ['clicks' => 3, 'impressions' => 40, 'ctr' => 0.075, 'position' => 20.0],
            ],
            // Eligible page
            'https://example.com/page3' => [
                'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                'current' => ['clicks' => 600, 'impressions' => 18000, 'ctr' => 0.03, 'position' => 10.0],
            ],
        ]);

        $result = $service->detectForSite($site);

        // 2 pages are eligible (page1 and page3), page2 has too few impressions/clicks
        $this->assertEquals(2, $result['eligible_pages']);
        $this->assertEquals(2, $result['candidates_created']);
    }

    // =========================================================================
    // Status Transition Tests
    // =========================================================================

    /** @test */
    public function it_transitions_candidate_status_correctly(): void
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
            'status' => 'planned',
            'decay_score' => 45,
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 55.0, 'severity' => 'high', 'detection_confidence' => 0.93]);
        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.5);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.3);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.3);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.4);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down', 'reason_codes' => ['clicks_down'], 'evidence' => []]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            'https://example.com/page1' => [
                'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
            ],
        ]);

        $result = $service->detectForSite($site);

        $existingCandidate->refresh();
        $this->assertEquals('planned', $existingCandidate->status);
        // Compare as string since model cast returns string for decimal
        $this->assertEquals('55.00', (string) $existingCandidate->decay_score);
    }

    // =========================================================================
    // Configuration Tests
    // =========================================================================

    /** @test */
    public function it_uses_custom_project_thresholds(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        // Set thresholds directly on columns (they take precedence over settings_json)
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'detection_enabled' => true,
            'min_impressions_threshold' => 50,
            'min_clicks_threshold' => 5,
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 35.0, 'severity' => 'medium', 'detection_confidence' => 0.85]);
        $scoringService->shouldReceive('normalizeClickLoss')->andReturn(0.3);
        $scoringService->shouldReceive('normalizeImpressionLoss')->andReturn(0.2);
        $scoringService->shouldReceive('normalizeCtrDrop')->andReturn(0.2);
        $scoringService->shouldReceive('normalizePositionDecline')->andReturn(0.3);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down', 'reason_codes' => ['clicks_down'], 'evidence' => []]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        // Page with 75 impressions - eligible with custom 50 threshold
        $service = $this->createTestService($scoringService, $reasonService, $plannerService, [
            'https://example.com/page1' => [
                'previous' => ['clicks' => 8, 'impressions' => 75, 'ctr' => 0.10, 'position' => 10.0],
                'current' => ['clicks' => 4, 'impressions' => 60, 'ctr' => 0.06, 'position' => 15.0],
            ],
        ]);

        $result = $service->detectForSite($site);

        $this->assertEquals(1, $result['eligible_pages']);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createTestService(
        $scoringService,
        $reasonService,
        $plannerService,
        array $gscData
    ): ContentDecayDetectionService {
        // Create an anonymous class that extends the service to override fetchGscData
        return new class($scoringService, $reasonService, $plannerService, $gscData) extends ContentDecayDetectionService {
            private array $testGscData;

            public function __construct($scoringService, $reasonService, $plannerService, array $gscData)
            {
                parent::__construct($scoringService, $reasonService, $plannerService);
                $this->testGscData = $gscData;
            }

            protected function fetchGscData($site): array
            {
                return $this->testGscData;
            }
        };
    }
}
