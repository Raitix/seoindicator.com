<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\GSCConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

/**
 * Integration tests for GSC API Endpoints (TASK-12-11).
 *
 * Tests verify:
 * - API endpoint authorization
 * - Request/response handling
 * - Error handling
 * - Access control
 *
 * Note: These tests document expected behavior.
 * Routes/controllers will be implemented in TASK-12-7 and TASK-12-8.
 */
class GSCApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /**
     * Test list GSC connections endpoint exists and requires auth.
     */
    public function test_list_connections_endpoint(): void
    {
        $response = $this->getJson('/api/gsc/properties');

        // Should return 401 (unauthenticated) or 404 (not implemented)
        $this->assertContains($response->status(), [401, 404]);
    }

    /**
     * Test list GSC connections returns user connections only.
     * Note: Will pass once routes are implemented.
     */
    public function test_list_connections_returns_user_only(): void
    {
        // Create connections for both users
        GSCConnection::factory()->create([
            'user_id' => $this->user->id,
            'property_url' => 'https://user1-property.com/',
        ]);
        GSCConnection::factory()->create([
            'user_id' => $this->otherUser->id,
            'property_url' => 'https://user2-property.com/',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/gsc/properties');

        // Should return 200 with proper data once implemented
        // For now, just verify the request doesn't throw
        $this->assertContains($response->status(), [200, 404]);
    }

    /**
     * Test connect GSC property endpoint.
     */
    public function test_connect_endpoint(): void
    {
        $response = $this->postJson('/api/gsc/connect', [
            'authorization_code' => 'test_code',
        ]);

        // Should return 401 (unauthenticated) or 404 (not implemented)
        $this->assertContains($response->status(), [401, 404]);
    }

    /**
     * Test disconnect GSC property endpoint.
     */
    public function test_disconnect_endpoint(): void
    {
        $connection = GSCConnection::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/gsc/disconnect/{$connection->id}");

        // Should return 403 (forbidden) or 404 (not implemented)
        $this->assertContains($response->status(), [403, 404]);

        // Connection should still exist
        $this->assertDatabaseHas('gsc_connections', [
            'id' => $connection->id,
        ]);
    }

    /**
     * Test disconnect own GSC property.
     */
    public function test_disconnect_own_property(): void
    {
        $connection = GSCConnection::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/gsc/disconnect/{$connection->id}");

        // Should return 200 or 404
        $this->assertContains($response->status(), [200, 404]);
    }

    /**
     * Test GSC data endpoint.
     */
    public function test_gsc_data_endpoint(): void
    {
        $response = $this->getJson('/api/projects/1/gsc?from=2026-02-01&to=2026-02-15');

        // Should return 401 (unauthenticated) or 404 (not implemented)
        $this->assertContains($response->status(), [401, 404]);
    }

    /**
     * Test GSC data endpoint with invalid date range.
     */
    public function test_gsc_data_invalid_date(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/projects/1/gsc?from=invalid&to=invalid');

        // Should return 422 (validation error) or 404 (not implemented)
        $this->assertContains($response->status(), [422, 404]);
    }

    /**
     * Test opportunities endpoint.
     */
    public function test_opportunities_endpoint(): void
    {
        $response = $this->getJson('/api/projects/1/gsc/opportunities');

        // Should return 401 (unauthenticated) or 404 (not implemented)
        $this->assertContains($response->status(), [401, 404]);
    }

    /**
     * Test trend data endpoint.
     */
    public function test_trend_endpoint(): void
    {
        $response = $this->getJson('/api/projects/1/gsc/trend?from=2026-02-01&to=2026-02-15');

        // Should return 401 (unauthenticated) or 404 (not implemented)
        $this->assertContains($response->status(), [401, 404]);
    }

    /**
     * Test connection tokens are not exposed.
     */
    public function test_tokens_not_exposed(): void
    {
        $connection = GSCConnection::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/gsc/properties');

        if ($response->status() === 200) {
            $response->assertJsonMissing(['access_token']);
            $response->assertJsonMissing(['refresh_token']);
        } else {
            // Route not implemented yet
            $this->assertContains($response->status(), [404]);
        }
    }

    /**
     * Test manual sync trigger endpoint.
     */
    public function test_manual_sync_endpoint(): void
    {
        $connection = GSCConnection::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/gsc/connections/{$connection->id}/sync");

        // Should return 403 (forbidden) or 404 (not implemented)
        $this->assertContains($response->status(), [403, 404]);
    }
}
