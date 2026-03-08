<?php

namespace Tests\Unit\Services\LocalSEO;

use App\Services\LocalSEO\Contracts\LocalRankServiceInterface;
use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use App\Services\LocalSEO\Contracts\GBPInsightsServiceInterface;
use App\Services\LocalSEO\LocalSEOAlertService;
use App\Models\LocalSEOAlert;
use App\Models\BusinessLocation;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LocalSEOAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LocalSEOAlertService $service;
    protected $mockRankService;
    protected $mockReviewsService;
    protected $mockInsightsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRankService = Mockery::mock(LocalRankServiceInterface::class);
        $this->mockReviewsService = Mockery::mock(GBPReviewsServiceInterface::class);
        $this->mockInsightsService = Mockery::mock(GBPInsightsServiceInterface::class);

        $this->app->instance(LocalRankServiceInterface::class, $this->mockRankService);
        $this->app->instance(GBPReviewsServiceInterface::class, $this->mockReviewsService);
        $this->app->instance(GBPInsightsServiceInterface::class, $this->mockInsightsService);

        $this->service = app(LocalSEOAlertService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // SERVICE INSTANTIATION TESTS
    // ==========================================

    public function test_service_instantiation(): void
    {
        $this->assertInstanceOf(LocalSEOAlertService::class, $this->service);
    }

    public function test_service_has_required_methods(): void
    {
        $this->assertTrue(method_exists($this->service, 'checkForAlerts'));
        $this->assertTrue(method_exists($this->service, 'createAlert'));
        $this->assertTrue(method_exists($this->service, 'getUnreadAlerts'));
        $this->assertTrue(method_exists($this->service, 'markAsRead'));
        $this->assertTrue(method_exists($this->service, 'markAllAsRead'));
    }

    // ==========================================
    // CHK: checkForAlerts METHOD TESTS
    // ==========================================

    /** @test */
    public function chk_001_ranking_improved_alert_created(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService
            ->shouldReceive('getLastRanking')
            ->with($location->id)
            ->andReturn((object) [
                'local_pack_position' => 2,
                'keyword' => 'best pizza',
            ]);

        $this->mockRankService
            ->shouldReceive('getPreviousRanking')
            ->with($location->id)
            ->andReturn((object) [
                'local_pack_position' => 5,
                'keyword' => 'best pizza',
            ]);

        $this->mockReviewsService->shouldReceive('getNewReviews')->andReturn(collect());
        $this->mockReviewsService->shouldReceive('getReviewStats')->andReturn([
            'average_rating' => 4.5,
            'previous_rating' => 4.5,
            'total_count' => 100,
            'previous_count' => 100,
        ]);
        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseHas('local_seo_alerts', [
            'location_id' => $location->id,
            'alert_type' => 'ranking_improved',
        ]);
    }

    /** @test */
    public function chk_002_ranking_dropped_alert_created(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService
            ->shouldReceive('getLastRanking')
            ->with($location->id)
            ->andReturn((object) [
                'local_pack_position' => 5,
                'keyword' => 'best pizza',
            ]);

        $this->mockRankService
            ->shouldReceive('getPreviousRanking')
            ->with($location->id)
            ->andReturn((object) [
                'local_pack_position' => 2,
                'keyword' => 'best pizza',
            ]);

        $this->mockReviewsService->shouldReceive('getNewReviews')->andReturn(collect());
        $this->mockReviewsService->shouldReceive('getReviewStats')->andReturn([
            'average_rating' => 4.5,
            'previous_rating' => 4.5,
            'total_count' => 100,
            'previous_count' => 100,
        ]);
        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
        ]);
    }

    /** @test */
    public function chk_003_ranking_unchanged_no_alert(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService
            ->shouldReceive('getLastRanking')
            ->with($location->id)
            ->andReturn((object) [
                'local_pack_position' => 3,
                'keyword' => 'best pizza',
            ]);

        $this->mockRankService
            ->shouldReceive('getPreviousRanking')
            ->with($location->id)
            ->andReturn((object) [
                'local_pack_position' => 3,
                'keyword' => 'best pizza',
            ]);

        $this->mockReviewsService->shouldReceive('getNewReviews')->andReturn(collect());
        $this->mockReviewsService->shouldReceive('getReviewStats')->andReturn([
            'average_rating' => 4.5,
            'previous_rating' => 4.5,
            'total_count' => 100,
            'previous_count' => 100,
        ]);
        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseMissing('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'ranking_improved',
        ]);

        $this->assertDatabaseMissing('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
        ]);
    }

    /** @test */
    public function chk_004_new_position_no_previous_no_alert(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService
            ->shouldReceive('getLastRanking')
            ->with($location->id)
            ->andReturn((object) [
                'local_pack_position' => 3,
                'keyword' => 'best pizza',
            ]);

        $this->mockRankService
            ->shouldReceive('getPreviousRanking')
            ->with($location->id)
            ->andReturn(null);

        $this->mockReviewsService->shouldReceive('getNewReviews')->andReturn(collect());
        $this->mockReviewsService->shouldReceive('getReviewStats')->andReturn([
            'average_rating' => 4.5,
            'previous_rating' => 4.5,
            'total_count' => 100,
            'previous_count' => 100,
        ]);
        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseMissing('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'ranking_improved',
        ]);
    }

    /** @test */
    public function chk_005_new_review_alert_created(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService->shouldReceive('getLastRanking')->andReturn(null);
        $this->mockRankService->shouldReceive('getPreviousRanking')->andReturn(null);

        $this->mockReviewsService
            ->shouldReceive('getNewReviews')
            ->andReturn(collect([
                (object) ['id' => 'rev123', 'rating' => 5, 'author' => 'John D.'],
            ]));

        $this->mockReviewsService
            ->shouldReceive('getReviewStats')
            ->andReturn([
                'average_rating' => 4.5,
                'previous_rating' => 4.5,
                'total_count' => 101,
                'previous_count' => 100,
            ]);

        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'new_review',
        ]);
    }

    /** @test */
    public function chk_006_negative_review_alert_created(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService->shouldReceive('getLastRanking')->andReturn(null);
        $this->mockRankService->shouldReceive('getPreviousRanking')->andReturn(null);

        $this->mockReviewsService
            ->shouldReceive('getNewReviews')
            ->andReturn(collect([
                (object) ['id' => 'rev124', 'rating' => 2, 'author' => 'Jane S.'],
            ]));

        $this->mockReviewsService
            ->shouldReceive('getReviewStats')
            ->andReturn([
                'average_rating' => 4.3,
                'previous_rating' => 4.5,
                'total_count' => 101,
                'previous_count' => 100,
            ]);

        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'negative_review',
        ]);
    }

    /** @test */
    public function chk_007_negative_review_rating_1_created(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService->shouldReceive('getLastRanking')->andReturn(null);
        $this->mockRankService->shouldReceive('getPreviousRanking')->andReturn(null);

        $this->mockReviewsService
            ->shouldReceive('getNewReviews')
            ->andReturn(collect([
                (object) ['id' => 'rev125', 'rating' => 1, 'author' => 'Angry C.'],
            ]));

        $this->mockReviewsService
            ->shouldReceive('getReviewStats')
            ->andReturn([
                'average_rating' => 4.0,
                'previous_rating' => 4.5,
                'total_count' => 101,
                'previous_count' => 100,
            ]);

        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'negative_review',
        ]);
    }

    /** @test */
    public function chk_008_positive_review_no_negative_alert(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService->shouldReceive('getLastRanking')->andReturn(null);
        $this->mockRankService->shouldReceive('getPreviousRanking')->andReturn(null);

        $this->mockReviewsService
            ->shouldReceive('getNewReviews')
            ->andReturn(collect([
                (object) ['id' => 'rev126', 'rating' => 5, 'author' => 'Happy C.'],
            ]));

        $this->mockReviewsService
            ->shouldReceive('getReviewStats')
            ->andReturn([
                'average_rating' => 4.6,
                'previous_rating' => 4.5,
                'total_count' => 101,
                'previous_count' => 100,
            ]);

        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'new_review',
        ]);

        $this->assertDatabaseMissing('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'negative_review',
        ]);
    }

    /** @test */
    public function chk_009_rating_dropped_alert_created(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService->shouldReceive('getLastRanking')->andReturn(null);
        $this->mockRankService->shouldReceive('getPreviousRanking')->andReturn(null);
        $this->mockReviewsService->shouldReceive('getNewReviews')->andReturn(collect());

        $this->mockReviewsService
            ->shouldReceive('getReviewStats')
            ->andReturn([
                'average_rating' => 4.2,
                'previous_rating' => 4.8,
                'total_count' => 100,
                'previous_count' => 100,
            ]);

        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'rating_dropped',
        ]);
    }

    /** @test */
    public function chk_010_rating_dropped_less_than_threshold_no_alert(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService->shouldReceive('getLastRanking')->andReturn(null);
        $this->mockRankService->shouldReceive('getPreviousRanking')->andReturn(null);
        $this->mockReviewsService->shouldReceive('getNewReviews')->andReturn(collect());

        $this->mockReviewsService
            ->shouldReceive('getReviewStats')
            ->andReturn([
                'average_rating' => 4.5,
                'previous_rating' => 4.8,
                'total_count' => 100,
                'previous_count' => 100,
            ]);

        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseMissing('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'rating_dropped',
        ]);
    }

    /** @test */
    public function chk_014_gbp_disconnected_alert_created(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService->shouldReceive('getLastRanking')->andReturn(null);
        $this->mockRankService->shouldReceive('getPreviousRanking')->andReturn(null);
        $this->mockReviewsService->shouldReceive('getNewReviews')->andReturn(collect());
        $this->mockReviewsService->shouldReceive('getReviewStats')->andReturn([
            'average_rating' => 4.5,
            'previous_rating' => 4.5,
            'total_count' => 100,
            'previous_count' => 100,
        ]);

        $this->mockInsightsService
            ->shouldReceive('getConnectionStatus')
            ->with($location->id)
            ->andReturn(false);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'gbp_disconnected',
            'severity' => 'critical',
        ]);
    }

    /** @test */
    public function chk_016_multiple_alerts_created_at_once(): void
    {
        $location = $this->createMockLocation();

        $this->mockRankService
            ->shouldReceive('getLastRanking')
            ->andReturn((object) [
                'local_pack_position' => 5,
                'keyword' => 'best pizza',
            ]);

        $this->mockRankService
            ->shouldReceive('getPreviousRanking')
            ->andReturn((object) [
                'local_pack_position' => 2,
                'keyword' => 'best pizza',
            ]);

        $this->mockReviewsService
            ->shouldReceive('getNewReviews')
            ->andReturn(collect([
                (object) ['id' => 'rev127', 'rating' => 2, 'author' => 'Unhappy C.'],
            ]));

        $this->mockReviewsService
            ->shouldReceive('getReviewStats')
            ->andReturn([
                'average_rating' => 4.2,
                'previous_rating' => 4.5,
                'total_count' => 101,
                'previous_count' => 100,
            ]);

        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
        ]);

        $this->assertDatabaseHas('local_seo_alerts', [
            'business_location_id' => $location->id,
            'alert_type' => 'negative_review',
        ]);
    }

    // ==========================================
    // CREATE: createAlert METHOD TESTS
    // ==========================================

    /** @test */
    public function create_001_create_info_severity_alert(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $alert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'ranking_improved',
            severity: 'info',
            title: 'Ranking improved',
            message: 'Your ranking improved from 5 to 2.'
        );

        $this->assertInstanceOf(LocalSEOAlert::class, $alert);
        $this->assertEquals('info', $alert->severity);
        $this->assertEquals('ranking_improved', $alert->alert_type);
    }

    /** @test */
    public function create_002_create_warning_severity_alert(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $alert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'ranking_dropped',
            severity: 'warning',
            title: 'Ranking dropped',
            message: 'Your ranking dropped from 2 to 5.'
        );

        $this->assertEquals('warning', $alert->severity);
    }

    /** @test */
    public function create_003_create_critical_severity_alert(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $alert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'gbp_disconnected',
            severity: 'critical',
            title: 'GBP disconnected',
            message: 'Your Google Business Profile connection has been lost.'
        );

        $this->assertEquals('critical', $alert->severity);
    }

    /** @test */
    public function create_004_invalid_severity_throws_exception(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'ranking_dropped',
            severity: 'urgent',
            title: 'Test',
            message: 'Test message'
        );
    }

    /** @test */
    public function create_005_deduplication_new_alert_created(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $alert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'ranking_dropped',
            severity: 'warning',
            title: 'Ranking dropped',
            message: 'Test message'
        );

        $this->assertNotNull($alert->id);
    }

    /** @test */
    public function create_006_deduplication_duplicate_within_24h(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        // Create first alert
        $firstAlert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'ranking_dropped',
            severity: 'warning',
            title: 'Ranking dropped',
            message: 'Test message'
        );

        // Try to create duplicate within 24h
        $secondAlert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'ranking_dropped',
            severity: 'warning',
            title: 'Ranking dropped again',
            message: 'Another message'
        );

        // Should return the same alert (deduplicated)
        $this->assertEquals($firstAlert->id, $secondAlert->id);
    }

    /** @test */
    public function create_008_alert_with_data_payload(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $data = [
            'old_position' => 5,
            'new_position' => 2,
            'keyword' => 'best pizza',
        ];

        $alert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'ranking_improved',
            severity: 'info',
            title: 'Ranking improved',
            message: 'Your ranking improved.',
            data: $data
        );

        $this->assertEquals($data, json_decode($alert->data, true));
    }

    // ==========================================
    // GET: getUnreadAlerts METHOD TESTS
    // ==========================================

    /** @test */
    public function get_001_no_unread_alerts(): void
    {
        $user = \App\Models\User::factory()->create();

        $alerts = $this->service->getUnreadAlerts($user->id);

        $this->assertCount(0, $alerts);
    }

    /** @test */
    public function get_002_one_unread_alert(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        LocalSEOAlert::create([
            'user_id' => $user->id,
            'business_location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'title' => 'Test',
            'message' => 'Test message',
        ]);

        $alerts = $this->service->getUnreadAlerts($user->id);

        $this->assertCount(1, $alerts);
    }

    /** @test */
    public function get_003_multiple_unread_alerts(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        for ($i = 0; $i < 5; $i++) {
            LocalSEOAlert::create([
                'user_id' => $user->id,
                'business_location_id' => $location->id,
                'alert_type' => 'ranking_dropped',
                'severity' => 'warning',
                'title' => "Test {$i}",
                'message' => 'Test message',
            ]);
        }

        $alerts = $this->service->getUnreadAlerts($user->id);

        $this->assertCount(5, $alerts);
    }

    /** @test */
    public function get_004_mixed_read_unread(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        // Create 3 unread
        for ($i = 0; $i < 3; $i++) {
            LocalSEOAlert::create([
                'user_id' => $user->id,
                'business_location_id' => $location->id,
                'alert_type' => 'ranking_dropped',
                'severity' => 'warning',
                'title' => "Unread {$i}",
                'message' => 'Test message',
            ]);
        }

        // Create 2 read
        for ($i = 0; $i < 2; $i++) {
            LocalSEOAlert::create([
                'user_id' => $user->id,
                'business_location_id' => $location->id,
                'alert_type' => 'ranking_improved',
                'severity' => 'info',
                'title' => "Read {$i}",
                'message' => 'Test message',
                'read_at' => now(),
            ]);
        }

        $alerts = $this->service->getUnreadAlerts($user->id);

        $this->assertCount(3, $alerts);
    }

    /** @test */
    public function get_005_other_users_alerts_isolation(): void
    {
        $user1 = \App\Models\User::factory()->create();
        $user2 = \App\Models\User::factory()->create();
        $location1 = $this->createMockLocation(['user_id' => $user1->id]);
        $location2 = $this->createMockLocation(['user_id' => $user2->id]);

        LocalSEOAlert::create([
            'user_id' => $user1->id,
            'business_location_id' => $location1->id,
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'title' => 'User 1 Alert',
            'message' => 'Test message',
        ]);

        LocalSEOAlert::create([
            'user_id' => $user2->id,
            'business_location_id' => $location2->id,
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'title' => 'User 2 Alert',
            'message' => 'Test message',
        ]);

        $user1Alerts = $this->service->getUnreadAlerts($user1->id);

        $this->assertCount(1, $user1Alerts);
        $this->assertEquals('User 1 Alert', $user1Alerts->first()->title);
    }

    // ==========================================
    // READ: markAsRead METHOD TESTS
    // ==========================================

    /** @test */
    public function read_001_mark_as_read(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $alert = LocalSEOAlert::create([
            'user_id' => $user->id,
            'business_location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'title' => 'Test',
            'message' => 'Test message',
        ]);

        $result = $this->service->markAsRead($alert->id);

        $this->assertTrue($result);
        $this->assertNotNull($alert->fresh()->read_at);
    }

    /** @test */
    public function read_002_already_read(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $alert = LocalSEOAlert::create([
            'user_id' => $user->id,
            'business_location_id' => $location->id,
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'title' => 'Test',
            'message' => 'Test message',
            'read_at' => now(),
        ]);

        $result = $this->service->markAsRead($alert->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function read_003_non_existent_alert(): void
    {
        $result = $this->service->markAsRead(99999);

        $this->assertFalse($result);
    }

    // ==========================================
    // READALL: markAllAsRead METHOD TESTS
    // ==========================================

    /** @test */
    public function readall_001_mark_all_as_read(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        for ($i = 0; $i < 5; $i++) {
            LocalSEOAlert::create([
                'user_id' => $user->id,
                'business_location_id' => $location->id,
                'alert_type' => 'ranking_dropped',
                'severity' => 'warning',
                'title' => "Test {$i}",
                'message' => 'Test message',
            ]);
        }

        $count = $this->service->markAllAsRead($user->id);

        $this->assertEquals(5, $count);
        $this->assertCount(0, $this->service->getUnreadAlerts($user->id));
    }

    /** @test */
    public function readall_002_no_unread_alerts(): void
    {
        $user = \App\Models\User::factory()->create();

        $count = $this->service->markAllAsRead($user->id);

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function readall_003_filter_by_location(): void
    {
        $user = \App\Models\User::factory()->create();
        $location1 = $this->createMockLocation(['user_id' => $user->id]);
        $location2 = $this->createMockLocation(['user_id' => $user->id]);

        // Alerts for location 1
        for ($i = 0; $i < 3; $i++) {
            LocalSEOAlert::create([
                'user_id' => $user->id,
                'business_location_id' => $location1->id,
                'alert_type' => 'ranking_dropped',
                'severity' => 'warning',
                'title' => "Loc1 {$i}",
                'message' => 'Test message',
            ]);
        }

        // Alerts for location 2
        for ($i = 0; $i < 2; $i++) {
            LocalSEOAlert::create([
                'user_id' => $user->id,
                'business_location_id' => $location2->id,
                'alert_type' => 'ranking_improved',
                'severity' => 'info',
                'title' => "Loc2 {$i}",
                'message' => 'Test message',
            ]);
        }

        $count = $this->service->markAllAsRead($user->id, $location1->id);

        $this->assertEquals(3, $count);

        // Location 2 alerts should still be unread
        $this->assertCount(2, $this->service->getUnreadAlerts($user->id));
    }

    // ==========================================
    // SEVERITY: Severity Assignment Tests
    // ==========================================

    /** @test */
    public function sev_003_ranking_dropped_1_to_3_is_warning(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $this->mockRankService
            ->shouldReceive('getLastRanking')
            ->andReturn((object) ['local_pack_position' => 5, 'keyword' => 'test']);

        $this->mockRankService
            ->shouldReceive('getPreviousRanking')
            ->andReturn((object) ['local_pack_position' => 2, 'keyword' => 'test']);

        $this->mockReviewsService->shouldReceive('getNewReviews')->andReturn(collect());
        $this->mockReviewsService->shouldReceive('getReviewStats')->andReturn([
            'average_rating' => 4.5, 'previous_rating' => 4.5,
            'total_count' => 100, 'previous_count' => 100,
        ]);
        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $alert = LocalSEOAlert::where('alert_type', 'ranking_dropped')->first();
        $this->assertEquals('warning', $alert->severity);
    }

    /** @test */
    public function sev_004_ranking_dropped_4_plus_is_critical(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $this->mockRankService
            ->shouldReceive('getLastRanking')
            ->andReturn((object) ['local_pack_position' => 8, 'keyword' => 'test']);

        $this->mockRankService
            ->shouldReceive('getPreviousRanking')
            ->andReturn((object) ['local_pack_position' => 2, 'keyword' => 'test']);

        $this->mockReviewsService->shouldReceive('getNewReviews')->andReturn(collect());
        $this->mockReviewsService->shouldReceive('getReviewStats')->andReturn([
            'average_rating' => 4.5, 'previous_rating' => 4.5,
            'total_count' => 100, 'previous_count' => 100,
        ]);
        $this->mockInsightsService->shouldReceive('getConnectionStatus')->andReturn(true);

        $this->service->checkForAlerts($location);

        $alert = LocalSEOAlert::where('alert_type', 'ranking_dropped')->first();
        $this->assertEquals('critical', $alert->severity);
    }

    // ==========================================
    // EDGE: Edge Cases Tests
    // ==========================================

    /** @test */
    public function edge_001_null_location_graceful_handling(): void
    {
        $result = $this->service->checkForAlerts(null);

        $this->assertNull($result);
    }

    /** @test */
    public function edge_004_unicode_in_alert_message(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $alert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'new_review',
            severity: 'info',
            title: '新しいレビュー',
            message: '素晴らしいサービス！非常好的服务！'
        );

        $this->assertEquals('新しいレビュー', $alert->title);
        $this->assertStringContainsString('素晴らしい', $alert->message);
    }

    // ==========================================
    // SEC: Security Tests
    // ==========================================

    /** @test */
    public function sec_001_sql_injection_in_title(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $alert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'ranking_dropped',
            severity: 'warning',
            title: "'; DROP TABLE alerts;--",
            message: 'Test message'
        );

        $this->assertStringContainsString("DROP TABLE", $alert->title);
        $this->assertDatabaseHas('local_seo_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function sec_002_xss_in_message(): void
    {
        $user = \App\Models\User::factory()->create();
        $location = $this->createMockLocation(['user_id' => $user->id]);

        $alert = $this->service->createAlert(
            userId: $user->id,
            locationId: $location->id,
            type: 'ranking_dropped',
            severity: 'warning',
            title: 'Test',
            message: '<script>alert(1)</script>'
        );

        // The message should be stored as-is but escaped when displayed
        $this->assertStringContainsString('<script>', $alert->message);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    protected function createMockLocation(array $attributes = []): BusinessLocation
    {
        $user = \App\Models\User::factory()->create();
        
        $default = [
            'user_id' => $user->id,
            'name' => 'Test Business',
            'gbp_place_id' => 'accounts/123/locations/456',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'address' => '123 Main St, New York, NY 10001',
            'is_active' => true,
        ];

        return BusinessLocation::create(array_merge($default, $attributes));
    }
}
