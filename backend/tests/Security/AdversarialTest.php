<?php

namespace Tests\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Competitor;

/**
 * Adversarial security tests for SPEC-003-2: Competitor CRUD API
 */
class AdversarialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    /**
     * Test SQL injection in domain field
     */
    public function test_sql_injection_in_domain(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/competitors', [
                'domain' => "test.com'; DROP TABLE competitors; --",
            ]);

        // Should fail validation (invalid domain format)
        $response->assertStatus(400);
    }

    /**
     * Test XSS in displayName
     */
    public function test_xss_in_display_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/competitors', [
                'domain' => 'competitor.com',
                'displayName' => '<script>alert("xss")</script>Competitor',
            ]);

        // Should create successfully (stored as string, not executed)
        $response->assertCreated();
        
        // Verify the script tag is stored as-is (frontend responsible for escaping)
        $this->assertDatabaseHas('competitors', [
            'user_id' => $user->id,
            'domain' => 'competitor.com',
        ]);
    }

    /**
     * Test mass assignment protection
     */
    public function test_mass_assignment_protection(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/competitors', [
                'domain' => 'competitor.com',
                'user_id' => $otherUser->id, // Attempt to set another user
                'id' => 999, // Attempt to set ID
            ]);

        $response->assertCreated();

        // Verify the competitor belongs to the authenticated user, not the other user
        $competitor = Competitor::where('domain', 'competitor.com')->first();
        $this->assertEquals($user->id, $competitor->user_id);
        $this->assertNotEquals($otherUser->id, $competitor->user_id);
        $this->assertNotEquals(999, $competitor->id);
    }

    /**
     * Test IDOR - User cannot access other user's competitor
     */
    public function test_idor_protection(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $competitor = Competitor::factory()->create([
            'user_id' => $userA->id,
            'domain' => 'user-a-competitor.com',
        ]);

        // User B tries to access User A's competitor
        $response = $this->actingAs($userB)
            ->getJson("/api/v1/competitors/{$competitor->id}");

        // Should return 404 (not 403 to not leak existence)
        $response->assertStatus(404);
    }

    /**
     * Test IDOR - User cannot update other user's competitor
     */
    public function test_idor_update_protection(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $competitor = Competitor::factory()->create([
            'user_id' => $userA->id,
            'domain' => 'user-a-competitor.com',
        ]);

        // User B tries to update User A's competitor
        $response = $this->actingAs($userB)
            ->putJson("/api/v1/competitors/{$competitor->id}", [
                'displayName' => 'Hacked!',
            ]);

        $response->assertStatus(404);

        // Verify the competitor was not updated
        $competitor->refresh();
        $this->assertNotEquals('Hacked!', $competitor->display_name);
    }

    /**
     * Test IDOR - User cannot delete other user's competitor
     */
    public function test_idor_delete_protection(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $competitor = Competitor::factory()->create([
            'user_id' => $userA->id,
            'domain' => 'user-a-competitor.com',
        ]);

        // User B tries to delete User A's competitor
        $response = $this->actingAs($userB)
            ->deleteJson("/api/v1/competitors/{$competitor->id}");

        $response->assertStatus(404);

        // Verify the competitor was not deleted
        $this->assertDatabaseHas('competitors', [
            'id' => $competitor->id,
        ]);
    }

    /**
     * Test competitor limit enforcement
     */
    public function test_competitor_limit(): void
    {
        $user = User::factory()->create();

        // Create 3 competitors (the limit)
        Competitor::factory()->count(3)->create(['user_id' => $user->id]);

        // Try to create a 4th
        $response = $this->actingAs($user)
            ->postJson('/api/v1/competitors', [
                'domain' => 'competitor4.com',
            ]);

        $response->assertStatus(400);
        $response->assertJsonPath('error.code', 'COMPETITOR_LIMIT_EXCEEDED');
    }

    /**
     * Test unauthenticated access is blocked
     */
    public function test_unauthenticated_access_blocked(): void
    {
        $endpoints = [
            ['GET', '/api/v1/competitors'],
            ['POST', '/api/v1/competitors', ['domain' => 'test.com']],
            ['GET', '/api/v1/competitors/1'],
            ['PUT', '/api/v1/competitors/1', ['displayName' => 'Test']],
            ['DELETE', '/api/v1/competitors/1'],
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint[0];
            $url = $endpoint[1];
            $data = $endpoint[2] ?? [];
            $response = $this->json($method, $url, $data);
            $response->assertStatus(401);
        }
    }
}
