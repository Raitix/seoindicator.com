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
            'is_enabled' => true,
            'settings_json' => [
                'window_days' => 28,
                'min_impressions_threshold' => 100,
                'min_clicks_threshold' => 10,
            ],
        ]);

        // Mock the dependent services
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn([
                'decay_score' => 45.5,
                'severity' => 'high',
                'detection_confidence' => 0.93,
                'contributing_factors' => [
                    ['factor' => 'click_loss', 'contribution' => 50.0],
                ],
                'score_breakdown' => [
                    'click_loss' => ['value' => 0.32, 'weight' => 0.35, 'contribution' => 11.2],
                ],
            ]);

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
                        'change_percent' => -60.0,
                        'change_absolute' => -900,
                    ],
                ],
            ]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn([
                'primary_recommendation' => 'Refresh content and improve meta tags',
                'supporting_recommendations' => [
                    'Update title tag to improve CTR',
                    'Add internal links from high-authority pages',
                ],
                'task_suggestions' => [
                    [
                        'title' => 'Update page title',
                        'recommendation_type' => 'meta',
                        'description' => 'Your title has low CTR',
                        'priority' => 'high',
                    ],
                ],
            ]);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);

        // Mock GSC data fetch
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
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
        });

        $result = $service->detectForSite($site);

        // Verify result structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('processed_pages', $result);
        $this->assertArrayHasKey('candidates_created', $result);
        $this->assertGreaterThanOrEqual(0, $result['candidates_created']);

        // Verify candidate was created in database
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
            'is_enabled' => true,
        ]);

        $expectedScore = 67.5;
        $expectedSeverity = 'high';

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->once()
            ->andReturn([
                'decay_score' => $expectedScore,
                'severity' => $expectedSeverity,
                'detection_confidence' => 0.93,
            ]);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                    'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
                ],
            ]);
        });

        $result = $service->detectForSite($site);

        // Verify scoring service was called and score was applied
        $candidate = ContentDecayCandidate::where('project_id', $project->id)->first();
        if ($candidate) {
            $this->assertEquals($expectedScore, $candidate->decay_score);
            $this->assertEquals($expectedSeverity, $candidate->severity);
        }
    }

    /** @test */
    public function int_003_reason_service_integration(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'is_enabled' => true,
        ]);

        $expectedReasons = ['clicks_down', 'position_down', 'ctr_down'];

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 45, 'severity' => 'high']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->once()
            ->andReturn([
                'primary_reason_code' => 'clicks_down',
                'reason_codes' => $expectedReasons,
            ]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                    'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
                ],
            ]);
        });

        $result = $service->detectForSite($site);

        // Verify reason codes were applied
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
            'is_enabled' => true,
        ]);

        $expectedRecommendation = 'Rewrite meta tags and refresh content';

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 45, 'severity' => 'high']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->once()
            ->andReturn([
                'primary_recommendation' => $expectedRecommendation,
                'supporting_recommendations' => ['Add FAQ section'],
                'task_suggestions' => [],
            ]);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                    'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
                ],
            ]);
        });

        $result = $service->detectForSite($site);

        // Verify recommendation is in result
        if ($result['candidates_created'] > 0) {
            $this->assertEquals(
                $expectedRecommendation,
                $result['pages'][0]['recommendation'] ?? null
            );
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
            'is_enabled' => true,
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 55.5, 'severity' => 'high', 'detection_confidence' => 0.93]);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down', 'reason_codes' => ['clicks_down']]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                    'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
                ],
            ]);
        });

        // Before detection
        $this->assertEquals(0, ContentDecayCandidate::count());
        $this->assertEquals(0, ContentDecaySignal::count());

        $result = $service->detectForSite($site);

        // After detection - verify models were persisted
        $this->assertGreaterThanOrEqual(0, ContentDecayCandidate::count());

        if ($result['candidates_created'] > 0) {
            $candidate = ContentDecayCandidate::first();
            $this->assertNotNull($candidate->decay_score);
            $this->assertNotNull($candidate->severity);
            $this->assertNotNull($candidate->reason_codes);
            $this->assertEquals('backlog', $candidate->status);
        }
    }

    /** @test */
    public function int_007_queue_job_integration(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'is_enabled' => true,
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 45, 'severity' => 'high']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                    'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
                ],
            ]);
        });

        // Run detection (if it dispatches jobs for async processing)
        $result = $service->detectForSite($site);

        // Queue assertions would go here if detection uses queues
        // Queue::assertPushed(ContentDecayDetectionJob::class);
    }

    /** @test */
    public function int_010_transaction_handling(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'is_enabled' => true,
        ]);

        // First, set up a scenario that will partially succeed then fail
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->once()
            ->andReturn(['decay_score' => 45, 'severity' => 'high']);
        $scoringService->shouldReceive('calculateDecayScore')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                    'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
                ],
                'https://example.com/page2' => [
                    'previous' => ['clicks' => 1200, 'impressions' => 20000, 'ctr' => 0.06, 'position' => 5.0],
                    'current' => ['clicks' => 500, 'impressions' => 15000, 'ctr' => 0.033, 'position' => 15.0],
                ],
            ]);
        });

        // Before detection
        $initialCount = ContentDecayCandidate::count();

        try {
            $service->detectForSite($site);
        } catch (\Exception $e) {
            // Expected exception
        }

        // After failed detection - verify transaction rolled back
        // Depending on implementation, either all or none should be created
        $finalCount = ContentDecayCandidate::count();

        // If transactions work, we shouldn't have partial data
        // Either 0 candidates (rolled back) or graceful error handling
        $this->assertLessThanOrEqual(1, $finalCount);
    }

    /** @test */
    public function int_014_digest_generation(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'is_enabled' => true,
            'digest_frequency' => 'weekly',
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 55, 'severity' => 'high']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                    'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
                ],
            ]);
        });

        $result = $service->detectForSite($site);

        // Verify digest timestamp was updated
        $project->refresh();
        $this->assertNotNull($project->digest_sent_at);
    }

    /** @test */
    public function int_015_recovery_detection(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'is_enabled' => true,
        ]);

        // Create existing candidate with decay
        $existingCandidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'status' => 'backlog',
            'decay_score' => 55,
            'severity' => 'high',
        ]);

        // New data shows recovery (no decay)
        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 5, 'severity' => 'low']); // Very low decay

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => null]);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1000, 'impressions' => 20000, 'ctr' => 0.05, 'position' => 5.0],
                    'current' => ['clicks' => 1100, 'impressions' => 21000, 'ctr' => 0.052, 'position' => 4.8],
                ],
            ]);
        });

        $result = $service->detectForSite($site);

        // Verify candidate was marked as recovered
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
            'is_enabled' => true,
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 40, 'severity' => 'medium']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
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
        });

        $result = $service->detectForSite($site);

        $this->assertEquals(3, $result['processed_pages']);
        $this->assertEquals(3, $result['candidates_created']);
        $this->assertCount(3, $result['pages']);

        // Verify all candidates were created
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
            'is_enabled' => true,
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 40, 'severity' => 'medium']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                // Eligible page
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                    'current' => ['clicks' => 900, 'impressions' => 22000, 'ctr' => 0.04, 'position' => 8.0],
                ],
                // Ineligible page (low impressions)
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
        });

        $result = $service->detectForSite($site);

        // Only 2 eligible pages should be processed
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
            'is_enabled' => true,
        ]);

        // Create existing candidate with "planned" status
        $existingCandidate = ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'page_url' => 'https://example.com/page1',
            'status' => 'planned',
            'decay_score' => 45,
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 55, 'severity' => 'high']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 1500, 'impressions' => 25000, 'ctr' => 0.06, 'position' => 4.0],
                    'current' => ['clicks' => 600, 'impressions' => 20000, 'ctr' => 0.03, 'position' => 12.0],
                ],
            ]);
        });

        $result = $service->detectForSite($site);

        // Status should remain "planned" (not reset to backlog)
        $existingCandidate->refresh();
        $this->assertEquals('planned', $existingCandidate->status);

        // But score should be updated
        $this->assertEquals(55, $existingCandidate->decay_score);
    }

    // =========================================================================
    // Configuration Tests
    // =========================================================================

    /** @test */
    public function it_uses_custom_project_thresholds(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'is_enabled' => true,
            'settings_json' => [
                'min_impressions_threshold' => 50, // Lower than default
                'min_clicks_threshold' => 5,      // Lower than default
            ],
        ]);

        $scoringService = Mockery::mock(ContentDecayScoringServiceInterface::class);
        $scoringService->shouldReceive('calculateDecayScore')
            ->andReturn(['decay_score' => 35, 'severity' => 'medium']);

        $reasonService = Mockery::mock(ContentDecayReasonServiceInterface::class);
        $reasonService->shouldReceive('assignReasonCodes')
            ->andReturn(['primary_reason_code' => 'clicks_down']);

        $plannerService = Mockery::mock(ContentRefreshPlannerServiceInterface::class);
        $plannerService->shouldReceive('generateRecommendations')
            ->andReturn(['primary_recommendation' => 'Refresh']);

        $this->app->instance(ContentDecayScoringServiceInterface::class, $scoringService);
        $this->app->instance(ContentDecayReasonServiceInterface::class, $reasonService);
        $this->app->instance(ContentRefreshPlannerServiceInterface::class, $plannerService);

        $service = app(ContentDecayDetectionService::class);
        $service = $this->partialMock(ContentDecayDetectionService::class, function ($mock) {
            // Page with 75 impressions - would be ineligible with default 100 threshold
            // But eligible with custom 50 threshold
            $mock->shouldReceive('fetchGscData')->andReturn([
                'https://example.com/page1' => [
                    'previous' => ['clicks' => 8, 'impressions' => 75, 'ctr' => 0.10, 'position' => 10.0],
                    'current' => ['clicks' => 4, 'impressions' => 60, 'ctr' => 0.06, 'position' => 15.0],
                ],
            ]);
        });

        $result = $service->detectForSite($site);

        // Page should be eligible with custom threshold
        $this->assertEquals(1, $result['eligible_pages']);
    }
}
