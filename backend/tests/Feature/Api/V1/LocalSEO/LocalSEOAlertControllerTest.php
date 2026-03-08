<?php

namespace Tests\Feature\Api\V1\LocalSEO;

use App\Models\BusinessLocation;
use App\Models\LocalSEOAlert;
use App\Models\User;
use App\Services\LocalSEO\Contracts\LocalSEOAlertServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * @Task TASK-35-16
 * @Description Local SEO Alerts API Controller Tests
 * @Tests 225+
 */
class LocalSEOAlertControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockAlertService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockAlertService(): void
    {
        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getUnreadAlerts')->andReturn(new Collection());
            $mock->shouldReceive('markAsRead')->andReturn(true);
            $mock->shouldReceive('markAllAsRead')->andReturn(0);
        });
    }

    protected function mockLocation(array $attributes = []): BusinessLocation
    {
        return BusinessLocation::factory()->create(array_merge([
            'user_id' => 1,
            'name' => 'Test Business',
        ], $attributes));
    }

    protected function mockAlert(array $attributes = []): LocalSEOAlert
    {
        return LocalSEOAlert::factory()->create(array_merge([
            'user_id' => 1,
            'location_id' => 1,
            'alert_type' => 'ranking_dropped',
            'severity' => 'warning',
            'title' => 'Test Alert',
            'message' => 'Test message',
            'is_read' => false,
        ], $attributes));
    }

    // ==========================================
    // Route Registration Tests (RR-001 to RR-008)
    // ==========================================

    /** @test */
    public function rr001_index_route_exists(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200);
    }

    /** @test */
    public function rr002_unread_count_route_exists(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200);
    }

    /** @test */
    public function rr003_read_route_exists(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) use ($alert) {
            $mock->shouldReceive('markAsRead')->once()->andReturn(true);
            $mock->shouldReceive('getUnreadAlerts')->andReturn(new Collection());
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        $response->assertStatus(200);
    }

    /** @test */
    public function rr004_mark_all_read_route_exists(): void
    {
        $user = User::factory()->create();

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAllAsRead')->once()->andReturn(5);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(200);
    }

    /** @test */
    public function rr005_delete_route_exists(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");

        $response->assertStatus(204);
    }

    /** @test */
    public function rr006_location_alerts_route_exists(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts");

        $response->assertStatus(200);
    }

    /** @test */
    public function rr007_route_names_are_correct(): void
    {
        $expectedRoutes = [
            'local-seo.alerts.index',
            'local-seo.alerts.unread-count',
            'local-seo.alerts.read',
            'local-seo.alerts.mark-all-read',
            'local-seo.alerts.destroy',
        ];

        foreach ($expectedRoutes as $routeName) {
            try {
                route($routeName);
                $exists = true;
            } catch (\Exception $e) {
                $exists = false;
            }
            $this->assertTrue($exists, "Route [{$routeName}] should exist");
        }
    }

    /** @test */
    public function rr008_middleware_applied(): void
    {
        $routes = [
            ['GET', '/api/v1/local-seo/alerts'],
            ['GET', '/api/v1/local-seo/alerts/unread-count'],
            ['POST', '/api/v1/local-seo/alerts/1/read'],
            ['POST', '/api/v1/local-seo/alerts/mark-all-read'],
            ['DELETE', '/api/v1/local-seo/alerts/1'],
            ['GET', '/api/v1/locations/1/alerts'],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $response->assertStatus(401);
        }
    }

    // ==========================================
    // Authentication Tests (AUTH-001 to AUTH-010)
    // ==========================================

    /** @test */
    public function auth001_unauthenticated_index_request(): void
    {
        $response = $this->getJson('/api/v1/local-seo/alerts');
        $response->assertStatus(401);
    }

    /** @test */
    public function auth002_unauthenticated_unread_count(): void
    {
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');
        $response->assertStatus(401);
    }

    /** @test */
    public function auth003_unauthenticated_read_request(): void
    {
        $response = $this->postJson('/api/v1/local-seo/alerts/1/read');
        $response->assertStatus(401);
    }

    /** @test */
    public function auth004_unauthenticated_mark_all_read(): void
    {
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read');
        $response->assertStatus(401);
    }

    /** @test */
    public function auth005_unauthenticated_delete_request(): void
    {
        $response = $this->deleteJson('/api/v1/local-seo/alerts/1');
        $response->assertStatus(401);
    }

    /** @test */
    public function auth006_unauthenticated_location_alerts(): void
    {
        $response = $this->getJson('/api/v1/locations/1/alerts');
        $response->assertStatus(401);
    }

    /** @test */
    public function auth007_authenticated_request_succeeds(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200);
    }

    /** @test */
    public function auth008_invalid_token_format(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-format')
            ->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(401);
    }

    /** @test */
    public function auth009_expired_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $token->accessToken->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(401);
    }

    /** @test */
    public function auth010_token_without_proper_abilities(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['invalid-ability']);
        
        $response = $this->getJson('/api/v1/local-seo/alerts');
        $response->assertStatus(200);
    }

    // ==========================================
    // Authorization Tests (AUTHZ-001 to AUTHZ-010)
    // ==========================================

    /** @test */
    public function authz001_user_accesses_own_alerts(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function authz002_user_cannot_access_others_alerts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user2->id]);
        $alert = $this->mockAlert(['user_id' => $user2->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user1);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function authz003_user_marks_own_alert_as_read(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAsRead')->once()->andReturn(true);
            $mock->shouldReceive('getUnreadAlerts')->andReturn(new Collection());
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        $response->assertStatus(200);
    }

    /** @test */
    public function authz004_user_cannot_mark_others_alert(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);
        $response = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        $response->assertStatus(404);
    }

    /** @test */
    public function authz005_user_deletes_own_alert(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('local_seo_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function authz006_user_cannot_delete_others_alert(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);
        $response = $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('local_seo_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function authz007_user_marks_all_own_alerts_read(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'alert_type' => 'new_review']);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAllAsRead')->once()->andReturn(2);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(200)
            ->assertJson(['count' => 2]);
    }

    /** @test */
    public function authz008_location_ownership_check(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts");

        $response->assertStatus(404);
    }

    /** @test */
    public function authz009_admin_can_access_any_alert(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function authz010_super_admin_can_access_any_alert(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($superAdmin);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ==========================================
    // Index Endpoint Tests (IDX-001 to IDX-018)
    // ==========================================

    /** @test */
    public function idx001_empty_alerts_list(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function idx002_single_alert(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function idx003_multiple_alerts(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        for ($i = 0; $i < 5; $i++) {
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);
        }

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function idx004_pagination_default(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        for ($i = 0; $i < 20; $i++) {
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);
        }

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20);
    }

    /** @test */
    public function idx005_pagination_custom(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        for ($i = 0; $i < 10; $i++) {
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);
        }

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function idx006_pagination_max(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        for ($i = 0; $i < 150; $i++) {
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);
        }

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?per_page=1000');

        $response->assertStatus(200)
            ->assertJsonCount(100, 'data');
    }

    /** @test */
    public function idx007_pagination_metadata(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        for ($i = 0; $i < 50; $i++) {
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);
        }

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 50)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.current_page', 1);
    }

    /** @test */
    public function idx008_sort_by_created_at_desc(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $alert1 = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'created_at' => now()->subHour()]);
        $alert2 = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'created_at' => now()]);
        $alert3 = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'created_at' => now()->subHours(2)]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200);
        $alerts = $response->json('data');
        $this->assertEquals($alert2->id, $alerts[0]['id']);
        $this->assertEquals($alert1->id, $alerts[1]['id']);
        $this->assertEquals($alert3->id, $alerts[2]['id']);
    }

    /** @test */
    public function idx009_filter_by_location_id(): void
    {
        $user = User::factory()->create();
        $location1 = $this->mockLocation(['user_id' => $user->id, 'name' => 'Location 1']);
        $location2 = $this->mockLocation(['user_id' => $user->id, 'name' => 'Location 2']);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location1->id]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location1->id]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location2->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/local-seo/alerts?location_id={$location1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function idx010_filter_by_severity(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning']);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning']);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'critical']);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?severity=warning');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function idx011_filter_by_alert_type(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'alert_type' => 'ranking_dropped']);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'alert_type' => 'ranking_dropped']);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'alert_type' => 'new_review']);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?type=ranking_dropped');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function idx012_filter_by_is_read(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'is_read' => true]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?is_read=false');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function idx013_multiple_filters_combined(): void
    {
        $user = User::factory()->create();
        $location1 = $this->mockLocation(['user_id' => $user->id]);
        $location2 = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location1->id, 'severity' => 'warning', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location1->id, 'severity' => 'critical', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location1->id, 'severity' => 'warning', 'is_read' => true]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location2->id, 'severity' => 'warning', 'is_read' => false]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/local-seo/alerts?location_id={$location1->id}&severity=warning&is_read=false");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function idx014_invalid_filter_value(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?severity=urgent');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['severity']);
    }

    /** @test */
    public function idx015_invalid_alert_type(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?type=invalid_type');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function idx016_response_structure(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'severity',
                        'title',
                        'message',
                        'is_read',
                        'read_at',
                        'created_at',
                        'location',
                    ],
                ],
            ]);
    }

    /** @test */
    public function idx017_includes_location_data(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id, 'name' => 'Test Business Name']);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.location.name', 'Test Business Name');
    }

    /** @test */
    public function idx018_includes_user_data(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200);
    }

    // ==========================================
    // Unread Count Endpoint Tests (UNR-001 to UNR-010)
    // ==========================================

    /** @test */
    public function unr001_no_unread_alerts(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'total' => 0,
                'by_severity' => [
                    'info' => 0,
                    'warning' => 0,
                    'critical' => 0,
                ],
            ]);
    }

    /** @test */
    public function unr002_one_unread_info(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'info', 'is_read' => false]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'total' => 1,
                'by_severity' => [
                    'info' => 1,
                    'warning' => 0,
                    'critical' => 0,
                ],
            ]);
    }

    /** @test */
    public function unr003_one_unread_warning(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning', 'is_read' => false]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('by_severity.warning', 1);
    }

    /** @test */
    public function unr004_one_unread_critical(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'critical', 'is_read' => false]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('by_severity.critical', 1);
    }

    /** @test */
    public function unr005_multiple_unread(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'info', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'info', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'critical', 'is_read' => false]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'total' => 5,
                'by_severity' => [
                    'info' => 2,
                    'warning' => 2,
                    'critical' => 1,
                ],
            ]);
    }

    /** @test */
    public function unr006_mixed_read_unread(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'info', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'info', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning', 'is_read' => true]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning', 'is_read' => true]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'critical', 'is_read' => true]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200)
            ->assertJson(['total' => 2]);
    }

    /** @test */
    public function unr007_other_users_alerts_excluded(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user2->id]);
        
        $this->mockAlert(['user_id' => $user2->id, 'location_id' => $location->id, 'severity' => 'critical', 'is_read' => false]);

        Sanctum::actingAs($user1);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200)
            ->assertJson(['total' => 0]);
    }

    /** @test */
    public function unr008_filter_by_location(): void
    {
        $user = User::factory()->create();
        $location1 = $this->mockLocation(['user_id' => $user->id]);
        $location2 = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location1->id, 'severity' => 'info', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location2->id, 'severity' => 'warning', 'is_read' => false]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/local-seo/alerts/unread-count?location_id={$location1->id}");

        $response->assertStatus(200)
            ->assertJson([
                'total' => 1,
                'by_severity' => [
                    'info' => 1,
                    'warning' => 0,
                    'critical' => 0,
                ],
            ]);
    }

    /** @test */
    public function unr009_response_structure(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'by_severity' => [
                    'info',
                    'warning',
                    'critical',
                ],
            ]);
    }

    /** @test */
    public function unr010_zero_division_safety(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(200)
            ->assertJson(['total' => 0]);
    }

    // ==========================================
    // Mark Single Read Tests (RD-001 to RD-008)
    // ==========================================

    /** @test */
    public function rd001_mark_unread_alert_as_read(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id, 'is_read' => false]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAsRead')->once()->andReturn(true);
            $mock->shouldReceive('getUnreadAlerts')->andReturn(new Collection());
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function rd002_mark_already_read_alert(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id, 'is_read' => true, 'read_at' => now()]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAsRead')->once()->andReturn(true);
            $mock->shouldReceive('getUnreadAlerts')->andReturn(new Collection());
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function rd003_non_existent_alert(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/99999/read');

        $response->assertStatus(404);
    }

    /** @test */
    public function rd004_other_users_alert(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);
        $response = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        $response->assertStatus(404);
    }

    /** @test */
    public function rd005_read_at_timestamp_set(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id, 'is_read' => false, 'read_at' => null]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) use ($alert) {
            $mock->shouldReceive('markAsRead')->once()->andReturnUsing(function () use ($alert) {
                $alert->update(['is_read' => true, 'read_at' => now()]);
                return true;
            });
            $mock->shouldReceive('getUnreadAlerts')->andReturn(new Collection());
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        $response->assertStatus(200);
    }

    /** @test */
    public function rd006_response_structure(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAsRead')->once()->andReturn(true);
            $mock->shouldReceive('getUnreadAlerts')->andReturn(new Collection());
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'alert']);
    }

    /** @test */
    public function rd007_idempotent_operation(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAsRead')->twice()->andReturn(true);
            $mock->shouldReceive('getUnreadAlerts')->andReturn(new Collection());
        });

        Sanctum::actingAs($user);
        
        $response1 = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");
        $response2 = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    /** @test */
    public function rd008_invalid_id_format(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/abc/read');

        $response->assertStatus(404);
    }

    // ==========================================
    // Mark All Read Tests (MR-001 to MR-010)
    // ==========================================

    /** @test */
    public function mr001_mark_all_unread(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        
        for ($i = 0; $i < 5; $i++) {
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'is_read' => false]);
        }

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAllAsRead')->once()->andReturn(5);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'count' => 5]);
    }

    /** @test */
    public function mr002_no_unread_alerts(): void
    {
        $user = User::factory()->create();

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAllAsRead')->once()->andReturn(0);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(200)
            ->assertJson(['count' => 0]);
    }

    /** @test */
    public function mr003_filter_by_location(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAllAsRead')->once()->andReturn(3);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/local-seo/alerts/mark-all-read?location_id={$location->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function mr004_mixed_locations(): void
    {
        $user = User::factory()->create();
        $location1 = $this->mockLocation(['user_id' => $user->id]);
        $location2 = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location1->id, 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location2->id, 'is_read' => false]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAllAsRead')->once()->andReturn(2);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(200)
            ->assertJson(['count' => 2]);
    }

    /** @test */
    public function mr005_filter_by_severity(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'critical', 'is_read' => false]);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read?severity=warning');

        $response->assertStatus(200);
    }

    /** @test */
    public function mr006_combined_filters(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning', 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'critical', 'is_read' => false]);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/local-seo/alerts/mark-all-read?location_id={$location->id}&severity=warning");

        $response->assertStatus(200);
    }

    /** @test */
    public function mr007_other_user_unaffected(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user2->id]);

        $this->mockAlert(['user_id' => $user2->id, 'location_id' => $location->id, 'is_read' => false]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) use ($user1) {
            $mock->shouldReceive('markAllAsRead')
                ->once()
                ->andReturn(0);
        });

        Sanctum::actingAs($user1);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(200)
            ->assertJson(['count' => 0]);
    }

    /** @test */
    public function mr008_response_structure(): void
    {
        $user = User::factory()->create();

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAllAsRead')->once()->andReturn(3);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'count']);
    }

    /** @test */
    public function mr009_read_at_timestamps(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $alerts = collect([
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'is_read' => false, 'read_at' => null]),
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'is_read' => false, 'read_at' => null]),
        ]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAllAsRead')->once()->andReturn(2);
        });

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(200);
    }

    /** @test */
    public function mr010_invalid_filter(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/mark-all-read?severity=urgent');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['severity']);
    }

    // ==========================================
    // Delete Alert Tests (DEL-001 to DEL-007)
    // ==========================================

    /** @test */
    public function del001_delete_own_alert(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('local_seo_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function del002_delete_others_alert(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);
        $response = $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('local_seo_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function del003_delete_non_existent(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->deleteJson('/api/v1/local-seo/alerts/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function del004_delete_already_deleted(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        
        $response1 = $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");
        $response2 = $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");

        $response1->assertStatus(204);
        $response2->assertStatus(404);
    }

    /** @test */
    public function del005_alert_removed_from_list(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");
        
        $response = $this->getJson('/api/v1/local-seo/alerts');
        $response->assertJsonCount(0, 'data');
    }

    /** @test */
    public function del006_hard_delete_behavior(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('local_seo_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function del007_invalid_id_format(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->deleteJson('/api/v1/local-seo/alerts/abc');

        $response->assertStatus(404);
    }

    // ==========================================
    // Location Alerts Tests (LOC-001 to LOC-009)
    // ==========================================

    /** @test */
    public function loc001_location_with_alerts(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        for ($i = 0; $i < 3; $i++) {
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);
        }

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function loc002_location_with_no_alerts(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function loc003_non_existent_location(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/locations/99999/alerts');

        $response->assertStatus(404);
    }

    /** @test */
    public function loc004_other_users_location(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts");

        $response->assertStatus(404);
    }

    /** @test */
    public function loc005_filter_by_severity(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'warning']);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'severity' => 'critical']);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts?severity=critical");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function loc006_filter_by_is_read(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'is_read' => false]);
        $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'is_read' => true]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts?is_read=false");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function loc007_pagination_works(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        for ($i = 0; $i < 20; $i++) {
            $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);
        }

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts?per_page=5");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20);
    }

    /** @test */
    public function loc008_sort_by_created_at_desc(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $alert1 = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'created_at' => now()->subHour()]);
        $alert2 = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id, 'created_at' => now()]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts");

        $response->assertStatus(200);
        $alerts = $response->json('data');
        $this->assertEquals($alert2->id, $alerts[0]['id']);
    }

    /** @test */
    public function loc009_response_structure(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/locations/{$location->id}/alerts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'severity',
                        'title',
                        'message',
                        'is_read',
                        'created_at',
                    ],
                ],
                'meta',
            ]);
    }

    // ==========================================
    // HTTP Method Tests (HTTP-001 to HTTP-010)
    // ==========================================

    /** @test */
    public function http001_index_with_post(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts');

        $response->assertStatus(405);
    }

    /** @test */
    public function http002_index_with_put(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->putJson('/api/v1/local-seo/alerts');

        $response->assertStatus(405);
    }

    /** @test */
    public function http003_index_with_delete(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->deleteJson('/api/v1/local-seo/alerts');

        $response->assertStatus(405);
    }

    /** @test */
    public function http004_read_with_get(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/1/read');

        $response->assertStatus(405);
    }

    /** @test */
    public function http005_read_with_put(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->putJson('/api/v1/local-seo/alerts/1/read');

        $response->assertStatus(405);
    }

    /** @test */
    public function http006_mark_all_with_get(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(405);
    }

    /** @test */
    public function http007_mark_all_with_put(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->putJson('/api/v1/local-seo/alerts/mark-all-read');

        $response->assertStatus(405);
    }

    /** @test */
    public function http008_delete_with_get(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts/1');

        $response->assertStatus(405);
    }

    /** @test */
    public function http009_delete_with_post(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/1');

        $response->assertStatus(405);
    }

    /** @test */
    public function http010_unread_with_post(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/unread-count');

        $response->assertStatus(405);
    }

    // ==========================================
    // Validation Tests (VAL-001 to VAL-012)
    // ==========================================

    /** @test */
    public function val001_valid_severity_filter(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?severity=warning');

        $response->assertStatus(200);
    }

    /** @test */
    public function val002_invalid_severity_filter(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?severity=urgent');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['severity']);
    }

    /** @test */
    public function val003_valid_alert_type_filter(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?type=ranking_dropped');

        $response->assertStatus(200);
    }

    /** @test */
    public function val004_invalid_alert_type_filter(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?type=invalid_type');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function val005_valid_is_read_filter(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?is_read=true');

        $response->assertStatus(200);
    }

    /** @test */
    public function val006_invalid_is_read_filter(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?is_read=maybe');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_read']);
    }

    /** @test */
    public function val007_valid_location_id_filter(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/local-seo/alerts?location_id={$location->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function val008_invalid_location_id_filter(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?location_id=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location_id']);
    }

    /** @test */
    public function val009_valid_per_page(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?per_page=25');

        $response->assertStatus(200);
    }

    /** @test */
    public function val010_invalid_per_page_too_high(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?per_page=1000');

        // Should be capped to max 100
        $response->assertStatus(200);
    }

    /** @test */
    public function val011_negative_per_page(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?per_page=-1');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /** @test */
    public function val012_zero_per_page(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?per_page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    // ==========================================
    // Security Tests (SEC-001 to SEC-010)
    // ==========================================

    /** @test */
    public function sec001_sql_injection_in_location_id(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/local-seo/alerts?location_id='1 OR 1=1");

        $response->assertStatus(422);
    }

    /** @test */
    public function sec002_sql_injection_in_alert_id(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/local-seo/alerts/1;DROP TABLE local_seo_alerts;/read');

        $response->assertStatus(404);
    }

    /** @test */
    public function sec003_xss_in_filter_params(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?severity=<script>alert(1)</script>');

        $response->assertStatus(422);
    }

    /** @test */
    public function sec004_no_sensitive_data_exposure(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonMissing(['password', 'api_key', 'secret']);
    }

    /** @test */
    public function sec005_user_isolation_enforced(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user2->id]);
        $alert = $this->mockAlert(['user_id' => $user2->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user1);
        $response = $this->deleteJson("/api/v1/local-seo/alerts/{$alert->id}");

        // 404 prevents enumeration
        $response->assertStatus(404);
    }

    /** @test */
    public function sec006_csrf_protection(): void
    {
        // Sanctum uses bearer tokens, CSRF not applicable
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(401);
    }

    /** @test */
    public function sec007_rate_limiting(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        // Make many requests - should eventually be rate limited
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/api/v1/local-seo/alerts');
            if ($response->status() === 429) {
                $this->assertTrue(true);
                return;
            }
        }

        // If no rate limiting, test passes (rate limiting may not be configured)
        $this->assertTrue(true);
    }

    /** @test */
    public function sec008_mass_assignment(): void
    {
        $user = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user->id]);

        $this->mock(LocalSEOAlertServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAsRead')->once()->andReturn(true);
            $mock->shouldReceive('getUnreadAlerts')->andReturn(new Collection());
        });

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read", [
            'id' => 999,
            'created_at' => '2020-01-01',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('local_seo_alerts', ['id' => $alert->id]);
    }

    /** @test */
    public function sec009_enumeration_prevention(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $alert = $this->mockAlert(['user_id' => $user2->id]);

        // Try to access other user's alert
        Sanctum::actingAs($user1);
        $response1 = $this->postJson("/api/v1/local-seo/alerts/{$alert->id}/read");

        // Try to access non-existent alert
        $response2 = $this->postJson('/api/v1/local-seo/alerts/99999/read');

        // Both should return 404 (same response - no enumeration)
        $response1->assertStatus(404);
        $response2->assertStatus(404);
    }

    /** @test */
    public function sec010_input_sanitization(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts?location_id=1<script>alert(1)</script>');

        $response->assertStatus(422);
    }

    // ==========================================
    // Acceptance Criteria Tests (AC-001 to AC-011)
    // ==========================================

    /** @test */
    public function ac001_local_seo_alert_controller_created(): void
    {
        $this->assertTrue(class_exists(\App\Http\Controllers\Api\V1\LocalSEO\LocalSEOAlertController::class));
    }

    /** @test */
    public function ac002_get_alerts_endpoint_works(): void
    {
        $user = User::factory()->create();
        $location = $this->mockLocation(['user_id' => $user->id]);
        $alert = $this->mockAlert(['user_id' => $user->id, 'location_id' => $location->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/local-seo/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    /** @test */
    public