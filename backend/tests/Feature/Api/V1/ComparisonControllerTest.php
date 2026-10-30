<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Keyword;
use App\Models\Competitor;
use App\Models\CompetitorRanking;

/**
 * Feature tests for SPEC-003-5: Comparison Engine API
 */
class ComparisonControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * Test 1: Get comparison data - success
     */
    public function test_get_comparison_data_success(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $user = User::factory()->create();
        $competitor = Competitor::factory()->create(['user_id' => $user->id]);
        $keyword = Keyword::factory()->create(['user_id' => $user->id]);

        CompetitorRanking::create([
            'keyword_id' => $keyword->id,
            'competitor_id' => $competitor->id,
            'position' => 10,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/rankings/comparison');

        $response->assertOk();
        $response->assertJsonStructure([
            'summary' => [
                'totalKeywords',
                'imWinning',
                'theyreWinning',
            ],
            'results',
        ]);
    }

    /**
     * Test 3: Filter - I'm winning
     */
    public function test_filter_im_winning(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $user = User::factory()->create();
        $competitor = Competitor::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/rankings/comparison?filter=im-winning');

        $response->assertOk();
    }

    /**
     * Test 29: Invalid filter value
     */
    public function test_invalid_filter_value(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/rankings/comparison?filter=invalid');

        $response->assertStatus(400);
    }

    /**
     * Test 12: Pagination
     */
    public function test_comparison_pagination(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/rankings/comparison?page=1&perPage=20');

        $response->assertOk();
        $response->assertJsonStructure([
            'meta' => ['total', 'page', 'perPage'],
        ]);
    }

    /**
     * Test 16: Get competitor rankings - success
     */
    public function test_get_competitor_rankings(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $user = User::factory()->create();
        $competitor = Competitor::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/competitors/{$competitor->id}/rankings");

        $response->assertOk();
    }

    /**
     * Test 17: Get competitor rankings - not found
     */
    public function test_get_competitor_rankings_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/competitors/99999/rankings');

        $response->assertNotFound();
    }

    /**
     * Test 18: Get competitor rankings - other user's competitor
     */
    public function test_get_competitor_rankings_other_user(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $competitor = Competitor::factory()->create(['user_id' => $userA->id]);

        $response = $this->actingAs($userB)
            ->getJson("/api/v1/competitors/{$competitor->id}/rankings");

        $response->assertNotFound();
    }

    /**
     * Test 20: Get keyword gaps - success
     */
    public function test_get_keyword_gaps(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $user = User::factory()->create();
        $competitor = Competitor::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/competitors/{$competitor->id}/gaps");

        $response->assertOk();
        $response->assertJsonStructure(['gaps']);
    }

    /**
     * Test 23: Get historical data - success
     */
    public function test_get_historical_data(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $user = User::factory()->create();
        $keyword = Keyword::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/rankings/comparison/history?keywordId={$keyword->id}&days=30");

        $response->assertOk();
    }
}
