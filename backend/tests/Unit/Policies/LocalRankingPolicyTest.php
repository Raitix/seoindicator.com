<?php

namespace Tests\Unit\Policies;

use App\Models\BusinessLocation;
use App\Models\LocalCompetitor;
use App\Models\Site;
use App\Models\User;
use App\Policies\LocalRankingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for LocalRankingPolicy
 * 
 * Tests authorization rules for local ranking endpoints.
 * 
 * @see TASK-35-15
 * @see TEST-35-15
 */
class LocalRankingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected LocalRankingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new LocalRankingPolicy();
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

    // =========================================================================
    // View Rankings Policy Tests
    // =========================================================================

    /** @test */
    public function user_can_view_own_location_rankings(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $result = $this->policy->viewRankings($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_view_other_users_location_rankings(): void
    {
        ['location' => $location] = $this->createUserWithLocation();
        $otherUser = User::factory()->create();

        $result = $this->policy->viewRankings($otherUser, $location);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Check Rankings Policy Tests
    // =========================================================================

    /** @test */
    public function user_can_check_own_location_rankings(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $result = $this->policy->checkRankings($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_check_other_users_location_rankings(): void
    {
        ['location' => $location] = $this->createUserWithLocation();
        $otherUser = User::factory()->create();

        $result = $this->policy->checkRankings($otherUser, $location);

        $this->assertFalse($result);
    }

    // =========================================================================
    // View Quota Policy Tests
    // =========================================================================

    /** @test */
    public function user_can_view_own_location_quota(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $result = $this->policy->viewQuota($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_view_other_users_location_quota(): void
    {
        ['location' => $location] = $this->createUserWithLocation();
        $otherUser = User::factory()->create();

        $result = $this->policy->viewQuota($otherUser, $location);

        $this->assertFalse($result);
    }

    // =========================================================================
    // View Competitors Policy Tests
    // =========================================================================

    /** @test */
    public function user_can_view_own_location_competitors(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $result = $this->policy->viewCompetitors($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_view_other_users_location_competitors(): void
    {
        ['location' => $location] = $this->createUserWithLocation();
        $otherUser = User::factory()->create();

        $result = $this->policy->viewCompetitors($otherUser, $location);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Add Competitor Policy Tests
    // =========================================================================

    /** @test */
    public function user_can_add_competitor_to_own_location(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        $result = $this->policy->addCompetitor($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_add_competitor_to_other_users_location(): void
    {
        ['location' => $location] = $this->createUserWithLocation();
        $otherUser = User::factory()->create();

        $result = $this->policy->addCompetitor($otherUser, $location);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Delete Competitor Policy Tests
    // =========================================================================

    /** @test */
    public function user_can_delete_own_location_competitor(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        $competitor = LocalCompetitor::factory()->create(['business_location_id' => $location->id]);

        $result = $this->policy->deleteCompetitor($user, $competitor);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_delete_other_users_competitor(): void
    {
        ['location' => $location] = $this->createUserWithLocation();
        $competitor = LocalCompetitor::factory()->create(['business_location_id' => $location->id]);
        $otherUser = User::factory()->create();

        $result = $this->policy->deleteCompetitor($otherUser, $competitor);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /** @test */
    public function policy_works_with_same_user_object(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        // Same user object should pass all checks
        $this->assertTrue($this->policy->viewRankings($user, $location));
        $this->assertTrue($this->policy->checkRankings($user, $location));
        $this->assertTrue($this->policy->viewQuota($user, $location));
        $this->assertTrue($this->policy->viewCompetitors($user, $location));
        $this->assertTrue($this->policy->addCompetitor($user, $location));
    }

    /** @test */
    public function policy_denies_all_for_non_owner(): void
    {
        ['location' => $location] = $this->createUserWithLocation();
        $otherUser = User::factory()->create();
        $competitor = LocalCompetitor::factory()->create(['business_location_id' => $location->id]);

        $this->assertFalse($this->policy->viewRankings($otherUser, $location));
        $this->assertFalse($this->policy->checkRankings($otherUser, $location));
        $this->assertFalse($this->policy->viewQuota($otherUser, $location));
        $this->assertFalse($this->policy->viewCompetitors($otherUser, $location));
        $this->assertFalse($this->policy->addCompetitor($otherUser, $location));
        $this->assertFalse($this->policy->deleteCompetitor($otherUser, $competitor));
    }

    /** @test */
    public function policy_handles_same_id_different_objects(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();
        
        // Create another user with same ID (hypothetical)
        $userWithSameId = User::find($user->id);

        $result = $this->policy->viewRankings($userWithSameId, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function policy_checks_user_id_ownership(): void
    {
        ['user' => $user, 'location' => $location] = $this->createUserWithLocation();

        // Verify the ownership chain: location -> site -> user
        $site = $location->site;
        $this->assertEquals($user->id, $site->user_id);
        $this->assertEquals($site->id, $location->site_id);
    }

    /** @test */
    public function policy_with_multiple_locations_same_user(): void
    {
        ['user' => $user, 'site' => $site] = $this->createUserWithLocation();
        
        $location2 = BusinessLocation::factory()->create(['site_id' => $site->id]);
        $location3 = BusinessLocation::factory()->create(['site_id' => $site->id]);

        $this->assertTrue($this->policy->viewRankings($user, $location2));
        $this->assertTrue($this->policy->viewRankings($user, $location3));
    }

    /** @test */
    public function policy_with_multiple_sites_same_user(): void
    {
        ['user' => $user] = $this->createUserWithLocation();
        
        $site2 = Site::factory()->create(['user_id' => $user->id]);
        $location4 = BusinessLocation::factory()->create(['site_id' => $site2->id]);

        $this->assertTrue($this->policy->viewRankings($user, $location4));
    }

    /** @test */
    public function policy_with_different_sites_different_users(): void
    {
        ['user' => $user1, 'location' => $location1] = $this->createUserWithLocation();
        ['user' => $user2, 'location' => $location2] = $this->createUserWithLocation();

        // Each user can only access their own locations
        $this->assertTrue($this->policy->viewRankings($user1, $location1));
        $this->assertTrue($this->policy->viewRankings($user2, $location2));
        
        $this->assertFalse($this->policy->viewRankings($user1, $location2));
        $this->assertFalse($this->policy->viewRankings($user2, $location1));
    }

    // =========================================================================
    // Policy Method Existence
    // =========================================================================

    /** @test */
    public function policy_has_view_rankings_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'viewRankings'));
    }

    /** @test */
    public function policy_has_check_rankings_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'checkRankings'));
    }

    /** @test */
    public function policy_has_view_quota_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'viewQuota'));
    }

    /** @test */
    public function policy_has_view_competitors_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'viewCompetitors'));
    }

    /** @test */
    public function policy_has_add_competitor_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'addCompetitor'));
    }

    /** @test */
    public function policy_has_delete_competitor_method(): void
    {
        $this->assertTrue(method_exists($this->policy, 'deleteCompetitor'));
    }
}
