<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\Keyword;
use App\Models\Site;
use App\Models\User;
use App\Models\Ranking;
use App\Services\Queue\JobQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class RankingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Site $site;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
    }

    // ==================== AUTHENTICATION TESTS ====================

    /**
     * Test unauthenticated request to history endpoint.
     */
    public function test_history_requires_authentication(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        $response = $this->getJson("/api/v1/keywords/{$keyword->id}/history");
        
        $response->assertStatus(401);
    }

    /**
     * Test unauthenticated request to check endpoint.
     */
    public function test_check_requires_authentication(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        $response = $this->postJson("/api/v1/keywords/{$keyword->id}/check");
        
        $response->assertStatus(401);
    }

    // ==================== IDOR PROTECTION TESTS ====================

    /**
     * Test user cannot access another user's keyword history.
     */
    public function test_user_cannot_access_other_users_keyword_history(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $keyword = Keyword::factory()->create(['site_id' => $otherSite->id]);
        
        Ranking::factory()->count(5)->create(['keyword_id' => $keyword->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}/history");
        
        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    /**
     * Test user cannot trigger check on another user's keyword.
     */
    public function test_user_cannot_check_other_users_keyword(): void
    {
        $otherUser = User::factory()->create();
        $otherSite = Site::factory()->create(['user_id' => $otherUser->id]);
        $keyword = Keyword::factory()->create(['site_id' => $otherSite->id]);
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$keyword->id}/check");
        
        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    /**
     * Test user can access their own keyword history via user_id.
     */
    public function test_user_can_access_keyword_with_direct_user_id(): void
    {
        $keyword = Keyword::factory()->create([
            'user_id' => $this->user->id,
            'site_id' => null, // No site, just direct user assignment
        ]);
        
        Ranking::factory()->count(3)->create(['keyword_id' => $keyword->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}/history");
        
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.history');
    }

    // ==================== HISTORY TESTS ====================

    /**
     * Test get ranking history for keyword.
     */
    public function test_get_ranking_history(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        Ranking::factory()->count(10)->create(['keyword_id' => $keyword->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}/history");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'keyword' => ['id', 'keyword'],
                    'history' => [
                        '*' => ['position', 'urlFound', 'checkedAt'],
                    ],
                    'summary' => [
                        'currentPosition',
                        'bestPosition',
                        'worstPosition',
                        'averagePosition',
                        'totalChecks',
                    ],
                ],
            ]);
    }

    /**
     * Test get ranking history with no data.
     */
    public function test_get_ranking_history_empty(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}/history");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.history', [])
            ->assertJsonPath('data.summary.totalChecks', 0);
    }

    /**
     * Test get ranking history invalid keyword.
     */
    public function test_get_ranking_history_invalid_keyword(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/keywords/9999/history');
        
        $response->assertStatus(404);
    }

    /**
     * Test get ranking history with date range.
     */
    public function test_get_ranking_history_with_date_range(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        // Within range
        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now()->subDays(10),
        ]);
        
        // Outside range
        Ranking::factory()->create([
            'keyword_id' => $keyword->id,
            'position' => 20,
            'checked_at' => now()->subDays(40),
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}/history?from=" . now()->subDays(30)->toDateString());
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.history');
    }

    /**
     * Test get ranking history with limit.
     */
    public function test_get_ranking_history_with_limit(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        Ranking::factory()->count(50)->create(['keyword_id' => $keyword->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}/history?limit=10");
        
        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.history');
    }

    /**
     * Test history summary statistics.
     */
    public function test_history_summary_statistics(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        Ranking::factory()->createMany([
            ['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()->subDays(3)],
            ['keyword_id' => $keyword->id, 'position' => 10, 'checked_at' => now()->subDays(2)],
            ['keyword_id' => $keyword->id, 'position' => 3, 'checked_at' => now()->subDay()],
            ['keyword_id' => $keyword->id, 'position' => 7, 'checked_at' => now()],
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}/history");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.summary.currentPosition', 7)
            ->assertJsonPath('data.summary.bestPosition', 3)
            ->assertJsonPath('data.summary.worstPosition', 10)
            ->assertJsonPath('data.summary.averagePosition', 6.25)
            ->assertJsonPath('data.summary.totalChecks', 4);
    }

    /**
     * Test history with NULL positions.
     */
    public function test_history_with_null_positions(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        Ranking::factory()->createMany([
            ['keyword_id' => $keyword->id, 'position' => null, 'checked_at' => now()->subDay()],
            ['keyword_id' => $keyword->id, 'position' => 5, 'checked_at' => now()],
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/keywords/{$keyword->id}/history");
        
        $response->assertStatus(200)
            ->assertJsonPath('data.summary.currentPosition', 5);
    }

    // ==================== MANUAL CHECK TESTS ====================

    /**
     * Test trigger manual rank check.
     */
    public function test_trigger_manual_rank_check(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        // Mock the job queue
        $this->mock(JobQueue::class, function ($mock) {
            $mock->shouldReceive('addJob')
                ->once()
                ->andReturn('job-abc123');
        });
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$keyword->id}/check");
        
        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'data' => ['jobId', 'status'],
            ])
            ->assertJsonPath('data.status', 'queued');
    }

    /**
     * Test trigger manual check invalid keyword.
     */
    public function test_trigger_manual_check_invalid_keyword(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/keywords/9999/check');
        
        $response->assertStatus(404);
    }

    /**
     * Test manual check already queued.
     */
    public function test_manual_check_already_queued(): void
    {
        $keyword = Keyword::factory()->create(['site_id' => $this->site->id]);
        
        // Mock the job queue returning existing job
        $this->mock(JobQueue::class, function ($mock) use ($keyword) {
            $mock->shouldReceive('addJob')
                ->once()
                ->andReturn('job-existing-123');
        });
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/keywords/{$keyword->id}/check");
        
        $response->assertStatus(202)
            ->assertJsonPath('data.jobId', 'job-existing-123');
    }
}
