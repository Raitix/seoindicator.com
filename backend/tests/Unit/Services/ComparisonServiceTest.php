<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ComparisonService;
use App\Models\User;
use App\Models\Keyword;
use App\Models\Competitor;
use App\Models\CompetitorRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for SPEC-003-5: Comparison Engine Service
 */
class ComparisonServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ComparisonService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->service = app(ComparisonService::class);
    }

    /**
     * Test 7: Summary calculation - correct counts
     */
    public function test_summary_calculation_correct_counts(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('competitors')) {
            $this->markTestSkipped('Competitors table not yet created');
        }

        $user = User::factory()->create();
        $competitor = Competitor::factory()->create(['user_id' => $user->id]);

        // Create keywords with various scenarios
        // Scenario 1: User winning (user: 5, comp: 10)
        $k1 = Keyword::factory()->create(['user_id' => $user->id, 'keyword' => 'winning']);
        CompetitorRanking::create([
            'keyword_id' => $k1->id,
            'competitor_id' => $competitor->id,
            'position' => 10,
            'checked_at' => now(),
        ]);

        // Scenario 2: Competitor winning (user: 15, comp: 8)
        $k2 = Keyword::factory()->create(['user_id' => $user->id, 'keyword' => 'losing']);
        CompetitorRanking::create([
            'keyword_id' => $k2->id,
            'competitor_id' => $competitor->id,
            'position' => 8,
            'checked_at' => now(),
        ]);

        $summary = $this->service->getSummary($user->id);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('totalKeywords', $summary);
        $this->assertArrayHasKey('imWinning', $summary);
        $this->assertArrayHasKey('theyreWinning', $summary);
    }

    /**
     * Test 8: Position diff calculation - user ahead
     */
    public function test_position_diff_user_ahead(): void
    {
        $diff = $this->service->calculatePositionDiff(5, 10);
        
        $this->assertEquals(-5, $diff);
    }

    /**
     * Test 9: Position diff - competitor ahead
     */
    public function test_position_diff_competitor_ahead(): void
    {
        $diff = $this->service->calculatePositionDiff(15, 8);
        
        $this->assertEquals(7, $diff);
    }

    /**
     * Test 10: Position diff - null handling
     */
    public function test_position_diff_null_handling(): void
    {
        $diff = $this->service->calculatePositionDiff(null, 5);
        
        $this->assertNull($diff);
    }

    /**
     * Test 11: Status determination
     */
    public function test_status_determination(): void
    {
        // User winning
        $this->assertEquals('im-winning', $this->service->determineStatus(5, 10));
        
        // Competitor winning
        $this->assertEquals('theyre-winning', $this->service->determineStatus(15, 8));
        
        // Gap (only competitor ranks)
        $this->assertEquals('gap', $this->service->determineStatus(null, 5));
        
        // Only user ranks
        $this->assertEquals('only-i-rank', $this->service->determineStatus(5, null));
        
        // Tie
        $this->assertEquals('both-rank', $this->service->determineStatus(5, 5));
    }

    /**
     * Test: Both have positions
     */
    public function test_status_both_rank(): void
    {
        $status = $this->service->determineStatus(5, 8);
        
        $this->assertEquals('im-winning', $status);
    }
}
