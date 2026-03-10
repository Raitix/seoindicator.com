<?php

namespace Tests\Unit\Policies;

use App\Policies\ContentDecayProjectPolicy;
use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group content-decay
 * @group policy
 */
class ContentDecayProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ContentDecayProjectPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ContentDecayProjectPolicy();
    }

    /**
     * Create a mock project object
     */
    protected function createMockProject(int $userId, int $siteId = 1): object
    {
        return (object) [
            'id' => 1,
            'user_id' => $userId,
            'site_id' => $siteId,
        ];
    }

    // ==========================================
    // VIEW POLICY TESTS
    // ==========================================

    /** @test */
    public function view_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user->id);

        $result = $this->policy->view($user, $project);

        $this->assertTrue($result);
    }

    /** @test */
    public function view_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $project = $this->createMockProject($owner->id);

        $result = $this->policy->view($nonOwner, $project);

        $this->assertFalse($result);
    }

    /** @test */
    public function view_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $project = $this->createMockProject($owner->id);

        $result = $this->policy->view($admin, $project);

        $this->assertTrue($result);
    }

    // ==========================================
    // UPDATE POLICY TESTS
    // ==========================================

    /** @test */
    public function update_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user->id);

        $result = $this->policy->update($user, $project);

        $this->assertTrue($result);
    }

    /** @test */
    public function update_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $project = $this->createMockProject($owner->id);

        $result = $this->policy->update($nonOwner, $project);

        $this->assertFalse($result);
    }

    /** @test */
    public function update_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $project = $this->createMockProject($owner->id);

        $result = $this->policy->update($admin, $project);

        $this->assertTrue($result);
    }

    // ==========================================
    // RUN DETECTION POLICY TESTS
    // ==========================================

    /** @test */
    public function runDetection_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user->id);

        $result = $this->policy->runDetection($user, $project);

        $this->assertTrue($result);
    }

    /** @test */
    public function runDetection_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $project = $this->createMockProject($owner->id);

        $result = $this->policy->runDetection($nonOwner, $project);

        $this->assertFalse($result);
    }

    /** @test */
    public function runDetection_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $project = $this->createMockProject($owner->id);

        $result = $this->policy->runDetection($admin, $project);

        $this->assertTrue($result);
    }

    // ==========================================
    // EXPORT POLICY TESTS
    // ==========================================

    /** @test */
    public function export_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user->id);

        $result = $this->policy->export($user, $project);

        $this->assertTrue($result);
    }

    /** @test */
    public function export_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $project = $this->createMockProject($owner->id);

        $result = $this->policy->export($nonOwner, $project);

        $this->assertFalse($result);
    }

    /** @test */
    public function export_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $project = $this->createMockProject($owner->id);

        $result = $this->policy->export($admin, $project);

        $this->assertTrue($result);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function view_returns_false_for_same_id_different_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Ensure different IDs
        $this->assertNotEquals($user1->id, $user2->id);

        $project = $this->createMockProject($user1->id);

        $result = $this->policy->view($user2, $project);

        $this->assertFalse($result);
    }

    /** @test */
    public function policy_works_with_user_object_having_same_id(): void
    {
        $user = User::factory()->create();
        $project = $this->createMockProject($user->id);

        // Verify the user can access their own project
        $this->assertTrue($this->policy->view($user, $project));
        $this->assertTrue($this->policy->update($user, $project));
        $this->assertTrue($this->policy->runDetection($user, $project));
        $this->assertTrue($this->policy->export($user, $project));
    }

    /** @test */
    public function all_actions_denied_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $project = $this->createMockProject($owner->id);

        $this->assertFalse($this->policy->view($nonOwner, $project));
        $this->assertFalse($this->policy->update($nonOwner, $project));
        $this->assertFalse($this->policy->runDetection($nonOwner, $project));
        $this->assertFalse($this->policy->export($nonOwner, $project));
    }
}
