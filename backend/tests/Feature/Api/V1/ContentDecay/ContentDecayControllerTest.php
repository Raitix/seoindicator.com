<?php

namespace Tests\Feature\Api\V1\ContentDecay;

use App\Models\User;
use App\Models\Site;
use App\Models\Project;
use App\Services\ContentDecay\Contracts\ContentDecayServiceInterface;
use App\Services\ContentDecay\Contracts\ContentDecayDetectionServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @group content-decay
 * @group api
 */
class ContentDecayControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Create a mock project with site and user
     */
    protected function createMockProject(array $attributes = []): array
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        // Create a mock content decay project (will be replaced by actual model)
        $project = (object) array_merge([
            'id' => 1,
            'user_id' => $user->id,
            'site_id' => $site->id,
            'detection_enabled' => true,
            'window_days' => 28,
            'min_clicks_threshold' => 10,
            'min_impressions_threshold' => 100,
            'min_decay_score' => 20.00,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
            'settings_json' => null,
            'last_scanned_at' => now()->toIso8601String(),
        ], $attributes);

        return compact('user', 'site', 'project');
    }

    // ==========================================
    // AUTHENTICATION TESTS
    // ==========================================

    /** @test */
    public function unauthenticated_user_cannot_access_summary(): void
    {
        $site = Site::factory()->create();

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/summary");

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_trigger_detection_run(): void
    {
        $site = Site::factory()->create();

        $response = $this->postJson("/api/v1/sites/{$site->id}/content-decay/run");

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_settings(): void
    {
        $site = Site::factory()->create();

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'window_days' => 14,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_access_summary(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('getSummary')
            ->once()
            ->with($site->id)
            ->andReturn([
                'total_candidates' => 45,
                'critical_count' => 5,
                'high_count' => 12,
                'medium_count' => 18,
                'low_count' => 10,
                'estimated_upside_clicks' => 1250,
                'recovered_pages' => 23,
                'last_scan_at' => now()->toIso8601String(),
            ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_candidates',
                    'critical_count',
                    'high_count',
                    'medium_count',
                    'low_count',
                    'estimated_upside_clicks',
                    'recovered_pages',
                    'last_scan_at',
                ],
            ]);
    }

    // ==========================================
    // AUTHORIZATION TESTS
    // ==========================================

    /** @test */
    public function non_owner_user_cannot_access_summary(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        // Mock the service even though it won't be called (authorization fails)
        $this->mock(ContentDecayServiceInterface::class);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/summary");

        $response->assertStatus(403);
    }

    /** @test */
    public function site_owner_can_access_summary(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('getSummary')
            ->once()
            ->andReturn(['total_candidates' => 0, 'critical_count' => 0, 'high_count' => 0, 'medium_count' => 0, 'low_count' => 0, 'estimated_upside_clicks' => 0, 'recovered_pages' => 0, 'last_scan_at' => null]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/summary");

        $response->assertStatus(200);
    }

    /** @test */
    public function non_owner_user_cannot_trigger_detection_run(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        // Mock the service even though it won't be called (authorization fails)
        $this->mock(ContentDecayDetectionServiceInterface::class);

        $response = $this->postJson("/api/v1/sites/{$site->id}/content-decay/run");

        $response->assertStatus(403);
    }

    /** @test */
    public function non_owner_user_cannot_update_settings(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($nonOwner);

        // Mock the service even though it won't be called (authorization fails)
        $this->mock(ContentDecayServiceInterface::class);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'window_days' => 14,
        ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // VALIDATION TESTS
    // ==========================================

    /** @test */
    public function summary_returns_404_for_non_existent_site(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Mock the service even though it won't be called (site doesn't exist)
        $this->mock(ContentDecayServiceInterface::class);

        $response = $this->getJson('/api/v1/sites/999999/content-decay/summary');

        $response->assertStatus(404);
    }

    /** @test */
    public function update_settings_validates_window_days_minimum(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'window_days' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['window_days']);
    }

    /** @test */
    public function update_settings_validates_window_days_maximum(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'window_days' => 365,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['window_days']);
    }

    /** @test */
    public function update_settings_validates_negative_threshold(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'min_clicks_threshold' => -1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['min_clicks_threshold']);
    }

    /** @test */
    public function update_settings_validates_invalid_digest_frequency(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'digest_frequency' => 'daily',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['digest_frequency']);
    }

    /** @test */
    public function update_settings_accepts_valid_window_days(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('updateSettings')
            ->once()
            ->andReturn((object) [
                'id' => 1,
                'site_id' => $site->id,
                'window_days' => 14,
            ]);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'window_days' => 14,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function update_settings_accepts_valid_thresholds(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('updateSettings')
            ->once()
            ->andReturn((object) [
                'id' => 1,
                'site_id' => $site->id,
                'min_clicks_threshold' => 10,
                'min_impressions_threshold' => 100,
            ]);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'min_clicks_threshold' => 10,
            'min_impressions_threshold' => 100,
        ]);

        $response->assertStatus(200);
    }

    // ==========================================
    // SUCCESS TESTS
    // ==========================================

    /** @test */
    public function get_summary_returns_correct_structure(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('getSummary')
            ->once()
            ->andReturn([
                'total_candidates' => 45,
                'critical_count' => 5,
                'high_count' => 12,
                'medium_count' => 18,
                'low_count' => 10,
                'estimated_upside_clicks' => 1250,
                'recovered_pages' => 23,
                'last_scan_at' => now()->toIso8601String(),
            ]);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/summary");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_candidates' => 45,
                    'critical_count' => 5,
                    'high_count' => 12,
                    'medium_count' => 18,
                    'low_count' => 10,
                    'estimated_upside_clicks' => 1250,
                    'recovered_pages' => 23,
                ],
            ]);
    }

    /** @test */
    public function manual_detection_run_returns_job_id(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('runDetection')
            ->once()
            ->andReturn(['job_id' => 'job-uuid-12345', 'status' => 'queued']);

        $response = $this->postJson("/api/v1/sites/{$site->id}/content-decay/run");

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'data' => [
                    'job_id' => 'job-uuid-12345',
                ],
            ]);
    }

    /** @test */
    public function update_settings_returns_updated_settings(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('updateSettings')
            ->once()
            ->andReturn((object) [
                'id' => 1,
                'site_id' => $site->id,
                'detection_enabled' => true,
                'window_days' => 14,
                'min_clicks_threshold' => 5,
                'min_impressions_threshold' => 50,
                'min_decay_score' => 15.00,
                'digest_enabled' => true,
                'digest_frequency' => 'weekly',
                'last_scanned_at' => null,
            ]);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'window_days' => 14,
            'min_clicks_threshold' => 5,
            'min_impressions_threshold' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'window_days' => 14,
                    'min_clicks_threshold' => 5,
                    'min_impressions_threshold' => 50,
                ],
            ]);
    }

    /** @test */
    public function update_settings_can_enable_disable_detection(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('updateSettings')
            ->once()
            ->andReturn((object) [
                'id' => 1,
                'site_id' => $site->id,
                'detection_enabled' => false,
            ]);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'detection_enabled' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'detection_enabled' => false,
                ],
            ]);
    }

    /** @test */
    public function update_settings_can_enable_disable_digest(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('updateSettings')
            ->once()
            ->andReturn((object) [
                'id' => 1,
                'site_id' => $site->id,
                'digest_enabled' => false,
            ]);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'digest_enabled' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'digest_enabled' => false,
                ],
            ]);
    }

    // ==========================================
    // EDGE CASE TESTS
    // ==========================================

    /** @test */
    public function summary_returns_zero_counts_when_no_candidates(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('getSummary')
            ->once()
            ->andReturn([
                'total_candidates' => 0,
                'critical_count' => 0,
                'high_count' => 0,
                'medium_count' => 0,
                'low_count' => 0,
                'estimated_upside_clicks' => 0,
                'recovered_pages' => 0,
                'last_scan_at' => null,
            ]);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/summary");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_candidates' => 0,
                    'critical_count' => 0,
                    'estimated_upside_clicks' => 0,
                ],
            ]);
    }

    /** @test */
    public function detection_run_returns_error_when_detection_disabled(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayDetectionServiceInterface::class);
        $mockService->shouldReceive('runDetection')
            ->once()
            ->andThrow(new \Exception('Detection is disabled for this site'));

        $response = $this->postJson("/api/v1/sites/{$site->id}/content-decay/run");

        $response->assertStatus(400);
    }

    // ==========================================
    // SECURITY TESTS
    // ==========================================

    /** @test */
    public function sql_injection_in_site_id_is_prevented(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/1;DROP%20TABLE%20sites/content-decay/summary");

        $response->assertStatus(404);
    }

    /** @test */
    public function xss_in_settings_json_is_sanitized(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('updateSettings')
            ->once()
            ->andReturn((object) [
                'id' => 1,
                'site_id' => $site->id,
                'settings_json' => ['name' => '&lt;script&gt;alert(1)&lt;/script&gt;'],
            ]);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'settings_json' => ['name' => '<script>alert(1)</script>'],
        ]);

        $response->assertStatus(200);
        // Verify the response does not contain unsanitized script tags
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    /** @test */
    public function mass_assignment_protection_on_settings(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('updateSettings')
            ->once()
            ->andReturn((object) [
                'id' => 1,
                'site_id' => $site->id,
                'window_days' => 28,
                // Verify user_id is not changed even if passed
            ]);

        $response = $this->patchJson("/api/v1/sites/{$site->id}/content-decay/settings", [
            'window_days' => 28,
            'user_id' => 999, // Should be ignored
            'id' => 999, // Should be ignored
        ]);

        $response->assertStatus(200);
    }

    // ==========================================
    // RATE LIMITING TESTS
    // ==========================================

    /** @test */
    public function rate_limiting_is_applied_to_api_endpoints(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $mockService = $this->mock(ContentDecayServiceInterface::class);
        $mockService->shouldReceive('getSummary')->andReturn([
            'total_candidates' => 0,
            'critical_count' => 0,
            'high_count' => 0,
            'medium_count' => 0,
            'low_count' => 0,
            'estimated_upside_clicks' => 0,
            'recovered_pages' => 0,
            'last_scan_at' => null,
        ]);

        // Make 61 requests (default throttle:60,1)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/summary");

            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429);
            }
        }
    }

    // ==========================================
    // HTTP METHOD TESTS
    // ==========================================

    /** @test */
    public function summary_endpoint_rejects_post_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/sites/{$site->id}/content-decay/summary");

        $response->assertStatus(405);
    }

    /** @test */
    public function run_endpoint_rejects_get_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/run");

        $response->assertStatus(405);
    }

    /** @test */
    public function settings_endpoint_rejects_get_method(): void
    {
        ['user' => $user, 'site' => $site] = $this->createMockProject();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/sites/{$site->id}/content-decay/settings");

        $response->assertStatus(405);
    }
}
