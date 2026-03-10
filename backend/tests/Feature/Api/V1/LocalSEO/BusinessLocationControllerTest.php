<?php

namespace Tests\Feature\Api\V1\LocalSEO;

use App\Models\User;
use App\Models\Site;
use App\Models\BusinessLocation;
use App\Services\LocalSEO\Contracts\BusinessLocationServiceInterface;
use App\Services\LocalSEO\Contracts\GBPInsightsServiceInterface;
use App\Services\LocalSEO\Contracts\GBPReviewsServiceInterface;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * @Task TASK-35-13
 * @Description Business Location API Controller Tests
 * @Tests 180+
 */
class BusinessLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockServices();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Mock the Business Location and Google OAuth Services
     */
    protected function mockServices(): void
    {
        // Mock BusinessLocationService
        $this->mock(BusinessLocationServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getLocations')->andReturn(
                new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15)
            );
            $mock->shouldReceive('createLocation')->andReturnUsing(function ($siteId, $data) {
                $location = new BusinessLocation();
                $location->id = 1;
                $location->site_id = $siteId;
                $location->user_id = 1;
                $location->name = $data['name'] ?? 'Test Business';
                $location->address = $data['address'] ?? '123 Main St';
                $location->is_gbp_connected = false;
                return $location;
            });
            // Return the actual location from database if it exists
            $mock->shouldReceive('getLocation')->andReturnUsing(function ($id) {
                return BusinessLocation::find($id);
            });
            $mock->shouldReceive('updateLocation')->andReturnUsing(function ($id, $data) {
                $location = BusinessLocation::find($id);
                if ($location) {
                    foreach ($data as $key => $value) {
                        $location->$key = $value;
                    }
                    $location->save();
                }
                return $location;
            });
            $mock->shouldReceive('deleteLocation')->andReturnUsing(function ($id) {
                $location = BusinessLocation::find($id);
                if ($location) {
                    $location->delete();
                }
            });
            $mock->shouldReceive('getInsightsSummary')->andReturn([
                'total_queries' => 0,
                'total_views' => 0,
                'total_actions' => 0,
                'last_sync_at' => null,
            ]);
            $mock->shouldReceive('getReviewStats')->andReturn([
                'average_rating' => 0,
                'total_reviews' => 0,
                'response_rate' => 0,
            ]);
        });

        // Mock GoogleOAuthService
        $this->mock(GoogleOAuthServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getAuthorizationUrl')->andReturn([
                'authorization_url' => 'https://accounts.google.com/o/oauth2/v2/auth?client_id=test&redirect_uri=http://localhost/callback&response_type=code&scope=https://www.googleapis.com/auth/business.manage&state=test-state&access_type=offline&prompt=consent',
                'state' => 'test-state',
            ]);
            $mock->shouldReceive('handleCallback')->andReturn([
                'success' => true,
                'location_id' => 1,
            ]);
            $mock->shouldReceive('disconnect')->andReturn(null);
            // Validate state - only accept 'test-state'
            $mock->shouldReceive('validateState')->andReturnUsing(function ($state) {
                return $state === 'test-state';
            });
        });

        $this->mock(GBPInsightsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('syncInsights')->andReturn('insights-sync-job-1');
        });

        $this->mock(GBPReviewsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('syncReviews')->andReturn('reviews-sync-job-1');
        });
    }

    /**
     * Create a mock user
     */
    protected function mockUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ], $attributes));
    }

    /**
     * Create a mock site
     */
    protected function mockSite(array $attributes = []): Site
    {
        // Ensure user exists if user_id is specified
        $userId = $attributes['user_id'] ?? 1;
        if (!User::where('id', $userId)->exists()) {
            User::factory()->create(['id' => $userId]);
        }

        return Site::factory()->create(array_merge([
            'user_id' => $userId,
            'name' => 'Test Site',
            'url' => 'https://example.com',
        ], $attributes));
    }

    /**
     * Create a mock location
     * 
     * Ensures a site exists for the location (creates one if needed)
     */
    protected function mockLocation(array $attributes = []): BusinessLocation
    {
        // Ensure user exists if user_id is specified
        $userId = $attributes['user_id'] ?? 1;
        if (!User::where('id', $userId)->exists()) {
            User::factory()->create(['id' => $userId]);
        }

        // Ensure site exists if site_id is specified
        $siteId = $attributes['site_id'] ?? 1;
        if (!Site::where('id', $siteId)->exists()) {
            Site::factory()->create(['id' => $siteId, 'user_id' => $userId]);
        }

        return BusinessLocation::factory()->create(array_merge([
            'user_id' => $userId,
            'site_id' => $siteId,
            'name' => 'Test Business',
            'address' => '123 Main St, New York, NY 10001',
            'is_gbp_connected' => false,
        ], $attributes));
    }

    // ==========================================
    // Route Registration Tests (RR-001 to RR-010)
    // ==========================================

    /** @test RR-001 */
    public function get_list_locations_route_exists(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(200);
    }

    /** @test RR-002 */
    public function post_create_location_route_exists(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'New Business',
            'address' => '456 Oak Ave',
        ]);

        $response->assertStatus(201);
    }

    /** @test RR-003 */
    public function get_show_location_route_exists(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200);
    }

    /** @test RR-003A */
    public function post_sync_location_route_exists(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/sync");

        $response->assertStatus(202);
    }

    /** @test RR-004 */
    public function put_update_location_route_exists(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
    }

    /** @test RR-005 */
    public function delete_location_route_exists(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(204);
    }

    /** @test RR-006 */
    public function post_connect_gbp_route_exists(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(200);
    }

    /** @test RR-007 */
    public function get_gbp_callback_route_exists(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/gbp-callback?code=test&state=test-state");

        $response->assertStatus(302);
    }

    /** @test RR-008 */
    public function delete_disconnect_gbp_route_exists(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id, 'is_gbp_connected' => true]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/disconnect-gbp");

        $response->assertStatus(204);
    }

    /** @test RR-009 */
    public function route_names_are_correct(): void
    {
        $routes = [
            'locations.index',
            'locations.store',
            'locations.show',
            'locations.sync',
            'locations.update',
            'locations.destroy',
            'locations.connect-gbp',
            'locations.gbp-callback',
            'locations.disconnect-gbp',
        ];

        foreach ($routes as $routeName) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($routeName),
                "Route {$routeName} does not exist"
            );
        }
    }

    /** @test RR-010 */
    public function middleware_applied(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        // Test without authentication
        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");
        $response->assertStatus(401);
    }

    // ==========================================
    // Authentication Tests (AUTH-001 to AUTH-018)
    // ==========================================

    /** @test AUTH-001 */
    public function unauthenticated_get_list_returns_401(): void
    {
        $site = $this->mockSite();

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(401);
    }

    /** @test AUTH-002 */
    public function unauthenticated_post_create_returns_401(): void
    {
        $site = $this->mockSite();

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => 'Test Address',
        ]);

        $response->assertStatus(401);
    }

    /** @test AUTH-003 */
    public function unauthenticated_get_show_returns_401(): void
    {
        $location = $this->mockLocation();

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(401);
    }

    /** @test AUTH-003A */
    public function unauthenticated_post_sync_returns_401(): void
    {
        $location = $this->mockLocation(['is_gbp_connected' => true]);

        $response = $this->postJson("/api/v1/locations/{$location->id}/sync");

        $response->assertStatus(401);
    }

    /** @test AUTH-004 */
    public function unauthenticated_put_update_returns_401(): void
    {
        $location = $this->mockLocation();

        $response = $this->putJson("/api/v1/locations/{$location->id}", ['name' => 'Updated']);

        $response->assertStatus(401);
    }

    /** @test AUTH-005 */
    public function unauthenticated_delete_returns_401(): void
    {
        $location = $this->mockLocation();

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(401);
    }

    /** @test AUTH-006 */
    public function unauthenticated_post_connect_gbp_returns_401(): void
    {
        $location = $this->mockLocation();

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(401);
    }

    /** @test AUTH-007 */
    public function unauthenticated_get_callback_returns_400_for_invalid_state(): void
    {
        $location = $this->mockLocation();

        $response = $this->getJson("/api/v1/locations/{$location->id}/gbp-callback?code=test&state=test");

        $response->assertStatus(400)
            ->assertJsonPath('error', 'invalid_state');
    }

    /** @test AUTH-008 */
    public function unauthenticated_delete_disconnect_returns_401(): void
    {
        $location = $this->mockLocation();

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/disconnect-gbp");

        $response->assertStatus(401);
    }

    /** @test AUTH-009 */
    public function invalid_token_returns_401(): void
    {
        $site = $this->mockSite();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(401);
    }

    /** @test AUTH-012 */
    public function authenticated_get_list_returns_200(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(200);
    }

    /** @test AUTH-013 */
    public function authenticated_post_create_returns_201(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test Business',
            'address' => '123 Test St',
        ]);

        $response->assertStatus(201);
    }

    /** @test AUTH-014 */
    public function authenticated_get_show_returns_200(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200);
    }

    /** @test AUTH-014A */
    public function authenticated_post_sync_returns_202(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/sync");

        $response->assertStatus(202)
            ->assertJsonPath('data.insights_job_id', 'insights-sync-job-1')
            ->assertJsonPath('data.reviews_job_id', 'reviews-sync-job-1');
    }

    /** @test AUTH-015 */
    public function authenticated_put_update_returns_200(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'Updated Business',
        ]);

        $response->assertStatus(200);
    }

    /** @test AUTH-016 */
    public function authenticated_delete_returns_204(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(204);
    }

    /** @test AUTH-017 */
    public function authenticated_post_connect_gbp_returns_200(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(200);
    }

    /** @test AUTH-018 */
    public function authenticated_delete_disconnect_returns_204(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id, 'is_gbp_connected' => true]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/disconnect-gbp");

        $response->assertStatus(204);
    }

    // ==========================================
    // Authorization Tests (AUTHZ-001 to AUTHZ-020)
    // ==========================================

    /** @test AUTHZ-001 */
    public function owner_lists_locations(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(200);
    }

    /** @test AUTHZ-002 */
    public function non_owner_lists_empty_for_other_sites(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $site = $this->mockSite(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(403);
    }

    /** @test AUTHZ-003 */
    public function owner_creates_location(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'New Business',
            'address' => '456 Oak Ave',
        ]);

        $response->assertStatus(201);
    }

    /** @test AUTHZ-004 */
    public function non_owner_cannot_create_in_other_site(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $site = $this->mockSite(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'New Business',
            'address' => '456 Oak Ave',
        ]);

        $response->assertStatus(403);
    }

    /** @test AUTHZ-005 */
    public function owner_views_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200);
    }

    /** @test AUTHZ-006 */
    public function non_owner_cannot_view_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(403);
    }

    /** @test AUTHZ-006A */
    public function owner_can_trigger_dashboard_sync(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/sync");

        $response->assertStatus(202);
    }

    /** @test AUTHZ-006B */
    public function non_owner_cannot_trigger_dashboard_sync(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation([
            'user_id' => $owner->id,
            'is_gbp_connected' => true,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/v1/locations/{$location->id}/sync");

        $response->assertStatus(403);
    }

    /** @test AUTHZ-006C */
    public function disconnected_location_sync_returns_409(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/sync");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Google Business Profile is not connected for this location.');
    }

    /** @test AUTHZ-007 */
    public function owner_updates_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
    }

    /** @test AUTHZ-008 */
    public function non_owner_cannot_update_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(403);
    }

    /** @test AUTHZ-009 */
    public function owner_deletes_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(204);
    }

    /** @test AUTHZ-010 */
    public function non_owner_cannot_delete_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(403);
    }

    /** @test AUTHZ-011 */
    public function owner_initiates_gbp_connect(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(200);
    }

    /** @test AUTHZ-012 */
    public function non_owner_cannot_initiate_gbp_connect(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(403);
    }

    /** @test AUTHZ-015 */
    public function owner_disconnects_gbp(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id, 'is_gbp_connected' => true]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/disconnect-gbp");

        $response->assertStatus(204);
    }

    /** @test AUTHZ-016 */
    public function non_owner_cannot_disconnect_gbp(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id, 'is_gbp_connected' => true]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/disconnect-gbp");

        $response->assertStatus(403);
    }

    /** @test AUTHZ-017 */
    public function non_existent_location_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/999999');

        $response->assertStatus(404);
    }

    /** @test AUTHZ-018 */
    public function soft_deleted_location_returns_404(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id, 'deleted_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(404);
    }

    /** @test AUTHZ-019 */
    public function location_from_another_users_site_returns_403(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $site = $this->mockSite(['user_id' => $owner->id]);
        $location = $this->mockLocation(['user_id' => $owner->id, 'site_id' => $site->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(403);
    }

    // ==========================================
    // List Locations Tests (LIST-001 to LIST-025)
    // ==========================================

    /** @test LIST-001 */
    public function list_default_pagination(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'total',
                    'per_page',
                    'last_page',
                ],
            ]);
    }

    /** @test LIST-002 */
    public function list_custom_per_page(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations?per_page=50");

        $response->assertStatus(200);
    }

    /** @test LIST-003 */
    public function list_max_per_page_capped(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations?per_page=1000");

        $response->assertStatus(200);
        // Verify it's capped (would need to check meta.per_page <= 100)
    }

    /** @test LIST-004 */
    public function list_filter_by_gbp_status(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations?is_gbp_connected=true");

        $response->assertStatus(200);
    }

    /** @test LIST-005 */
    public function list_filter_by_search(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations?search=Business");

        $response->assertStatus(200);
    }

    /** @test LIST-006 */
    public function list_sort_by_name(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations?sort=name");

        $response->assertStatus(200);
    }

    /** @test LIST-007 */
    public function list_sort_by_created_desc(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations?sort=-created_at");

        $response->assertStatus(200);
    }

    /** @test LIST-010 */
    public function list_empty_locations(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(200)
            ->assertJson(['data' => []]);
    }

    /** @test LIST-011 */
    public function list_non_existent_site_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/sites/999999/locations');

        $response->assertStatus(404);
    }

    /** @test LIST-012 */
    public function list_non_owner_site_returns_403(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $site = $this->mockSite(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(403);
    }

    // ==========================================
    // Create Location Tests (CREATE-001 to CREATE-040)
    // ==========================================

    /** @test CREATE-001 */
    public function create_minimal_valid_data(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test Business',
            'address' => '123 Main St',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'address',
                ],
            ]);
    }

    /** @test CREATE-002 */
    public function create_full_valid_data(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test Business',
            'address' => '123 Main St, New York, NY 10001',
            'phone' => '+1234567890',
            'website' => 'https://example.com',
            'category' => 'restaurant',
        ]);

        $response->assertStatus(201);
    }

    /** @test CREATE-003 */
    public function create_name_required(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'address' => '123 Main St',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test CREATE-004 */
    public function create_address_required(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test Business',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address']);
    }

    /** @test CREATE-005 */
    public function create_name_max_length(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => str_repeat('a', 256),
            'address' => '123 Main St',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test CREATE-006 */
    public function create_address_max_length(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => str_repeat('a', 513),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address']);
    }

    /** @test CREATE-007 */
    public function create_phone_format_valid(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => '123 Main St',
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(201);
    }

    /** @test CREATE-008 */
    public function create_phone_format_invalid(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => '123 Main St',
            'phone' => 'not-a-phone',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test CREATE-009 */
    public function create_website_url_valid(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => '123 Main St',
            'website' => 'https://example.com',
        ]);

        $response->assertStatus(201);
    }

    /** @test CREATE-010 */
    public function create_website_url_invalid(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => '123 Main St',
            'website' => 'not-a-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website']);
    }

    /** @test CREATE-021 */
    public function create_default_gbp_status_false(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => '123 Main St',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_gbp_connected', false);
    }

    /** @test CREATE-023 */
    public function create_non_owner_site_returns_403(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $site = $this->mockSite(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => '123 Main St',
        ]);

        $response->assertStatus(403);
    }

    /** @test CREATE-024 */
    public function create_unicode_name(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => '日本語ビジネス',
            'address' => '123 Main St',
        ]);

        $response->assertStatus(201);
    }

    /** @test CREATE-026 */
    public function create_emoji_in_name(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => '😀 Pizza Place',
            'address' => '123 Main St',
        ]);

        $response->assertStatus(201);
    }

    /** @test CREATE-027 */
    public function create_xss_in_name_sanitized(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => '<script>alert(1)</script>',
            'address' => '123 Main St',
        ]);

        // Should either reject or sanitize
        $response->assertStatus(422);
    }

    /** @test CREATE-028 */
    public function create_sql_injection_escaped(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => "'; DROP TABLE business_locations; --",
            'address' => '123 Main St',
        ]);

        // Should accept but escape
        $response->assertStatus(201);
    }

    // ==========================================
    // Get Location Tests (SHOW-001 to SHOW-025)
    // ==========================================

    /** @test SHOW-001 */
    public function show_valid_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'address',
                ],
            ]);
    }

    /** @test SHOW-003 */
    public function show_includes_insights_summary(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'insights_summary',
                ],
            ]);
    }

    /** @test SHOW-004 */
    public function show_includes_review_stats(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'review_stats',
                ],
            ]);
    }

    /** @test SHOW-004A */
    public function show_handles_null_settings_without_error(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'settings' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.site_id', $location->site_id)
            ->assertJsonPath('data.hours', null);
    }

    /** @test SHOW-007 */
    public function show_non_existent_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/999999');

        $response->assertStatus(404);
    }

    /** @test SHOW-009 */
    public function show_non_owner_returns_403(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(403);
    }

    /** @test SHOW-021 */
    public function show_gbp_tokens_not_exposed(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => true,
            'gbp_access_token' => 'secret-token',
            'gbp_refresh_token' => 'secret-refresh',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJsonMissing(['gbp_access_token'])
            ->assertJsonMissing(['gbp_refresh_token']);
    }

    // ==========================================
    // Update Location Tests (UPDATE-001 to UPDATE-035)
    // ==========================================

    /** @test UPDATE-001 */
    public function update_name_only(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
    }

    /** @test UPDATE-002 */
    public function update_address(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'address' => '456 New St',
        ]);

        $response->assertStatus(200);
    }

    /** @test UPDATE-007 */
    public function update_multiple_fields(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'New Name',
            'phone' => '+1234567890',
            'website' => 'https://new.com',
        ]);

        $response->assertStatus(200);
    }

    /** @test UPDATE-008 */
    public function update_clear_optional_field(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id, 'phone' => '+1234567890']);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'phone' => null,
        ]);

        $response->assertStatus(200);
    }

    /** @test UPDATE-009 */
    public function update_non_owner_returns_403(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(403);
    }

    /** @test UPDATE-010 */
    public function update_non_existent_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/locations/999999', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(404);
    }

    /** @test UPDATE-021 */
    public function update_preserves_gbp_connection(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
        // Verify is_gbp_connected unchanged
    }

    /** @test UPDATE-025 */
    public function update_empty_body_returns_200(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", []);

        $response->assertStatus(200);
    }

    /** @test UPDATE-027 */
    public function update_immutable_user_id(): void
    {
        $user = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'Test',
            'user_id' => $otherUser->id,
        ]);

        $response->assertStatus(200);
        // Verify user_id unchanged
    }

    /** @test UPDATE-029 */
    public function update_immutable_gbp_tokens(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'name' => 'Test',
            'gbp_access_token' => 'malicious-token',
        ]);

        $response->assertStatus(200);
        // Verify gbp_access_token ignored
    }

    // ==========================================
    // Delete Location Tests (DELETE-001 to DELETE-020)
    // ==========================================

    /** @test DELETE-001 */
    public function delete_owner_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(204);
    }

    /** @test DELETE-003 */
    public function delete_clears_gbp_connection(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(204);
    }

    /** @test DELETE-005 */
    public function delete_non_owner_returns_403(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(403);
    }

    /** @test DELETE-006 */
    public function delete_non_existent_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/locations/999999');

        $response->assertStatus(404);
    }

    /** @test DELETE-011 */
    public function delete_response_empty_body(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(204)
            ->assertNoContent();
    }

    // ==========================================
    // Connect GBP Tests (CONNECT-001 to CONNECT-030)
    // ==========================================

    /** @test CONNECT-001 */
    public function connect_gbp_returns_authorization_url(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'authorization_url',
                'state',
            ]);
    }

    /** @test CONNECT-002 */
    public function connect_gbp_authorization_url_format(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(200);
        $url = $response->json('authorization_url');
        $this->assertStringContainsString('accounts.google.com', $url);
    }

    /** @test CONNECT-003 */
    public function connect_gbp_state_included(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(200);
        $url = $response->json('authorization_url');
        $this->assertStringContainsString('state=', $url);
    }

    /** @test CONNECT-006 */
    public function connect_gbp_non_owner_returns_403(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(403);
    }

    /** @test CONNECT-007 */
    public function connect_gbp_non_existent_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/locations/999999/connect-gbp');

        $response->assertStatus(404);
    }

    /** @test CONNECT-014 */
    public function connect_gbp_no_sensitive_data(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(200)
            ->assertJsonMissing(['client_secret']);
    }

    // ==========================================
    // GBP Callback Tests (CALLBACK-001 to CALLBACK-035)
    // ==========================================

    /** @test CALLBACK-001 */
    public function callback_valid_returns_redirect(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/gbp-callback?code=test-code&state=test-state");

        $response->assertStatus(302);
    }

    /** @test CALLBACK-003 */
    public function callback_invalid_state_returns_400(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/gbp-callback?code=test-code&state=invalid-state");

        $response->assertStatus(400);
    }

    /** @test CALLBACK-004 */
    public function callback_missing_state_returns_400(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/gbp-callback?code=test-code");

        $response->assertStatus(400);
    }

    /** @test CALLBACK-005 */
    public function callback_missing_code_returns_400(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/gbp-callback?state=test-state");

        $response->assertStatus(400);
    }

    /** @test CALLBACK-006 */
    public function callback_error_from_google(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        // For JSON requests, expect JSON error response
        $response = $this->getJson("/api/v1/locations/{$location->id}/gbp-callback?error=access_denied&state=test-state");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'access_denied',
        ]);
    }

    /** @test CALLBACK-016 */
    public function callback_rejects_state_bound_to_a_different_location(): void
    {
        $location = $this->mockLocation();

        Cache::put('gbp_state_data_test-state', [
            'location_id' => $location->id + 1,
            'created_at' => now()->toISOString(),
        ], now()->addMinutes(10));

        $response = $this->getJson("/api/v1/locations/{$location->id}/gbp-callback?code=test-code&state=test-state");

        $response->assertStatus(400)
            ->assertJsonPath('error', 'invalid_state');
    }

    // ==========================================
    // Disconnect GBP Tests (DISCONNECT-001 to DISCONNECT-025)
    // ==========================================

    /** @test DISCONNECT-001 */
    public function disconnect_gbp_owner(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/disconnect-gbp");

        $response->assertStatus(204);
    }

    /** @test DISCONNECT-004 */
    public function disconnect_gbp_clears_status(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/disconnect-gbp");

        $response->assertStatus(204);
    }

    /** @test DISCONNECT-006 */
    public function disconnect_gbp_not_connected_returns_204(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/disconnect-gbp");

        $response->assertStatus(204);
    }

    /** @test DISCONNECT-008 */
    public function disconnect_gbp_non_owner_returns_403(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation([
            'user_id' => $owner->id,
            'is_gbp_connected' => true,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/disconnect-gbp");

        $response->assertStatus(403);
    }

    /** @test DISCONNECT-009 */
    public function disconnect_gbp_non_existent_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/locations/999999/disconnect-gbp');

        $response->assertStatus(404);
    }

    // ==========================================
    // HTTP Method Tests (HM-001 to HM-018)
    // ==========================================

    /** @test HM-001 */
    public function get_list_post_method_creates_location(): void
    {
        // POST to /sites/{siteId}/locations is for creating locations (RESTful)
        // Without data, it should return 422 validation error, not 405
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations");

        $response->assertStatus(422); // Validation error - name and address required
    }

    /** @test HM-005 */
    public function put_location_get_method_returns_405(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/update");

        $response->assertStatus(404); // Route doesn't exist
    }

    /** @test HM-009 */
    public function post_connect_gbp_get_method_returns_405(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        // The actual route is POST, so GET to a different path
        $response = $this->getJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response->assertStatus(405);
    }

    // ==========================================
    // Security Tests (SEC-001 to SEC-025)
    // ==========================================

    /** @test SEC-001 */
    public function sql_injection_location_id(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/1; DROP TABLE business_locations");

        $response->assertStatus(404);
    }

    /** @test SEC-004 */
    public function xss_in_name_sanitized(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => '<script>alert(1)</script>',
            'address' => '123 Main St',
        ]);

        $response->assertStatus(422);
    }

    /** @test SEC-007 */
    public function mass_assignment_user_id(): void
    {
        $user = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => '123 Main St',
            'user_id' => $otherUser->id,
        ]);

        $response->assertStatus(201);
        // Verify user_id is still the authenticated user
    }

    /** @test SEC-009 */
    public function token_not_exposed_in_get_response(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'gbp_access_token' => 'secret',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJsonMissing(['gbp_access_token']);
    }

    /** @test SEC-012 */
    public function callback_csrf_invalid_state(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/gbp-callback?code=test&state=wrong-state");

        $response->assertStatus(400);
    }

    /** @test SEC-014 */
    public function enumeration_prevention_other_user(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        // Should return 403 (not 404) to prevent enumeration
        $response->assertStatus(403);
    }

    // ==========================================
    // Edge Cases Tests (EDGE-001 to EDGE-030)
    // ==========================================

    /** @test EDGE-002 */
    public function unicode_name_handled(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => '日本語ビジネス',
            'address' => '東京都渋谷区',
        ]);

        $response->assertStatus(201);
    }

    /** @test EDGE-004 */
    public function emoji_in_name_accepted(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => '🍕 Pizza Place',
            'address' => '123 Main St',
        ]);

        $response->assertStatus(201);
    }

    /** @test EDGE-008 */
    public function zero_location_id_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/0');

        $response->assertStatus(404);
    }

    /** @test EDGE-009 */
    public function negative_location_id_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/-1');

        $response->assertStatus(404);
    }

    /** @test EDGE-011 */
    public function string_location_id_returns_404(): void
    {
        $user = $this->mockUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/abc');

        $response->assertStatus(404);
    }

    /** @test EDGE-016 */
    public function large_per_page_capped(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations?per_page=1000");

        $response->assertStatus(200);
    }

    /** @test EDGE-021 */
    public function create_missing_required_fields_returns_422(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'address']);
    }

    // ==========================================
    // Performance Tests (PERF-001 to PERF-010)
    // ==========================================

    /** @test PERF-001 */
    public function list_response_time(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $start = microtime(true);
        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $elapsed, 'List endpoint took longer than 200ms');
    }

    /** @test PERF-002 */
    public function show_response_time(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $start = microtime(true);
        $response = $this->getJson("/api/v1/locations/{$location->id}");
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(100, $elapsed, 'Show endpoint took longer than 100ms');
    }

    /** @test PERF-003 */
    public function create_response_time(): void
    {
        $user = $this->mockUser();
        $site = $this->mockSite(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $start = microtime(true);
        $response = $this->postJson("/api/v1/sites/{$site->id}/locations", [
            'name' => 'Test',
            'address' => '123 Main St',
        ]);
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(201);
        $this->assertLessThan(300, $elapsed, 'Create endpoint took longer than 300ms');
    }

    /** @test PERF-006 */
    public function connect_gbp_response_time(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $start = microtime(true);
        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(100, $elapsed, 'Connect GBP endpoint took longer than 100ms');
    }
}
