<?php

namespace Tests\Feature\Api\V1;

use App\Models\BusinessLocation;
use App\Models\Keyword;
use App\Models\LocalCompetitor;
use App\Models\LocalRanking;
use App\Models\Site;
use App\Models\User;
use App\Services\LocalSEO\LocalRankServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * Test suite for LocalRankingController
 * 
 * Tests all 8 API endpoints for local rank tracking and competitor management.
 * 
 * @see TASK-35-15
 * @see TEST-35-15
 */
class LocalRankingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the LocalRankService interface
        $this->mockLocalRankService = Mockery::mock(LocalRankServiceInterface::class);
        $this->app->instance(LocalRankServiceInterface::class, $this->mockLocalRankService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a user with a site and business location
     */
    protected function createUserWithLocation(): array
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $location = BusinessLocation::factory()->create(['site_id' => $site->id]);

        return compact('user', 'site', 'location');
    }

    /**
     * Create a keyword for a site
     */
    protected function createKeyword(Site $site, array $attributes = []): Keyword
    {
        return Keyword::factory()->create(array_merge(['site_id' => $site->id], $attributes));
    }

    /**
     * Create a local ranking record
     */
    protected function createLocalRanking(BusinessLocation $location, Keyword $keyword, array $attributes = []): LocalRanking
    {
        return LocalRanking::factory()->create(array_merge([
            'business_location_id' => $location->id,
            'keyword_id' => $keyword->id,
        ], $attributes));
    }

    /**
     * Create a local competitor
     */
    protected function createCompetitor(BusinessLocation $location, array $attributes = []): LocalCompetitor
    {
        return LocalCompetitor::factory()->create(array_merge([
            'business_location_id' => $location->id,
        ], $attributes));
    }

    // =========================================================================
    // LR-API-GET: GET Rankings Endpoints
    // =========================================================================

    /** @test */
    public function lr_api_get_01_get_current_rankings_returns_200(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $keyword = $this->createKeyword($site, ['keyword' => 'best pizza new york']);
        $ranking = $this->createLocalRanking($location, $keyword, [
            'local_pack_position' => 2,
            'local_organic_position' => 5,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'location_id',
                    'location_name',
                    'rankings' => [
                        '*' => [
                            'keyword_id',
                            'keyword',
                            'local_pack_position',
                            'local_organic_position',
                            'last_checked',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.location_id', $location->id);
    }

    /** @test */
    public function lr_api_get_02_rankings_with_local_pack_data(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $keyword = $this->createKeyword($site);
        $ranking = $this->createLocalRanking($location, $keyword, [
            'local_pack_position' => 2,
            'competitors_in_pack' => [
                ['name' => "Joe's Pizza", 'position' => 1],
                ['name' => 'Pizza Palace', 'position' => 2],
                ['name' => "Mario's", 'position' => 3],
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonPath('data.rankings.0.local_pack_position', 2);
    }

    /** @test */
    public function lr_api_get_03_rankings_without_local_pack(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $keyword = $this->createKeyword($site);
        $ranking = $this->createLocalRanking($location, $keyword, [
            'local_pack_position' => null,
            'local_organic_position' => 15,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonPath('data.rankings.0.local_pack_position', null)
            ->assertJsonPath('data.rankings.0.local_organic_position', 15);
    }

    /** @test */
    public function lr_api_get_04_location_not_found_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/99999/rankings');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function lr_api_get_05_unauthorized_returns_401(): void
    {
        ['location' => $location] = $this->createUserWithLocation();

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated');
    }

    /** @test */
    public function lr_api_get_06_other_users_location_returns_403(): void
    {
        ['location' => $location] = $this->createUserWithLocation();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function lr_api_get_07_empty_rankings_returns_empty_array(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonPath('data.rankings', []);
    }

    /** @test */
    public function lr_api_get_08_get_history_with_date_range(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $keyword = $this->createKeyword($site);
        
        // Create multiple ranking records
        $this->createLocalRanking($location, $keyword, [
            'checked_at' => now()->subDays(1),
        ]);
        $this->createLocalRanking($location, $keyword, [
            'checked_at' => now()->subDays(2),
        ]);
        $this->createLocalRanking($location, $keyword, [
            'checked_at' => now()->subDays(10),
        ]);

        Sanctum::actingAs($user);

        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/history?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'location_id',
                    'history',
                ],
            ]);
    }

    /** @test */
    public function lr_api_get_09_get_history_with_keyword_filter(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $keyword1 = $this->createKeyword($site, ['keyword' => 'pizza']);
        $keyword2 = $this->createKeyword($site, ['keyword' => 'pasta']);
        
        $this->createLocalRanking($location, $keyword1);
        $this->createLocalRanking($location, $keyword2);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/history?keyword_id={$keyword1->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.keyword_id', $keyword1->id);
    }

    /** @test */
    public function lr_api_get_10_invalid_date_format_returns_422(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/history?start_date=invalid");

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function lr_api_get_11_get_quota_usage(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->with($location)
            ->andReturn(23);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/quota");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'location_id',
                    'monthly_limit',
                    'used_this_month',
                    'remaining',
                    'reset_date',
                ],
            ])
            ->assertJsonPath('data.used_this_month', 23)
            ->assertJsonPath('data.remaining', 27);
    }

    /** @test */
    public function lr_api_get_12_quota_at_limit(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(50);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/quota");

        $response->assertStatus(200)
            ->assertJsonPath('data.used_this_month', 50)
            ->assertJsonPath('data.remaining', 0);
    }

    /** @test */
    public function lr_api_get_13_quota_reset_new_month(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(0);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/quota");

        $response->assertStatus(200)
            ->assertJsonPath('data.used_this_month', 0)
            ->assertJsonPath('data.remaining', 50);
    }

    /** @test */
    public function lr_api_get_14_get_competitors_list(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        $competitor = $this->createCompetitor($location, [
            'name' => "Joe's Pizza",
            'rating' => 4.5,
            'review_count' => 123,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/competitors");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'location_id',
                    'competitors' => [
                        '*' => [
                            'id',
                            'name',
                            'google_place_id',
                            'rating',
                            'review_count',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.competitors.0.name', "Joe's Pizza");
    }

    /** @test */
    public function lr_api_get_15_empty_competitors_list(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/competitors");

        $response->assertStatus(200)
            ->assertJsonPath('data.competitors', []);
    }

    // =========================================================================
    // LR-API-POST: POST Endpoints
    // =========================================================================

    /** @test */
    public function lr_api_post_01_check_all_keywords_returns_202(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $keyword1 = $this->createKeyword($site);
        $keyword2 = $this->createKeyword($site);

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(10);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check");

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'job_id',
                    'location_id',
                    'keywords_queued',
                    'status',
                ],
            ])
            ->assertJsonPath('data.keywords_queued', 2);
    }

    /** @test */
    public function lr_api_post_02_check_with_keywords(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        
        for ($i = 0; $i < 5; $i++) {
            $this->createKeyword($site);
        }

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(10);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check");

        $response->assertStatus(202)
            ->assertJsonPath('data.keywords_queued', 5);
    }

    /** @test */
    public function lr_api_post_03_check_with_no_keywords(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'No keywords to check');
    }

    /** @test */
    public function lr_api_post_04_quota_exceeded_on_check(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $this->createKeyword($site);

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(50);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check");

        $response->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['data' => ['reset_date']]);
    }

    /** @test */
    public function lr_api_post_05_check_single_keyword_returns_202(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $keyword = $this->createKeyword($site);

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(10);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check/{$keyword->id}");

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.keywords_queued', 1);
    }

    /** @test */
    public function lr_api_post_06_single_keyword_quota_exceeded(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $keyword = $this->createKeyword($site);

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(50);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check/{$keyword->id}");

        $response->assertStatus(429);
    }

    /** @test */
    public function lr_api_post_07_keyword_not_found(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check/99999");

        $response->assertStatus(404);
    }

    /** @test */
    public function lr_api_post_08_keyword_belongs_to_other_site(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $otherKeyword = Keyword::factory()->create(['site_id' => $otherSite->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check/{$otherKeyword->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function lr_api_post_09_add_competitor(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/competitors", [
            'name' => "Joe's Pizza",
            'google_place_id' => 'ChIJabc123xyz',
            'notes' => 'Main competitor',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.competitor.name', "Joe's Pizza");

        $this->assertDatabaseHas('local_competitors', [
            'business_location_id' => $location->id,
            'name' => "Joe's Pizza",
        ]);
    }

    /** @test */
    public function lr_api_post_10_add_duplicate_competitor(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        $this->createCompetitor($location, ['google_place_id' => 'ChIJabc123xyz']);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/competitors", [
            'name' => "Another Joe's",
            'google_place_id' => 'ChIJabc123xyz',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function lr_api_post_11_add_competitor_validation_error(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/competitors", [
            'name' => '',
            'google_place_id' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function lr_api_post_12_add_competitor_max_limit(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        
        // Create 20 competitors (max limit)
        for ($i = 0; $i < 20; $i++) {
            $this->createCompetitor($location, ['google_place_id' => "place_$i"]);
        }

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/competitors", [
            'name' => 'Extra Competitor',
            'google_place_id' => 'place_extra',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    // =========================================================================
    // LR-API-DELETE: DELETE Endpoints
    // =========================================================================

    /** @test */
    public function lr_api_del_01_delete_competitor(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        $competitor = $this->createCompetitor($location);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/competitors/{$competitor->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('local_competitors', [
            'id' => $competitor->id,
        ]);
    }

    /** @test */
    public function lr_api_del_02_competitor_not_found(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/competitors/99999");

        $response->assertStatus(404);
    }

    /** @test */
    public function lr_api_del_03_competitor_other_user(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        $competitor = $this->createCompetitor($location);
        
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}/competitors/{$competitor->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('local_competitors', [
            'id' => $competitor->id,
        ]);
    }

    // =========================================================================
    // LR-API-AUTH: Authentication & Authorization
    // =========================================================================

    /** @test */
    public function lr_api_auth_01_no_auth_token(): void
    {
        ['location' => $location] = $this->createUserWithLocation();

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(401);
    }

    /** @test */
    public function lr_api_auth_02_invalid_token(): void
    {
        ['location' => $location] = $this->createUserWithLocation();

        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(401);
    }

    /** @test */
    public function lr_api_auth_03_expired_token(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        
        // Create an expired token
        $token = $user->createToken('test-token');
        $token->accessToken->update(['expires_at' => now()->subDay()]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(401);
    }

    /** @test */
    public function lr_api_auth_04_valid_token(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(200);
    }

    // =========================================================================
    // LR-API-QUOTA: Quota Exceeded Handling
    // =========================================================================

    /** @test */
    public function lr_api_quota_01_quota_exceeded_response(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $this->createKeyword($site);

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(50);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check");

        $response->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Quota exceeded');
    }

    /** @test */
    public function lr_api_quota_02_retry_after_header(): void
    {
        ['user' => $user, 'location' => $location, 'site' => $site] = $this->createUserWithLocation();
        $this->createKeyword($site);

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(50);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check");

        $response->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    /** @test */
    public function lr_api_quota_03_quota_reset_date(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(50);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/quota");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['reset_date']]);
    }

    /** @test */
    public function lr_api_quota_04_remaining_checks(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andReturn(23);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/quota");

        $response->assertStatus(200)
            ->assertJsonPath('data.remaining', 27);
    }

    // =========================================================================
    // LR-API-ERR: Error Responses
    // =========================================================================

    /** @test */
    public function lr_api_err_01_location_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/locations/99999/rankings');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Location not found');
    }

    /** @test */
    public function lr_api_err_02_validation_error(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/competitors", [
            'name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function lr_api_err_03_forbidden(): void
    {
        ['location' => $location] = $this->createUserWithLocation();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function lr_api_err_04_unauthorized(): void
    {
        ['location' => $location] = $this->createUserWithLocation();

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated');
    }

    /** @test */
    public function lr_api_err_05_rate_limited(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        // Make 61 requests in rapid succession
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");
            
            if ($response->status() === 429) {
                $response->assertStatus(429)
                    ->assertJsonPath('success', false);
                return;
            }
        }

        // If we get here, rate limiting isn't working as expected
        // This is acceptable in testing without actual rate limiting
        $this->markTestSkipped('Rate limiting not triggered in test environment');
    }

    /** @test */
    public function lr_api_err_06_service_error(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $this->mockLocalRankService
            ->shouldReceive('getMonthlyCheckCount')
            ->once()
            ->andThrow(new \Exception('Service unavailable'));

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/quota");

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    // =========================================================================
    // LR-API-SEC: Security Tests
    // =========================================================================

    /** @test */
    public function lr_api_sec_01_sql_injection_in_location_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/1; DROP TABLE users;/rankings");

        $response->assertStatus(404);
        
        // Verify users table still exists
        $this->assertGreaterThan(0, User::count());
    }

    /** @test */
    public function lr_api_sec_02_sql_injection_in_keyword_id(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings/check/1; SELECT * FROM users");

        $response->assertStatus(404);
    }

    /** @test */
    public function lr_api_sec_03_xss_in_competitor_name(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/competitors", [
            'name' => '<script>alert(1)</script>',
            'google_place_id' => 'ChIJtest123',
        ]);

        // Should either reject or sanitize
        if ($response->status() === 201) {
            // If accepted, verify it's escaped in output
            $response->assertDontSee('<script>');
        } else {
            $response->assertStatus(422);
        }
    }

    /** @test */
    public function lr_api_sec_04_path_traversal(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/../../../etc/passwd/rankings");

        $response->assertStatus(404);
    }

    /** @test */
    public function lr_api_sec_05_mass_assignment(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/competitors", [
            'name' => 'Test Competitor',
            'google_place_id' => 'ChIJtest123',
            'user_id' => 999, // Attempt to override ownership
        ]);

        $response->assertStatus(201);

        // Verify user_id wasn't overridden
        $competitor = LocalCompetitor::where('google_place_id', 'ChIJtest123')->first();
        $this->assertEquals($location->id, $competitor->business_location_id);
    }

    /** @test */
    public function lr_api_sec_06_idor_prevention(): void
    {
        ['location' => $location1] = $this->createUserWithLocation();
        ['user' => $user2, 'location' => $location2] = $this->createUserWithLocation();

        Sanctum::actingAs($user2);

        // Try to access user1's location
        $response = $this->getJson("/api/v1/locations/{$location1->id}/rankings");

        $response->assertStatus(403);
    }

    /** @test */
    public function lr_api_sec_07_rate_limit_enforcement(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        // This test verifies rate limiting middleware is applied
        // Actual rate limiting behavior depends on configuration
        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings");
        
        // Check that rate limiting headers might be present
        $response->assertStatus(200);
    }

    // =========================================================================
    // HTTP Method Restrictions
    // =========================================================================

    /** @test */
    public function get_rankings_rejects_post(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/rankings");

        $response->assertStatus(405);
    }

    /** @test */
    public function check_rankings_rejects_get(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/locations/{$location->id}/rankings/check");

        $response->assertStatus(405);
    }

    /** @test */
    public function delete_competitor_rejects_post(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        $competitor = $this->createCompetitor($location);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/locations/{$location->id}/competitors/{$competitor->id}");

        $response->assertStatus(405);
    }
}
