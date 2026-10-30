<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\GSCConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Unit tests for GSCConnection model (TASK-12-1).
 *
 * Tests verify:
 * - Model relationships
 * - Token encryption
 * - Status management
 * - Token expiration helpers
 * - Scopes
 */
class GSCConnectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test connection belongs to a user.
     */
    public function test_connection_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $connection = GSCConnection::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $connection->user);
        $this->assertEquals($user->id, $connection->user->id);
    }

    /**
     * Test token expiration check - not expired.
     */
    public function test_is_token_expired_false(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($connection->isTokenExpired());
    }

    /**
     * Test token expiration check - expired.
     */
    public function test_is_token_expired_true(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => now()->subMinute(),
        ]);

        $this->assertTrue($connection->isTokenExpired());
    }

    /**
     * Test token expires within minutes.
     */
    public function test_token_expires_within(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => now()->addMinutes(3),
        ]);

        $this->assertTrue($connection->tokenExpiresWithin(5));
        $this->assertFalse($connection->tokenExpiresWithin(2));
    }

    /**
     * Test is_active helper.
     */
    public function test_is_active(): void
    {
        $activeConnection = GSCConnection::factory()->create(['status' => 'active']);
        $expiredConnection = GSCConnection::factory()->create(['status' => 'expired']);
        $revokedConnection = GSCConnection::factory()->create(['status' => 'revoked']);

        $this->assertTrue($activeConnection->isActive());
        $this->assertFalse($expiredConnection->isActive());
        $this->assertFalse($revokedConnection->isActive());
    }

    /**
     * Test mark as expired.
     */
    public function test_mark_as_expired(): void
    {
        $connection = GSCConnection::factory()->create(['status' => 'active']);
        $connection->markAsExpired();

        $this->assertEquals('expired', $connection->fresh()->status);
    }

    /**
     * Test mark as revoked.
     */
    public function test_mark_as_revoked(): void
    {
        $connection = GSCConnection::factory()->create(['status' => 'active']);
        $connection->markAsRevoked();

        $this->assertEquals('revoked', $connection->fresh()->status);
    }

    /**
     * Test mark as synced.
     */
    public function test_mark_as_synced(): void
    {
        $connection = GSCConnection::factory()->create(['last_synced_at' => null]);
        $connection->markAsSynced();

        $this->assertNotNull($connection->fresh()->last_synced_at);
    }

    /**
     * Test update tokens.
     */
    public function test_update_tokens(): void
    {
        $connection = GSCConnection::factory()->create([
            'access_token' => 'old_access',
            'refresh_token' => 'old_refresh',
            'token_expires_at' => now()->subHour(),
            'status' => 'expired',
        ]);

        $newExpires = now()->addHour();
        $connection->updateTokens('new_access', 'new_refresh', $newExpires);

        $fresh = $connection->fresh();
        $this->assertEquals('new_access', $fresh->access_token);
        $this->assertEquals('new_refresh', $fresh->refresh_token);
        $this->assertEquals('active', $fresh->status);
    }

    /**
     * Test active scope.
     */
    public function test_scope_active(): void
    {
        GSCConnection::factory()->count(2)->create(['status' => 'active']);
        GSCConnection::factory()->create(['status' => 'expired']);
        GSCConnection::factory()->create(['status' => 'revoked']);

        $activeConnections = GSCConnection::active()->get();

        $this->assertCount(2, $activeConnections);
    }

    /**
     * Test needs refresh scope.
     */
    public function test_scope_needs_refresh(): void
    {
        // Expires in 3 minutes - needs refresh
        GSCConnection::factory()->create([
            'status' => 'active',
            'token_expires_at' => now()->addMinutes(3),
        ]);

        // Expires in 10 minutes - doesn't need refresh yet
        GSCConnection::factory()->create([
            'status' => 'active',
            'token_expires_at' => now()->addMinutes(10),
        ]);

        // Expired but still active - should be included
        GSCConnection::factory()->create([
            'status' => 'active',
            'token_expires_at' => now()->subMinutes(5),
        ]);

        $needsRefresh = GSCConnection::needsRefresh(5)->get();

        $this->assertCount(2, $needsRefresh);
    }

    /**
     * Test ready for sync scope.
     */
    public function test_scope_ready_for_sync(): void
    {
        GSCConnection::factory()->create(['status' => 'active']);
        GSCConnection::factory()->create(['status' => 'active']);
        GSCConnection::factory()->create(['status' => 'expired']);

        $readyForSync = GSCConnection::readyForSync()->get();

        $this->assertCount(2, $readyForSync);
    }

    /**
     * Test tokens are hidden in array/json.
     */
    public function test_tokens_hidden_in_array(): void
    {
        $connection = GSCConnection::factory()->create();

        $array = $connection->toArray();

        $this->assertArrayNotHasKey('access_token', $array);
        $this->assertArrayNotHasKey('refresh_token', $array);
    }

    /**
     * Test carbon casting for datetime fields.
     */
    public function test_datetime_casting(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => '2026-03-01 12:00:00',
            'last_synced_at' => '2026-02-28 10:30:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $connection->token_expires_at);
        $this->assertInstanceOf(Carbon::class, $connection->last_synced_at);
    }

    /**
     * Test mass assignment of fillable attributes.
     */
    public function test_mass_assignment(): void
    {
        $user = User::factory()->create();
        $expiresAt = now()->addHour();

        $connection = GSCConnection::create([
            'user_id' => $user->id,
            'site_url' => 'test-site.com',
            'property_url' => 'https://test-site.com/',
            'access_token' => 'test_access',
            'refresh_token' => 'test_refresh',
            'token_expires_at' => $expiresAt,
            'status' => 'active',
            'last_synced_at' => now(),
        ]);

        $this->assertDatabaseHas('gsc_connections', [
            'id' => $connection->id,
            'user_id' => $user->id,
            'site_url' => 'test-site.com',
            'property_url' => 'https://test-site.com/',
        ]);
    }

    /**
     * Test factory states.
     */
    public function test_factory_states(): void
    {
        // Expired state
        $expired = GSCConnection::factory()->expired()->make();
        $this->assertTrue($expired->token_expires_at->isPast());
        $this->assertEquals('expired', $expired->status);

        // Revoked state
        $revoked = GSCConnection::factory()->revoked()->make();
        $this->assertEquals('revoked', $revoked->status);

        // Recently synced state
        $synced = GSCConnection::factory()->recentlySynced()->make();
        $this->assertNotNull($synced->last_synced_at);
        $this->assertLessThanOrEqual(31, $synced->last_synced_at->diffInMinutes(now()));

        // Expires soon state
        $expiresSoon = GSCConnection::factory()->expiresSoon()->make();
        $this->assertLessThanOrEqual(4, $expiresSoon->token_expires_at->diffInMinutes(now()));
    }
}
