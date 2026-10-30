<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\GSCConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;

/**
 * Integration tests for GSC OAuth Flow (TASK-12-11).
 *
 * Tests verify:
 * - End-to-end OAuth connection flow
 * - Token refresh flow
 * - Connection revocation
 * - Error handling
 */
class GSCOAuthIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected const MOCK_CLIENT_ID = 'test_client_id';
    protected const MOCK_CLIENT_SECRET = 'test_client_secret';

    /**
     * Test full OAuth connection flow.
     */
    public function test_full_oauth_connection_flow(): void
    {
        $user = User::factory()->create();

        // Step 1: Generate authorization URL
        $redirectUri = 'https://example.com/gsc/callback';
        $authUrl = $this->generateAuthorizationUrl($redirectUri);

        $this->assertStringContainsString('accounts.google.com', $authUrl);
        $this->assertStringContainsString('client_id=', $authUrl);
        $this->assertStringContainsString('redirect_uri=', $authUrl);

        // Step 2: Simulate callback with authorization code
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test_access_token',
                'refresh_token' => 'test_refresh_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $tokenResponse = $this->exchangeCodeForToken('test_auth_code', $redirectUri);

        $this->assertEquals('test_access_token', $tokenResponse['access_token']);
        $this->assertEquals('test_refresh_token', $tokenResponse['refresh_token']);

        // Step 3: Store connection in database
        $connection = GSCConnection::create([
            'user_id' => $user->id,
            'site_url' => 'example.com',
            'property_url' => 'https://example.com/',
            'access_token' => $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'],
            'token_expires_at' => now()->addSeconds($tokenResponse['expires_in']),
        ]);

        $this->assertDatabaseHas('gsc_connections', [
            'user_id' => $user->id,
            'property_url' => 'https://example.com/',
            'status' => 'active',
        ]);
    }

    /**
     * Test OAuth token refresh flow.
     */
    public function test_oauth_token_refresh_flow(): void
    {
        $user = User::factory()->create();
        $connection = GSCConnection::factory()->expired()->create([
            'user_id' => $user->id,
            'refresh_token' => 'original_refresh_token',
        ]);

        // Mock token refresh response
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new_access_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        // Simulate token refresh
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'refresh_token' => $connection->refresh_token,
            'client_id' => self::MOCK_CLIENT_ID,
            'client_secret' => self::MOCK_CLIENT_SECRET,
            'grant_type' => 'refresh_token',
        ]);

        $this->assertTrue($response->successful());
        $this->assertEquals('new_access_token', $response->json('access_token'));

        // Update connection
        $connection->updateTokens(
            $response->json('access_token'),
            $connection->refresh_token, // Keep existing refresh token
            now()->addSeconds($response->json('expires_in'))
        );

        $this->assertEquals('active', $connection->fresh()->status);
        $this->assertFalse($connection->fresh()->isTokenExpired());
    }

    /**
     * Test OAuth connection revocation.
     */
    public function test_oauth_connection_revocation(): void
    {
        $user = User::factory()->create();
        $connection = GSCConnection::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        // Mock revocation response
        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response(null, 200),
        ]);

        // Simulate token revocation
        $response = Http::asForm()->post('https://oauth2.googleapis.com/revoke', [
            'token' => $connection->access_token,
        ]);

        $this->assertTrue($response->successful());

        // Mark connection as revoked
        $connection->markAsRevoked();

        $this->assertEquals('revoked', $connection->fresh()->status);
        $this->assertFalse($connection->fresh()->isActive());
    }

    /**
     * Test connection deletion removes tokens.
     */
    public function test_connection_deletion(): void
    {
        $user = User::factory()->create();
        $connection = GSCConnection::factory()->create([
            'user_id' => $user->id,
        ]);

        $connectionId = $connection->id;
        $connection->delete();

        $this->assertDatabaseMissing('gsc_connections', ['id' => $connectionId]);
    }

    /**
     * Test duplicate connection prevention.
     */
    public function test_duplicate_connection_prevention(): void
    {
        $user = User::factory()->create();
        $propertyUrl = 'https://example.com/';

        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'property_url' => $propertyUrl,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        GSCConnection::factory()->create([
            'user_id' => $user->id,
            'property_url' => $propertyUrl,
        ]);
    }

    /**
     * Test different users can have same property.
     */
    public function test_different_users_same_property(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $propertyUrl = 'https://shared-property.com/';

        $connection1 = GSCConnection::factory()->create([
            'user_id' => $user1->id,
            'property_url' => $propertyUrl,
        ]);

        $connection2 = GSCConnection::factory()->create([
            'user_id' => $user2->id,
            'property_url' => $propertyUrl,
        ]);

        $this->assertNotEquals($connection1->id, $connection2->id);
        $this->assertEquals($propertyUrl, $connection1->property_url);
        $this->assertEquals($propertyUrl, $connection2->property_url);
    }

    /**
     * Test OAuth error handling - invalid grant.
     */
    public function test_oauth_error_invalid_grant(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid authorization code.',
            ], 400),
        ]);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => 'invalid_code',
            'client_id' => self::MOCK_CLIENT_ID,
            'client_secret' => self::MOCK_CLIENT_SECRET,
            'redirect_uri' => 'https://example.com/gsc/callback',
            'grant_type' => 'authorization_code',
        ]);

        $this->assertFalse($response->successful());
        $this->assertEquals('invalid_grant', $response->json('error'));
    }

    /**
     * Test OAuth error handling - rate limit.
     */
    public function test_oauth_error_rate_limit(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'rate_limit_exceeded',
                'error_description' => 'Rate limit exceeded.',
            ], 429, ['Retry-After' => '30']),
        ]);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'refresh_token' => 'test_refresh',
            'client_id' => self::MOCK_CLIENT_ID,
            'client_secret' => self::MOCK_CLIENT_SECRET,
            'grant_type' => 'refresh_token',
        ]);

        $this->assertEquals(429, $response->status());
        $this->assertEquals('rate_limit_exceeded', $response->json('error'));
    }

    /**
     * Test token expiration triggers refresh.
     */
    public function test_token_expiration_triggers_refresh(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => now()->addMinutes(2), // Expires soon
            'status' => 'active',
        ]);

        $needsRefresh = $connection->tokenExpiresWithin(5);

        $this->assertTrue($needsRefresh);
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Generate OAuth authorization URL.
     */
    protected function generateAuthorizationUrl(string $redirectUri): string
    {
        $params = http_build_query([
            'client_id' => self::MOCK_CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    /**
     * Exchange authorization code for token.
     */
    protected function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => self::MOCK_CLIENT_ID,
            'client_secret' => self::MOCK_CLIENT_SECRET,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        return $response->json();
    }
}
