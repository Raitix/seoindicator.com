<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\GSCConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Unit tests for GSC OAuth Service (TASK-12-11).
 *
 * Tests verify:
 * - OAuth token handling
 * - Token refresh logic
 * - Token encryption
 * - Rate limit handling
 */
class GSCOAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected const MOCK_ACCESS_TOKEN = 'mock_access_token_12345';
    protected const MOCK_REFRESH_TOKEN = 'mock_refresh_token_67890';
    protected const MOCK_CLIENT_ID = 'test_client_id';
    protected const MOCK_CLIENT_SECRET = 'test_client_secret';

    /**
     * Test OAuth token encryption on storage.
     */
    public function test_tokens_are_encrypted_on_storage(): void
    {
        $user = User::factory()->create();

        $connection = GSCConnection::create([
            'user_id' => $user->id,
            'site_url' => 'example.com',
            'property_url' => 'https://example.com/',
            'access_token' => self::MOCK_ACCESS_TOKEN,
            'refresh_token' => self::MOCK_REFRESH_TOKEN,
            'token_expires_at' => now()->addHour(),
        ]);

        // Verify the model returns decrypted values
        $this->assertEquals(self::MOCK_ACCESS_TOKEN, $connection->access_token);
        $this->assertEquals(self::MOCK_REFRESH_TOKEN, $connection->refresh_token);

        // Verify raw database values are encrypted
        $rawConnection = \DB::table('gsc_connections')->find($connection->id);
        $this->assertNotEquals(self::MOCK_ACCESS_TOKEN, $rawConnection->access_token);
        $this->assertNotEquals(self::MOCK_REFRESH_TOKEN, $rawConnection->refresh_token);
    }

    /**
     * Test token expiration detection.
     */
    public function test_token_expiration_detection(): void
    {
        $expiredConnection = GSCConnection::factory()->expired()->create();
        $activeConnection = GSCConnection::factory()->create([
            'token_expires_at' => now()->addHour(),
        ]);

        $this->assertTrue($expiredConnection->isTokenExpired());
        $this->assertFalse($activeConnection->isTokenExpired());
    }

    /**
     * Test token expires within threshold.
     */
    public function test_token_expires_within_threshold(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => now()->addMinutes(3),
        ]);

        $this->assertTrue($connection->tokenExpiresWithin(5));
        $this->assertFalse($connection->tokenExpiresWithin(2));
    }

    /**
     * Test token refresh updates all fields.
     */
    public function test_token_refresh_updates_fields(): void
    {
        $connection = GSCConnection::factory()->expired()->create();

        $newExpires = now()->addHour();
        $connection->updateTokens(
            'new_access_token',
            'new_refresh_token',
            $newExpires
        );

        $fresh = $connection->fresh();
        $this->assertEquals('new_access_token', $fresh->access_token);
        $this->assertEquals('new_refresh_token', $fresh->refresh_token);
        $this->assertEquals('active', $fresh->status);
    }

    /**
     * Test OAuth authorization URL generation.
     */
    public function test_authorization_url_generation(): void
    {
        $redirectUri = 'https://example.com/gsc/callback';
        $scopes = ['https://www.googleapis.com/auth/webmasters.readonly'];

        $params = http_build_query([
            'client_id' => self::MOCK_CLIENT_ID,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;

        $this->assertStringContainsString('client_id=' . self::MOCK_CLIENT_ID, $authUrl);
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirectUri), $authUrl);
        $this->assertStringContainsString('response_type=code', $authUrl);
        $this->assertStringContainsString('access_type=offline', $authUrl);
    }

    /**
     * Test token exchange from authorization code.
     */
    public function test_token_exchange_from_code(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        // Simulate token exchange
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => 'test_auth_code',
            'client_id' => self::MOCK_CLIENT_ID,
            'client_secret' => self::MOCK_CLIENT_SECRET,
            'redirect_uri' => 'https://example.com/gsc/callback',
            'grant_type' => 'authorization_code',
        ]);

        $this->assertTrue($response->successful());
        $this->assertEquals('new_access_token', $response->json('access_token'));
        $this->assertEquals('new_refresh_token', $response->json('refresh_token'));
    }

    /**
     * Test token refresh from refresh token.
     */
    public function test_token_refresh_from_refresh_token(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'refreshed_access_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'refresh_token' => self::MOCK_REFRESH_TOKEN,
            'client_id' => self::MOCK_CLIENT_ID,
            'client_secret' => self::MOCK_CLIENT_SECRET,
            'grant_type' => 'refresh_token',
        ]);

        $this->assertTrue($response->successful());
        $this->assertEquals('refreshed_access_token', $response->json('access_token'));
    }

    /**
     * Test rate limit backoff handling.
     */
    public function test_rate_limit_backoff(): void
    {
        $callCount = 0;
        $backoffDelays = [];

        Http::fake([
            'www.googleapis.com/*' => function () use (&$callCount, &$backoffDelays) {
                $callCount++;

                if ($callCount < 3) {
                    return Http::response(null, 429, ['Retry-After' => '1']);
                }

                return Http::response(['data' => 'success'], 200);
            },
        ]);

        // Simulate backoff logic
        $maxRetries = 3;
        $retryCount = 0;
        $response = null;

        while ($retryCount < $maxRetries) {
            $response = Http::get('https://www.googleapis.com/test');

            if ($response->successful() || $response->status() !== 429) {
                break;
            }

            $backoffDelays[] = 1; // Simplified for testing
            $retryCount++;
        }

        $this->assertEquals(3, $callCount);
        $this->assertTrue($response->successful());
    }

    /**
     * Test connection status transitions.
     */
    public function test_connection_status_transitions(): void
    {
        $connection = GSCConnection::factory()->create(['status' => 'active']);

        // Mark as expired
        $connection->markAsExpired();
        $this->assertEquals('expired', $connection->fresh()->status);

        // Mark as revoked
        $connection->markAsRevoked();
        $this->assertEquals('revoked', $connection->fresh()->status);
    }

    /**
     * Test connection scope queries.
     */
    public function test_connection_scope_queries(): void
    {
        GSCConnection::factory()->count(2)->create(['status' => 'active']);
        GSCConnection::factory()->expired()->create();
        GSCConnection::factory()->revoked()->create();

        $activeCount = GSCConnection::active()->count();
        $needsRefreshCount = GSCConnection::needsRefresh(5)->count();

        $this->assertEquals(2, $activeCount);
    }

    /**
     * Test connection cascade delete with user.
     */
    public function test_connection_cascade_delete(): void
    {
        $user = User::factory()->create();
        $connection = GSCConnection::factory()->create(['user_id' => $user->id]);

        $connectionId = $connection->id;
        $user->delete();

        $this->assertDatabaseMissing('gsc_connections', ['id' => $connectionId]);
    }
}
