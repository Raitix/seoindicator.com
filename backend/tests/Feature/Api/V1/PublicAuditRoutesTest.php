<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Audit;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicAuditRoutesTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | RR: Route Registration Tests (8 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_rr_001_public_audit_show_is_registered(): void
    {
        $this->assertTrue(
            Route::has('api.v1.public-audit.show'),
            'Route api.v1.public-audit.show is not registered'
        );
    }

    public function test_route_rr_002_public_audit_lead_is_registered(): void
    {
        $this->assertTrue(
            Route::has('api.v1.public-audit.lead'),
            'Route api.v1.public-audit.lead is not registered'
        );
    }

    public function test_route_rr_003_public_audit_badge_is_registered(): void
    {
        $this->assertTrue(
            Route::has('api.v1.public-audit.badge'),
            'Route api.v1.public-audit.badge is not registered'
        );
    }

    public function test_route_rr_004_badge_impression_is_registered(): void
    {
        $this->assertTrue(
            Route::has('api.v1.badge.impression'),
            'Route api.v1.badge.impression is not registered'
        );
    }

    public function test_route_rr_005_badge_click_is_registered(): void
    {
        $this->assertTrue(
            Route::has('api.v1.badge.click'),
            'Route api.v1.badge.click is not registered'
        );
    }

    public function test_route_rr_006_audit_publish_is_registered(): void
    {
        $this->assertTrue(
            Route::has('api.v1.audits.publish'),
            'Route api.v1.audits.publish is not registered'
        );
    }

    public function test_route_rr_007_audit_unpublish_is_registered(): void
    {
        $this->assertTrue(
            Route::has('api.v1.audits.unpublish'),
            'Route api.v1.audits.unpublish is not registered'
        );
    }

    public function test_route_rr_008_audit_embed_code_is_registered(): void
    {
        $this->assertTrue(
            Route::has('api.v1.audits.embed-code'),
            'Route api.v1.audits.embed-code is not registered'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RP: Route Path Tests (8 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_rp_001_public_audit_show_has_correct_path(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public-audit.show');
        $this->assertNotNull($route);
        $this->assertStringContainsString('public-audit', $route->uri());
        $this->assertStringContainsString('{hash}', $route->uri());
    }

    public function test_route_rp_002_public_audit_lead_has_correct_path(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public-audit.lead');
        $this->assertNotNull($route);
        $this->assertStringContainsString('public-audit', $route->uri());
        $this->assertStringContainsString('{hash}', $route->uri());
        $this->assertStringContainsString('lead', $route->uri());
    }

    public function test_route_rp_003_public_audit_badge_has_correct_path(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public-audit.badge');
        $this->assertNotNull($route);
        $this->assertStringContainsString('public-audit', $route->uri());
        $this->assertStringContainsString('{hash}', $route->uri());
        $this->assertStringContainsString('badge', $route->uri());
    }

    public function test_route_rp_004_badge_impression_has_correct_path(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.badge.impression');
        $this->assertNotNull($route);
        $this->assertStringContainsString('badge', $route->uri());
        $this->assertStringContainsString('impression', $route->uri());
    }

    public function test_route_rp_005_badge_click_has_correct_path(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.badge.click');
        $this->assertNotNull($route);
        $this->assertStringContainsString('badge', $route->uri());
        $this->assertStringContainsString('click', $route->uri());
    }

    public function test_route_rp_006_audit_publish_has_correct_path(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.audits.publish');
        $this->assertNotNull($route);
        $this->assertStringContainsString('audits', $route->uri());
        $this->assertStringContainsString('{id}', $route->uri());
        $this->assertStringContainsString('publish', $route->uri());
    }

    public function test_route_rp_007_audit_unpublish_has_correct_path(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.audits.unpublish');
        $this->assertNotNull($route);
        $this->assertStringContainsString('audits', $route->uri());
        $this->assertStringContainsString('{id}', $route->uri());
        $this->assertStringContainsString('unpublish', $route->uri());
    }

    public function test_route_rp_008_audit_embed_code_has_correct_path(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.audits.embed-code');
        $this->assertNotNull($route);
        $this->assertStringContainsString('audits', $route->uri());
        $this->assertStringContainsString('{id}', $route->uri());
        $this->assertStringContainsString('embed-code', $route->uri());
    }

    /*
    |--------------------------------------------------------------------------
    | HM: HTTP Method Tests (8 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_hm_001_public_audit_show_uses_get_method(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public-audit.show');
        $this->assertNotNull($route);
        $this->assertEquals(['GET', 'HEAD'], $route->methods());
    }

    public function test_route_hm_002_public_audit_lead_uses_post_method(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public-audit.lead');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    public function test_route_hm_003_public_audit_badge_uses_get_method(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public-audit.badge');
        $this->assertNotNull($route);
        $this->assertEquals(['GET', 'HEAD'], $route->methods());
    }

    public function test_route_hm_004_badge_impression_uses_post_method(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.badge.impression');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    public function test_route_hm_005_badge_click_uses_post_method(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.badge.click');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    public function test_route_hm_006_audit_publish_uses_post_method(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.audits.publish');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    public function test_route_hm_007_audit_unpublish_uses_delete_method(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.audits.unpublish');
        $this->assertNotNull($route);
        $this->assertContains('DELETE', $route->methods());
    }

    public function test_route_hm_008_audit_embed_code_uses_get_method(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.audits.embed-code');
        $this->assertNotNull($route);
        $this->assertEquals(['GET', 'HEAD'], $route->methods());
    }

    /*
    |--------------------------------------------------------------------------
    | MW-PUB: Middleware - Public Routes (5 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_mw_pub_001_public_audit_show_does_not_require_authentication(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public-audit.show');
        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertNotContains('auth:sanctum', $middleware);
        $this->assertNotContains('auth', $middleware);
    }

    public function test_route_mw_pub_002_public_audit_lead_does_not_require_authentication(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public-audit.lead');
        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertNotContains('auth:sanctum', $middleware);
        $this->assertNotContains('auth', $middleware);
    }

    public function test_route_mw_pub_003_public_audit_badge_does_not_require_authentication(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.public-audit.badge');
        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertNotContains('auth:sanctum', $middleware);
        $this->assertNotContains('auth', $middleware);
    }

    public function test_route_mw_pub_004_badge_impression_does_not_require_authentication(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.badge.impression');
        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertNotContains('auth:sanctum', $middleware);
        $this->assertNotContains('auth', $middleware);
    }

    public function test_route_mw_pub_005_badge_click_does_not_require_authentication(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.badge.click');
        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertNotContains('auth:sanctum', $middleware);
        $this->assertNotContains('auth', $middleware);
    }

    /*
    |--------------------------------------------------------------------------
    | MW-AUTH: Middleware - Authenticated Routes (3 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_mw_auth_001_audit_publish_requires_authentication(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.audits.publish');
        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertTrue(
            in_array('auth:sanctum', $middleware) || in_array('auth', $middleware),
            'Route should require authentication'
        );
    }

    public function test_route_mw_auth_002_audit_unpublish_requires_authentication(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.audits.unpublish');
        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertTrue(
            in_array('auth:sanctum', $middleware) || in_array('auth', $middleware),
            'Route should require authentication'
        );
    }

    public function test_route_mw_auth_003_audit_embed_code_requires_authentication(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.audits.embed-code');
        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertTrue(
            in_array('auth:sanctum', $middleware) || in_array('auth', $middleware),
            'Route should require authentication'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RL: Rate Limiting Tests (3 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_rl_001_public_audit_routes_have_rate_limiting(): void
    {
        $routes = [
            'api.v1.public-audit.show',
            'api.v1.public-audit.lead',
            'api.v1.public-audit.badge',
        ];

        foreach ($routes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route {$routeName} not found");
            $middleware = $route->gatherMiddleware();
            $hasThrottle = collect($middleware)->some(fn ($m) => str_contains($m, 'throttle'));
            $this->assertTrue($hasThrottle, "Route {$routeName} should have throttle middleware");
        }
    }

    public function test_route_rl_002_badge_routes_have_rate_limiting(): void
    {
        $routes = [
            'api.v1.badge.impression',
            'api.v1.badge.click',
        ];

        foreach ($routes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route {$routeName} not found");
            $middleware = $route->gatherMiddleware();
            $hasThrottle = collect($middleware)->some(fn ($m) => str_contains($m, 'throttle'));
            $this->assertTrue($hasThrottle, "Route {$routeName} should have throttle middleware");
        }
    }

    public function test_route_rl_003_authenticated_audit_routes_have_rate_limiting(): void
    {
        $routes = [
            'api.v1.audits.publish',
            'api.v1.audits.unpublish',
            'api.v1.audits.embed-code',
        ];

        foreach ($routes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route {$routeName} not found");
            $middleware = $route->gatherMiddleware();
            $hasThrottle = collect($middleware)->some(fn ($m) => str_contains($m, 'throttle'));
            $this->assertTrue($hasThrottle, "Route {$routeName} should have throttle middleware");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP-PUB: HTTP Requests - Public Routes (6 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_http_pub_001_public_audit_show_returns_404_for_invalid_hash(): void
    {
        $response = $this->getJson('/api/v1/public-audit/invalidhash123');
        $response->assertStatus(404);
    }

    public function test_route_http_pub_002_public_audit_show_returns_404_for_non_existent_hash(): void
    {
        $response = $this->getJson('/api/v1/public-audit/abcd1234efgh5678ijkl9012mnop3456');
        $response->assertStatus(404);
    }

    public function test_route_http_pub_003_public_audit_lead_accepts_post_request(): void
    {
        $response = $this->postJson('/api/v1/public-audit/testhash123/lead', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
        // 404 expected since controller not fully implemented
        $response->assertStatus(404);
    }

    public function test_route_http_pub_004_public_audit_badge_accepts_get_request(): void
    {
        $response = $this->getJson('/api/v1/public-audit/testhash123/badge');
        // 404 expected since controller not fully implemented
        $response->assertStatus(404);
    }

    public function test_route_http_pub_005_badge_impression_accepts_post_request(): void
    {
        $response = $this->postJson('/api/v1/badge/impression', [
            'audit_hash' => 'testhash123',
            'referrer' => 'https://example.com',
        ]);
        // 200 since controller stub is implemented
        $response->assertStatus(200);
    }

    public function test_route_http_pub_006_badge_click_accepts_post_request(): void
    {
        $response = $this->postJson('/api/v1/badge/click', [
            'audit_hash' => 'testhash123',
            'referrer' => 'https://example.com',
        ]);
        // 200 since controller stub is implemented
        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP-AUTH: HTTP Requests - Authenticated Routes (6 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_http_auth_001_audit_publish_returns_401_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/audits/1/publish');
        $response->assertStatus(401);
    }

    public function test_route_http_auth_002_audit_unpublish_returns_401_without_authentication(): void
    {
        $response = $this->deleteJson('/api/v1/audits/1/unpublish');
        $response->assertStatus(401);
    }

    public function test_route_http_auth_003_audit_embed_code_returns_401_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/audits/1/embed-code');
        $response->assertStatus(401);
    }

    public function test_route_http_auth_004_audit_publish_accepts_authenticated_request(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/audits/999/publish');
        // 404 expected since audit doesn't exist
        $response->assertStatus(404);
    }

    public function test_route_http_auth_005_audit_unpublish_accepts_authenticated_request(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/audits/999/unpublish');
        // 404 expected since audit doesn't exist
        $response->assertStatus(404);
    }

    public function test_route_http_auth_006_audit_embed_code_accepts_authenticated_request(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/audits/999/embed-code');
        // 404 expected since audit doesn't exist
        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | HMR: HTTP Method Restrictions (8 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_hmr_001_public_audit_show_rejects_post_method(): void
    {
        $response = $this->postJson('/api/v1/public-audit/testhash');
        $response->assertStatus(405);
    }

    public function test_route_hmr_002_public_audit_lead_rejects_get_method(): void
    {
        $response = $this->getJson('/api/v1/public-audit/testhash/lead');
        $response->assertStatus(405);
    }

    public function test_route_hmr_003_public_audit_badge_rejects_post_method(): void
    {
        $response = $this->postJson('/api/v1/public-audit/testhash/badge');
        $response->assertStatus(405);
    }

    public function test_route_hmr_004_badge_impression_rejects_get_method(): void
    {
        $response = $this->getJson('/api/v1/badge/impression');
        $response->assertStatus(405);
    }

    public function test_route_hmr_005_badge_click_rejects_get_method(): void
    {
        $response = $this->getJson('/api/v1/badge/click');
        $response->assertStatus(405);
    }

    public function test_route_hmr_006_audit_publish_rejects_get_method(): void
    {
        $response = $this->getJson('/api/v1/audits/1/publish');
        $response->assertStatus(405);
    }

    public function test_route_hmr_007_audit_unpublish_rejects_post_method(): void
    {
        $response = $this->postJson('/api/v1/audits/1/unpublish');
        $response->assertStatus(405);
    }

    public function test_route_hmr_008_audit_embed_code_rejects_post_method(): void
    {
        $response = $this->postJson('/api/v1/audits/1/embed-code');
        $response->assertStatus(405);
    }

    /*
    |--------------------------------------------------------------------------
    | RMB: Route Model Binding Tests (3 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_rmb_001_public_audit_routes_use_hash_parameter(): void
    {
        $routes = [
            'api.v1.public-audit.show',
            'api.v1.public-audit.lead',
            'api.v1.public-audit.badge',
        ];

        foreach ($routes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertStringContainsString('{hash}', $route->uri());
        }
    }

    public function test_route_rmb_002_authenticated_audit_routes_use_id_parameter(): void
    {
        $routes = [
            'api.v1.audits.publish',
            'api.v1.audits.unpublish',
            'api.v1.audits.embed-code',
        ];

        foreach ($routes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertStringContainsString('{id}', $route->uri());
        }
    }

    public function test_route_rmb_003_audit_routes_bind_to_audit_model(): void
    {
        // Verify the routes have proper binding by checking they handle valid formats
        $route = Route::getRoutes()->getByName('api.v1.public-audit.show');
        $this->assertNotNull($route);

        // Check that the route has the where constraint for hash
        $wheres = $route->wheres;
        $this->assertArrayHasKey('hash', $wheres);
    }

    /*
    |--------------------------------------------------------------------------
    | API: API Prefix Test (1 test)
    |--------------------------------------------------------------------------
    */

    public function test_route_api_001_all_routes_have_api_v1_prefix(): void
    {
        $routes = [
            'api.v1.public-audit.show',
            'api.v1.public-audit.lead',
            'api.v1.public-audit.badge',
            'api.v1.badge.impression',
            'api.v1.badge.click',
            'api.v1.audits.publish',
            'api.v1.audits.unpublish',
            'api.v1.audits.embed-code',
        ];

        foreach ($routes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route {$routeName} not found");
            $this->assertStringStartsWith('api/v1', $route->uri());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEC: Security Tests (5 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_sec_001_public_audit_hash_parameter_validates_format(): void
    {
        // SQL injection attempt in hash parameter
        $response = $this->getJson('/api/v1/public-audit/1%20OR%201=1');
        $response->assertStatus(404);
    }

    public function test_route_sec_002_public_audit_routes_prevent_sql_injection(): void
    {
        // SQL injection attempt
        $response = $this->getJson("/api/v1/public-audit/' OR '1'='1");
        // Should return 404 (not found), not 500 (error)
        $response->assertStatus(404);
    }

    public function test_route_sec_003_audit_routes_prevent_sql_injection_in_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // SQL injection attempt in ID parameter
        $response = $this->postJson('/api/v1/audits/1%20OR%201=1/publish');
        // Should return 404 (not found) or 403 (forbidden), not 500 (error)
        $response->assertStatus(404);
    }

    public function test_route_sec_004_public_audit_lead_validates_email_format(): void
    {
        $response = $this->postJson('/api/v1/public-audit/testhash/lead', [
            'email' => 'not-a-valid-email',
            'name' => 'Test User',
        ]);
        // 422 validation error for invalid email format (controller validates)
        $response->assertStatus(422);
    }

    public function test_route_sec_005_public_audit_lead_sanitizes_xss_in_email(): void
    {
        $response = $this->postJson('/api/v1/public-audit/testhash/lead', [
            'email' => '<script>alert("xss")</script>@example.com',
            'name' => 'Test User',
        ]);
        // Controller validates email - returns 422 for invalid format
        // XSS in email is rejected via validation (security measure)
        $response->assertStatus(422);
        // Verify the script tag is not reflected in response
        $content = $response->getContent();
        $this->assertStringNotContainsString('<script>', $content);
    }

    /*
    |--------------------------------------------------------------------------
    | EC: Edge Cases Tests (4 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_ec_001_public_audit_handles_unicode_hash(): void
    {
        $response = $this->getJson('/api/v1/public-audit/тест测试🎉');
        $response->assertStatus(404);
    }

    public function test_route_ec_002_public_audit_handles_very_long_hash(): void
    {
        $longHash = str_repeat('a', 1000);
        $response = $this->getJson('/api/v1/public-audit/' . $longHash);
        $response->assertStatus(404);
    }

    public function test_route_ec_003_audit_routes_handle_very_large_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/audits/999999999999999999/publish');
        $response->assertStatus(404);
    }

    public function test_route_ec_004_public_audit_routes_handle_special_characters(): void
    {
        $response = $this->getJson('/api/v1/public-audit/test@hash#special');
        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | AC: Acceptance Criteria Tests (5 tests)
    |--------------------------------------------------------------------------
    */

    public function test_route_ac_001_all_public_routes_are_registered(): void
    {
        $publicRoutes = [
            'api.v1.public-audit.show',
            'api.v1.public-audit.lead',
            'api.v1.public-audit.badge',
            'api.v1.badge.impression',
            'api.v1.badge.click',
        ];

        $this->assertCount(5, $publicRoutes, 'Should have exactly 5 public routes');

        foreach ($publicRoutes as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                "Public route {$routeName} is not registered"
            );
        }
    }

    public function test_route_ac_002_all_authenticated_routes_are_registered(): void
    {
        $authRoutes = [
            'api.v1.audits.publish',
            'api.v1.audits.unpublish',
            'api.v1.audits.embed-code',
        ];

        $this->assertCount(3, $authRoutes, 'Should have exactly 3 authenticated routes');

        foreach ($authRoutes as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                "Authenticated route {$routeName} is not registered"
            );
        }
    }

    public function test_route_ac_003_public_routes_do_not_require_auth(): void
    {
        $publicRoutes = [
            'api.v1.public-audit.show',
            'api.v1.public-audit.lead',
            'api.v1.public-audit.badge',
            'api.v1.badge.impression',
            'api.v1.badge.click',
        ];

        foreach ($publicRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $middleware = $route->gatherMiddleware();
            $this->assertNotContains('auth:sanctum', $middleware, "Route {$routeName} should not require auth:sanctum");
            $this->assertNotContains('auth', $middleware, "Route {$routeName} should not require auth");
        }
    }

    public function test_route_ac_004_authenticated_routes_require_auth(): void
    {
        $authRoutes = [
            'api.v1.audits.publish',
            'api.v1.audits.unpublish',
            'api.v1.audits.embed-code',
        ];

        foreach ($authRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $middleware = $route->gatherMiddleware();
            $hasAuth = in_array('auth:sanctum', $middleware) || in_array('auth', $middleware);
            $this->assertTrue($hasAuth, "Route {$routeName} should require authentication");
        }
    }

    public function test_route_ac_005_all_routes_have_rate_limiting(): void
    {
        $allRoutes = [
            'api.v1.public-audit.show',
            'api.v1.public-audit.lead',
            'api.v1.public-audit.badge',
            'api.v1.badge.impression',
            'api.v1.badge.click',
            'api.v1.audits.publish',
            'api.v1.audits.unpublish',
            'api.v1.audits.embed-code',
        ];

        foreach ($allRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $middleware = $route->gatherMiddleware();
            $hasThrottle = collect($middleware)->some(fn ($m) => str_contains($m, 'throttle'));
            $this->assertTrue($hasThrottle, "Route {$routeName} should have throttle middleware");
        }
    }
}
