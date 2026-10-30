<?php

namespace Tests\Unit\Services\LocalSEO;

use App\Models\BusinessLocation;
use App\Services\LocalSEO\Contracts\GoogleOAuthServiceInterface;
use App\Services\LocalSEO\GoogleOAuthService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class GoogleOAuthServiceTest extends TestCase
{
    protected GoogleOAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Session::flush();
        Session::start();

        Config::set('app.url', 'https://seoindicator.test');
        Config::set('services.google.client_id', 'google-client-id');
        Config::set('services.google.client_secret', 'google-client-secret');
        Config::set('services.google.redirect', 'https://seoindicator.test/api/v1/locations/oauth/callback');

        $this->service = app(GoogleOAuthService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        Session::flush();

        parent::tearDown();
    }

    protected function invokeProtected(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }

    // =====================================================================
    // GOS-CLS: Class & Contract Tests
    // =====================================================================

    #[Test]
    public function gos_cls_001_service_class_exists(): void
    {
        $this->assertTrue(class_exists(GoogleOAuthService::class));
    }

    #[Test]
    public function gos_cls_002_service_implements_interface(): void
    {
        $this->assertInstanceOf(GoogleOAuthServiceInterface::class, $this->service);
    }

    #[Test]
    public function gos_cls_003_interface_exposes_controller_and_token_lifecycle_methods(): void
    {
        foreach ([
            'getAuthorizationUrl',
            'handleCallback',
            'disconnect',
            'disconnectLocation',
            'validateState',
            'refreshToken',
            'refreshAccessToken',
            'storeTokens',
            'getValidAccessToken',
            'isTokenValid',
        ] as $method) {
            $this->assertTrue(method_exists(GoogleOAuthServiceInterface::class, $method), "Missing interface method: {$method}");
        }
    }

    #[Test]
    public function gos_cls_004_service_keeps_token_exchange_helper_available_for_callback_flow(): void
    {
        $this->assertTrue(method_exists($this->service, 'exchangeCodeForTokens'));
    }

    // =====================================================================
    // GOS-URL: Authorization URL Tests
    // =====================================================================

    #[Test]
    public function gos_url_001_get_authorization_url_returns_google_oauth_url_with_required_parameters(): void
    {
        $result = $this->service->getAuthorizationUrl(42);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('authorization_url', $result);
        $this->assertArrayHasKey('state', $result);

        $query = [];
        parse_str((string) parse_url($result['authorization_url'], PHP_URL_QUERY), $query);

        $this->assertSame('https', parse_url($result['authorization_url'], PHP_URL_SCHEME));
        $this->assertSame('accounts.google.com', parse_url($result['authorization_url'], PHP_URL_HOST));
        $this->assertSame('google-client-id', $query['client_id'] ?? null);
        $this->assertSame('code', $query['response_type'] ?? null);
        $this->assertSame('offline', $query['access_type'] ?? null);
        $this->assertSame('consent', $query['prompt'] ?? null);
        $this->assertSame('https://www.googleapis.com/auth/business.manage', $query['scope'] ?? null);
        $this->assertSame('https://seoindicator.test/api/v1/locations/42/gbp-callback', $query['redirect_uri'] ?? null);
        $this->assertSame($result['state'], $query['state'] ?? null);
    }

    #[Test]
    public function gos_url_002_get_authorization_url_stores_state_in_session_for_csrf_validation(): void
    {
        $result = $this->service->getAuthorizationUrl(55);

        $this->assertSame($result['state'], Session::get('gbp_oauth_state'));
        $this->assertSame(55, Session::get('gbp_oauth_location_id'));
    }

    #[Test]
    public function gos_url_003_get_authorization_url_generates_a_unique_high_entropy_state(): void
    {
        $first = $this->service->getAuthorizationUrl(1);
        $second = $this->service->getAuthorizationUrl(1);

        $this->assertNotSame($first['state'], $second['state']);
        $this->assertGreaterThanOrEqual(32, strlen($first['state']));
    }

    // =====================================================================
    // GOS-STATE: State Validation Tests
    // =====================================================================

    #[Test]
    public function gos_state_001_validate_state_accepts_matching_session_state(): void
    {
        Session::put('gbp_oauth_state', 'state-123');

        $this->assertTrue($this->service->validateState('state-123'));
    }

    #[Test]
    public function gos_state_002_validate_state_rejects_missing_session_state(): void
    {
        Session::forget('gbp_oauth_state');

        $this->assertFalse($this->service->validateState('state-123'));
    }

    #[Test]
    public function gos_state_003_validate_state_rejects_mismatched_state(): void
    {
        Session::put('gbp_oauth_state', 'expected-state');

        $this->assertFalse($this->service->validateState('wrong-state'));
    }

    // =====================================================================
    // GOS-EXCH: Token Exchange Tests
    // =====================================================================

    #[Test]
    public function gos_exch_001_exchange_code_for_tokens_posts_authorization_code_grant_and_normalizes_payload(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token-123',
                'refresh_token' => 'refresh-token-456',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $tokens = $this->invokeProtected($this->service, 'exchangeCodeForTokens', ['oauth-code-123', 42]);

        $this->assertIsArray($tokens);
        $this->assertSame('access-token-123', $tokens['access_token'] ?? null);
        $this->assertSame('refresh-token-456', $tokens['refresh_token'] ?? null);
        $this->assertArrayHasKey('expires_at', $tokens);
        $this->assertTrue(Carbon::parse($tokens['expires_at'])->isFuture());

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return $request->url() === 'https://oauth2.googleapis.com/token'
                && ($data['grant_type'] ?? null) === 'authorization_code'
                && ($data['code'] ?? null) === 'oauth-code-123'
                && ($data['client_id'] ?? null) === 'google-client-id'
                && ($data['client_secret'] ?? null) === 'google-client-secret'
                && ($data['redirect_uri'] ?? null) === 'https://seoindicator.test/api/v1/locations/42/gbp-callback';
        });
    }

    #[Test]
    public function gos_exch_002_exchange_code_for_tokens_returns_null_on_token_endpoint_failure(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $tokens = $this->invokeProtected($this->service, 'exchangeCodeForTokens', ['bad-code', 42]);

        $this->assertNull($tokens);
    }

    #[Test]
    public function gos_exch_003_refresh_access_token_uses_refresh_grant_and_returns_normalized_payload(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-access-token',
                'expires_in' => 1800,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $tokens = $this->service->refreshAccessToken('stored-refresh-token');

        $this->assertIsArray($tokens);
        $this->assertSame('new-access-token', $tokens['access_token'] ?? null);
        $this->assertSame('stored-refresh-token', $tokens['refresh_token'] ?? null);
        $this->assertArrayHasKey('expires_at', $tokens);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return $request->url() === 'https://oauth2.googleapis.com/token'
                && ($data['grant_type'] ?? null) === 'refresh_token'
                && ($data['refresh_token'] ?? null) === 'stored-refresh-token';
        });
    }

    // =====================================================================
    // GOS-STOR: Token Storage Tests
    // =====================================================================

    #[Test]
    public function gos_stor_001_store_tokens_encrypts_json_payload_and_marks_location_connected(): void
    {
        $location = Mockery::mock(BusinessLocation::class)->makePartial();
        $location->shouldReceive('save')->once()->andReturn(true);

        $tokens = [
            'access_token' => 'plain-access-token',
            'refresh_token' => 'plain-refresh-token',
            'expires_at' => now()->addHour()->toISOString(),
        ];

        $this->service->storeTokens($location, $tokens);

        $this->assertIsString($location->gbp_tokens);
        $this->assertStringNotContainsString('plain-access-token', $location->gbp_tokens);
        $this->assertTrue($location->gbp_connected);
        $this->assertSame($tokens, json_decode(decrypt($location->gbp_tokens), true));
    }

    #[Test]
    public function gos_stor_002_store_tokens_keeps_refresh_token_payload_available_for_future_refreshes(): void
    {
        $location = Mockery::mock(BusinessLocation::class)->makePartial();
        $location->shouldReceive('save')->once()->andReturn(true);

        $this->service->storeTokens($location, [
            'access_token' => 'access-1',
            'refresh_token' => 'refresh-1',
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ]);

        $payload = json_decode(decrypt($location->gbp_tokens), true);

        $this->assertSame('refresh-1', $payload['refresh_token'] ?? null);
    }

    // =====================================================================
    // GOS-ACCESS: Valid Access Token Tests
    // =====================================================================

    #[Test]
    public function gos_access_001_get_valid_access_token_returns_current_token_when_not_expired(): void
    {
        $location = new BusinessLocation();
        $location->gbp_tokens = encrypt(json_encode([
            'access_token' => 'still-valid',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ]));

        $token = $this->service->getValidAccessToken($location);

        $this->assertSame('still-valid', $token);
    }

    #[Test]
    public function gos_access_002_get_valid_access_token_refreshes_expired_tokens_and_persists_the_new_payload(): void
    {
        $location = new BusinessLocation();
        $location->gbp_tokens = encrypt(json_encode([
            'access_token' => 'expired-access',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->subMinute()->toISOString(),
        ]));

        $service = Mockery::mock(GoogleOAuthService::class)->makePartial();
        $service->shouldReceive('refreshAccessToken')->once()->with('refresh-token')->andReturn([
            'access_token' => 'new-access',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addHour()->toISOString(),
        ]);
        $service->shouldReceive('storeTokens')->once()->with($location, Mockery::on(function (array $tokens) {
            return ($tokens['access_token'] ?? null) === 'new-access';
        }));

        $token = $service->getValidAccessToken($location);

        $this->assertSame('new-access', $token);
    }

    #[Test]
    public function gos_access_003_get_valid_access_token_returns_null_when_location_is_not_connected(): void
    {
        $location = new BusinessLocation();
        $location->gbp_tokens = null;

        $this->assertNull($this->service->getValidAccessToken($location));
    }

    #[Test]
    public function gos_access_004_get_valid_access_token_returns_null_when_refresh_fails(): void
    {
        $location = new BusinessLocation();
        $location->gbp_tokens = encrypt(json_encode([
            'access_token' => 'expired-access',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->subMinute()->toISOString(),
        ]));

        $service = Mockery::mock(GoogleOAuthService::class)->makePartial();
        $service->shouldReceive('refreshAccessToken')->once()->with('refresh-token')->andReturn(null);
        $service->shouldNotReceive('storeTokens');

        $this->assertNull($service->getValidAccessToken($location));
    }

    // =====================================================================
    // GOS-DISC: Disconnect Tests
    // =====================================================================

    #[Test]
    public function gos_disc_001_disconnect_location_revokes_google_token_and_clears_local_storage(): void
    {
        $location = Mockery::mock(BusinessLocation::class)->makePartial();
        $location->id = 77;
        $location->gbp_tokens = encrypt(json_encode([
            'access_token' => 'live-access-token',
            'refresh_token' => 'live-refresh-token',
            'expires_at' => now()->addHour()->toISOString(),
        ]));
        $location->gbp_connected = true;
        $location->shouldReceive('save')->once()->andReturn(true);

        $service = Mockery::mock(GoogleOAuthService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('revokeToken')->once()->with('live-access-token')->andReturn(true);

        $service->disconnectLocation($location);

        $this->assertNull($location->gbp_tokens);
        $this->assertFalse($location->gbp_connected);
    }

    #[Test]
    public function gos_disc_002_disconnect_location_is_idempotent_even_when_google_revocation_fails(): void
    {
        $location = Mockery::mock(BusinessLocation::class)->makePartial();
        $location->id = 88;
        $location->gbp_tokens = encrypt(json_encode([
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addHour()->toISOString(),
        ]));
        $location->gbp_connected = true;
        $location->shouldReceive('save')->once()->andReturn(true);

        $service = Mockery::mock(GoogleOAuthService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('revokeToken')->once()->with('access-token')->andThrow(new \RuntimeException('revocation failed'));

        $service->disconnectLocation($location);

        $this->assertNull($location->gbp_tokens);
        $this->assertFalse($location->gbp_connected);
    }
}
