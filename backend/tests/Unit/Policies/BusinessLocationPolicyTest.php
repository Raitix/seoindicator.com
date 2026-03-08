<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\Site;
use App\Models\BusinessLocation;
use App\Policies\BusinessLocationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @Task TASK-35-13
 * @Description Business Location Policy Tests
 * @Tests 30+
 */
class BusinessLocationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessLocationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BusinessLocationPolicy();
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
     * Create a mock location
     */
    protected function mockLocation(array $attributes = []): BusinessLocation
    {
        return BusinessLocation::factory()->create(array_merge([
            'user_id' => 1,
            'site_id' => 1,
            'name' => 'Test Business',
            'address' => '123 Main St',
        ], $attributes));
    }

    // ==========================================
    // View Policy Tests
    // ==========================================

    /** @test */
    public function user_can_view_own_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $result = $this->policy->view($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_view_other_users_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        $result = $this->policy->view($otherUser, $location);

        $this->assertFalse($result);
    }

    // ==========================================
    // Create Policy Tests
    // ==========================================

    /** @test */
    public function user_can_create_location_in_own_site(): void
    {
        $user = $this->mockUser();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $result = $this->policy->create($user, $site);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_create_location_in_other_users_site(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $site = Site::factory()->create(['user_id' => $owner->id]);

        $result = $this->policy->create($otherUser, $site);

        $this->assertFalse($result);
    }

    // ==========================================
    // Update Policy Tests
    // ==========================================

    /** @test */
    public function user_can_update_own_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $result = $this->policy->update($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_update_other_users_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        $result = $this->policy->update($otherUser, $location);

        $this->assertFalse($result);
    }

    // ==========================================
    // Delete Policy Tests
    // ==========================================

    /** @test */
    public function user_can_delete_own_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $result = $this->policy->delete($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_delete_other_users_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        $result = $this->policy->delete($otherUser, $location);

        $this->assertFalse($result);
    }

    // ==========================================
    // Restore Policy Tests
    // ==========================================

    /** @test */
    public function user_can_restore_own_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id, 'deleted_at' => now()]);

        $result = $this->policy->restore($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_restore_other_users_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id, 'deleted_at' => now()]);

        $result = $this->policy->restore($otherUser, $location);

        $this->assertFalse($result);
    }

    // ==========================================
    // Force Delete Policy Tests
    // ==========================================

    /** @test */
    public function user_can_force_delete_own_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $result = $this->policy->forceDelete($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_force_delete_other_users_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        $result = $this->policy->forceDelete($otherUser, $location);

        $this->assertFalse($result);
    }

    // ==========================================
    // Connect GBP Policy Tests
    // ==========================================

    /** @test */
    public function user_can_connect_gbp_to_own_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id]);

        $result = $this->policy->connectGbp($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_connect_gbp_to_other_users_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        $result = $this->policy->connectGbp($otherUser, $location);

        $this->assertFalse($result);
    }

    // ==========================================
    // Disconnect GBP Policy Tests
    // ==========================================

    /** @test */
    public function user_can_disconnect_gbp_from_own_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id, 'is_gbp_connected' => true]);

        $result = $this->policy->disconnectGbp($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_disconnect_gbp_from_other_users_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id, 'is_gbp_connected' => true]);

        $result = $this->policy->disconnectGbp($otherUser, $location);

        $this->assertFalse($result);
    }

    // ==========================================
    // Sync GBP Policy Tests
    // ==========================================

    /** @test */
    public function user_can_sync_gbp_for_own_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation(['user_id' => $user->id, 'is_gbp_connected' => true]);

        $result = $this->policy->syncGbp($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function user_cannot_sync_gbp_for_other_users_location(): void
    {
        $owner = $this->mockUser();
        $otherUser = $this->mockUser(['email' => 'other@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id, 'is_gbp_connected' => true]);

        $result = $this->policy->syncGbp($otherUser, $location);

        $this->assertFalse($result);
    }

    // ==========================================
    // Admin Override Tests
    // ==========================================

    /** @test */
    public function admin_can_view_any_location(): void
    {
        $admin = $this->mockUser(['role' => 'admin']);
        $owner = $this->mockUser(['email' => 'owner@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        $result = $this->policy->view($admin, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function admin_can_update_any_location(): void
    {
        $admin = $this->mockUser(['role' => 'admin']);
        $owner = $this->mockUser(['email' => 'owner@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        $result = $this->policy->update($admin, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function admin_can_delete_any_location(): void
    {
        $admin = $this->mockUser(['role' => 'admin']);
        $owner = $this->mockUser(['email' => 'owner@example.com']);
        $location = $this->mockLocation(['user_id' => $owner->id]);

        $result = $this->policy->delete($admin, $location);

        $this->assertTrue($result);
    }

    // ==========================================
    // Edge Cases Tests
    // ==========================================

    /** @test */
    public function policy_handles_deleted_location(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'deleted_at' => now(),
        ]);

        $result = $this->policy->view($user, $location);

        $this->assertTrue($result);
    }

    /** @test */
    public function policy_handles_location_with_gbp_connected(): void
    {
        $user = $this->mockUser();
        $location = $this->mockLocation([
            'user_id' => $user->id,
            'is_gbp_connected' => true,
        ]);

        $result = $this->policy->view($user, $location);

        $this->assertTrue($result);
    }
}
