<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\CompetitorRanking;
use App\Models\Competitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for CompetitorRanking model
 * 
 * Tests verify:
 * - Fillable attributes
 * - Relationships
 * - Nullable fields
 */
class CompetitorRankingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 11: Model has correct fillable attributes
     */
    public function test_competitor_ranking_has_fillable_attributes(): void
    {
        $ranking = new CompetitorRanking();
        
        $expectedFillable = ['competitor_id', 'keyword_id', 'position', 'url', 'checked_at'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains(
                $attribute,
                $ranking->getFillable(),
                "CompetitorRanking model missing fillable attribute: {$attribute}"
            );
        }
    }

    /**
     * Test 14: CompetitorRanking belongs to Competitor relationship
     */
    public function test_ranking_belongs_to_competitor(): void
    {
        $ranking = new CompetitorRanking();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $ranking->competitor(),
            'CompetitorRanking should have a belongsTo relationship with Competitor'
        );
        
        $this->assertEquals(
            Competitor::class,
            $ranking->competitor()->getRelated()::class,
            'CompetitorRanking competitor relationship should reference Competitor model'
        );
    }

    /**
     * Test 15: CompetitorRanking belongs to Keyword relationship
     */
    public function test_ranking_belongs_to_keyword(): void
    {
        $ranking = new CompetitorRanking();
        
        // Check if keyword relationship exists
        if (method_exists($ranking, 'keyword')) {
            $this->assertInstanceOf(
                \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
                $ranking->keyword(),
                'CompetitorRanking should have a belongsTo relationship with Keyword'
            );
        } else {
            $this->markTestSkipped('Keyword model not yet implemented (requires SPEC-001-1)');
        }
    }

    /**
     * Test 18: Position can be null
     */
    public function test_position_can_be_null(): void
    {
        $this->artisan('migrate');
        
        if (!\Illuminate\Support\Facades\Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $keyword = \App\Models\Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'test keyword',
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);
        
        $ranking = CompetitorRanking::create([
            'competitor_id' => $competitor->id,
            'keyword_id' => $keyword->id,
            'position' => null,
            'checked_at' => now(),
        ]);
        
        $this->assertNull($ranking->position);
    }

    /**
     * Test 19: URL can be null
     */
    public function test_url_can_be_null(): void
    {
        $this->artisan('migrate');
        
        if (!\Illuminate\Support\Facades\Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $keyword = \App\Models\Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'test keyword',
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);
        
        $ranking = CompetitorRanking::create([
            'competitor_id' => $competitor->id,
            'keyword_id' => $keyword->id,
            'position' => 5,
            'url' => null,
            'checked_at' => now(),
        ]);
        
        $this->assertNull($ranking->url);
    }

    /**
     * Test: CompetitorRanking can be created with all attributes
     */
    public function test_ranking_can_be_created_with_all_attributes(): void
    {
        $this->artisan('migrate');
        
        if (!\Illuminate\Support\Facades\Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $keyword = \App\Models\Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'test keyword',
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);
        
        $checkedAt = now();
        
        $ranking = CompetitorRanking::create([
            'competitor_id' => $competitor->id,
            'keyword_id' => $keyword->id,
            'position' => 5,
            'url' => 'https://competitor.com/page',
            'checked_at' => $checkedAt,
        ]);
        
        $this->assertEquals($competitor->id, $ranking->competitor_id);
        $this->assertEquals($keyword->id, $ranking->keyword_id);
        $this->assertEquals(5, $ranking->position);
        $this->assertEquals('https://competitor.com/page', $ranking->url);
        $this->assertEquals($checkedAt->toDateTimeString(), $ranking->checked_at->toDateTimeString());
    }

    /**
     * Test: Ranking access via competitor relationship
     */
    public function test_ranking_accessible_via_competitor(): void
    {
        $this->artisan('migrate');
        
        if (!\Illuminate\Support\Facades\Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test4@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $keyword = \App\Models\Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'test keyword',
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);
        
        CompetitorRanking::create([
            'competitor_id' => $competitor->id,
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now(),
        ]);
        
        CompetitorRanking::create([
            'competitor_id' => $competitor->id,
            'keyword_id' => $keyword->id,
            'position' => 3,
            'checked_at' => now()->subDay(),
        ]);
        
        $this->assertCount(2, $competitor->rankings);
    }

    /**
     * Test: Position must be an integer when set
     */
    public function test_position_must_be_integer(): void
    {
        $this->artisan('migrate');
        
        if (!\Illuminate\Support\Facades\Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test5@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $keyword = \App\Models\Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'test keyword',
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);
        
        $ranking = CompetitorRanking::create([
            'competitor_id' => $competitor->id,
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now(),
        ]);
        
        // Position should be integer
        $this->assertIsInt($ranking->position);
    }

    /**
     * Test: Checked at is required
     */
    public function test_checked_at_is_required(): void
    {
        $this->artisan('migrate');
        
        if (!\Illuminate\Support\Facades\Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test6@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $keyword = \App\Models\Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'test keyword',
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        CompetitorRanking::create([
            'competitor_id' => $competitor->id,
            'keyword_id' => $keyword->id,
            'position' => 5,
            // checked_at is missing
        ]);
    }

    /**
     * Security Test: Mass assignment protection
     */
    public function test_mass_assignment_protection(): void
    {
        $this->artisan('migrate');
        
        if (!\Illuminate\Support\Facades\Schema::hasTable('keywords')) {
            $this->markTestSkipped('Keywords table not yet created (requires SPEC-001-1)');
        }
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test7@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $keyword = \App\Models\Keyword::create([
            'user_id' => $user->id,
            'keyword' => 'test keyword',
        ]);
        
        $competitor = Competitor::create([
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);
        
        // Attempt to set id via mass assignment (should be ignored)
        $ranking = CompetitorRanking::create([
            'competitor_id' => $competitor->id,
            'keyword_id' => $keyword->id,
            'position' => 5,
            'checked_at' => now(),
            'id' => 99999, // Should be ignored
        ]);
        
        // The actual ID should be auto-incremented, not 99999
        $this->assertNotEquals(99999, $ranking->id);
    }
}
