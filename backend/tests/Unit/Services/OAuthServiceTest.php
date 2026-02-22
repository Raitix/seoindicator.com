<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\GSCConnection;
use App\Models\User;
use App\Services\OAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * Unit tests for OAuthService.
 *
 * Tests cover:
 * - Authorization URL generation
 * - Redirect URI validation
 * - Token exchange
 * - Token refresh
 * - Token revocation
 * - Error handling
 */
class OAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected const TEST_CLIENT_ID = 'test_client_id_12345';
    protected const TEST_CLIENT_SECRET = 'test_client_secret_67890';
    protected const TEST_REDIRECT_URI = 'http://localhost:3000/gsc/callback';

    protected function setUp(): void
    {
        parent::setUp();

        // Configure Google OAuth for testing
        Config::set('services.google.client_id', self::TEST_CLIENT_ID);
        Config::set('services.google.client_secret', self::TEST_CLIENT_SECRET);
        Config::set('services.google.redirect_uris', [self::TEST_REDIRECT_URI]);
    }

    // ============================================
    // Authorization URL Tests
    // ============================================

    public function test_get_authorization_url_generates_valid_url(): void
    {
        $service = app(OAuthService::class);

        $url = $service->getAuthorizationUrl(self::TEST_REDIRECT_URI);

        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', $url);
        $this->assertStringContainsString('client_id=' . self::TEST_CLIENT_ID, $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode(self::TEST_REDIRECT_URI), $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('access_type=offline', $url);
        $this->assertStringContainsString('prompt=consent', $url);
    }

    public function test_get_authorization_url_includes_gsc_scope(): void
    {
        $service = app(OAuthService::class);

        $url = $service->getAuthorizationUrl(self::TEST_REDIRECT_URI);

        $expectedScope = urlencode('https://www.googleapis.com/auth/webmasters.readonly');
        $this->assertStringContainsString('scope=' . $expectedScope, $url);
    }

    public function test_get_authorization_url_includes_state_parameter(): void
    {
        $service = app(OAuthService::class);

        $url = $service->getAuthorizationUrl(self::TEST_REDIRECT_URI);

        $this->assertMatchesRegularExpression('/state=[a-zA-Z0-9]{40}/', $url);
    }

    public function test_get_authorization_url_uses_custom_state(): void
    {
        $service = app(OAuthService::class);
        $customState = 'my_custom_state_value';

        $url = $service->getAuthorizationUrl(self::TEST_REDIRECT_URI, $customState);

        $this->assertStringContainsString('state=' . $customState, $url);
    }

    public function test_get_authorization_url_throws_for_invalid_redirect_uri(): void
    {
        $service = app(OAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redirect URI not allowed');

        $service->getAuthorizationUrl('https://malicious-site.com/callback');
    }

    // ============================================
    // Redirect URI Validation Tests
    // ============================================

    public function test_validate_redirect_uri_accepts_configured_uri(): void
    {
        $service = app(OAuthService::class);

        $validUris = $service->getValidRedirectUris();

        $this->assertContains(self::TEST_REDIRECT_URI, $validUris);
    }

    public function test_validate_redirect_uri_accepts_localhost_in_development(): void
    {
        $service = app(OAuthService::class);

        $validUris = $service->getValidRedirectUris();

        // Development URIs should be added in local environment
        $this->assertContains('http://localhost:3000/gsc/callback', $validUris);
        $this->assertContains('http://localhost:8000/gsc/callback', $validUris);
    }

    public function test_validate_redirect_uri_rejects_malformed_uri(): void
    {
        $service = app(OAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid redirect URI format');

        $service->getAuthorizationUrl('not-a-valid-uri');
    }

    // ============================================
    // Token Exchange Tests
    // ============================================

    public function test_handle_callback_creates_connection(): void
    {
        $user = User::factory()->create();
        $propertyUrl = 'https://example.com/';

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test_access_token',
                'refresh_token' => 'test_refresh_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $service = app(OAuthService::class);
        $connection = $service->handleCallback('auth_code_123', self::TEST_REDIRECT_URI, $user, $propertyUrl);

        $this->assertInstanceOf(GSCConnection::class, $connection);
        $this->assertEquals($user->id, $connection->user_id);
        $this->assertEquals($propertyUrl, $connection->property_url);
        $this->assertEquals('example.com', $connection->site_url);
        $this->assertEquals(GSCConnection::STATUS_ACTIVE, $connection->status);
    }

    public function test_handle_callback_updates_existing_connection(): void
    {
        $user = User::factory()->create();
        $propertyUrl = 'https://example.com/';

        // Create existing connection
        $existingConnection = GSCConnection::factory()->create([
            'user_id' => $user->id,
            'property_url' => $propertyUrl,
            'access_token' => 'old_token',
            'refresh_token' => 'old_refresh',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $service = app(OAuthService::class);
        $connection = $service->handleCallback('auth_code_123', self::TEST_REDIRECT_URI, $user, $propertyUrl);

        $this->assertEquals($existingConnection->id, $connection->id);
        $this->assertNotEquals('old_token', $connection->access_token);
    }

    public function test_handle_callback_throws_on_missing_tokens(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'token_type' => 'Bearer',
                // Missing access_token and refresh_token
            ], 200),
        ]);

        $service = app(OAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid token response');

        $service->handleCallback('auth_code_123', self::TEST_REDIRECT_URI, $user, 'https://example.com/');
    }

    public function test_handle_callback_throws_on_invalid_grant(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid authorization code.',
            ], 400),
        ]);

        $service = app(OAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Token exchange failed');

        $service->handleCallback('invalid_code', self::TEST_REDIRECT_URI, $user, 'https://example.com/');
    }

    // ============================================
    // Token Refresh Tests
    // ============================================

    public function test_refresh_access_token_updates_connection(): void
    {
        $connection = GSCConnection::factory()->expired()->create([
            'refresh_token' => 'original_refresh_token',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new_access_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $service = app(OAuthService::class);
        $result = $service->refreshAccessToken($connection);

        $this->assertTrue($result);

        $connection->refresh();
        $this->assertEquals('active', $connection->status);
        $this->assertFalse($connection->isTokenExpired());
    }

    public function test_refresh_access_token_throws_without_refresh_token(): void
    {
        $connection = GSCConnection::factory()->create([
            'refresh_token' => null,
        ]);

        $service = app(OAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no refresh token available');

        $service->refreshAccessToken($connection);
    }

    public function test_refresh_access_token_throws_for_inactive_connection(): void
    {
        $connection = GSCConnection::factory()->create([
            'status' => GSCConnection::STATUS_REVOKED,
            'refresh_token' => 'test_refresh',
        ]);

        $service = app(OAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('connection is not active');

        $service->refreshAccessToken($connection);
    }

    public function test_refresh_access_token_marks_expired_on_invalid_grant(): void
    {
        $connection = GSCConnection::factory()->create([
            'status' => GSCConnection::STATUS_ACTIVE,
            'refresh_token' => 'test_refresh',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been revoked.',
            ], 400),
        ]);

        $service = app(OAuthService::class);

        $this->expectException(RuntimeException::class);
        $service->refreshAccessToken($connection);

        $connection->refresh();
        $this->assertEquals(GSCConnection::STATUS_EXPIRED, $connection->status);
    }

    // ============================================
    // Ensure Valid Token Tests
    // ============================================

    public function test_ensure_valid_token_returns_true_for_valid_token(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => now()->addHour(),
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $service = app(OAuthService::class);
        $result = $service->ensureValidToken($connection);

        $this->assertTrue($result);
    }

    public function test_ensure_valid_token_refreshes_expiring_token(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => now()->addMinutes(3), // Within 5-minute buffer
            'status' => GSCConnection::STATUS_ACTIVE,
            'refresh_token' => 'test_refresh',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'refreshed_token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = app(OAuthService::class);
        $result = $service->ensureValidToken($connection);

        $this->assertTrue($result);
        Http::assertSentCount(1);
    }

    public function test_ensure_valid_token_returns_false_for_inactive_connection(): void
    {
        $connection = GSCConnection::factory()->create([
            'status' => GSCConnection::STATUS_REVOKED,
        ]);

        $service = app(OAuthService::class);
        $result = $service->ensureValidToken($connection);

        $this->assertFalse($result);
    }

    // ============================================
    // Token Revocation Tests
    // ============================================

    public function test_revoke_access_marks_connection_revoked(): void
    {
        $connection = GSCConnection::factory()->create([
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response(null, 200),
        ]);

        $service = app(OAuthService::class);
        $result = $service->revokeAccess($connection);

        $this->assertTrue($result);
        $this->assertEquals(GSCConnection::STATUS_REVOKED, $connection->fresh()->status);
    }

    public function test_revoke_access_handles_already_revoked_token(): void
    {
        $connection = GSCConnection::factory()->create([
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([
                'error' => 'invalid_token',
            ], 400),
        ]);

        $service = app(OAuthService::class);
        $result = $service->revokeAccess($connection);

        // Should still mark as revoked locally
        $this->assertTrue($result);
        $this->assertEquals(GSCConnection::STATUS_REVOKED, $connection->fresh()->status);
    }

    public function test_revoke_access_returns_false_on_error(): void
    {
        $connection = GSCConnection::factory()->create([
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([
                'error' => 'server_error',
            ], 500),
        ]);

        $service = app(OAuthService::class);
        $result = $service->revokeAccess($connection);

        $this->assertFalse($result);
        $this->assertEquals(GSCConnection::STATUS_ACTIVE, $connection->fresh()->status);
    }

    public function test_revoke_access_handles_empty_token(): void
    {
        $connection = GSCConnection::factory()->create([
            'status' => GSCConnection::STATUS_ACTIVE,
            'access_token' => null,
        ]);

        $service = app(OAuthService::class);
        $result = $service->revokeAccess($connection);

        $this->assertTrue($result);
        $this->assertEquals(GSCConnection::STATUS_REVOKED, $connection->fresh()->status);
    }

    // ============================================
    // Needs Refresh Tests
    // ============================================

    public function test_needs_refresh_returns_false_for_valid_token(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => now()->addHour(),
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $service = app(OAuthService::class);

        $this->assertFalse($service->needsRefresh($connection));
    }

    public function test_needs_refresh_returns_true_for_expiring_token(): void
    {
        $connection = GSCConnection::factory()->create([
            'token_expires_at' => now()->addMinutes(3), // Within 5-minute buffer
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $service = app(OAuthService::class);

        $this->assertTrue($service->needsRefresh($connection));
    }

    public function test_needs_refresh_returns_true_for_expired_token(): void
    {
        $connection = GSCConnection::factory()->expired()->create([
            'status' => GSCConnection::STATUS_ACTIVE,
        ]);

        $service = app(OAuthService::class);

        $this->assertTrue($service->needsRefresh($connection));
    }

    public function test_needs_refresh_returns_false_for_inactive_connection(): void
    {
        $connection = GSCConnection::factory()->expired()->create([
            'status' => GSCConnection::STATUS_REVOKED,
        ]);

        $service = app(OAuthService::class);

        $this->assertFalse($service->needsRefresh($connection));
    }

    // ============================================
    // Configuration Tests
    // ============================================

    public function test_throws_when_client_id_not_configured(): void
    {
        Config::set('services.google.client_id', null);

        $service = app(OAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('client ID is not configured');

        $service->getAuthorizationUrl(self::TEST_REDIRECT_URI);
    }

    public function test_throws_when_client_secret_not_configured(): void
    {
        Config::set('services.google.client_secret', null);

        $user = User::factory()->create();

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test',
                'refresh_token' => 'test',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = app(OAuthService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('client secret is not configured');

        $service->handleCallback('code', self::TEST_REDIRECT_URI, $user, 'https://example.com/');
    }

    // ============================================
    // Site URL Extraction Tests
    // ============================================

    public function test_extracts_domain_from_https_url(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test',
                'refresh_token' => 'test',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = app(OAuthService::class);
        $connection = $service->handleCallback('code', self::TEST_REDIRECT_URI, $user, 'https://www.example.com/');

        $this->assertEquals('www.example.com', $connection->site_url);
    }

    public function test_extracts_domain_from_domain_property(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test',
                'refresh_token' => 'test',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = app(OAuthService::class);
        $connection = $service->handleCallback('code', self::TEST_REDIRECT_URI, $user, 'sc-domain:example.com');

        $this->assertEquals('example.com', $connection->site_url);
    }
}
