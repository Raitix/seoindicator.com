<?php

namespace Tests\Feature\Api\V1\LocalSEO;

use App\Models\BusinessLocation;
use App\Models\Site;
use App\Models\User;
use App\Services\LocalSEO\BusinessLocationService;
use App\Services\LocalSEO\GoogleOAuthService;
use App\Services\LocalSEO\Contracts\BusinessLocationServiceInterface;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class BusinessLocationControllerProductionRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(BusinessLocationServiceInterface::class, BusinessLocationService::class);
        $this->app->instance(
            GoogleOAuthServiceInterface::class,
            Mockery::mock(GoogleOAuthServiceInterface::class)
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function bindRealGoogleOAuthServiceWithoutCredentials(): void
    {
        config()->set('services.google.client_id', null);
        config()->set('services.google.client_secret', null);
        config()->set('services.google.redirect', null);

        $this->app->forgetInstance(GoogleOAuthServiceInterface::class);
        $this->app->bind(GoogleOAuthServiceInterface::class, GoogleOAuthService::class);
    }

    /** @test */
    public function locations_index_returns_200_without_sort_query_and_uses_real_service(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        BusinessLocation::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'name' => 'Primary Location',
            'address' => '123 Main Street',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Primary Location');
    }

    /** @test */
    public function locations_index_still_returns_200_when_google_oauth_credentials_are_missing(): void
    {
        $this->bindRealGoogleOAuthServiceWithoutCredentials();

        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        BusinessLocation::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'name' => 'Credentialless Location',
            'address' => '456 Fallback Avenue',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Credentialless Location');
    }

    /** @test */
    public function locations_index_returns_404_for_missing_site_instead_of_500(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/sites/999999/locations');

        $response->assertNotFound();
    }

    /** @test */
    public function locations_index_returns_403_for_non_owner_instead_of_500(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations");

        $response->assertForbidden();
    }

    /** @test */
    public function locations_index_honors_search_and_false_connection_filter(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        BusinessLocation::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'name' => 'Alpha Bakery',
            'address' => '123 Main Street',
            'gbp_connected' => false,
        ]);

        BusinessLocation::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'name' => 'Bravo Bakery',
            'address' => '456 Main Street',
            'gbp_connected' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations?search=Alpha&is_gbp_connected=false");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Bakery')
            ->assertJsonPath('data.0.is_gbp_connected', false);
    }

    /** @test */
    public function locations_index_maps_connection_sort_alias_to_real_column(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        BusinessLocation::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'name' => 'Disconnected Location',
            'gbp_connected' => false,
        ]);

        BusinessLocation::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'name' => 'Connected Location',
            'gbp_connected' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/locations?sort=-is_gbp_connected");

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Connected Location')
            ->assertJsonPath('data.1.name', 'Disconnected Location');
    }

    /** @test */
    public function locations_show_returns_404_for_missing_location_instead_of_500(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/locations/999999');

        $response->assertNotFound();
    }

    /** @test */
    public function connect_gbp_returns_404_for_missing_location_instead_of_500(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/locations/999999/connect-gbp');

        $response->assertNotFound();
    }

    /** @test */
    public function connect_gbp_returns_503_when_google_oauth_credentials_are_missing_instead_of_500(): void
    {
        $this->bindRealGoogleOAuthServiceWithoutCredentials();

        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $location = BusinessLocation::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/connect-gbp");

        $response
            ->assertStatus(503)
            ->assertJsonPath('message', 'Google Business Profile OAuth is not configured.');
    }

    /** @test */
    public function gbp_callback_returns_popup_bridge_html_without_auth_when_state_matches_location(): void
    {
        $location = BusinessLocation::factory()->create();

        Cache::put('gbp_state_data_test-state', [
            'location_id' => $location->id,
            'created_at' => now()->toISOString(),
        ], now()->addMinutes(10));

        $oauthService = $this->app->make(GoogleOAuthServiceInterface::class);
        $oauthService->shouldReceive('handleCallback')
            ->once()
            ->with('oauth-code', 'test-state')
            ->andReturn([
                'success' => true,
                'location_id' => $location->id,
            ]);
        $oauthService->shouldNotReceive('validateState');

        $response = $this->withHeaders(['Accept' => 'text/html'])
            ->get("/api/v1/locations/{$location->id}/gbp-callback?code=oauth-code&state=test-state");

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('gbp_oauth_callback', false)
            ->assertSee('window.opener.postMessage', false)
            ->assertSee('Connection complete. You can close this window.');
    }

    /** @test */
    public function gbp_callback_returns_400_bridge_for_state_location_mismatch_instead_of_500(): void
    {
        $location = BusinessLocation::factory()->create();

        Cache::put('gbp_state_data_test-state', [
            'location_id' => $location->id + 1,
            'created_at' => now()->toISOString(),
        ], now()->addMinutes(10));

        $oauthService = $this->app->make(GoogleOAuthServiceInterface::class);
        $oauthService->shouldNotReceive('handleCallback');
        $oauthService->shouldNotReceive('validateState');

        $response = $this->withHeaders(['Accept' => 'text/html'])
            ->get("/api/v1/locations/{$location->id}/gbp-callback?code=oauth-code&state=test-state");

        $response
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('Google Business Profile connection failed')
            ->assertSee('Invalid state parameter');
    }
}
