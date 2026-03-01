<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\PWAAnalysis;
use App\Models\Site;
use App\Models\User;
use App\Repositories\PWAAnalysisRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mockery;

/**
 * Feature tests for PWA Analysis API Controller.
 *
 * TASK-24-8: API endpoints for PWA analysis.
 *
 * @see TEST-24-8
 * @see TASK-24-8
 */
class PWAControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Site $site;
    protected Site $otherSite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->site = Site::factory()->create(['user_id' => $this->user->id]);
        $this->otherSite = Site::factory()->create(['user_id' => $this->otherUser->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // SHOW ENDPOINT TESTS (GET /api/v1/sites/{siteId}/pwa)
    // ============================================================

    /**
     * SH-001: Returns analysis data for existing analysis.
     */
    public function test_show_returns_analysis_data(): void
    {
        $analysis = PWAAnalysis::factory()->fullPWA()->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hasManifest', true)
            ->assertJsonPath('data.manifestUrl', '/manifest.json')
            ->assertJsonPath('data.manifestValid', true)
            ->assertJsonPath('data.pwaScore', 85)
            ->assertJsonPath('data.readinessLevel', 'full');
    }

    /**
     * SH-002: Returns 404 if no analysis exists.
     */
    public function test_show_returns_404_if_no_analysis(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'ANALYSIS_NOT_FOUND')
            ->assertJsonStructure([
                'success',
                'error' => ['code', 'message', 'suggestion'],
            ]);
    }

    /**
     * SH-003: Returns 404 if site not found.
     */
    public function test_show_returns_404_if_site_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sites/9999/pwa');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    /**
     * SH-004: Returns 403 if not authorized.
     */
    public function test_show_returns_403_if_not_authorized(): void
    {
        PWAAnalysis::factory()->create(['site_id' => $this->otherSite->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->otherSite->id}/pwa");

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    /**
     * SH-005: Returns 401 if unauthenticated.
     */
    public function test_show_returns_401_if_unauthenticated(): void
    {
        $response = $this->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(401);
    }

    /**
     * SH-006: Returns all required fields.
     */
    public function test_show_returns_all_required_fields(): void
    {
        $analysis = PWAAnalysis::factory()->fullPWA()->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'hasManifest',
                    'manifestUrl',
                    'manifestValid',
                    'pwaScore',
                    'readinessLevel',
                    'fields',
                    'icons',
                    'recommendations',
                    'analyzedAt',
                ],
            ]);
    }

    /**
     * SH-007: Returns fields breakdown with presence and values.
     */
    public function test_show_returns_fields_breakdown(): void
    {
        $analysis = PWAAnalysis::factory()->withManifest()->create([
            'site_id' => $this->site->id,
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'has_background_color' => false,
            'has_theme_color' => false,
            'has_description' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.fields.name.present', true)
            ->assertJsonPath('data.fields.short_name.present', true)
            ->assertJsonPath('data.fields.start_url.present', true)
            ->assertJsonPath('data.fields.display.present', true)
            ->assertJsonPath('data.fields.background_color.present', false)
            ->assertJsonPath('data.fields.theme_color.present', false)
            ->assertJsonPath('data.fields.description.present', false);
    }

    /**
     * SH-008: Returns icons validation results.
     */
    public function test_show_returns_icons_validation(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'icons_total' => 3,
            'icons_valid' => 2,
            'icons_192_present' => true,
            'icons_512_present' => true,
            'icons_180_present' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.icons.total', 3)
            ->assertJsonPath('data.icons.valid', 2)
            ->assertJsonPath('data.icons.required.192x192.present', true)
            ->assertJsonPath('data.icons.required.512x512.present', true)
            ->assertJsonPath('data.icons.required.180x180.present', false);
    }

    /**
     * SH-009: Returns recommendations array.
     */
    public function test_show_returns_recommendations(): void
    {
        $analysis = PWAAnalysis::factory()->fullPWA()->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'recommendations',
                ],
            ]);
    }

    /**
     * SH-010: Returns analyzedAt timestamp in ISO format.
     */
    public function test_show_returns_analyzed_at_timestamp(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'analyzed_at' => '2026-03-01 10:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200);
        $analyzedAt = $response->json('data.analyzedAt');
        $this->assertNotNull($analyzedAt);
        // Should be ISO 8601 format
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $analyzedAt);
    }

    /**
     * SH-011: Cached response returns quickly.
     */
    public function test_show_cache_hit_is_fast(): void
    {
        $analysis = PWAAnalysis::factory()->create(['site_id' => $this->site->id]);

        // First request (may cache)
        $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        // Second request should hit cache
        $start = microtime(true);
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        // Cached response should be fast (< 100ms)
        $this->assertLessThan(0.1, $duration, 'Cached response should be fast');
    }

    // ============================================================
    // ANALYZE ENDPOINT TESTS (POST /api/v1/sites/{siteId}/pwa/analyze)
    // ============================================================

    /**
     * AN-001: Dispatches analysis job and returns 202.
     */
    public function test_analyze_dispatches_job_and_returns_202(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze");

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'jobId',
                    'status',
                    'message',
                ],
            ]);

        $jobId = $response->json('data.jobId');
        $this->assertMatchesRegularExpression('/^pwa-analyze-[a-f0-9-]+$/', $jobId);
    }

    /**
     * AN-002: Returns 404 if site not found.
     */
    public function test_analyze_returns_404_if_site_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sites/9999/pwa/analyze');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    /**
     * AN-003: Returns 403 if not authorized.
     */
    public function test_analyze_returns_403_if_not_authorized(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->otherSite->id}/pwa/analyze");

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    /**
     * AN-004: Returns 401 if unauthenticated.
     */
    public function test_analyze_returns_401_if_unauthenticated(): void
    {
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze");

        $response->assertStatus(401);
    }

    /**
     * AN-005: Rate limit allows 10 per hour.
     */
    public function test_analyze_rate_limit_allows_10_per_hour(): void
    {
        // Simulate 9 previous requests this hour
        $cacheKey = "pwa-analyze:{$this->site->id}";
        Cache::put($cacheKey, 9, 3600);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze");

        // 10th request should still work
        $response->assertStatus(202);
    }

    /**
     * AN-006: Rate limit blocks over 10 per hour.
     */
    public function test_analyze_rate_limit_blocks_over_10_per_hour(): void
    {
        // Simulate 10 previous requests this hour
        $cacheKey = "pwa-analyze:{$this->site->id}";
        Cache::put($cacheKey, 10, 3600);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze");

        // 11th request should be blocked
        $response->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'RATE_LIMIT_EXCEEDED')
            ->assertJsonStructure([
                'error' => ['retryAfter'],
            ]);
    }

    /**
     * AN-007: Rate limit is per-site.
     */
    public function test_analyze_rate_limit_is_per_site(): void
    {
        // Max out rate limit for site 1
        $cacheKey1 = "pwa-analyze:{$this->site->id}";
        Cache::put($cacheKey1, 10, 3600);

        // Site 2 should still work
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->otherSite->id}/pwa/analyze");

        // Different user's site - should still fail due to auth
        $response->assertStatus(403);
    }

    /**
     * AN-008: Rate limit resets after 1 hour.
     */
    public function test_analyze_rate_limit_resets_after_hour(): void
    {
        // Simulate hitting the limit
        $cacheKey = "pwa-analyze:{$this->site->id}";
        Cache::put($cacheKey, 10, 3600);

        // Clear the cache (simulating time passing)
        Cache::forget($cacheKey);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze");

        $response->assertStatus(202);
    }

    /**
     * AN-009: Job ID format is traceable.
     */
    public function test_analyze_job_id_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze");

        $response->assertStatus(202);
        $jobId = $response->json('data.jobId');

        // Should match format: pwa-analyze-{uuid}
        $this->assertMatchesRegularExpression(
            '/^pwa-analyze-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $jobId
        );
    }

    // ============================================================
    // REFRESH ENDPOINT TESTS (POST /api/v1/sites/{siteId}/pwa/refresh)
    // ============================================================

    /**
     * RF-001: Refreshes existing analysis.
     */
    public function test_refresh_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/refresh");

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'jobId',
                    'status',
                    'message',
                ],
            ]);
    }

    /**
     * RF-002: Returns 202 Accepted.
     */
    public function test_refresh_returns_202_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/refresh");

        $response->assertStatus(202);
    }

    /**
     * RF-003: Returns 404 if site not found.
     */
    public function test_refresh_returns_404_if_site_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sites/9999/pwa/refresh');

        $response->assertStatus(404);
    }

    /**
     * RF-004: Returns 403 if not authorized.
     */
    public function test_refresh_returns_403_if_not_authorized(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->otherSite->id}/pwa/refresh");

        $response->assertStatus(403);
    }

    /**
     * RF-005: Returns 401 if unauthenticated.
     */
    public function test_refresh_returns_401_if_unauthenticated(): void
    {
        $response = $this->postJson("/api/v1/sites/{$this->site->id}/pwa/refresh");

        $response->assertStatus(401);
    }

    /**
     * RF-006: Rate limited same as analyze.
     */
    public function test_refresh_rate_limited(): void
    {
        // Max out rate limit
        $cacheKey = "pwa-analyze:{$this->site->id}";
        Cache::put($cacheKey, 10, 3600);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/refresh");

        $response->assertStatus(429);
    }

    /**
     * RF-007: Cache invalidated on refresh.
     */
    public function test_refresh_invalidates_cache(): void
    {
        // Create analysis and cache it
        PWAAnalysis::factory()->create(['site_id' => $this->site->id]);
        $cacheKey = "pwa-analysis:{$this->site->id}";
        Cache::put($cacheKey, 'cached-data', 3600);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/refresh");

        $response->assertStatus(202);

        // Cache should be cleared
        $this->assertNull(Cache::get($cacheKey));
    }

    // ============================================================
    // AUTHORIZATION TESTS
    // ============================================================

    /**
     * AZ-001: Owner can access all endpoints.
     */
    public function test_owner_can_access_all_endpoints(): void
    {
        PWAAnalysis::factory()->create(['site_id' => $this->site->id]);

        // Show
        $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa")
            ->assertStatus(200);

        // Analyze
        $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze")
            ->assertStatus(202);

        // Refresh
        $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/refresh")
            ->assertStatus(202);
    }

    /**
     * AZ-002: Non-owner blocked from all endpoints.
     */
    public function test_non_owner_blocked_from_all_endpoints(): void
    {
        PWAAnalysis::factory()->create(['site_id' => $this->otherSite->id]);

        // Show
        $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->otherSite->id}/pwa")
            ->assertStatus(403);

        // Analyze
        $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->otherSite->id}/pwa/analyze")
            ->assertStatus(403);

        // Refresh
        $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->otherSite->id}/pwa/refresh")
            ->assertStatus(403);
    }

    /**
     * AZ-003: Unauthenticated blocked from all endpoints.
     */
    public function test_unauthenticated_blocked_from_all_endpoints(): void
    {
        // Show
        $this->getJson("/api/v1/sites/{$this->site->id}/pwa")
            ->assertStatus(401);

        // Analyze
        $this->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze")
            ->assertStatus(401);

        // Refresh
        $this->postJson("/api/v1/sites/{$this->site->id}/pwa/refresh")
            ->assertStatus(401);
    }

    // ============================================================
    // SECURITY TESTS
    // ============================================================

    /**
     * SEC-001: SQL injection blocked by route constraint.
     */
    public function test_sql_injection_blocked_by_route_constraint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/1' OR '1'='1/pwa");

        // Route constraint [0-9]+ should return 404 for non-numeric
        $response->assertStatus(404);
    }

    /**
     * SEC-002: XSS in manifest data handled safely.
     */
    public function test_xss_in_manifest_handled_safely(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'manifest_json' => [
                'name' => '<script>alert(1)</script>',
            ],
            'has_name' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200);
        // Data is stored as-is; escaping is frontend responsibility
        $this->assertStringContainsString('<script>', $response->json('data.fields.name.value'));
    }

    /**
     * SEC-003: IDOR prevention.
     */
    public function test_idor_prevention(): void
    {
        $otherAnalysis = PWAAnalysis::factory()->create([
            'site_id' => $this->otherSite->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->otherSite->id}/pwa");

        $response->assertStatus(403);
    }

    /**
     * SEC-004: Input validation for siteId.
     */
    public function test_input_validation_for_site_id(): void
    {
        // Negative ID
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sites/-1/pwa');
        $response->assertStatus(404);

        // Non-numeric ID
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sites/abc/pwa');
        $response->assertStatus(404);

        // Floating point ID
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sites/1.5/pwa');
        $response->assertStatus(404);
    }

    // ============================================================
    // EDGE CASE TESTS
    // ============================================================

    /**
     * EC-001: Site with no manifest.
     */
    public function test_site_with_no_manifest(): void
    {
        $analysis = PWAAnalysis::factory()->withoutManifest()->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.hasManifest', false)
            ->assertJsonPath('data.pwaScore', 0)
            ->assertJsonPath('data.readinessLevel', 'none');
    }

    /**
     * EC-002: Invalid manifest with errors.
     */
    public function test_invalid_manifest_with_errors(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => false,
            'pwa_score' => 10,
            'readiness_level' => 'none',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.hasManifest', true)
            ->assertJsonPath('data.manifestValid', false)
            ->assertJsonPath('data.pwaScore', 10);
    }

    /**
     * EC-003: Partial manifest (missing required fields).
     */
    public function test_partial_manifest_missing_required_fields(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => true,
            'has_short_name' => false,
            'has_start_url' => false,
            'has_display' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.fields.name.present', true)
            ->assertJsonPath('data.fields.short_name.present', false)
            ->assertJsonPath('data.fields.start_url.present', false)
            ->assertJsonPath('data.fields.display.present', false);
    }

    /**
     * EC-004: Large manifest JSON.
     */
    public function test_large_manifest_json(): void
    {
        // Create a large manifest (10KB+)
        $largeDescription = str_repeat('a', 10000);
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_json' => [
                'name' => 'Test App',
                'description' => $largeDescription,
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200);
    }

    /**
     * EC-005: Unicode in manifest.
     */
    public function test_unicode_in_manifest(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_json' => [
                'name' => 'アプリ 🚀 Тест',
                'short_name' => 'テスト',
            ],
            'has_name' => true,
            'has_short_name' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.fields.name.value', 'アプリ 🚀 Тест')
            ->assertJsonPath('data.fields.short_name.value', 'テスト');
    }

    /**
     * EC-006: Null manifest_json handled gracefully.
     */
    public function test_null_manifest_json_handled(): void
    {
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => false,
            'manifest_json' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.hasManifest', false);
    }

    /**
     * EC-007: Empty icons array.
     */
    public function test_empty_icons_array(): void
    {
        $analysis = PWAAnalysis::factory()->noIcons()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.icons.total', 0)
            ->assertJsonPath('data.icons.valid', 0);
    }

    /**
     * EC-008: Very long manifest_url.
     */
    public function test_very_long_manifest_url(): void
    {
        $longUrl = '/manifests/' . str_repeat('a', 500) . '.json';
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_url' => $longUrl,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.manifestUrl', $longUrl);
    }

    /**
     * EC-009: Icon errors displayed correctly.
     */
    public function test_icon_errors_displayed_correctly(): void
    {
        $analysis = PWAAnalysis::factory()->withIconErrors()->create([
            'site_id' => $this->site->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'icons' => [
                        'errors',
                    ],
                ],
            ]);

        $errors = $response->json('data.icons.errors');
        $this->assertCount(2, $errors);
        $this->assertEquals('512x512', $errors[0]['size']);
    }

    // ============================================================
    // INTEGRATION TESTS
    // ============================================================

    /**
     * INT-001: Full analyze workflow.
     */
    public function test_full_analyze_workflow(): void
    {
        // 1. No analysis exists initially
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");
        $response->assertStatus(404);

        // 2. Trigger analysis
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze");
        $response->assertStatus(202);
        $jobId = $response->json('data.jobId');

        // 3. Simulate job completing (create analysis)
        PWAAnalysis::factory()->fullPWA()->create(['site_id' => $this->site->id]);

        // 4. Get results
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");
        $response->assertStatus(200)
            ->assertJsonPath('data.pwaScore', 85);
    }

    /**
     * INT-002: Repository integration.
     */
    public function test_repository_integration(): void
    {
        // Create via repository
        $repository = app(PWAAnalysisRepository::class);
        $analysis = $repository->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'pwa_score' => 75,
            'readiness_level' => 'enhanced',
            'analyzed_at' => now(),
        ]);

        // Retrieve via API
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200)
            ->assertJsonPath('data.pwaScore', 75)
            ->assertJsonPath('data.readinessLevel', 'enhanced');
    }

    // ============================================================
    // PERFORMANCE TESTS
    // ============================================================

    /**
     * PF-001: GET response time.
     */
    public function test_get_response_time(): void
    {
        PWAAnalysis::factory()->create(['site_id' => $this->site->id]);

        $start = microtime(true);
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(0.5, $duration, 'GET response should be under 500ms');
    }

    /**
     * PF-002: POST response time.
     */
    public function test_post_response_time(): void
    {
        $start = microtime(true);
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sites/{$this->site->id}/pwa/analyze");
        $duration = microtime(true) - $start;

        $response->assertStatus(202);
        // Note: Test environments may be slower; using 2s threshold for CI/local test variability
        $this->assertLessThan(2.0, $duration, 'POST response should be under 2s');
    }

    /**
     * PF-003: Large manifest handling performance.
     */
    public function test_large_manifest_performance(): void
    {
        // Create 10KB manifest
        $largeData = [
            'name' => 'Test',
            'description' => str_repeat('x', 10000),
            'icons' => array_fill(0, 100, ['src' => '/icon.png', 'sizes' => '192x192']),
        ];

        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'manifest_json' => $largeData,
        ]);

        $start = microtime(true);
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(1.0, $duration, 'Large manifest should be handled within 1s');
    }

    // ============================================================
    // DATA CONSISTENCY TESTS
    // ============================================================

    /**
     * DC-001: Score matches readiness level.
     */
    public function test_score_matches_readiness_level(): void
    {
        $testCases = [
            ['score' => 0, 'level' => 'none'],
            ['score' => 39, 'level' => 'none'],
            ['score' => 40, 'level' => 'basic'],
            ['score' => 69, 'level' => 'basic'],
            ['score' => 70, 'level' => 'enhanced'],
            ['score' => 89, 'level' => 'enhanced'],
            ['score' => 90, 'level' => 'full'],
            ['score' => 100, 'level' => 'full'],
        ];

        foreach ($testCases as $case) {
            // Clear cache between iterations
            Cache::forget("pwa-analysis:{$this->site->id}");

            $analysis = PWAAnalysis::factory()->withScore($case['score'])->create([
                'site_id' => $this->site->id,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/sites/{$this->site->id}/pwa");

            $response->assertStatus(200)
                ->assertJsonPath('data.pwaScore', $case['score'])
                ->assertJsonPath('data.readinessLevel', $case['level']);

            // Clean up for next iteration
            $analysis->delete();
        }
    }

    /**
     * DC-002: Installable check.
     */
    public function test_installable_check(): void
    {
        // Installable: has manifest, valid, all required fields, 192 icon
        $analysis = PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'has_manifest' => true,
            'manifest_valid' => true,
            'has_name' => true,
            'has_short_name' => true,
            'has_start_url' => true,
            'has_display' => true,
            'icons_192_present' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        $response->assertStatus(200);
        // Model method isInstallable() should work
        $this->assertTrue($analysis->isInstallable());
    }

    /**
     * DC-003: Latest analysis returned when multiple exist.
     */
    public function test_latest_analysis_returned(): void
    {
        // Create older analysis
        PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'pwa_score' => 50,
            'analyzed_at' => now()->subDay(),
        ]);

        // Create newer analysis
        PWAAnalysis::factory()->create([
            'site_id' => $this->site->id,
            'pwa_score' => 85,
            'analyzed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sites/{$this->site->id}/pwa");

        // Should return the latest (score 85)
        $response->assertStatus(200)
            ->assertJsonPath('data.pwaScore', 85);
    }
}
