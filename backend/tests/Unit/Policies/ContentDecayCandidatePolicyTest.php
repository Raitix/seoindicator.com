<?php

namespace Tests\Unit\Policies;

use App\Policies\ContentDecayCandidatePolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group content-decay
 * @group policy
 */
class ContentDecayCandidatePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ContentDecayCandidatePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ContentDecayCandidatePolicy();
    }

    /**
     * Create a mock candidate object with nested project
     */
    protected function createMockCandidate(int $userId, int $projectId = 1): object
    {
        return (object) [
            'id' => 1,
            'project_id' => $projectId,
            'project' => (object) [
                'id' => $projectId,
                'user_id' => $userId,
                'site_id' => 1,
            ],
        ];
    }

    // ==========================================
    // VIEW POLICY TESTS
    // ==========================================

    /** @test */
    public function view_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $candidate = $this->createMockCandidate($user->id);

        $result = $this->policy->view($user, $candidate);

        $this->assertTrue($result);
    }

    /** @test */
    public function view_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $result = $this->policy->view($nonOwner, $candidate);

        $this->assertFalse($result);
    }

    /** @test */
    public function view_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $result = $this->policy->view($admin, $candidate);

        $this->assertTrue($result);
    }

    // ==========================================
    // UPDATE POLICY TESTS
    // ==========================================

    /** @test */
    public function update_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $candidate = $this->createMockCandidate($user->id);

        $result = $this->policy->update($user, $candidate);

        $this->assertTrue($result);
    }

    /** @test */
    public function update_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $result = $this->policy->update($nonOwner, $candidate);

        $this->assertFalse($result);
    }

    /** @test */
    public function update_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $result = $this->policy->update($admin, $candidate);

        $this->assertTrue($result);
    }

    // ==========================================
    // RESTORE POLICY TESTS
    // ==========================================

    /** @test */
    public function restore_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $candidate = $this->createMockCandidate($user->id);

        $result = $this->policy->restore($user, $candidate);

        $this->assertTrue($result);
    }

    /** @test */
    public function restore_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $result = $this->policy->restore($nonOwner, $candidate);

        $this->assertFalse($result);
    }

    /** @test */
    public function restore_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $result = $this->policy->restore($admin, $candidate);

        $this->assertTrue($result);
    }

    // ==========================================
    // CREATE TASK POLICY TESTS
    // ==========================================

    /** @test */
    public function createTask_returns_true_for_owner(): void
    {
        $user = User::factory()->create();
        $candidate = $this->createMockCandidate($user->id);

        $result = $this->policy->createTask($user, $candidate);

        $this->assertTrue($result);
    }

    /** @test */
    public function createTask_returns_false_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $result = $this->policy->createTask($nonOwner, $candidate);

        $this->assertFalse($result);
    }

    /** @test */
    public function createTask_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $result = $this->policy->createTask($admin, $candidate);

        $this->assertTrue($result);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function all_actions_denied_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $this->assertFalse($this->policy->view($nonOwner, $candidate));
        $this->assertFalse($this->policy->update($nonOwner, $candidate));
        $this->assertFalse($this->policy->restore($nonOwner, $candidate));
        $this->assertFalse($this->policy->createTask($nonOwner, $candidate));
    }

    /** @test */
    public function all_actions_allowed_for_owner(): void
    {
        $user = User::factory()->create();
        $candidate = $this->createMockCandidate($user->id);

        $this->assertTrue($this->policy->view($user, $candidate));
        $this->assertTrue($this->policy->update($user, $candidate));
        $this->assertTrue($this->policy->restore($user, $candidate));
        $this->assertTrue($this->policy->createTask($user, $candidate));
    }

    /** @test */
    public function all_actions_allowed_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $candidate = $this->createMockCandidate($owner->id);

        $this->assertTrue($this->policy->view($admin, $candidate));
        $this->assertTrue($this->policy->update($admin, $candidate));
        $this->assertTrue($this->policy->restore($admin, $candidate));
        $this->assertTrue($this->policy->createTask($admin, $candidate));
    }

    /** @test */
    public function policy_checks_user_id_through_nested_project(): void
    {
        $owner = User::factory()->create();
        $differentUser = User::factory()->create();

        $candidate = $this->createMockCandidate($owner->id);

        // Owner can access
        $this->assertTrue($this->policy->view($owner, $candidate));

        // Different user cannot
        $this->assertFalse($this->policy->view($differentUser, $candidate));
    }
}
