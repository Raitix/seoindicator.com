<?php

namespace Tests\Feature\Requests;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;

/**
 * Feature tests for CrawlRequest validation integration.
 *
 * Tests full request validation flow including:
 * - CrawlRequest form request validation
 * - ValidCrawlUrl rule integration
 * - MaxContentSize rule integration
 * - Rate limiting middleware
 * - Authorization
 *
 * @see TASK-22-12
 * @see \App\Http\Requests\CrawlRequest
 */
class CrawlRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Valid Requests (REQ-VALID-*)
    // =========================================================================

    /** @test */
    public function req_valid_01_minimal_valid_request(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        // Controller doesn't exist yet, so we test validation passes
        $response->assertStatus(202);
    }

    /** @test */
    public function req_valid_02_full_valid_request(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'depth' => 2,
                'options' => [
                    'waitForSelector' => '.content',
                    'waitTime' => 5000,
                    'userAgent' => 'CustomBot/1.0',
                    'includeImages' => true,
                    'followRedirects' => false,
                    'maxRedirects' => 3,
                ],
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function req_valid_03_with_project_context(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'project_id' => $this->project->id,
            ]);

        $response->assertStatus(202);
    }

    // =========================================================================
    // SSRF Protection Integration (REQ-SSRF-*)
    // =========================================================================

    /** @test */
    public function req_ssrf_01_localhost_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://localhost',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function req_ssrf_02_private_ip_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://192.168.1.1',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function req_ssrf_03_metadata_endpoint_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://169.254.169.254',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function req_ssrf_04_internal_domain_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://server.internal',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function req_ssrf_05_file_protocol_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'file:///etc/passwd',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function req_ssrf_06_decimal_ip_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://2130706433', // 127.0.0.1 in decimal
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    // =========================================================================
    // Rate Limiting Integration (REQ-RATE-*)
    // =========================================================================

    /** @test */
    public function req_rate_01_within_limit(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function req_rate_02_daily_limit_exceeded(): void
    {
        // Simulate 10 existing jobs today
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->user)
                ->postJson('/api/v1/crawler/crawl', [
                    'url' => "https://example{$i}.com",
                ]);
        }

        // 11th request should be blocked
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example11.com',
            ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function req_rate_03_admin_bypasses_limit(): void
    {
        // Admin should not be rate limited
        for ($i = 0; $i < 15; $i++) {
            $response = $this->actingAs($this->admin)
                ->postJson('/api/v1/crawler/crawl', [
                    'url' => "https://example{$i}.com",
                ]);

            $response->assertStatus(202);
        }
    }

    /** @test */
    public function req_rate_04_rate_limit_headers_present(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    // =========================================================================
    // Authorization Tests (REQ-AUTH-*)
    // =========================================================================

    /** @test */
    public function req_auth_01_unauthenticated_blocked(): void
    {
        $response = $this->postJson('/api/v1/crawler/crawl', [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function req_auth_02_authenticated_allowed(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function req_auth_03_other_users_project_blocked(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'project_id' => $otherProject->id,
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // Validation Error Format (REQ-ERROR-*)
    // =========================================================================

    /** @test */
    public function req_error_01_validation_error_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'not-a-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'url',
                ],
            ]);
    }

    /** @test */
    public function req_error_02_multiple_validation_errors(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'not-a-url',
                'depth' => 5,
                'options' => [
                    'waitTime' => 50000,
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url', 'depth', 'options.waitTime']);
    }

    /** @test */
    public function req_error_03_rate_limit_error_format(): void
    {
        // Exhaust rate limit
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->user)
                ->postJson('/api/v1/crawler/crawl', [
                    'url' => "https://example{$i}.com",
                ]);
        }

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(429)
            ->assertJsonStructure([
                'message',
                'retry_after',
            ]);
    }

    /** @test */
    public function req_error_04_ssrf_error_message(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'http://localhost',
            ]);

        $response->assertStatus(422);

        $errorMessage = $response->json('errors.url.0');
        $this->assertStringContainsString('not allowed', strtolower($errorMessage));
    }

    // =========================================================================
    // Edge Cases (REQ-EDGE-*)
    // =========================================================================

    /** @test */
    public function req_edge_01_empty_request(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function req_edge_02_null_url(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => null,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function req_edge_03_very_long_url(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2070);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => $longUrl,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function req_edge_04_unicode_url(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://münchen.de',
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function req_edge_05_extra_fields_ignored(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
                'extra_field' => 'should be ignored',
                'malicious' => '<script>alert(1)</script>',
            ]);

        $response->assertStatus(202);
    }

    /** @test */
    public function req_edge_06_content_type_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/api/v1/crawler/crawl', [
                'url' => 'https://example.com',
            ], [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);

        // Should still work with different content type
        $response->assertStatus(202);
    }
}
