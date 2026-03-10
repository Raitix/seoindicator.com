<?php

namespace Tests\Feature\Api\V1\LocalSEO;

use App\Models\BusinessLocation;
use App\Models\Site;
use App\Models\User;
use App\Services\LocalSEO\BusinessLocationService;
use App\Services\LocalSEO\Contracts\BusinessLocationServiceInterface;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
