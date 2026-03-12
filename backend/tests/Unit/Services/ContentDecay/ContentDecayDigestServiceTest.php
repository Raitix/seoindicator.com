<?php

namespace Tests\Unit\Services\ContentDecay;

use App\Mail\ContentDecayDigestEmail;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\Notification;
use App\Models\Site;
use App\Models\User;
use App\Services\ContentDecay\ContentDecayDigestService;
use App\Services\ContentDecay\Contracts\ContentDecayDigestServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for ContentDecayDigestService.
 *
 * @see TEST-36-7.md for test documentation
 * @see TASK-36-7
 */
class ContentDecayDigestServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentDecayDigestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContentDecayDigestService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // SI: Service Instantiation Tests
    // ==========================================

    /** @test */
    public function si_001_class_exists_in_correct_namespace(): void
    {
        $this->assertTrue(class_exists(ContentDecayDigestService::class));
    }

    /** @test */
    public function si_002_class_can_be_instantiated(): void
    {
        $service = new ContentDecayDigestService();
        $this->assertInstanceOf(ContentDecayDigestService::class, $service);
    }

    /** @test */
    public function si_003_container_binding_works(): void
    {
        $service = app(ContentDecayDigestService::class);
        $this->assertInstanceOf(ContentDecayDigestService::class, $service);
    }

    /** @test */
    public function si_004_implements_interface(): void
    {
        $service = app(ContentDecayDigestService::class);
        $this->assertInstanceOf(ContentDecayDigestServiceInterface::class, $service);
    }

    /** @test */
    public function si_005_generate_digest_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'generateDigest'));
    }

    /** @test */
    public function si_006_should_send_digest_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'shouldSendDigest'));
    }

    /** @test */
    public function si_007_send_digest_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'sendDigest'));
    }

    /** @test */
    public function si_008_get_digest_period_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getDigestPeriod'));
    }

    /** @test */
    public function si_009_get_new_candidates_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getNewCandidates'));
    }

    /** @test */
    public function si_010_get_recovered_candidates_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getRecoveredCandidates'));
    }

    // ==========================================
    // GD: generateDigest() Tests
    // ==========================================

    /** @test */
    public function gd_001_returns_digest_array_structure(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertIsArray($digest);
        $this->assertArrayHasKey('digest_id', $digest);
        $this->assertArrayHasKey('project_id', $digest);
        $this->assertArrayHasKey('site_id', $digest);
        $this->assertArrayHasKey('generated_at', $digest);
        $this->assertArrayHasKey('period_start', $digest);
        $this->assertArrayHasKey('period_end', $digest);
        $this->assertArrayHasKey('new_candidates', $digest);
        $this->assertArrayHasKey('recovered_candidates', $digest);
        $this->assertArrayHasKey('summary', $digest);
        $this->assertArrayHasKey('top_candidates', $digest);
        $this->assertArrayHasKey('actionable_recommendations', $digest);
    }

    /** @test */
    public function gd_002_respects_weekly_frequency(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertEquals('weekly', $digest['frequency']);
    }

    /** @test */
    public function gd_003_respects_monthly_frequency(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'monthly',
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertEquals('monthly', $digest['frequency']);
    }

    /** @test */
    public function gd_005_excludes_ignored_candidates(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        // Create ignored candidate
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'ignored',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        // Create active candidate
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 45,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertEquals(1, $digest['summary']['total_new']);
        $this->assertCount(1, $digest['new_candidates']);
    }

    /** @test */
    public function gd_007_includes_newly_detected_pages(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertCount(1, $digest['new_candidates']);
    }

    /** @test */
    public function gd_008_includes_recovered_pages(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'recovered',
            'recovered_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertCount(1, $digest['recovered_candidates']);
        $this->assertEquals(1, $digest['summary']['total_recovered']);
    }

    /** @test */
    public function gd_009_generates_summary_statistics(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'severity' => 'critical',
            'decay_score' => 80,
            'lost_click_estimate' => 500,
            'recovery_upside_estimate' => 600,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'severity' => 'high',
            'decay_score' => 60,
            'lost_click_estimate' => 300,
            'recovery_upside_estimate' => 350,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertEquals(2, $digest['summary']['total_new']);
        $this->assertEquals(1, $digest['summary']['critical_count']);
        $this->assertEquals(1, $digest['summary']['high_count']);
        $this->assertEquals(800, $digest['summary']['total_lost_clicks']);
        $this->assertEquals(950, $digest['summary']['estimated_upside_clicks']);
    }

    /** @test */
    public function gd_010_returns_empty_digest_if_no_changes(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertEquals(0, $digest['summary']['total_new']);
        $this->assertEquals(0, $digest['summary']['total_recovered']);
        $this->assertEmpty($digest['new_candidates']);
        $this->assertEmpty($digest['recovered_candidates']);
    }

    /** @test */
    public function gd_012_handles_project_digest_disabled(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => false,
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertNull($digest['digest_id']);
        $this->assertEquals('digest_disabled', $digest['reason']);
    }

    /** @test */
    public function gd_019_sorts_by_decay_score_desc(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 40,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 80,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 60,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $scores = collect($digest['new_candidates'])->pluck('decay_score')->toArray();
        $this->assertEquals([80, 60, 40], $scores);
    }

    /** @test */
    public function gd_025_generates_digest_id(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertStringStartsWith('digest-', $digest['digest_id']);
        $this->assertEquals(19, strlen($digest['digest_id']));
    }

    /** @test */
    public function gd_035_returns_actionable_recommendations(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'severity' => 'critical',
            'decay_score' => 80,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertNotEmpty($digest['actionable_recommendations']);
        $this->assertIsArray($digest['actionable_recommendations']);
    }

    // ==========================================
    // SSD: shouldSendDigest() Tests
    // ==========================================

    /** @test */
    public function ssd_001_returns_true_for_enabled_weekly(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'detection_enabled' => true,
            'digest_frequency' => 'weekly',
            'last_digest_sent_at' => now()->subDays(8),
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertTrue($shouldSend);
    }

    /** @test */
    public function ssd_002_returns_false_for_disabled_digest(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => false,
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertFalse($shouldSend);
    }

    /** @test */
    public function ssd_003_returns_false_for_not_yet_due(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
            'last_digest_sent_at' => now()->subDays(3),
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertFalse($shouldSend);
    }

    /** @test */
    public function ssd_004_returns_true_for_overdue_weekly(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'detection_enabled' => true,
            'digest_frequency' => 'weekly',
            'last_digest_sent_at' => now()->subDays(8),
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertTrue($shouldSend);
    }

    /** @test */
    public function ssd_005_returns_true_for_overdue_monthly(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'detection_enabled' => true,
            'digest_frequency' => 'monthly',
            'last_digest_sent_at' => now()->subDays(32),
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertTrue($shouldSend);
    }

    /** @test */
    public function ssd_006_respects_weekly_frequency(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
            'last_digest_sent_at' => now()->subDays(6),
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertFalse($shouldSend);
    }

    /** @test */
    public function ssd_007_respects_monthly_frequency(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'monthly',
            'last_digest_sent_at' => now()->subDays(25),
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertFalse($shouldSend);
    }

    /** @test */
    public function ssd_008_handles_null_last_digest_sent_at(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
            'last_digest_sent_at' => null,
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertTrue($shouldSend);
    }

    /** @test */
    public function ssd_009_returns_false_if_detection_disabled(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'detection_enabled' => false,
            'digest_frequency' => 'weekly',
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertFalse($shouldSend);
    }

    /** @test */
    public function ssd_018_returns_true_for_first_digest(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
            'last_digest_sent_at' => null,
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertTrue($shouldSend);
    }

    // ==========================================
    // NC: getNewCandidates() Tests
    // ==========================================

    /** @test */
    public function nc_001_returns_collection_of_candidates(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $candidates = $this->service->getNewCandidates($project, now()->subDays(7), now());

        $this->assertInstanceOf(Collection::class, $candidates);
        $this->assertCount(1, $candidates);
    }

    /** @test */
    public function nc_003_filters_by_status_backlog(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'ignored',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $candidates = $this->service->getNewCandidates($project, now()->subDays(7), now());

        $this->assertCount(1, $candidates);
        $this->assertEquals('backlog', $candidates->first()->status);
    }

    /** @test */
    public function nc_005_excludes_ignored_candidates(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'ignored',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $candidates = $this->service->getNewCandidates($project, now()->subDays(7), now());

        $this->assertCount(0, $candidates);
    }

    /** @test */
    public function nc_007_filters_by_severity_threshold(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'min_decay_score' => 50,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 60,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 30,
            'first_detected_at' => now()->subDay(),
        ]);

        $candidates = $this->service->getNewCandidates($project, now()->subDays(7), now());

        $this->assertCount(1, $candidates);
        $this->assertEquals(60, $candidates->first()->decay_score);
    }

    /** @test */
    public function nc_008_orders_by_decay_score_desc(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 40,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 80,
            'first_detected_at' => now()->subDay(),
        ]);

        $candidates = $this->service->getNewCandidates($project, now()->subDays(7), now());

        $this->assertEquals(80, $candidates->first()->decay_score);
    }

    /** @test */
    public function nc_017_handles_empty_result(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $candidates = $this->service->getNewCandidates($project, now()->subDays(7), now());

        $this->assertInstanceOf(Collection::class, $candidates);
        $this->assertCount(0, $candidates);
    }

    // ==========================================
    // RC: getRecoveredCandidates() Tests
    // ==========================================

    /** @test */
    public function rc_001_returns_collection_of_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'recovered',
            'recovered_at' => now()->subDay(),
        ]);

        $candidates = $this->service->getRecoveredCandidates($project, now()->subDays(7), now());

        $this->assertInstanceOf(Collection::class, $candidates);
        $this->assertCount(1, $candidates);
    }

    /** @test */
    public function rc_002_filters_by_status_recovered(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'recovered',
            'recovered_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'first_detected_at' => now()->subDay(),
        ]);

        $candidates = $this->service->getRecoveredCandidates($project, now()->subDays(7), now());

        $this->assertCount(1, $candidates);
        $this->assertEquals('recovered', $candidates->first()->status);
    }

    /** @test */
    public function rc_005_excludes_ignored_candidates(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'ignored',
            'recovered_at' => now()->subDay(),
        ]);

        $candidates = $this->service->getRecoveredCandidates($project, now()->subDays(7), now());

        $this->assertCount(0, $candidates);
    }

    /** @test */
    public function rc_006_orders_by_recovered_at_desc(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'recovered',
            'recovered_at' => now()->subDays(3),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'recovered',
            'recovered_at' => now()->subDay(),
        ]);

        $candidates = $this->service->getRecoveredCandidates($project, now()->subDays(7), now());

        $this->assertTrue($candidates->first()->recovered_at->gt($candidates->last()->recovered_at));
    }

    /** @test */
    public function rc_013_handles_empty_result(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $candidates = $this->service->getRecoveredCandidates($project, now()->subDays(7), now());

        $this->assertInstanceOf(Collection::class, $candidates);
        $this->assertCount(0, $candidates);
    }

    // ==========================================
    // DP: getDigestPeriod() Tests
    // ==========================================

    /** @test */
    public function dp_001_returns_array_with_start_and_end(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_frequency' => 'weekly',
        ]);

        $period = $this->service->getDigestPeriod($project);

        $this->assertIsArray($period);
        $this->assertArrayHasKey('start_date', $period);
        $this->assertArrayHasKey('end_date', $period);
    }

    /** @test */
    public function dp_002_weekly_period_is_seven_days(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_frequency' => 'weekly',
        ]);

        $period = $this->service->getDigestPeriod($project);

        $daysDiff = (int) round($period['start_date']->diffInDays($period['end_date']));
        $this->assertEquals(7, $daysDiff);
    }

    /** @test */
    public function dp_003_monthly_period_is_thirty_days(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_frequency' => 'monthly',
        ]);

        $period = $this->service->getDigestPeriod($project);

        $daysDiff = (int) round($period['start_date']->diffInDays($period['end_date']));
        $this->assertEquals(30, $daysDiff);
    }

    // ==========================================
    // SD: sendDigest() Tests
    // ==========================================

    /** @test */
    public function sd_001_calls_notification_system(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $digest = [
            'digest_id' => 'digest-test123',
            'project_id' => 1,
            'summary' => [
                'total_new' => 5,
                'total_recovered' => 2,
                'critical_count' => 1,
            ],
        ];

        $this->service->sendDigest($user, $digest);

        Mail::assertQueued(ContentDecayDigestEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /** @test */
    public function sd_007_creates_in_app_notification(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $digest = [
            'digest_id' => 'digest-test123',
            'project_id' => 1,
            'summary' => [
                'total_new' => 5,
                'total_recovered' => 2,
                'critical_count' => 1,
            ],
        ];

        $this->service->sendDigest($user, $digest);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'content_decay_digest',
        ]);
    }

    /** @test */
    public function sd_004_handles_notification_exception(): void
    {
        $user = User::factory()->create();
        $digest = [
            'digest_id' => 'digest-test123',
            'project_id' => 1,
            'summary' => [],
        ];

        // Mock Mail to throw exception
        Mail::shouldReceive('to->queue')
            ->once()
            ->andThrow(new \Exception('Mail failed'));

        $this->expectException(\Exception::class);
        $this->service->sendDigest($user, $digest);
    }

    // ==========================================
    // EX: Exclusion Logic Tests
    // ==========================================

    /** @test */
    public function ex_001_excludes_ignored_candidates(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'ignored',
            'decay_score' => 80,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertEquals(1, $digest['summary']['total_new']);
    }

    /** @test */
    public function ex_004_excludes_candidates_below_score(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'min_decay_score' => 40,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 30,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertEquals(1, $digest['summary']['total_new']);
        $this->assertEquals(50, $digest['new_candidates'][0]['decay_score']);
    }

    // ==========================================
    // EDGE: Edge Case Tests
    // ==========================================

    /** @test */
    public function edge_001_empty_project(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertEquals(0, $digest['summary']['total_new']);
        $this->assertEquals(0, $digest['summary']['total_recovered']);
    }

    /** @test */
    public function edge_006_unicode_in_page_urls(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'page_url' => 'https://example.com/文章/标题',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertStringContainsString('文章', $digest['new_candidates'][0]['page_url']);
    }

    /** @test */
    public function edge_007_very_long_url(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $longUrl = 'https://example.com/' . str_repeat('a', 200);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'page_url' => $longUrl,
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertLessThanOrEqual(83, strlen($digest['new_candidates'][0]['page_url']));
    }

    /** @test */
    public function edge_017_invalid_frequency_setting(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'invalid',
        ]);

        $shouldSend = $this->service->shouldSendDigest($project);

        $this->assertFalse($shouldSend);
    }

    // ==========================================
    // FDP: formatDigestPayload() Tests
    // ==========================================

    /** @test */
    public function fdp_001_returns_formatted_array(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_frequency' => 'weekly',
        ]);

        $newCandidates = collect([
            ContentDecayCandidate::factory()->make([
                'project_id' => $project->id,
                'status' => 'backlog',
                'decay_score' => 50,
                'severity' => 'high',
            ]),
        ]);

        $recoveredCandidates = collect([]);

        $payload = $this->service->formatDigestPayload($project, $newCandidates, $recoveredCandidates);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('digest_id', $payload);
        $this->assertArrayHasKey('summary', $payload);
    }

    /** @test */
    public function fdp_020_json_serializable(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
        ]);

        $payload = $this->service->formatDigestPayload($project, collect([]), collect([]));

        $json = json_encode($payload);
        $this->assertNotFalse($json);
        $this->assertIsString($json);
    }
}
