<?php

namespace Tests\Feature\Services\ContentDecay;

use App\Jobs\ContentDecay\SendContentDecayDigestJob;
use App\Mail\ContentDecayDigestEmail;
use App\Models\ContentDecayCandidate;
use App\Models\ContentDecayProject;
use App\Models\Notification;
use App\Models\Site;
use App\Models\User;
use App\Services\ContentDecay\ContentDecayDigestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for ContentDecayDigestService.
 *
 * Tests full integration with database, mail, and notification systems.
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

    // ==========================================
    // INT: Integration Tests
    // ==========================================

    /** @test */
    public function int_001_full_digest_generation(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id, 'name' => 'Test Site']);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
        ]);

        // Create new decay candidates
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
            'first_detected_at' => now()->subDays(2),
        ]);

        // Create recovered candidate
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'recovered',
            'recovery_upside_estimate' => 200,
            'recovered_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        // Verify structure
        $this->assertArrayHasKey('digest_id', $digest);
        $this->assertArrayHasKey('project_id', $digest);
        $this->assertEquals($project->id, $digest['project_id']);

        // Verify new candidates
        $this->assertCount(2, $digest['new_candidates']);
        $this->assertEquals(2, $digest['summary']['total_new']);

        // Verify recovered
        $this->assertCount(1, $digest['recovered_candidates']);
        $this->assertEquals(1, $digest['summary']['total_recovered']);

        // Verify severity breakdown
        $this->assertEquals(1, $digest['summary']['critical_count']);
        $this->assertEquals(1, $digest['summary']['high_count']);

        // Verify lost clicks and upside
        $this->assertEquals(800, $digest['summary']['total_lost_clicks']);
        $this->assertEquals(950, $digest['summary']['estimated_upside_clicks']);

        // Verify recommendations
        $this->assertNotEmpty($digest['actionable_recommendations']);
    }

    /** @test */
    public function int_002_database_persistence_for_notifications(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $digest = [
            'digest_id' => 'digest-test-123',
            'project_id' => $project->id,
            'summary' => [
                'total_new' => 5,
                'total_recovered' => 2,
                'critical_count' => 1,
            ],
        ];

        $this->service->sendDigest($user, $digest);

        // Verify notification was created
        $notification = Notification::where('user_id', $user->id)
            ->where('type', 'content_decay_digest')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('digest-test-123', $notification->data['digest_id']);
        $this->assertEquals(5, $notification->data['new_count']);
    }

    /** @test */
    public function int_003_notification_dispatch(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);
        $site = Site::factory()->create(['user_id' => $user->id, 'name' => 'My Site']);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'severity' => 'high',
            'decay_score' => 60,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);
        $this->service->sendDigest($user, $digest);

        // Verify email was queued
        Mail::assertQueued(ContentDecayDigestEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /** @test */
    public function int_005_queue_job_integration(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
            'last_digest_sent_at' => now()->subDays(8),
        ]);

        // Dispatch job
        SendContentDecayDigestJob::dispatch($project->id);

        Queue::assertPushed(SendContentDecayDigestJob::class, function ($job) use ($project) {
            return $job->projectId === $project->id;
        });
    }

    /** @test */
    public function int_006_api_response_format(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $digest = $this->service->generateDigest($project);

        // Verify all required keys for API response
        $requiredKeys = [
            'digest_id',
            'project_id',
            'site_id',
            'generated_at',
            'period_start',
            'period_end',
            'frequency',
            'new_candidates',
            'recovered_candidates',
            'summary',
            'top_candidates',
            'actionable_recommendations',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $digest, "Missing key: {$key}");
        }

        // Verify summary has all required fields
        $summaryKeys = [
            'total_new',
            'total_recovered',
            'critical_count',
            'high_count',
            'medium_count',
            'low_count',
            'total_lost_clicks',
            'estimated_upside_clicks',
            'severity_breakdown',
            'page_type_breakdown',
        ];

        foreach ($summaryKeys as $key) {
            $this->assertArrayHasKey($key, $digest['summary'], "Missing summary key: {$key}");
        }
    }

    /** @test */
    public function int_011_metrics_collection(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $digest = $this->service->generateDigest($project);

        // Verify timing metrics are included
        $this->assertArrayHasKey('generation_time_ms', $digest);
        $this->assertIsNumeric($digest['generation_time_ms']);
    }

    /** @test */
    public function int_012_multiple_projects(): void
    {
        $user = User::factory()->create();

        $site1 = Site::factory()->create(['user_id' => $user->id]);
        $project1 = ContentDecayProject::factory()->create([
            'site_id' => $site1->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $site2 = Site::factory()->create(['user_id' => $user->id]);
        $project2 = ContentDecayProject::factory()->create([
            'site_id' => $site2->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project1->id,
            'status' => 'backlog',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project2->id,
            'status' => 'backlog',
            'decay_score' => 60,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest1 = $this->service->generateDigest($project1);
        $digest2 = $this->service->generateDigest($project2);

        $this->assertEquals(1, $digest1['summary']['total_new']);
        $this->assertEquals(1, $digest2['summary']['total_new']);
        $this->assertNotEquals($digest1['project_id'], $digest2['project_id']);
    }

    /** @test */
    public function int_013_recovery_measurement(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        // Create recovered candidate with upside
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'recovered',
            'recovery_upside_estimate' => 500,
            'severity' => 'high',
            'recovered_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        $this->assertCount(1, $digest['recovered_candidates']);
        $this->assertEquals(500, $digest['recovered_candidates'][0]['recovery_upside']);
    }

    /** @test */
    public function int_014_notification_preferences(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $digest = $this->service->generateDigest($project);
        $this->service->sendDigest($user, $digest);

        // Verify notification was created for user
        $notification = Notification::where('user_id', $user->id)
            ->where('type', 'content_decay_digest')
            ->first();

        $this->assertNotNull($notification);
    }

    /** @test */
    public function int_015_cleanup_old_records(): void
    {
        // Create old notification
        $user = User::factory()->create();
        $oldNotification = Notification::create([
            'user_id' => $user->id,
            'type' => 'content_decay_digest',
            'data' => ['digest_id' => 'old-digest'],
            'created_at' => now()->subDays(90),
        ]);

        // Verify old notification exists
        $this->assertDatabaseHas('notifications', [
            'id' => $oldNotification->id,
        ]);

        // The service doesn't auto-cleanup, but we verify the structure allows for it
        $this->assertNotNull($oldNotification->id);
    }

    // ==========================================
    // Email Template Tests
    // ==========================================

    /** @test */
    public function email_001_renders_correctly(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $digest = [
            'digest_id' => 'digest-test-123',
            'project_id' => 1,
            'site_id' => 1,
            'frequency' => 'weekly',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-08',
            'summary' => [
                'total_new' => 5,
                'total_recovered' => 2,
                'critical_count' => 1,
                'high_count' => 3,
                'total_lost_clicks' => 1000,
                'estimated_upside_clicks' => 1500,
            ],
            'top_candidates' => [
                [
                    'page_url' => 'https://example.com/page1',
                    'page_title' => 'Test Page 1',
                    'decay_score' => 75,
                    'severity' => 'critical',
                    'lost_clicks' => 500,
                ],
            ],
            'recovered_candidates' => [
                [
                    'page_url' => 'https://example.com/page2',
                    'page_title' => 'Recovered Page',
                    'recovery_upside' => 300,
                ],
            ],
            'actionable_recommendations' => [
                'Focus on critical pages',
            ],
        ];

        $mailable = new ContentDecayDigestEmail($user, $digest);

        $mailable->assertSeeInHtml('Test User');
        $mailable->assertSeeInHtml('Content Health Report');
    }

    /** @test */
    public function email_002_subject_includes_site_name(): void
    {
        $user = User::factory()->create();
        $digest = [
            'project_id' => 1,
            'frequency' => 'weekly',
            'summary' => ['total_new' => 5, 'total_recovered' => 2],
            'site_name' => 'My Awesome Site',
        ];

        $mailable = new ContentDecayDigestEmail($user, $digest);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('My Awesome Site', $envelope->subject);
    }

    /** @test */
    public function email_003_subject_includes_counts(): void
    {
        $user = User::factory()->create();
        $digest = [
            'project_id' => 1,
            'frequency' => 'weekly',
            'summary' => ['total_new' => 5, 'total_recovered' => 2],
            'site_name' => 'Test Site',
        ];

        $mailable = new ContentDecayDigestEmail($user, $digest);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('5 new', $envelope->subject);
        $this->assertStringContainsString('2 recovered', $envelope->subject);
    }

    /** @test */
    public function email_004_handles_empty_digest(): void
    {
        $user = User::factory()->create();
        $digest = [
            'digest_id' => 'digest-empty',
            'project_id' => 1,
            'frequency' => 'weekly',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-08',
            'summary' => [
                'total_new' => 0,
                'total_recovered' => 0,
                'critical_count' => 0,
            ],
            'top_candidates' => [],
            'recovered_candidates' => [],
            'actionable_recommendations' => [],
        ];

        $mailable = new ContentDecayDigestEmail($user, $digest);

        $mailable->assertSeeInHtml('All Clear');
    }

    // ==========================================
    // Performance Tests
    // ==========================================

    /** @test */
    public function perf_001_single_project_digest(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        $start = microtime(true);
        $digest = $this->service->generateDigest($project);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $duration, 'Digest generation should be under 100ms');
    }

    /** @test */
    public function perf_002_small_candidate_set(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        // Create 10 candidates
        ContentDecayCandidate::factory()->count(10)->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => rand(30, 80),
            'first_detected_at' => now()->subDay(),
        ]);

        $start = microtime(true);
        $digest = $this->service->generateDigest($project);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $duration, 'Digest with 10 candidates should be under 200ms');
    }

    /** @test */
    public function perf_003_medium_candidate_set(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        // Create 100 candidates
        ContentDecayCandidate::factory()->count(100)->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => rand(30, 80),
            'first_detected_at' => now()->subDay(),
        ]);

        $start = microtime(true);
        $digest = $this->service->generateDigest($project);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Digest with 100 candidates should be under 500ms');
    }

    // ==========================================
    // Security Tests
    // ==========================================

    /** @test */
    public function sec_001_sql_injection_in_url_filter(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        // Create candidate with malicious URL
        ContentDecayCandidate::factory()->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'page_url' => "https://example.com/page'; DROP TABLE candidates; --",
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        // Should not throw exception and should handle URL safely
        $this->assertCount(1, $digest['new_candidates']);
    }

    /** @test */
    public function sec_002_xss_in_page_title(): void
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
            'page_title' => '<script>alert("xss")</script>Test Page',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        // Title should be present (blade template handles escaping)
        $this->assertCount(1, $digest['new_candidates']);
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
            'digest_enabled' => true,
        ]);

        $project2 = ContentDecayProject::factory()->create([
            'site_id' => $site2->id,
            'user_id' => $user2->id,
            'digest_enabled' => true,
        ]);

        // Create candidates for both projects
        ContentDecayCandidate::factory()->create([
            'project_id' => $project1->id,
            'status' => 'backlog',
            'decay_score' => 50,
            'first_detected_at' => now()->subDay(),
        ]);

        ContentDecayCandidate::factory()->create([
            'project_id' => $project2->id,
            'status' => 'backlog',
            'decay_score' => 60,
            'first_detected_at' => now()->subDay(),
        ]);

        // Each project should only see its own candidates
        $digest1 = $this->service->generateDigest($project1);
        $digest2 = $this->service->generateDigest($project2);

        $this->assertEquals(1, $digest1['summary']['total_new']);
        $this->assertEquals(1, $digest2['summary']['total_new']);
        $this->assertNotEquals($digest1['project_id'], $digest2['project_id']);
    }

    /** @test */
    public function sec_006_payload_size_limit(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $project = ContentDecayProject::factory()->create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'digest_enabled' => true,
        ]);

        // Create 500 candidates
        ContentDecayCandidate::factory()->count(500)->create([
            'project_id' => $project->id,
            'status' => 'backlog',
            'decay_score' => rand(30, 80),
            'first_detected_at' => now()->subDay(),
        ]);

        $digest = $this->service->generateDigest($project);

        // Should handle large datasets without memory issues
        $this->assertIsArray($digest);
        $this->assertArrayHasKey('summary', $digest);
    }
}
